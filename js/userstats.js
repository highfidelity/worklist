/**
 * Copyright (c) 2014, High Fidelity Inc.
 * All Rights Reserved.
 *
 * http://highfidelity.io
 */

if (typeof stats == "undefined") {

$(function(){

    var dialog_options = { dialogClass: 'white-theme', autoOpen: false, width: '685px', show: 'fade', hide: 'fade'};
    $('#jobs-popup').dialog(dialog_options);
    $('#lovelist-popup').dialog(dialog_options);
    $('#latest-earnings-popup').dialog(dialog_options);

    $('#total-jobs').click(function(){
        stats.stats_page = 1;
        stats.showJobs('doneJobs');
        return false;
    });

    $('#runner-total-jobs').click(function(){
        stats.stats_page = 1;
        stats.showJobs('runnerTotalJobs');
        return false;
    });

    $('#runner-active-jobs, #quick-links-working').click(function(){
        stats.stats_page = 1;
        $('#jobs-popup').dialog('option', 'title', 'Active jobs');
        stats.showJobs('runnerActiveJobs');
        return false;
    });

    $('#active-jobs, #quick-links-working').click(function(){
        stats.stats_page = 1;
        $('#jobs-popup').dialog('option', 'title', 'Active jobs');
        stats.showJobs('activeJobs');
        return false;
    });

    $('#quick-links-review').click(function(){
        stats.stats_page = 1;
        $('#jobs-popup').dialog('option', 'title', 'Jobs in review');
        stats.showJobs('reviewJobs');
        return false;
    });

    $('#quick-links-completed').click(function(){
        stats.stats_page = 1;
        $('#jobs-popup').dialog('option', 'title', 'Completed jobs');
        stats.showJobs('completedJobs');
        return false;
    });

    $("nav .following").click(function(){
        stats.stats_page = 1;
        $('#jobs-popup').dialog({
            title: "Jobs I am Following",
            dialogClass: 'white-theme',
            modal: true
        });
        stats.showJobs('following');
        return false;
    });

    $('#latest-earnings').click(function(){
        stats.stats_page = 1;
        stats.showLatestEarnings();
        return false;
    });

    $('#love').click(function(){
        stats.stats_page = 1;
        stats.showLove();
        return false;
    });

});




var stats = {

    stats_page: 1,
    user_id: 0,

    setUserId: function(id){
        stats.user_id = id;
    },

    showJobs: function(job_type, popup, container){
        var containerDiv = '';
        var user_id = (stats.user_id == 0) ? window.user_id : stats.user_id;
        if (popup != 0) {
            containerDiv = '#jobs-popup';
        } else {
            if (typeof(container) !== 'undefined') {
                containerDiv = '#' + container;
            } else {
                containerDiv = '#jobs-table';
            }
        }

        $.getJSON('api.php?action=getUserStats',
                    {id: user_id, statstype: job_type, page: stats.stats_page},
                    function(json) {
                        if (job_type == 'activeJobs' || job_type == 'runnerActiveJobs' || job_type == 'following') {
                            $(containerDiv + ' th.status').show();
                        } else {
                            $(containerDiv + ' th.status').hide();
                        }
                        if (job_type == 'following') {
                            $(containerDiv + ' th.unfollow').show();
                        } else {
                            $(containerDiv + ' th.unfollow').hide();
                        }

                        stats.fillJobs(json, partial(stats.showJobs, job_type), job_type, popup, container);

                        if (popup != 0) {
                            $('#jobs-popup').dialog('open');
                        }

                        if (job_type == 'following') {
                            $('a[id^=unfollow-]').click(function() {
                                var workitem_id = $(this).attr('id').split('-')[1];
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
                                            $('#unfollow-' + workitem_id).parents('tr').remove();
                                        }
                                    }
                                });
                            });
                            $('#jobs-popup').addClass('table-popup');
                        }
                    });
    },

    showLatestEarnings: function(){
        $.getJSON('api.php?action=getUserStats',
                    {id: stats.user_id, statstype: 'latest_earnings', page: stats.stats_page},
                    function(json){
                        stats.fillEarnings(json, stats.showLatestEarnings);
                        $('#latest-earnings-popup').dialog('open');
                    });
    },

    showLove: function(){
        $.getJSON('api.php?action=getuserstats',
                    {id: stats.user_id, statstype: 'love', page: stats.stats_page},
                    function(json){
                        stats.fillLove(json, stats.showLove);
                        $('#lovelist-popup').dialog('open');
                    });
    },

    // func is a functin to be called when clicked on pagination link
    fillJobs: function(json, func, job_type, popup, container) {
        if (popup != 0) {
             table = $('#jobs-popup table tbody');
        } else {
            if (typeof(container) !== 'undefined') {
                table = $('#' + container + ' table tbody');
            } else {
                table = $('#jobs-table table tbody');
            }
        }
        $('tr', table).remove();
        $.each(json.joblist, function(i, jsonjob) {
            var runner_nickname = jsonjob.runner_nickname != null ? jsonjob.runner_nickname : '----';
            if (popup != 0) {
                var toAppend = '<tr>'
                            + '<td class="workitem" id="workitem-' + jsonjob.id + '"><a href = "' + worklistUrl
                            + jsonjob.id
                            + '" target = "_blank">#'+ jsonjob.id + '</a></td>'
                            + '<td>' + jsonjob.summary + '</td>'
                            + '<td>' + jsonjob.creator_nickname + '</td>'
                            + '<td>' + runner_nickname + '</td>'
                            + '<td>' + jsonjob.created + '</td>';

                if (job_type == 'activeJobs' || job_type == 'runnerActiveJobs' || job_type == 'following') {
                    toAppend += '<td>' + jsonjob.status + '</td>';
                }

                if (job_type == 'following') {
                    toAppend += '<td><a href="#" id="unfollow-' + jsonjob.id + '">Un-Follow</a></td>';
                }

            } else {
                var toAppend = '<tr class="';
                if (i % 2 == 1) {
                    toAppend += 'rowodd';
                } else {
                    toAppend += 'roweven';
                }

                    toAppend += '">'
                            + '<td class="workitem" id="workitem-' + jsonjob.id + '"><a href = "' + worklistUrl
                            + jsonjob.id
                            + '" target = "_blank">#'+ jsonjob.id + ' - <span>' + jsonjob.summary + '</span></a></td>';

                if (job_type == 'activeJobs' || job_type == 'runnerActiveJobs' || job_type == 'following') {
                    toAppend += '<td>' + jsonjob.status + '</td>';
                }

                if (job_type == 'completedJobsWithStats') {
                    toAppend += '<td class="cost">$' + (jsonjob.cost ? jsonjob.cost : '0.00') + '</td>';
                    toAppend += '<td class="time">' + ( (jsonjob.days && jsonjob.days > 1) ? jsonjob.days + ' days' : '1 day') + '</td>';
                }

                if (job_type == 'following') {
                    toAppend += '<td><a href="#" id="unfollow-' + jsonjob.id + '">Un-Follow</a></td>';
                }

            }
            toAppend += '</tr>';
            table.append(toAppend);
        });
        if (popup != 0) {
            table.data('func', func);
            stats.appendStatsPagination(json.page, json.pages, table);
        }

        makeWorkitemTooltip($('.workitem'));
    },

    fillEarnings: function(json, func){
        var table = $('#latest-earnings-popup table tbody');
        $('tr', table).remove();
        $.each(json.joblist, function(i, jsonjob){
            var runner_nickname = jsonjob.runner_nickname != null ? jsonjob.runner_nickname : '----';
            var toAppend = '<tr>'
                        + '<td><a href = "' + worklistUrl
                        + jsonjob.worklist_id
                        + '" target = "_blank">#'+ jsonjob.worklist_id + '</a></td>'
                        + '<td>$' + jsonjob.amount + '</td>'
                        + '<td>' + jsonjob.summary + '</td>'
                        + '<td>' + jsonjob.creator_nickname + '</td>'
                        + '<td>' + runner_nickname + '</td>'
                        + '<td>' + jsonjob.paid_formatted + '</td>'
                        + '</tr>';

            table.append(toAppend);
        });
        table.data('func', func);
        stats.appendStatsPagination(json.page, json.pages, table);
    },

    fillLove: function(json, func){
        $('#lovelist-popup table tbody tr').remove();
        $.each(json.love, function(i, jsonlove){
            var toAppend = '<tr>'
                        + '<td>' + jsonlove.giver + '</td>'
                        + '<td>' + jsonlove.why + '</td>'
                        + '<td>' + jsonlove.at_format + '</td>'
                        + '</tr>';

            $('#lovelist-popup table tbody').append(toAppend);
        });

        var table = $('#lovelist-popup table tbody');
        table.data('func', func);
        stats.appendStatsPagination(json.page, json.pages, table);
    },

    appendStatsPagination: function(page, cPages, table){
        stats.stats_page = page;
        var paginationRow = $('<tr bgcolor="#FFFFFF">');
        paginationTD = $('<td colspan="7" style="text-align:center;">');

        if (page > 1) {

            paginationTD.append(stats.getA(page-1, 'Prev'));
            paginationTD.append('&nbsp;');
        }
        for (var i = 1; i <= cPages; i++) {
            if (i == page) {
                paginationTD.append(i + " &nbsp;");
            } else {
                paginationTD.append(stats.getA(i, i));
                paginationTD.append('&nbsp;');
            }
            if(i%20==0) {
                paginationTD.append('<br/>');
            }
        }
        if (page < cPages) {
            paginationTD.append(stats.getA(parseInt(page) + 1, 'Next'));
        }

        paginationRow.append(paginationTD);
        table.append(paginationRow);

        $('.pagination-link', table).click(function(){
            stats.stats_page = $(this).data('page');
            var func = table.data('func');
            func();
            return false;
        });
    },

    getA: function(page, txt){
        var a = $('<a href = "#">');
        a.data('page', page);
        a.addClass('pagination-link');
        a.html(txt);
        return a;
    }

}

function partial(func /*, 0..n args */) {
  var args = Array.prototype.slice.call(arguments, 1);
  return function() {
    var allArguments = args.concat(Array.prototype.slice.call(arguments));
    return func.apply(this, allArguments);
  };
}
}
