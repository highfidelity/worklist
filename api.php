<?php
/**
 * Copyright (c) 2014, High Fidelity Inc.
 * All Rights Reserved.
 *
 * http://highfidelity.io
 */

require_once ("config.php");
require_once ("models/DataObject.php");
require_once ("models/Review.php");
require_once ("models/Users_Favorite.php");
require_once ("models/Budget.php");

Session::check();

if (!defined("ALL_ASSETS"))      define("ALL_ASSETS", "all_assets");

// TODO: add API keys to these function calls
// uploadProfilePicture
// getSystemDrawerJobs
// getTimezone

if(validateAction()) {
    if(!empty($_REQUEST['action'])){
        mysql_connect (DB_SERVER, DB_USER, DB_PASSWORD);
        mysql_select_db (DB_NAME);
        switch($_REQUEST['action']){
            case 'updateuser':
                validateAPIKey();
                updateuser();
                break;
            case 'pushVerifyUser':
                validateAPIKey();
                pushVerifyUser();
                break;
            case 'login':
                validateAPIKey();
                loginUserIntoSession();
                break;
            case 'getTaskPosts':
                getTaskPosts();
                break;
            case 'uploadProfilePicture':
                uploadProfilePicture();
                break;
            case 'updateProjectList':
                validateAPIKey();
                updateProjectList();
                break;
            case 'getSystemDrawerJobs':
                getSystemDrawerJobs();
                break;
            case 'bidNotification':
                validateAPIKey();
                sendBidNotification();
                break;
            case 'processW2Masspay':
                validateAPIKey();
                processW2Masspay();
                break;
            case 'doScanAssets':
                validateAPIKey();
                doScanAssets();
                break;
            case 'version':
                validateAPIKey();
                exec('svnversion > ver');
                break;
            case 'sendContactEmail':
                // @TODO: why do we require an API key for this?
                // I don't get it. The request is sent via JS, so if we included the API key it would
                // then become visible to all who want to see it, leaving the form open for abuse... - lithium
                // validateAPIKey();
                sendContactEmail();
                break;
            case 'getTimezone':
                getTimezone();
                break;
            case 'updateLastSeen':
                updateLastSeen();
                break;
            case 'sendTestNotifications':
                validateAPIKey();
                sendTestNotifications();
                break;
            case 'autoPass':
                validateAPIKey();
                autoPassSuggestedJobs();
                break;
            case 'processPendingReviewsNotifications':
                validateAPIKey();
                processPendingReviewsNotifications();
                break;
            case 'pruneJournalEntries' :
                validateAPIKey();
                pruneJournalEntries();
                break;
            case 'createRepo':
                createRepo();
                break;
            case 'createSandbox':
                createSandbox();
                break;
            case 'createDatabaseNewProject':
                createDatabaseNewProject();
                break;
            case 'sendNewProjectEmails':
                sendNewProjectEmails();
                break;
            case 'modifyConfigFile':
                modifyConfigFile();
                break;
            case 'addPostCommitHook':
                addPostCommitHook();
                break;
            case 'deployStagingSite':
                deployStagingSite();
                break;
            case 'getFavoriteUsers':
                getFavoriteUsers();
                break;
            case 'getTwilioCountries':
                getTwilioCountries();
                break;
            case 'deployErrorNotification':
                validateAPIKey();
                deployErrorNotification();
                break;
            case 'saveSoundSettings':
                saveSoundSettings();
                break;
            case 'sendNotifications':
                validateAPIKey();
                sendNotifications();
                break;
            case 'checkInactiveProjects':
                validateAPIKey();
                checkInactiveProjects();
                break;
            case 'checkRemovableProjects':
                validateAPIKey();
                checkRemovableProjects();
                break;
            case 'addProject':
                addProject();
                break;
            case 'addWorkitem':
                addWorkitem();
                break;
            case 'setFavorite':
                setFavorite();
                break;
            case 'getBidItem':
                getBidItem();
                break;
            case 'getBonusHistory':
                getBonusHistory();
                break;
            case 'getFeeItem':
                getFeeItem();
                break;
            case 'getCodeReviewStatus':
                getCodeReviewStatus();
                break;
            case 'getFeeSums':
                getFeeSums();
                break;
            case 'getJobInformation':
                getJobInformation();
                break;
            case 'getMultipleBidList':
                getMultipleBidList();
                break;
            case 'getProjects':
                $userId = isset($_SESSION['userid']) ? $_SESSION['userid'] : 0;
                $currentUser = User::find($userId);
                getProjects(!$currentUser->isInternal());
                break;
            case 'getReport':
                getReport();
                break;
            case 'getSkills':
                getSkills();
                break;
            case 'getStats':
                $req =  isset($_REQUEST['req'])? $_REQUEST['req'] : 'table';
                $interval =  isset($_REQUEST['req'])? $_REQUEST['req'] : 30;
                echo json_encode(getStats($req, $interval));
                break;
            case 'getUserItem':
                getUserItem();
                break;
            case 'getUserItems':
                getUserItems();
                break;
            case 'getUserList':
                getUserList();
                break;
            case 'getUsersList':
                getUsersList();
                break;
            case 'getWorkitem':
                getWorkitem();
                break;
            case 'payBonus':
                payBonus();
                break;
            case 'payCheck':
                payCheck();
                break;
            case 'pingTask':
                pingTask();
                break;
            case 'refreshFilter':
                refreshFilter();
                break;
            case 'userReview':
                userReview();
                break;
            case 'workitemSandbox':
                workitemSandbox();
                break;
            case 'userNotes':
                userNotes();
                break;
            case 'visitQuery':
                visitQuery();
                break;
            case 'wdFee':
                wdFee();
                break;
            case 'timeline':
                timeline();
                break;
            case 'newUserNotification':
                validateAPIKey();
                sendNewUserNotification();
                break;
            case 'sendJobReport':
                validateAPIKey();
                sendJobReport();
                break;
            default:
                die("Invalid action.");
        }
    }
}

function validateAction() {
    if (validateRequest()) {
        return true;
    } else {
        return false;
    }
}

function validateRequest() {
    if( ! isset($_SERVER['HTTPS'])) {
        error_log("Only HTTPS connection is accepted.");
        die("Only HTTPS connection is accepted.");
    } else if ( ! isset($_REQUEST['action'])) {
        error_log("API not defined");
        die("API not defined");
    } else {
        return true;
    }
}

/*
* Setting session variables for the user so he is logged in
*/
function loginUserIntoSession(){
    $db = new Database();
    $uid = (int) $_REQUEST['user_id'];
    $sid = $_REQUEST['session_id'];
    $csrf_token = md5(uniqid(rand(), TRUE));

    $sql = "SELECT * FROM ".WS_SESSIONS." WHERE session_id = '".mysql_real_escape_string($sid, $db->getLink())."'";
    $res = $db->query($sql);

    $session_data  ="running|s:4:\"true\";";
    $session_data .="userid|s:".strlen($uid).":\"".$uid."\";";
    $session_data .="username|s:".strlen($_REQUEST['username']).":\"".$_REQUEST['username']."\";";
    $session_data .="nickname|s:".strlen($_REQUEST['nickname']).":\"".$_REQUEST['nickname']."\";";
    $session_data .="admin|s:".strlen($_REQUEST['admin']).":\"".$_REQUEST['admin']."\";";
    $session_data .="csrf_token|s:".strlen($csrf_token).":\"".$csrf_token."\";";

    if(mysql_num_rows($res) > 0){
        $sql = "UPDATE ".WS_SESSIONS." SET ".
             "session_data = '".mysql_real_escape_string($session_data,$db->getLink())."' ".
             "WHERE session_id = '".mysql_real_escape_string($sid, $db->getLink())."';";
        $db->query($sql);
    } else {
        $expires = time() + SESSION_EXPIRE;
        $db->insert(WS_SESSIONS,
            array("session_id" => $sid,
                  "session_expires" => $expires,
                  "session_data" => $session_data),
            array("%s","%d","%s")
        );
    }
}

function uploadProfilePicture() {
    // check if we have a file
    if (empty($_FILES)) {
        respond(array(
            'success' => false,
            'message' => 'No file uploaded!'
        ));
    }

    if (empty($_REQUEST['userid'])) {
        respond(array(
            'success' => false,
            'message' => 'No user ID set!'
        ));
    }

    $ext = end(explode(".", $_FILES['profile']['name']));
    $tempFile = $_FILES['profile']['tmp_name'];
    $imgName = strtolower($_REQUEST['userid'] . '.' . $ext);
    $path = APP_IMAGE_PATH . $imgName;

    try {
        File::s3Upload($tempFile, $path);

        $query = "
            UPDATE `" . USERS . "`
            SET `picture` = '" . mysql_real_escape_string($imgName) . "' ,
            `s3bucket` = '" . S3_BUCKET ."'
            WHERE `id` = " . (int) $_REQUEST['userid'] . "
            LIMIT 1";

        if (! mysql_query($query)) {
            error_log("s3upload mysql: ".mysql_error());
            respond(array(
                'success' => false,
                'message' => SL_DB_FAILURE
            ));
        }

        respond(array(
            'success' => true,
            'picture' => $imgName
        ));

    } catch (Exception $e) {
        $success = false;
        $error = 'There was a problem uploading your file';
        error_log(__FILE__.": Error uploading images to S3:\n$e");

        return $this->setOutput(array(
            'success' => false,
            'message' => 'An error occured while uploading the file, please try again!'
        ));
    }


}

function updateuser(){
    $sql = "UPDATE ".USERS." ".
           "SET ";
    $id = (int)$_REQUEST["user_id"];
    foreach($_REQUEST["user_data"] as $key => $value){
        $sql .= $key." = '".mysql_real_escape_string($value)."', ";
    }
    $sql = substr($sql,0,(strlen($sql) - 1));
    $sql .= " ".
            "WHERE id = ".$id;
    mysql_query($sql);
}

function pushVerifyUser(){
    $user_id = intval($_REQUEST['id']);
    $sql = "UPDATE " . USERS . " SET `confirm` = '1', is_active = '1' WHERE `id` = $user_id";
    mysql_unbuffered_query($sql);

    respond(array('success' => false, 'message' => 'User has been confirmed!'));
}

function updateProjectList(){
    $repo = basename($_REQUEST['repo']);

    $project = new Project();
    $project->loadByRepo($repo);
    $commit_date = date('Y-m-d H:i:s');
    $project->setLastCommit($commit_date);
    $project->save();
}

function getSystemDrawerJobs(){
    $sql = " SELECT "
         . " SUM(CASE WHEN w.status = 'Bidding' THEN 1 ELSE 0 END) AS bidding, "
         . " SUM(CASE WHEN w.status = 'Review'  THEN 1 ELSE 0 END) AS review "
         . " FROM " . WORKLIST . " AS w "
         . " WHERE w.status = 'Bidding' OR (w.status = 'Review' "
         .   " AND w.code_review_completed = 0 "
         .   " AND w.code_review_started = 0);";

    $result = mysql_query($sql);
    if ($result && ($row = mysql_fetch_assoc($result))) {
        $bidding_count = $row['bidding'];
        $review_count = $row['review'];
        $need_review = array();
        if ($review_count) {
            $sql = " SELECT w.id, w.summary "
                .  " FROM " . WORKLIST . " AS w "
                .  " WHERE w.status = 'Review' "
                .    " AND w.code_review_completed = 0 "
                .    " AND w.code_review_started = 0"
                .  " LIMIT 7;";
            $result = mysql_query($sql);
            while ($row = mysql_fetch_assoc($result)) {
                $need_review[] = array(
                    'id' => $row['id'],
                    'summary' => $row['summary']
                );
            }
        }
        respond(array(
            'success' => true,
            'bidding' => $bidding_count,
            'review' => $review_count,
            'need_review' => $need_review
        ));
    } else {
        respond(array('success' => false, 'message' => "Couldn't retrieve jobs"));
    }
}

