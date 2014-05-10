<?php

class UserReview {

    public function __construct() {
    }

    /**
     * Get the review
     */
    public function getView() {
        $ret = $this->validateRequest(array('reviewee_id', 'withTrust'));
        $reviewer_id = getSessionUserId();
        $reqUser = new User();
        if ($reviewer_id > 0) {
            $reqUser->findUserById($reviewer_id);
        } else {
            echo "You have to be logged in to access user info!";
        }

        $reviewee_id = (int) $_REQUEST['reviewee_id'];
        $revieweeUser = new User();
        if ($reviewee_id > 0) {
            $revieweeUser->findUserById($reviewee_id);
        } else {
            echo "Invalid reviewee id!";
        }
        $withTrust = (int) $_REQUEST['withTrust'];
        $withTrustHTML = "";
        if ($withTrust == 1) {
                $users_favorite = new Users_Favorite();
                $favorite_enabled = 1;
                $favorite = $users_favorite->getMyFavoriteForUser($reviewer_id, $reviewee_id);
                if (isset($favorite['favorite'])) {
                    $favorite_enabled = $favorite['favorite'];
                }
                $favorite_count = $users_favorite->getUserFavoriteCount($reviewee_id);
                if ($reqUser->getId() != $revieweeUser->getId()) {
                    if ( !$reqUser->isRunner() && !$_SESSION["admin"] && !$reqUser->isActive() ) {
                        $favoriteClass = "favorite_curr_user";
                        $title = "You must have been paid for a job in the last 90 days to Trust a person.";
                    } else {
                        $favoriteClass = "favorite_user";
                        $favoriteClass .= ($favorite_enabled == 1) ? " myfavorite" : " notmyfavorite" ;
                        $title = ($favorite_enabled == 1) ?
                            "Remove " . ucwords($revieweeUser->getNickname()) . " as someone you trust. (don't worry it's anonymous)" :
                            "Add " . ucwords($revieweeUser->getNickname()) . " as someone you trust." ;
                    }
                } else {
                    $favoriteClass = "favorite_curr_user";
                    $title = "";
                }
            $withTrustHTML = '<div class="reviewTrustArea"><div class="profileInfoFavorite" id="profileInfoFavoriteInReview">
                <div class="' . $favoriteClass . '" title="' . $title . '">&nbsp;</div>
                <span class="profileFavoriteText" data-favorite_count="' . $favorite_count . '"></span>
            </div></div>';
            $withTrustHTML .= "Your review of <span class='reviewee_nickname'></span> (anonymous)<br/>
                ";
        }
        $review = new Review();
        if ($review->loadById($reviewer_id,$reviewee_id) ){
            echo $withTrustHTML . "<textarea class='userReview'>" . $review->review . "</textarea>";
            exit(0);
        }else{
            echo $withTrustHTML . "<textarea class='userReview'></textarea>";
            exit(0);
        }
    }

    /**
     * Save anonymous review
     */
    public function saveReview() {
        $ret = $this->validateRequest(array('userReview', 'reviewee_id', 'notify_now'));
        $notify_now = (int) $_REQUEST['notify_now'];
        $reviewer_id = getSessionUserId();
        $reqUser = new User();
        if ($reviewer_id > 0) {
            $reqUser->findUserById($reviewer_id);
        } else {
            $this->respond(false, "You have to be logged in to access user info!",'');
        }
        $userReview = $_REQUEST['userReview'];
        $reviewee_id = (int) $_REQUEST['reviewee_id'];
        if ($reviewer_id == $reviewee_id) {
            $this->respond(true, "Self review is not allowed.",'');
        }
        $review = new Review();
        if ($review->loadById($reviewer_id,$reviewee_id) ){
            if ($userReview == "") {
                $oReview = $review->getReviews($reviewee_id, $reviewer_id, ' AND r.reviewer_id=' . $reviewer_id);
                if ($review->removeRow(" reviewer_id = ".$reviewer_id . " AND reviewee_id = ".$reviewee_id)) {
                    if ($notify_now) {
                        sendReviewNotification($reviewee_id, "delete", $oReview);
                    }
                    $this->respond(true, "Review deleted.", '');
                } else {
                    $this->respond(false, "Cannot delete review! Please retry later.",'');
                }
            } else {
                if (!strcmp($review->review, $userReview)) {
                    $this->respond(true, "No changes made.",'');
                }
                $review->review = $userReview;
                $review->journal_notified = 0;
                if ($review->save('reviewer_id', 'reviewee_id')) {
                    $oReview = $review->getReviews($reviewee_id, $reviewer_id, ' AND r.reviewer_id=' . $reviewer_id);
                    $this->respond(true, "Review updated.",'');
                } else {
                    $this->respond(false, "Cannot update review! Please retry later.",'');
                }
            }
        } else {
            if ($userReview != "") {
                $values = array(
                    'reviewer_id' => $reviewer_id,
                    'reviewee_id' => $reviewee_id,
                    'review' => $userReview,
                    'journal_notified' => -1
                );

                if ($review->insertNew($values)) {
                    $myReview = $review->getReviews($reviewee_id, $reviewer_id, ' AND r.reviewer_id=' . $reviewer_id);
                    if (count($myReview) == 0) {
                        $review->removeRow(" reviewer_id = ".$reviewer_id . " AND reviewee_id = ".$reviewee_id);
                        $this->respond(true, "Review with no paid fee is not allowed.",'');
                    }
                    $this->respond(true, "Review saved.", array('myReview' => $myReview));
                } else {
                    $this->respond(false, "Cannot create new review! Please retry later.", '');
                }
            } else {
                $this->respond(true, "New empty review is not saved.", '');
            }
        }
     }


    /**
     * Check that all the @fields were sent on the request
     * returns true/false.
     *
     * @fields has to be an array of strings
     */
    public function validateRequest($fields, $return=false) {
        // If @fields ain't an array return false and exit
        if (!is_array($fields)) {
            return false;
        }

        foreach ($fields as $field) {
            if (!isset($_REQUEST[$field])) {
                // If we specified that the function must return do so
                if ($return) {
                    return false;
                } else { // If not, send the default reponse and exit
                    $this->respond(false, "Not all params supplied.");
                }
            }
        }
    }


    /**
     * Sends a json encoded response back to the caller
     * with @succeeded and @message
     */
    public function respond($succeeded, $message, $params=null) {
        $response = array('succeeded' => $succeeded,
                          'message' => $message,
                          'params' => $params);
        echo json_encode($response);
        exit(0);
    }
}

