var page;
var lockGetWorklist = 0;
var status_refresh = 5 * 1000;
var statusTimeoutId = null;
var lastStatus = 0;

// This variable needs to be in sync with the PHP filter name
var filterName = '.worklist';
var affectedHeader = false;
var directions = {"ASC":"images/arrow-up.svg","DESC":"images/arrow-down.svg"};
var lastId;

var timeoutId;
var workitem = 0;
var workitems;
var dirDiv;
var dirImg;

var addFromJournal = false;
var resetOrder = false;
var skills = null;

stats.setUserId(user_id);

$(document).ready(function() {

    // Fix the layout for the User selection box
    var box_h = $('select[name=user]').height() +1;
    $('#userbox').css('margin-top', '-'+box_h+'px');

    // Validate review input
    $(':checkbox').change(function() {
        validateCodeReviews();
    });

    dirDiv = $("#direction");
    dirImg = $("#direction img");
    hdr = $(".table-worklist > thead > tr");
    if (sort != 'delta') {
        hdr.find(".clickable").each(function() {
            if ($(this).text().toLowerCase() == unescape(sort.toLowerCase())) {
                affectedHeader = $(this);
            }
        });
    }
    else {
        affectedHeader = $('#defaultsort');
    }
    hdr.find(".clickable").click(function() {
        affectedHeader = $(this);
        orderBy($(this).text().toLowerCase());
    });

    GetWorklist(page, false, true);

    reattachAutoUpdate();

    $('#query input[type="text"]').keypress(function(event) {
        if (event.keyCode == '13') {
            event.preventDefault();
            $("#search").submit();
        }
    });

    $('#query input[type="text"] + button').click(function(e){
        e.preventDefault();
        $('#query input[type="text"]').val('');
        affectedHeader = false;
        resetOrder = true;
        sort = 'null';
        dir = 'asc';
        GetWorklist(1, false);
        return false;
    });

    $("#search").submit(function(){
        GetWorklist(1, false);
        return false;
    });

    $('.filter > select').chosen();

    if(getQueryVariable('status') != null) {
        if (timeoutId) clearTimeout(timeoutId);
        GetWorklist(page, false);
    }
});