function sendBidNotification() {
    require_once('./classes/Notification.class.php');
    $notify = new Notification();
    $notify->emailExpiredBids();
}

function processW2Masspay() {
    if (!defined('COMMAND_API_KEY')
        or !array_key_exists('COMMAND_API_KEY',$_POST)
        or $_POST['COMMAND_API_KEY'] != COMMAND_API_KEY)
        { die('Action Not configured'); }

    $con = mysql_connect(DB_SERVER,DB_USER,DB_PASSWORD);
    if (!$con) {
        die('Could not connect: ' . mysql_error());
    }
    mysql_select_db(DB_NAME, $con);

    $sql = " UPDATE " . FEES . " AS f, " . WORKLIST . " AS w, " . USERS . " AS u "
         . " SET f.paid = 1, f.paid_date = NOW() "
         . " WHERE f.paid = 0 AND f.worklist_id = w.id AND w.status = 'Done' "
         . "   AND f.withdrawn = 0 "
         . "   AND f.user_id = u.id "
         . "   AND u.has_W2 = 1 "
         . "   AND w.status_changed < CAST(DATE_FORMAT(NOW(),'%Y-%m-01') as DATE) "
         . "   AND f.date <  CAST(DATE_FORMAT(NOW() ,'%Y-%m-01') as DATE); ";

    // Marks all Fees from the past month as paid (for DONEd jobs)
    if (!$result = mysql_query($sql)) { error_log("mysql error: ".mysql_error()); die("mysql_error: ".mysql_error()); }
    $total = mysql_affected_rows();

    if( $total) {
        echo "{$total} fees were processed.";
    } else {
        echo "No fees were found!";
    }

    $sql = " UPDATE " . FEES . " AS f, " . USERS . " AS u "
         . " SET f.paid = 1, f.paid_date = NOW() "
         . " WHERE f.paid = 0 "
         . "   AND f.bonus = 1 "
         . "   AND f.withdrawn = 0 "
         . "   AND f.user_id = u.id "
         . "   AND u.has_W2 = 1 "
         . "   AND f.date <  CAST(DATE_FORMAT(NOW() ,'%Y-%m-01') as DATE); ";

    // Marks all Fees from the past month as paid (for DONEd jobs)
    if (!$result = mysql_query($sql)) { error_log("mysql error: ".mysql_error()); die("mysql_error: ".mysql_error()); }
    $total = mysql_affected_rows();

    if( $total) {
        echo "{$total} bonuses were processed.";
    } else {
        echo "No bonuses were found!";
    }
    mysql_close($con);
}

function doScanAssets() {
    $scanner = new ScanAssets();
    $scanner->scanAll();
}

function respond($val){
    exit(json_encode($val));
}

function sendContactEmail(){
    $name = isset($_REQUEST['name']) ? $_REQUEST['name'] : '';
    $email = isset($_REQUEST['email']) ? $_REQUEST['email'] : '';
    $phone = isset($_REQUEST['phone']) ? $_REQUEST['phone'] : '';
    $proj_name = isset($_REQUEST['project']) ? $_REQUEST['project'] : '';
    $proj_desc = isset($_REQUEST['proj_desc']) ? $_REQUEST['proj_desc'] : '';
    $website = isset($_REQUEST['website']) ? $_REQUEST['website'] : '';
    if (empty($phone) || empty($email) || empty($phone) || empty($proj_name) || empty($proj_desc)) {
        exit(json_encode(array('error' => 'All Fields are required!')));
    }
    require_once('./classes/Notification.class.php');
    $notify = new Notification();
    if ($notify->emailContactForm($name, $email, $phone, $proj_name, $proj_desc, $website)) {
        exit(json_encode(array('success' => true)));
    } else {
        exit(json_encode(array('error' => 'There was an error sending your message, please try again later.')));
    }
}// end sendContactEmail

function autoPassSuggestedJobs() {
    $con = mysql_connect(DB_SERVER, DB_USER, DB_PASSWORD);
    if (!$con) {
        die('Could not connect: ' . mysql_error());
    }
    mysql_select_db(DB_NAME, $con);
    $sql = "SELECT id FROM `" . WORKLIST ."` WHERE  status  IN ( 'Suggestion', 'Bidding') AND DATEDIFF(now() , status_changed) > 30";

    $result = mysql_query($sql);
    $delay = 0;
    if(mysql_num_rows($result) > 1) {
        $delay = 5;
    }
    while ($row = mysql_fetch_assoc($result)) {
        $status = 'Pass';
        $workitem = new WorkItem($row['id']);
        $prev_status = $workitem->getStatus();

        // change status of the workitem to PASS.
        $workitem->setStatus($status);
        if ($workitem->save()) {

            $recipients = array('creator');
            $emails = array();
            $data = array('prev_status' => $prev_status);

            if ($prev_status == 'Bidding') {
                $recipients[] = 'usersWithBids';
                $emails = preg_split('/[\s]+/', ADMINS_EMAILS);
            }

            //notify
            Notification::workitemNotify(
                array(
                    'type' => 'auto-pass',
                    'workitem' => $workitem,
                    'recipients' => $recipients,
                    'emails' => $emails
                ),
                $data
            );

            //sendJournalnotification
            $journal_message =  "\\\\#" . $workitem->getId() . " updated by @Otto. Status set to " . $status;
            sendJournalNotification(stripslashes($journal_message));
        } else {
            error_log("Otto failed to update the status of workitem #" . $workitem->getId() . " to " . $status);
        }
        sleep($delay);
    }
    mysql_free_result($result);
    mysql_close($con);
}

function getTimezone() {
    if (isset($_REQUEST['username'])) {
        $username = $_REQUEST['username'];
    } else {
        respond(array('succeeded' => false, 'message' => 'Error: Could not determine the user'));
    }

    $user = new User();
    if ($user->findUserByUsername($username)) {
        respond(array('succeeded' => true, 'message' => $user->getTimezone()));
    } else {
        respond(array('succeeded' => false, 'message' => 'Error: Could not determine the user'));
    }
}

function updateLastSeen() {
    if (isset($_REQUEST['username'])) {
        $username = $_REQUEST['username'];
    } else {
        respond(array('succeeded' => false, 'message' => 'Error: Could not determine the user'));
    }
    $qry = "UPDATE ". USERS ." SET last_seen = NOW() WHERE username='". $username ."'";
    if ($res = mysql_query($qry)) {
        respond(array('succeeded' => true, 'message' => 'Last seen time updated!'));
    } else {
        respond(array('succeeded' => false, 'message' => mysql_error()));
    }
}

function processPendingReviewsNotifications() {
    // Check if it is time to process notifications
    if (!isset($_REQUEST['force']) && !canProcessNotifications()) {
        return;
    }

    // process pending journal notifications
    $pendingReviews = Review::getReviewsWithPendingJournalNotifications();
    if($pendingReviews !== false && count($pendingReviews) > 0) {
        echo "<br/>Processing " . count($pendingReviews) . " reviews.";
        foreach ($pendingReviews as $review) {
            $tReview = new Review();
            $tReview->loadById($review['reviewer_id'], $review['reviewee_id']);
            if ($tReview->journal_notified == 0) {
                sendReviewNotification($tReview->reviewee_id, 'update',
                    $tReview->getReviews($tReview->reviewee_id, $tReview->reviewer_id, ' AND r.reviewer_id=' . $tReview->reviewer_id));
            } else {
                sendReviewNotification($tReview->reviewee_id, 'new',
                    $tReview->getReviews($tReview->reviewee_id, $tReview->reviewer_id, ' AND r.reviewer_id=' . $tReview->reviewer_id));
            }
            $tReview->journal_notified = 1;
            $tReview->save('reviewer_id', 'reviewee_id');
            usleep(4000000);
        }
    } else {
        echo "<br />Processed. No pending Reviews.";
    }
    resetCronFile();
}

function canProcessNotifications() {
    $file = REVIEW_NOTIFICATIONS_CRON_FILE;
    // If no temp file is set (first time?) run it
    if (!file_exists($file)) {
        return true;
    } else {
        $hour = (int) file_get_contents($file);
        $serverHour = (int) date('H');
        if ($serverHour == $hour) {
            return true;
        } else {
            echo "<br/>It is not time yet.";
            echo "<br/>Next hour: " . $hour;
            echo "<br/>Current hour:" . $serverHour;
            return false;
        }
    }
}

function resetCronFile() {
    $hourLag = mt_rand(5, 12);
    $serverHour = (int) date('H');
    $newHour = $hourLag + $serverHour;
    if ($newHour > 23) {
        $newHour -= 24;
    }
    echo "<br/>Cron File Reseted.";
    echo "<br/>Next hour: " . $newHour;
    unlink(REVIEW_NOTIFICATIONS_CRON_FILE);
    file_put_contents(REVIEW_NOTIFICATIONS_CRON_FILE, $newHour);
    chmod (REVIEW_NOTIFICATIONS_CRON_FILE, 0755);
}


// Prune Journal entries by deleting all entries except the latest 100
function pruneJournalEntries() {
    $sql = " SELECT MAX(id) AS maxId FROM " . ENTRIES;
    $result = mysql_query($sql);
    if ($result) {
        $row = mysql_fetch_assoc($result);
    } else {
        die( 'Failed to get all entries');
    }
    $total = (int) $row['maxId'] - 100;

    $sql = " DELETE FROM " . ENTRIES . " WHERE id <= {$total};";
    echo $sql;
    $result = mysql_unbuffered_query($sql);
    echo "<br/> # of deleted entries: " . mysql_affected_rows();

}

function createDatabaseNewProject() {
    $sandBoxUtil = new SandBoxUtil();
    if (array_key_exists('project', $_REQUEST)) {
        try {
            if ($sandBoxUtil->createDatabaseNewProject($_REQUEST['project'], $_REQUEST['username'])) {
                echo json_encode(array('success'=>true, 'message'=>'Database created succesfully'));
            } else {
                echo json_encode(array('success'=>false, 'message'=>'Database creation failed'));
            }
        } catch (Exception $e) {
            echo json_encode(array('success'=>false, 'message'=>$e->getMessage()));
        }
    } else {
        echo json_encode(array('success'=>false, 'message'=>'Missing Parameters'));
    }
}

function createRepo() {
    $sandBoxUtil = new SandBoxUtil();
    if (array_key_exists('project', $_REQUEST)) {
        try {
            if ($sandBoxUtil->createRepo($_REQUEST['project'])) {
                echo json_encode(array('success'=>true, 'message'=>'Repository created succesfully'));
            } else {
                echo json_encode(array('success'=>false, 'message'=>'Repository not created'));
            }
        } catch (Exception $e) {
            echo json_encode(array('success'=>false, 'message'=>$e->getMessage()));
        }
    } else {
        echo json_encode(array('success'=>false, 'message'=>'Missing parameters'));
    }
}

