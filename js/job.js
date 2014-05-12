
$(function() {

     Workitem.init();

    $('#statusCombo').chosen();
    $('#project_id').chosen();
    if (action == 'edit') {
        $('select[name="runner"]').chosen({width: 'auto'});
        $('select[name="status"]').chosen({width: 'auto'});
    }
    if($("#is_bug").is ( ":checked" )) {
        $("#bug_job_id").keyup();
    }

    if (status_error) {
        openNotifyOverlay(status_error, false);    
    }
    applyPopupBehavior();

    $('#invite-people').dialog({
        autoOpen: false,
        dialogClass: 'white-theme',
        resizable: false,
        modal: true,
        show: 'fade',
        hide: 'fade',
        width: 'auto',
        height: 'auto',
        open: function() {  
            var autoArgs = autocompleteMultiple('getuserslist');
            $("#invite").bind("keydown", autoArgs.bind);
            $("#invite").autocomplete(autoArgs, null);               
        }
            
    });
    $("#invite-link").click(function() {
        $('#invite-people').dialog('open');
    });

    if (displayDialogAfterDone && mechanic_id > 0) {
        WReview.displayInPopup({
            'user_id': mechanic_id,
            'nickname': mechanic_nickname,
            'withTrust': true,
            'notify_now': 0
        });
    }

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
        return saveWorkitem();
    });

    //if the page was loaded with request to display userinfo automatically then do it.
    if (userinfotoshow){
        window.location.href='userinfo.php?id=' + userinfotoshow;
    }

    //lookup and show job summary on bug_job_id change
    $("#bug_job_id").keyup(function() {
        var id=$("#bug_job_id").val();
        if(id.length) {
            $.ajax({
                url: 'api.php',
                dataType: 'json',
                data: {
                    action: 'getJobInformation',
                    itemid: id
                },
                type: 'POST',
                success: function(json) {
                    if (!json || json === null) {
                        alert("json null in getjobinformation");
                        return;
                    }
                    if (json.error) {
                        alert(json.error);
                    } else {
                        if(json.returnString.length > 0) {
                            $('#bugJobSummary').attr('title', id);
                        } else {
                            $('#bugJobSummary').attr('title', 0);
                        }
                    }
                }
            });
        }
    });

    $('#invite-form').on('submit', function(event) {
        event.preventDefault();
        sendInviteForm();
        return false;
    });

    Entries.formatWorklistStatus();
});

var Workitem = {

    sandbox_url: '',
    
    init: function() {
        $("#view-sandbox").click(function() {
            if (repo_type == 'git') {
                window.open(sandbox_url, '_blank');
            } else {
                Workitem.openDiffPopup({
                    sandbox_url: sandbox_url,
                    workitem_id: workitem_id
                });
            }
        });       
    },
    
    openDiffPopup: function(options) {
        if ($("#diffUrlDialog").length == 0) {
            $("<div id='diffUrlDialog' class='popup-body'><div class='content'>Loading ...</div></div>").appendTo("body"); 
            $("#diffUrlDialog").data("options", options);
            $('#diffUrlDialog').dialog({
                dialogClass: 'white-theme',
                title: 'View Sandbox Diff',
                autoOpen: false,
                closeOnEscape: true,
                resizable: false,
                width: '420px',
                show: 'drop',
                hide: 'drop',
                buttons: [
                    {
                        text: 'Ok',
                        click: function() {
                            if ($("#diffUrlDialog #diff-sandbox-url").length > 0) {
                                $("#diffUrlDialog").data("options", {
                                    sandbox_url: $("#diffUrlDialog #diff-sandbox-url").val(),
                                    workitem_id: $("#diffUrlDialog").data("options").workitem_id
                                });
                                Workitem.fillDiffPopup();
                            } else {
                                $(this).dialog("close");
                            }                           
                        }
                    }
                ],
                open: function() {
                    Workitem.fillDiffPopup();
                },
                close: function() {
                    $("#diffUrlDialog .content").html("");
                }
            });
        } else {
            $("#diffUrlDialog").data("options", options);
            $("#diffUrlDialog .content").html("");                       
        }
        $("#diffUrlDialog").dialog("open");
    },
    
    fillDiffPopup: function() {
        var options = $("#diffUrlDialog").data("options");
        $("#diffUrlDialog .content").load("api.php #urlContent", {
            action: 'workitemSandbox',
            method: 'getDiffUrlView',
            sandbox_url: options.sandbox_url,
            workitem_id: options.workitem_id
        });        
    }
    
    
}

function reply(id) {
    var commentForm = $('#commentform');
    var clone = commentForm.clone();
    commentForm.remove();
    clone.insertAfter($('#comment-' + id));
    $('#commentform textarea').height(61).autosize();
    commentMargin = $('#comment-' + id).css('margin-left');
    leftMargin = 64 + "px";
    clone.css({'margin-left':leftMargin});
    $('#commentform input[name=comment_id]').val(id);
    $('#commentform input[name=newcomment]').val('Reply');
    $('#commentform input[name=cancel]').removeClass('hidden');

    $('#commentform input[name=cancel]').click(function(event) {
        event.preventDefault();
        var commentForm = $('#commentform');
        var clone = commentForm.clone();
        commentForm.remove();
        clone.css({'margin-left':'0'});
        $('input[name=cancel]', clone).addClass('hidden');
        clone.insertAfter($('#commentZone ul'));
        $('#commentform textarea').height(61).autosize();

        $(this).parent().addClass('hidden');
        $('#commentform input[name=newcomment]').val('Comment');
        $('#commentform input[name=comment_id]').val('');
        $('#commentform input[name=newcomment]').click(function(event) {
            event.preventDefault();
            postComment();
        });
    });

    $('#commentform input[name=newcomment]').click(function(event) {
        event.preventDefault();
        postComment();
    });

    runDisableable();
    return false;
}

