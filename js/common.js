jQuery.fn.center = function () {
    this.css("position", "absolute");
    this.css("top", (( $(window).height() - this.outerHeight() ) / 2 ) + "px");
    this.css("left", (( $(window).width() - this.outerWidth() ) / 2 ) + "px");
    return this;
}


var favoriteUsers = [];
function getFavoriteUsers()
{
    $.ajax({
        url: 'api.php',
        type: 'post',
        data: {'action': 'getFavoriteUsers'},
        dataType: 'json',
        success: function(json) {
            if (!json || json === null) {
                return;
            }
            favoriteUsers = json.favorite_users;
        },
    });
}

jQuery.fn.centerDialog = function() {
    return this.each(function() {
        var $this = $(this);
        var p = $this.parent();
        var x = (document.body.clientWidth - p.width()) / 2;
        var y = Math.max(0, ($(window).height() - p.height()) / 2) + $(window).scrollTop();
        p.animate({opacity: 0}, 0).css({left:x, top:y}).animate({opacity: 1}, 300);

    });
};

function RelativeTime(x, shortMode) {
    var plural = '';
    var mins = 60,
        hour = mins * 60;
        day = hour * 24,
        week = day * 7,
        month = week * 4,
        year = day * 365;
    var negative = (x < 0);
    if (negative) {
        x = x * -1;
    }
    if (x >= year) {
        x = (x / year) | 0;
        dformat = shortMode ? "yr" : "year";
    } else if (x >= month) {
        x = (x / month) | 0;
        dformat = shortMode ? "mnth" : "month";
    } else if (x >= day * 4) {
        x = (x / day) | 0;
        dformat = "day";
    } else if (x >= hour) {
        x = (x / hour) | 0;
        dformat = shortMode ? "hr" : "hour";
    } else if (x >= mins) {
        x = (x / mins) | 0;
        dformat = shortMode ? "min" : "minute";
    } else {
        x |= 0;
        dformat = "sec";
    }
    if (x > 1) {
        plural = 's';
    }
    if (x < 0) {
        x = 0;
    }

    return (negative ? '-' : '') + x + ' ' + dformat + plural;
}

// Code for stats
$(function() {
    if (('#stats-text').length > 0) {
        $.ajax({
            type: "POST",
            url: 'api.php',
            data: {
                action: 'getStats',
                req: 'currentlink'
            },
            dataType: 'json',
            success: function(json) {
                if (!json || json === null) return;
                $("#count_b").text(json.count_b);
                $("#count_w").text(json.count_w);
                $('#stats-text').show();
            }
        });
    }
});

/* When applied to a textfield or textarea provides default text which is displayed, and once clicked on it goes away
 Example:  $("#name").DefaultValue("Your fullname.");
*/
jQuery.fn.DefaultValue = function(text){
    return this.each(function(){
    //Make sure we're dealing with text-based form fields
    if(this.type != 'text' && this.type != 'password' && this.type != 'textarea')
      return;

    //Store field reference
    var fld_current=this;

    //Set value initially if none are specified
        if(this.value=='' || this.value == text) {
      this.value=text;
    } else {
      //Other value exists - ignore
      return;
    }

    //Remove values on focus
    $(this).focus(function() {
      if(this.value==text || this.value=='')
        this.value='';
    });

    //Place values back on blur
    $(this).blur(function() {
      if(this.value==text || this.value=='')
        this.value=text;
    });

    //Capture parent form submission
    //Remove field values that are still default
    $(this).parents("form").each(function() {
      //Bind parent form submit
      $(this).submit(function() {
        if(fld_current.value==text) {
          fld_current.value='';
        }
      });
    });
    });
};

/**
 * Use this function to create multiple html messages for
 * openNotifyOverlay function. Pass all messages in in a string array
 * Retunrs html
 */
function createMultipleNotifyHtmlMessages(arrayOfMessages) {
    var html = '';
    for(var i=0; i < arrayOfMessages.length; i++) {
        html += '<div>' + arrayOfMessages[i] + '</div>'
    }
    return html;
}

