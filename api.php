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
            case 'manageBudget':
                manageBudget();
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
                getProjects();
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
            case 'getUserStats':
                getUserStats();
                break;
            case 'getWorkitem':
                getWorkitem();
                break;
            case 'getWorklist':
                getWorklist();
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
            case 'testFlight':
                testFlight();
                break;
            case 'updateBudget':
                updateBudget();
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
            case 'budgetInfo':
                budgetInfo();
                break;
            case 'budgetHistory':
                budgetHistory();
                break;
            case 'timeline':
                timeline();
                break;
            case 'newUserNotification':
                validateAPIKey();
                sendNewUserNotification();
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

function validateAPIKey() {
    if( ! isset($_REQUEST["api_key"])) {
        error_log("No api key defined.");
        die("No api key defined.");
    } else if(strcmp($_REQUEST["api_key"],API_KEY) != 0 ) {
        error_log("Wrong api key provided.");
        die("Wrong api key provided.");
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
function sendTestNotifications(){
    $workItemId = isset($_REQUEST['workitem_id']) ? $_REQUEST['workitem_id'] : 0 ;
    $results = isset($_REQUEST['results']) ? $_REQUEST['results'] : '';
    $revision = isset($_REQUEST['revision']) ? $_REQUEST['revision'] : '';
    require_once('./classes/Notification.class.php');
    if($workItemId > 0) {
        $notify = new Notification();
        $notify->autoTestNofications($workItemId,$results,$revision);
        exit(json_encode(array('success' => true)));
    }
}

function autoPassSuggestedJobs() {
    $con = mysql_connect(DB_SERVER, DB_USER, DB_PASSWORD);
    if (!$con) {
        die('Could not connect: ' . mysql_error());
    }
    mysql_select_db(DB_NAME, $con);
    $sql = "SELECT id FROM `" . WORKLIST ."` WHERE  status  IN ( 'Suggested' , 'SuggestedWithBid', 'Bidding') AND DATEDIFF(now() , status_changed) > 30";

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

function addProject() {
    $journal_message = '';
    $nick = '';

    $userId = getSessionUserId();
    if ($userId) {
        initUserById($userId);
        $user = new User();
        $user->findUserById( $userId );
        $nick = $user->getNickname();

        $project = new Project();
        $cr_3_favorites = $_REQUEST["cr_3_favorites"];
        $args = array(
            'name',
            'description',
            'logo',
            'website',
            'checkGitHub',
            'github_repo_url',
            'defaultGithubApp',
            'githubClientId',
            'githubClientSecret'
        );

        foreach ($args as $arg) {
            $$arg = !empty($_POST[$arg]) ? $_POST[$arg] : '';
        }

        if (!ctype_alnum($name)) {
            die(json_encode(array('error' => "The name of the project can only contain letters (A-Z) and numbers (0-9). Please review and try again.")));
        }
        $repository = $name;

        if ($project->getIdFromName($name)) {
            die(json_encode(array('error' => "Project with the same name already exists!")));
        }

        $project->setName($name);
        $project->setDescription($description);
        $project->setWebsite($website);
        $project->setContactInfo($user->getUsername());
        $project->setOwnerId($userId);
        $project->setActive(true);
        $project->setLogo($logo);
        if ($checkGitHub == 'true') {
            $project->setRepo_type('git');
            $project->setRepository($github_repo_url);

            if ($defaultGithubApp == 'false') {
                $project->setGithubId($githubClientId);
                $project->setGithubSecret($githubClientSecret);
            }
        } else {
            $project->setRepo_type('svn');
            $project->setRepository($name);
        }
        $project->save();

        $journal_message = '@' . $nick . ' added project *' . $name . '*';
        if (!empty($journal_message)) {
            //sending journal notification
            sendJournalNotification($journal_message);
        }

        echo json_encode(array( 'return' => "Done!"));
    } else {
        echo json_encode(array( 'error' => "You must be logged in to add a new project!"));
    }
}

function addWorkitem() {
    $journal_message = '';
    $workitem_added = false;
    $nick = '';

    $workitem = new WorkItem();

    $userId = getSessionUserId();
    if (!$userId > 0 ) {
        echo json_encode(array('error' => "Invalid parameters !"));
        return;
    }

    initUserById($userId);
    $user = new User();
    $user->findUserById( $userId );
    $nick = $user->getNickname();

    $itemid = $_REQUEST['itemid'];
    $summary = $_REQUEST['summary'];
    $project_id = $_REQUEST['project_id'];
    $skills = $_REQUEST['skills'];
    $status = $_REQUEST['status'];
    $notes = $_REQUEST['notes'];
    $invite = $_REQUEST['invite'];
    $is_expense = $_REQUEST['is_expense'];
    $is_rewarder = $_REQUEST['is_rewarder'];
    $fileUpload = $_REQUEST['fileUpload'];
    $bug_job_id = $_REQUEST['bug_job_id'];
    $is_bug = false;

    if (!$user->getIs_runner() || !preg_match('/^(Draft|Suggested|Bidding|Working|Done)$/', $status)) {
        $status = 'Suggested';
    }

    if (! empty($_POST['itemid'])) {
        $workitem->loadById($_POST['itemid']);
    } else {
        $workitem->setCreatorId($userId);
        $workitem_added = true;
    }
    $workitem->setSummary($summary);

    try {
        $bugof_workitem = new WorkItem();
        $bugof_workitem->loadById($bug_job_id);
        $is_bug = true;
    } catch(Exception $e) {
        // do not set as bug
        $bug_job_id = 0;
    }


    //If this item is a bug add original item id
    $workitem->setBugJobId($bug_job_id);
    // not every runner might want to be assigned to the item he created - only if he sets status to 'BIDDING'
    if ($status == 'Bidding' && $user->getIs_runner() == 1) {
        $runner_id = $userId;
    } else {
        $runner_id = 0;
    }

    $skillsArr = explode(', ', $skills);

    $workitem->setRunnerId($runner_id);
    $workitem->setProjectId($project_id);
    $workitem->setStatus($status);
    $workitem->setNotes($notes);
    $workitem->setWorkitemSkills($skillsArr);
    $workitem->setIs_bug($is_bug == 'true' ? 1 : 0);
    $workitem->save();
    $related = getRelated($notes);
    Notification::massStatusNotify($workitem);

    // if files were uploaded, update their workitem id
    $file = new File();
    // update images first
    if (isset($fileUpload['images'])) {
        foreach ($fileUpload['images'] as $image) {
            $file->findFileById($image);
            $file->setWorkitem($workitem->getId());
            $file->save();
        }
    }
    // update documents
    if (isset($fileUpload['documents'])) {
        foreach ($fileUpload['documents'] as $document) {
            $file->findFileById($document);
            $file->setWorkitem($workitem->getId());
            $file->save();
        }
    }

    if($is_bug && $bug_job_id > 0) {
        //Load information about original job and notify
        //users with fees and runner
        Notification::workitemNotify(array(
            'type' => 'bug_found',
            'workitem' => $workitem,
            'recipients' => array('runner', 'usersWithFeesBug')
        ));

        $bugJournalMessage= " (bug of #" . $workitem->getBugJobId() .")";
    } else {
        $bugJournalMessage= "";
    }

    if (empty($_POST['itemid'])) {
        $bid_fee_itemid = $workitem->getId();
        $journal_message .= "\\\\#"  . $bid_fee_itemid . ' ' .$bugJournalMessage.' created by @' . $nick . ' Status set to ' . $status;
        if (!empty($_POST['files'])) {
            $files = explode(',', $_POST['files']);
            foreach ($files as $file) {
                $sql = 'UPDATE `' . FILES . '` SET `workitem` = ' . $bid_fee_itemid . ' WHERE `id` = ' . (int)$file;
                mysql_query($sql);
            }
        }
    } else {
        $bid_fee_itemid = $itemid;
        $journal_message .= '\\#' . $bid_fee_itemid . ' updated by ' . $nick . 'Status set to ' . $status;
    }
    $journal_message .=  "$related. ";
    if (!empty($_POST['invite'])) {
        $people = explode(',', $_POST['invite']);
        invitePeople($people, $workitem);
    }

    // don't send any journal notifications for DRAFTS
    if (!empty($journal_message) && $status != 'Draft') {

        sendJournalNotification(stripslashes($journal_message));

        if ($workitem_added) {
            $options = array(
                'type' => 'workitem-add',
                'workitem' => $workitem,
            );
            $data = array(
                'notes' => $notes,
                'nick' => $nick,
                'status' => $status
            );

            Notification::workitemNotifyHipchat($options, $data);
        }

        // workitem mentions
        $matches = array();
        if (preg_match_all(
            '/@(\w+)/',
            $workitem->getNotes(),
            $matches,
            PREG_SET_ORDER
        )) {

            $user = new User();

            foreach ($matches as $mention) {
                // validate the username actually exists
                if ($recipient = $user->findUserByNickname($mention[1])) {
                    $emailTemplate = 'workitem-mention';
                    $data = array(
                        'job_id' => $workitem->getId(),
                        'author' => $_SESSION['nickname'],
                        'text' => $workitem->getNotes(),
                        'link' => '<a href="' . WORKLIST_URL . $workitem->getId() . '">See the workitem</a>'
                    );

                    $senderEmail = 'Worklist <contact@worklist.net>';
                    sendTemplateEmail($recipient->getUsername(), $emailTemplate, $data, $senderEmail);
                }
            }
        }
    }

    // Notify Runners of new suggested task
    if ($status == 'Suggested' && $project_id != '') {
        $options = array(
            'type' => 'suggested',
            'workitem' => $workitem,
            'recipients' => array('projectRunners')
        );
        $data = array(
            'notes' => $notes,
            'nick' => $nick,
            'status' => $status
        );

        Notification::workitemNotify($options, $data);
    }

    echo json_encode(array(
        'return' => "Done!",
        'workitem' => $workitem->getId()
    ));

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

                $resetUrl = SECURE_SERVER_URL . 'jobs#userid=' . $favorite_user_id ;
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

function manageBudget() {
    // Check that this info is requested by a runner
    if (!isset($_SESSION['is_runner']) || $_SESSION['is_runner'] != 1) {
        echo "Error: Unauthorized";
        die;
    }

    // Check a section request is given
    if (!isset($_REQUEST['section'])) {
        echo "No section requested.";
        die;
    }
    $budget_id = 0;
    if (isset($_REQUEST['budget_id'])) {
        $budget_id = (int) $_REQUEST['budget_id'];
    }

    // Check if we've received sorting request
    $sortby = "";
    $desc = "";
    $sort = false;
    if (isset($_REQUEST['sortby']) && isset($_REQUEST['desc'])) {
        switch ($_REQUEST['sortby']) {
            case 'be-id':
            case 'bet-id':
                $sortby = 'id';
                break;
            case 'be-budget':
                $sortby = 'budget_id';
                break;
            case 'bet-budget':
                $sortby = 'budget_title';
                break;
            case 'be-summary':
                $sortby = 'summary';
                break;
            case 'bet-notes':
                $sortby = 'notes';
                break;
            case 'be-who':
            case 'bet-who':
                $sortby = 'who';
                break;
            case 'be-amount':
            case 'bet-amount':
                $sortby = 'amount';
                break;
            case 'be-status':
                $sortby = 'status';
                break;
            case 'be-created':
            case 'bet-created':
                $sortby = 'created';
                break;
            case 'be-paid':
                $sortby = 'paid';
                break;
        }
        $desc = $_REQUEST['desc'];
        $sort = true;
    }

    $section = $_REQUEST['section'];

    if (!isset($_REQUEST['method'])) {
        switch ($section) {
            case 0:
                if ($sort) {
                    echo BudgetTools::getAllocated($budget_id, $sortby, $desc);
                } else {
                    echo BudgetTools::getAllocated($budget_id);
                }
                break;
            case 1:
                if ($sort) {
                    echo BudgetTools::getSubmitted($budget_id, $sortby, $desc);
                } else {
                    echo BudgetTools::getSubmitted($budget_id);
                }
                break;
            case 2:
                if ($sort) {
                    echo BudgetTools::getPaid($budget_id, $sortby, $desc);
                } else {
                    echo BudgetTools::getPaid($budget_id);
                }
                break;
            case 3:
                if ($sort) {
                    echo BudgetTools::getTransferred($budget_id, $sortby, $desc);
                } else {
                    echo BudgetTools::getTransferred($budget_id);
                }
                break;
        }
    } else {
        if ($_REQUEST['method'] == 'export') {
            // Export to CSV
            switch ($section) {
                case 0:
                    $data = json_decode(BudgetTools::getAllocated());
                    BudgetTools::exportCSV($data);
                    break;
                case 1:
                    $data = json_decode(BudgetTools::getSubmitted());
                    BudgetTools::exportCSV($data);
                    break;
                case 2:
                    $data = json_decode(BudgetTools::getPaid());
                    BudgetTools::exportCSV($data);
                    break;
                case 3:
                    $data = json_decode(BudgetTools::getTransferred($budget_id));
                    BudgetTools::exportCSV_Transferred($data);
                    break;
            }
        }
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
    $data = '';

    if (sizeof($bids) > 0 ) {
        ?>
            <form name="popup-multiple-bid-form" id="popup-multiple-bid-form" action="" method="post">
                 <table width="100%" class="table-bids">
                    <caption class="table-caption" >
                        <b>Bids</b>
                    </caption>
                    <thead>
                        <tr class="table-hdng">
                            <th><span>User</span></th>
                            <th><span>Amount</span></th>
                            <th><span>Done in</span></th>
                            <th><span>Expires</span></th>
                            <th><span>Notes</span></th>
                            <th><span>Accept</span></th>
                            <th><span>Mechanic</span></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                            foreach($bids as $bid) {
                                $expire_class = '';
                                if ($bid['expires'] <= BID_EXPIRE_WARNING) {
                                    $expire_class = 'class="warn"';
                                }
                                $data .= '
                                        <tr>
                                            <td>
                                                <a href="./user/' . $bid['bidder_id'] . '" >
                                                   ' . $bid['nickname'] . '
                                                </a>
                                            </td>
                                            <td>'.$bid['bid_amount'].'</td>
                                            <td>'.$bid['done_in'].'</td>
                                            <td ' . $expire_class . '>'. relativeTime($bid['expires']) .'</td>
                                            <td>'.$bid['notes'].'</td>
                                            <td><input type="checkbox" class="acceptMechanic" name="chkMultipleBid[]" value="'.$bid['id'].'" /></td>
                                            <td><input type="checkbox" name="mechanic" class="chkMechanic" value="'.$bid['bidder_id'].'" /></td>
                                        </tr>';
                            }
                            echo $data;
                        ?>
                    </tbody>
                </table>
                <input type="submit" style="display:none;" name="accept_multiple_bid" id="accept_multiple_bid" value="Confirm Accept Selected">
                <input type="hidden" id="budget_id_multiple_bid" name="budget_id" value="" />
                <input type="button" id="accept_bid_select_budget"
                       name="accept_bid_select_budget" value="Accept Selected">
            </form>
        <?php
    } else {
        echo 'No Bid Present';
    }
}

function getProjects() {
    // Create project object
    $projectHandler = new Project();

    // page 1 is "all active projects"
    $page = isset($_REQUEST['page']) ? (int) $_REQUEST['page'] : 1;

    // for subsequent pages, which will be inactive projects, return 10 at a time
    if ($page > 1) {
        // Define values for sorting a display
        $limit = 10;
        // Get listing of all inactive projects
        $projectListing = $projectHandler->getProjects(false, array(), true);

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
        $projectsOnPage = $projectHandler->getProjects(true);
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
          . " WHERE `mechanic_id` = $userId AND status = 'Working'";
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
        LEFT JOIN (SELECT `mechanic_id`, COUNT(`mechanic_id`) AS `count` FROM `" . WORKLIST . "` WHERE (`status` IN ('Working', 'Functional', 'SvnHold', 'Review', 'Completed', 'Done')) GROUP BY `mechanic_id`) AS `mechanics` ON `".USERS."`.`id` = `mechanics`.`mechanic_id`
        LEFT JOIN (SELECT `creator_id`, COUNT(`creator_id`) AS `count` FROM `" . WORKLIST . "` WHERE (`status` IN ('Working', 'Functional', 'SvnHold', 'Review', 'Completed', 'Done')) AND `creator_id` != `mechanic_id` GROUP BY `creator_id`) AS `creators` ON `".USERS."`.`id` = `creators`.`creator_id`
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
        LEFT JOIN (SELECT `mechanic_id`, COUNT(`mechanic_id`) AS `count` FROM `" . WORKLIST . "` WHERE (`status` IN ('Working', 'Functional', 'SvnHold', 'Review', 'Completed', 'Done')) GROUP BY `mechanic_id`) AS `mechanics` ON `".USERS."`.`id` = `mechanics`.`mechanic_id`
        LEFT JOIN (SELECT `creator_id`, COUNT(`creator_id`) AS `count` FROM `" . WORKLIST . "` WHERE (`status` IN ('Working', 'Functional', 'SvnHold', 'Review', 'Completed', 'Done')) AND `creator_id` != `mechanic_id` GROUP BY `creator_id`) AS `creators` ON `".USERS."`.`id` = `creators`.`creator_id`
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
        $userStats = new UserStats($row['id']);

        $user = new User();
        $user->findUserById($row['id']);
        if ($row['budget'] < 1){
            $row['budget'] = 'NONE';
        } else {
            $row['budget'] = '$'.number_format($user->getRemainingFunds(), 0);
        }
        $row['earnings'] = $userStats->getTotalEarnings();
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

function getUserStats() {
    if (isset($_REQUEST['id'])) {
        $userId = (int)$_REQUEST['id'];
    } else {
        die("No id provided");
    }

    $userStats = new UserStats($userId);
    $userStats->setItemsPerPage(9);

    $page = isset($_REQUEST['page']) ? $_REQUEST['page'] : 1;

    switch($_REQUEST['statstype']){

    case 'love':
        echo json_encode($userStats->getTotalLove($page));
        break;

    case 'doneJobs':
        echo json_encode($userStats->getUserItems('Done', $page));
        break;

    case 'activeJobs':
        echo json_encode($userStats->getActiveUserItems(9, $page));
        break;

    case 'reviewJobs':
        echo json_encode($userStats->getUserItems('Review', $page));
        break;

    case 'workingJobs':
        echo json_encode($userStats->getUserItems('Working', $page));
        break;

    case 'completedJobsWithStats':
        echo json_encode($userStats->getCompletedJobsWithStats());
        break;

    case 'completedJobs':
        echo json_encode($userStats->getUserItems('Completed', $page));
        break;

    case 'latest_earnings':
        echo json_encode($userStats->getLatestEarningsJobs(30, $page));
        break;
    case 'following':
        echo json_encode($userStats->getFollowingJobs($page));
        break;

    case 'runnerTotalJobs':
        echo json_encode($userStats->getTotalRunnerItems($page));
        break;

    case 'runnerActiveJobs':
        echo json_encode($userStats->getActiveRunnerItems($page));
        break;

    case 'project_history':
        $projectId = $_REQUEST['project_id'];
        echo json_encode($userStats->getUserItemsForASpecificProject('Done', $projectId));
        break;

    case 'counts':
            setlocale(LC_MONETARY,'en_US');
            $_totalEarnings = $userStats->getTotalEarnings();
            $_bonusPayments = $userStats->getBonusPaymentsTotal();
            $ajaxTotalEarnings =  preg_replace('/\.[0-9]{2,}$/','',money_format('%n',round($_totalEarnings)));
            $ajaxLatestEarnings = preg_replace('/\.[0-9]{2,}$/','',money_format('%n',$userStats->getLatestEarnings(30)));
            $bonus = preg_replace('/\.[0-9]{2,}$/','',money_format('%n',round($_bonusPayments)));
            $_bonusPercent = round((($_bonusPayments + 0.00000001) / ($_totalEarnings + 0.000001)) * 100,2);

        echo json_encode(array(
                            'total_jobs' => $userStats->getTotalJobsCount(),
                            'active_jobs' => $userStats->getActiveJobsCount(),
                            'total_earnings' => $ajaxTotalEarnings,
                            'latest_earnings' => $ajaxLatestEarnings,
                            'bonus_total' => $bonus,
                            'bonus_percent' => $_bonusPercent.'%'
                              ));
        break;
    }
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

function getWorklist() {
    // Test for a string containing 0 characters of anything other than 0-9 and #
    // After a quick trim ofcourse! :)
    // I know regex is usually the bad first stop, but there would be no back tracking in this
    // particular regular expression
    if (preg_match("/^\#?\d+$/", $query = trim($_REQUEST['query']))) {
        // if we reach here, include workitem package, autoloaded (hans)
        $workitem = new WorkItem();
        if ($workitem->idExists($id = ltrim($query, '#'))) {
            $obj = array('redirect',$id);
            die(JSON_encode($obj));
        }
        // if we're not dead continue on!
    }
    $limit = 30;

    $_REQUEST['name'] = '.worklist';
    $filter = new Agency_Worklist_Filter($_REQUEST);

    $is_runner = !empty( $_SESSION['is_runner'] ) ? 1 : 0;
    $userId = isset($_SESSION['userid'])? $_SESSION['userid'] : 0;

    $sfilter = explode('/', $filter->getStatus());
    $ufilter = $filter->getUser();
    $pfilter = !empty($_POST['project_id']) ? $_POST['project_id'] : $filter->getProjectId();
    $cfilter = !empty($_POST['inComment']) ? $_POST['inComment'] : $filter->getInComment();
    $ofilter = $filter->getSort();
    $subofilter = $filter->getSubSort();
    $dfilter = $filter->getDir();
    $page = $filter->getPage();
    $where = '';

    $mobile_filter = isset($_POST['mobile']) ? ($_POST['mobile'] == 'true') : false;

    // Status filter
    if ($sfilter) {
        $where = "WHERE (";
        foreach ($sfilter as $val) {

            $val = mysql_real_escape_string($val);

                if (($val == 'ALL' || $val == '') && !$is_runner) {
                    /**
                     * if current user is not a runner and is filtering by ALL
                     * status it wont fetch workitems in DRAFT status
                     */
                    $where .= "1 AND status != 'Draft' ";
                    if (! empty($ufilter) && $ufilter != 'ALL') {
                        $where .= " AND (IF(status = 'Bidding', IF(`fees`.user_id = $ufilter, 0, 1), 1)) OR ";
                    } else {
                        $where .= " OR ";
                    }
                }
                if (($val == 'ALL' || $val == '') && $is_runner == 1 ){
                    /**
                     * if current user is a runner and is filtering by ALL status
                     * it wont fetch workitems in DRAFT status created by any other
                     * user
                     */
                    $where .= "1 AND status != 'Draft' OR (status = 'Draft' AND creator_id = $userId) OR  ";
                }
                if ($val == 'Draft'){
                    /**
                     * if filtering by DRAFT status will only fetch workitems in
                     * DRAFT status created by current user
                     */
                    $where .= "(status = 'Draft' AND creator_id = $userId) OR  ";
                } else {

                    if ($val == 'Bidding') {
                        /**
                         * runner can see all
                         */
                        if ($is_runner || $mobile_filter) {
                            $where .= "(status = '$val') OR ";
                        } else if ($ufilter != 'ALL') {
                            /**
                             * if bidding, and user filter is not set to all users,
                             * we need to check that logged in user is the bidder
                             * otherwise we reveal other user's tasks they are
                             * bidding on
                             */
                             $where .= "(status = 'Bidding' AND ((`" . WORKLIST . "`.`id` in (SELECT `worklist_id` ";
                             $where .= "FROM `" . BIDS . "` where `bidder_id` = '$userId' AND status = 'Bidding')) OR runner_id = $userId)) OR ";
                         } else {
                             $where .= "status = '$val' OR ";
                         }
                    } else if ($val == 'Code Review') {
                        $where .= "status = 'Review' OR ";
                    } else if ($val == 'Needs-Review') {
                        $where .= "(status = 'Review' AND code_review_started = 0) OR ";
                    } else if ($val != 'ALL') {
                        /**
                         * if filtering by any status different than ALL and (DRAFT, BIDDING) it
                         * won't do any magic
                         */
                        $where .= "status = '$val' OR ";
                    } else {
                        $where .= "status = '$val' OR ";
                    }
                }
        }
        $where .= "0)";
    }

    // User filter
    if (!empty($ufilter) && $ufilter != 'ALL') {
        if (empty($where)) {
            $where = "WHERE (";
        } else {
            $where .= " AND (";
        }

        $severalStatus = "";
        foreach ($sfilter as $val) {
            if ($val == 'ALL') {
                $status_cond = "";
            } else {
                $status_cond = "status='$val' AND";
            }

            if ($val == 'Bidding' && $mobile_filter) {
                $where .= $severalStatus . "( $status_cond 1)";
            } else if ($is_runner && $val == 'Bidding' || $val == 'SuggestedWithBid' && $ufilter == $userId) {
                /**
                 * If current user is a runner and filtering for himself and
                 * (BIDDING or SwB) status then fetch all workitems where he
                 * is mechanic, runner, creator or has bids.
                 */
                $where .= $severalStatus .
                    "( $status_cond (`mechanic_id` = '$ufilter' OR `runner_id` = '$ufilter' OR `creator_id` = '$ufilter'
                    OR `" . WORKLIST . "`.`id` in (SELECT `worklist_id` FROM `" . BIDS . "` where `bidder_id` = '$ufilter')
                    ))";
            } else if ((!$is_runner && $val == 'Bidding' || $val == 'SuggestedWithBid' && $ufilter == $userId)) {
                /**
                 * If current user is a runner and filtering for certain user and
                 * (BIDDING or SwB) status then fetch all workitems where selected
                 * user is runner, creator or has bids.
                 */
                $where .= $severalStatus . "( $status_cond ( `runner_id` = '$ufilter' OR `creator_id` = '$ufilter'
                    OR `" . WORKLIST . "`.`id` in (SELECT `worklist_id` FROM `" . BIDS . "` where `bidder_id` = '$ufilter')
                    ))";
            } else if (($val == 'Bidding' || $val == 'SuggestedWithBid') && $ufilter != $userId) {
                /**
                 * If current user is not a runner and is filtering for certain user
                 * and (BIDDING or SwB) status then fetch all workitems where selected
                 * user is mechanic, runner or creator.
                 */
                $where .= $severalStatus . "( $status_cond ( mechanic_id='$ufilter' OR `runner_id` = '$ufilter' OR creator_id='$ufilter'))";
            } else if ($val == 'Working' || $val =='Review' || $val =='Functional' || $val =='Completed') {
                /**
                 * If current user is filtering for any user (himself or not) and
                 * (WORKING or REVIEW or FUNCTIONAL or COMPLETED) status then fetch
                 * all workitems where selected user is mechanic, creator or runner.
                 */
                $where .= $severalStatus . "( $status_cond ( mechanic_id='$ufilter' OR `creator_id`='$ufilter' OR `runner_id` = '$ufilter'))";
            } else  {
                /**
                 * If current user is filtering for any user (himself or not) and
                 * didn't match above cases (filtering ALL or any other status
                 * different than BIDDING, SwB, WORKING, REVIEW, FUNCTIONAL and
                 * COMPLETED) then fetch all workitems where selected user is
                 * creator, runner, mechanic, has fees or has bids
                 */
                $where .= $severalStatus .
                    "( $status_cond (`creator_id` = '$ufilter' OR `runner_id` = '$ufilter' OR `mechanic_id` = '$ufilter'
                    OR (`" . FEES . "`.user_id = '$ufilter' AND status != 'Bidding')
                    OR `" . WORKLIST . "`.`id` in (SELECT `worklist_id` FROM `" . BIDS . "` where `bidder_id` = '$ufilter' AND status != 'Bidding')
                    ))";
            }
            $severalStatus = " OR ";
        }
        $where .= ')';
    }

    // Project filter
    if (!empty($pfilter) && $pfilter != 'All') {
        if (empty($where)) {
            $where = "WHERE ";
        } else {
            $where .= " AND ";
        }
        $where .= " `".WORKLIST."`.`project_id` = '{$pfilter}' ";
    }

    $query = $filter->getQuery();
    $commentsjoin ="";
    if($query!='' && $query!='Search...') {
        $searchById = false;
         if(is_numeric(trim($query))) {
            $rt = mysql_query("select count(*) from ".WORKLIST." LEFT JOIN `".FEES."` ON `".WORKLIST."`.`id` = `".FEES."`.`worklist_id` $where AND `".WORKLIST."`.`id` = " .$query);
            $row = mysql_fetch_row($rt);
            $rowCount = intval($row[0]);
            if($rowCount >0)
            {
                $searchById = true;
                $where .= " AND `". WORKLIST ."`.`id` = " . $query;
            }
        }
        if(!$searchById) {
            // #11500
            // INPUT: 'one OR    two   three' ;
            // RESULT: 'one two,three' ;
            // split the query into an array using space as delimiter
            // remove empty elements
            // convert spaces into commas
            // change ',OR,' into  space
            $query = preg_replace('/,OR,/', ' ', implode(',', array_filter(explode(' ', $query)))) ;

            $array=explode(",",rawurldecode($query));
            $commentPart = "";

            foreach ($array as $item) {
                $item = mysql_escape_string($item);
                if ($cfilter == 1) {
                    $commentPart = " OR MATCH (`com`.`comment`) AGAINST ('$item')";
                }
                $where .= " AND ( MATCH (summary, `" . WORKLIST . "`.`notes`) AGAINST ('$item')OR MATCH (`" . FEES .
                    "`.notes) AGAINST ('$item') OR MATCH (`ru`.`nickname`) AGAINST ('$item') OR MATCH (`cu`.`nickname`) AGAINST ('$item') OR MATCH (`mu`.`nickname`) AGAINST ('$item') " .
                    $commentPart . ") ";
            }
            if ($cfilter == 1) {
                $commentsjoin = "LEFT OUTER JOIN `" . COMMENTS . "` AS `com` ON `" . WORKLIST . "`.`id` = `com`.`worklist_id`";
            }
        }
    }

    $qcnt  = "SELECT count(DISTINCT `".WORKLIST."`.`id`)";

    //mega-query with total fees and latest bid for the worklist item
    $qsel  = "
        SELECT `".WORKLIST."`.`id`, `summary`,
        (CASE
            WHEN status = 'Review' AND code_review_started = 0 THEN 'Needs Review'
            WHEN status = 'Review' AND code_review_started = 1 AND code_review_completed = 0 THEN 'In Review'
            WHEN status = 'Review' AND code_review_completed = 1 THEN 'Reviewed'
            WHEN status != 'Review' THEN status
        END) `status`,
        `bug_job_id` AS `bug_job_id`,
        `cu`.`nickname` AS `creator_nickname`,
        `ru`.`nickname` AS `runner_nickname`,
        `mu`.`nickname` AS `mechanic_nickname`,
        `proj`.`name` AS `project_name`,
        `worklist`.`project_id` AS `project_id`,
        TIMESTAMPDIFF(SECOND, `created`, NOW()) as `delta`,
        SUM(`" . FEES . "`.amount) AS `total_fees`,
        (SELECT `bid_amount`
            FROM `" . BIDS . "`
            WHERE `withdrawn` = 0
            AND (`bid_expires` > NOW()
            OR `bid_expires` = '0000-00-00 00:00:00')
            AND `worklist_id` = `worklist`.`id`
            ORDER BY bid_created DESC LIMIT 1) `bid_amount`,

        `creator_id`, `runner_id`, `mechanic_id`,
        (SELECT COUNT(`".BIDS."`.`id`) FROM `".BIDS."`
        WHERE `".BIDS."`.`worklist_id` = `".WORKLIST."`.`id` AND (`".BIDS."`.`withdrawn` = 0) AND (NOW() < `".BIDS."`.`bid_expires` OR `bid_expires`='0000-00-00 00:00:00') LIMIT 1) as bid_count,
        TIMESTAMPDIFF(SECOND,NOW(), (SELECT `".BIDS."`.`bid_done` FROM `".BIDS."`
        WHERE `".BIDS."`.`worklist_id` = `".WORKLIST."`.`id` AND `".BIDS."`.`accepted` = 1 LIMIT 1)) as bid_done,
        (SELECT COUNT(`".COMMENTS."`.`id`) FROM `".COMMENTS."`
        WHERE `".COMMENTS."`.`worklist_id` = `".WORKLIST."`.`id`) AS `comments`";

    // Highlight jobs I bid on in a different color
    // 14-JUN-2010 <Tom>
    if ((isset($_SESSION['userid']))) {
        $qsel .= ", (SELECT `".BIDS."`.`id` FROM `".BIDS."` WHERE `".BIDS."`.`worklist_id` = `".WORKLIST."`.`id` AND `".BIDS."`.`bidder_id` = ".$_SESSION['userid']." AND `withdrawn` = 0  AND (`".WORKLIST."`.`status`='Bidding' OR `".WORKLIST."`.`status`='SuggestedWithBid') ORDER BY `".BIDS."`.`id` DESC LIMIT 1) AS `current_bid`";
        $qsel .= ", (SELECT `".BIDS."`.`bid_expires` FROM `".BIDS."` WHERE `".BIDS."`.`id` = `current_bid`) AS `current_expire`";
        $qsel .= ", (SELECT COUNT(`".BIDS."`.`id`) FROM `".BIDS."` WHERE `".BIDS."`.`worklist_id` = `".WORKLIST."`.`id`  AND (`".WORKLIST."`.`status`='Bidding' OR `".WORKLIST."`.`status`='SuggestedWithBid') AND `".BIDS."`.`bidder_id` = ".$_SESSION['userid']." AND `withdrawn` = 0 AND (`bid_expires` > NOW() OR `bid_expires`='0000-00-00 00:00:00')) AS `bid_on`";
    }

    $qbody = "FROM `".WORKLIST."`
              LEFT JOIN `".USERS."` AS cu ON `".WORKLIST."`.`creator_id` = `cu`.`id`
              LEFT JOIN `".USERS."` AS ru ON `".WORKLIST."`.`runner_id` = `ru`.`id`
              LEFT JOIN `" . USERS . "` AS mu ON `" . WORKLIST . "`.`mechanic_id` = `mu`.`id`
              LEFT JOIN `" . FEES . "` ON `" . WORKLIST . "`.`id` = `" . FEES . "`.`worklist_id` AND `" . FEES . "`.`withdrawn` = 0
              INNER JOIN `".PROJECTS."` AS `proj` ON `".WORKLIST."`.`project_id` = `proj`.`project_id` AND `proj`.`internal` = 1
                            AND `proj`.`active` = 1
              $commentsjoin
              $where
              ";

    if ($ofilter == "delta") {
        $idsort = $dfilter == 'DESC' ? 'ASC' : 'DESC';
        $qorder = "GROUP BY `".WORKLIST."`.`id` ORDER BY `".WORKLIST."`.`id` {$idsort} LIMIT "
            . ($page-1)*$limit . ",{$limit}";
    }else{
        $qorder = "GROUP BY `".WORKLIST."`.`id` ORDER BY {$ofilter} {$dfilter},{$subofilter} {$dfilter}  LIMIT "
            . ($page-1)*$limit . ",{$limit}";
    }

    $rtCount = mysql_query("$qcnt $qbody");
    if ($rtCount) {
        $row = mysql_fetch_row($rtCount);
        $items = intval($row[0]);
    } else {
        $items = 0;
    }
    $cPages = ceil($items/$limit);
    $worklist = array(array($items, $page, $cPages));

    // Construct json for history
    $qry="$qsel $qbody $qorder";

    //Don't export mysql errors to the browser by default
    $rtQuery = mysql_query($qry) or error_log('getworklist mysql error: '. mysql_error());
    while ($rtQuery && $row=mysql_fetch_assoc($rtQuery)) {
        $worklist[] = array(
             0 => $row['id'],
             1 => $row['summary'],
             2 => $row['status'],
             3 => $row['creator_nickname'],
             4 => $row['runner_nickname'],
             5 => $row['mechanic_nickname'],
             6 => $row['delta'],
             7 => $row['total_fees'],
             8 => ($is_runner == 1) ? $row['bid_amount'] : 0,
             9 => $row['creator_id'],
            10 => $row['bid_count'],
            11 => ($row['status'] == 'Done') ? date("m/d/Y",time()+$row['bid_done']):$row['bid_done'],
            12 => $row['comments'],
            13 => $row['runner_id'],
            14 => $row['mechanic_id'],
            15 => (!empty($row['bid_on']) ? $row['bid_on'] : 0),
            16 => $row['project_id'],
            17 => $row['project_name'],
            18 => $row['bug_job_id'],
            19 => (!empty($row['current_expire']) && strtotime($row['current_expire'])<time() && trim($row['current_expire'])!='0000-00-00 00:00:00') ? 'expired' : 0,
            20 => (!empty($row['current_bid']) ? $row['current_bid'] : 0),
        );
    }

    $json = json_encode($worklist);
    echo $json;
}

function payBonus() {
    $error = false;
    $message = '';

    // user must be logged in
    if (! isset($_SESSION['userid'])) {
        $error = true;
        $message = 'error: unauthorized';
    } else {
        $userId = getSessionUserId();

        $is_runner = isset($_SESSION['is_runner']) ? $_SESSION['is_runner'] : 0;
        $is_payer = isset($_SESSION['is_payer']) ? $_SESSION['is_payer'] : 0;

        if( $userId > 0 && ( $is_runner || $is_payer ) ) {
            $giver = new User();
            $giver->findUserById($userId);
            $budget = $giver->getBudget();

            // validate required fields
            if (empty($_REQUEST['budget-source-combo-bonus']) || empty($_REQUEST['receiver_id']) || empty($_REQUEST['amount'])) {
                $error = true;
                $message = 'error: args';
            }

            $budget_source_combo = (int) $_REQUEST['budget-source-combo-bonus'];
            $budgetSource = new Budget();
            if (!$budgetSource->loadById($budget_source_combo) ) {
                $error = true;
                $message = 'Invalid budget!';
            }

            $amount = floatval($_REQUEST['amount']);
            $stringAmount = number_format($amount, 2);

            $receiver_id = intval($_REQUEST['receiver_id']);
            $reason = $_REQUEST['reason'];
        } else {
            $error = true;
            $message = 'error: session';
        }
    }
    if (! $error) {
        $remainingFunds = $budgetSource->getRemainingFunds();
        if ($amount <= $budget && $amount <= $remainingFunds) {
            if (payBonusToUser($receiver_id, $amount, $reason, $budget_source_combo)) {
                // deduct amount from balance
                $giver->updateBudget(- $amount, $budget_source_combo);

                $receiver = getUserById($receiver_id);
                $receiver_email = $receiver->username;

                sendTemplateEmail( $receiver_email, 'bonus_received', array('amount' => $stringAmount, 'reason' => $reason));
                if (sendJournalNotification('@' . $receiver->nickname . ' received a bonus of $' . $stringAmount) == 'ok') {
                } else {
                    // journal notification failed
                }
                $error = false;
                $message =  'Paid ' . $receiver->nickname . ' a bonus of $' . $stringAmount;
            } else {
                $error = true;
                $message = 'DB error';
            }
        } else {
            $error = true;
            $message = 'You do not have enough budget available to pay this bonus.';
        }
    }
    $json = json_encode(array('success' => !$error, 'message' => $message));
    echo $json;
}

function payCheck() {
    $is_payer = !empty($_SESSION['is_payer']) ? true : false;
    // Check if we have a payer
    if (!$is_payer) {
        exit('{"success": false, "message": "Nothing to see here. Move along!" }');
    }

    // Get clean data
    if (isset($_REQUEST['paid_check']) && ($_REQUEST['paid_check'] == '1')) {
        $paid_check = 1;
    } else {
        $paid_check = 0;
    }
    $paid_notes = $_REQUEST['paid_notes'];
    if (isset($paid_notes) && !empty($paid_notes)) {
        $paid_notes = mysql_real_escape_string($_REQUEST['paid_notes']);
    } else {
        die('{"success": false, "message": "You must write a note!" }');
    }

    if (isset($_REQUEST['itemid']) && !empty($_REQUEST['itemid'])) {
        $fee_id = mysql_real_escape_string($_REQUEST['itemid']);
    } else {
        die('{"success": false, "message": "No fee set!" }');
    }

    // What user is paying
    $user = $_SESSION['userid'];

    // get the fund_id from the project that the fee/workitem belongs to
    $fund_query = "
        SELECT p.fund_id AS fund_id
        FROM " . FEES . " f
        LEFT JOIN " . WORKLIST . " w ON  f.worklist_id = w.id
        LEFT JOIN " . PROJECTS . " p ON w.project_id = p.project_id
        WHERE f.id = {$fee_id}";

    if ($fund_result = mysql_query($fund_query)) {
        $fund_row = mysql_fetch_array($fund_result);
        $fund_id = $fund_row['fund_id'];
    } else {
        $fund_id = 0;
    }

    // Exit of this script
    if (Fee::markPaidById($fee_id, $user, $paid_notes, $paid_check, false, $fund_id)) {
        /* Only send the email when marking as paid. */
        if ($paid_check) {
            $fees_query  =  'SELECT  `amount`,`user_id`,`worklist_id`,`desc` FROM '.FEES.'  WHERE `id` =  '.$fee_id;
            $result1 = mysql_query($fees_query);
            $fee_pay= mysql_fetch_array($result1);
            $total_fee_pay = $fee_pay['amount'];

            $summary =  getWorkItemSummary($fee_pay['worklist_id']);

            $mail = 'SELECT `username` FROM '.USERS.' WHERE `id` = '.$fee_pay['user_id'].'';
            $userData = mysql_fetch_array(mysql_query($mail));

            $subject = "Worklist.net paid you " . $total_fee_pay ." for ". $summary;
            $body  = "Your Fee was marked paid.<br/>";
            $body .= "Job <a href='./" . $fee_pay['worklist_id'] . "' />#" . $fee_pay['worklist_id'] .
              "</a>: <a href='./" . $fee_pay['worklist_id'] . "' />" . SERVER_URL . $fee_pay['worklist_id'] . "</a><br/>";
            $body .= "Fee Description : ".nl2br($fee_pay['desc'])."<br/>";
            $body .= "Paid Notes : ".nl2br($_REQUEST['paid_notes'])."<br/><br/>";
            $body .= "Contact the job Designer with any questions<br/><br/>Worklist.net<br/>";

            if(!send_email($userData['username'], $subject, $body)) { error_log("paycheck: send_email failed"); }
        }
        die('{"success": true, "message": "Payment has been saved!" }');
    } else {
        die('{"success": false, "message": "Hmm, that was unexpected... Payment Failed!" }');
    }
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

function userReview() {
    $userReview = new UserReview();
    $userReview->validateRequest(array('method'));

    $method = $_REQUEST['method'];
    $userReview->$method();
}

function workitemSandbox() {
    $workitemSandbox = new WorkitemSandbox();
    $workitemSandbox->validateRequest(array('method'));
    $method = $_REQUEST['method'];
    $workitemSandbox->$method();
}

function testFlight() {
    if (! $_REQUEST['project_id']) {
        echo json_encode(array(
            "error" => "There is no project ID."
        ));
        return;
    }

    $userId = getSessionUserId();

    $project = new Project();
    $project->loadById( mysql_real_escape_string($_REQUEST['project_id']) );
    $testFlightTeamToken = $project->getTestFlightTeamToken();

    if ($project->getTestFlightEnabled()) {
        if ($testFlightTeamToken == "") {
            echo json_encode(array(
                "error" => "TestFlight Team Token is empty."
            ));
        } else if ($project->isOwner($userId)) {
            $ipaFile = mysql_real_escape_string($_REQUEST['ipa_file']);
            $svnUrl = $config['websvn']['baseUrl'] . "/svn/repos/" . $project->getRepository();

            if ($ipaFile == "") {
                $ipaFiles = array();
                $svnMessage = array();
                exec("svn list --config-dir /tmp -R " . $svnUrl ." | grep .ipa", $ipaFiles);
                exec("svn log --config-dir /tmp -r HEAD " . $svnUrl, $svnMessage);

                if (count($ipaFiles) > 0) {
                    echo json_encode(array(
                        "ipaFiles" => $ipaFiles,
                        "message" => $svnMessage[3]
                    ));
                } else {
                    echo json_encode(array(
                        "error" => "No .ipa files in repository."
                    ));
                }
            } else {
                $fileName = "/tmp/" . md5( time().rand() );
                exec("svn export --config-dir /tmp " . $svnUrl . "/" . $ipaFile . " " . $fileName);

                $ch = curl_init();
                curl_setopt($ch, CURLOPT_HEADER, 0);
                curl_setopt($ch, CURLOPT_VERBOSE, 0);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/4.0 (compatible;)");
                curl_setopt($ch, CURLOPT_URL, "http://testflightapp.com/api/builds.json");
                curl_setopt($ch, CURLOPT_POST, true);
                $post = array(
                    "api_token" => TESTFLIGHT_API_TOKEN,
                    "team_token" => $testFlightTeamToken,
                    "file" => "@" . $fileName,
                    "notes" => mysql_real_escape_string($_REQUEST['message']),
                    "notify" => mysql_real_escape_string($_REQUEST['notify'])
                );
                curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
                $out = curl_exec($ch);

                if (json_decode($out) != $out) {
                    echo $out;
                } else {
                    echo json_encode(array(
                        "error" => $out
                    ));
                }
            }
        } else {
            echo json_encode(array(
                "error" => "You don't have the permissions."
            ));
        }
    }
}

function updateBudget() {
    $error = false;
    $message = '';

    if (!isset($_SESSION['userid'])) {
        echo json_encode(array('success' => false, 'message' => 'error: unauthorized'));
        return;
    }

    if (!isset($_REQUEST['budget_seed']) || !isset($_REQUEST['budget_source'])
        || !isset($_REQUEST['budget_source_combo']) || !isset($_REQUEST['budget_note'])
        || !isset($_REQUEST['add_funds_to'])) {
        echo json_encode(array('success' => false, 'message' => 'error: args'));
        return;
    }
    $budget_seed = (int) $_REQUEST['budget_seed'];
    $budget_source = mysql_real_escape_string($_REQUEST['budget_source']);
    $budget_source_combo = (int) $_REQUEST['budget_source_combo'];
    $add_funds_to = (int) $_REQUEST['add_funds_to'];
    $budget_note = mysql_real_escape_string($_REQUEST['budget_note']);
    if ($budget_seed == 1) {
        $budget_source_combo = 0;
        $source = $budget_source;
        if (empty($source)) {
            echo json_encode(array('success' => false, 'message' => 'Source field is mandatory'));
            return;
        }
    } else {
        $source = "Amount from budget id: " . $budget_source_combo;
        if ($budget_source_combo == 0) {
            echo json_encode(array('success' => false, 'message' => 'Source field is mandatory'));
            return;
        }
    }

    $receiver_id = intval($_REQUEST['receiver_id']);
    $amount = isset($_REQUEST['amount']) ? floatval($_REQUEST['amount']) : 0;
    $reason = mysql_real_escape_string($_REQUEST['reason']);
    if (empty($receiver_id)) {
        echo json_encode(array('success' => false, 'message' => 'Receiver field is mandatory'));
        return;
    }
    if (empty($amount)) {
        echo json_encode(array('success' => false, 'message' => 'Amount field is mandatory'));
        return;
    }
    if ($add_funds_to == 0 && empty($reason)) {
        echo json_encode(array('success' => false, 'message' => 'For field is mandatory'));
        return;
    }

    $giver = new User();
    $receiver = new User();
    if (!$giver->findUserById($_SESSION['userid']) || !$receiver->findUserById($receiver_id)) {
        echo json_encode(array('success' => false, 'message' => 'error: invalid user'));
        return;
    }

    $stringAmount = number_format($amount, 2);

    if ($budget_seed != 1) {
        $budget = new Budget();
        if (!$budget->loadById($budget_source_combo) ) {
            echo json_encode(array('success' => false, 'message' => 'Invalid budget!'));
            return;
        }
        // Check if user is owner of source budget
        if ($budget->receiver_id != $_SESSION['userid']) {
            echo json_encode(array('success' => false, 'message' => 'You\'re not the owner of this budget!'));
            error_log('Possible Hacking attempt: User ' . $_SESSION['userid'] . ' attempted to budget ' . $amount . ' to ' . $receiver_id . ' from budget ' . $budget->id);
            return;
        }
        $remainingFunds = $budget->getRemainingFunds();
    }

    $add_funds_to_budget = false;
    if ($add_funds_to != 0) {
        $add_funds_to_budget = new Budget();
        if (!$add_funds_to_budget->loadById($add_funds_to) ) {
            echo json_encode(array('success' => false, 'message' => 'Invalid budget (add funds parameter)!'));
            return;
        }
        $grantor = new User();
        if (!$grantor->findUserById($add_funds_to_budget->giver_id)) {
            echo json_encode(array('success' => false, 'message' => 'error: invalid grantor'));
            return;
        }
    }


    if ($budget_seed == 1 ||
        ($amount <=  $budget->getRemainingFunds())) {
        $receiver->setBudget($receiver->getBudget() + $amount)->save();
        if ($add_funds_to == 0) {
            $query = "INSERT INTO `" . BUDGETS .
                    "` (`giver_id`, `receiver_id`, `amount`, `remaining`, `reason`, `transfer_date`, `seed`, `source_data`,  `notes`, `active`) VALUES ('" .
                    $_SESSION['userid'] .
                    "', '$receiver_id', '$amount', '$amount', '$reason', NOW(), '$budget_seed', '$source', '$budget_note', 1)";
            if (!mysql_unbuffered_query($query)){
                $json = json_encode(array('success' => false, 'message' => 'Error in query.'));
                echo $json;
                return;
            }
            $add_funds_to =  mysql_insert_id();
        } else {
            $query = "UPDATE `" . BUDGETS .
                    "` SET `amount`= `amount` + $amount, `remaining` = `remaining` + $amount
                    WHERE id = $add_funds_to";
            if (!mysql_unbuffered_query($query)){
                $json = json_encode(array('success' => false, 'message' => 'Error in query.'));
                echo $json;
                return;
            }
        }
        $query = "INSERT INTO `" . BUDGET_SOURCE .
                "` (`giver_id`, `budget_id`, `source_budget_id`, `amount_granted`, `original_amount`, `transfer_date`,  `source_data`) VALUES ('" .
                $_SESSION['userid'] .
                "', '$add_funds_to', '$budget_source_combo', '$amount', '0', NOW(), '$source')";
        if (!mysql_unbuffered_query($query)){
            $json = json_encode(array('success' => false, 'message' => 'Error in query.'));
            echo $json;
            return;
        }
        if ($budget_seed != 1) {
            $giver->updateBudget(- $amount, $budget_source_combo);
            $budget = new Budget();
            $budget->loadById($add_funds_to);
            $reason = $budget->reason;
        }
        $query2 = " UPDATE `" . USERS . "` SET `is_runner` = 1 WHERE `id` = $receiver_id AND `is_runner` = 0 ";
        if (mysql_unbuffered_query($query2)) {
            $journal_message = '@' . $giver->getNickname() . ' budgeted @' . $receiver->getNickname() . " $" . number_format($amount, 2) .
            " for " . $reason . ".";
            sendJournalNotification($journal_message);
            if ($add_funds_to_budget == false) {
                Notification::notifyBudget($amount, $reason, $giver, $receiver);
            } else {
                Notification::notifyBudgetAddFunds($amount, $giver, $receiver, $grantor, $add_funds_to_budget);
            }
            if ($budget_seed == 1) {
                Notification::notifySeedBudget($amount, $reason, $source, $giver, $receiver);
            }
            $receiver = getUserById($receiver_id);
            $message =  'You gave ' . '$' . $stringAmount . ' budget to ' . $receiver->nickname;
        } else {
            $error = true;
            $message = 'Error in query.';
        }
    } else {
        $error = true;
        $message = 'You do not have enough budget available to give this amount (total: $' . $giver->getBudget() . ", from budget: " . $budget->id . ")";
    }

    $json = json_encode(array('success' => !$error, 'message' => $message));
    echo $json;
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

function budgetInfo() {
    $budgetInfo = new BudgetInfo();
    $budgetInfo->validateRequest(array('method'));

    $method = $_REQUEST['method'];
    $budgetInfo->$method();
}

function budgetHistory() {
    $id = (int) $_REQUEST['id'];

    $fromUserid = "n";
    if(isset($_REQUEST['fromUserid'])) {
        $fromUserid = $_REQUEST['fromUserid'];
    }

    if(!isset($_REQUEST['page'])) {
        $_REQUEST['page'] = 1;
    }

    $userId = getSessionUserId();
    $user = new User();
    if ($userId > 0) {
        $user->findUserById($userId);
    } else {
        $user->setId(0);
    }
    $inDiv = $_REQUEST['inDiv'];
    $page = (int) $_REQUEST['page'];
    $limit = 8;
    $init = ($page -1) * $limit;
    $fromUseridFilter = "";
    if ($fromUserid == "y") {
        $fromUseridFilter = " AND b.giver_id = " . $userId;
    }

    // Query to get User's Budget entries
    $query =  ' SELECT DATE_FORMAT(b.transfer_date, "%Y-%m-%d") AS date, b.amount,'
            . ' IF( b.active=1, b.remaining, 0.00) AS remaining, b.reason, b.active, b.notes, b.seed, b.id AS budget_id, '
            . '(SELECT COUNT(s.giver_id) FROM ' . BUDGET_SOURCE .
                ' AS s WHERE s.budget_id = b.id AND s.giver_id = ' . $userId . ') AS userid_count, '
            . '(SELECT COUNT(DISTINCT giver_id) FROM ' . BUDGET_SOURCE . ' AS s WHERE s.budget_id = b.id ) AS givers_count, '
            . '(SELECT u.nickname FROM ' . USERS . ' AS u, ' . BUDGET_SOURCE
            . ' AS s WHERE u.id = s.giver_id AND s.budget_id = b.id LIMIT 0, 1) AS nickname '
            . ' FROM ' . BUDGETS . ' AS b '
            . ' WHERE b.receiver_id = ' . $id
            . $fromUseridFilter
            . ' ORDER BY b.id DESC '
            . ' LIMIT ' . $init . ',' . $limit;

    // Get total # of entries
    $queryTotal =  ' SELECT COUNT(*) AS total'
            . ' FROM ' . BUDGETS . ' AS b '
            . ' WHERE b.receiver_id = ' . $id
            . $fromUseridFilter;

    if($result = mysql_query($queryTotal)) {
        $count = mysql_fetch_assoc($result);
        $totalPages = floor(( (int) $count['total'] - 1) / $limit) + 1;
    } else {
        $total = 1;
    }
    if ( $count['total'] == 0){
        echo "This user hasn't been assigned any budget yet.";
        exit(0);
    }
    ?>
    <table class="table table-striped">
      <thead>
        <tr>
          <th>Created</th>
          <th>ID #</th>
          <th>Grantor</th>
          <th>Amount</th>
          <?php if (!empty($id) && $userId == $id) { ?>
          <th>Remaining</th>
          <?php } ?>
          <th>For</th>
          <th>Active</th>
        </tr>
      </thead>

    <?php
    $result = mysql_query($query);
    $budgetList = array();
    if ($result) {
        $i = 1;
        while ($row = mysql_fetch_assoc($result)) {
            $notes = "";
            if ($userId == $id ||
                (array_key_exists('is_payer', $_SESSION)  && $_SESSION['is_payer']) ||
                $row['userid_count'] > 0) {
                if (!empty($row['notes'])) {
                    $notes = " title='" . $row['budget_id'] . " - Notes: " . $row['notes'];
                } else {
                    $notes = " title='" . $row['budget_id'] . " - Notes: None";
                }
                $notes .=  "' ";
                $classBudgetRow = " budgetRow";
            } else {
                $classBudgetRow = "";
            }

    ?>

        <tr class="<?php echo $classBudgetRow; ?>" data-budgetid="<?php echo $row['budget_id']; ?>"
            <?php echo (!empty($notes)) ? $notes : $row['budget_id'] ; ?>
        >
            <td><span><?php echo $row['date']; ?></span></td>
            <td><span><?php echo $row['budget_id']; ?></span></td>
            <td><span><?php echo ($row['givers_count'] == 1 ) ? $row['nickname'] : "Various"; ?></span></td>
            <td><span><?php echo $row['amount']; ?></span></td>
            <?php if (!empty($id) && $userId == $id) { ?>
            <td<?php if ($row['remaining'] < 0) echo ' class="red"'; ?>><span>
                <?php echo $row['remaining']; ?></span></td>
            <?php } ?>
            <td><span><?php echo $row['reason']; ?></span></td>
            <td><span><?php echo ($row['active'] == 1) ? "open" : "closed"; ?></span></td>
        </tr>

    <?php
        $i++;
        }
    }
    ?>

    </table>
    <div><ul class="pagination"><?php
    for ($i = 1; $i <= $totalPages; $i++) {
        if ($i == $page) {
            echo '<li><a href="#">' . $i . '</a></li>';
        } else {
            echo '<li><a href="javascript:Budget.budgetHistory({inDiv: \'' . $inDiv . '\', id: ' . $id . ', page: ' . $i . ', fromUserid: \'' . $fromUserid . '\'});">' . $i . '</a></li>';
        }
    }
    echo '</ul></div>';
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
