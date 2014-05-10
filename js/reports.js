
var timeoutId;
var ttlPaid = 0;
var paid_list = [];
var workitem = 0;
var workitems;
var filterName = ".reports";
var _fromDate, _toDate;
var fromDate = '';
var toDate = '';
var datePickerControl; // Month/Year date picker.
var dateChangedUsingField = false; // True  if the date was changed using date field rather than picker.
    /**
    *
    */
    function withdraw_fee(fee_id) {
        var ajax_connection = $.get('api.php',
            {
                action: 'wdFee',
                wd_fee_id: fee_id
            },
            function(data) {
                if (data = 'Update Successful!') {
                    $('#workitem-'+fee_id).remove();
                }
                alert(data);
            }
        );
    }

function fmtDate(d) {
    return '' + (d.getMonth()+1) + '/' + d.getDate() + '/' + d.getFullYear();
}

function fmtDate2(d) {
    return d.getFullYear() + '-' + String(d.getMonth() + 101).slice(-2) + '-' + String(d.getDate() + 100).slice(-2);
}

var getPaidItems = function() {
    var paidItems = 0;
    $(paid_list).each(function(idx, checked) {
       if (!checked) {
           return;
       }
       paidItems++;
    });
    return paidItems;
};

function AppendPagination(page, cPages, table) {
    if(table == 'worklist' || table == 'worklist-payee') {
         if(table == 'worklist') {
            if (is_payer) {
                cspan = '8'
            } else {
                cspan = '6'
            }
        } else if(table == 'worklist-payee') {
            cspan = '4';
        }
        var pagination = '<tr bgcolor="#FFFFFF" class="row-' + table + '-live ' + table + '-pagination-row" ><td colspan="'+cspan+'" style="text-align:center;">Pages : &nbsp;';
        if (page > 1) {
            pagination += '<a href="./reports?page=' + (page-1) + '" title="'+(page-1)+'">Prev</a> &nbsp;';
        }
        for (var i = 1; i <= cPages; i++) {
            if (i == page) {
                pagination += i + " &nbsp;";
            } else {
                pagination += '<a href="./reports?page=' + i + '" title="'+i+'">' + i + '</a> &nbsp;';
            }
        }
        if (page < cPages) {
            pagination += '<a href="./reports?page=' + (page+1) + '" title="'+(page+1)+'">Next</a> &nbsp;';
        }
        pagination += '</td></tr>';
        $('.table-' + table).append(pagination);
    }
}


// json row fields: id, summary, status, payee, fee
function AppendRow(json, odd) {
    var pre = '', post = '';
    var row;

    row = '<tr id="workitem-' + json[1] + '" class="row-worklist-live ';
    if (odd) { row += 'rowodd' } else { row += 'roweven' }
    row += '">';
    if (is_payer) {
        row += '<td><input type="checkbox" name="fee_id[]" value="' + json[1] + '" data="' + json[5] + '" class="workitem-paid" /> </td>';
        row += '<td> <a href="javascript: void();" onclick="withdraw_fee(\'' + json[1] + '\')">Void</a> </td>';
    }
    if (json[0] == 0) {
    row += '<td>' + pre + 'Bonus' + post + '</td>'; // Id
    }
    if (json[0] != 0) {
    pre = '<a href="./'+json[0]+'">';
    post = '</a>';
    row += '<td>' + pre + json[0] + post + '</td>'; // Id
    }
    pre = '', post = '';
    if (json[0] == 0) {
        row += '<td>' + pre + 'Bonus Payment' + post + '</td>'; // Summary
        }
    if (json[0] != 0) {
    row += '<td>' + pre + json[2] + post + '</td>'; // Summary
    }
    row += '<td>' + pre + json[3] + post + '</td>'; // Description
    row += '<td';
    if (json[11] == 1) {
        row += ' class="greenText"';
    } else {
        if (json[7] == 0) {
            row += ' class="redtext"';
        }
    }
    row += '>' + pre + formatValueForDisplay(json[4]) + post + '</td>'; // Payee
    row += '<td>' + pre + formatValueForDisplay(json[6]) + post; // Paid Date
    if (json[9] == 1) {
        row += ' (r)' + '</td>';
    }
    row += '<td>' + pre + '$' + json[5] + post + '</td>'; // Amount
    row += '</tr>';

    $('.table-worklist tbody').append(row);
}