function postComment() {
    var id = $('#commentform input[name=comment_id]').val();
    var my_comment = $('#commentform textarea[name=comment]').val();

    var color = 'imOdd';
    $('#commentform textarea[name=comment]').val('');
    $('#commentform input[name=comment_id]').val('');
    $('#commentform input[name=newcomment]').val('Comment');
    $('#commentform input[name=cancel]').addClass('hidden');
    $('#commentform').css({'margin-left':0});
    var commentForm = $('#commentform');
    var clone = commentForm.clone();
    commentForm.remove();

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
                $('#no_comments').hide();
                if (id != '') {
                    elementClass = $('#comment-' + id).attr('class').split(" ");
                    depth = Number(elementClass[0].substring(elementClass[0].indexOf('-') + 1)) + 1;
                } else {
                    depth = 0;
                }
                var replyLink = (depth < 6) ? '<div class="reply-lnk">' +
                        '<a href="#commentform" onClick="reply(' + data.id + '); return false;">Reply</a>' +
                    '</div>' : '';
                var newcomment =
                    '<li id="comment-' + data.id + '" class="depth-' + depth + ' ' + color + '">' +
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
                                '<div class="reply-lnk">' +
                                    replyLink +
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
            }

            clone.insertAfter($('#commentZone ul'));
            $('#commentform textarea').height(61).autosize();
            $('#commentform input[name=newcomment]').click(function(event) {
                event.preventDefault();
                postComment();
            });
        }
    });
}

var getSliderValueFromText = function(val) {
    switch (val) {
        case '1 hour':
            return 0;
            break;
        case '2 hours':
            return 1;
            break;
        case '4 hours':
            return 2;
            break;
        case '8 hours':
            return 3;
            break;
        case '1 day':
            return 4;
            break;
        case '2 days':
            return 5;
            break;
        case '3 days':
            return 6;
            break;
        case '4 days':
            return 7;
            break;
        case '5 days':
            return 8;
            break;
        case '6 days':
            return 9;
            break;
        case '7 days':
            return 10;
            break;
        default:
            return 11;
    }
    return false;
}

