<?php

class UserView extends View {
    public $layout = 'NewWorklist';
    public $title = "%s's profile - Worklist";

    public $stylesheets = array(
        'css/favorites.css',
        'css/userinfo.css',
        'css/user.css'
    );

    public $scripts = array(
        'js/jquery/jquery.blockUI.js',
        'js/paginator.js',
        'js/userNotes.js',
        'js/review.js',
        'js/favorites.js',
        'js/userinfo.js'
    );

    public function render() {
        $this->userId = $this->read('userId');
        $this->profileUser = $this->read('user');
        $this->reqUser = $this->read('reqUser');
        $this->title = sprintf($this->title, $this->profileUser->getNickname());
        $this->manager = $this->read('manager');
        $this->referred_by = $this->read('referred_by');
        $this->tab = $this->read('tab');
        $this->reqUserId = $this->read('reqUserId');
        $this->userStats = $this->read('userStats');
        $this->favorite_count = $this->read('favorite_count');
        $this->Annual_Salary = $this->read('Annual_Salary');
        $this->filter = $this->read('filter');
        $this->has_sandbox = $this->read('has_sandbox');
        $this->projects = $this->read('projects');

        // Google Maps search Base URL
        $this->gMapSearch = "http://maps.google.com/maps?q=";

        // CIA.gov Base URL
        $this->ciaGovBase = "https://www.cia.gov/library/publications/the-world-factbook/geos/";

        return parent::render();
    }

    public function userAvatarUrl() {
        $user = $this->read('user');
        return $user->getAvatar(80, 80);
    }

    public function favoriteClass() {
        $user = $this->read('user');
        $reqUser = $this->read('reqUser');
        $favorite_enabled = $this->read('favorite_enabled');

        $ret = '';
        if ($reqUser->getId() != $user->getId()) {
            if ( !$reqUser->isRunner() && !array_key_exists('admin',$_SESSION) && !$reqUser->isActive() ) {
                $ret = "favorite_curr_user wl-icon-star";
            } else {
                $ret = "favorite_user";
                $ret .= ($favorite_enabled == 1) ? " myfavorite" : " notmyfavorite" ;
            }
        } else {
            $ret = "favorite_curr_user wl-icon-star";
        }
        return $ret;
    }

    public function favoriteTitle() {
        $user = $this->read('user');
        $reqUser = $this->read('reqUser');
        $favorite_enabled = $this->read('favorite_enabled');

        $ret = '';
        if ($reqUser->getId() != $user->getId()) {
            if ( !$reqUser->isRunner() && !array_key_exists('admin',$_SESSION) && !$reqUser->isActive() ) {
                $ret = "You must have been paid for a job in the last 90 days to Trust a person.";
            } else {
                $ret = ($favorite_enabled == 1) ?
                    "Remove " . ucwords($user->getNickname()) . " as someone you trust. (don't worry it's anonymous)" :
                    "Add " . ucwords($user->getNickname()) . " as someone you trust." ;
            }
        }
        return $ret;
    }

    public function countryCodeUrl() {
        global $countrylist, $countryurllist;
        // CCF = Country Code Fetched from user
        $CCF = $this->profileUser->getCountry();
        $ret = '';
        if ($CCF != "") {
            if (array_key_exists($CCF, $countrylist)) {
                $countryName = $countrylist[$CCF];
                if (array_key_exists($countryName, $countryurllist)) {
                    $ret = strtolower($countryurllist[$countryName]); // To prevent URL from not working, case sensitiv.
                }
            }
        }
        return $ret;
    }

    public function countryName() {
        global $countrylist;
        // CCF = Country Code Fetched from user
        $CCF = $this->profileUser->getCountry();
        $ret = '';
        if ($CCF != "") {
            if (array_key_exists($CCF, $countrylist)) {
                $ret = $countrylist[$CCF];
            }
        }
        return $ret;
    }

    public function joined() {
        return formatableRelativeTime(strtotime($this->profileUser->getAdded(), 2));
    }

    public function localTime() {
        return convertTimeZoneToLocalTime($this->profileUser->getTimezone(), 1);
    }

    public function timezone() {
        global $timezoneTable;
        return $timezoneTable[$this->profileUser->getTimezone()];
    }

    public function totalEarnings() {
        setlocale(LC_MONETARY,'en_US');
        return preg_replace('/\.[0-9]{2,}$/','',money_format('%n', $this->userStats->getTotalEarnings()));
    }

    public function latestEarnings() {
        return preg_replace('/\.[0-9]{2,}$/','',money_format('%n',$this->userStats->getLatestEarnings(30)));
    }

    public function ownProfile() {
        return $this->reqUserId > 0 && $this->profileUser->getId() == $_SESSION['userid'];
    }

