var Job = {
    uploadedFiles: [],
    filesUploading: 0,

    init: function() {
        $("#view-sandbox").click(function() {
            window.open(sandbox_url, '_blank');
        });

        $('#statusCombo, #project_id').chosen({
            width: '200px'
        });

        if (action == 'edit') {
            $('select[name="runner"], select[name="status"], select[name="assigned"]').chosen({
                width: '140px',
                disable_search_threshold: 10
            });
        }

        if (status_error) {
            openNotifyOverlay(status_error, false);
        }
        applyPopupBehavior();

        Job.showUniquePageViews();

        $("#tweet-link").click(function() {
            var jobid = $(this).data('jobid');
            var jobsummary = $(this).data('jobsummary');
            var message = 'Contract job: "' + jobsummary + '" http://worklist.net/' + jobid;

            // Proper centering with dualscreen implemented with help from http://www.xtf.dk/2011/08/center-new-popup-window-even-on.html
            var dualScreenLeft = window.screenLeft != undefined ? window.screenLeft : screen.left;
            var dualScreenTop = window.screenTop != undefined ? window.screenTop : screen.top;

            var windowWidth = window.innerWidth ? window.innerWidth :
                              (document.documentElement.clientWidth ? document.documentElement.clientWidth : screen.width);
            var windowHeight = window.innerHeight ? window.innerHeight :
                               (document.documentElement.clientHeight ? document.documentElement.clientHeight : screen.height);
            var popupWidth = 550;
            var popupHeight = 260;
            var left = ((windowWidth / 2) - (popupWidth / 2)) + dualScreenLeft;
            var top = ((windowHeight / 2) - (popupHeight / 2)) + dualScreenTop;

            var opts   = 'status=1' +
                ',width=' + popupWidth +
                ',height=' + popupHeight +
                ',top=' + top +
                ',left=' + left;

            var url = "http://twitter.com/share?text=" + encodeURIComponent(message);
            window.open(url, 'tweetWindow', opts);

            return false;
        });

        if (user_id) {
            $.get('api.php?action=getSkills', function(data) {
                var skillsData = eval(data);
                var autoArgsSkills = autocompleteMultiple('getskills', skillsData);
                $("#skills-edit").bind("keydown", autoArgsSkills.bind);
                $("#skills-edit").autocomplete(autoArgsSkills);
            });
        }
        makeWorkitemTooltip(".worklist-item");

        $('#workitem-form').submit(function() {
            return Job.saveWorkitem();
        });

        //if the page was loaded with request to display userinfo automatically then do it.
        if (userinfotoshow){
            window.location.href='userinfo.php?id=' + userinfotoshow;
        }

        Entries.formatWorklistStatus();

        $.loggedin = (user_id > 0);

        $(".editable").attr("title","Switch to Edit Mode").click(function() {
            window.location.href = "./" + workitem_id + "?action=edit";
        });

        $('.table-bids tbody td > a[href^="./user/"]').click(function(event) {
            event.stopPropagation();
            return true;
        });

        // Reassign runner
        if (canReassignRunner) {
            $('#runnerBox span.changeRunner input[name=changerunner]').click(function() {
                var runner_id = $('#runnerBox span.changeRunner select[name=runner]').val();
                $.ajax({
                    type: 'post',
                    url: 'jsonserver.php',
                    data: {
                        action: 'changeRunner',
                        // to avoid script loading error when not logged
                        userid: user_id,
                        runner: runner_id,
                        workitem: workitem_id
                    },
                    dataType: 'json'
                });
            });
        }

        //-- gets every element who has .iToolTip and sets it's title to values from tooltip.php
        setTimeout(MapToolTips, 800);

       $('#commentform input[name=newcomment]').click(function(event) {
            event.preventDefault();
            if ($.trim($('#commentform textarea[name=comment]').val()).length > 0) {
                Job.postComment();
            }
        });

        $('#commentform input[name=cancel]').addClass('hidden');

        if ($('#is_internal').length) {
            $('#is_internal').on('click', function() {
                $.ajax({
                    type: 'post',
                    url: './job/toggleInternal/' + workitem_id,
                    dataType: 'json',
                    success: function(data) {
                        if (data.success) {
                            // nothing to do here at this stage
                        }
                    }
                });
            });
        }

        $('#following').click(function() {
            $.ajax({
                type: 'post',
                url: './job/toggleFollowing/' + workitem_id,
                dataType: 'json',
                success: function(data) {
                    if (data.success) {
                        isFollowing = !isFollowing;
                        Job.setFollowingText(isFollowing);
                    }
                }
            });
        });

        // journal info accordian
        // flag to say we've not loaded anything in there yet
        $.journalChat = false;

        setTimeout(function(){
            $(".view_bid_id").click();
        }, 500);
        if (user_id) {
            Job.setFollowingText(isFollowing);
        } else {
            $('#followingLogin').html('<a href="./github/login">Login to follow this task.</a>');
        }

        if (user_id) {
            $('.paid-link').click(Job.paidModal);

            $('.wd-link').click(function(e) {
                e.stopPropagation();
                e.preventDefault();
                var fee_id = $(this).attr('id').substr(3);
                $('#withdraw .fee_id').val(fee_id);
                $('#withdraw').submit();
            });

            $('tr.row-bidlist-live').click(function() {
                $.metadata.setType("elem", "script");
                var bidData = $(this).metadata();
                if (!bidData.id) {
                    return; // row hasn't bid data attached so user isn't either bidder or runner
                }
                var showEditButton = (bidData.bidder_id == user_id) && !hasAcceptedBids,
                    showWithdrawButton = showWithdrawOrDeclineButtons && (bidData.bidder_id == user_id),
                    showDeclineButton = (is_project_runner || (is_admin && is_runner)) && bidData.bidder_id != user_id;

                /* check if user comes from bid email link*/
                var referrer = document.referrer
                var current = window.location.search;
                var isMobile = window.matchMedia("only screen and (max-width: 760px)");
                if(referrer != '' && referrer.indexOf("worklist") < 0 && current.indexOf("view_bid") >= 0){
                    var fromMail = true;
                }else{
                    var fromMail = false;
                }
                /* coming from bid email link AND on mobile*/
                if(fromMail && isMobile) {
                    var displayNotes = false;
                }else{
                    var displayNotes = true;
                }



                Utils.modal('bidinfo', {
                    job_id: workitem_id,
                    current_id: userId,
                    bid: bidData,
                    showStatistics: showBidderStatistics,
                    canAccept: showAcceptBidButton,
                    canEdit: showEditButton,
                    canWithdraw: showWithdrawButton,
                    canDecline: showDeclineButton,
                    fromMail: fromMail,
                    isMobile: isMobile.matches,
                    displayNotes: displayNotes,
                    open: function(modal) {
                        if (showAcceptBidButton) {
                            $.ajax({
                                url: './user/budget/' + userId,
                                dataType: 'json',
                                success: function(json) {
                                    if (!json.budget) {
                                        return;
                                    }
                                    var budgets = json.budget.active;
                                    for(var i = 0; i < budgets.length; i++) {
                                        var budget = budgets[i],
                                            link = $('<a>').attr({
                                                budget: budget.id,
                                                reason: budget.reason,
                                                remaining: budget.remaining
                                            });
                                        link.text(budget.reason + ' ($' + budget.remaining + ')');
                                        var item = $('<li>').append(link);
                                        $('.modal-footer .dropup ul', modal).append(item);
                                    }
                                    $('.modal-footer .dropup ul a', modal).click(function(event) {
                                        var budget = $(this).attr('budget');
                                        $('input[name="budget_id"]', modal).val(budget);
                                        $('button[name="accept"]', modal).html(
                                            '<span>' + $(this).attr('reason') + '</span> ' +
                                            '($' + $(this).attr('remaining') + ') ' +
                                            '<span class="caret"></span>'
                                        );
                                        if (!$('button[name="accept_bid"]', modal).length) {
                                            var confirm = $('<button>')
                                                .attr({
                                                    type: 'submit',
                                                    name: 'accept_bid'
                                                })
                                                .addClass('btn btn-primary')
                                                .text('Confirm Accept');
                                            $('.modal-footer', modal).append(confirm);
                                        }
                                    })
                                }
                            });
                            $('button[name="accept_bid"]', modal).click(function(event) {
                                if (!$('input[name="budget_id"]', modal).val()) {
                                    $('button[name="accept_bid"] + button', modal).click();
                                    return false;
                                }
                            });
                        }
                        $('button[name="edit"]', modal).click(function() {
                            Job.showBidForm(bidData)
                        });
                        $('button[name="withdraw_bid_accept"]', modal).click(function() {
                            Job.showWithdrawBidReason(bidData.id);
                        });
                        $('button[name="decline_bid_accept"]', modal).click(function() {
                            Job.showDeclineBidReason(bidData.id);
                        });
                        $.ajax({
                            url: './user/projectHistory/' + bidData.bidder_id + '/' + project_id,
                            dataType: 'json',
                            success: function(json) {
                                if (!json.jobs) {
                                    return;
                                }
                                var html = '';
                                var project_link = '<a href="./' + project_name + '">' + project_name + '</a>';
                                if (!json.jobs.length) {
                                    html = '<tr><td>No prior jobs for '  + project_link + '</td></tr>';
                                    $('.modal-body > .row > div:first-child tbody', modal).html(html);
                                } else {
                                    for (var i = 0; i < (json.jobs.length > 3 ? 3 : json.jobs.length); i++) {
                                        job = json.jobs[i];
                                        html +=
                                            '<tr>' +
                                            '  <td><a href="./' + job.id + '">#' + job.id + '</a> ' + job.summary + '</td>' +
                                            '</tr>';
                                        $('.modal-body > .row > div:first-child tbody', modal).html(html);
                                    }
                                }
                            }
                        });
                        $.ajax({
                            url: './user/counts/' + bidData.bidder_id,
                            dataType: 'json',
                            success: function(json) {
                                $('.modal-body > .row > div:last-child td:nth-child(1)', modal).html(
                                    '<a href="#">' + json.total_jobs + '</a> / ' +
                                    '<a href="#">' + json.active_jobs + '</a>'
                                );
                                $('.modal-body > .row > div:last-child td:nth-child(1) a:first-child', modal).click(function() {
                                    $(modal).modal('hide');
                                    UserStats.showTotalJobs(1, bidData.nickname);
                                    return false;
                                });
                                $('.modal-body > .row > div:last-child td:nth-child(1) a:last-child', modal).click(function() {
                                    $(modal).modal('hide');
                                    UserStats.showActiveJobs(1, bidData.nickname);
                                    return false;
                                });
                                $('.modal-body > .row > div:last-child td:nth-child(2)', modal).html(
                                    '$' + json.total_earnings + ' / ' +
                                    '<a href="#" id="latest-earnings">' + '$' + json.latest_earnings + '</a>'
                                );
                                $('.modal-body > .row > div:last-child td:nth-child(2) a:last-child', modal).click(function() {
                                    $(modal).modal('hide');
                                    UserStats.showLatestEarnings(1, bidData.nickname);
                                    return false;
                                });
                                $('.modal-body > .row > div:last-child td:nth-child(3)', modal).html(
                                    '$' + json.bonus_total + ' / ' + json.bonus_percent
                                );

                            }
                        });
                    }
                });
            });

            $('tr.row-feelist-live').click(function() {
                $.metadata.setType("elem", "script")
                var feeData = $(this).metadata();

                // row has bid data attached so user is a bidder or a runner
                // - see table creation routine
                if (feeData.id) {
                    Utils.emptyModal({
                        title: 'Fee information',
                        content:
                            '<table class="table table-striped">' +
                            '  <thead>' +
                            '    <tr>' +
                            '      <th>User</th>' +
                            '      <th>Amount</th>' +
                            '      <th>Fee entered</th>' +
                            '      <th>Notes</th>' +
                            '    </tr>' +
                            '  </thead>' +
                            '  <tbody>' +
                            '    <tr>' +
                            '      <td><a href="./user/' + feeData.user_id + '" >' + feeData.nickname + '</a></td>' +
                            '      <td>' + feeData.amount + '</td>' +
                            '      <td>' + feeData.fee_created + '</td>' +
                            '      <td>' + feeData.desc + '</td>' +
                            '    </tr>' +
                            '  </tbody>' +
                            '</table>'
                    });
                }
            });

        }

        $('select[name="quick-status"]').change(function(ev) {
            if ($(this).val() == 'Done' && budget_id == 0) {
                openNotifyOverlay("No budget has been assigned to this job.", true);
                $('.statusComboList').css('display', 'none');
                $('.statusComboList li[val=' +  origStatus + ']').click();
                return false;
            }
            if (job_status == 'Review') {
                job_status == 'Code Review';
            }
            if ($(this).val() != null && $(this).val() != job_status) {
                var html = "<span>Changing status from <strong>" + job_status + "</strong> to <strong>"
                    + $(this).val() +"</strong></span>";

                if ($(this).val() == 'QA Ready') {
                    if(mechanic_id == user_id && promptForReviewUrl) {
                        Job.reviewUrlModal(function() {
                            $('#quick-status form').submit();
                        });
                    }
                } else {
                    openNotifyOverlay(html, false, false);
                    $('#quick-status form').submit();
                }
            }
            return false;
        });

        if (showReviewUrlPopup) {
            $('#edit_review_url').click(function(e){
                Job.reviewUrlModal();
            });
        }

        Job.setCodeReviewEvents();
        Job.initFileUpload();
    },

    initFileUpload: function() {
        var options = {iframe: {url: './file/add/' + workitem_id}};
        var zone = new FileDrop('attachments', options);

        zone.event('send', function (files) {
            files.each(function (file) {
                file.event('done', Job.fileUploadDone);
                file.event('error', Job.fileUploadError);
                file.sendTo('./file/add/' + workitem_id);
                Job.filesUploading++;
                Job.animateUploadSpin();
            });
        });
        zone.multiple(true);

        $('#attachments > label > em').click(function() {
            $('#attachments input.fd-file').click();
        });

        $.ajax({
            type: 'get',
            url: './file/listForJob/' + workitem_id,
            dataType: 'json',
            success: function(data) {
                if (!data.success) {
                    return;
                }
                for (i = 0; i < data.data.length; i++) {
                    var fileData = data.data[i];
                    Job.renderAttachment(fileData);
                }
            }
        });
    },

    fileUploadDone: function(xhr) {
        var fileData = $.parseJSON(xhr.responseText);
        $.ajax({
            url: './file/scan/' + fileData.fileid,
            type: 'POST',
            dataType: "json",
            success: function(json) {
                if (json.success == true) {
                    fileData.url = json.url;
                }
                Job.renderAttachment(fileData);
                Job.fileUploadFinished();
            }
        });

    },

    renderAttachment: function(file) {
        Utils.parseMustache('partials/upload-document', file, function(parsed) {
            $('#attachments > ul').append(parsed);
            $('#attachments li[attachment=' + file.fileid + '] > i').click(Job.removeFile);
            Job.uploadedFiles.push(file.fileid);
        });
    },

    fileUploadError: function(e, xhr) {
        Job.fileUploadFinished();
    },

    fileUploadFinished: function() {
        Job.filesUploading--;
        if (Job.filesUploading == 0) {
            Job.stopUploadSpin();
        }
    },

    removeFile: function(event) {
        var id = parseInt($(this).parent().attr('attachment'));
        $.ajax({
            url: './file/remove/' + id,
            type: 'POST',
            dataType: "json",
            success: function(json) {
                if (json.success == true) {
                    $('#attachments li[attachment=' + id + ']').remove();
                    for (var i = 0; i < Job.uploadedFiles.length; i++) {
                        if (Job.uploadedFiles[i] == id) {
                            Job.uploadedFiles.splice(i, 1);
                        }
                    }
                }
            }
        });
    },

    animateUploadSpin: function() {
        if ($('#attachments > .loading').length) {
            return;
        }
        $('<div>').addClass('loading').prependTo('#attachments');
        var target = $('#attachments > .loading')[0];
        var spinner = new Spinner({
            lines: 9,
            length: 3,
            width: 4,
            radius: 6,
            corners: 1,
            rotate: 12,
            direction: 1,
            color: '#000',
            speed: 1.1,
            trail: 68
          }).spin(target);
    },

    stopUploadSpin: function() {
        $('#attachments > .loading').remove();
    },

    postComment: function() {
        var id = $('#commentform input[name=comment_id]').val();
        var my_comment = $('#commentform textarea[name=comment]').attr("disabled", true).val();
        $('#commentform input[name=newcomment]').val('Posting...').attr("disabled", true).addClass('disable-comment-button');
        $('#commentform input[name=cancel]').addClass('hidden');
        $('#commentform').css({'margin-left':0});

        $.ajax({
            type: 'post',
            url: './' + workitem_id,
            data: {
                job_id: workitem_id,
                worklist_id: workitem_id,
                user_id: user_id,
                newcomment: '1',
                comment: my_comment,
                comment_id: id
            },
            dataType: 'json',
            success: function(data) {
                var depth;
                var elementClass;
                if (data.success) {
                    if (id != '') {
                        elementClass = $('#comment-' + id).attr('class').split(" ");
                        depth = Number(elementClass[0].substring(elementClass[0].indexOf('-') + 1)) + 1;
                    } else {
                        depth = 0;
                    }
                    var newcomment =
                        '<li id="comment-' + data.id + '" class="imOdd depth-' + depth + '">' +
                            '<div class="comment">' +
                                '<a href="./user/' + data.userid + '" >' +
                                    '<img class="picture profile-link" src="' + data.avatar + '" title="Profile Picture - ' + data.nickname + '" />' +
                                '</a>' +
                                '<div class="comment-container">' +
                                    '<div class="comment-info">' +
                                        '<a class="author profile-link" href="./user/' + data.userid +'" >' +
                                            data.nickname +
                                        '</a> ' +
                                        '<span class="date">' +
                                            data.date +
                                        '</span>' +
                                    '</div>' +
                                    '<div class="comment-text">' +
                                         data.comment +
                                    '</div>' +
                                '</div>' +
                            '</div>'
                         '</li>';

                    if (id == '') {
                        $('#commentZone ul').append(newcomment);
                    } else {
                        var cond = new Array();
                        var depthtmp = depth -1;
                        while (depthtmp > -1) {
                            cond.push('li[class*="depth-' + (depthtmp) + '"]');
                            depthtmp--;
                        }
                        $(newcomment).insertAfter($('#comment-' + id).nextUntil(cond.join(',')).andSelf().filter(":last"));
                    }
                    $('#commentform textarea[name=comment]').val('').attr("disabled", false).focus();
                    $('#commentform input[name=newcomment]').val('Comment').attr("disabled", false).removeClass('disable-comment-button');
                }
            }
        });
    },

    showBidConfirmForm: function() {
        return Job.showConfirmForm('bid');
    },

    showFeeConfirmForm: function() {
        return Job.showConfirmForm('fee');
    },

    showConfirmForm: function(i) {
        if (GitHub.validate()) {
            Utils.emptyModal({
                content:
                    "<p>" +
                    "  <strong>I agree that</strong> by adding either a bid or a fee, I accept that" +
                    "  I will not be paid for this work unless " + project_owner +
                    "  and the owner of this job approves payment." +
                    "</p>" +
                    "<p>" +
                    "  Also, by clicking the 'I accept' button, I am contributing all code and work" +
                    "  that I attach to this job or upload to the Worklist servers, including any and" +
                    "  all intellectual property rights related thereto, whether or not I am paid." +
                    "</p>" +
                    "<p>" +
                    "  All intellectual property and code I contribute is solely owned by " +
                    "  " + project_owner + ", and I hereby make all assignments necessary " +
                    "  to accomplish the foregoing." +
                    "</p>",
                buttons: [
                    {
                        content: 'I accept',
                        className: 'btn-primary',
                        dismiss: true
                    }
                ],
                open: function(modal) {
                    $('.btn-primary', modal).on('click', function() {
                        if (i == 'bid') {
                            Job.showBidForm();
                        } else if (i == 'fee') {
                            Job.showFeeForm();
                        }
                    });
                }
            });
        } else {
            GitHub.handleUserConnect();
        }
        return false;
    },

    showBidIneligible: function() {
        return Job.showIneligible('bid');
    },

    showFeeIneligible: function() {
        return Job.showIneligible('fee');
    },

    showIneligible: function(problem) {
        Utils.emptyModal({
            title: 'Your account is ineligible',
            content:
                '<p>' +
                '    <strong>You are not eligible</strong> toplace ' + problem + 's on this item. ' +
                '    Please check your settings and make sure you have:' +
                '</p> ' +
                '<ul>' +
                '    <li>Verified your Paypal address</li>' +
                '    <li>Uploaded your completed <a href="http://www.irs.gov/pub/irs-pdf/fw9.pdf">W-9</a> (US citizens only)</li>' +
                '</ul>' +
                '<br/>',
            buttons: [
                {
                    content: 'I accept',
                    className: 'btn-primary',
                    dismiss: true
                }
            ],
            close: function() {
                window.location = './settings';
            }
        });
    },

    showBidForm: function(bid) {
        var minDoneIn = 7200, // in segs = 2 hours,
            maxDoneIn = 604800; // in segs = 7 days
        bid = typeof(bid) != "undefined" ? bid : {id: '', amount: '', notes: '', time_to_complete: minDoneIn, done_in: '2 hrs'};
        Utils.modal('bidform', {
            editBid: bid.id ? true : false,
            bid: bid,
            jobId: workitem_id,
            currentUserId: userId,
            open: function(modal) {
                $('input[name="done_in"]', modal).after($('<div>').addClass('dragdealer'));
                $('<div>').addClass('handle').text('drag').appendTo('.dragdealer', modal);
                var a = new Dragdealer($('.dragdealer', modal)[0], {
                    speed: 1,
                    animationCallback: function(x, y) {
                        var text = Utils.relativeTime(Math.round(x * (maxDoneIn - minDoneIn)) + minDoneIn, false, false, false, false);
                        $('.dragdealer > .handle').text(text);
                        $('input[name="done_in"]').val(text);
                    }
                });
                $('form', modal).submit(function() {
                    // see http://regexlib.com/REDetails.aspx?regexp_id=318
                    // but without dollar sign 22-NOV-2010 <krumch>
                    var regex_bid = /^(\d{1,3},?(\d{3},?)*\d{3}(\.\d{0,2})?|\d{1,3}(\.\d{0,2})?|\.\d{1,2}?)$/;
                    var regex_date = /^\d{1,2}\/\d{1,2}\/\d{4}$|^\d{1,2}\/\d{1,2}\/\d{4} \d{1,2}:\d{2} (am|pm)$/;

                    var bid_amount = new LiveValidation('bid_amount',{
                        insertAfterWhatNode: $('label[for="bid_amount"] + .input-group', modal)[0],
                        onlyOnSubmit: true
                    });
                    bid_amount.add(Validate.Presence, {
                        failureMessage: "Can't be empty!"
                    });
                    bid_amount.add(Validate.Format, {
                        pattern: regex_bid,
                        failureMessage: "Invalid Input!"
                    });

                    var notes = new LiveValidation('notes', {onlyOnSubmit: true});
                    notes.add( Validate.Presence, {failureMessage: "Can't be empty!" });
                    var massValidationBid = LiveValidation.massValidate([bid_amount, notes]);
                    if (!massValidationBid) {
                        return false;
                    }
                    return true;
                });
            }
        });
    },

    showFeeForm: function() {
        $.get(
            './user/index',
            function(data) {
                Utils.modal('addfee', {
                    job_id: workitem_id,
                    users: data.users,
                    current_nickname: sessionusername,
                    current_id: userId,
                    canFeeOthers: (is_runner || is_project_founder || is_project_runner),
                    open: function() {
                        $('#mechanicFee').chosen();

                         // see http://regexlib.com/REDetails.aspx?regexp_id=318
                        // but without  dollar sign 22-NOV-2010 <krumch>
                        var regex = /^(\d{1,3},?(\d{3},?)*\d{3}(\.\d{0,2})?|\d{1,3}(\.\d{0,2})?|\.\d{1,2}?)$/;
                        var fee_amount = new LiveValidation('fee_amount', {onlyOnSubmit: true});
                            fee_amount.add(Validate.Presence, { failureMessage: "Can't be empty!" });
                            fee_amount.add(Validate.Format, { pattern: regex, failureMessage: "Invalid Input!" });

                        var fee_desc = new LiveValidation('fee_desc', {onlyOnSubmit: true});
                            fee_desc.add( Validate.Presence, { failureMessage: "Can't be empty!" });
                        $('form').submit(function() {
                            var massValidationFee = LiveValidation.massValidate([fee_amount, fee_desc]);
                            if (!massValidationFee) {
                                return false;
                            }
                            return true;
                        });
                    }
                });
            },
            'json'
        );
    },

    showWithdrawBidReason: function(bid_id) {
        var msg =
            '<input type="hidden" name="bid_id" value="' + bid_id + '" />' +
            '<label for="withdraw_bid_reason">Why is this bid being withdrawn?</label>' +
            '<textarea id="withdraw_bid_reason" name="withdraw_bid_reason" class="form-control"></textarea>'
        Utils.emptyFormModal({
            action: './' + workitem_id,
            content: msg,
            buttons: [
                {
                    type: 'submit',
                    name: 'withdraw_bid',
                    content: 'Withdraw Bid',
                    className: 'btn-primary',
                    dismiss: false
                }
            ]
        });
        return false;
    },

    showDeclineBidReason: function(bid_id) {
        var msg =
            '<input type="hidden" name="bid_id" value="' + bid_id + '" />' +
            '<label for="decline_bid_reason">Why is this bid being declined?</label>' +
            '<textarea id="decline_bid_reason" name="decline_bid_reason" class="form-control"></textarea>'
        Utils.emptyFormModal({
            action: './' + workitem_id,
            content: msg,
            buttons: [
                {
                    type: 'submit',
                    name: 'decline_bid',
                    content: 'Decline Bid',
                    className: 'btn-primary',
                    dismiss: false
                }
            ]
        });
        return false;
    },

    saveWorkitem: function() {
        var massValidation;

        var summary = new LiveValidation('summary');
        summary.add( Validate.Presence, {
            failureMessage: "Summary field can't be empty!"
        });

        var editProject = new LiveValidation('project_id');
        editProject.add( Validate.Exclusion, {
            within: [ 'select' ],
            partialMatch: true,
            failureMessage: "You have to choose a project!"
        });
        massValidation = LiveValidation.massValidate([editProject,summary],true);
        if (!massValidation) {
            // Validation failed. We use openNotifyOverlay to display messages
            var errorHtml = createMultipleNotifyHtmlMessages(LiveValidation.massValidateErrors);
            openNotifyOverlay(errorHtml, null, null, true);
            return false;
        }
    },

    AcceptMultipleBidOpen: function() {
        var job_id = workitem_id;
        $.ajax({
            type: 'POST',
            url: 'api.php',
            data: {
                "action": "getMultipleBidList",
                "job_id": job_id
            },
            dataType: 'json',
            success: function(json) {
                if (!json.bids) {
                    return;
                }
                Utils.modal('multiplebidinfo', {
                    job_id: workitem_id,
                    bids: json.bids,
                    open: function(modal) {
                        $.ajax({
                            url: './user/budget/' + userId,
                            dataType: 'json',
                            success: function(json) {
                                if (!json.budget) {
                                    return;
                                }
                                var budgets = json.budget.active;
                                for(var i = 0; i < budgets.length; i++) {
                                    var budget = budgets[i],
                                        link = $('<a>').attr({
                                            budget: budget.id,
                                            reason: budget.reason,
                                            remaining: budget.remaining
                                        });
                                    link.text(budget.reason + ' ($' + budget.remaining + ')');
                                    var item = $('<li>').append(link);
                                    $('.modal-footer .dropup ul', modal).append(item);
                                }
                                $('.modal-footer .dropup ul a', modal).click(function(event) {
                                    var budget = $(this).attr('budget');
                                    $('input[name="budget_id"]', modal).val(budget);
                                    $('button[name="accept"]', modal).html(
                                        '<span>' + $(this).attr('reason') + '</span> ' +
                                        '($' + $(this).attr('remaining') + ') ' +
                                        '<span class="caret"></span>'
                                    );
                                    if (!$('button[name="accept_bid"]', modal).length) {
                                        var confirm = $('<button>')
                                            .attr({
                                                type: 'submit',
                                                name: 'accept_multiple_bid'
                                            })
                                            .addClass('btn btn-primary')
                                            .text('Confirm Accept');
                                        $('.modal-footer', modal).append(confirm);
                                    }
                                })
                            }
                        });
                        $('button[name="accept_bid"]', modal).click(function(event) {
                            if (!$('input[name="budget_id"]', modal).val()) {
                                $('button[name="accept_bid"] + button', modal).click();
                                return false;
                            }
                        });
                        $('input[type="checkbox"]', modal).on('change', function() {
                            if ($(this).is(':checked')) {
                                if (!$('input[type="radio"]:checked', modal).length) {
                                    $('input[type="radio"]', $(this).parent()).prop('checked', true);
                                }
                            } else {
                                $('input[type="radio"]:checked', $(this).parent()).prop('checked', false);
                            }
                        });
                        $('input[type="radio"]', modal).on('change', function() {
                            if ($(this).is(':checked')) {
                                $('input[type="checkbox"]', $(this).parent()).prop('checked', true);
                            }
                        });
                        $('form', modal).on('submit', function(event) {
                            if ($(this).find('input[type="checkbox"]:checked').length && $(this).find('input[type="radio"]:checked').length == 1) {
                                return true;
                            } else {
                                return false;
                            }
                        });
                    }
                });
            }
        });
    },

    setFollowingText: function(isFollowing) {
        if(isFollowing == true) {
            $('#following').attr('title', 'You are currently following this job');
            $('#following').html('Un-Follow this job');
        } else {
            $('#following').attr('title', 'Click to receive updates for this job');
            $('#following').html('Follow this job');
        }
    },

    refreshCodeReviewPartial: function(data) {
        Utils.parseMustache('partials/job/codeReview', data, function(parsed) {
            $('#code-review').remove();
            $('.skills').after(parsed);
            Job.setCodeReviewEvents();
        });
    },

    setCodeReviewEvents: function() {
        $('#code-review > form').submit(function() {
            if ($(this).parent().attr('started') == '1') {
                Job.endCodeReview();
            } else {
                Job.startCodeReview();
            }
            return false;
        });
        $('#code-review > form button[type="button"]').click(Job.cancelCodeReview);
    },

    startCodeReview: function() {
        $.ajax({
            type: 'post',
            url: './job/startCodeReview/' + workitem_id,
            dataType: 'json',
            success: function(data) {
                Job.refreshCodeReviewPartial(data);
            }
        });
        return false;
    },

    cancelCodeReview: function() {
        $.ajax({
            type: 'post',
            url: './job/cancelCodeReview/' + workitem_id,
            dataType: 'json',
            success: function(data) {
                Job.refreshCodeReviewPartial(data);
            }
        });
        return false;
    },

    endCodeReview: function() {
        var fee = parseFloat($('#code-review > form input[name="fee"]').val());
        var desc = $('#code-review > form input[name="desc"]').val();
        $.ajax({
            type: 'post',
            data: {desc: desc},
            url: './job/endCodeReview/' + workitem_id + '/' + fee,
            dataType: 'json',
            success: function(data) {
                $('#code-review').remove();
            }
        });
        return false;
    },

    paidModal: function(e) {
        e.stopPropagation();
        var fee_id = $(this).attr('id').substr(8);
        $.ajax({
            url: './fee/info/' + fee_id,
            dataType: 'json',
            success: function(data) {
                var paidCheckedStr = (parseInt(data.paid) ? ' checked="checked"' : '');
                var notPaidCheckedStr = (!parseInt(data.paid) ? ' checked="checked"' : '');
                Utils.emptyFormModal({
                    title: 'Fee paid status',
                    content:
                        '<div class="row">' +
                        '  <div class="col-md-6">' +
                        '    <label>Fee status</label>' +
                        '  </div>' +
                        '  <div class="col-md-3">' +
                        '    <input type="radio" class="wlradiobox" name="paid" id="paid"' + paidCheckedStr +  '>' +
                        '    <label for="paid">Paid</label>' +
                        '  </div>' +
                        '  <div class="col-md-3">' +
                        '    <input type="radio" class="wlradiobox" name="paid" id="notpaid"' + notPaidCheckedStr +  '>' +
                        '    <label for="notpaid">Not Paid</label>' +
                        '  </div>' +
                        '</div>' +
                        '<div class="row">' +
                        '  <div class="col-md-12">' +
                        '    <label for="paidnotes">Notes</label>' +
                        '    <textarea id="paidnotes" name="notes" class="form-control">' + data.notes + '</textarea>' +
                        '  </div>' +
                        '</div>',
                    buttons: [
                        {
                            type: 'button',
                            name: 'cancel',
                            content: 'Cancel',
                            className: 'btn-primary',
                            dismiss: true
                        },
                        {
                            type: 'submit',
                            name: 'save',
                            content: 'Save',
                            className: 'btn-primary',
                            dismiss: false
                        }
                    ],
                    open: function(modal) {
                        $('form', modal).submit(function() {
                            var paid = $('input[name="paid"]:eq(0)', modal)[0].checked;
                            var notes = $('textarea[name="notes"]', modal).val();
                            $.ajax({
                                url: './fee/setPaid/' + fee_id + '/' + (paid ? '1' : '0'),
                                type: 'post',
                                data: {notes: notes},
                                dataType: 'json',
                                success: function(data) {
                                    $(modal).modal('hide');
                                }
                            });
                            return false;
                        });
                    }
                });
            }
        });
        return false;
    },

    showUniquePageViews: function() {
        $.ajax({
            url: 'api.php?action=visitQuery&jobid=' + workitem_id,
            dataType: 'json',
            success: function (json) {
                $('.visits').text(json.visits);
            }
        });
    },

    reviewUrlModal: function(fAfter) {
        Utils.emptyFormModal({
            title: 'Branch URL',
            content:
                '<div class="row">' +
                '  <div class="col-md-4">' +
                '    <label for="sburl">Branch URL</label>' +
                '  </div>' +
                '  <div class="col-md-8">' +
                '    <input type="text" class="form-control" name="url" ' +
                '      id="sburl" value="' + sandbox_url + '">' +
                '  </div>' +
                '</div>' +
                '<div class="row">' +
                '  <div class="col-md-12">' +
                '    <label for="sburlnotes">Notes</label>' +
                '    <textarea id="sburlnotes" name="notes" class="form-control"></textarea>' +
                '  </div>' +
                '</div>',
            buttons: [
                {
                    type: 'submit',
                    name: 'save',
                    content: 'Save',
                    className: 'btn-primary',
                    dismiss: false
                }
            ],
            open: function(modal) {
                $('form', modal).submit(function() {
                    var url = $('input[name="url"]', modal).val();
                    var notes = $('textarea[name="notes"]', modal).val();
                    $.ajax({
                        type: 'post',
                        url: './job/updateSandboxUrl/' + workitem_id,
                        data: {
                            url: url,
                            notes: notes
                        },
                        dataType: 'json',
                        success: function(data) {
                            $(modal).modal('hide');
                            if (fAfter) {
                                fAfter();
                            }
                        }
                    });
                  return false;
                });
            }
        });
    }
};