function createSandbox() {
    $sandBoxUtil = new SandBoxUtil();
    if (array_key_exists('username', $_REQUEST) && array_key_exists('nickname', $_REQUEST)
        && array_key_exists('unixusername', $_REQUEST) && array_key_exists('projectname', $_REQUEST)) {
        try {
            if ($sandBoxUtil->createSandbox($_REQUEST['username'],
                                        $_REQUEST['nickname'],
                                        $_REQUEST['unixusername'],
                                        $_REQUEST['projectname'],
                                        null,
                                        $_REQUEST['newuser'])) {
                $user = new User();
                $user->findUserByNickname($_REQUEST['nickname']);
                $user->setHas_sandbox(1);
                $user->setUnixusername($_REQUEST['unixusername']);
                $user->setProjects_checkedout($_REQUEST['projectname']);
                $user->save();
                echo json_encode(array('success'=>true, 'message'=>'Sandbox created'));
            } else {
                echo json_encode(array('success'=>false, 'message'=>'Sandbox creation and project checkout failed'));
            }
        } catch (Exception $e) {
            echo json_encode(array('success'=>false, 'message'=>$e->getMessage()));
        }
    } else {
        echo json_encode(array('success'=>false, 'message'=>'Missing parameters'));
    }
}

function sendNewProjectEmails() {
    if (array_key_exists('username', $_REQUEST) && array_key_exists('nickname', $_REQUEST)
        && array_key_exists('unixusername', $_REQUEST) && array_key_exists('projectname', $_REQUEST)) {
        $data = array();
        $data['project_name'] = $_REQUEST['projectname'];
        $data['nickname'] = $_REQUEST['unixusername'];
        $data['database_user'] = $_REQUEST['dbuser'];
        $data['repo_type'] = $_REQUEST['repo_type'];
        $data['github_repo_url'] = $_REQUEST['github_repo_url'];
        $user = new User();
        sendTemplateEmail(SUPPORT_EMAIL, 'ops-project-created', $data);
        if (!sendTemplateEmail($_REQUEST['username'], $_REQUEST['template'], $data)) {
            echo json_encode(array('success'=>false, 'message'=>'Emails not sent'));
        } else {
            echo json_encode(array('success'=>true, 'message'=>'Emails sent out'));
        }
    } else {
        echo json_encode(array('success'=>false, 'message'=>'Missing parameters'));
    }
}

function modifyConfigFile() {
    $sandBoxUtil = new SandBoxUtil();
    if (array_key_exists('username', $_REQUEST) && array_key_exists('nickname', $_REQUEST)
        && array_key_exists('unixusername', $_REQUEST) && array_key_exists('projectname', $_REQUEST)) {
        if ($sandBoxUtil->modifyConfigFile($_REQUEST['unixusername'],
                                           $_REQUEST['projectname'],
                                           $_REQUEST['dbuser'])) {
            echo json_encode(array('success'=>true, 'message'=>'Sandbox created'));
        } else {
            echo json_encode(array('success'=>false, 'message'=>'Sandbox creation and project checkout failed'));
        }
    } else {
        echo json_encode(array('success'=>false, 'message'=>'Missing parameters'));
    }
}

function addPostCommitHook() {
    $sandBoxUtil = new SandBoxUtil();
    if (array_key_exists('repo', $_REQUEST)) {
        try {
            if ($sandBoxUtil->addPostCommitHook($_REQUEST['repo'])) {
                echo json_encode(array('success'=>true, 'message'=>'Post commit hook added'));
            } else {
                echo json_encode(array('success'=>false, 'message'=>'Failed adding post commit hook'));
            }
        } catch (Exception $e) {
            echo json_encode(array('success'=>false, 'message'=>$e->getMessage()));
        }
    } else {
        echo json_encode(array('success'=>false, 'message'=>'Missing parameters'));
    }
}

function deployStagingSite() {
    $sandBoxUtil = new SandBoxUtil();
    if (array_key_exists('repo', $_REQUEST)) {
        try {
            if ($sandBoxUtil->deployStagingSite($_REQUEST['repo'])) {
                echo json_encode(array('success'=>true, 'message'=>'Post commit hook added'));
            } else {
                echo json_encode(array('success'=>false, 'message'=>'Failed adding post commit hook'));
            }
        } catch (Exception $e) {
            echo json_encode(array('success'=>false, 'message'=>$e->getMessage()));
        }
    } else {
        echo json_encode(array('success'=>false, 'message'=>'Missing parameters'));
    }
}

function getFavoriteUsers() {
    if (!$userid = (isset($_SESSION['userid']) ? $_SESSION['userid'] : 0)) {
    echo json_encode(array('favorite_users' => array()));
    return;
    }
    $users_favorite = new Users_Favorite();
    $data = array('favorite_users' => $users_favorite->getFavoriteUsers($userid));
    echo json_encode($data);
}

/**
 * Returns a list of all the countries supported by Twilio
 */
function getTwilioCountries() {
    $sql = 'SELECT `country_code`, `country_phone_prefix` FROM `' . COUNTRIES . '` WHERE `country_twilio_enabled` = 1';

    $result = mysql_query($sql);
    if(!is_resource($result)) {
        echo json_encode(array(
            'success' => false,
            'message' => 'Could not retrieve the list of twilio supported countries'
        ));
        return;
    }

    $list = array();
    while ($row = mysql_fetch_assoc($result)) {
        $list[$row['country_code']] = $row['country_phone_prefix'];
    }

    echo json_encode(array(
        'success' => true,
        'list' => $list
    ));
    return;
}

function deployErrorNotification() {

    $work_item_id = isset($_REQUEST['workitem']) ? $_REQUEST['workitem'] : 0;
    $error_msg = isset($_REQUEST['error']) ? base64_decode($_REQUEST['error']) : '';
    $commit_rev = isset($_REQUEST['rev']) ? $_REQUEST['rev'] : '';
    require_once('classes/Notification.class.php');

    $notify = new Notification();
    $notify->deployErrorNotification($work_item_id, $error_msg, $commit_rev);
    exit(json_encode(array('success' => true)));
}

function saveSoundSettings() {
    if (!$userid = (isset($_SESSION['userid']) ? $_SESSION['userid'] : 0)) {
        echo json_encode(array('success'=>false, 'message'=>'Not logged-in user'));
        return;
    }
    try {
        $settings = 0;
        $settings_arr = preg_split('/:/', $_REQUEST['settings'], 5);

        if ((int) $settings_arr[0]) {
            $settings = $settings | JOURNAL_CHAT_SOUND;
        }
        if ((int) $settings_arr[1]) {
            $settings = $settings | JOURNAL_SYSTEM_SOUND;
        }
        if ((int) $settings_arr[2]) {
            $settings = $settings | JOURNAL_BOT_SOUND;
        }
        if ((int) $settings_arr[3]) {
            $settings = $settings | JOURNAL_PING_SOUND;
        }
        if ((int) $settings_arr[4]) {
            $settings = $settings | JOURNAL_EMERGENCY_ALERT;
        }

        $user = new User();
        $user->findUserById($userid);
        $user->setSound_settings($settings);
        $user->save();
        echo json_encode(array('success'=>true, 'message'=>'Settings saved'));
    } catch(Exception $e) {
        echo json_encode(array('success'=>false, 'message'=>'Settings saving failed'));
    }
}

function sendNotifications() {
    if (! array_key_exists('command', $_REQUEST)) {
        echo json_encode(array('success' => false, 'message' => 'Missing parameters'));
        exit;
    }
    $command = $_REQUEST['command'];
    switch ($command) {
        case 'statusNotify':
            if (! array_key_exists('workitem', $_REQUEST)) {
                echo json_encode(array('success' => false, 'message' => 'Missing parameters'));
                exit;
            }
            $workitem_id = (int) $_REQUEST['workitem'];
            $workitem = new WorkItem;
            $workitem->loadById($workitem_id);
            Notification::statusNotify($workitem);
            error_log('api.php: statusNotify completed');
            break;
    }
    echo json_encode(array('success' => true, 'message' => 'Notifications sent'));
}

function checkInactiveProjects() {
    $report_message = '';
    $db = new Database();

    $sql_inactive_projects = "
        SELECT w.project_id, p.name, p.contact_info, u.nickname, MAX(status_changed) AS last_change
        FROM " . WORKLIST . " AS w
        INNER JOIN " . PROJECTS . " AS p ON w.project_id=p.project_id
        LEFT JOIN " . USERS . " AS u ON u.id=p.owner_id
        WHERE p.active = 1 OR 1
        GROUP BY w.project_id HAVING last_change < DATE_SUB(NOW(), INTERVAL 90 DAY)
        ORDER BY p.name ASC";

    // Delete accounts which exists for at least 45 days and never have been used.
    $result = $db->query($sql_inactive_projects);

    while ($row = mysql_fetch_assoc($result)) {
        $project = new Project($row['project_id']);
        // send email
        $data = array(
            'owner' => $row['nickname'],
            'projectUrl' => Project::getProjectUrl($row['project_id']),
            'projectName' => $row['name']
        );
        if (! sendTemplateEmail($row['contact_info'], 'project-inactive', $data)) {
            $report_message .= ' <p> Ok ---';
        } else {
            $report_message .= ' <p> Fail -';
        }
        $report_message .= ' Project (' . $row['project_id'] . ')- <a href="' . Project::getProjectUrl($row['project_id']) . '">' . $row['name'] . '</a> -- Last changed status: ' .  $row['last_change'] . '</p>';
        $project->setActive(0);
        $project->save();
    }
    // Send report to ops if any project was set as inactive
    if ($report_message != '') {
        $headers['From'] = DEFAULT_SENDER;
        $subject = "Inactive Projects Report";
        $body = $report_message;
        if (!send_email(OPS_EMAIL, $subject, $body, null, $headers )) {
            error_log ('checkActiveProjects cron: Failed to send email report');
        }
    }
}

