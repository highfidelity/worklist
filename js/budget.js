var Budget = {
    init : function() {
        $('#budget-give-modal form button[type="submit"]').click(function(event) {
            event.preventDefault();
            $('#budget-give-modal form button[type="submit"]').attr("disabled", "disabled");
            $.ajax({
                url: 'api.php',
                data: {
                    action: 'updateBudget',
                    receiver_id: $('#userid').val(),
                    reason: $('#budget-reason').val(),
                    amount: parseFloat($('#budget-give-modal input[name="amount"]').val().replace('$', '')),
                    budget_seed: $('#budget-give-modal input[name="budget-seed"]').is(':checked') ? 1 : 0,
                    budget_source: $('#budget-give-modal input[name="budget-source"]').val(),
                    budget_source_combo: $('#budget-give-modal select[name="budget-source-combo"]').val(),
                    budget_note: $('#budget-note').val(),
                    add_funds_to: 0
                },
                dataType: 'json',
                type: "POST",
                cache: false,
                success: function(json) {
                    $('#budget-give-modal form button[type="submit"]').removeAttr('disabled');
                    if (json.success) {
                        $('#budget-give-modal').modal('hide');
                        setTimeout(function() {
                            alert(json.message);
                            Budget.budgetHistory({
                                inDiv: "tabs",
                                id: $('#userid').val(),
                                page: 1
                            });
                        }, 50);
                        $("#isrunner").prop('checked', true);
                    } else {
                        alert(json.message);
                    }
                },
                error: function(json) {
                    if (json.message) {
                        alert(json.message);
                    } else {
                        alert('All fields are required');
                    }
                }
            });
            return false;
        });
        $(".currentBudgetArea").click(function() {
            $('#budget-dialog').data("fromUserid", "n");
            $('#budget-dialog').dialog('open');
            return false;
        });
        $(".givenBudgetArea").click(function() {
            $('#budget-dialog').data("fromUserid", "y");
            $('#budget-dialog').dialog('open');
            return false;
        });
        if ($('#budget-give-modal input[name="budget-seed"]').length == 0) {
            $('#budget-give-modal input[name="budget-source"]').hide();
            $('#budget-give-modal #budget-source-combo-area').show();
        } else {
            var toggleSeedBudget = function() {
                if ($(this).is(':checked')) {
                    $('#budget-give-modal input[name="budget-source"]').show();
                    $('#budget-give-modal #budget-source-combo-area').hide();
                } else {
                    $('#budget-give-modal input[name="budget-source"]').hide();
                    $('#budget-give-modal #budget-source-combo-area').show();
                }
            };
            $('#budget-give-modal input[name="budget-seed"]').click(toggleSeedBudget);
            toggleSeedBudget();
        }
        $('#budget-dialog').dialog({
            autoOpen: false,
            modal: true,
            width: 750,
            height: 340,
            title: 'Budget details',
            show: 'fade',
            hide: 'fade',
            open: function() {
                var fromUserid = "&fromUserid=" + $('#budget-dialog').data("fromUserid");
                if ($('#budget-dialog').data("fromUserid") == "n") {
                    $('#budget-dialog').dialog("option", "title", "All budget grants assigned to user");
                } else {
                    $('#budget-dialog').dialog("option", "title", "All budget grants from you");
                }
                $('#budget-dialog').load("api.php?action=budgetHistory&inDiv=budget-dialog&id=" + $('#budget-dialog').data("userid") + "&page=1" + fromUserid);
            }
        });
        $('#budget-source-combo').chosen({width: 'auto'});
    },

    budgetHistory: function(options) {
        if (!options.page) {
            var lastPage = $("#" + options.inDiv + " #budgetHistoryContent").data("lastPage");
            if (!lastPage) {
                options.page = 1;
            } else {
                options.page = lastPage;
            }
        }
        if (!options.fromUserid) {
            options.fromUserid = "";
        }
        $("#" + options.inDiv + " #budgethistory").data("lastPage", options.page);
        $.ajax({
            type: 'post',
            url: 'api.php',
            dataType: 'html',
            data: {
                action: 'budgetHistory',
                inDiv: options.inDiv,
                id: options.id,
                page: options.page,
                fromUserid: options.fromUserid
            },
            success: function(data) {
                $('#budgethistory').html(data);
            }
        });

    },

    initAddFunds: function() {
        var budget_seed = $('#add-funds-modal input[name="budget-seed"]').val() != 0 ? 1 : 0;
        if (budget_seed) {
            $('#add-funds-modal input[name="budget-source"]').show();
            $('#add-funds-modal #budget-source-combo-area').hide();
        } else {
            $('#add-funds-modal input[name="budget-source"]').hide();
            $('#add-funds-modal #budget-source-combo-area').show();
        }
        $('#add-funds-modal select[name="budget-source-combo"]').chosen({width: 'auto'});
        $("#amountToAdd").blur(function(){
            var amountToAdd = parseFloat($("#amountToAdd").val()),
                budgetAmount = parseFloat($('#budget-update-modal input[name="amount"]').val().replace('$', ''));
            if (!isNaN(amountToAdd + budgetAmount)) {
                $("#newBudgetTotal").html(amountToAdd + budgetAmount);
            } else {
                $("#newBudgetTotal").html("");
            }
        });
        $('#add-funds-modal form button[type="submit"]').click(function() {
            $('#add-funds-modal form button[type="submit"]').attr("disabled", "disabled");
            $.ajax({
                url: 'api.php',
                data: {
                    action: 'updateBudget',
                    receiver_id: $('#userid').val(),
                    reason: "",
                    amount: $('#amountToAdd').val(),
                    budget_seed: budget_seed,
                    budget_source: $('#add-funds-modal input[name="budget-source"]').val(),
                    budget_source_combo: $('#add-funds-modal select[name="budget-source-combo"]').val(),
                    budget_note: "",
                    add_funds_to: $('#add_funds_to').val()
                },
                dataType: 'json',
                type: "POST",
                cache: false,
                success: function(json) {
                    $('#add-funds-modal form button[type="submit"]').removeAttr('disabled');
                    if (json.success) {
                        $('#add-funds-modal').modal('hide');
                        $('#budget-update-modal').modal("hide");
                        setTimeout(function() {
                            alert(json.message);
                            Budget.budgetHistory({
                                inDiv: "tabs",
                                id: $('#userid').val(),
                                page: 1
                            });
                        }, 50);
                        $("#isrunner").prop('checked', true);
                    } else {
                        alert(json.message);
                    }
                },
                error: function(json) {
                    if (json.message) {
                        alert(json.message);
                    } else {
                        alert('All fields are required');
                    }
                }
            });
            return false;
        });

    },

    initUpdateDialog: function() {
        $('#addFundsButton').click(function(){
            $('#budget-update-modal').modal('hide');
            $('#add-funds-modal').modal('show');
            $('#newBudgetTotal').html('0');
            $.ajax({
                type: "POST",
                url: 'api.php',
                data: {
                    action: "budgetInfo",
                    method: "getViewAddFunds",
                    budgetId: $('#budget-update-modal').data("budgetId")
                },
                dataType: 'json',
                success: function(json) {
                    if (json && json.succeeded) {
                        data = json.params;
                        $("#budget-seed").val(data.seed == 1 ? 1 : 0);
                        $('#add_funds_to').val(data.budget_id);
                     } else {
                        alert(json.message);
                    }
                },
                error: function(json) {
                    alert('error in addFunds');
                }
            });
            return false;
        });
        $("#updateButton").click(function() {
            $.ajax({
                type: "POST",
                url: 'api.php',
                data: {
                    action: "budgetInfo",
                    method: "updateBudget",
                    budgetId: $('#budget-update-modal').data("budgetId"),
                    budgetReason: $('#budget-reason').val(),
                    budgetNote: $('#budget-note').val()
                },
                dataType: 'json',
                success: function(json) {
                    if (json && json.succeeded) {
                        $('#budget-update-modal').modal("hide");
                        Budget.budgetHistory({
                            inDiv: "tabs",
                            id: $('#userid').val()
                        });
                     } else {
                        alert(json.message);
                    }
                },
                error: function(json) {
                    alert('error in updateBudget');
                }
            });
        });
        $("#closeOutButton").click(function() {
            $.ajax({
                type: "POST",
                url: 'api.php',
                data: {
                    action: "budgetInfo",
                    method: "closeOutBudget",
                    budgetId: $('#budget-update-modal').data("budgetId")
                },
                dataType: 'json',
                success: function(json) {
                    if (json && json.succeeded) {
                        $('#budget-update-modal').modal("hide");
                        Budget.budgetHistory({
                            inDiv: "tabs",
                            id: $('#userid').val()
                        });
                     } else {
                        alert(json.message);
                    }
                },
                error: function(json) {
                    alert('error in updateBudget');
                }
            });
        });
    },

    initBudgetList: function() {
        $('#budgetAllocated > td').click(function() {
            var budgetId = $('#budget-update-modal').data("budgetId");
            Budget.budgetExpand(0, budgetId);
        });
        $('#budgetSubmitted > td').click(function() {
            var budgetId = $('#budget-update-modal').data("budgetId");
            Budget.budgetExpand(1, budgetId);
        });
        $('#budgetPaid > td').click(function() {
            var budgetId = $('#budget-update-modal').data("budgetId");
            Budget.budgetExpand(2, budgetId);
        });
        $('#budgetTransferred > td').click(function() {
            var budgetId = $('#budget-update-modal').data("budgetId");
            Budget.budgetExpand(3, budgetId);
        });
        $("tr.budgetRow").live("click",function() {
            $('#budget-update-modal').data("budgetId", $(this).attr('data-budgetid'));
            $.ajax({
                type: "POST",
                url: 'api.php',
                data: {
                    action: "budgetInfo",
                    method: "getUpdateView",
                    budgetId: $('#budget-update-modal').data("budgetId")
                },
                dataType: 'json',
                success: function(json) {
                    if (json && json.succeeded) {
                        data = json.params;
                        $('#budget-update-modal input[name="amount"]').val('$' + data.amount);
                        $('#budget-update-modal input[name="budget-reason"]').val(data.reason).attr({disabled: data.closed == 1});
                        if ((data.req_user_authorized && data.seed == 1) || data.seed == 0) {
                            $('#budget-sources-table').show();
                        } else {
                            $('#budget-sources-table').hide();
                        }
                        $('#budget-sources-table > table > tbody > tr').remove();
                        for(var i=0; i< data.sources.length; i++) {
                            var source = data.sources[i];
                            html =
                                '<tr>' +
                                '  <td>' + source.transfer_date + '</td>' +
                                '  <td>' + source.nickname + '</td>' +
                                '  <td>' + source.amount_granted + '</td>' +
                                '  <td>' +
                                    (
                                        data.seed == 0
                                            ?  'Budget ID: ' + source.budget_id + ' - ' + source.reason
                                            :  'Seed Budget: ' + source.source_data
                                    ) +
                                '</td>' +
                                '</tr>';
                            $(html).appendTo('#budget-sources-table > table > tbody');
                        }
                        $('#budget-note').val(data.notes).attr({disabled: data.closed == 1});
                        $('#budgetRemainingFunds > td:last-child').text('$' + data.remaining);
                        $('#budgetAllocated > td:last-child').text('$' + data.allocated);
                        $('#budgetSubmitted > td:last-child').text('$' + data.submitted);
                        $('#budgetPaid > td:last-child').text('$' + data.paid);
                        $('#budgetTransferred > td:last-child').text('$' + data.transferred);

                        if (data.closed == 1) {
                            $('#updateButton').hide();
                            $('#closeButton').show();
                        } else {
                            $('#updateButton').show();
                            $('#closeButton').hide();
                        }
                    } else {
                        $('#budget-update-modal').modal("hide");
                        alert(json.message);
                    }
                },
                error: function(json) {
                    $('#budget-update-modal').modal("hide");
                    alert('error in getUpdateView');
                }
            });
            $('#budget-update-modal').modal('show');
            return false;
        });
    },

    displayHistory: function(user_id) {
        $('#budgetPopup').dialog('close');
        window.location = './user/' + user_id + '&tab=tabBudgetHistory';
    },

    /**
    * Show a dialog with expanded info on the selected @section
    * Sections:
    *  - 0: Allocated
    *  - 1: Submited
    *  - 2: Paid
    *  - 3: Transfered
    */
    budgetExpand: function(section, budget_id) {
        $('#add-funds-modal').modal('hide');
        $('#budget-update-modal').modal("hide");

        $('#be-search-field').val('');
        $('#be-search-field').keyup(function() {
            // Search current text in the table by hiding rows
            var search = $(this).val().toLowerCase();

            $('.data_row').each(function() {
                var html = $(this).text().toLowerCase();
                // If the Row doesn't contain the pattern hide it
                if (!html.match(search)) {
                    $(this).fadeOut('fast');
                } else { // If is hidden but matches the pattern, show it
                    if (!$(this).is(':visible')) {
                        $(this).fadeIn('fast');
                    }
                }
            });
        });
        $('#bet-search-field').val('');
        $('#bet-search-field').keyup(function() {
            // Search current text in the table by hiding rows
            var search = $(this).val().toLowerCase();

            $('.data_row').each(function() {
                var html = $(this).text().toLowerCase();
                // If the Row doesn't contain the pattern hide it
                if (!html.match(search)) {
                    $(this).fadeOut('fast');
                } else { // If is hidden but matches the pattern, show it
                    if (!$(this).is(':visible')) {
                        $(this).fadeIn('fast');
                    }
                }
            });
        });
        // If clean search, fade in any hidden items
        $('#be-search-clean').click(function() {
            $('#be-search-field').val('');
            $('.data_row').each(function() {
                $(this).fadeIn('fast');
            });
        });
        // If clean search, fade in any hidden items
        $('#bet-search-clean').click(function() {
            $('#bet-search-field').val('');
            $('.data_row').each(function() {
                $(this).fadeIn('fast');
            });
        });
        $('#budget-expanded').dialog({
            modal: false,
            autoOpen: false,
            position: ['middle'],
            resizable: false,
            dialogClass: 'white-theme'});
        $('#budget-transferred').dialog('option', 'dialogClass', 'white-theme');
        $('#budget-transferred').dialog({
            modal: false,
            autoOpen: false,
            position: ['middle'],
            resizable: false,
            dialogClass: 'white-theme'});

        switch (section) {
            case 0:
                // Fetch new data via ajax
                $('#budget-expanded').dialog('open');
                Budget.be_getData(section, budget_id);
                break;
            case 1:
                // Fetch new data via ajax
                $('#budget-expanded').dialog('open');
                Budget.be_getData(section, budget_id);
                break;
            case 2:
                // Fetch new data via ajax
                $('#budget-expanded').dialog('open');
                Budget.be_getData(section, budget_id);
                break;
            case 3:
                // Fetch new data via ajax
                $('#budget-transferred').dialog('open');
                Budget.be_getData(section, budget_id);
                break;
        }
    },

    showBudgetTransferDialog: function() {
        $('#budget-transfer').dialog('option', 'position', ['center', 'center']);
        $('#budget-transfer').dialog('option', 'dialogClass', 'white-theme');
        $('#budget-transfer').addClass('table-popup');
        $('#budget-transfer').dialog('open');
    },

    be_attachEvents: function(section, budget_id) {
        $('#be-id').click(function() {
            Budget.be_handleSorting(section, budget_id, $(this));
        });
        $('#be-budget').click(function() {
            Budget.be_handleSorting(section, budget_id, $(this));
        });
        $('#be-summary').click(function() {
            Budget.be_handleSorting(section, budget_id, $(this));
        });
        $('#be-who').click(function() {
            Budget.be_handleSorting(section, budget_id, $(this));
        });
        $('#be-amount').click(function() {
            Budget.be_handleSorting(section, budget_id, $(this));
        });
        $('#be-status').click(function() {
            Budget.be_handleSorting(section, budget_id, $(this));
        });
        $('#be-created').click(function() {
            Budget.be_handleSorting(section, budget_id, $(this));
        });
        $('#be-paid').click(function() {
            Budget.be_handleSorting(section, budget_id, $(this));
        });
        $('#bet-id').click(function() {
            Budget.be_handleSorting(section, budget_id, $(this));
        });
        $('#bet-budget').click(function() {
            Budget.be_handleSorting(section, budget_id, $(this));
        });
        $('#bet-notes').click(function() {
            Budget.be_handleSorting(section, budget_id, $(this));
        });
        $('#bet-who').click(function() {
            Budget.be_handleSorting(section, budget_id, $(this));
        });
        $('#bet-amount').click(function() {
            Budget.be_handleSorting(section, budget_id, $(this));
        });
        $('#bet-created').click(function() {
            Budget.be_handleSorting(section, budget_id, $(this));
        });
    },

    be_getData: function(section, budget_id, item, desc) {
        // Clear old data
		var table = '';
		if (section == 3) {
			table = '#table-budget-transferred';
		} else {
			table = '#table-budget-expanded';
		}

        var header = $(table).children()[0];
        $(table).children().remove();
        $(table).append(header);
        Budget.be_attachEvents(section, budget_id);

        var params = '?action=manageBudget&section=' + section + '&budget_id=' + budget_id;
        var sortby = '';
        // If we've got an item sort by it
        if (item) {
            sortby = item.attr('id');
            params += '&sortby='+sortby+'&desc='+desc;
        }
        $.getJSON('api.php'+params, function(data) {
            // Fill the table
            for (var i = 0; i < data.length; i++) {
                if (section == 3 ) {
                    var row =
                        '<tr class="data_row">' +
                        '    <td>#' + data[i].id + '</td>' +
                        '    <td title="' + data[i].budget_title + '">' +
                        '        ' + data[i].budget_title +
                        '    </td>' +
                        '    <td>' + data[i].notes + '</td>' +
                        '    <td>' +
                        '        <a href="./user/' + data[i].receiver_id + '" >' + data[i].who + '</a>' +
                        '    </td>' +
                        '    <td>$' + data[i].amount + '</td>' +
                        '    <td>' + data[i].created + '</td>' +
                        '</tr>';
                    $('#table-budget-transferred').append(row);

                } else {
                    var link = '<a href="./' + data[i].id + '" >';
                    // Separate "who" names into an array so we can add the userinfo for each one
                    var who = (data[i].who === false) ? new Array() : data[i].who.split(", ");
                    var who_link = '';
                    for (var z = 0; z < who.length; z++) {
                        who[z] = '<a href="./user/' + data[i].ids[z] + '" >' + who[z] + '</a> ';
                        if (z < who.length - 1) {
                            who[z] += ', ';
                        }
                        who_link += who[z];
                    }

                    var row =
                        '<tr class="data_row">' +
                        '    <td>' + link + '#' + data[i].id + '</a></td>' +
                        '    <td title="' + data[i].budget_title + '">' +
                        '        ' + data[i].budget_id +
                        '    </td>' +
                        '    <td>' + link + data[i].summary + '</a></td>' +
                        '    <td>' + who_link + '</td>' +
                        '    <td>$' + data[i].amount + '</td>' +
                        '    <td>' + data[i].status + '</td>' +
                        '    <td>' + data[i].created + '</td>' +
                        '    <td>' + data[i].paid + '</td>' +
                        '</tr>';
                    $('#table-budget-expanded').append(row);
                }
			}
        });
        $('#budget-report-export').click(function() {
            window.location = 'api.php?action=manageBudget&section='+section+'&method=export';
        });
        $('#budget-report-export-transferred').click(function() {
            window.location = 'api.php?action=manageBudget&section=' + section + '&method=export&budget_id=' + budget_id;
        });
    },

    be_handleSorting: function(section, budget_id, item) {
        var desc = true;
        if (item.hasClass('desc')) {
            desc = false;
        }

        // Cleanup sorting
        Budget.be_cleaupTableSorting();
        item.removeClass('asc');
        item.removeClass('desc');

        // Add arrow
        var arrow_up = '<div style="float:right;">'+
                       '<img src="images/arrow-up.png" height="15" width="15" alt="arrow"/>'+
                       '</div>';

        var arrow_down = '<div style="float:right;">'+
                         '<img src="images/arrow-down.png" height="15" width="15" alt="arrow"/>'+
                         '</div>';

        if (desc) {
            item.append(arrow_down);
            item.addClass('desc');
        } else {
            item.append(arrow_up);
            item.addClass('asc');
        }

        // Update Data
        Budget.be_getData(section, budget_id, item, desc);
    },

    be_cleaupTableSorting: function() {
        $('#be-id').children().remove();
        $('#be-budget').children().remove();
        $('#be-summary').children().remove();
        $('#be-who').children().remove();
        $('#be-amount').children().remove();
        $('#be-status').children().remove();
        $('#be-created').children().remove();
        $('#be-paid').children().remove();
        $('#bet-id').children().remove();
        $('#bet-budget').children().remove();
        $('#bet-notes').children().remove();
        $('#bet-who').children().remove();
        $('#bet-amount').children().remove();
        $('#bet-created').children().remove();
    }

};

$(function() {
    Budget.initBudgetList();
    Budget.initUpdateDialog();
    Budget.initAddFunds();
    Budget.init();
    $('#budget-expanded').dialog({ autoOpen: false, width:780, show:'fade', hide:'drop' });
    $('#budget-transferred').dialog({ autoOpen: false, width:780, show:'fade', hide:'drop' });
    $('#budget-transfer').dialog({ autoOpen: false, width:780, show:'fade', hide:'drop' });

});