// json row fields: payeeName, Jobs, Avg/job, Total
function AppendPayeeRow(json, odd) {
    var row;
    row = '<tr class="row-worklist-payee-live ';
    row += (odd) ? 'rowodd' : 'roweven';
    row += '">';
    row += '<td>' + json[0] +  '</td>'; // payeeName
    row += '<td>' + json[1] +  '</td>'; // Jobs
    row += '<td>$' + json[2] +  '</td>'; // Avg/job
    row += '<td>$' + json[3] +  '</td>'; // Total Fee
    row += '</tr>';
    $('.table-worklist-payee tbody').append(row);
}

/**
 *Formats the given value for display. For now null values are shown as --
 *
*/
function formatValueForDisplay(valueToFormat) {
    var formattedValue = '--';
    if (valueToFormat != null) {
        formattedValue = valueToFormat;
    }
    return formattedValue;
}

/**
 * Appends the Page , Grand totals to the bottom of table
 *
*/
function AppendTotals(pageTotal, grandTotal) {
    if (is_payer) {
        cspan = '7'
    } else {
        cspan = '5'
    }
    row =  '<tr class="row-worklist-live rowodd">'+
            '   <td colspan="'+cspan+'" align="right">Page Total </td>' +
            '   <td align="center">'+ '$' + pageTotal +'</td>' +
            '</tr>';
    $('.table-worklist tbody').append(row);
    row =  '<tr class="row-worklist-live rowodd">'+
            '   <td colspan="'+cspan+'" align="right">Grand Total </td>' +
            '   <td align="center">'+ '$' + grandTotal +'</td>' +
            '</tr>';

    $('.table-worklist tbody').append(row);
}

function GetReport(npage, reload, sort) {
    from = $("#start-date").val().split("/");
    to = $("#end-date").val().split("/");
    _fromDate = new Date(from[2], from[0] - 1, from[1]);
    _toDate = new Date(to[2], to[0] - 1, to[1]);
    if (_fromDate != null) {
        fromDate = fmtDate(_fromDate);
    }
    if(_toDate != null) {
    toDate = fmtDate(_toDate);
    }
    var order = '';
    sort_key= current_sortkey;
    var order = current_order ? 'ASC' : 'DESC';
    var paidStatus = $('#paid-status').val();

    $.ajax({
        type: "POST",
        url: 'api.php',
        data: {
            action: "getReport",
            page: npage,
            status: $('select[name=status]').val(),
            user: $('select[name=user]').val(),
            runner: $('select[name=runner]').val(),
            project_id: $('select[name=project]').val(),
            fund_id: $('select[name=fund]').val(),
            w2_only: $('#w2_only').is(':checked') ? 1 : 0,
            order: sort_key,
            dir: order,
            type: $('#type-status').val(),
            start: fromDate,
            end: toDate,
            paidstatus: paidStatus,
            reload: ((reload == true) ? true : false)
        },
        dataType: 'json',
        success: function(json) {
            page = json[0][1]|0;
            var cPages = json[0][2]|0;

            $('.row-worklist-live').remove();
            workitems = json;
            if (json[0][0] == 0 ) {
                $('.table-worklist').append(
                  '<tr class="row-worklist-live rowodd">'+
                  '   <td colspan="8" align="center">Oops! We couldn\'t find any work items.</td>' +
                  '</tr>');

              return;
            }

            /* Output the worklist rows. */
            var odd = true;
            for (var i = 1; i < json.length; i++) {
                AppendRow(json[i], odd);
                odd = !odd;
            }
            AppendPagination(page, cPages, 'worklist');
            AppendTotals(json[0][3]|0.00 ,json[0][4]|0.00);
            $('.table-worklist .workitem-paid').click(function(e){
                $('#amtpaid').show();
                if ($(this).prop('checked')) {
                    ttlPaid = parseFloat(ttlPaid) + parseFloat($(this).attr('data'));
                    paid_list[$(this).val()] = 1;
                } else {
                    ttlPaid = parseFloat(ttlPaid) - parseFloat($(this).attr('data'));
                    paid_list[$(this).val()] = 0;
                }
                $('#amtpaid').text('($'+ttlPaid+' paid, ' + getPaidItems() + ' items)');
            });

            /* Reflect the paid list values as pages are reloaded. */
            $("#report-check-all").prop('checked', false);
            $('.table-worklist .workitem-paid').each(function(){
                if (paid_list[$(this).val()]) $(this).prop('checked', true);
            });
        },
        error: function(xhdr, status, err) {
            $('.row-worklist-live').remove();
            $('.table-worklist').append(
                '<tr class="row-worklist-live rowodd">'+
                '   <td colspan="8" align="center">Oops! We couldn\'t find any work items.  <a id="again" href="#">Please try again.</a></td>' +
                '</tr>');
            $('#again').click(function(e){
                if (timeoutId) clearTimeout(timeoutId);
                GetReport(page);
                e.stopPropagation();
                return false;
            });
        }
    });

    timeoutId = setTimeout("GetReport("+page+", true)", ajaxRefresh);
}