function checkRemovableProjects() {
    $report_message = '';
    $db = new Database();

    $sql_projects = "
        SELECT p.project_id, p.name, u.nickname, p.creation_date
        FROM " . PROJECTS . " AS p
        LEFT JOIN " . USERS . " AS u ON u.id=p.owner_id
        WHERE p.project_id NOT IN (SELECT DISTINCT w1.project_id
        FROM " . WORKLIST . " AS w1)
          AND p.creation_date < DATE_SUB(NOW(), INTERVAL 180 DAY)";

    $result = $db->query($sql_projects);
    while ($row = mysql_fetch_assoc($result)) {
        // send email
        $data = array(
            'owner' => $row['nickname'],
            'projectUrl' => Project::getProjectUrl($row['project_id']),
            'projectName' => $row['name'],
            'creation_date' => date('Y-m-d', strtotime($row['creation_date']))
        );
        if (sendTemplateEmail($row['contact_info'], 'project-removed', $data)) {
            $report_message .= ' <p> Ok email---';
        } else {
            $report_message .= ' <p> Failed email -';
        }
        $report_message .= ' Project (' . $row['project_id'] . ')- <a href="' . Project::getProjectUrl($row['project_id']) . '">' . $row['name'] . '</a> -- Created: ' .  $row['creation_date'] . '</p>';

    // Remove projects dependencies

        // Remove project users
        $report_message .= '<p> Users removed for project id ' . $row['project_id'] . ':</p>';
        $sql_get_project_users = "SELECT * FROM " . PROJECT_USERS . " WHERE project_id = " . $row['project_id'];
        $result_temp = $db->query($sql_get_project_users);
        while ($row_temp = mysql_fetch_assoc($result_temp)) {
            $report_message .= dump_row_values($row_temp);
        }

        $sql_remove_project_users = "DELETE FROM " . PROJECT_USERS . " WHERE project_id = " . $row['project_id'];
        $db->query($sql_remove_project_users);

        // Remove project runners
        $report_message .= '<p> Designers removed for project id ' . $row['project_id'] . ':</p>';
        $sql_get_project_runners = "SELECT * FROM " . PROJECT_RUNNERS . " WHERE project_id = " . $row['project_id'];
        $result_temp = $db->query($sql_get_project_runners);
        while ($row_temp = mysql_fetch_assoc($result_temp)) {
            $report_message .= dump_row_values($row_temp);
        }
        $sql_remove_project_runners = "DELETE FROM " . PROJECT_RUNNERS . " WHERE project_id = " . $row['project_id'];
        $db->query($sql_remove_project_runners);

        // Remove project roles
        $report_message .= '<p> Roles removed for project id ' . $row['project_id'] . ':</p>';
        $sql_get_project_roles = "SELECT * FROM " . ROLES . " WHERE project_id = " . $row['project_id'];
        $result_temp = $db->query($sql_get_project_roles);
        while ($row_temp = mysql_fetch_assoc($result_temp)) {
            $report_message .= dump_row_values($row_temp);
        }
        $sql_remove_project_roles = "DELETE FROM " . ROLES . " WHERE project_id = " . $row['project_id'];
        $db->query($sql_remove_project_roles);

        $url = TOWER_API_URL;
        $fields = array(
                'action' => 'staging_cleanup',
                'name' => $row['name']
        );

        $result = CURLHandler::Post($url, $fields);

        // Remove project
        $report_message .= '<p> Project id ' . $row['project_id'] . ' removed </p>';
        $sql_get_project = "SELECT * FROM " . PROJECTS . " WHERE project_id = " . $row['project_id'];
        $result_temp = $db->query($sql_get_project);
        while ($row_temp = mysql_fetch_assoc($result_temp)) {
            $report_message .= dump_row_values($row_temp);
        }
        $sql_remove_project = "DELETE FROM " . PROJECTS . " WHERE project_id = " . $row['project_id'];
        $db->query($sql_remove_project);
    }
    // Send report to ops if any project was set as inactive
    if ($report_message != '') {
        $headers['From'] = DEFAULT_SENDER;
        $subject = "Removed Projects Report";
        $body = $report_message;
        if (!send_email(OPS_EMAIL, $subject, $body, null, $headers )) {
            error_log ('checkActiveProjects cron: Failed to send email report');
        }
    }
}

function dump_row_values($row) {
    $dump = '<p>';
    foreach ($row as $key=> $val ) {
        $dump .= '"' . $key . '" => ' . $val . ':';
    }
    $dump .= '</p>';
    return $dump;
}

function setFavorite() {
    if ( !isset($_REQUEST['favorite_user_id']) ||
         !isset($_REQUEST['newVal']) ) {
        echo json_encode(array( 'error' => "Invalid parameters!"));
    }
    $userId = getSessionUserId();
    if ($userId > 0) {
        initUserById($userId);
        $user = new User();
        $user->findUserById( $userId );

        $favorite_user_id = (int) $_REQUEST['favorite_user_id'];
        $newVal = (int) $_REQUEST['newVal'];
        $users_favorites  = new Users_Favorite();
        $res = $users_favorites->setMyFavoriteForUser($userId, $favorite_user_id, $newVal);
        if ($res == "") {
            // send chat if user has been marked a favorite
            $favorite_user = new User();
            $favorite_user->findUserById($favorite_user_id);
            if ($newVal == 1) {

                $resetUrl = SECURE_SERVER_URL . 'user/' . $favorite_user_id ;
                $resetUrl = '<a href="' . $resetUrl . '" title="Your profile">' . $resetUrl . '</a>';
                $data = array();
                $data['link'] = $resetUrl;
                $nick = $favorite_user->getNickname();
                if (! sendTemplateEmail($favorite_user->getUsername(), 'trusted', $data)) {
                    error_log("setFavorite: send_email failed on favorite notification");
                }

                // get favourite count
                $count = $users_favorites->getUserFavoriteCount($favorite_user_id);
                if ($count > 0) {
                    if ($count == 1) {
                        $message = "**{$count}** person";
                    } else {
                        $message = "**{$count}** people";
                    }
                    $journal_message = '@' . $nick . ' is now trusted by ' . $message . '!';
                    //sending journal notification
                    sendJournalNotification(stripslashes($journal_message));
                }
            }
            echo json_encode(array( 'return' => "Trusted saved."));
        } else {
            echo json_encode(array( 'error' => $res));
        }
    } else {
        echo json_encode(array( 'error' => "You must be logged in!"));
    }
}

function getBidItem() {
    $blankbid = array(
        'id' => 0,
        'bidder_id' => 0,
        'worklist_id' => 0,
        'email' => '*name hidden*',
        'bid_amount' => '0',
        'done_in' => '',
        'notes' => '',
    );
    $blankjson = json_encode($blankbid);

    $item = isset($_REQUEST['item']) ? (int)$_REQUEST['item'] : 0;
    if ($item == 0) {
        echo $blankjson;
        return;
    }

    $userId = getSessionUserId();
    $user = new User();
    if ($userId > 0) {
        $user = $user->findUserById($userId);
    } else {
        $user->setId(0);
    }
    // Guest or hacking
    if ($user->getId() == 0) {
        echo $blankjson;
        return;
    }

    $bid = new Bid($item);

    if ($bid->id) {
        $workItem = new WorkItem();
        $workItem->conditionalLoadByBidId($item);
        // Runner, item creator, or bidder can see item.
        if ($user->isRunner() || ($user->getId() == $workItem->getCreatorId()) || ($user->getId() == $bid->bidder_id)) {
            $bid->setAnyAccepted($workItem->hasAcceptedBids());
            $row = $bid->toArray();
            $row['notes'] = html_entity_decode($row['notes'], ENT_QUOTES);
            $json = json_encode($row);
            echo $json;
        } else {
            echo $blankjson;
        }
    }
}

function getBonusHistory() {
    checkLogin();

    if (empty($_SESSION['is_runner'])) {
        die(json_encode(array()));
    }

    $limit = 7;
    $page = (int) $_REQUEST['page'];
    $rid = (int) $_REQUEST['rid'];
    $uid = (int) $_REQUEST['uid'];

    $where = 'AND `'.FEES.'`.`payer_id` = ' . $uid;

    // Add option for order results
    $orderby = "ORDER BY `".FEES."`.`date` DESC";

    $qcnt = "SELECT count(*)";
    $qsel = "SELECT DATE_FORMAT(`date`, '%m-%d-%Y') as date,
                    `amount`,
                    `nickname`,
                    `desc`";

    $qbody = " FROM `".FEES."`
               LEFT JOIN `".USERS."` ON `".USERS."`.`id` = `".FEES."`.`user_id`
               WHERE `bonus` = 1 AND `amount` != 0 $where ";

    $qorder = "$orderby LIMIT " . ($page - 1) * $limit . ",$limit";

    $rtCount = mysql_query("$qcnt $qbody");
    if ($rtCount) {
        $row = mysql_fetch_row($rtCount);
        $items = intval($row[0]);
    } else {
        $items = 0;
        die(json_encode(array()));
    }
    $cPages = ceil($items/$limit);
    $report = array(array($items, $page, $cPages));

    // Construct json for history
    $rtQuery = mysql_query("$qsel $qbody $qorder");
    for ($i = 1; $rtQuery && $row = mysql_fetch_assoc($rtQuery); $i++) {
        $report[$i] = array($row['date'],
                            $row['amount'],
                            $row['nickname'],
                            $row['desc']);
    }

    $json = json_encode($report);
    echo $json;
}

function getCodeReviewStatus() {
    $id = (int) $_REQUEST['workitemid'];
    $query = "
        SELECT id, code_reviewer_id, code_review_started, code_review_completed
        FROM " . WORKLIST . "
        WHERE id = '" . $id . "'";
    $result = mysql_query($query);
    $data = array();
    while ($result && $row=mysql_fetch_assoc($result)) {
        $data[] = $row;
    }
    echo json_encode($data);
}

function getFeeItem() {
    $item = isset($_REQUEST["item"]) ? intval($_REQUEST["item"]) : 0;
    if (empty($item))
        return;

    $query = "SELECT id, paid, notes FROM ".FEES." WHERE ".FEES.".id='{$item}'";

    $rt = mysql_query($query);
    $row = mysql_fetch_assoc($rt);

    $json = json_encode(array($row['id'], $row['paid'], $row['notes']));
    echo $json;
}

function getFeeSums() {
    $sum = Fee::getSums(isset($_GET["type"]) ? $_GET["type"] : '');
    echo json_encode($sum);
}

function getJobInformation() {
    $page=isset($_REQUEST["page"]) ? intval($_REQUEST["page"]) : 1; //Get the page number to show, set default to 1

    $workitem = new WorkItem();

    $userId = getSessionUserId();

    if( $userId > 0 )   {
        initUserById($userId);
        $user = new User();
        $user->findUserById( $userId );
    }

    if ($user->getId() > 0 ) {
        $args = array( 'itemid');
        foreach ($args as $arg) {
            if(!empty($_POST[$arg])) {
                $$arg=$_POST[$arg];
            } else {
                $$arg='';
            }
        }
        if (!empty($itemid)) {
            try {
                $workitem->loadById($itemid);
                $summary= "#". $workitem->getId()." - ". $workitem->getSummary();
            } catch(Exception $e) {
                //Item id doesnt exist
                $summary="";
            }
        } else {
            $summary='';
        }

        $returnString=$summary;

    } else {
        echo json_encode(array('error' => "Invalid parameters !"));
        return;
    }

    echo json_encode(array('returnString' => $returnString));
}

function getMultipleBidList() {
    $job_id = isset($_REQUEST['job_id']) ? (int) $_REQUEST['job_id'] : 0;
    if ($job_id == 0) {
        echo $job_id;
        return;
    }
    $workItem = new WorkItem();
    $bids = $workItem->getBids($job_id);

    $ret = array();
    foreach($bids as $bid) {
        $bid['expired'] = $bid['expires'] <= BID_EXPIRE_WARNING;
        $bid['expires_text'] = relativeTime($bid['expires'] , false, false, false, false);
        $ret[] = $bid;
    }

    echo json_encode(array('bids' => $ret));
    return;
}

