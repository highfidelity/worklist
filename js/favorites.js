/**
 * Copyright (c) 2014, High Fidelity Inc.
 * All Rights Reserved.
 *
 * http://highfidelity.io
 */

var WLFavorites = {
    favMissionText: null,
    init: function(containerID, favorite_user_id, fav_user_nickname) {
        $('.favorite_user, .favorite_count', $("#" + containerID)).click(function(){
            var newVal = ($(this).hasClass("myfavorite")) ? 0 : 1;
            WLFavorites.setFavorite(favorite_user_id, newVal, containerID, null, fav_user_nickname);
        });

        var favCount = $('.profileFavoriteText').attr('data-favorite-count');
        var isMyFav = false;
        if ($('.favorite_user').hasClass('myfavorite')) {
            isMyFav = true;
        }

        var favText = WLFavorites.getFavoriteText(favCount, isMyFav, 'Trusted ');

        $('.profileFavoriteText').html(favText);
    },
    setFavorite: function( favorite_user_id, newVal, containerID, fAfter, fav_user_nickname ) {
        /*
         * remove the .favorite_count
         */
        $.ajax({
            type: 'POST',
            url: 'api.php',
            data: {
                action: 'setFavorite',
                favorite_user_id: favorite_user_id,
                newVal: newVal
            },
            dataType: 'json',
            success: function(json) {
                if ((json === null) || (json.error)) {
                    var message="Error returned - f1. ";
                    if (json !== null) {
                        message = message + json.error;
                    }
                    alert(message);
                    return;
                }
                var fav = parseInt($('.profileFavoriteText').attr('data-favorite-count'));
                if (newVal == 1) {
                    $('.profileFavoriteText').attr('data-favorite-count', fav + 1);
                    var favText = WLFavorites.getFavoriteText(fav + 1, true, 'Trusted ');
                    $('.profileFavoriteText').html(favText);
                    $(".favorite_user, .favorite_count")
                        .removeClass("notmyfavorite")
                        .addClass("myfavorite")
                        .attr("title", "Remove " + fav_user_nickname + " from your trusted by. (don't worry it's anonymous)");
                } else {
                    $('.profileFavoriteText').attr('data-favorite-count', fav - 1);
                    var favText = WLFavorites.getFavoriteText(fav - 1, false, 'Trusted ');
                    $('.profileFavoriteText').html(favText);
                    $(".favorite_user, .favorite_count")
                        .removeClass("myfavorite")
                        .addClass("notmyfavorite")
                        .attr("title",  "Add " + fav_user_nickname + " as one of your trusted people.");

                }
                if (fAfter) {
                    fAfter(true);
                }
            }

        });
    },
    getFavoriteIcon: function(favoriteEnabled, objectId, type) {
        if (type == 'mission') {
            toggleClass = 'favoriteMissionIconFor' + objectId;
        } else {
            toggleClass = 'favoriteIconFor' + objectId;
        }
        var imageFavorite = '',
            imageFavoriteClass = 'favorite0',
            imageFavoritePath = 'images/white_star.png';
        if (favoriteEnabled == 1) {
            imageFavoriteClass = 'favorite1';
            if (type == 'userlist') {
                imageFavoritePath = 'images/peopleFavorite.png';
            } else {
                imageFavoritePath = 'images/yellow_star.png';
            }
        }
        imageFavorite = '<img class="' + imageFavoriteClass + ' favoriteIcon ' + toggleClass + '" src="' + imageFavoritePath + '" alt="Favorite"/>';
        return imageFavorite;
    },
    // function that returns the appropriate favorite text for users
    // and for missions. Takes the number of favorites and a boolean for
    // that a true if the user/mission is a favorite of the currently
    // logged in user.
    getFavoriteText: function(favCount, isMyFav, favText) {
        favText = typeof(favText) != 'undefined' ? favText : '';

        var pluralize = 'people';
        if (favCount == 1) {
            pluralize = 'person';
        }

        // if there are no favorites
        if (favCount == 0) {
            favText += 'by no one... for now!';
        } else if (isMyFav) {
            if (favCount == 1) {
                favText += '<span class="favBlue">by you</span>';
            } else /* if the user is not the only user to favorite */ {
                if (favCount == 2) {
                    pluralize = 'person';
                }
                favText += 'by <span class="favBlack">' + favCount
                + '</span> ' + pluralize + ' <span class="favBlue">(including you)</span>';
            }
        } else /*if the user has not favorited the mission */ {
            favText += 'by <span class="favBlack">' + favCount + '</span> ' + pluralize;
        }

        return favText;
    }
};