function openNotifyOverlay(html, autohide, button, displayRedBorder) {
    Utils.emptyModal({
        content: html
    });
    return;

    $('#sent-notify').html(html);
    $('#sent-notify').attr('autohide', autohide);

    /**
     *  'Got it' button is shown by default, unless autohide is true
     */
    if (typeof(button) == 'undefined' || button == null) {
        var button = true;

        if (autohide) {
            button = false;
        }
    }

    $('#sent-notify').dialog({
        dialogClass:'white-theme'
    });

    if (button) {
        $('#sent-notify').dialog('option', 'buttons', [{
            text: 'Got it',
            click: function() {
                $(this).dialog('close');
            }
        }]);
    }

    $('#sent-notify').dialog('open');

    /**
     * we need to remove the height so that the element automatically readjusts to
     * a proper height based on the mesages to display.
     */
    $('#sent-notify').css("height", "");

    var sentNotifyParent = $('#sent-notify').parent();
    $(sentNotifyParent).attr('id', ''); // We remove the id to default back to blue border
    if(displayRedBorder) {
        /**
         * We give the container an id. This is important so that
         * we can have our css to set the red border trump the white theme css
         */
        $(sentNotifyParent).attr('id', 'openOverlayContainer');
    }
}

function closeNotifyOverlay() {
    $('#sent-notify').dialog('close');
}

function makeWorkitemTooltip(className){
    $(className).tooltip({
        delay: 500,
        extraClass: "white-theme content",
        showURL: false,
        bodyHandler: function() {
            var msg = "Loading...";
            var worklist_id = $(this).attr('id').substr(9);
            $.ajax({
                type: "POST",
                async: false,
                url: 'api.php',
                data: {
                    'action': 'getWorkitem',
                    'item' : worklist_id
                },
                dataType: 'json',
                bgcolor:"#ffffff",
                success: function(json) {
                    msg = json.summary ? '<div class = "head">' + json.summary + '</div>' : '';
                    msg += json.notes ? '<div class = "tip-entry no-border">' + json.notes + '</div>' : '';
                    msg += json.project ? '<div class = "tip-entry">Project: ' + json.project + '</div>' : '';
                    msg += '<div class="tip-entry">';

                    if (json.runner) {
                        msg += '<div class = "tip-entry FL no-border">Designer: ' + json.runner + '</div>';
                    }

                    if (json.creator) {
                        msg += '<div class = "tip-entry FL no-border">Creator: ' + json.creator + '</div>';
                    }

                    if (json.mechanic) {
                        msg += '<div class="tip-entry FL no-border">Developer: ' + json.mechanic + '</div>';
                    }

                    msg += '</div>';
                    msg += '<div class="clear"></div>';

                    msg += json.job_status ? '<div class = "tip-entry">Status: ' + json.job_status + '</div>' : '';
                    if (json.comment) {
                        msg += '<div class = "tip-entry">Last Comment by <i>' + json.commentAuthor + '</i>: ' + json.comment + '</div>';
                    } else {
                        msg += '<div class = "tip-entry">No comments yet.</div>';
                    }
                    if (msg == '') {
                        msg = 'No data available';
                    }
                },
                error: function(xhdr, status, err) {
                    msg = 'Data loading error.<br />Please try again.';
                }
            });
            return $('<div>').html(msg);
        }
    });
}

function validateUploadImage(file, extension) {
    if (!(extension && /^(jpg|jpeg|gif|png)$/i.test(extension))) {
        // extension is not allowed
        openNotifyOverlay('This filetype is not allowed', false);
        // cancel upload
        return false;
    }
}

// main common.js initialization
$(function() {
    if ($('#sent-notify').length == 0) {
        $('<div>').attr({id: 'sent-notify'}).css({display: 'none'}).appendTo('body');
    }
    $('#sent-notify').dialog({
        modal: false,
        autoOpen: false,
        width: 350,
        height: 70,
        position: ['middle'],
        resizable: false,
        open: function() {
            $('#sent-notify').parent().children('.ui-dialog-titlebar').hide();
            var autoHide = $('#sent-notify').attr('autohide') == 'true' ? true : false;
            if (autoHide) {
                setTimeout(function() {
                    closeNotifyOverlay();
                }, 3000);
            }
        }
    });
});