function GetPayeeReport(npage, reload, sort) {
    from = $("#start-date").val().split("/");
    to = $("#end-date").val().split("/");
    _fromDate = new Date(from[2], from[0] - 1, from[1]);
    _toDate = new Date(to[2], to[0] - 1, to[1]);
    var defaultSort = 'total_fees';
    if (_fromDate != null) {
        fromDate = fmtDate(_fromDate);
    }
    if(_toDate != null) {
    toDate = fmtDate(_toDate);
    }
    var order = '';
    sort_key= current_sortkey;
    order = current_order ? 'ASC' : 'DESC';
    var paidStatus = $('#paid-status').val();

    if ($('.table-worklist-payee th div').hasClass('show-arrow')) {
       defaultSort = '';
    }

    $.ajax({
        type: "POST",
        url: 'api.php',
        data: {
            action: "getReport",
            qType: 'payee',
            page: npage,
            status: $('select[name=status]').val(),
            user: $('select[name=user]').val(),
            runner: $('select[name=runner]').val(),
            project_id: $('select[name=project]').val(),
            fund_id: $('select[name=fund]').val(),
            w2_only: $('#w2_only').is(':checked') ? 1 : 0,
            order: sort_key,
            dir: order,
            type: $('#type-status').val(),
            start: fromDate,
            end: toDate,
            paidstatus: paidStatus,
            defaultSort:defaultSort,
            reload: ((reload == true) ? true : false)
        },
        dataType: 'json',
        success: function(json) {

            page = json[0][1]|0;
            var cPages = json[0][2]|0;

            $('.row-worklist-payee-live').remove();

            if (json[0][0] == 0 ) {
                $('.table-worklist-payee').append(
                  '<tr class="row-worklist-payee-live rowodd">'+
                  '   <td colspan="4" align="center">Oops! We couldn\'t find any payee details.</td>' +
                  '</tr>');

              return;
            }

            /* Output the  payee worklist rows. */
            var odd = true;
            for (var i = 1; i < json.length; i++) {
                AppendPayeeRow(json[i], odd);
                odd = !odd;
            }
            AppendPagination(page, cPages, 'worklist-payee');

        },
        error: function(xhdr, status, err) {
            $('.row-worklist-live-payee').remove();
            $('.table-worklist-payee').append(
                '<tr class="row-worklist-live-payee rowodd">'+
                '   <td colspan="4" align="center">Oops! We couldn\'t find any payee report.  <a id="again-payee" href="#">Please try again.</a></td>' +
                '</tr>');
            $('#again-payee').click(function(e){
                if (timeoutId) clearTimeout(timeoutId);
                GetPayeeReport(page);
                e.stopPropagation();
                return false;
            });
        }
    });

    timeoutId = setTimeout("GetPayeeReport("+page+", true)", ajaxRefresh);
}

function initializeTabs() {
    $('#tabs a[data-toggle]').click(function (e) {
        var tab = $(this).attr('href');
        console.log(tab);
        if(tab == '#details') {
            currentTab = 0;
            timeoutId = setTimeout("GetReport("+page+", true)", 50);
        } else if(tab == '#chart') {
            currentTab = 1;
            timeoutId = setTimeout("setupTimelineChart(false)", 50);
        }
        else if(tab == '#payee') {
            currentTab = 2;
            timeoutId = setTimeout("GetPayeeReport("+page+", true)", 50);
        }
    });
    $('#tabs a[data-toggle][href="#chart"]').click();
}

function setupTimelineChart(reload) {
    var chartPanelId = 'timeline-chart';
    $('#'+chartPanelId).empty();
    LoveChart.initialize(chartPanelId, 585, 225, 30);
    LoveChart.forceWeeklyLabels(false);
    LoveChart.fetchData = function (from, to, username, callback) {
        if (from.getTime() > to.getTime()) {
            var tmp = from;
            from = to;
            to = tmp;
        }

        var fromDate = fmtDate(from), toDate = fmtDate(to);
        var paidStatus = $('#paid-status').val();
        $.ajax({
            type: "POST",
            url: 'api.php',
            data: {
                action: "getReport",
                qType: 'chart',
                status: $('select[name=status]').val(),
                user: $('select[name=user]').val(),
                runner: $('select[name=runner]').val(),
                project_id: $('select[name=project]').val(),
                fund_id: $('select[name=fund]').val(),
                w2_only: $('#w2_only').is(':checked') ? 1 : 0,
                order: $('#sort-by').val(),
                start: fromDate,
                end: toDate,
                paidstatus: paidStatus,
                // adding type filter content to the request
                // 30-APR-2010
                type: $('#type-status').val(),
                reload: ((reload == true) ? true : false)
            },
            dataType: 'json',
            success: function(data) {
                if (data && data.fees && data.fees !== null  ) {
                    callback(data.fees, data.uniquePeople, data.feeCount, data.labels);
                }
            } ,
            error: function(xhdr, status, err) {
                 $('#again').click(function(e){
                    if (timeoutId) clearTimeout(timeoutId);
                    e.stopPropagation();
                    return false;
                });
            }
        });
    };
    loadTimelineChart();
}

