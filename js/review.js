/**
 * Worklist
 * Copyright (c) 2011 LoveMachine, LLc.
 * All rights reserved.
 */

var WReview = {
    notify_now: 1,
    initList : function() {
        $('.reviewAddLink,.reviewEditLink').click(function(){
            WReview.displayInPopup({
                user_id: userInfo.user_id,
                nickname: userInfo.nickName,
                withTrust: false,
                notify_now: 1
            });
        });
        if ($(".myReview").length > 0) {
            $(".reviewAddLink").hide();
            $(".reviewEditLink").show();
            $(".reviewsList").prepend($(".myReview"));
            //$(".myReview").get(1).remove();
        }
    },
    init : function(opt){
        if (opt.fAfter) opt.fAfter();
    },
    block : function(bBlock,sMess,oElement){
        if (!oElement) {
            oElement = $("#settingsDialog").parent();
        }
        if (bBlock) {
            oElement.block({
                message: sMess,
                css: {
                    width:'50%'
                }
            });
            setTimeout(function(){oElement.unblock();}, 30000);
        } else {
             oElement.unblock();
        }
    },

    saveReview: function(fAfter) {
        WReview.block(true,"Saving ...",$("#reviewDialog").parent());
        $.ajax({
            type: 'POST',
            url: 'api.php',
            data: {
                action: 'userReview',
                userReview: $("textarea.userReview").val(),
                reviewee_id: $("#reviewDialog").data("reviewee_id"),
                method: 'saveReview',
                notify_now: WReview.notify_now
            },
            dataType: 'json',
            success: function(json) {
                WReview.block(false,"",$("#reviewDialog").parent());
                if ((json === null) || (json.succeeded !== true)  ) {
                    var message="Error returned. ";
                    if (json !== null) {
                        message = message + json.message;
                    }
                    alert(message);
                    return;
                }
                if (json.params && json.params.myReview) {
                    var review = json.params.myReview[0],
                        sReview;
                    if (review) {
                        sReview = '<div class="listReviewElement myReview" title="My review">' +
                            "<div class='feeRange' title='Number of jobs the reviewer has worked on'>" +
                                review['feeRange'] + "</div><div class='reviewText'>" + review['review']+ "</div>" +
                            '</div>' ;
                        $(".reviewsList .noReview").remove();
                        $(".reviewsList").prepend(sReview);
                        $(".reviewAddLink").hide();
                        $(".reviewEditLink").show();
                    }
                } else {
                    if ($("textarea.userReview").val() != "") {
                        $(".myReview .reviewText").html($("textarea.userReview").val());
                    } else {
                        $(".myReview").remove();
                        $(".reviewAddLink").show();
                        $(".reviewEditLink").hide();
                        if ($(".reviewsList .listReviewElement").length == 0) {
                            $(".reviewsList").html("<div class='noReview'>No Review available for this user.</div>");
                        }
                    }
                }
                $("#infoMessageReviews").append("<div class='infoMessageSaveReviews'>"+json.message+"</div>");
                $.blockUI({
                    message: $('#infoMessageReviews'),
                    fadeIn: 700,
                    fadeOut: 700,
                    timeout: 3000,
                    showOverlay: false,
                    centerY: true,
                    css: {
                        width: '350px',
                        left: '50%',
                        border: 'none',
                        padding: '5px',
                        backgroundColor: '#000',
                        '-webkit-border-radius': '10px',
                        '-moz-border-radius': '10px',
                        opacity: .6,
                        color: '#fff' ,
                        'z-index': 4000
                    } ,
                    onUnblock: function() {
                        $(".infoMessageSaveReviews").remove();
                    }
                });
                if (fAfter) {
                    fAfter(true);
                }
            }

        });
    },
    clickSaveButton: function(fAfter) {
        WReview.saveReview(function(saved){
            if (saved) {
                $("#reviewDialog").dialog("close");
                if (fAfter) {
                    fAfter();
                }
            }
        });
    },

    displayInPopup: function(options){
        var title = "Edit the review of "+ options.nickname;
        WReview.notify_now = options.notify_now;
        if (options.withTrust) {
            title = "Review "+ options.nickname;
        }
        if ($("#reviewDialog").length == 0) {
            $("<div id='reviewDialog' ><div id='infoMessageReviews'></div><div class='content'></div></div>").appendTo("body");
            $("#reviewDialog").data("reviewee_id", options.user_id);
            $("#reviewDialog").data("nickName", options.nickname);
            $("#reviewDialog").dialog({
                dialogClass: 'white-theme',
                buttons: [{
                    text: "Update Review",
                    click: function() {
                        WReview.clickSaveButton();
                        return;
                    },
                    "class": "updateReviewButton"
                },{
                    text: "No Changes",
                    click: function() {
                        $("#reviewDialog").dialog("close");
                        return;
                    }
                }],
                modal: true,
                title: title,
                autoOpen: false,
                resizable: false,
                width: 650,
                position: ['top'],
                open: function() {
                    var oThis=this;
                    $(".updateReviewButton", $("#reviewDialog").parent()).attr("disabled", "disabled").addClass('ui-state-disabled');
                    $("#reviewDialog .content").load("api.php",
                        {
                            action: "userReview",
                            method: "getView",
                            withTrust: (options.withTrust) ? 1 : 0,
                            reviewee_id: $("#reviewDialog").data("reviewee_id")
                        },
                        function(response, status, xhr){
                        if (status == "error") {
                            var msg = "Sorry but there was an error: ";
                                $("#error").html(msg + xhr.status + " " + xhr.statusText);
                        } else {
                            $(".userReview").keyup(function() {
                                $(".updateReviewButton", $("#reviewDialog").parent()).removeAttr("disabled").removeClass('ui-state-disabled')
                            });
                            if (options.withTrust) {
                                $(".userReview").height("80%");
                                WLFavorites.init( "profileInfoFavoriteInReview", options.user_id, options.nickname);
                                // setup the variables needed to call the getFavoriteText function
                                var favCount = $('.profileInfoFavorite span').attr('data-favorite_count');
                                var isMyFav = false;
                                if ($('.profileInfoFavorite .favorite_user').hasClass('myfavorite')) {
                                    isMyFav = true;
                                }

                                // set the favText with the getFavoriteText function
                                var favText = WLFavorites.getFavoriteText(favCount, isMyFav, 'trusted ');

                                $('.profileInfoFavorite span').html(favText);
                            }
                            $(".reviewee_nickname").text(options.nickname);

                            WReview.init({
                                fAfter: function() {
                                    $("#reviewSave").click(function(){
                                        WReview.clickSaveButton();
                                    });
                                }
                            });
                        }
                    });
                },
                close: function() {
                    $("#reviewDialog .content").html("");
                }
            });
            $("#reviewDialog").dialog("open");
        } else {
            $("#reviewDialog").data("nickName", options.nickname);
            $("#reviewDialog").data("reviewee_id", options.user_id);
            $("#reviewDialog .content").html("");
            $("#reviewDialog").dialog("open");
        }
    }
};

