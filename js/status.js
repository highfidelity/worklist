Status = {
    recentWorklistEntryDate: 0,

    init: function() {
        // Collect Bidding Jobs info
        Status.getBiddingReviewDrawers();
        Status.scrollTop();
        Status.longPoll();
        Entries.formatWorklistStatus();
    },

    scrollTop: function() {
        window.scrollTo(0, 0);
    },

    getBiddingReviewDrawers: function() {
        doc = document.location.href.split("journal");
        $.ajax({
            url:'api.php',
            type: 'post',
            data: { 'action':'getSystemDrawerJobs'  },
            dataType: 'json',
            success: function(json) {
                Status.fillBiddingReviewDrawers(json);
            }
        });
    },

    fillBiddingReviewDrawers: function(json) {
        if (json == null || !json.success) {
            return;
        }
        var bidding = (json.bidding == 0 || json.bidding == null) ? 'no jobs' : (json.bidding == 1 ? '1 job' : json.bidding + ' jobs');
        var review = (json.review == 0 || json.review == null) ? 'no jobs' : (json.review == 1 ? '1 job' : json.review + ' jobs');

        $('#need-review ul li').remove();
        $('#need-review ul + a').remove();
        if (parseInt(review) > 0 && json.need_review) {
            var need_review = json.need_review;
            for (var i = 0; i < need_review.length; i++) {
                workitem = need_review[i];
                var li = $('<li>');
                $('<span>#' + workitem.id + '</span> ').appendTo(li);
                $('<a>').attr(
                    {
                        class: 'workitem-' + workitem.id,
                        href: './' + workitem.id
                    }).text(workitem.summary).appendTo(li);
                $('#need-review ul').append(li);
            }
            if (parseInt(review) > 7) {
                $('<a>').attr({href: './jobs?project=&user=0&status=needs-review'})
                    .text('View them all')
                    .insertAfter('#need-review ul');
            }
            $('#need-review ul').show();
        }  else {
            $('#need-review ul').hide();
        }
        $('#biddingJobs p > span + a').text(bidding);
        $('#biddingJobs p > span').text(parseInt(bidding) == 1 ? 'is' : 'are');
    },

    findRecentWorklistEntryDate: function() {
        var recent = 0;
        $('#entries li[type="worklist"]').each(function() {
            var currentEntryDate = $(this).attr('date');
            if (currentEntryDate > recent) {
                recent = currentEntryDate;
            }
        });
        return recent;
    },

    findRecentGithubEntryDate: function() {
        var recent = 0;
        $('#entries li[type^="github"]').each(function() {
            var currentEntryDate = $(this).attr('date');
            if (currentEntryDate > recent) {
                recent = currentEntryDate;
            }
        });
        return recent;
    },
    longPoll: function() {
        if (!Status.recentWorklistEntryDate) {
            Status.recentWorklistEntryDate = Status.findRecentWorklistEntryDate();
        }
        if (!Status.recentWorklistEntryDate) {
            Status.recentWorklistEntryDate = Status.findRecentGithubEntryDate();
        }
        $.ajax({
            url: "./status",
            dataType: "json",
            type: "post",
            complete: function() {
                // start a new longpoll after 500 ms so we give some time advantage on the success
                // event to update recent entries date and then use it on next request
                setTimeout(Status.longPoll, 500);
            },
            timeout: 30000,
            data: {
                action: 'worklist_longpoll',
                since: Status.recentWorklistEntryDate
            },
            success: function(response) {
                if (!response.success) {
                    return;
                }
                var entries = response.data;
                ret = '';
                for(var i = entries.length-1; i >= 0 ; i--) {
                    var entry = entries[i];
                    if (Status.recentWorklistEntryDate < entry.date) {
                        Status.recentWorklistEntryDate = entry.date
                    }
                    ret +=
                          '<li entryid="' + entry.id + '" date="' + entry.date + '" type="worklist">'
                        +     '<h4>' + entry.relativeDate + '</h4>'
                        +     entry.content
                        + '</li>';
                }
                $(ret).prependTo('#entries');
                Entries.formatWorklistStatus();
                if (entries.length) {
                    Status.scrollTop();
                }
            }
        });
    }
}

$(function() {
    Status.init();
});