function AppendPagination(page, cPages, table)    {
    // support for moving rows between pages
    var selfLink = './?page=';
    if (table == 'worklist') {
        // preparing dialog
        $('#pages-dialog select').remove();
        var selector = $('<select>');
        for (var i = 1; i <= cPages; i++) {
            selector.append('<option value = "' + i + '">' + i + '</option>');
        }
        $('#pages-dialog').prepend(selector);
    }

    var previousLink = page > 1
            ? '<a class="previous" href="' + selfLink + (page-1) + '">Previous</a> '
            : '<span class="previous inactive">Previous</span>',
        nextLink = page < cPages
            ? '<a class="next" href="' + selfLink + (page+1) + '">Next</a> '
            : '<span class="next inactive">Next</span>';

    var pagination =
        '<tr bgcolor="#FFFFFF" class="row-' + table + '-live ' + table + '-pagination-row nodrag nodrop">' +
        '<td class="not-workitem" colspan="6" style="text-align:center;"><span>' + previousLink;

    var fromPage = 1;
    if (cPages > 10 && page > 6) {
        if (page + 4 <= cPages) {
            fromPage = page - 6;
        } else {
            fromPage = cPages - 10;
        }
    }

    for (var i = fromPage; (i <= (fromPage +10) && i <= cPages); i++) {
        if (i == page) {
            pagination += '<span class="page current">' + i + "</span> ";
        } else {
            pagination += '<a class="page" href="' + selfLink + '?page=' + i + '" >' + i + '</a> ';
        }
    }
    pagination += nextLink + '</span></td></tr>';
    $('.table-' + table).append(pagination);
}
// see getWorklist in api.php for json column mapping
function AppendRow (json, prepend, moreJson, idx) {
    var row;
    row = '<tr job="' + json[0] + '" id="workitem-' + json[0] + '" class="row-worklist-live ';

    // disable dragging for all rows except with "BIDDING" status
    if (json[2] != 'Bidding') {
        row += ' nodrag ';
    }

    // Check if the user is either creator, runner, mechanic and assigns the rowown class
    // also highlight expired and tasks bidding on
    if (user_id == 0) { // Checks if a user is logged in, as of now it
                        // would show to non logged in users, since mechanic
                        // aren't checked for session
    } else if(user_id == json[13]) {// Runner
        row += ' rowrunner';
    } else if(user_id == json[14]) {// Mechanic
        row += ' rowmechanic';
    } else if(json[15] >0) { //user bid on this task
        row += ' rowbidon';
    } else if(json[19] == 'expired') { // bid expired
        row += ' rowbidexpired';
    } else if(user_id == json[9]) { // Creator
        row += ' rowown';
    }

    row += '">';

    var project_link = worklistUrl + '' + json[17];
    row +=
        '<td class="clickable not-workitem project-col" onclick="location.href=\'' + project_link + '\'">' +
            '<a href="' + project_link + '">' + (json[17] == null ? '' : json[17]) + '</a>' +
        '</td>';
    //If job is a bug, add reference to original job
    if( json[18] > 0) {
        extraStringBug = '<p> (bug of <a href="./' + json[18] + '">#' + json[18] + '</a>) </p>';
    } else {
        extraStringBug = '';
    }

    // Displays the ID of the task in the first row
    // 26-APR-2010 <Yani>
    var workitemId = 'workitem-' + json[0];
    row +=
        '<td>' +
            '<a href="./' + json[0] + '">#' + json[0] + '</a> ' +
            '<h4>' + json[1] + '</h4>' + extraStringBug +
        '</td>';

    var bidCount = '';
    if ((json[2] == 'Bidding' || json[2] == 'SuggestedWithBid') && json[10] > 0) {
        bidCount = ' (' + json[10] + ')';
    }
    colStatus = json[2].replace(/\s/g, '');
    row += '<td class="status-col status' + colStatus + '"><i></i><span>' + json[2] + '</span>' + bidCount + '</td>';

    var who = '',
        createTagWho = function (id, nickname) {
            return '<a href="./user/' + id + '" title="' + nickname + '\'s profile">' + nickname + '</a>';
        };
    if (json[3] == json[4]) {
        // creator nickname can't be null, so not checking here
        who += createTagWho(json[9],json[3]);
    } else {
        var runnerNickname = json[4] != null ? ', ' + createTagWho(json[13], json[4]) : '';
        who += createTagWho(json[9], json[3]) + runnerNickname;
    }
    if (json[5] != null){
        who += ', ' + createTagWho(json[14], json[5]);
    }

    row += '<td class="who not-workitem who-col">' +
               who +
           '</td>';

    if (json[2] == 'Working' && json[11] != null) {
        var pastDuePre = '',
            pastDuePost = '',
            strAge = RelativeTime(json[11], true);
        if (strAge.substr(0, 1) == '-') {
            strAge = strAge.substr(1);
            strAge = "<span class='past-due'>Due: " + strAge + '</div>';
        } else {
            strAge = strAge + ' from now';
        }
        row += '<td class="age-col">' +
                 strAge
               '</td>';
    } else if (json[2] == 'Done' ) {
        if (json[6] != null){
            row += '<td class="age-col">' +
                     RelativeTime(json[6], true) +
                    '</td>';
        } else {
            row += '<td class="age-col">unknown</td>';
        }
    } else {
        row += '<td class="age-col">' +
                 RelativeTime(json[6], true) +
               '</td>';
    }

    // Comments
    comments = (json[12] == 0) ? "" : json[12];
    row += '<td class="age-col">' +
             comments +
           '</td>';

    row += '</tr>';
    $row = $(row);
    $('a', $row).on('click', function(e) {
        e.stopPropagation();
        return true;
    });
    $row.on('click', function() {
        console.log($(this).attr('job'));
        window.location.href = './' + $(this).attr('job');
    });
    if (prepend) {
        // animate in each row
        $row.hide().prependTo('.table-worklist tbody').fadeIn(300);
        $('#workitem-' + json[0]).removeAttr('style');
        setTimeout(function(){
            if (moreJson && idx-- > 1) {
                AppendRow(moreJson[idx], true, moreJson, idx);
            }
        }, 300);
    } else {
        $row.appendTo('.table-worklist tbody');
    }
}

function Change(obj, evt)    {
    if(evt.type=="focus")
        obj.style.borderColor="#6A637C";
    else if(evt.type=="blur")
       obj.style.borderColor="#d0d0d0";
}

function ClearSelection () {
    if (document.selection)
        document.selection.empty();
    else if (window.getSelection)
        window.getSelection().removeAllRanges();
}

function SetWorkItem(item) {
    var match;
    if (item.attr('id')) {
        match = item.attr('id').match(/workitem-\d+/);
    }
    if (match) {
        workitem = match[0].substr(9);
    } else {
        workitem = 0;
    }
    return workitem;
}

function orderBy(option) {
    if (option == sort) dir = ((dir == 'asc')? 'desc':'asc');
    else {
        sort = option;
        dir = 'asc';
    }
    GetWorklist(1, false);
}