function getProjects($public_only = true) {
    // Create project object
    $projectHandler = new Project();

    // page 1 is "all active projects"
    $page = isset($_REQUEST['page']) ? (int) $_REQUEST['page'] : 1;

    // for subsequent pages, which will be inactive projects, return 10 at a time
    if ($page > 1) {
        // Define values for sorting a display
        $limit = 10;
        // Get listing of all inactive projects
        $projectListing = $projectHandler->getProjects(false, array(), true,false, $public_only);

        // Create content for each page
        // Select projects that match the letter chosen and construct the array for
        // the selected page
        $pageFinish = $page * $limit;
        $pageStart = $pageFinish - ($limit - 1);

        // leaving 'letter' filter in place for the time being although the UI is not supporting it
        $letter = isset($_REQUEST["letter"]) ? trim($_REQUEST["letter"]) : "all";
        if($letter == "all") {
            $letter = ".*";
        } else if ($letter == "_") { //numbers
            $letter = "[^A-Za-z]";
        }

        // Count total number of active projects
        $activeProjectsCount = count($projectListing);

        if ($projectListing != null) {
            foreach ($projectListing as $key => $value) {
                if (preg_match("/^$letter/i", $value["name"])) {
                    $selectedProjects[] = $value;
                }
            }

            // Count number of projects to display
            $projectsToDisplay = count($selectedProjects);
            // Determine total number of pages
            $displayPages = ceil($projectsToDisplay / $limit);
            // Construct json for pagination
            // $projectsOnPage = array(array($projectsToDisplay, $page, $displayPages));
            $projectsOnPage = array();

            // Select projects for current page
            $i = $pageStart - 1;
            while ($i < $pageFinish) {
                if (isset($selectedProjects[$i])) {
                    $projectsOnPage[] = $selectedProjects[$i];
                }
                $i++;
            }
        }

    } else {
        // Get listing of active projects
        $projectsOnPage = $projectHandler->getProjects(true,array(), false,false,$public_only);
        usort($projectsOnPage, function($a, $b) {
            if ( $b["bCount"] < $a["bCount"] ) return -1;
            if ( $b["bCount"] > $a["bCount"] ) return 1;
            if ( $b["cCount"] < $a["cCount"] ) return -1;
            if ( $b["cCount"] > $a["cCount"] ) return 1;
            if ( $b["feesCount"] > $a["feesCount"] ) return -1;
            if ( $b["feesCount"] < $a["feesCount"] ) return 1;
            return 0;
        });
    }

    // Prepare data for printing in projects
    $json = json_encode($projectsOnPage);
    echo $json;
}

function getReport() {
    $limit = 30;

    $_REQUEST['name'] = '.reports';
    $filter = new Agency_Worklist_Filter($_REQUEST);
    $from_date = mysql_real_escape_string($filter->getStart());
    $to_date = mysql_real_escape_string($filter->getEnd());
    $paidStatus = $filter->getPaidstatus();
    $page = $filter->getPage();
    $w2_only = (int) $_REQUEST['w2_only'];
    $dateRangeFilter = '';

    if (isset($from_date) || isset($to_date)) {
        $mysqlFromDate = GetTimeStamp($from_date);
        $mysqlToDate = GetTimeStamp($to_date);
        $dateRangeFilter = " AND DATE(`date`) BETWEEN '".$mysqlFromDate."' AND '".$mysqlToDate."'" ;
    }

    $w2Filter = '';
    if ($w2_only) {
        $w2Filter = " AND " . USERS . ".`has_w2` = 1";
    }

    $paidStatusFilter = '';
    if (isset($paidStatus) && ($paidStatus) !="ALL") {
        $paidStatus= mysql_real_escape_string($paidStatus);
        $paidStatusFilter = " AND `".FEES."`.`paid` = ".$paidStatus."";
    }

    $sfilter = $filter->getStatus();
    $pfilter = $filter->getProjectId();
    $fundFilter = $filter->getFund_id();
    $ufilter = $filter->getUser();
    $rfilter = $filter->getRunner();
    $order = $filter->getOrder();
    $dir = $filter->getDir();
    $type = $filter->getType();

    $queryType = isset( $_REQUEST['qType'] ) ? $_REQUEST['qType'] :'detail';

    $where = '';
    if ($ufilter) {
        $where = " AND `".FEES."`.`user_id` = $ufilter ";
    }

    if ($rfilter) {
        $where = " AND `".FEES."`.`user_id` = $rfilter AND `" . WORKLIST . "`.runner_id = $rfilter ";
    }

    if ($sfilter){
        if($sfilter != 'ALL') {
            $where .= " AND `" . WORKLIST . "`.status = '$sfilter' ";
        }
    }
    if ($pfilter) {
        // ignore the fund filter?
        if($pfilter != 'ALL') {
          $where .= " AND `" . WORKLIST . "`.project_id = '$pfilter' ";
        } elseif ($fundFilter) {
            $where .= " AND `".PROJECTS."`.`fund_id` = " . $fundFilter;
        }
    } elseif (isset($fundFilter) && $fundFilter != -1) {
        if ($fundFilter == 0) {
            $where .= " AND `".PROJECTS."`.`fund_id` = " . $fundFilter . " || `".PROJECTS."`.`fund_id` IS NULL";
        } else {
            $where .= " AND `".PROJECTS."`.`fund_id` = " . $fundFilter;
        }
    }



    if ($type == 'Fee') {
        $where .= " AND `".FEES."`.expense = 0 AND `".FEES."`.rewarder = 0 AND `".FEES. "`.bonus = 0";
    } else if ($type == 'Expense') {
        $where .= " AND `".FEES."`.expense = 1 AND `".FEES."`.rewarder = 0 AND `".FEES. "`.bonus = 0";
    } else if ($type == 'Bonus') {
        $where .= " AND (rewarder = 1 OR bonus = 1)";
    } else if ($type == 'ALL') {
        $where .= " AND `".FEES."`.expense = 0 AND `".FEES."`.rewarder = 0";
    }

    // Add option for order results
    $orderby = "ORDER BY ";
    switch ($order) {
        case 'date':
            $orderby .= "`".FEES."`.`date`";
            break;

        case 'name':
        case 'payee':
            $orderby .= "`".USERS."`.`nickname`";
            break;

        case 'desc':
            $orderby .= "`".FEES."`.`desc`";
            break;

        case 'summary':
            $orderby .= "`".WORKLIST."`.`summary`";
            break;

        case 'paid_date':
            $orderby .= "`".FEES."`.`paid_date`";
            break;

        case 'id':
            $orderby .= "`".FEES."`.`worklist_id`";
            break;

        case 'fee':
            $orderby .= "`".FEES."`.`amount`";
            break;

        case 'jobs':
            $orderby .= "`jobs`";
            break;

        case 'avg_job':
            $orderby .= "`average`";
            break;

        case 'total_fees':
            $orderby .= "`total`";
            break;
    }

    if ($dateRangeFilter) {
        $where .= $dateRangeFilter;
    }

    if (! empty($w2Filter)) {
        $where .= $w2Filter;
    }

    if ($paidStatusFilter) {
      $where .= $paidStatusFilter;
    }

    if($queryType == "detail") {

        $qcnt = "SELECT count(*)";
        $qsel = "SELECT `".FEES."`.id AS fee_id, DATE_FORMAT(`paid_date`, '%m-%d-%Y') AS paid_date,`worklist_id`,`".WORKLIST."`.`summary` AS `summary`,`desc`,`status`,`".USERS."`.`nickname` AS `payee`,`".FEES."`.`amount`, `".USERS."`.`paypal` AS `paypal`, `expense` AS `expense`,`rewarder` AS `rewarder`,`bonus` AS `bonus`, `" . USERS . "`.`has_W2` AS `has_W2`";
        $qsum = "SELECT SUM(`amount`) as page_sum FROM (SELECT `amount` ";
        $qbody = " FROM `".FEES."`
                   LEFT JOIN `".WORKLIST."` ON `".WORKLIST."`.`id` = `".FEES."`.`worklist_id`
                   LEFT JOIN `".USERS."` ON `".USERS."`.`id` = `".FEES."`.`user_id`
                   LEFT JOIN ".PROJECTS." ON `".WORKLIST."`.`project_id` = `".PROJECTS."`.`project_id`
                   WHERE `amount` != 0 AND `".FEES."`.`withdrawn` = 0 $where ";
        $qorder = "$orderby $dir, `status` ASC, `worklist_id` ASC LIMIT " . ($page - 1) * $limit . ",$limit";

        $rtCount = mysql_query("$qcnt $qbody");
        if ($rtCount) {
            $row = mysql_fetch_row($rtCount);
            $items = intval($row[0]);
        } else {
            $items = 0;
            die(json_encode(array()));
        }
        $cPages = ceil($items/$limit);

        $qPageSumClose = "$orderby $dir, `status` ASC, `worklist_id` ASC LIMIT " . ($page - 1) * $limit . ", $limit ) fee_sum ";

        $sumResult = mysql_query("$qsum $qbody $qPageSumClose");
        if ($sumResult) {
            $get_row = mysql_fetch_row($sumResult);
            $pageSum = $get_row[0];
        } else {
            $pageSum = 0;
        }
        $qGrandSumClose = "ORDER BY `".USERS."`.`nickname` ASC, `status` ASC, `worklist_id` ASC ) fee_sum ";
        $grandSumResult = mysql_query("$qsum $qbody $qGrandSumClose");
        if ($grandSumResult) {
            $get_row = mysql_fetch_row($grandSumResult);
            $grandSum = $get_row[0];
        } else {
            $grandSum = 0;
        }
        $report = array(array($items, $page, $cPages, $pageSum, $grandSum));


        // Construct json for history
        $rtQuery = mysql_query("$qsel $qbody $qorder");
        for ($i = 1; $rtQuery && $row = mysql_fetch_assoc($rtQuery); $i++) {
            $report[$i] = array($row['worklist_id'], $row['fee_id'], $row['summary'], $row['desc'], $row['payee'], $row['amount'], $row['paid_date'], $row['paypal'],$row['expense'],$row['rewarder'],$row['bonus'],$row['has_W2']);
        }

        $concatR = '';
        if ($row['rewarder'] ==1) {
            $concatR = "R";
        }

        $json = json_encode($report);
        echo $json;
    } else if ($queryType == "chart" ) {
        $fees = array();
        $uniquePeople = array();
        $feeCount = array();
        if(isset($from_date)) {
          $fromDate = ReportTools::getMySQLDate($from_date);
        }
        if(isset($to_date)) {
          $toDate = ReportTools::getMySQLDate($to_date);
        }
        $fromDateTime = mktime(0,0,0,substr($fromDate,5,2),  substr($fromDate,8,2), substr($fromDate,0,4));
        $toDateTime = mktime(0,0,0,substr($toDate,5,2),  substr($toDate,8,2), substr($toDate,0,4));

        $daysInRange = round( abs($toDateTime-$fromDateTime) / 86400, 0 );
        $rollupColumn = ReportTools::getRollupColumn('`date`', $daysInRange);
        $dateRangeType = $rollupColumn['rollupRangeType'];

        $qbody = " FROM `".FEES."`
              LEFT JOIN `".WORKLIST."` ON `worklist`.`id` = `".FEES."`.`worklist_id`
              LEFT JOIN `".USERS."` ON `".USERS."`.`id` = `".FEES."`.`user_id`
              LEFT JOIN ".PROJECTS." ON `".WORKLIST."`.`project_id` = `".PROJECTS."`.`project_id`

              WHERE `amount` != 0 AND `".FEES."`.`withdrawn` = 0 $where ";
        $qgroup = " GROUP BY fee_date";

        $qcols = "SELECT " . $rollupColumn['rollupQuery'] . " as fee_date, count(1) as fee_count,sum(amount) as total_fees, count(distinct user_id) as unique_people ";

        $res = mysql_query("$qcols $qbody $qgroup");
        if($res && mysql_num_rows($res) > 0) {
            while ($row = mysql_fetch_array($res, MYSQL_ASSOC)) {
          if ($row['fee_count'] >=1 ) {
                $feeCount[$row['fee_date']] = $row['fee_count'];
                $fees[$row['fee_date']] = $row['total_fees'];
                $uniquePeople[$row['fee_date']] = $row['unique_people'];
               }
            }
        }
        $json_data = array('fees' => ReportTools::fillAndRollupSeries($fromDate, $toDate, $fees, false, $dateRangeType),
            'uniquePeople' => ReportTools::fillAndRollupSeries($fromDate, $toDate, $uniquePeople, false, $dateRangeType),
            'feeCount' => ReportTools::fillAndRollupSeries($fromDate, $toDate, $feeCount, false, $dateRangeType),
            'labels' => ReportTools::fillAndRollupSeries($fromDate, $toDate, null, true, $dateRangeType),
            'fromDate' => $fromDate, 'toDate' => $toDate);
        $json = json_encode($json_data);
        echo $json;
    } else if($queryType == "payee") {
        $payee_report = array();
        $page = $filter->getPage();
        $count_query = " SELECT count(1) FROM ";
        $query = " SELECT   `nickname` AS payee_name, count(1) AS jobs, sum(`amount`) / count(1) AS average, sum(`amount`) AS total  FROM `".FEES."`
                   LEFT JOIN `".USERS."` ON `".FEES."`.`user_id` = `".USERS."`.`id`
                   LEFT JOIN `".WORKLIST."` ON `worklist`.`id` = `".FEES."`.`worklist_id`
                   LEFT JOIN ".PROJECTS." ON `".WORKLIST."`.`project_id` = `".PROJECTS."`.`project_id`
                   WHERE  `".FEES."`.`paid` = 1  ".$where." GROUP BY  `user_id` ";
        $result_count = mysql_query($count_query."(".$query.") AS payee_name");
        if ($result_count) {
            $count_row = mysql_fetch_row($result_count);
            $items = intval($count_row[0]);
        } else {
            $items = 0;
            die(json_encode(array()));
        }
        $countPages = ceil($items/$limit);
        $payee_report[] = array($items, $page, $countPages);
        if(!empty($_REQUEST['defaultSort']) && $_REQUEST['defaultSort'] == 'total_fees') {
            $query .= " ORDER BY total DESC";
        } else {
            $query .= $orderby." ".$dir;
        }
        $query .= "  LIMIT " . ($page - 1) * $limit . ",$limit";
        $result = mysql_query($query);
        if($result && mysql_num_rows($result) > 0) {
          while ($row = mysql_fetch_array($result, MYSQL_ASSOC)) {
             $payee_name = $row['payee_name'];
             $jobs = $row['jobs'];
             $total = number_format($row['total'], 2, '.', '');
             $average =  number_format($row['average'], 2, '.', '');
             $payee_report[] = array($payee_name, $jobs, $average, $total);
          }
        }
        echo json_encode($payee_report);
    }

}