$(document).ready(function(){
    $('#popupSelectBudget').dialog({
        autoOpen: false,
        dialogClass: 'white-theme',
        modal: true,
        width: 470,
        resizable: false,
        height: 350,
        open: function(event, ui) {

        }
    });
    //Budget.initCombo();
    $('#budget-source-combo').chosen({
        width: '300px',
        disable_search: true
    });
    $("#popupSelectBudget #confirm_budget").click(function(event) {
        event.preventDefault();
        var budget = new LiveValidation('budget-source-combo', {
            onlyOnSubmit: true ,
            onInvalid : function() {
                this.insertMessage( this.createMessageSpan() ); this.addFieldClass();
            }
        });
        budget.add( Validate.Exclusion, { within: [ 0 ], failureMessage: "You must select a budget!" });
        massValidation = LiveValidation.massValidate( [ budget ]);
        if (!massValidation) {
          return false;
        }
        $("#budget_id, #budget_id_multiple_bid").val($('#budget-source-combo').val());
        $('#popupSelectBudget').dialog("close");
        openNotifyOverlay("Accepting bids", false, false);
        $('#' + $('#popupSelectBudget').data("clickon")).click();
    });
    $("#popupSelectBudget #cancel_budget").click(function() {
        $("#budget_id, #budget_id_multiple_bid").val("");
        //var val1 = $($('#budget-source-combo option').get(0)).attr("value");
        //$('#budget-source-combo').comboBox({action:"val", param: [val1]});
        $('#budget-source-combo').chosen();
        $('#popupSelectBudget').dialog("close");
    });

    $('#bidDoneSlider').slider({
        value: 1,
        min: 0,
        max: 10,
        step: 1,
        slide: function(event, ui) {
            $('.sliderStepValue', $(this)).remove();
            $('.sliderStep', $(this)).eq(ui.value).html('<div class="sliderStepValue">' + getTextFromSliderValue(ui.value) + '</div>');
            $('#done_in').val(getTextFromSliderValue(ui.value));
        }
    });

    $('#bidExpireSlider').slider({
        value: 10,
        min: 0,
        max: 10,
        step: 1,
        slide: function(event, ui) {
            $('.sliderStepValue', $(this)).remove();
            $('.sliderStep', $(this)).eq(ui.value).html('<div class="sliderStepValue">' + getTextFromSliderValue(ui.value) + '</div>');
            $('#bid_expires').val(getTextFromSliderValue(ui.value));
        }
    });

    $('.sliderStepValue', '#bidDoneSlider').remove();
    $('.sliderStep', '#bidDoneSlider').eq(1).html('<div class="sliderStepValue">' + '2 hours' + '</div>');
    $('.sliderStepValue', '#bidExpireSlider').remove();
    $('.sliderStep', '#bidExpireSlider').eq(10).html('<div class="sliderStepValue">' + '7 days' + '</div>');

    $('#bidExpireEditSlider').slider({
        value: getSliderValueFromText($('#bid_expires_edit').val()),
        min: 0,
        max: 10,
        step: 1,
        slide: function(event, ui) {
            $('.sliderStepValue', $(this)).remove();
            $('.sliderStep', $(this)).eq(ui.value).html('<div class="sliderStepValue">' + getTextFromSliderValue(ui.value) + '</div>');
            $('#bid_expires_edit').val(getTextFromSliderValue(ui.value));
        }
    });

    $('#bidDoneEditSlider').slider({
        value: 1,
        min: 0,
        max: 10,
        step: 1,
        slide: function(event, ui) {
            $('.sliderStepValue', $(this)).remove();
            $('.sliderStep', $(this)).eq(ui.value).html('<div class="sliderStepValue">' + getTextFromSliderValue(ui.value) + '</div>');
            $('#done_in_edit').val(getTextFromSliderValue(ui.value));
        }
    });

    var getTextFromSliderValue = function(val) {
        var sRet="2 hours";
        switch (val) {
            case 0:
                sRet  = "1 hour";
                break;
            case 1:
                sRet = "2 hours";
                break;
            case 2:
                sRet = "4 hours";
                break;
            case 3:
                sRet = "8 hours";
                break;
            case 4:
                sRet = "1 day";
                break;
            case 5:
                sRet = "2 days";
                break;
            case 6:
                sRet = "3 days";
                break;
            case 7:
                sRet = "4 days";
                break;
            case 8:
                sRet = "5 days";
                break;
            case 9:
                sRet = "6 days";
                break;
            case 10:
                sRet = "7 days";
                break;
        }
        return sRet;
    };

    // default dialog options
    var dialog_options = { dialogClass: 'white-theme', autoOpen: false, modal: true, maxWidth: 600, width: 485, show: 'fade', hide: 'fade', resizable: false };
    $('#popup-bid').dialog(dialog_options);
    $('#popup-review-started').dialog(dialog_options);

    $('#popup-edit-bid-info').dialog(
        { dialogClass: 'white-theme', autoOpen: false, modal: true, maxWidth: 600, width: 485, show: 'fade', hide: 'fade', resizable: false }
    );

    $('#popup-ineligible').dialog({
        dialogClass: 'white-theme',
        modal: true,
        title: "Your account is ineligible",
        autoOpen: false,
        width: 300,
        position: ['top'],
        open: function() {
            $('#button_settings').click(function() {
                document.location.href = './settings#payment-info';
            });
        }
    });

    // If the bid info popup is set to modal clipboard won't work!
    $('#popup-bid-info').dialog({
        dialogClass: 'white-theme',
        autoOpen: false,
        modal: false,
        resizable: false,
        width: 510,
        maxHeight: 600,
        show: 'fade',
        hide: 'fade',
        open: function() {
        }
    });
    $('#popup-fee-info').dialog({ dialogClass: 'white-theme', autoOpen: false, modal: false, width: 400, show: 'fade', hide: 'fade', resizable: false });
    $('#popup-multiple-bid-info').dialog({ dialogClass: 'white-theme', autoOpen: false, modal: true, width: 750, position: ['center', 160], show: 'fade', hide: 'fade' });
    $('#popup-startreview').dialog({
        closeOnEscape: false,
        dialogClass: 'white-theme',
        autoOpen: false,
        modal: false,
        width: 400,
        show: 'fade',
        hide: 'fade',
        open: function(event, ui) {
            $(".ui-dialog-titlebar-close").hide();
        }
    });
    $('#popup-paid').dialog({ dialogClass: 'white-theme', autoOpen: false, maxWidth: 600, width: 450, show: 'fade', hide: 'fade' });
    $('#message').dialog({ dialogClass: 'white-theme', autoOpen: true, show: 'fade', hide: 'fade' });
    $('#popup-pingtask').dialog({
        autoOpen: false,
        dialogClass: 'white-theme',
        width: 400,
        height: "auto",
        resizable: false,
        position: [ 'top' ],
        show: 'fade',
        hide: 'fade',
        close: function() {
            $('#ping-msg').val('').css("height", "100px");
        }
    });
    $('#ping-msg').autogrow();
    $('#popup-reviewurl').dialog({
        autoOpen: false,
        dialogClass: 'white-theme',
        modal: true,
        width: 450,
        show: 'fade',
        hide: 'fade',
        resizable: false,
        close: function() {
            $('select[name=quick-status]').val(origStatus);
        }
    });
    $('#popup-withdraw-bid').dialog({ dialogClass: 'white-theme', autoOpen: false, width: 450, show: 'fade', hide: 'fade' });
    $('#popup-decline-bid').dialog({ autoOpen: false, width: 420, show: 'fade', hide: 'fade' });
    if (mechanic_id == user_id) {
        $('#popup-addtip').dialog({ dialogClass: 'white-theme', autoOpen: false, modal: true, width: 365, height: 385, show: 'fade', hide: 'fade' });
        $('.addTip').click(function() {
            $('#popup-addtip').dialog('open');
        });
    }

   $('#commentform input[name=newcomment]').click(function(event) {
        event.preventDefault();
        postComment();
    });
    
    $('#commentform input[name=cancel]').addClass('hidden');

    $("#switchmode_edit").click(function(event) {
        if (!is_project_runner && insufficientRightsToEdit) {
                 $("#workitem_no_edit").dialog({
                     title: "Insufficient User Rights",
                     autoOpen: false,
                     height: 120,
                     width: 370,
                     position: ['center','center'],
                     modal: true
            });
            $("#workitem_no_edit").dialog("open");
            event.preventDefault();
        }
    });
    $('#following').click(function() {
        $.ajax({
            type: 'post',
            url: 'jsonserver.php',
            data: {
                workitem: workitem_id,
                userid: user_id,
                action: 'ToggleFollowing'
            },
            dataType: 'json',
            success: function(data) {
                if (data.success) {
                    isFollowing = !isFollowing;
                    setFollowingText(isFollowing);
                }
            }
        });
    });

    (function($) {
        // journal info accordian
        // flag to say we've not loaded anything in there yet
        $.journalChat = false;
        $('.accordion').accordion({
            clearStyle: true,
            collapsible: true,
            active: true
            }
        );
        $('.accordion').accordion( "activate" , 0 );
        $.ajax({
            type: 'post',
            url: 'jsonserver.php',
            data: {
                workitem: workitem_id,
                userid: user_id,
                action: 'getFilesForWorkitem'
            },
            dataType: 'json',
            success: function(data) {
                if (data.success) {
                    var images = data.data.images;
                    var documents = data.data.documents;
                    for (var i=0; i < images.length; i++) {
                        imageArray.push(images[i].fileid);
                    }
                    for (var i=0; i < documents.length; i++) {
                        documentsArray.push(documents[i].fileid);
                    }
                    var files = $('#uploadedFiles').parseTemplate(data.data);
                    $('#uploadPanel').append(files);
                    if (user_id) {
                        $('#accordion').fileUpload({images: imageArray, documents: documentsArray});
                    }
                    $('#uploadPanel').data('files', data.data);
                    $('#accordion').bind( "accordionchangestart", function(event, ui) {
                        $('#uploadButtonDiv').appendTo(ui.newContent);
                        $('#uploadButtonDiv').css('display', 'block');
                    });
                }
            }
        });
        setTimeout(function(){
            $(".view_bid_id").click();
        }, 500);
        if (user_id) {
            setFollowingText(isFollowing);
        } else {  
            $('#followingLogin').html('<a href="./github/login">Login to follow this task.</a>');
        }
    })(jQuery);
    
    SimplePopup('#popup-bid', 'Place Bid', workitem_id, [['input', 'itemid', 'keyId', 'eval']]);
    $('.popup-body form input[type="submit"]').click(function(){
        var name = $(this).attr('name');
        switch(name) {
            case "reset":
                ResetPopup();
                return false;
            case "cancel":
                $('#popup-paid').dialog('close');
                return false;
         }
    });
    if (user_id) {
        $('.paid-link').click(function(e){
            e.stopPropagation();
            var fee_id = $(this).attr('id').substr(8);
            if ($('#feeitem-' + fee_id).html() == "Yes") {
                $('#paid_check').attr('checked', "1");
            } else if ($('#feeitem-' + fee_id).html() == "No" ) {
                $('#notpaid_check').attr('checked', "1");
            }

            $('#paid_check').click(function() {
                   var $checkbox = $('#notpaid_check');
                   $checkbox.attr('checked', !$checkbox.attr('checked'));
            });
            $('#notpaid_check').click(function() {
                   var $checkbox = $('#paid_check');
                   $checkbox.attr('checked', !$checkbox.attr('checked'));
            });
            AjaxPopup('#popup-paid',
                'Pay Fee',
                'api.php?action=getFeeItem',
                fee_id,
                [ ['input', 'itemid', 'keyId', 'eval'],
                ['textarea', 'paid_notes', 'json[2]', 'eval'],
                ['checkbox', 'paid_check', 'json[1]', 'eval'] ]);
                $('.paidnotice').empty();
                $('#popup-paid').dialog('open');

                // onSubmit event handler for the form
                $('#popup-paid > form').submit(function() {
                    // now we save the payment via ajax
                    $.ajax({
                        url: 'api.php',
                        dataType: 'json',
                        data: {
                            action: 'payCheck',
                            itemid: $('#' + this.id + ' input[name=itemid]').val(),
                            paid_check: $('#' + this.id + ' input[name=paid_check]').prop('checked') ? 1 : 0,
                            paid_notes: $('#' + this.id + ' textarea[name=paid_notes]').val()
                        },
                        success: function(data) {
                            // We need to empty the notice field before we refill it
                            if (!data.success) {
                                // Failure message
                                var html = '<div style="padding: 0 0.7em; margin: 0.7em 0;" class="ui-state-error ui-corner-all">' +
                                                '<p><span style="float: left; margin-right: 0.3em;" class="ui-icon ui-icon-alert"></span>' +
                                                '<strong>Alert:</strong> ' + data.message + '</p>' +
                                            '</div>';
                                $('.paidnotice').append(html);
                                // Fire the failure event
                                $('#popup-paid > form').trigger('failure');
                            } else {
                                // Success message
                                var html = '<div style="padding: 0 0.7em; margin: 0.7em 0;" class="ui-state-highlight ui-corner-all">' +
                                                '<p><span style="float: left; margin-right: 0.3em;" class="ui-icon ui-icon-info"></span>' +
                                                '<strong>Info:</strong> ' + data.message + '</p>' +
                                            '</div>';
                                $('.paidnotice').append(html);
                                // Fire the success event
                                $('#popup-paid > form').trigger('success');
                            }
                        }
                    });

                    return false;
                });

                // Here we need to capture the event and fire a new one to the upper container
                $('#popup-paid > form').bind('success', function(e, d) {
                    $('.table-feelist tbody').empty();
                    //TODO Make this use a refresh when this page supports AJAX data refresh in future
                    location.reload();
                });

            return false;
        });

        $('.wd-link').click(function(e) {
            e.stopPropagation();
            e.preventDefault();
            var fee_id = $(this).attr('id').substr(3);
            $('#withdraw .fee_id').val(fee_id);
            $('#withdraw').submit();
        });

        $('tr.row-bidlist-live').click(function() {

            $.metadata.setType("elem", "script")
            var bidData = $(this).metadata();

            // row has bid data attached so user is a bidder or a runner
            // - see table creation routine
            if(bidData.id){
                $('#popup-bid-info form input[type="submit"]').remove();
                $('#popup-bid-info form input[type="button"]').remove();

                $('#popup-bid-info input[name="bid_id"]').val(bidData.id);
                $('#popup-bid-info #info-email').html('<a href="./user/' + bidData.bidder_id +'" >' + bidData.nickname + '</a>');
                $('#popup-bid-info #info-bid-created').text(bidData.bid_created);
                if (bidData.bid_accepted.length > 0) {
                    $('#popup-bid-info #info-bid-accepted').text(bidData.bid_accepted);
                } else {
                    $('#bidAcceptedRow').hide();
                }
                $('#popup-bid-info #info-bid-expires').text(bidData.bid_expires);
                $('#popup-bid-info #info-bid-amount').text(bidData.amount);
                $('#popup-bid-info #info-bid-done-in').text(bidData.done_in);
                $('#popup-bid-info #info-notes').html(bidData.notes);

                if (showAcceptBidButton && $('#accept_bid').length == 0) {
                    $('#popup-bid-info-buttons').append('<input type="button" class="disableable" id="accept_bid_select_budget" ' +
                                '  onClick="return selectBudget(\'accept_bid\');" name="accept_bid_select_budget" value="Accept">');
                    $('#popup-bid-info-buttons').append('<input type="submit" class="disableable" style="display:none;" id="accept_bid" name="accept_bid" value="Confirm Accept">');
                    runDisableable();
                }

                if((bidData.bidder_id == user_id) && !hasAcceptedBids) {
                    $('#popup-bid-info-buttons').append('<input type="button" name="edit" id="edit" value="Edit" onClick="return ConfirmEditBid()" style="padding-left:20px;padding-right:20px;">');
                }

                if (showPingBidderButton) {
                    $('#popup-bid-info-buttons').append('<input id="ping_bidder" type="button" name="ping_bidder" value="Reply"  onClick="return pingBidder(' + bidData.id + ')" >');
                }

                if (showWithdrawOrDeclineButtons && (bidData.bidder_id == user_id) && $('#withdraw_bid_accept').length == 0) {
                    $('#popup-bid-info-buttons').append('<input id="withdraw_bid_accept" type="button" name="withdraw_bid_accept" value="Withdraw" onClick="return showWithdrawBidReason()" >');
                } else if ((is_project_runner || (is_admin && is_runner)) && bidData.bidder_id != user_id) {
                    $('#popup-bid-info-buttons').append
                     ('<input id="decline_bid_accept" type="button" name="decline_bid_accept" value="Decline" onClick="return showDeclineBidReason()" >');
                }
                // change user id to current bidder id
                stats.setUserId(bidData.bidder_id);
                
                // filling and appending user stats table
                $('.loader').show();

                // get data for recent jobs completed
                $.getJSON('api.php?action=getUserStats', {
                    id: bidData.bidder_id,
                    project_id: project_id,
                    statstype: 'project_history'
                }, function(json) {
                    if (json.joblist) {
                        var html = '';

                        if (json.joblist.length > 0) {
                            var jobCount = json.joblist.length > 3 ? 3 : json.joblist.length;

                            html += '<div class="info-label block bidderStats">';
                            html += 'Last ' + jobCount + ' job(s) for ' + project_name + '</div><br />';
                            var urlBase = '<a  class="worklist-item font-14" href="./';
                            for (var i = 0; i < jobCount; i++) {
                                job = json.joblist[i];
                                html += urlBase;
                                html += job.id + '" id="worklist-' + job.id + '">#' +job.id + 
                                    '</a> - ' + job.summary + '<br />';
                            }

                        } else {
                            html += 'No prior jobs for '  + project_name;
                        }

                        $('#project_history').html(html);
                        // activate tooltips
                        makeWorkitemTooltip("#project_history .worklist-item");
                    }
                });

                $.getJSON('api.php?action=getUserStats', {id: bidData.bidder_id, statstype: 'counts'}, function(json) {

                    // filling the table from json stats
                    $('#total-jobs').html(json.total_jobs );
                    $('#active-jobs').html(json.active_jobs);
                    $('#total-earnings').html(json.total_earnings);
                    $('#latest_earnings').html(json.latest_earnings);
                    $('#total-bonus').html(json.bonus_total);
                    $('#percent_bonus').html(json.bonus_percent);
                    $('.loader').hide();
                });

                // calculate the maximum height of the modal based on the window height
                var maxHeight = $(window).height() - 250;
                $('#popup-bid-info').css('max-height', maxHeight + 'px');
                $('#popup-bid-info').dialog('open');

            }
        });

        $('tr.row-feelist-live').click(function() {
            $.metadata.setType("elem", "script")
            var feeData = $(this).metadata();

            // row has bid data attached so user is a bidder or a runner
            // - see table creation routine
            if (feeData.id){
                $('#popup-fee-info #info-fee-email').html('<a href="./user/' + feeData.user_id + '" >' + feeData.nickname + '</a>');
                $('#popup-fee-info #info-fee-created').text(feeData.fee_created);
                $('#popup-fee-info #info-fee-amount').text(feeData.amount);
                $('#popup-fee-info #info-fee-notes').html(feeData.desc);
            }
            $('#popup-fee-info').dialog('open');
        });

    }
        $('#bid').click(function(e){
            if ( already_bid
                && $(this).parent().find('#mechanic_id').val() == user_id
              && !confirm("You have already placed a bid, do you want to place a new one?")
            ) {
                $('#popup-bid').dialog('close');
                return false;
            }
        });

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

            if ($(this).val() == 'Functional') {
                if(mechanic_id == user_id && promptForReviewUrl) {
                $('#sandbox-url').val(sandbox_url);
                    $('#quick-status-review').val($(this).val());
                    $('#popup-reviewurl').dialog('open');
                } else {
                    openNotifyOverlay(html, false, false);
                    $('#quick-status form').submit();
                }
            } else {
                openNotifyOverlay(html, false, false);
                $('#quick-status form').submit();
            }
        }
    });

    if (showReviewUrlPopup) {
        $('#edit_review_url').click(function(e){
            $('#sandbox-url').val(sandbox_url);
            $('#popup-reviewurl').dialog('open');
        });
    }
});