function GetWorklist(npage, update, reload) {
   if (addFromJournal) {
        return true;
    }
    while(lockGetWorklist) {
        // count atoms of the Universe untill old instance finished...
    }
    lockGetWorklist = 1;
    if (timeoutId) {
        clearTimeout(timeoutId);
    }
    var search_status = '',
        search_user = '0',
        search_project = '0',
        save_filter = true,
        mobile_filter = false;

    search_project = $('select[name="project"]').val();
    search_user = $('select[name="user"]').val();

    search_status = '';
    if (search_status == 'Review' && only_needs_review_jobs) {
        reload = undefined;
        save_filter = false;
        search_status = 'Needs-Review';
    } else if ($('select[name="status"]').val()) {
        search_status = ($('select[name="status"]').val()).join("/");
    } else {
        search_status = 'ALL';
        mobile_filter = true;
    }

    $.ajax({
        type: "POST",
        url: 'api.php',
        cache: false,
        data: {
            action: 'getWorklist',
            page: npage,
            project_id: search_project,
            status: search_status,
            sort: sort,
            dir: dir,
            user: search_user,
            inComment: 0,
            query: $('#query input[type="text"]').val(),
            reload: ((reload == undefined) ? false : true),
            save: save_filter,
            mobile: mobile_filter
        },
        dataType: 'json',
        success: function(json) {
            if (json[0] == "redirect") {
                lockGetWorklist = 0;
                $('#query input[type="text"]').val('');
                window.location.href = buildHref( json[1] );
                return false;
            }

            if (affectedHeader) {
                affectedHeader.append(dirDiv);
                dirImg.attr('src',directions[dir.toUpperCase()]);
                dirDiv.css('display','block');
            } else {
                if (resetOrder) {
                    dirDiv.css('display','none');
                    resetOrder = false;
                }
            }
            affectedHeader = false;
            page = json[0][1]|0;
            var cPages = json[0][2]|0;

            $('.row-worklist-live').remove();
            workitems = json;
            if (!json[0][0]) return;

            // When updating, find the last first element
            for (var lastFirst = 1; update && lastFirst < json.length && lastId != json[lastFirst][0]; lastFirst++);

            if (update && lastId && lastFirst == json.length) {
                // lastId has disappeared from the list during an update.. avoid copious animations by reverting lastFirst
                lastFirst = 1;
            }

            // Output the worklist rows.
            for (var i = lastFirst; i < json.length; i++) {
                AppendRow(json[i]);
            }

            AppendPagination(page, cPages, 'worklist');

            if (update && lastFirst > 1) {
                // Update the view by scrolling in the new entries from the top.
                AppendRow(json[lastFirst-1], true, json, lastFirst-1);
            }
            lastId = json[1][0];

            $('.worklist-pagination-row a').click(function(e){
                page = $(this).attr('href').match(/page=\d+/)[0].substr(5);
                if (timeoutId) clearTimeout(timeoutId);
                GetWorklist(page, false);
                e.stopPropagation();
                lockGetWorklist = 0;
                return false;
            });

        },
        error: function(xhdr, status, err) {
            $('.row-worklist-live').remove();
            $('.table-worklist').append('<tr class="row-worklist-live"><td colspan="5" align="center">Oops! We couldn\'t find any work items.  <a id="again" href="#">Please try again.</a></td></tr>');
//              Ticket #11560, hide the waiting message as soon as there is an error
//              Ticket #11596, fix done with 11517
//              $('#again').click(function(){
            $('#again').click(function(e){
                if (timeoutId) clearTimeout(timeoutId);
                GetWorklist(page, false);
                e.stopPropagation();
                lockGetWorklist = 0;
                return false;
            });
        }
    });

    timeoutId = setTimeout("GetWorklist("+page+", true, true)", ajaxRefresh);
    lockGetWorklist = 0;
}

// Is this function below needed or used somewhere?
function ToolTip() {
    xOffset = 10;
    yOffset = 20;
    var el_parent, el_child;
    $(".toolparent").hover(function(e){
        if (el_child) el_child.appendTo(el_parent).hide();
        el_parent = $(this);
        el_child = el_parent.children(".tooltip")
            .appendTo("body")
            .css("top",(e.pageY - xOffset) + "px")
            .css("left",(e.pageX + yOffset) + "px")
            .fadeIn("fast");
    },
    function(){
        if (el_child) el_child.appendTo(el_parent);
        $(".tooltip").hide();
        el_child = null;
    });
    $(".toolparent").mousemove(function(e){
        if (el_child) {
            el_child
                .css("top",(e.pageY - xOffset) + "px")
                .css("left",(e.pageX + yOffset) + "px");
        }
    });
}

function buildHref(item ) {
    return worklistUrl + item;
}

function validateCodeReviews() {
    if (!$('#cr_anyone_field').is(':checked') && !$('#cr_3_favorites_field').is(':checked') && !$('#cr_project_admin_field').is(':checked') && !$('#cr_job_runner_field').is(':checked')) {
        $('#cr_anyone_field').prop('checked', true);
        $('#edit_cr_error').html("One selection must be checked");
        $('#edit_cr_error').fadeIn();
        $('#edit_cr_error').delay(2000).fadeOut();
        return false;
    };

};


function reattachAutoUpdate() {
    $("select[name=user], select[name=status], select[name=project]").change(function(){
        if ($("#search-filter").val() == 'UNPAID') {
            $(".worklist-fees").text('Unpaid');
        } else {
            $(".worklist-fees").text('Fees/Bids');
        }

        page = 1;
        if (timeoutId) clearTimeout(timeoutId);
        timeoutId = setTimeout('GetWorklist(' + page + ', false)', 50);
    });
}
