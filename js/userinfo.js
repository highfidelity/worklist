var UserInfo = {

    init: function() {
        // admin settings
        // checkboxes and dropdowns are handled by master functions below
        // the dropdown value should always be an integer

        // set the current values
        $('#manager').val(userInfo.manager);
        $('#referrer').val(userInfo.referred_by);

        // master function to handle change in dropdowns
        $('select', '#admin').change(function() {
            var value = $(this).val();
            var field = $(this).attr('id');
            if (field == 'w9status') {
                if (value == 'rejected') {
                    Utils.emptyFormModal({
                        title: 'Reject W9',
                        content:
                            '<div class="row">' +
                            '  <div class="col-md-3">' +
                            '    <label for="w9reject-reason">Reason:</label>' +
                            '  </div>' +
                            '  <div class="col-md-9">' +
                            '    <input type="text" class="form-control" id="w9reject-reason" name="reason" />' +
                            '  </div>' +
                            '</div>',
                        buttons: [
                            {
                                type: 'submit',
                                name: 'confirm',
                                content: 'Confirm',
                                className: 'btn-primary',
                                dismiss: false
                            }
                        ],
                        open: function(modal) {
                            $('form', modal).submit(function() {
                                UserInfo.setW9Status('rejected', $('input[name="reason"]', modal).val(), function(data) {
                                    if (data.success) {
                                        $(modal).modal('hide');
                                    }
                                });
                                return false;
                            });
                        }
                    });
                } else {
                    UserInfo.setW9Status(value);
                }
                return;
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

        $('.reviewAddLink').click(UserInfo.reviewDialog);

        $('#give').click(function(){
            Utils.modal('budget-give', {
                receiver_id: userInfo.user_id,
                budgetAuthorized: budgetAuthorized,
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
                                $('input[name="source_id"]', modal).val(budget);
                                $('button[name="give"]', modal).html(
                                    '<span>' + $(this).attr('reason') + '</span> ' +
                                    '($' + $(this).attr('remaining') + ') ' +
                                    '<span class="caret"></span>'
                                );
                                if (!$('button[name="give_budget"]', modal).length) {
                                    var confirm = $('<button>')
                                        .attr({
                                            type: 'submit',
                                            name: 'give_budget'
                                        })
                                        .addClass('btn btn-primary')
                                        .text('Confirm give');
                                    $('.modal-footer > .row > div:last-child', modal).append(confirm);
                                }
                            });
                        }
                    });

                    $('input[name="budget-seed"]', modal).on('change', function() {
                        if (this.checked) {
                            $('.modal-footer .row > div').show();
                            $('.modal-footer .row > div:eq(3)').hide();
                        } else {
                            $('.modal-footer .row > div:eq(1), .modal-footer .row > div:eq(2)').hide();
                            $('.modal-footer .row > div:eq(3)').show();
                        }
                    });
                    $('input[name="budget-seed"]', modal).change();

                    $('form', modal).submit(function() {
                        $.ajax({
                            url: './budget/give',
                            data: {
                                receiver_id: $('input[name="receiver_id"]', modal).val(),
                                reason: $('input[name="budget-reason"]', modal).val(),
                                amount: parseFloat($('input[name="amount"]', modal).val()),
                                budget_seed: $('input[name="budget-seed"]', modal).is(':checked') ? 1 : 0,
                                source_txt: $('input[name="budget-source"]', modal).val(),
                                source_id: $('input[name="source_id"]', modal).val(),
                                budget_note: $('#budget-note').val()
                            },
                            dataType: 'json',
                            type: "POST",
                            cache: false,
                            success: function(json) {
                                if (json.success) {
                                    $(modal).modal('hide');
                                }
                            }
                        });
                        return false;
                    });
                }
            });
            return false;
        });

        $('#budgetHistory').click(function(){
            Budget.showBudgetHistory(1, userInfo.user_id);
            return false;
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

        $('#budget-source-combo-bonus').chosen({width: 'auto'});
        var bonus_amount;

        $('#pay_bonus').click(function(e) {
            Utils.modal('paybonus', {
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
                                $('button[name="pay"]', modal).html(
                                    '<span>' + $(this).attr('reason') + '</span> ' +
                                    '($' + $(this).attr('remaining') + ') ' +
                                    '<span class="caret"></span>'
                                );
                                if (!$('button[name="pay_bonus"]', modal).length) {
                                    var confirm = $('<button>')
                                        .attr({
                                            type: 'submit',
                                            name: 'pay_bonus'
                                        })
                                        .addClass('btn btn-primary')
                                        .text('Confirm Pay');
                                    $('.modal-footer', modal).append(confirm);
                                }
                            })
                        }
                    });

                    var regex_bid = /^(\d{1,3},?(\d{3},?)*\d{3}(\.\d{0,2})?|\d{1,3}(\.\d{0,2})?|\.\d{1,2}?)$/;
                    bonus_amount = new LiveValidation($('input[name="amount"]', modal)[0], {onlyOnSubmit: true});
                    bonus_amount.add( Validate.Presence, {failureMessage: "Can't be empty!"});
                    bonus_amount.add( Validate.Format, {pattern: regex_bid, failureMessage: "Invalid Input!"});

                    $('form', modal).submit(function() {
                        if (!bonus_amount.validate()) {
                            return false;
                        }
                        $.ajax({
                            url: './user/payBonus/' + userInfo.nickName,
                            data: {
                                budget: $('input[name="budget_id"]', modal).val(),
                                amount: $('input[name="amount"]', modal).val(),
                                reason: $('input[name="reason"]', modal).val()
                            },
                            dataType: 'json',
                            type: "POST",
                            cache: false,
                            success: function(json) {
                                if (json.success) {
                                    $(modal).modal('hide');
                                }
                            }
                        });
                        return false;
                    });

                }
            });
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

        $('#total-jobs').click(function() {
            UserStats.showDoneJobs(1, userInfo.user_id);
            return false;
        });

        $('#runner-total-jobs').click(function() {
            UserStats.showDesignerTotalJobs(1, userInfo.user_id);
            return false;
        });

        $('#runner-active-jobs').click(function() {
            UserStats.showDesignerActiveJobs(1, userInfo.user_id);
            return false;
        });

        $('#latest-earnings').click(function(){
            UserStats.showLatestEarnings(1, userInfo.user_id);
            return false;
        });

        $('#profile-nav a').click(function (event) {
            event.preventDefault();
            $(this).tab('show');
        });
        var tab = document.URL.split('#').pop();
        if ($('#profile-nav a[href="#' + tab + '"]').length) {
            $('#profile-nav a[href="#' + tab + '"]').click();
        }

    },

    setW9Status: function(status, reason, fAfter) {
        $.ajax({
            type: 'POST',
            url: './user/setW9Status/' + userInfo.user_id + '/' + status,
            dataType: 'json',
            data: {
                reason: reason
            },
            success: function(json) {
                if (fAfter) {
                    fAfter(json);
                }
            }
        });
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
    },

    reviewDialog: function() {
        $.ajax({
            url: './user/review/' + userInfo.nickName,
            dataType: 'json',
            success: function(data) {
                Utils.emptyFormModal({
                    title: 'My review for <a href="./user/' + userInfo.nickName + '">' + userInfo.nickName + '</a>',
                    content:
                        '<div class="row">' +
                        '  <div class="col-md-12">' +
                        '    <label for="myreview">My Review:</label>' +
                        '    <textarea class="form-control" id="myreview" name="myreview">' + data.myReview + '</textarea>' +
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
                            $.ajax({
                                type: 'POST',
                                url: './user/review/' + userInfo.nickName,
                                data: {
                                    userReview: $('textarea[name="myreview"]', modal).val()
                                },
                                dataType: 'json',
                                success: function(data) {
                                    if (data.success) {
                                        $(modal).modal('hide');
                                        window.location = './user/' + userInfo.nickName;
                                    }
                                }
                            });
                            return false;
                        });
                    }
                });
            }
        });
        return false;
    }
 };