function loadTimelineChart() {
    from = $("#start-date").val().split("/");
    to = $("#end-date").val().split("/");
    _fromDate = new Date(from[2], from[0] - 1, from[1]);
    _toDate = new Date(to[2], to[0] - 1, to[1]);
    if (_fromDate != null) {
        fromDate = fmtDate(_fromDate);
    }
    if (_toDate != null) {
        toDate = fmtDate(_toDate);
    }

    LoveChart.load(_fromDate, _toDate, "");
}

$(document).ready(function(){
    GetReport(page, true);
    GetPayeeReport(page, true);

    // table sorting thing
    $('.table-worklist thead tr th').hover(function(e){
        if(! $('div', this).hasClass('show-arrow')){
            if ($(this).data('direction')) {
                $('div', this).addClass('arrow-up');
            } else {
                $('div', this).addClass('arrow-down');
            }
        }
    }, function(e){
        if(!$('div', this).hasClass('show-arrow')){
            $('div', this).removeClass('arrow-up');
            $('div', this).removeClass('arrow-down');
        }
    });

    $('.table-worklist thead tr th').data('direction', false); //false == desc order
    $('.table-worklist thead tr th').click(function(e){
        $('.table-worklist thead tr th div').removeClass('show-arrow');
        $('.table-worklist thead tr th div').removeClass('arrow-up');
        $('.table-worklist thead tr th div').removeClass('arrow-down');
        $('div', this).addClass('show-arrow');
        var direction = $(this).data('direction');

        if (direction){
            $('div', this).addClass('arrow-up');
        } else {
            $('div', this).addClass('arrow-down');
        }

        var data = $(this).metadata();
        if (!data.sortkey) {
            alert("no sortkey");
            return false;
        }

        reload = false;
        current_sortkey = data.sortkey;
        current_order = $(this).data('direction');
        $('#sort-by').val(current_sortkey);
        GetReport(page, false, current_sortkey);
        $('.table-worklist thead tr th').data('direction', false); //reseting to default other rows
        $(this).data('direction',!direction); //switching on current
    }); //end of table sorting

    // Payee tab table sorting handling
    $('.table-worklist-payee thead tr th').hover(function(e){

        if(! $('div', this).hasClass('show-arrow')){
            if ($(this).data('direction')) {
                $('div', this).addClass('arrow-up');
            } else {
                $('div', this).addClass('arrow-down');
            }
        }
    }, function(e){
        if(!$('div', this).hasClass('show-arrow')){
            $('div', this).removeClass('arrow-up');
            $('div', this).removeClass('arrow-down');
        }
    });

    $('.table-worklist-payee thead tr th').data('direction', false); //false == desc order
    $('.table-worklist-payee thead tr th').click(function(e){
        $('.table-worklist-payee thead tr th div').removeClass('show-arrow');
        $('.table-worklist-payee thead tr th div').removeClass('arrow-up');
        $('.table-worklist-payee thead tr th div').removeClass('arrow-down');
        $('div', this).addClass('show-arrow');
        var direction = $(this).data('direction');

        if (direction){
            $('div', this).addClass('arrow-up');
        } else {
            $('div', this).addClass('arrow-down');
        }

        var data = $(this).metadata();
        if (!data.sortkey) {
            alert("no sortkey");
            return false;
        }

        reload = false;
        current_sortkey = data.sortkey;
        current_order = $(this).data('direction');
        $('#sort-by').val(current_sortkey);
        GetPayeeReport(page, false, current_sortkey);
        $('.table-worklist-payee thead tr th').data('direction', false); //reseting to default other rows
        $(this).data('direction',!direction); //switching on current
    }); //end of payee table sorting

    initializeTabs();
    $("#report-check-all").live('change', function(){
        var isChecked = $("#report-check-all").prop('checked');

        $('.table-worklist .workitem-paid').each(function(){
            if (isChecked && !$(this).prop('checked')) {
                $(this).prop('checked', true);
                ttlPaid = parseFloat(ttlPaid) + parseFloat($(this).attr('data'));
                paid_list[$(this).val()] = 1;
            } else if (isChecked == '' && $(this).prop('checked')) {
                $(this).prop('checked', false);
                ttlPaid = parseFloat(ttlPaid) - parseFloat($(this).attr('data'));
                paid_list[$(this).val()] = 0;
            }
            $('#amtpaid').text('($'+ttlPaid+' paid, ' + getPaidItems() + ' items)');
        });

        $('#amtpaid').show();
    });
    $('.worklist-pagination-row a').live('click', function(e){
        page = $(this).attr('href').match(/page=\d+/)[0].substr(5);
        if (timeoutId) clearTimeout(timeoutId);
        GetReport(page);
        e.stopPropagation();
        return false;

    });
    $('.worklist-payee-pagination-row a').live('click', function(e){
        page = $(this).attr('href').match(/page=\d+/)[0].substr(5);
        if (timeoutId) clearTimeout(timeoutId);
        GetPayeeReport(page);
        e.stopPropagation();
        return false;
    });
    $('#pay').click(function(){
        var paidLst = '';
        for (var i in paid_list) {
            if (paid_list[i]) paidLst += i + ',';
        }
        $('#paid-list').val(paidLst);
        return true;
    });

    // Show PayPal Payment Run page
    $('#pp-masspay-button').click(function() {
        var new_window = window.open('./payments', '_blank');
        new_window.focus();
        return false;
    });

    $('#start-date, #end-date').datepicker({format: 'mm/dd/yyyy'});

    $('#refreshReport').click(function() {
        paid_list = [];
        if (timeoutId) clearTimeout(timeoutId);
        from = $("#start-date").val().split("/");
        to = $("#end-date").val().split("/");
        _fromDate = new Date(from[2], from[0] - 1, from[1]);
        _toDate = new Date(to[2], to[0] - 1, to[1]);
        if(_fromDate != null) {
            fromDate = fmtDate(_fromDate);
        }
        if(_toDate != null) {
            toDate = fmtDate(_toDate);
        }
        if(currentTab == 0) {
            location.href =
                './reports?reload=false&view=details&user=' + $('select[name=user]').val()
              + '&status=' + $('select[name=status]').val()
              + '&project_id=' + $('select[name=project]').val()
              + '&runner=' + $('select[name=runner]').val()
              + '&fund_id=' + $('select[name=fund]').val()
              + '&type=' + $('#type-status').val()
              + '&order=' + $('#sort-by').val()
              + '&start=' + fromDate
              + '&end=' + toDate
              + '&paidstatus=' + $('#paid-status').val()
              + '&w2_only=' + ($('#w2_only').is(':checked') ? 1 : 0)
              + '&activeProjects=0'
              + '&activeRunners=0'
              + '&activeUsers=0';
        } else if(currentTab == 1) {
            location.href =
                './reports?reload=false&view=chart&user=' + $('select[name=user]').val()
              + '&status=' + $('select[name=status]').val()
              + '&project_id=' + $('select[name=project]').val()
              + '&runner=' + $('select[name=runner]').val()
              + '&fund_id=' + $('select[name=fund]').val()
              + '&type=' + $('#type-status').val()
              + '&order=' + $('#sort-by').val()
              + '&start=' + fromDate
              + '&end=' + toDate
              + '&paidstatus=' + $('#paid-status').val()
              + '&w2_only=' + ($('#w2_only').is(':checked') ? 1 : 0)
              + '&activeProjects=0'
              + '&activeRunners=0'
              + '&activeUsers=0';
        } else if(currentTab == 2) {
            location.href =
                './reports?reload=false&view=payee&user=' + $('select[name=user]').val()
              + '&status=' + $('select[name=status]').val()
              + '&project_id=' + $('select[name=project]').val()
              + '&runner=' + $('select[name=runner]').val()
              + '&fund_id=' + $('select[name=fund]').val()
              + '&type=' + $('#type-status').val()
              + '&order=' + $('#sort-by').val()
              + '&start=' + fromDate
              + '&end=' + toDate
              + '&paidstatus=' + $('#paid-status').val()
              + '&w2_only=' + ($('#w2_only').is(':checked') ? 1 : 0)
              + '&activeProjects=0'
              + '&activeRunners=0'
              + '&activeUsers=0';
        }
    });

    $('#tabs').tabs('select', currentTab);

    $( '#userCombo, #type-status, #paid-status, #sort-by, select[name=status], ' +
       'select[name=fund], #mechanic_id, #runnerCombo, #projectCombo'
    ).chosen({width: '60%'});
    $('#sort-by').chosen();
});