    public function reviewsList() {
        $reviewsList = $this->read('reviewsList');
        $res="<div class='noReview'>No Review available for this user.</div>";
        $length = count($reviewsList);
        if ($length > 0) {
            $res = "";
            for ($i = 0; $i < $length; $i++) {
                $review = $reviewsList[$i];
                if ($review['me'] == 'y') {
                    $classColor = " myReview";
                    $title = "My review";
                    $feeRange = "Me";
                    $feeRangeTitle ='My review (' . $review['feeRange'] . ')';
                    $feeText = "From <span class='feeRange' title='" . $feeRangeTitle . "'>Me</span>...";
                } else {
                    $classColor = " ";
                    $title = "";
                    $feeRange = $review['feeRange'];
                    $feeRangeTitle ='Number of jobs the reviewer has worked on';
                    $feeText = "From a user involved in <span class='feeRange' title='" . $feeRangeTitle . "'>" .$feeRange . "</span> Worklist jobs ...";
                }
                $res .=
                    '<div class="listReviewElement ' . $classColor . '" title="' . $title . '">' .
                    $feeText . "<div class='reviewText'>" . $review['review'] . "</div>" .
                    '</div>' ;
            }
        }
        return $res;
    }

    public function disableGiveBudget() {
        $reqUser = $this->read('reqUser');
        $reqUserId = $this->read('reqUserId');
        return
             (!$reqUser->isRunner() || $reqUserId == $this->profileUser->getId())
          &&  strpos(BUDGET_AUTHORIZED_USERS, "," . $reqUserId . ",") === false;
    }

    public function disablePayBonus() {
        $reqUserId = $this->read('reqUserId');
        return $reqUserId == $this->profileUser->getId() || ! $this->currentUser['is_runner'];
    }

    public function w9StatusAwaitingReceipt() {
        return $this->profileUser->getW9_status() == 'awaiting-receipt';
    }

    public function w9StatusPendingApproval() {
        return $this->profileUser->getW9_status() == 'pending-approval';
    }

    public function w9StatusApproved() {
        return $this->profileUser->getW9_status() == 'approved';
    }

    public function w9StatusRejected() {
        return $this->profileUser->getW9_status() == 'rejected';
    }

    public function w9StatusNotApplicable() {
        return $this->profileUser->getW9_status() == 'not-applicable';
    }

    public function managerUserSelectbox () {
        return $this->filter->getManagerUserSelectboxS("width:180px;");
    }

    public function referrerUserSelectbox () {
        return $this->filter->getReferrerUserSelectboxS("width:180px;");
    }

    public function userIsInactive() {
        return $this->profileUser->getIs_active() == 0;
    }

    public function userIsActive() {
        return $this->profileUser->getIs_active() == 1;
    }

    public function userIsSecured() {
        return $this->profileUser->getIs_active() == 2;
    }

    public function lastSeend() {
        return relativeTime($this->profileUser->getTimeLastSeen(), false);
    }

    public function projectsList() {
        $ret = '';
        foreach ($this->projects as $project) {
            $ret .=
                '<div class="quarter-column">' .
                  '<input ';
            if (($this->has_sandbox) && $this->profileUser->isProjectCheckedOut($project['id'])) {
                $ret .= 'checked="checked"  disabled="disabled" ';
            }
            $ret .= 'type="checkbox" id="' . $project['id'] . '" />';
            $ret .= '<input type="hidden" class="repo" value="' . $project['repo'] . '" />' . $project['name'] . '</div>';
        }
        return $ret;
    }

    public function runnerWorkers() {
        $ret = '';
        if ($runnerWorkers = $this->userStats->getDevelopersForRunner()) {
            foreach($runnerWorkers as $runnerWorker) {
                $ret .=
                    '<tr class="row-runner-developer-list-live">' .
                        '<td class="runnerWorker">' . $runnerWorker['nickname'] . '</td>' .
                        '<td class="workerJobCount">' . $runnerWorker['totalJobCount'] . '</td>' .
                        '<td>' . (($runnerWorker['totalEarnings'] > 0) ? "$" . $runnerWorker['totalEarnings'] : "")  . '</td>' .
                    '</tr>';
            }
        }
        return $ret;
    }

    public function runnerProjects() {
        $ret = '';
        if ($runnerProjects = $this->userStats->getProjectsForRunner()) {
            foreach($runnerProjects as $runnerProject) { +
                $ret .=
                    '<tr class="row-runner-project-list-live">' .
                        '<td class="runnerProject">' .$runnerProject['name'] . '</td>' .
                        '<td class="projectJobCount">' . $runnerProject['totalJobCount'] . '</td>' .
                        '<td>' . (($runnerProject['totalEarnings'] > 0) ? "$" . $runnerProject['totalEarnings'] : "") . '</td>' .
                    '</tr>';
            }
        }
        return $ret;
    }

    public function budgetAuthorized() {
        return (strpos(BUDGET_AUTHORIZED_USERS, "," . $this->reqUserId . ",") !== false);
    }
}