function ResetPopup() {
    $('#for_edit').show();
    $('#for_view').hide();
    $('.popup-body form input[type="text"]').val('');
    $('.popup-body form select option[index=0]').prop('selected', true);
    $('.popup-body form textarea').val('');
}

function selectBudget(id) {
    $("#budget_id, #budget_id_multiple_bid").val("");
    //var val1 = $($('#budget-source-combo option').get(0)).attr("value");
    //$('#budget-source-combo').comboBox({action:"val", param: [val1]});
    $('#budget-source-combo').chosen();
    $('#popupSelectBudget').data("clickon", id).dialog('open');
}

function ConfirmEditBid(){
    var bid_id = $('#popup-bid-info input[name="bid_id"]').val();

    $('#popup-bid-info').dialog('close');
    AjaxPopup('#popup-edit-bid-info',
        'Edit Bid',
        'api.php?action=getBidItem',
        bid_id,
        [
            ['input', 'bid_id', 'keyId', 'eval'],
            ['input', 'bid_amount', 'json.bid_amount', 'eval'],
            ['input', 'bid_expires_edit', 'json.bid_expires', 'eval'],
            ['input', 'done_in_edit', 'json.bid_done_in', 'eval'],
            ['textarea', 'notes', 'json.notes', 'eval']
        ],

        function(json) {
            // figure out expires
            var expireSeconds = json.unix_expires - json.now;
            var expireHours = Math.round(expireSeconds / 3600);
            var expireText = '';
            if (expireHours > 24 || expireHours > 12) {
                expireText = Math.round(expireHours / 24) + ' days';
            } else if (expireHours > 6) {
                expireText = '8 hours';
            } else if (expireHours >= 4) {
                expireText = '4 hours';
            } else if (expireHours > 1) {
                expireText = expireHours + ' hours';
            } else {
                expireText = '1 hour';
            }

            if(expireHours <= 0) {
                $('#bid_expires_edit').val('1 hour');
            }

            var expireValue = getSliderValueFromText(expireText);
            var doneInValue = getSliderValueFromText(json.bid_done_in);

            $('.sliderStepValue', '#bidExpireEditSlider').remove();
            $('.sliderStep', '#bidExpireEditSlider').eq(expireValue).html('<div class="sliderStepValue">' + expireText + '</div>');
            $('.sliderStepValue', '#bidDoneEditSlider').remove();
            $('.sliderStep', '#bidDoneEditSlider').eq(doneInValue).html('<div class="sliderStepValue">' + json.bid_done_in + '</div>');

            $('#bidExpireEditSlider').slider('value', expireValue);
            $('#bidDoneEditSlider').slider('value', doneInValue);

        }
    );
    $('#popup-edit-bid-info').dialog('open');
}