/* We replaced the jquery.autocomplete.js because it was obsolete and unsupported.
 * Please refer to #19214 for details. Teddy 1/Apr/2013
 */
function autocompleteSplit( val ) {
    return val.split( /,\s*/ );
}

function autocompleteExtractLast( term ) {
    return autocompleteSplit( term ).pop();
}
var autocompleteUserSource = function(request, response) {
    $.getJSON( "api.php?action=getUsersList", {
        startsWith: autocompleteExtractLast( request.term ),
        getNicknameOnly: true
        }, response );
};
/**
 *
 * @param status
 * @param datasource - pass null if unused.
 * @returns object autocomplete arguments
 */
function autocompleteMultiple(status, datasource) {
    var autocompleteArguments;
    autocompleteArguments = {
        bind: function( event ) {
            if (event.keyCode === $.ui.keyCode.TAB &&
                $(this).data("ui-autocomplete") &&
                $(this).data("ui-autocomplete").menu.active
            ) {
                event.preventDefault();
            }

        },
        minLength: 0,
        focus: function(event, ui) {
            return false;
        },
        select: function( event, ui ) {
            var terms = autocompleteSplit( this.value );
            // remove the current input
            terms.pop();
            // add the selected item
            terms.push( ui.item.value );
            // add placeholder to get the comma-and-space at the end
            terms.push( "" );
            this.value = terms.join( ", " );
            return false;
        }

    };

    if (status == 'getuserslist') {
            autocompleteArguments.source = autocompleteUserSource;
            autocompleteArguments.search = function() {
                // custom minLength
                var term = autocompleteExtractLast(this.value);
                if ( term.length < 1 ) {
                  return false;
                }
            };

    } else if (status == 'getskills') {
        autocompleteArguments.source = function( request, response ) {
         // delegate back to autocomplete, but extract the last term
            response( $.ui.autocomplete.filter(
                    skillsSet, autocompleteExtractLast( request.term ) ) );
            };
    }
    return autocompleteArguments;

}

//Code for Add Project
$(document).ready(function() {
});

// tooltip plugin and dictionary
function MapToolTips() {
    return;
    var tooltipPhraseBook = {
        "menuWorklist": "Explore jobs or add new ones.",
        "menuJournal": "Chat",
        "menuLove": "Show appreciation for cool things people have done",
        "menuRewarder": "Give earned points\/money to other teammates.",
        "menuReports": "Audit all the work and money flow.",
        "jobsBidding": "See more stats on jobs and team members.",
        "hoverJobRow": "View, edit, or make a bid on this job.",
        "cR": "",
        "endCr": "",
        "cRDisallowed": "",
        "cRSetToFunctional": "Set this job to Functional then to Code Review to enable Code Review.",
        "endCrDisallowed": "You are not authorized to End Code Review on this job",
        "addFee": "",
        "addBid": "Make an offer to do this job.",
        "changeSBurl": "",
        "budgetRemaining1": "Funds still available to use towards jobs for all my budgets",
        "budgetAllocated1": "Funds linked to fees in active jobs (Working, Functional, Review, Completed) for all my budgets",
        "budgetSubmitted1": "Funds linked to fees in Done'd jobs that are not yet paid for all my budgets",
        "budgetPaid1": "Funds linked to fees that have been Paid through system for all my budgets",
        "budgetTransfered1": "Funds granted to others via giving budget for all my budgets",
        "budgetManaged1": "Total amount of budget funds granted to me since joining Worklist",
        "budgetSave": "Save changes made to title or notes",
        "budgetClose": "Reconcile &amp; close this open budget",
        "budgetRemaining2": "Funds still available to use towards jobs",
        "budgetAllocated2": "Funds linked to fees in active jobs (Working, Functional, Review, Completed)",
        "budgetSubmitted2": "Funds linked to fees in Done'd jobs that are not yet paid",
        "budgetPaid2": "Funds linked to fees that have been Paid through system",
        "budgetTransfered2": "Funds granted to others via giving budget",
        "enterAmount": "Enter the amount you want to be paid if this job is accepted and done.",
        "enterNotes": "Enter detailed code review info in Comments Section.",
        "enterCrAmount": "Recommended review fee based on project settings.",
        "addProj": "Add a new project to the Worklist."
    };
    $.each(tooltipPhraseBook, function(k,v) {
        $('.iToolTip.' + k).attr('title', v);
    });
    $('.iToolTip.hoverJobRow').each(function(a,b) {
        var jobId = $(this).attr('id');
        var jobIdNum = jobId.substring(jobId.lastIndexOf('-') + 1, jobId.length);
        var tit = tooltipPhraseBook.hoverJobRow;
        $(this).attr('title',(tit + ' #' + jobIdNum));
    });
    $('.iToolTip').tooltip({
        track: false,
        delay: 600,
        showURL: false,
        showBody: " - ",
        fade: 150,
        positionLeft: true
    });
};

