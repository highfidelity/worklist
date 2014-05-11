var available = 0;

$(document).ready(function() {
    UserInfo.init();
    stats.setUserId(userInfo.user_id);
    stats.showJobs('activeJobs', 0);
    stats.showJobs('completedJobsWithStats', 0, 'completed-jobs-table');

    $('#profile-nav a').click(function (event) {
        event.preventDefault();
        $(this).tab('show');
        if ($(this).attr('href') == '#budgethistory') {
            $.ajax({
                type: 'post',
                url: 'api.php',
                dataType: 'html',
                data: {
                    action: 'budgetHistory',
                    inDiv: 'tabs',
                    id: userInfo.user_id,
                    num: 100
                },
                success: function(data) {
                    $('#budgethistory').html(data);
                }
            });
        } else if ($(this).attr('href') == '#mynotes') {
            $.ajax({
                type: 'post',
                url: 'api.php',
                dataType: 'html',
                data: {
                    action: 'userNotes',
                    method: 'getNote',
                    userId: userInfo.user_id
                },
                success: function(data) {
                    $('#mynotes').html(data);
                }
            });
        }
    });
});

$(function () {
    if ($('#fees-week').length > 0) {
        $('#fees-week').parents("tr").click(function() {
            var author = "Guest";
            if($('#user').length > 0) {
                author = $('#user').html();
            }
            var t = 'Weekly fees for '+author;
            $('#wFees').dialog({
                autoOpen: false,
                title: t,
                show: 'fade',
                hide: 'fade'
            });
            $('#wFees').dialog( "option", "title", t );
            $('#wFees').html('<img src="images/loader.gif" />');
            $('#wFees').dialog('open');
            $.getJSON('api.php?action=getFeeSums&type=weekly', function(json) {
                if (json.error == 1) {
                    $('#wFees').html('Some error occured or you are not logged in.');
                } else {
                  $('#wFees').html(json.output);
                }
            });
        });
    }

    if($('#fees-month').length > 0){
        $('#fees-month').parents("tr").click(function() {
            var author = "Guest";
            if ($('#user').length > 0) {
                author = $('#user').html();
            }
            var t = 'Monthly fees for '+author;
            $('#wFees').dialog({
                autoOpen: false,
                title: t,
                show: 'fade',
                hide: 'fade'
            });
            $('#wFees').dialog("option", "title", t);
            $('#wFees').html('<img src="images/loader.gif" />');
            $('#wFees').dialog('open');
            $.getJSON('api.php?action=getFeeSums&type=monthly', function(json) {
                if (json.error == 1) {
                    $('#wFees').html('Some error occured or you are not logged in.');
                } else {
                    $('#wFees').html(json.output);
                }
            });
        });
    }
});
var UserInfo = {
    init: function() {
        userNotes.init();
        // admin settings
        // checkboxes and dropdowns are handled by master functions below
        // the dropdown value should always be an integer

        // set the current values
        $('#manager').val(userInfo.manager);
        $('#referrer').val(userInfo.referred_by);

        WLFavorites.init( "profileInfoFavorite",userInfo.user_id, userInfo.nickName );
        // setup the variables needed to call the getFavoriteText function
        var favCount = $('.profileInfoFavorite span').attr('data-favorite-count');
        var isMyFav = false;
        if ($('.profileInfoFavorite .favorite_user').hasClass('myfavorite')) {
            isMyFav = true;
        }

        // set the favText with the getFavoriteText function
        var favText = WLFavorites.getFavoriteText(favCount, isMyFav, 'Trusted ');

        $('.profileInfoFavorite span').html(favText);

        // master function to handle change in dropdowns
        $('select', '#admin').change(function() {
            var value = $(this).val();
            var field = $(this).attr('id');
            if (field == 'w9status') {
                if (value == 'rejected') {
                    $('#reject-w9').dialog('open');
                    // we don't post this here, it's sent along with the reason
                    // for rejection by the dialog handler
                    return;
                }
            }

            $.ajax({
                type: 'post',
                url: './user/' + userInfo.user_id,
                dataType: 'json',
                data: {
                    value: $(this).val(),
                    field: $(this).attr('id'),
                    user_id: userInfo.user_id
                },
                success: function() {
                }
            });
        });

        // master function to handle checkbox changes
        $('input[type=checkbox]', '#admin').click(function() {
            // get the checkbox value
            var value = $(this).is(':checked') ? 1 : 0;
            // and the id of the field being changed
            var field = $(this).attr('id');

            $.ajax({
                type: 'post',
                url: './user/' + userInfo.user_id,
                dataType: 'json',
                data: {
                    value: value,
                    field: field,
                    user_id: userInfo.user_id
                },
                success: function(json) {
                    // if (json
                }
            });

        });

        $('#reject-w9').dialog({
            autoOpen: false,
            show: 'fade',
            hide: 'fade',
            buttons: [{
                text: 'Send notification',
                click: function() {
                    $.ajax({
                        type: 'post',
                        url: './user/' + userInfo.user_id,
                        dataType: 'json',
                        data: {
                            value: 'rejected',
                            field: 'w9status',
                            user_id: userInfo.user_id,
                            reason: $('#reject-reason').val()
                        },
                        success: function(json) {
                            $('#reject-w9').dialog('close');
                        }
                    });
                }
            }],
            open: function() {
                $('#reject-reason').bind('keyup', 'keydown', function() {
                    if ($(this).val().length > 100) {
                        $(this).val($(this).val().substring(0, 100));
                    } else {
                        $('#charCount').text(100 - $(this).val().length);
                    }
                });
            }
        });

        // custom function for setting salary
        $('#save_salary').click(function() {
            // Get the specified salary
            var salary = $('#annual_salary').val();
            // Get the manager
            var manager = $('#manager :selected').text();

            // if no manager, and a salary larger than 0, reject
            if (manager === 'None' && salary > 0) {
                alert('Users with salary must have a manager.');
                return false;
            }

            $.ajax({
                type: 'post',
                url: './user/' + userInfo.user_id,
                dataType: 'json',
                data: {
                    value: $('#annual_salary').val(),
                    'save-salary': true,
                    user_id: userInfo.user_id
                },
                success: function() {
                }
            });
        });

        $('#popup-pingtask').dialog({
            autoOpen: false,
            width: 50,
            height: "auto",
            resizable: false,
            position: [ 'top' ],
            show: 'fade',
            hide: 'fade',
            close: function() {
                $('#ping-msg').val('').css("height", "50px");
            }
        });
        $('#ping-msg').autogrow(80, 150);

        $('#send-ping-btn').click(function() {
            $('#send-ping-btn').attr("disabled", "disabled");
            var msg = $('#ping-msg').val();
            // always send email
            var journal = $('#echo-journal').is(':checked') ? 1 : 0;
            var cc = $('#copy-me').is(':checked') ? 1 : 0;
            var data = {
                'action': 'pingTask',
                'userid' : userInfo.user_id,
                'msg' : msg,
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
                        var success_msg = "<p><strong>Your message has been sent.</strong></p>";

                        Utils.emptyModal({
                            content: success_msg,
                            buttons: [
                                {
                                    content: 'Ok',
                                    className: 'btn-primary',
                                    dismiss: true
                                }
                            ]
                        });
                    }
                    $('#ping-msg').val("");
                    $('#send-ping-btn').removeAttr("disabled");
                },
                error: function() {
                    $('#send-ping-btn').removeAttr("disabled");
                }
            });
            return false;
        });

        WReview.initList();

        $('#nickname-ping, .nickname-ping').click(function() {
        $('#popup-pingtask').dialog('option', 'title', 'Message user: ' + $(this).text());
        $('#popup-pingtask form h5').html('Ping message:');
        $('#popup-pingtask').dialog('open');
            return false;
        });

        $('#give').click(function(){
            $('#budget-give-modal').modal('show');
        });

        $('#create_sandbox').click(function(){
            var projects = '';

            // get project ids that are newly checked - setup to allow adding
            // projects to sandbox that is already created, sandbox bash
            // script needs updating to support this
            $('#sandboxForm input[type=checkbox]:checked').not(':disabled').each(function() {
                if ($(this).prop('checked') && !$(this).prop('disabled')) {
                    projects += $(this).next('.repo').val() + ',';
                }
            });

            if (projects != '') {
                // remove the last comma
                projects = projects.slice(0, -1)
                $.ajax({
                    type: "POST",
                    url: './user/' + userInfo.user_id,
                    dataType: 'json',
                    data: {
                        action: "create-sandbox",
                        id: userInfo.user_id,
                        unixusername: $('#unixusername').val(),
                        projects: projects
                    },
                    success: function(json) {
                        if(json.error) {
                            alert("Sandbox Creation failed:"+json.error);
                        } else {
                            alert("Sandbox created successfully");
                        }
                    }
                });
            } else {
                alert('You did not choose any projects to check out.');
            }

            return false;
        });

        $('#pay-bonus').dialog({
            autoOpen: false,
            width: 720,
            show: 'fade',
            dialogClass: 'white-theme',
            resizable: false,
            hide: 'fade',
        });
        $('#budget-source-combo-bonus').chosen({width: 'auto'});
        var bonus_amount;

        $('#pay_bonus').click(function(e) {
            // clear form input fields
            $('#pay-bonus form input[type="text"]').val('');
            $('#pay-bonus').dialog('open');

            var regex_bid = /^(\d{1,3},?(\d{3},?)*\d{3}(\.\d{0,2})?|\d{1,3}(\.\d{0,2})?|\.\d{1,2}?)$/;

            bonus_amount = new LiveValidation('bonus-amount', {onlyOnSubmit: true });
            bonus_amount.add( Validate.Presence, { failureMessage: "Can't be empty!" });
            bonus_amount.add( Validate.Format, { pattern: regex_bid, failureMessage: "Invalid Input!" });
            bonus_budget = new LiveValidation('budget-source-combo-bonus', {
                onlyOnSubmit: true ,
                insertAfterWhatNode: "validationMessage"
            });
            bonus_budget.add( Validate.Exclusion, { within: [ 0 ], failureMessage: "You must select a budget!" });

            UserInfo.getBonusHistory(1);
        });

        $('#pay-bonus form').submit(function() {

            if (bonus_amount.validate() && bonus_budget.validate()) {
                if (confirm('Are you sure you want to pay $' + $('#bonus-amount').val() + ' to ' + userInfo.nickName + '?')) {
                    $('#pay-bonus').dialog('close');
                    $.ajax({
                        url: 'api.php?action=payBonus',
                        data: $('#pay-bonus form').serialize(),
                        dataType: 'json',
                        type: "POST",
                        cache: false,
                        success: function(json) {
                            if (json.success) {
                                alert(json.message);
                            } else {
                                alert(json.message);
                            }
                        },
                        error: function(json) {
                            alert('error');
                        }
                    });
                }
            }

            return false;
        });

        var limitPerPage = 10;

        $('#runnersAccordion').accordion({
            clearStyle: true,
            collapsible: true,
            active: true,
            create: function(event, ui) {
                var workersIntervalId = setInterval(function() {
                    if($("#runner-workers tr").length) {
                        $('#runner-workers').paginate(limitPerPage, 500);
                        clearInterval(workersIntervalId);
                    }
                }, 2000);
                var intervalId = setInterval(function() {
                    if($("#runner-projects tr").length) {
                        $('#runner-projects').paginate(limitPerPage, 500);
                        clearInterval(intervalId);
                    }
                }, 2000);
            }
        });

        if (! is_payer) {
            $('#ispaypalverified').prop('disabled', true);
            $('#isw9approved').prop('disabled', true);
            $('#isw2employee').prop('disabled', true);
        } else {
            $('#isw2employee').click(function () {
                if ($('#isw2employee').is(':checked')) {
                    $('#ispaypalverified').removeAttr('checked');
                    $('#w9status').val('not-applicable');
                } else {
                    $('#ispaypalverified').removeAttr('disabled');
                }
            });
            $('#ispaypalverified').click(function() {
                if ($(this).is(':checked')) {
                    $('#isw2employee').removeAttr('checked');
                } else {
                    $('#isw2employee') .removeAttr('disabled');
                }
            });
            $('#w9status').change(function() {
                if ($(this).val() == 'not-applicable') {
                    $('#isw2employee') .removeAttr('disabled');
                } else {
                    $('#isw2employee') .removeAttr('checked');
                }
            });
        }
        if (userInfo.tab != "") {
            $("#" + userInfo.tab).click();
        }
    },

    appendPagination: function(page, cPages, table) {
        var cspan = '4'
        var pagination = '<tr bgcolor="#FFFFFF" class="row-' + table + '-live ' + table + '-pagination-row" >' +
                         '<td colspan="' + cspan + '" style="text-align:center;">';
        if (page > 1) {
            pagination += '<a href="#" onclick="UserInfo.getBonusHistory(' + (page - 1) + ')" title="' +
                          (page - 1) + '">Prev</a> &nbsp;';
        }
        for (var i = 1; i <= cPages; i++) {
            if (i == page) {
                pagination += i + " &nbsp;";
            } else {
                pagination += '<a href="#" onclick="UserInfo.getBonusHistory(' + i + ')" title="' + i +
                              '">' + i + '</a> &nbsp;';
            }
        }
        if (page < cPages) {
            pagination += '<a href="#" onclick="UserInfo.getBonusHistory(' + (page + 1) + ')" title="' +
                          (page + 1) + '">Next</a> &nbsp;';
        }
        pagination += '</td></tr>';
        $('.table-' + table).append(pagination);
    },

    appendRow: function(json, table) {
        var pre = '', post = '';
        var row;

        row = '<tr>';

        row += '<td>' + pre + json[0] + post + '</td>'; // Date
        row += '<td>' + pre + '$' + json[1] + post + '</td>'; // Amount
        row += '<td>' + pre + json[2] + post + '</td>'; // Receiver
        row += '<td>' + pre + json[3] + post + '</td>'; // Description

        row += '</tr>';

        $('.table-' + table).append(row);
    },

    getBonusHistory: function(page) {
        $.ajax({
            cache: false,
            type: 'GET',
            url: 'api.php',
            data: {
                action: 'getBonusHistory',
                uid: user_id,
                rid: UserInfo.user_id,
                page: page
            },
            dataType: 'json',
            success: function(json) {
                var footer = '<tr bgcolor="#FFFFFF"><td colspan="4" style="text-align:center;">No bonus history yet</tr>';
                $('.bonus-history').find("tr:gt(0)").remove();

                for (var i = 1; i < json.length; i++) {
                    UserInfo.appendRow(json[i], 'bonus-history');
                }

                // If there's only one page don't add the pagination
                if (json.length > 0 && json[0][2] > 1) {
                    UserInfo.appendPagination(json[0][1], json[0][2], 'bonus-history');
                } else if(json[0][0] == 0 || (json.length > 0 && json[0][2] == 0)) {
                    $('.bonus-history').append(footer);
                }
            }
        });
    }
 };
