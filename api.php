<?php

require_once ("config.php");
require_once ("models/DataObject.php");
require_once ("models/Review.php");
require_once ("models/Users_Favorite.php");
require_once ("models/Budget.php");

Core::bootstrap(false);
Session::check();

if (!defined("ALL_ASSETS"))      define("ALL_ASSETS", "all_assets");

// TODO: add API keys to these function calls
// getSystemDrawerJobs

if(validateAction()) {
    if(!empty($_REQUEST['action'])){
        mysql_connect (DB_SERVER, DB_USER, DB_PASSWORD);
        mysql_select_db (DB_NAME);
        switch($_REQUEST['action']){
            case 'updateuser':
                Utils::validateAPIKey();
                updateuser();
                break;
            case 'pushVerifyUser':
                Utils::validateAPIKey();
                pushVerifyUser();
                break;
            case 'login':
                Utils::validateAPIKey();
                loginUserIntoSession();
                break;
            case 'updateProjectList':
                Utils::validateAPIKey();
                updateProjectList();
                break;
            case 'getSystemDrawerJobs':
                getSystemDrawerJobs();
                break;
            case 'bidNotification':
                Utils::validateAPIKey();
                sendBidNotification();
                break;
            case 'processW2Masspay':
                Utils::validateAPIKey();
                processW2Masspay();
                break;
            case 'doScanAssets':
                Utils::validateAPIKey();
                doScanAssets();
                break;
            case 'sendTestNotifications':
                Utils::validateAPIKey();
                sendTestNotifications();
                break;
            case 'autoPass':
                Utils::validateAPIKey();
                autoPassSuggestedJobs();
                break;
            case 'processPendingReviewsNotifications':
                Utils::validateAPIKey();
                processPendingReviewsNotifications();
                break;
            case 'getFavoriteUsers':
                getFavoriteUsers();
                break;
            case 'deployErrorNotification':
                Utils::validateAPIKey();
                deployErrorNotification();
                break;
            case 'sendNotifications':
                Utils::validateAPIKey();
                sendNotifications();
                break;
            case 'checkInactiveProjects':
                Utils::validateAPIKey();
                checkInactiveProjects();
                break;
            case 'checkRemovableProjects':
                Utils::validateAPIKey();
                checkRemovableProjects();
                break;
            case 'getBonusHistory':
                getBonusHistory();
                break;
            case 'getMultipleBidList':
                getMultipleBidList();
                break;
            case 'getProjects':
                $userId = isset($_SESSION['userid']) ? $_SESSION['userid'] : 0;
                $currentUser = User::find($userId);
                getProjects(!$currentUser->isInternal());
                break;
            case 'getUserList':
                getUserList();
                break;
            case 'getUsersList':
                getUsersList();
                break;
            case 'payBonus':
                payBonus();
                break;
            case 'pingTask':
                pingTask();
                break;
            case 'userReview':
                userReview();
                break;
            case 'visitQuery':
                echo json_encode(VisitQueryTools::visitQuery((int) $_GET['jobid']));
                break;
            case 'wdFee':
                wdFee();
                break;
            case 'timeline':
                timeline();
                break;
            case 'newUserNotification':
                Utils::validateAPIKey();
                sendNewUserNotification();
                break;
            case 'sendJobReport':
                Utils::validateAPIKey();
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

            $journal_message =  "\\\\#" . $workitem->getId() . " updated by @Otto. Status set to " . $status;
            Utils::systemNotification(stripslashes($journal_message));
        } else {
            error_log("Otto failed to update the status of workitem #" . $workitem->getId() . " to " . $status);
        }
        sleep($delay);
    }
    mysql_free_result($result);
    mysql_close($con);
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
                Utils::sendReviewNotification($tReview->reviewee_id, 'update',
                    $tReview->getReviews($tReview->reviewee_id, $tReview->reviewer_id, ' AND r.reviewer_id=' . $tReview->reviewer_id));
            } else {
                Utils::sendReviewNotification($tReview->reviewee_id, 'new',
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

function getFavoriteUsers() {
    if (!$userid = (isset($_SESSION['userid']) ? $_SESSION['userid'] : 0)) {
    echo json_encode(array('favorite_users' => array()));
    return;
    }
    $users_favorite = new Users_Favorite();
    $data = array('favorite_users' => $users_favorite->getFavoriteUsers($userid));
    echo json_encode($data);
}

function deployErrorNotification() {
    $work_item_id = isset($_REQUEST['workitem']) ? $_REQUEST['workitem'] : 0;
    $error_msg = isset($_REQUEST['error']) ? base64_decode($_REQUEST['error']) : '';
    $commit_rev = isset($_REQUEST['rev']) ? $_REQUEST['rev'] : '';
    $notify = new Notification();
    $notify->deployErrorNotification($work_item_id, $error_msg, $commit_rev);
    exit(json_encode(array('success' => true)));
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
        if (! Utils::sendTemplateEmail($row['contact_info'], 'project-inactive', $data)) {
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
        if (!Utils::send_email(OPS_EMAIL, $subject, $body, null, $headers )) {
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
        if (Utils::sendTemplateEmail($row['contact_info'], 'project-removed', $data)) {
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
        if (!Utils::send_email(OPS_EMAIL, $subject, $body, null, $headers )) {
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
        $bid['expires_text'] = Utils::relativeTime($bid['expires'] , false, false, false, false);
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
    }

    // Prepare data for printing in projects
    $json = json_encode($projectsOnPage);
    echo $json;
}

function getUserList() {
    $limit = 30;
    $page = isset($_REQUEST["page"])?intval($_REQUEST["page"]) : 1;
    $letter = isset($_REQUEST["letter"]) ? mysql_real_escape_string(trim($_REQUEST["letter"])) : "";
    $order = !empty($_REQUEST["order"]) ? mysql_real_escape_string(trim($_REQUEST["order"])) : "jobs_count";
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
        IFNULL(`creators`.`count`,0) + IFNULL(`mechanics`.`count`,0) AS `jobs_count`
        FROM `".USERS."`
        LEFT JOIN (SELECT `mechanic_id`, COUNT(`mechanic_id`) AS `count` FROM `" . WORKLIST . "` WHERE (`status` IN ('In Progress', 'QA Ready', 'Review', 'Merged', 'Done')) GROUP BY `mechanic_id`) AS `mechanics` ON `".USERS."`.`id` = `mechanics`.`mechanic_id`
        LEFT JOIN (SELECT `creator_id`, COUNT(`creator_id`) AS `count` FROM `" . WORKLIST . "` WHERE (`status` IN ('In Progress', 'QA Ready', 'Review', 'Merged', 'Done')) AND `creator_id` != `mechanic_id` GROUP BY `creator_id`) AS `creators` ON `".USERS."`.`id` = `creators`.`creator_id`
        LEFT JOIN (SELECT `user_id`, SUM(amount) AS `sum` FROM `".FEES."` WHERE ($sfilter AND `withdrawn`=0 AND `paid` = 1) AND `expense`=1 GROUP BY `user_id`) AS `expenses_billed` ON `".USERS."`.`id` = `expenses_billed`.`user_id`
        WHERE `nickname` REGEXP '^$letter' AND `is_active` = 1 $myfavorite_cond ORDER BY `$order` $order_dir LIMIT " . ($page-1)*$limit . ",$limit";
    }    else if( $active == 'TRUE' )    {
        $query = "
        SELECT `id`, `nickname`,`added` AS `joined`, `budget`,
        IFNULL(`creators`.`count`,0) + IFNULL(`mechanics`.`count`,0) AS `jobs_count`
        FROM `".USERS."`
        LEFT JOIN (SELECT `user_id`,MAX(`date`) AS `date` FROM `".FEES."` WHERE `paid` = 1 AND `amount` != 0 AND `withdrawn` = 0 AND `expense` = 0 GROUP BY `user_id`) AS `dates` ON `".USERS."`.id = `dates`.user_id
        LEFT JOIN (SELECT `mechanic_id`, COUNT(`mechanic_id`) AS `count` FROM `" . WORKLIST . "` WHERE (`status` IN ('In Progress', 'QA Ready', 'Review', 'Merged', 'Done')) GROUP BY `mechanic_id`) AS `mechanics` ON `".USERS."`.`id` = `mechanics`.`mechanic_id`
        LEFT JOIN (SELECT `creator_id`, COUNT(`creator_id`) AS `count` FROM `" . WORKLIST . "` WHERE (`status` IN ('In Progress', 'QA Ready', 'Review', 'Merged', 'Done')) AND `creator_id` != `mechanic_id` GROUP BY `creator_id`) AS `creators` ON `".USERS."`.`id` = `creators`.`creator_id`
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
        $row['joined'] = Utils::formatableRelativeTime($diffseconds,2);
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

function pingTask() {
    Utils::checkLogin();

    // Get sender Nickname
    $id = Session::uid();
    $user = User::find($id);
    $nickname = $user->getNickname();
    $email = $user->getUsername();
    $msg = $_REQUEST['msg'];
    $send_cc = isset($_REQUEST['cc']) ? (int) $_REQUEST['cc'] : false;
    // Get Receiver Info
    $receiver = User::find(intval($_REQUEST['userid']));
    $receiver_nick = $receiver->getNickname();
    $receiver_email = $receiver->getUsername();

    $mail_subject = $nickname." sent you a message on Worklist";
    $mail_msg = "<p><a href='" . WORKLIST_URL .'user/' . $id . "'>" . $nickname . "</a>";
    $mail_msg .=" sent you a message: ";
    $mail_msg .= "</p><p>----------<br/>". nl2br($msg)."<br />----------</p><p>You can reply via email to ".$email."</p>";

    $headers = array(
        'X-tag' => 'ping',
        'From' => NOREPLY_SENDER,
        'Reply-To' => '"' . $nickname . '" <' . $email . '>'
    );
    if ($send_cc) {
        $headers['Cc'] = '"' . $nickname . '" <' . $email . '>';
    }
    if (!Utils::send_email($receiver_email, $mail_subject, $mail_msg, '', $headers)) {
        error_log("pingtask.php:!id: Utils::send_email failed");
    }
    echo json_encode(array());
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

    if (! Utils::sendTemplateEmail($recipient, 'user-signups', $mergeData)) {
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

    if (! Utils::sendTemplateEmail($emails, 'jobs-weekly-report', $email_content, 'contact@highfidelity.io')) {
        error_log('sendJobReport cron: Emails could not be sent.');
    }
}