function getSkills() {
    $query = "SELECT `skill` FROM ".SKILLS." ORDER BY skill";
    $result = mysql_query($query);

    $data = array();
    while ($result && $row=mysql_fetch_assoc($result)) {
        $data[] = $row['skill'];
    }

    echo json_encode($data);
}

function getUserItem() {
    $req =  isset($_REQUEST['req'])? $_REQUEST['req'] : 'item';

    if( $req == 'id' )  {
        // Convert Nickname to User ID
        $author = $_REQUEST['nickname'];
        $rt = mysql_query("SELECT id FROM ".USERS." WHERE nickname='$author'");
        $row = mysql_fetch_assoc($rt);
        $json_array = array();
        foreach( $row as $item )    {
            $json_array[] = $item;
        }
        echo json_encode( $json_array );

    } else if ( $req == 'item' )    {
        $item = isset($_REQUEST["item"]) ? intval($_REQUEST["item"]) : 0;
        if ( empty($item) ) {
            return;
        }

        $query = "SELECT id, nickname,username,about,contactway,payway,skills,timezone,DATE_FORMAT(added, '%m/%d/%Y'),is_runner,is_payer
                        FROM ".USERS." WHERE id= $item";

        $rt = mysql_query($query);
        $row = mysql_fetch_assoc($rt);
        $json_row = array();
        foreach($row as $item){
            $json_row[] = $item;
        }

        //changing timezone to human-readable
        if( $json_row[7] )  {
            $json_row[7] = $timezoneTable[$json_row[7]];
        }

        $json = json_encode($json_row);
        echo $json;
    }
}

function getUserItems() {
    $userId = isset($_REQUEST["id"]) ? intval($_REQUEST["id"]) : 0;
    if (empty($userId))
        return;

    $query = "SELECT `" . WORKLIST . "`.`id`, `summary`, `bid_amount`, `bid_done`,"
          . " TIMESTAMPDIFF(SECOND, NOW(), `" . BIDS . "`.`bid_done`) AS `future_delta` FROM `" . WORKLIST . "`"
          . " LEFT JOIN `" . BIDS . "` ON `bidder_id` = `mechanic_id`"
          . " AND `" . BIDS . "`.`accepted`= 1 AND `" . BIDS . "`.`withdrawn`= 0 AND `worklist_id` = `" . WORKLIST . "`.`id`"
          . " WHERE `mechanic_id` = $userId AND status = 'In Progress'";
    $rt = mysql_query($query);

    $items = array();

    while($row = mysql_fetch_assoc($rt)){

        $row['relative'] = relativeTime($row['future_delta']);
        $items[] = $row;
    }

    $json = json_encode($items);

    echo $json;
}