function showConfirmForm(i) {
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
                        showPlaceBidForm();
                    } else if (i == 'fee') {
                        showFeeForm();
                    }                
                });
            }
        });
    } else {
        GitHub.handleUserConnect();
    }
    return false;
}

function showIneligible(problem) {
    Utils.emptyModal({
        title: 'Your account is ineligible',
        content: 
            '<p>' +
            '    <strong>You are not eligible</strong> to bid or place ' + problem + 's on this item. ' +
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
}

function showPlaceBidForm() {
    var minDoneIn = 7200, // in segs = 2 hours,
        maxDoneIn = 604800; // in segs = 7 days
    Utils.modal('addbid', {
        job_id: workitem_id,
        current_id: userId,
        open: function(modal) {
            $('input[name="done_in"]', modal).after($('<div>').addClass('dragdealer'));
            $('<div>').addClass('handle').text('drag').appendTo('.dragdealer', modal);
            var a = new Dragdealer($('.dragdealer', modal)[0], {
                steps: 10,
                speed: 0.5,
                animationCallback: function(x, y) {
                    var text = Utils.relativeTime(Math.round(x * (maxDoneIn - minDoneIn)) + minDoneIn, false, false, false).replace(/,.*/, '');
                    $('.dragdealer > .handle').text(text);
                    $('input[name="done_in"]').val(text);
                }
            });
            $('form#addbid').submit(function() {
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

}

function showWithdrawBidReason() {
  var bid_id = $('#popup-bid-info input[name="bid_id"]').val();
  $('#popup-bid-info').dialog('close');

  SimplePopup('#popup-withdraw-bid',
            'Withdraw Bid',
             bid_id,
             [['input', 'bid_id', 'keyId', 'eval']]);


    $('#popup-withdraw-bid').dialog('open');
    return false;
}

function showDeclineBidReason() {
  var bid_id = $('#popup-bid-info input[name="bid_id"]').val();
  $('#popup-bid-info').dialog('close');

  SimplePopup('#popup-decline-bid',
            'Decline Bid',
             bid_id,
             [['input', 'bid_id', 'keyId', 'eval']]);


    $('#popup-decline-bid').dialog({
        dialogClass: 'white-theme',
        autoOpen: true,
        width: 450
    });
    return false;
}

function pingBidder(id) {
    ping_who = 'bidder';
    ping_bid_id = id;
    $('#echo-journal').prop('checked', false);
    $('#echo-journal-span').css('display', 'none');
    $('#send-ping-btn').val('Send Reply');
    $('#popup-pingtask form h5').html('Ping about Bid');
    $('#popup-pingtask form input[name="bidder"]').val(id);
    $('#popup-pingtask').dialog('open');
    $('#popup-pingtask').dialog('option', 'title', 'Ping about Bid');
    return false;
}

function showFeeForm() {
    $.get(
        './user/index/all',
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
                    $('form#addfee').submit(function() {
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
}

function CheckCodeReviewStatus() {
  if (repo_type == 'svn') {
    $.ajax({
        type: 'post',
        url: 'api.php',
        data: {
            action: 'getCodeReviewStatus',
            workitemid: workitem_id
        },
        dataType: 'json',
        success: function(data) {

                //now check the returned data. if review has already been started show dialog
                if(data[0].code_review_started == 1 ) {
                    $('#popup-review-started').dialog('open');
                }
                else {
                    showReviewForm();
                }

        }
    });
  } else {
      showReviewForm();
  }
}

function showReviewForm() {
    $.ajax({
        type: 'post',
        url: 'jsonserver.php',
        data: {
            workitem: workitem_id,
            userid: user_id,
            action:'startCodeReview'
        },
        dataType: 'json',
        success: function(data) {
            closeNotifyOverlay();
            if (data.success) {
                $('#popup-startreview').dialog('open');
            } else {
                openNotifyOverlay(data.data);
                $("#sent-notify").css({'min-height': '80px', 'text-align': 'left'});
                setTimeout(function() {
                    window.location.reload();
                }, 1500);
            }
        },
        error: function() {
            closeNotifyOverlay();
        }
    });
    return false;
}

function showEndReviewForm() {
    $('#popup-endreview').dialog({
        dialogClass: 'white-theme',
        closeOnEscape: false,
        autoOpen: false,
        modal: false,
        width: 650,
        open: function(event, ui) {
            $(".ui-dialog-titlebar-close").hide();
        }
    });
    $('#popup-endreview').dialog('open');
}

function saveWorkitem() {
    var massValidation;
    var bugJobId;
    if($('#is_bug').is(':checked')) {
        bugJobId = new LiveValidation('bug_job_id');
        bugJobId.add( Validate.Custom, {
            against: function(value,args){
                id = $('#bugJobSummary').attr('title');
                return (id!=0)
            },
            failureMessage: "Invalid item Id"
        });
    }
    
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

    if($('#is_bug').is(':checked')) { 
        massValidation = LiveValidation.massValidate([editProject, summary, bugJobId],true);
    } else {
        massValidation = LiveValidation.massValidate([editProject,summary],true);
    }
                
    if (!massValidation) {
        // Validation failed. We use openNotifyOverlay to display messages
        var errorHtml = createMultipleNotifyHtmlMessages(LiveValidation.massValidateErrors);
        openNotifyOverlay(errorHtml, null, null, true);
        return false;
    }
    
}

function AcceptMultipleBidOpen(){
    var job_id = workitem_id;
    $.ajax({
        type: 'POST',
        url: 'api.php',
        data: {
            "action": "getMultipleBidList",
            "job_id": job_id
        },
        success:function(response) {
            $('#popup-multiple-bid-info').html(response);
            $("#popup-multiple-bid-info .chkMechanic").change(function() {
                if (this.checked) {
                    // remove ticks
                    $('#popup-multiple-bid-info .chkMechanic').removeAttr('checked');
                    // and add it back
                    $(this).prop('checked', true);
                    // and auto accept
                    $(this).parent().parent().find('.acceptMechanic').prop('checked', true);
                }
            });

            $('#popup-multiple-bid-info .acceptMechanic').change(function() {
                if (this.checked) {
                } else {
                    if ($(this).parent().parent().find('.chkMechanic').is(':checked')) {
                        $(this).parent().parent().find('.chkMechanic').removeAttr('checked');
                    }
                }
            });

            $("#accept_bid_select_budget").click(function(){
                selectBudget('accept_multiple_bid');
            });

            $('#popup-multiple-bid-form').submit(function() {
                if ($(this).find('input.chkMechanic:checked').length > 0) {
                    return true;
                } else {
                    $('<div id="popup-mechanic-required"><div class="content"></div></div>').appendTo('body');
                    $('#popup-mechanic-required').dialog({
                        modal: true,
                        title: 'Failed to specify developer',
                        autoOpen: true,
                        width: 300,
                        position: ['top'],
                        open: function() {
                            $('#popup-mechanic-required .content').html('<p>You must pick which user will be the main developer for this task.</p><input class="closeButton" type="button" value="Close" />');
                            $('#popup-mechanic-required .closeButton').click(function() {
                                $('#popup-mechanic-required').dialog('close');
                            });
                        }
                    });

                    return false;
                }
            });
        }
    });
    $('#popup-multiple-bid-info').dialog('open');
}

$(function() {
    $.loggedin = (user_id > 0);

    $(".editable").attr("title","Switch to Edit Mode").click(function() {
        window.location.href = "./" + workitem_id + "?action=edit";
    });

    // Call via Ajax to ping the user in the journal
    // and in email.
    $('#send-ping-btn').click(function()    {
        $('#send-ping-btn').attr("disabled", "disabled");
        var msg = $('#ping-msg').val();
        // if( $('#send-mail:checked').val() ) mail = 1;
        // always send email
        var mail = 1;
        var journal = $('#echo-journal').is(':checked') ? 1 : 0;
        var cc = $('#copy-me').is(':checked') ? 1 : 0;
        var data = {
            'action': 'pingTask',
            'id' : workitem_id, 
            'who' : ping_who, 
            'bid_id': ping_bid_id, 
            'msg' : msg, 
            'mail' : mail, 
            'journal' : journal, 
            'cc' : cc
        };
        $.ajax({
            type: "POST",
            url: 'api.php',
            data: data,
            dataType: 'json',
            success: function(json) {
                if (json && json.error) {
                    alert("Ping failed:" + json.error);
                } else {
                    var msg = "<span>Your message has been sent.</span>";
                    if ($('#send-ping-btn').val() == 'Send Reply') {
                        msg = "<span>Your reply has been sent.</span>";
                    }
                    Utils.emptyModal({
                        content: msg,
                        buttons: [
                            {
                                content: 'Ok',
                                className: 'btn-primary',
                                dismiss: true
                            }
                        ]
                    });
                }
                $('#popup-pingtask').dialog('close');
                $('#send-ping-btn').removeAttr("disabled");
            },
            error: function() {
                $('#send-ping-btn').removeAttr("disabled");
            }
        });
        return false;

    });
    
    $('#popup-pingtask').bind('dialogclose', function(event) {
        $("#echo-journal-span").css("display", "block");
        $("#send-ping-btn").val('Send ping');
        $('#echo-journal').prop('checked', true);
    });
    
    $('#pingMechanic').click(function() {
        if (!$.loggedin) {
            sendToLogin();
            return;
        }

        ping_who = 'mechanic';
        ping_bid_id = 0;
        $('#popup-pingtask form h5').html('Ping the Developer about the task');
        $('#popup-pingtask').dialog('open');
        $('#popup-pingtask').dialog('option', 'title', 'Ping the Developer about the task');
        return false;
    });

    $('#pingRunner').click(function() {
        if (!$.loggedin) {
            sendToLogin();
            return;
        }

        ping_who = 'runner';
        ping_bid_id = 0;
        $('#popup-pingtask form h5').html('Ping the Designer about the task');
        $('#popup-pingtask').dialog('open');
        $('#popup-pingtask').dialog('option', 'title', 'Ping the Designer about the task');
        return false;
    });

    $('#pingCreator').click(function() {
        if (!$.loggedin) {
            sendToLogin();
            return;
        }

        ping_who = 'creator';
        ping_bid_id = 0;
        $('#popup-pingtask form h5').html('Ping the Creator about the task');
        $('#popup-pingtask').dialog('open');
        $('#popup-pingtask').dialog('option', 'title', 'Ping the Creator about the task');
        return false;
    });

    $('.table-bids tbody td > a[href^="./user/"]').click(function(event) {
        event.stopPropagation();
        return true;
    });

    // Reassign runner
    if (canReassignRunner) {
        (function($) {
            if ($('#runnerBox span.runnerName') !== null) {
                $('#runnerBox span.runnerName').css({
                    'cursor': 'pointer',
                }).click(function() {
                    var shown = false;
                    $(this).parent().siblings().fadeOut(1000, function() {
                        if (shown != true) {
                            shown = true;
                            $('#runnerBox').css('width', '400px');
                            $('#runnerBox #ping-r-btn').css('display', 'none');
                            $('#runnerBox span.changeRunner').fadeIn(1000, function() {
                                $('#runnerBox span.changeRunner input[name=changerunner]').click(function() {
                                    $(this).unbind('click');
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
                                        dataType: 'json',
                                        success: function(j) {
                                            if (j.success == true) {
                                                $('#runnerBox #ping-r-btn').text(j.nickname);
                                                $('#runnerBox #ping-r-btn').attr('data-user-id', runner_id);
                                                $('#runnerBox input[name=cancel]').click();
                                            }
                                        }
                                    });
                                });
                                $('#runnerBox span.changeRunner input[name=cancel]').click(function() {
                                    $('#runnerBox span.changeRunner').fadeOut(1000, function() {
                                        $('#runnerBox span.changeRunner').css('display', 'none');
                                        $('#runnerBox').css('width', '130px');
                                        $('#runnerBox #ping-r-btn').css('display', 'block');
                                        $('#runnerBox span.runnerName').parent().siblings().fadeIn(1000);
                                    });
                                });
                            });
                        }
                    });
                });
            }
        })(jQuery);
    }

    //-- gets every element who has .iToolTip and sets it's title to values from tooltip.php
    setTimeout(MapToolTips, 800);

    return false;
});

function sendToLogin(){
    window.location = './github/login?redir=./' + workitem_id;
}

function setFollowingText(isFollowing){
    if(isFollowing == true) {
        $('#following').attr('title', 'You are currently following this job');
        $('#following').html('Un-Follow this job');
    } else {
        $('#following').attr('title', 'Click to receive updates for this job');
        $('#following').html('Follow this job');
    }
}

function sendInviteForm(){
  var name = $('input[name="invite"]', $("#invite-people")).val();
  $.ajax({
    type: "POST",
    url: "./" + workitem_id,
    data: "invite=" + name + "&invite-people=Invite",
    dataType: "json",
    success: function(json) {
 
        if (!json.length) {
            $("#sent-notify").html("<span>invite sent to <strong>"+name+"</strong></span>");
            $('input[name="invite"]').val('');
            $('#invite-people').dialog('close');
            $("#sent-notify").dialog("open");
            setTimeout(function() {
                $("#sent-notify").dialog("close"); 
            }, 2000);
            
        } else {
            alert("Some of the users you sent do not exist. Please correct those shown and try again.");
            $('#invite').val('');
            // we need to enter unsent items back into text field
            for (var i = 0; i < json.length; i++) {
                if(i != 0) {
                    $('#invite').val($('#invite').val() + ',' + json[i]);
                } else {
                    $('#invite').val($('#invite').val() + json[i]);
                }
                
            }
        }
        
    },
    error: function(xhdr, status, err) {
      $("#sent-notify").html("<span>Error sending invitation</span>");
    }
  });
  return false;
}