$(function () {
    // @TODO: This only needs to run on certain pages, settings -- lithium
    $('#username').watermark('Email address', {useNative: false});
    $('#password').watermark('Password', {useNative: false});
    //$('#oldpassword').watermark('Current Password', {useNative: false});
    //$('#newpassword').watermark('New Password', {useNative: false});
    //$('#confirmpassword').watermark('Confirm Password', {useNative: false});
    $('#nickname').watermark('Nickname', {useNative: false});
    $('#about').watermark('Tell us about yourself', {useNative: false});
    $('#contactway').watermark('Skype, email, phone, etc.', {useNative: false});
    $('#payway').watermark('Paypal, check, etc.', {useNative: false});
    $('.skills-watermark').watermark('Your skills', {useNative: false});
    $('#findus').watermark('Google, Yahoo, others..', {useNative: false});
    $('#phoneconfirmstr').watermark('Phone confirm string', {useNative: false});
    // @TODO: This looks specific to masspay -- lithium
    $('#pp_api_username').watermark('API Username', {useNative: false});
    $('#pp_api_password').watermark('API Password', {useNative: false});
    $('#pp_api_signature').watermark('API Signature', {useNative: false});

    $('textarea.autogrow').autosize();

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
                dialogClass: 'white-theme',
                show: 'fade',
                hide: 'fade'
            });
            $('#wFees').dialog( "option", "title", t );
            $('#wFees').addClass('table-popup');
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
                dialogClass: 'white-theme',
                show: 'fade',
                hide: 'fade'
            });
            $('#wFees').dialog("option", "title", t);
            $('#wFees').addClass('table-popup');
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

    $('a.feesum').tooltip({
        delay: 300,
        showURL: false,
        fade: 150,
        bodyHandler: function() {
            return $($(this).attr("href")).html();
        },
        positionLeft: false
    });
});

var updateFeeSumsTimes = setInterval(function () {
    $.get('api.php?action=getFeeSums', function(data) {
        var sum = eval('('+data+')');
        if (typeof sum != 'object') {
            return false;
        }
        $('#fees-week').html ('$'+sum.week);
        $('#fees-month').html ('$'+sum.month);
    });
}, ajaxRefresh);

$(function() {
    if ($("#budgetPopup").length > 0) {
        $("#budgetPopup").dialog({
            title: "Earning & Budget",
            autoOpen: false,
            width: 340,
            position: ['center',60],
            modal: true
        });
        $("nav a.budget").click(function(){
            $("#budgetPopup").dialog("open");
        });
    }
});

/* get analytics info for this page */
$(function() {
    $.analytics = $('#analytics');
    if ($.analytics.length) {
        var jobid=$.analytics.attr('data');
        $.ajax({
            url: 'api.php?action=visitQuery&jobid=' + jobid,
            dataType: 'json',
            success: function(json) {
                if(parseInt(json.visits)+parseInt(json.views) == 0)
                {
                    $.analytics.hide();
                    return;
                }
                var p = $('<p>').html('Page views');
                p.append($('<span>').html(' Unique: ' + json.visits))
                p.append($('<span>').html(' Total: ' + json.views));
                $.analytics.append(p);
            }
        });
    }
});