function getUserList() {
    $limit = 30;
    $page = isset($_REQUEST["page"])?intval($_REQUEST["page"]) : 1;
    $letter = isset($_REQUEST["letter"]) ? mysql_real_escape_string(trim($_REQUEST["letter"])) : "";
    $order = !empty($_REQUEST["order"]) ? mysql_real_escape_string(trim($_REQUEST["order"])) : "earnings30";
    $order_dir =  isset($_REQUEST["order_dir"]) ? mysql_real_escape_string(trim($_REQUEST["order_dir"])) : "DESC";
    $active = isset( $_REQUEST['active'] ) && $_REQUEST['active'] == 'TRUE' ? 'TRUE' : 'FALSE';
    $myfavorite = isset( $_REQUEST['myfavorite'] ) && $_REQUEST['myfavorite'] == 'TRUE' ? 'TRUE' : 'FALSE';

    $sfilter = $_REQUEST['sfilter'];

    if($letter == "all"){
      $letter = ".*";
    }
    if($letter == "0-9"){ //numbers
      $letter = "[^A-Za-z]";
    }

    $userid = $_SESSION['userid'];
    $myfavorite_cond = '';
    if ($userid > 0 && $myfavorite == 'TRUE') {
        $myfavorite_cond = 'AND (SELECT COUNT(*) FROM `' . USERS_FAVORITES . "` uf WHERE uf.`user_id`=$userid AND uf.`favorite_user_id`=`" . USERS . "`.`id` AND uf.`enabled` = 1) > 0";
    }

    if( $active == 'FALSE' )    {
        $rt = mysql_query("SELECT COUNT(*) FROM `".USERS."` WHERE `nickname` REGEXP '^$letter' AND `is_active` = 1 $myfavorite_cond");

        $row = mysql_fetch_row($rt);
        $users = intval($row[0]);

    }   else if( $active == 'TRUE' )    {
        $rt = mysql_query("
        SELECT COUNT(*) FROM `".USERS."`
        LEFT JOIN (SELECT `user_id`,MAX(`paid_date`) AS `date` FROM `".FEES."` WHERE `paid_date` IS NOT NULL AND `paid` = 1 AND `withdrawn` != 1 GROUP BY `user_id`) AS `dates` ON `".USERS."`.id = `dates`.user_id
        WHERE `date` > DATE_SUB(NOW(), INTERVAL $sfilter DAY) AND `is_active` = 1 AND `nickname` REGEXP '^$letter' $myfavorite_cond");

        $row = mysql_fetch_row($rt);
        $users = intval($row[0]);
    }
    //SELECT `id`, `nickname`,DATE_FORMAT(`added`, '%m/%d/%Y') AS `joined`, `budget`,
    $cPages = ceil($users/$limit);

    if( $active == 'FALSE' ) {
        $query = "
        SELECT `id`, `nickname`,`added` AS `joined`, `budget`,
        IFNULL(`creators`.`count`,0) + IFNULL(`mechanics`.`count`,0) AS `jobs_count`,
        IFNULL(`earnings`.`sum`,0) AS `earnings`,
        IFNULL(`earnings30`.`sum`,0) AS `earnings30`,
        IFNULL(`rewarder`.`sum`,0)AS `rewarder`
        FROM `".USERS."`
        LEFT JOIN (SELECT `mechanic_id`, COUNT(`mechanic_id`) AS `count` FROM `" . WORKLIST . "` WHERE (`status` IN ('In Progress', 'QA Ready', 'Review', 'Merged', 'Done')) GROUP BY `mechanic_id`) AS `mechanics` ON `".USERS."`.`id` = `mechanics`.`mechanic_id`
        LEFT JOIN (SELECT `creator_id`, COUNT(`creator_id`) AS `count` FROM `" . WORKLIST . "` WHERE (`status` IN ('In Progress', 'QA Ready', 'Review', 'Merged', 'Done')) AND `creator_id` != `mechanic_id` GROUP BY `creator_id`) AS `creators` ON `".USERS."`.`id` = `creators`.`creator_id`
        LEFT JOIN (SELECT `user_id`, SUM(amount) AS `sum` FROM `".FEES."` WHERE $sfilter AND `paid` = 1 AND `withdrawn`=0 AND (`rewarder`=1 OR `bonus`=1) GROUP BY `user_id`) AS `rewarder` ON `".USERS."`.`id` = `rewarder`.`user_id`
        LEFT JOIN (SELECT `user_id`, SUM(amount) AS `sum` FROM `".FEES."` WHERE $sfilter AND `withdrawn`=0 AND `expense`=0 AND `paid` = 1 AND `paid_date` IS NOT NULL GROUP BY `user_id`) AS `earnings` ON `".USERS."`.`id` = `earnings`.`user_id`
        LEFT JOIN (SELECT `user_id`, SUM(amount) AS `sum` FROM `".FEES."` WHERE `withdrawn`=0 AND `paid` = 1 AND `paid_date` IS NOT NULL AND `paid_date` > DATE_SUB(NOW(), INTERVAL 30 DAY) AND `expense`=0 GROUP BY `user_id`) AS `earnings30` ON `".USERS."`.`id` = `earnings30`.`user_id`
        LEFT JOIN (SELECT `user_id`, SUM(amount) AS `sum` FROM `".FEES."` WHERE ($sfilter AND `withdrawn`=0 AND `paid` = 1) AND `expense`=1 GROUP BY `user_id`) AS `expenses_billed` ON `".USERS."`.`id` = `expenses_billed`.`user_id`
        WHERE `nickname` REGEXP '^$letter' AND `is_active` = 1 $myfavorite_cond ORDER BY `$order` $order_dir LIMIT " . ($page-1)*$limit . ",$limit";
    }    else if( $active == 'TRUE' )    {
        $query = "
        SELECT `id`, `nickname`,`added` AS `joined`, `budget`,
        IFNULL(`creators`.`count`,0) + IFNULL(`mechanics`.`count`,0) AS `jobs_count`,
        IFNULL(`earnings`.`sum`,0) AS `earnings`,
        IFNULL(`earnings30`.`sum`,0) AS `earnings30`,
        IFNULL(`rewarder`.`sum`,0)AS `rewarder`
        FROM `".USERS."`
        LEFT JOIN (SELECT `user_id`,MAX(`date`) AS `date` FROM `".FEES."` WHERE `paid` = 1 AND `amount` != 0 AND `withdrawn` = 0 AND `expense` = 0 GROUP BY `user_id`) AS `dates` ON `".USERS."`.id = `dates`.user_id
        LEFT JOIN (SELECT `mechanic_id`, COUNT(`mechanic_id`) AS `count` FROM `" . WORKLIST . "` WHERE (`status` IN ('In Progress', 'QA Ready', 'Review', 'Merged', 'Done')) GROUP BY `mechanic_id`) AS `mechanics` ON `".USERS."`.`id` = `mechanics`.`mechanic_id`
        LEFT JOIN (SELECT `creator_id`, COUNT(`creator_id`) AS `count` FROM `" . WORKLIST . "` WHERE (`status` IN ('In Progress', 'QA Ready', 'Review', 'Merged', 'Done')) AND `creator_id` != `mechanic_id` GROUP BY `creator_id`) AS `creators` ON `".USERS."`.`id` = `creators`.`creator_id`
        LEFT JOIN (SELECT `user_id`, SUM(amount) AS `sum` FROM `".FEES."` WHERE $sfilter AND `paid` = 1 AND `withdrawn`=0 AND (`rewarder`=1 OR `bonus`= 1) GROUP BY `user_id`) AS `rewarder` ON `".USERS."`.`id` = `rewarder`.`user_id`
        LEFT JOIN (SELECT `user_id`, SUM(amount) AS `sum` FROM `".FEES."` WHERE $sfilter AND `withdrawn`=0 AND `expense`=0 AND `paid` = 1 AND `paid_date` IS NOT NULL GROUP BY `user_id`) AS `earnings` ON `".USERS."`.`id` = `earnings`.`user_id`
        LEFT JOIN (SELECT `user_id`, SUM(amount) AS `sum` FROM `".FEES."` WHERE `withdrawn`=0 AND `paid` = 1 AND `paid_date` IS NOT NULL AND `paid_date` > DATE_SUB(NOW(), INTERVAL 30 DAY) AND `expense`=0 GROUP BY `user_id`) AS `earnings30` ON `".USERS."`.`id` = `earnings30`.`user_id`
        LEFT JOIN (SELECT `user_id`, SUM(amount) AS `sum` FROM `".FEES."` WHERE ($sfilter AND `withdrawn`=0 AND `paid` = 1) AND `expense`=1 GROUP BY `user_id`) AS `expenses_billed` ON `".USERS."`.`id` = `expenses_billed`.`user_id`
        WHERE `date` > DATE_SUB(NOW(), INTERVAL $sfilter DAY) AND `nickname` REGEXP '^$letter' AND `is_active` = 1 $myfavorite_cond ORDER BY `$order` $order_dir LIMIT " . ($page-1)*$limit . ",$limit";
    }
    $rt = mysql_query($query);

    // Construct json for pagination
    $userlist = array(array($users, $page, $cPages));

    while($row = mysql_fetch_assoc($rt)){
        $user = new User();
        $user->findUserById($row['id']);
        if ($row['budget'] < 1){
            $row['budget'] = 'NONE';
        } else {
            $row['budget'] = '$'.number_format($user->getRemainingFunds(), 0);
        }
        $row['earnings'] = $user->totalEarnings();
        $diffseconds = strtotime($row['joined']);
        $row['joined'] = formatableRelativeTime($diffseconds,2);
        $userlist[] = $row;
    }

    $json = json_encode($userlist);
    echo $json;
}

function getUsersList() {
    $query = "SELECT id, nickname FROM " . USERS . " WHERE 1=1";

    if (isset($_REQUEST['getNicknameOnly'])) {
        $query = "SELECT nickname FROM " . USERS . " WHERE 1=1";
    }

    if (isset($_REQUEST['startsWith']) && !empty($_REQUEST['startsWith'])) {
        $startsWith = $_REQUEST['startsWith'];
        $query .= " AND nickname like '".mysql_real_escape_string($startsWith)."%'";
    }
    $query .= " order by nickname limit 0,10";

    $result = mysql_query($query);


    $data = array();
    while ($result && $row=mysql_fetch_assoc($result)) {
        if ($_REQUEST['getNicknameOnly']) {
            $data[] = $row['nickname'];
        } else {
            $data[] = $row;
        }

    }

    echo json_encode($data);
}

function getWorkitem() {
    $userId = isset($_SESSION['userid'])? $_SESSION['userid'] : 0;

    $item = isset($_REQUEST["item"]) ? intval($_REQUEST["item"]) : 0;
    if (empty($item))
        return;

    $query = "SELECT
            w.id,
            w.summary,
            c.nickname creator,
            w.status job_status,
            w.notes,
            p.name project,
            r.nickname runner,
            m.nickname mechanic
        FROM ".WORKLIST." w
        LEFT JOIN " . USERS . " c ON w.creator_id = c.id
        LEFT JOIN " . USERS . " r ON w.runner_id = r.id
        LEFT JOIN " . USERS . " m ON w.mechanic_id = m.id
        LEFT JOIN ".PROJECTS." p ON w.project_id = p.project_id
        WHERE w.id = '$item'
            AND (w.status <> 'Draft' OR (w.status = 'Draft' AND w.creator_id = '$userId'))";
    $rt = mysql_query($query);
    if ($rt) {
        $row = mysql_fetch_assoc($rt);
        $row['notes'] = truncateText($row['notes']);
        $query1 = ' SELECT c.comment, u.nickname '
                . ' FROM ' . COMMENTS . ' AS c '
                . ' INNER JOIN ' . USERS . ' AS u ON c.user_id = u.id '
                . ' WHERE c.worklist_id = ' . $row['id']
                . ' ORDER BY c.id DESC '
                . ' LIMIT 1';

        $rtc = mysql_query($query1);
        if ($rt) {
            $rowc = mysql_fetch_assoc($rtc);
            $row['comment'] = truncateText($rowc['comment']);
            $row['commentAuthor'] = $rowc['nickname'];
        } else {
            $row['comment'] = 'No comments yet.';
        }
        $json = json_encode($row);
    } else {
        $json = json_encode(array('error' => "No data available"));
    }
    echo $json;
}

function pingTask() {
    checkLogin();

    // Get sender Nickname
    $id = getSessionUserId();
    $user = getUserById($id);
    $nickname = $user->nickname;
    $email = $user->username;
    $msg = $_REQUEST['msg'];
    $send_cc = isset($_REQUEST['cc']) ? (int) $_REQUEST['cc'] : false;

    // ping about concrete task
    if (isset($_REQUEST['id'])) {
        $item_id = intval($_REQUEST['id']);
        $who = $_REQUEST['who'];
        // Get item
        $item = getWorklistById( $item_id );

        if( $who == 'mechanic' ) {
            // Get mechanic Nickname & email
            $receiver_id = $item['mechanic_id'];
            $receiver = getUserById( $receiver_id );
            $receiver_nick = $receiver->nickname;
            $receiver_email = $receiver->username;
        } else if( $who == 'runner' ) {
            // Get runner Nickname & email
            $receiver_id = $item['runner_id'];
            $receiver = getUserById( $receiver_id );
            $receiver_nick = $receiver->nickname;
            $receiver_email = $receiver->username;
        } else if($who == 'creator' ) {
            // Get runner Nickname & email
            $receiver_id = $item['creator_id'];
            $receiver = getUserById( $receiver_id );
            $receiver_nick = $receiver->nickname;
            $receiver_email = $receiver->username;
        } else if ($who == 'bidder') {
            // Get bidder Nickname & email
            if (isset($_REQUEST['bid_id'])) {
                $bid_id = (int) $_REQUEST['bid_id'];
            } else {
                echo json_encode(array("error" => "missing parameter bid_id"));
                die();
            }
            $bid = new Bid();
            $bid->findBidById($bid_id);
            $bid_info = $bid->toArray();
            $receiver_id = $bid_info['bidder_id'];
            $receiver = getUserById( $receiver_id );
            $receiver_nick = $receiver->nickname;
            $receiver_email = $receiver->username;
        }
        // Send mail
        if ($who != 'bidder') {
            $mail_subject = $nickname." sent you a message on Worklist for item #".$item_id;
            $mail_msg .= "<p><a href='" . WORKLIST_URL .'user/' . $id . "'>" . $nickname . "</a>";
            $mail_msg .= " sent you a message about item ";
            $mail_msg .= "<a href='" . WORKLIST_URL . $item_id . "'>#" . $item_id . "</a>";
            $mail_msg .= "</p><p>----------<br/>".$msg."<br/>----------</p><p>You can reply via email to: ".$email."</p>";
            $headers = array('X-tag' => 'ping, task', 'From' => NOREPLY_SENDER, 'Reply-To' => '"' . $nickname . '" <' . $email . '>');
            if ($send_cc) {
                $headers['Cc'] = '"' . $nickname . '" <' . $email . '>';
            }
            if (!send_email($receiver_email, $mail_subject, $mail_msg, '', $headers)) {
                error_log('pingtask.php:id: send_email failed');
            }

        } else if ($who == 'bidder') {
            $project = new Project();
            $project->loadById($item['project_id']);
            $project_name = $project->getName();
            $mail_subject = "#" . $item_id . " - " . $item['summary'];
            $mail_msg = "<p>The Designer for #" . $item_id . " sent a reply to your bid.</p>";
            $mail_msg .= "<p>Message from " . $nickname . ":<br/>" . $msg . "</p>";
            $mail_msg .= "<p>Your bid info:</p>";
            $mail_msg .= "<p>Amount: " . $bid_info['bid_amount'] . "<br />Done in: " . $bid_info['bid_done_in'] . "<br />Expires: " . $bid_info['bid_expires'] . "</p>";
            $mail_msg .= "<p>Notes: " . $bid_info['notes'] . "</p>";
            $mail_msg .= "<p>You can view the job here. <a href='./" . $item_id . "?action=view'>#" . $item_id . "</a></p>";
            $mail_msg .= "<p><a href=\"www.worklist.net\">www.worklist.net</a></p>";
            $headers = array('From' => '"'. $project_name.'-bid reply" <'. SMS_SENDER . '>', '
                X-tag' => 'ping, task',
                'From' => NOREPLY_SENDER,
                'Reply-To' => '"' . $nickname . '" <' . $email . '>');
            if ($send_cc) {
                $headers['Cc'] = '"' . $nickname . '" <' . $email . '>';
            }
            if (!send_email($receiver_email, $mail_subject, $mail_msg, '', $headers)) {
                error_log('pingtask.php:id: send_email failed');
            }

        }

    } else {

        // just send general ping to user

        $receiver = getUserById(intval($_REQUEST['userid']));
        $receiver_nick = $receiver->nickname;
        $receiver_email = $receiver->username;

        $mail_subject = $nickname." sent you a message on Worklist";
        $mail_msg = "<p><a href='" . WORKLIST_URL .'user/' . $id . "'>" . $nickname . "</a>";
        $mail_msg .=" sent you a message: ";
        $mail_msg .= "</p><p>----------<br/>". nl2br($msg)."<br />----------</p><p>You can reply via email to ".$email."</p>";

        $headers = array('X-tag' => 'ping', 'From' => NOREPLY_SENDER, 'Reply-To' => '"' . $nickname . '" <' . $email . '>');
        if ($send_cc) {
            $headers['Cc'] = '"' . $nickname . '" <' . $email . '>';
        }
        if (!send_email($receiver_email, $mail_subject, $mail_msg, '', $headers)) {
            error_log("pingtask.php:!id: send_email failed");
        }
    }
    echo json_encode(array());
}

function refreshFilter() {
    checkLogin();

    // If no action is passed exit
    if (!isset($_REQUEST['name']) || !isset($_REQUEST['active'])) {
        return;
    }

    $name = $_REQUEST['name'];
    $active = intval($_REQUEST['active']);
    $type = $_REQUEST['filter'];

    $filter = new Agency_Worklist_Filter();
    $filter->setName($name)
           ->initFilter();

    $json = array();

    switch ($type) {
        case 'projects':
            $projects = Project::getProjects($active);

           $json[] = array(
                'value' => 0,
                'text' => 'All projects',
                'selected' => false
            );

            foreach ($projects as $project) {
                $json[] = array(
                    'value' => $project['project_id'],
                    'text' => $project['name'],
                    'selected' => false
                );
            }

            break;

        case 'users':
            $users = User::getUserList(getSessionUserId(), $active);
            $json[] = array(
                'value' => 0,
                'text' => 'All users',
                'selected' => (($filter->getUser() == 0) ? true : false)
            );
            foreach ($users as $user) {
                $json[] = array(
                    'value' => $user->getId(),
                    'text' => $user->getNickname(),
                    'selected' => (($filter->getUser() == $user->getId()) ? true : false)
                );
            }

            break;
    }

    echo(json_encode($json));
}

function workitemSandbox() {
    $workitemSandbox = new WorkitemSandbox();
    $workitemSandbox->validateRequest(array('method'));
    $method = $_REQUEST['method'];
    $workitemSandbox->$method();
}

function userNotes() {
    $usernotes = new UserNotes();
    $usernotes->validateRequest(array('method'));

    $method = $_REQUEST['method'];
    $usernotes->$method();
}

function visitQuery() {
    /*
     * Google Analytics API Token
     * New tokens can be created by calling auth.php in the subdir resources
     */
    $token = GOOGLE_ANALYTICS_TOKEN;
    /* site ids can be obtained from analytics
     * by logging into the profile, it's currently
     * called Profile ID on screen
     */
    $ids = GOOGLE_ANALYTICS_PROFILE_ID;

    $jobid = (int) $_GET['jobid'];
    if ($jobid > 0) {
        $results = VisitQueryTools::getJobResults($jobid, $token, $ids);
    } elseif ($jobid === 0) {
        $results = VisitQueryTools::getAllJobResults($token, $ids);
    }
    if (!isset($results)) {
        echo "{error: 'invalid job id supplied'}";
    } else {
        if(!isset($results['error'])) {
            if ($jobid === 0) {
                $data = VisitQueryTools::parseItems($results['result']);
            } else {
                $data = VisitQueryTools::parseItem($results['result']);
            }
            echo json_encode($data);
        } else {
            echo "{error: '" . $results['error'] . "' }";
        }
    }
}

function wdFee() {
    checkLogin();

    $fee_id = (int)$_GET["wd_fee_id"];
    if ($fee_id < 1) { return 'Update Failed'; }

    $fee_update_sql = 'UPDATE '.FEES.' SET withdrawn = \'1\' WHERE id = '.$fee_id;

    //Restrict fee removal to user and those authorized to affect money
    if (empty($_SESSION['is_payer']) && empty($_SESSION['is_runner']) && !empty($_SESSION['userid'])) {
        $fee_update_sql .= ' and `user_id` = ' . ($_SESSION['userid']);
    }

    $fee_update = mysql_query($fee_update_sql) or error_log("wd_fee mysql error: $fee_update_sql\n".json_encode($_SESSION) . mysql_error());

    if ($fee_update) {
        echo 'Update Successful!';
    } else {
        echo 'Update Failed!';
    }
}

function timeline() {
    require_once('models/Timeline.php');

    $timeline = new Timeline();
    if ($_POST["method"] == "getHistoricalData") {
        if (isset($_POST["project"])) {
            $project = $_POST["project"];
        }
        if ($project) {
            $objectData = $timeline->getHistoricalData($project);
        } else {
            $objectData = $timeline->getHistoricalData();
        }
        echo json_encode($objectData);
    } else if ($_POST["method"] == "getDistinctLocations") {
        $objectData = $timeline->getDistinctLocations();
        echo json_encode($objectData);
    } else if ($_POST["method"] == "storeLatLong") {
        $location = $_POST["location"];
        $latlong = $_POST["latlong"];
        $timeline->insertLocationData($location, $latlong);
    } else if ($_REQUEST["method"] == "getLatLong") {
        $objectData = $timeline->getLocationData();
        echo json_encode($objectData);
    } else if ($_POST["method"] == "getListOfMonths"){
        $months = $timeline->getListOfMonths();
        echo json_encode($months);
    }
}

function sendNewUserNotification() {

    $db = new Database();
    $recipient = array('grayson@highfidelity.io', 'chris@highfidelity.io');

    /**
     * The email is to be sent Monday to Friday, therefore on a Monday
     * we want to capture new signups since the previous Friday morning
     */
    $interval = 1;
    if (date('N') === 1) {
        $interval = 3;
    }

    $sql = "
        SELECT * FROM " . USERS . "
        WHERE
            added > DATE_SUB(NOW(), INTERVAL {$interval} DAY)";

    $result_temp = $db->query($sql);

    $data = '<ol>';

    while ($row_temp = mysql_fetch_assoc($result_temp)) {
        $data .= sprintf('<li><a href="%suser/%d">%s</a> / <a href="mailto:%s">%s</a></li>',
            SERVER_URL,
            $row_temp['id'],
            $row_temp['nickname'],
            $row_temp['username'],
            $row_temp['username']
        );
    }

    $data .= '</ol>';

    $mergeData = array(
        'userList' => $data,
        'hours' => $interval * 25
    );

    if (! sendTemplateEmail($recipient, 'user-signups', $mergeData)) {
        error_log('sendNewUserNotification cron: Failed to send email report');
    }
}

// This is responsible for the weekly job report that is being sent to the users.
function sendJobReport() {
    // Let's fetch the data.
    $sql = "
        SELECT w.id, u.nickname, w.summary, w.status, u.first_name, u.last_name
        FROM worklist w
        INNER JOIN users u
          ON u.id = w.runner_id
        WHERE
          (w.status IN('In Progress', 'Review', 'QA Ready', 'Merged'))
        OR
          (w.status_changed > DATE_SUB(NOW(), INTERVAL 7 Day) AND w.status IN('Done'))
        ORDER BY u.nickname, w.id;";

    // Build our data
    # $jobs_data = array( array(), array() );
    $res = mysql_query($sql);
    if($res) {
        while($row = mysql_fetch_assoc($res)) {
            if ($row['status'] == 'Done') {
                $jobs_data[$row['nickname']]['done'][] = $row;
            } else {
                $jobs_data[$row['nickname']]['working'][] = $row;
            }

        }
    }

    // Build the output
    $html = $text = '';
    $img_baseurl = WORKLIST_URL . 'user/avatar/';

    foreach ($jobs_data as $user_jobs) {
        $fullname = trim($user_jobs[key($user_jobs)][0]['first_name'] . ' ' . $user_jobs[key($user_jobs)][0]['last_name']);
        $nickname = $user_jobs[key($user_jobs)][0]['nickname'];
        $img_url = $img_baseurl . $nickname . '/35';
        if ($fullname == '') {
          $calling = $nickname;
        } else {
          $calling = $fullname . '(' . $nickname . ')';
        }

        $html .=
            '<tr>' .
            '  <td style="width: 35px; padding: 0">' .
            '    <a href="' . WORKLIST_URL . 'user/' . $nickname . '">' .
            '      <img src="' . $img_url . '" />' .
            '    </a>' .
            '  </td>' .
            '  <td style="padding: 0 0 0 10px">' .
            '    <a href="' . WORKLIST_URL . 'user/' . $nickname . '">' .
            '      <h3 style="color: #007F7C; margin: 0; display: inline-block; font-size: 1.5em">' . $calling . '</h3>' .
            '    </a>' .
            '  </td>' .
            '</tr>' .
            '<tr>' .
            '  <td style="width: 35px; padding: 0">&nbsp;</td>' .
            '  <td style="padding: 0 0 0 10px">';
        $text .= '### ' . $calling . "\n";

        // Completed jobs
        if (isset($user_jobs['done'])) {
            $html .=
                '    <h4 style="margin: 0">Completed in last week:</h4>' .
                '    <ul style="padding-left: 10px">';
            $text .= "#### Completed in last week:\n";
            foreach ($user_jobs['done'] as $job) {
                $html .=
                    '      <li>' .
                    '        <a style="color: #333; text-decoration: none" href="' . WORKLIST_URL .  $job['id'] . '">' .
                    '          #' . $job['id'] . ' - ' . $job['summary'] .
                    '        </a>' .
                    '      </li>';
                $text .= ' * #' . $job['id'] . ' - ' . $job['summary'] . ': ' . WORKLIST_URL . '/' . $job['id'] . "\n";
            }
            $html .= '    </ul>';
            $text .= "\n";
        }

        // In progress
        if (isset($user_jobs['working'])) {
            $html .=
                '    <h4 style="margin: 0">In Progress:</h4>' .
                '    <ul style="padding-left: 10px">';
            $text .= "#### In Progress:\n";
            foreach ($user_jobs['working'] as $job) {
                $html .= '      <li>' .
                         '        <a style="color: #333; text-decoration: none" href="' . WORKLIST_URL .  $job['id'] . '">' .
                         '          #' . $job['id'] . ' - ' . $job['summary'] .
                         '        </a>' .
                         '      </li>';
                $text .= ' * #' . $job['id'] . ' - ' . $job['summary'] . ': ' . WORKLIST_URL . '/' . $job['id'] . "\n";
            }
            $html .= '    </ul>';
            $text .= "\n";
        }

        $html .=
            '  </td>' .
            '</tr>';
        $text .= "\n";

    }

    // Send the emails
    $sql = 'SELECT DISTINCT username FROM users WHERE is_runner = 1' ;
    $user_data = mysql_query($sql);
    $emails = array();
    while ($row = mysql_fetch_assoc($user_data)) {
        array_push($emails, $row["username"]);
    }

    $email_content = array(
        'data' => $html,
        'text' => $text
    );

    if (! sendTemplateEmail($emails, 'jobs-weekly-report', $email_content, 'contact@highfidelity.io')) {
        error_log('sendJobReport cron: Emails could not be sent.');
    }
}
