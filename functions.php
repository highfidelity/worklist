<?php

function worklist_autoloader($class) {
    if ($class != 'View' && substr($class, -4) == 'View') {
        $fileName = substr($class, 0, -4);
        $file = VIEWS_DIR . DIRECTORY_SEPARATOR . $fileName . '.php';
    } elseif ($class != 'Layout' && substr($class, -6) == 'Layout') {
        $fileName = substr($class, 0, -6);
        $file = VIEWS_DIR . DIRECTORY_SEPARATOR . 'layout' . DIRECTORY_SEPARATOR . $fileName . '.php';
    } else if ($class != 'Controller' && substr($class, -10) == 'Controller') {
        $fileName = substr($class, 0, -10);
        $file = CONTROLLERS_DIR . DIRECTORY_SEPARATOR . $fileName . '.php';
    } else if ($class != 'Model' && substr($class, -5) == 'Model') {
        $fileName = substr($class, 0, -5);
        $file = MODELS_DIR . DIRECTORY_SEPARATOR . $fileName . '.php';
    } else {
        $file = realpath(dirname(__FILE__) . DIRECTORY_SEPARATOR . 'classes') .
            DIRECTORY_SEPARATOR . "$class.class.php";
    }
    if (file_exists($file)) {
        require_once($file);
    }
}

spl_autoload_register('worklist_autoloader');

/**
 * Timezones functions
 */
function getTimeZoneDateTime($GMT) {
    $timezones = array(
        '-1200'=>'Pacific/Kwajalein',
        '-1100'=>'Pacific/Samoa',
        '-1000'=>'Pacific/Honolulu',
        '-0900'=>'America/Juneau',
        '-0800'=>'America/Los_Angeles',
        '-0700'=>'America/Denver',
        '-0600'=>'America/Mexico_City',
        '-0500'=>'America/New_York',
        '-0400'=>'America/Caracas',
        '-0330'=>'America/St_Johns',
        '-0300'=>'America/Argentina/Buenos_Aires',
        '-0200'=>'Atlantic/Azores',// no cities here so just picking an hour ahead
        '-0100'=>'Atlantic/Azores',
        '+0000'=>'Europe/London',
        '+0100'=>'Europe/Paris',
        '+0200'=>'Europe/Helsinki',
        '+0300'=>'Europe/Moscow',
        '+0330'=>'Asia/Tehran',
        '+0400'=>'Asia/Baku',
        '+0430'=>'Asia/Kabul',
        '+0500'=>'Asia/Karachi',
        '+0530'=>'Asia/Calcutta',
        '+0600'=>'Asia/Colombo',
        '+0700'=>'Asia/Bangkok',
        '+0800'=>'Asia/Singapore',
        '+0900'=>'Asia/Tokyo',
        '+0930'=>'Australia/Darwin',
        '+1000'=>'Pacific/Guam',
        '+1100'=>'Asia/Magadan',
        '+1200'=>'Asia/Kamchatka'
    );
    if(isset($timezones[$GMT])){
        return $timezones[$GMT];
    } else {
        return date_default_timezone_get();
    }
}

// @param $short == 1 -> return date format as 4:31 AM
// else return date format as 04:31:22 AM
function convertTimeZoneToLocalTime($timeoffset, $short) {
    $DefZone = getTimeZoneDateTime($timeoffset);
    date_default_timezone_set($DefZone);
    if (strlen($timeoffset) == 5) {
        $formatedTime = str_split($timeoffset);
        $Symbol = $formatedTime[0];
        $First = $formatedTime[1];
        $Second = $formatedTime[2];
        $Third = $formatedTime[3];
        $Fourth = $formatedTime[4];
        if ($Third=="3") {
            $Third =5;
        }
        $timezone_local = $Symbol.$First.$Second.".".$Third.$Fourth;
    } else {
        $timezone_local = 0;
    }

    $time = time();
    $timezone_offset = date("Z");
    $timezone_add = round($timezone_local*60*60);
    $ar = localtime($time,true);
    if ($ar['tm_isdst']) { $time += 3600; }
    $time = round($time-$timezone_offset+$timezone_add);
    if (isset($short) && $short == 1)
        $LocalTime = date("g:i A", $time);
    else
        $LocalTime = date("h:i:s A", $time);

    return $LocalTime;
}

function checkReferer() {
    $len = strlen(SERVER_NAME);
    if (   empty($_SERVER['HTTP_REFERER'])
    || (  substr($_SERVER['HTTP_REFERER'], 0, $len + 8) != 'https://'.SERVER_NAME )) {
        return false;
    } else {
        return true;
    }
}

function enforceRateLimit($class, $id, $test=false) {
        $classMap = array('love'=>array('cost'=>15, 'maximum'=>20));

        if (!isset($classMap[$class])) return 0;
        $cost = $classMap[$class]['cost'];
        $maximum = $classMap[$class]['maximum'];

        $qry = "select TIMESTAMPDIFF(SECOND,NOW(),expires) as expires from ".LIMITS." where class='$class' and id='$id'";
        $res = mysql_query($qry);
        if ($res && ($row = mysql_fetch_assoc($res))) {
            $expires = max(0, $row['expires']);

            if ($expires > $maximum) {
                return $expires - $maximum;
            }
        } else {
            $expires = 0;
        }

        if (!$test) {
            $expires += $cost;
            $res = mysql_query("update ".LIMITS." set expires=TIMESTAMPADD(SECOND,$expires,NOW()) where class='$class' and id='$id'");
            if (!$res) {
                $res = mysql_query("insert into ".LIMITS." set class='$class', id='$id', expires=TIMESTAMPADD(SECOND,$expires,NOW())");
            }
        }

        return 0;
}

// Get the userId from the session, or set it to 0 for Guests.
function getSessionUserId() {
	return isset($_SESSION['userid']) ? (int)$_SESSION['userid'] : 0;
}

function getNickName($username) {
    static $map = array();
    if (!isset($map[$username])) {
        $strSQL = "select nickname from ".USERS." where username='".$username."'";
        $result = mysql_query($strSQL);
        $row    = mysql_fetch_array($result);
        $map[$username] = $row['nickname'];
    }
    return $map[$username];
}

function getWorkItemSummary($itemid) {
    $query = "select summary from ".WORKLIST." where id='$itemid'";
    $rt = mysql_query($query);
    if ($rt) {
        $row = mysql_fetch_assoc($rt);
        $summary = $row['summary'];
    }
    return $summary;
}

/* initSessionData
 *
 * Initializes the session data for a user.  Takes as input either a username or a an array containing
 * data from a row in the users table.
 *
 * NOTE: keep this function in sync with the same function in journal!!!
 */
function initSessionData($user) {
    if (!is_array($user)) {
        $res = mysql_query("select * from ".USERS." where username='".mysql_real_escape_string($user)."'");
        $user_row = (($res) ? mysql_fetch_assoc($res) : null);
        if (empty($user_row)) return;
    } else {
        $user_row = $user;
    }

    $_SESSION['username']           = $user_row['username'];
    $_SESSION['userid']             = $user_row['id'];
    $_SESSION['confirm_string']     = $user_row['confirm_string'];
    $_SESSION['nickname']           = $user_row['nickname'];
    $_SESSION['timezone']           = $user_row['timezone'];
    $_SESSION['is_admin']           = intval($user_row['is_admin']);
    $_SESSION['is_runner']          = intval($user_row['is_runner']);
    $_SESSION['is_payer']           = intval($user_row['is_payer']);
}

//joanne - TODO - wl uses function initUserById($userid)journal uses $uid {
//let's see if we can take this out and replace any calls with function initUserById($userid) {

function initSessionDataByUserId($uid) {
    $res = mysql_query("select * from ".USERS." where id='".mysql_real_escape_string($uid)."'");
    $user_row = (($res) ? mysql_fetch_assoc($res) : null);
    if (empty($user_row)) return;

    $_SESSION['username']           = $user_row['username'];
    $_SESSION['confirm_string']     = isset($user_row['confirm_string']) ? $user_row['confirm_string'] : 0;
    $_SESSION['nickname']           = $user_row['nickname'];
    $_SESSION['timezone']           = isset($user_row['timezone']) ? $user_row['timezone'] : 0;
    $_SESSION['is_admin']           = $user_row['is_admin'];
    $_SESSION['is_runner']          = $user_row['is_runner'];
    $_SESSION['is_payer']           = isset($user_row['is_payer']) ? intval($user_row['is_payer']) : 0;
}

function initUserById($userid) {
    $res = mysql_query("select * from ".USERS." where id='".mysql_real_escape_string($userid)."'");
    $user_row = (($res) ? mysql_fetch_assoc($res) : null);
    if (empty($user_row)) return;

    $_SESSION['username']           = $user_row['username'];
    $_SESSION['userid']             = $user_row['id'];
    $_SESSION['confirm_string']     = $user_row['confirm_string'];
    $_SESSION['nickname']           = $user_row['nickname'];
    $_SESSION['timezone']           = $user_row['timezone'];
    $_SESSION['is_runner']          = intval($user_row['is_runner']);
    $_SESSION['is_payer']           = intval($user_row['is_payer']);

    // set the session variable for the inline message for new users before last seen is updated
    if ($user_row['last_seen'] === null) {
        $_SESSION['inlineHide'] = 0;
    } else {
        $_SESSION['inlineHide'] = 1;
    }

    $last_seen_db = substr($user_row['last_seen'], 0, 10);
    $today = date('Y-m-d');

    if ($last_seen_db != $today) {
        $res = mysql_query("UPDATE ".USERS." SET last_seen = NOW() WHERE id={$userid}");
    }
    $_SESSION['last_seen'] = $today;
}

function isEnabled($features) {
    if (empty($_SESSION['features']) || ($_SESSION['features'] & $features) != $features) {
        return false;
    } else {
        return true;
    }
}

function isSuperAdmin() {
    if (empty($_SESSION['features']) || ($_SESSION['features'] & FEATURE_SUPER_ADMIN) != FEATURE_SUPER_ADMIN) {
        return false;
    } else {
        return true;
    }
}

/*  Function: countLoveToUser
 *
 *  Purpose: Gets the count of love sent to a user.
 *
 *  Parameters: username - The username of the desired user.
 *              fromUser - If set will get the love sent by this user.
 */
function countLove($username, $fromUsername="") {
    defineSendLoveAPI();
    //Wires off countLove to 0, ignores API (api working 5/24)
    //return array('status'=>SL_OK,'error'=>SL_NO_ERROR,array('count'=>0));

    if($fromUsername != "") {
        $params = array (
                'action' => 'getcount',
                'api_key' => SENDLOVE_API_KEY,
                'username' => $username,
                'fromUsername' => $fromUsername);
    } else {
        $params = array (
                'action' => 'getcount',
                'api_key' => SENDLOVE_API_KEY,
                'username' => $username);
    }
    $referer = (empty($_SERVER['HTTPS'])?'http://':'https://').$_SERVER['SERVER_NAME'].$_SERVER['PHP_SELF'];
    $retval = json_decode(postRequest (SENDLOVE_API_URL, $params, array(CURLOPT_REFERER => $referer)), true);

    if ($retval['status'] == "ok") {
        return $retval['data']['count'];
    } else {
        return -1;
    }
}

/*  Function: getUserLove
 *
 *  Purpose: Get Love sent to the user
 *
 *  Parameters: username - The username of the user to get love from.
 *              fromUsername - If set it will filter to the love sent by this username.
 */
function getUserLove($username, $fromUsername="") {
    defineSendLoveAPI();
    //Wires off getUserLove to 0, ignores API (api working 5/24)
    //return array('status'=>SL_OK,'error'=>SL_NO_ERROR,array('count'=>0));

    if($fromUsername != "") {
		$params = array (
		        'action' => 'getlove',
		        'api_key' => SENDLOVE_API_KEY,
		        'username' => $username,
		        'fromUsername' => $fromUsername,
		        'pagination' => 0);
    } else {
        $params = array (
                'action' => 'getlove',
                'api_key' => SENDLOVE_API_KEY,
                'username' => $username,
                'pagination' => 0);
    }
	$referer = (empty($_SERVER['HTTPS'])?'http://':'https://').$_SERVER['SERVER_NAME'].$_SERVER['PHP_SELF'];
    $retval = json_decode(postRequest (SENDLOVE_API_URL, $params, array(CURLOPT_REFERER => $referer)), true);

    if ($retval['status'] == "ok") {
        return $retval['data'];
    } else {
        return -1;
    }
}

function defineSendLoveAPI() {
    // Sendlove API status and error codes. Keep in sync with .../sendlove/api.php
    // only define constants once
    if (!defined('SL_OK')){
        define ('SL_OK', 'ok');
        define ('SL_ERROR', 'error');
        define ('SL_WARNING', 'warning');
        define ('SL_NO_ERROR', '');
        define ('SL_NO_RESPONSE', 'no response');
        define ('SL_BAD_CALL', 'bad call');
        define ('SL_DB_FAILURE', 'db failure');
        define ('SL_UNKNOWN_USER', 'unknown user');
        define ('SL_NOT_COWORKER', 'receiver not co-worker');
        define ('SL_RATE_LIMIT', 'rate limit');
        define ('SL_SEND_FAILED', 'send failed');
        define ('SL_JOURNAL_FAILED', 'journal failed');
        define ('SL_NO_SSL', 'no ssl call');
        define ('SL_WRONG_KEY', 'wrong api key');
        define ('SL_LOVE_DISABLED', 'sendlove disabled');
    }
}

// This will be handled by Rewarder API
/*
* Populate the rewarder team automatically. It's based on who added a fee to a task you worked on in the last 30 days.
*
*
*/
 function PopulateRewarderTeam($user_id, $worklist_id = '') {
    //Wire off rewarder interface for the time being - gj 5/21/10
    // returns results of mysql update operation (success=true)
    return true;

   $where = !empty($worklist_id) ?  " f.worklist_id = $worklist_id  " : "  f.worklist_id IN (SELECT DISTINCT  f1.worklist_id FROM " . FEES . " f1 WHERE f1.user_id = $user_id and f1.rewarder = 0) ";
   $rewarder_limit_day = GetPopulateRewarderLimit($user_id);
   $rewarder_limit_day = $rewarder_limit_day == 0 ? 30 : $rewarder_limit_day;
   $where .= " AND f.paid_date BETWEEN  (NOW() - INTERVAL $rewarder_limit_day day) AND NOW() " ;
// This will be replaced with an API call
//   $sql = "INSERT INTO " . REWARDER . " (giver_id,receiver_id,rewarder_points) SELECT DISTINCT $user_id, u.id, 0 FROM " . USERS . " u INNER JOIN " . FEES . " f ON (f.user_id = u.id) WHERE  $where AND NOT EXISTS (SELECT 1 FROM " . REWARDER . " rd WHERE rd.giver_id = $user_id) AND u.id <> $user_id ";
//   mysql_query($sql);
 }

 function GetPopulateRewarderLimit($user_id) {
    //Wire off rewarder, will use API - gj 5/24/10
    //Rewarder limit/day just return 0
    return 0;

    $sql = "SELECT rewarder_limit_day FROM ". USERS . " WHERE id= $user_id ";
    $rt = mysql_query($sql);
    if($row = mysql_fetch_assoc($rt)) {
      return $row['rewarder_limit_day'];
    }
    return 0;
 }

/* postRequest
 *
 * Function for performing a CURL request given an url and post data.
 * Returns the results.
 */
function postRequest($url, $post_data, $options = array(), $curlopt_timeout = 30) {
    if (!function_exists('curl_init')) {
        error_log('Curl is not enabled.');
        return 'error: curl is not enabled.';
    }

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_TIMEOUT, $curlopt_timeout);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

    if (count($options)) {
        curl_setopt_array($ch, $options);
    }

    $result = curl_exec($ch);
    curl_close($ch);

    return $result;
}

//converts unix timestamp to user's time according to his timezone settings
function getUserTime($timestamp){
    //need a default to not spew errors when browser is not logged in
    //We should probably change logic to always has a SESSION defined (from default)
    //Determine login status by SESSION['userid'] etc
    if (!empty($_SESSION['timezone'])) {
        $tz_correction = $_SESSION['timezone'];
        if(strpos($_SESSION['timezone'], "+") === 0){
            $tz_correction = "-".substr($_SESSION['timezone'],1);
        }elseif(strpos($_SESSION['timezone'], "-") === 0){
            $tz_correction = "+".substr($_SESSION['timezone'],1);
        }
    } else { $tz_correction=0; }

    $server_tz = date_default_timezone_get();
    date_default_timezone_set  ("Europe/London");
    $userTime = date("m/d/Y h:i a", strtotime(date("Y-m-d H:i", $timestamp)." ".$tz_correction));
    date_default_timezone_set  ($server_tz);
    return $userTime;
}

// converts server time to users timzone time
function convertTimezone($timestamp){
    if (isset($_SESSION['timezone']) && !empty($_SESSION['timezone'])) {
        $time_zone_date_time = getTimeZoneDateTime($_SESSION['timezone']);
        if ($time_zone_date_time) {
            $oTz = date_default_timezone_get();
            date_default_timezone_set($time_zone_date_time);
            $new_time = date('m/d/Y h:i a', $timestamp);
            date_default_timezone_set($oTz);
            return $new_time;
        }
    }
    return date('m/d/Y h:i a', $timestamp);
}

/*    Function: AddFee
 *
 *     Purpose: This function inserts
 *
 *  Parameters:     itemid - id of the worklist entry
 *              fee_amount - amount of the fee
 *            fee_category - accounting category for the fee (Refer to below list)
 *                fee_desc - description of the fee entry
 *             mechanic_id - userid of the mechanic
 *
 * Fee Categories: Bid, Code Review, Design Spec, Misc Expense, Management Fee
 *
 */
function AddFee($itemid, $fee_amount, $fee_category, $fee_desc, $mechanic_id, $is_expense, $is_rewarder=0)
{
    if ($is_rewarder) $is_expense = 0;
    // Get work item summary
    $query = "select summary from ".WORKLIST." where id='$itemid'";
    $rt = mysql_query($query);
    if ($rt) {
        $row = mysql_fetch_assoc($rt);
        $summary = $row['summary'];
    }

    $query = "INSERT INTO `".FEES."` (`id`, `worklist_id`, `amount`, `category`, `user_id`, `desc`, `date`, `paid`, `expense`) ".
        "VALUES (NULL, '".(int)$itemid."', '".(float)$fee_amount."', '".(int)$fee_category."', '".(int)$mechanic_id."', '".mysql_real_escape_string($fee_desc)."', NOW(), '0', '".mysql_real_escape_string($is_expense)."' )";
    $result = mysql_unbuffered_query($query);

    // Journal notification
    if($mechanic_id == $_SESSION['userid'])
    {
        $journal_message = '@' . $_SESSION['nickname'] . ' added a fee of $' . $fee_amount . ' to #' . $itemid;
    }
    else
    {
        // Get the mechanic's nickname
        $rt = mysql_query("select nickname from ".USERS." where id='".(int)$mechanic_id."'");
        if ($rt) {
            $row = mysql_fetch_assoc($rt);
            $nickname = $row['nickname'];
        }
        else
        {
            $nickname = "unknown-{$mechanic_id}";
        }

        $journal_message = '@' . $_SESSION['nickname'] . ' on behalf of @' . $nickname . ' added a fee of $' . $fee_amount . ' to #' . $itemid;
    }

    return $journal_message;
}

function AddTip($itemid, $tip_amount, $tip_desc, $mechanic_id) {
    // Get work item summary
    $query = "SELECT `summary` FROM " . WORKLIST. " WHERE `id` = '{$itemid}'";
    $rt = mysql_query($query);
    if ($rt) {
        $row = mysql_fetch_assoc($rt);
        $summary = $row['summary'];
    }

    // get the tippee's nickname
    $rt = mysql_query("SELECT nickname FROM " . USERS . " WHERE id = '". (int) $mechanic_id . "'");
    if ($rt) {
        $row = mysql_fetch_assoc($rt);
        $nickname = $row['nickname'];
    }

    // validate
    $query = "
        SELECT * FROM " . FEES . "
        WHERE
            `worklist_id` = $itemid
            AND `user_id` = " . getSessionUserId() . "
            AND `desc` = 'Accepted Bid'
            AND `withdrawn` = 0";

    $rt = mysql_query($query);
    if ($rt) {
        if (mysql_num_rows($rt) > 0) {
            $row = mysql_fetch_assoc($rt);

            // deduct the tip from the mechanic's accepted bid fee
            if ($tip_amount > 0 && $tip_amount <= $row['amount']) {
                $adjusted_amount = $row['amount'] - $tip_amount;
                // reduce the mechanic's accepted bid
                $query = "UPDATE " . FEES . " SET amount = {$adjusted_amount} WHERE id = {$row['id']}";
                mysql_query($query);
            }
            // add the tip as a fee on the job
            $tip_desc = 'Tip: ' . $tip_desc;
            $query = "INSERT INTO `".FEES."` (`id`, `worklist_id`, `amount`, `user_id`, `desc`, `date`, `paid`) ".
                     "VALUES (NULL, '".(int)$itemid."', '".(float)$tip_amount."', '".(int)$mechanic_id."', '".mysql_real_escape_string($tip_desc)."', NOW(), '0')";

            $result = mysql_unbuffered_query($query);
            return $_SESSION['nickname'] . " tipped $nickname on job #$itemid: $summary. ";
        }
    }
    return '';
}

function payBonusToUser($user_id, $amount, $notes, $budget_id) {

    $query = "INSERT INTO `".FEES."` (`id`, `worklist_id`, `budget_id`, `payer_id`, `user_id`, `amount`, `notes`, `desc`, `date`, `bonus`,`paid`,`category`)".
             "VALUES ".
             "(NULL, 0, '" . (int)$budget_id . "', '" . (int)$_SESSION['userid'] . "', '" . (int)$user_id . "', '" . (float)$amount . "', 'BONUS','" . mysql_real_escape_string($notes) . "', NOW(), 1, 0,0)";
    $result = mysql_unbuffered_query($query);

    if (mysql_insert_id()) {
        return true;
    } else {
        return false;
    }
}
function formatableRelativeTime($timestamp, $detailLevel = 1) {
	$periods = array("sec", "min", "hr", "day", "week", "mnth", "yr", "decade");
	$lengths = array("60", "60", "24", "7", "4.357", "12", "10");
	$now = time();
	if(empty($timestamp)) {
		return "Unknown time";
	}
	if($now > $timestamp) {
		$difference = $now - $timestamp;
		$tense = "";
	} else {
		$difference = $timestamp - $now;
		$tense = "from now";
	}
	if ($difference == 0) {
		return "1 second ago";
	}
	$remainders = array();
	for($j = 0; $j < count($lengths); $j++) {
		$remainders[$j] = floor(fmod($difference, $lengths[$j]));
		$difference = floor($difference / $lengths[$j]);
	}
	$difference = round($difference);
	$remainders[] = $difference;
	$string = "";
	for ($i = count($remainders) - 1; $i >= 0; $i--) {
		if ($remainders[$i]) {
			$string .= $remainders[$i] . " " . $periods[$i];
			if($remainders[$i] != 1) {
				$string .= "s";
			}
			$string .= " ";
			$detailLevel--;
			if ($detailLevel <= 0) {
				break;
			}
		}
	}
	return $string . $tense;
}

function relativeTime($time, $withIn = true, $justNow = true, $withAgo = true) {
    $secs = abs($time);
    $mins = 60;
    $hour = $mins * 60;
    $day = $hour * 24;
    $week = $day * 7;
    $month = $day * 30;
    $year = $day * 365;

    // years
    $segments = array();
    $segments['yr']   = intval($secs / $year);
    $secs %= $year;
    // month
    $segments['mnth'] = intval($secs / $month);
    $secs %= $month;
    if (!$segments['yr']) {
        $segments['day']  = intval($secs / $day);
        $secs %= $day;
        if (!$segments['mnth']) {
            $segments['hr']   = intval($secs / $hour);
            $secs %= $hour;
            if (!$segments['day']) {
                $segments['min']  = intval($secs / $mins);
                $secs %= $mins;
                if (!$segments['hr'] && !$segments['min']) {
                    $segments['sec']  = $secs;
                }
            }
        }
    }

    $relTime = '';
    foreach ($segments as $unit=>$cnt) {
        if ($segments[$unit]) {
            $relTime .= "$cnt $unit";
            if ($cnt > 1) {
                $relTime .= 's';
            }
            $relTime .= ', ';
        }
    }
    $relTime = substr($relTime, 0, -2);
    if (!empty($relTime)) {
        return ($time < 0) ? ($withAgo ? '' : '-') . ("$relTime " . ($withAgo ? 'ago' : '')) : ($withIn ? "in $relTime" : $relTime);
    } else {
        return $justNow ? 'just now' : '';
    }
}

function is_runner() {
    return !empty($_SESSION['is_runner']) ? true : false;
}

function sendJournalNotification($message) {
    $entry = new EntryModel();
    return $entry->notify($message);
    global $chat;

    $username = mysql_real_escape_string(JOURNAL_API_USER);
    $password = mysql_real_escape_string(sha1(JOURNAL_API_PWD));
    $sql = "select id, nickname from ".USERS." where username='$username' and password='$password' and confirm='1'";
    if (! $res = mysql_query($sql)) {
        error_log("jadd.mysql: ".mysql_error());
    }
    if($res && mysql_num_rows($res) > 0) {
        $row = mysql_fetch_assoc($res);
        $data = $chat->sendEntry($row['nickname'], $message, array('userid' => $row['id']), false, false);
        if($data['status'] == 'ok') {
            return "ok";
        } else {
            return "error: failed while writing entry with status: {$data['status']}.";
        }
    } else {
        return "error: invalid user.";
    }
}

function withdrawBid($bid_id, $withdraw_reason) {
    $res = mysql_query('SELECT * FROM `' . BIDS . '` WHERE `id`='.$bid_id);
    $bid = mysql_fetch_object($res);

    // checking if is bidder or runner
    if (is_runner() || ($bid->bidder_id == $_SESSION['userid'])) {
        // getting the job
        $res = mysql_query('SELECT * FROM `' . WORKLIST . '` WHERE `id` = ' . $bid->worklist_id);
        $job = mysql_fetch_assoc($res);

        if (! in_array($job['status'], array(
            'Draft',
            'Suggested',
            'SuggestedWithBid',
            'Bidding',
            'Done'
        ))) {

            $creator_fee_desc = 'Creator';
            $runner_fee_desc = 'Runner';

            $WorkItem = new WorkItem($bid->worklist_id);
            $fees = $WorkItem->getFees($WorkItem->getId());

            foreach ($fees as $fee) {
                if ($fee['desc'] == $creator_fee_desc) {
                    deleteFee($fee['id']);
                }

                if ($fee['desc'] == $runner_fee_desc) {
                    deleteFee($fee['id']);
                }
            }
        }

        // additional changes if status is WORKING, SVNHOLD, FUNCTIONAL or REVIEW
        if (($job['status'] == 'Working' || $job['status'] == 'SVNHold' || $job['status'] == 'Review' || $job['status'] == 'Functional')
        && ($bid->accepted == 1) && (is_runner() || ($bid->bidder_id == $_SESSION['userid']))) {
            // change status of worklist item
            mysql_unbuffered_query("UPDATE `" . WORKLIST . "`
	            						SET `mechanic_id` = '0',
										`status` = 'Bidding'
										WHERE `id` = $bid->worklist_id
										LIMIT 1 ;");
        }

        // set back to suggested if swb and is only bid
        $res = mysql_query('SELECT count(*) AS count_bids FROM `' . BIDS . '` WHERE `worklist_id` = ' . $job['id'] . ' AND `withdrawn` = 0');
        $bidCount = mysql_fetch_assoc($res);

        if ($bidCount['count_bids'] == 1 && $job['status'] == 'SuggestedWithBid') {
        mysql_unbuffered_query("UPDATE `" . WORKLIST . "` SET `status` = 'Suggested' WHERE `id` = $bid->worklist_id LIMIT 1 ;");
        }

        // change bid to withdrawn and set bids.accepted to 0
        mysql_unbuffered_query('UPDATE `' . BIDS . '`
	        						SET `withdrawn` = 1 , `accepted` = 0
	        						WHERE `id` = ' . $bid->id);

        // delete the fee entry for this bid
        mysql_unbuffered_query('UPDATE `' . FEES . '`
                                    SET `withdrawn` = 1
                                    WHERE `worklist_id` = ' . $bid->worklist_id . '
                                    AND `user_id` = ' . $bid->bidder_id . '
                                    AND `bid_id` = ' . $bid->id);

        // Get user
        $user = getUserById($bid->bidder_id);

        // Journal message
        $message  = 'A bid was deleted from #' . $job['id'];

        // Journal notification
        sendJournalNotification($message);

        // Sending email to the bidder or runner
        $subject = "Bid: " . $job['id'] . " (" . $job['summary']. ")";

        if(is_runner()){
        	// Send to bidder
        	$recipient=$user;
        	$body = "<p>Your bid has been deleted from item #" . $job['id'] . " by: ".$_SESSION['nickname']."</p>";
        } else {
        	// Send to runner
        	$recipient=getUserById($job['runner_id']);
        	$body = "<p>A bid has been deleted from item #" . $job['id'] . " by: ".$_SESSION['nickname']."</p>";
        }

        if(strlen($withdraw_reason)>0) {
		    // nl2br is added for proper formatting in email alert 12-MAR-2011 <webdev>
        	$body .= "<p>Reason: " .nl2br($withdraw_reason)."</p>";
        }

        // Continue adding text to email body
        $item_link = SERVER_URL . $bid->worklist_id;
        $body .= "<p><a href='${item_link}'>View Item</a></p>";
        $body .= "<p>If you think this has been done in error, please contact the job Runner.</p>";
        if (!send_email($recipient->username, $subject, $body)) { error_log("withdrawBid: send_email failed"); }

        // Check if there are any active bids remaining
        $res = mysql_query("SELECT count(*) AS active_bids FROM `" . BIDS . "` WHERE `worklist_id` = " . $job['id'] . " AND `withdrawn` = 0 AND (NOW() < `bid_expires` OR `bid_expires`='0000-00-00 00:00:00')");
        $bids = mysql_fetch_assoc($res);
        if ($bids['active_bids'] < 1) {
        	// There are no active bids, so resend notifications
        	$workitem = new WorkItem();
        	$workitem->loadById($job['id']);

        	Notification::massStatusNotify($workitem);
        }

    }
}

function deleteFee($fee_id) {
    $res = mysql_query('SELECT * FROM `' . FEES . '` WHERE `id`='.$fee_id);
    $fee = mysql_fetch_object($res);

    // checking if is bidder or runner
    if (is_runner() || ($fee->user_id == $_SESSION['userid'])) {
        mysql_unbuffered_query('UPDATE `' . FEES . '`
	    							SET `withdrawn` = 1
			            			WHERE `id` = ' . $fee_id);

        // Get worklist item summary
        $summary = getWorkItemSummary($fee->worklist_id);
        // Get user
        $user = getUserById($fee->user_id);

        if ($user !== false) {
            // Journal message
            $message  = '@' . $_SESSION['nickname'] . ' deleted a fee from @';
            $message .= $user->nickname . ' on #' . $fee->worklist_id;

            // Journal notification
            sendJournalNotification($message);

            //sending email to the bidder
            $options = array();
            $options['emails'] = array($user->username);
            $options['workitem'] = new WorkItem();
            $options['workitem']->loadById($fee->worklist_id);
            $options['type'] = "fee_deleted";
            Notification::workitemNotify($options);

            $data = array(
                'nick' => $_SESSION['nickname'],
                'fee_nick' => $user->nickname
            );
            Notification::workitemNotifyHipchat($options, $data);

        }
    }
}

function getUserById($id) {
    $res = mysql_query('SELECT * FROM `' . USERS . '` WHERE `id` = ' . $id . ' AND `is_active` = 1');
    if ($res && (mysql_num_rows($res) == 1)) {
        return mysql_fetch_object($res);
    }
    return false;
}

function getUserByNickname($nickname) {
    $res = mysql_query('SELECT * FROM `' . USERS . '` WHERE `nickname` = "' . $nickname . '" AND `is_active` = 1;');
    if ($res && (mysql_num_rows($res) == 1)) {
        return mysql_fetch_object($res);
    }
    return false;
}

function getWorklistById($id) {
    $query = "select * from ".WORKLIST." where id='$id'";
    $rt = mysql_query($query);
    if ($rt && (mysql_num_rows($rt) == 1)) {
        return mysql_fetch_assoc($rt);
    }
    return false;
}

/* invite one peorson By nickname or by email*/
function invitePerson($invite, $workitem) {
    // trim the whitespaces
    $invite = trim($invite);
    if (!empty($invite)) {
        // get the user by Nickname
        $user = getUserByNickname($invite);
        if ($user !== false) {
            //sending email to the invited developer
            Notification::workitemNotify(array(
	        'type' => 'invite-user',
	        'workitem' => $workitem,
	        'emails' => array($user->username)
                ));
            return true;
        } else if (validEmail($invite)) {
            //sending email to the NEW invited developer
            Notification::workitemNotify(array(
	        'type' => 'invite-email',
	        'workitem' => $workitem,
	        'emails' => array($invite)
                ));
            return true;
        }
    }
    return false;
}
/* invite people By nickname or by email*/
function invitePeople(array $people, $workitem) {
    $nonExistingInvites = array();
    foreach ($people as $invite) {
        $invite = trim($invite);
        if(!empty($invite)){
            // Call the invite person function
            if(!invitePerson($invite, $workitem)) {
                // means person was not invited cause they dont exist
                $nonExistingInvites[] = $invite;
            }
        }
    }
    return $nonExistingInvites;
}
/**
 Validate an email address.
 Provide email address (raw input)
 Returns true if the email address has the email
 address format and the domain exists.
 */
function validEmail($email) {
    $isValid = true;
    $atIndex = strrpos($email, "@");
    if (is_bool($atIndex) && !$atIndex) {
        $isValid = false;
    } else {
        $domain = substr($email, $atIndex+1);
        $local = substr($email, 0, $atIndex);
        $localLen = strlen($local);
        $domainLen = strlen($domain);
        if ($localLen < 1 || $localLen > 64) {
            // local part length exceeded
            $isValid = false;
        } else if ($domainLen < 1 || $domainLen > 255) {
            // domain part length exceeded
            $isValid = false;
        } else if ($local[0] == '.' || $local[$localLen-1] == '.') {
            // local part starts or ends with '.'
            $isValid = false;
        } else if (preg_match('/\\.\\./', $local)) {
            // local part has two consecutive dots
            $isValid = false;
        } else if (!preg_match('/^[A-Za-z0-9\\-\\.]+$/', $domain)) {
            // character not valid in domain part
            $isValid = false;
        } else if (preg_match('/\\.\\./', $domain)) {
            // domain part has two consecutive dots
            $isValid = false;
        } else if (!preg_match('/^(\\\\.|[A-Za-z0-9!#%&`_=\\/$\'*+?^{}|~.-])+$/', str_replace("\\\\","",$local))) {
            // character not valid in local part unless
            // local part is quoted
            if (!preg_match('/^"(\\\\"|[^"])+"$/', str_replace("\\\\","",$local))) {
                $isValid = false;
            }
        }
        if ($isValid && !(checkdnsrr($domain,"MX") || checkdnsrr($domain,"A"))) {
            // domain not found in DNS
            $isValid = false;
        }
    }
    return $isValid;
}

function  GetTimeStamp($MySqlDate, $i='')
{
    if (empty($MySqlDate)) $MySqlDate = date('Y/m/d');
    $date_array = explode("/",$MySqlDate); // split the array

    $var_year = $date_array[0];
    $var_month = $date_array[1];
    $var_day = $date_array[2];
    $var_timestamp=$date_array[2]."-".$date_array[0]."-".$date_array[1];
    //$var_timestamp=$var_month ."/".$var_day ."-".$var_year;
    return($var_timestamp); // return it to the user
}

// is user posting data without being logged in
function handleUnloggedPost() {
    // get the IP address
    $request_ip = $_SERVER['REMOTE_ADDR'];
    $request_uri = $_SERVER['REQUEST_URI'];
    error_log('Possible hack attempt from ' . $request_ip . ' on: ' . $request_uri);
    error_log(json_encode($_REQUEST));
    die('You are not authorized to post to this URL. Click ' .
        '<a href="' . SERVER_URL . '">here</a> to go to the main page. ' . "\n");
}

function checkLogin() {
    if (! getSessionUserId()) {
        $_SESSION = array();
        session_destroy();
        if (!empty($_POST)) {
            handleUnloggedPost();
        }
        Utils::redirect('./github/login?expired=1&redir=' . urlencode($_SERVER['REQUEST_URI']));
        exit;
    }
}

/* linkify function
 *
 * this takes some input and makes links where it thinks they should go
 *
 */
function linkify($url, $author = null, $bot = false, $process = true)
{
    $original = $url;

    if(!$process) {
        if (mb_detect_encoding($url, 'UTF-8', true) === FALSE) {
            $url = utf8_encode($url);
        }
        return '<a href="http://' . htmlentities($url, ENT_QUOTES, "UTF-8") . '">' . htmlspecialchars($url) . '</a>';
    }

    $class = '';
    if(strpos($url, "helper/get_attachment.php") > 0)
    {
        $bot = true;
        $class=' class="attachment noicon"';
    } else {
        $class='';
    }
    $url = html_entity_decode($url, ENT_QUOTES);
    if (preg_match("/\<a href=\"([^\"]*)\"/i", $url) == 0) {
        // modified this so that it will exclude certain characters from the end of the url
        // add to this as you see fit as I assume the list is not exhaustive
        $regexp="/((?:(?:ht|f)tps?\:\/\/|www\.)\S+[^\s\.\)\"\'])/i";
        $url=  preg_replace($regexp, DELIMITER . '<a href="$0"' . $class . '>$0</a>' . DELIMITER, $url);

        $regexp="/href=\"(www\.\S+?)\"/i";
        $url = preg_replace($regexp,'href="http://$1"', $url);
    }

    $regexp="/(href=)(.)?((www\.)\S+(\.)\S+)/i";
    $url = preg_replace($regexp,'href="http://$3"', $url);

    // Replace '#<number>' with a link to the worklist item with the same number
    $regexp = "/\#([1-9][0-9]{4})(\s|[^0-9a-z]|$)/i";
    if (!function_exists('workitemLinkPregReplaceCallback')) {
        /**
         * Checks whether a #<number> string should be taken as a workitem link or not.
         * This function is used as a callback with preg_replace_callback (see below lines)
         */
        function workitemLinkPregReplaceCallback($matches) {
            $job_id = (int) $matches[1];
            if ($job_id < 99999 && WorkItem::idExists($job_id)) {
                return
                    DELIMITER .
                    '<a href="' . WORKLIST_URL . $job_id . '"' .
                    ' class="worklist-item" id="worklist-' . $job_id . '" >#' . $job_id . '</a>' .
                    DELIMITER . $matches[2];
            } else {
                return $matches[0];
            }
        }
    }
    $url = preg_replace_callback($regexp,  'workitemLinkPregReplaceCallback', $url);

    // Replace '##<projectName>##' with a link to the worklist project with the same name
    // This is used in situations where the project name has a space or spaces or no space
    $regexp = "/\#\#([A-Za-z0-9_ ]+)\#\#/";
    $link = DELIMITER . '<a href="' . WORKLIST_URL . '$1">$1</a>' . DELIMITER;
    $url = preg_replace($regexp,  $link, $url);

    // Replace '##<projectName>' with a link to the worklist project with the same name
    // This is used in situations where the first space encountered is assumed to
    // be the end of the project name. Left mainly for backward compatibility.
    $regexp = "/\#\#([A-Za-z0-9_]+)/";
    $link = DELIMITER . '<a href="' . WORKLIST_URL . '$1">$1</a>' . DELIMITER;
    $url = preg_replace($regexp,  $link, $url);

    // Replace '#<nick>/<url>' with a link to the author sandbox
    $regexp="/\#([A-Za-z]+)\/(\S*)/i";
    $url = preg_replace(
        $regexp, DELIMITER .
        '<a href="https://' . SANDBOX_SERVER . '/~$1/$2" class="sandbox-item" id="sandbox-$1">$1 : $2</a>' . DELIMITER,
        $url
    );

	// Replace '<repo> v####' with a link to the SVN server
    $regexp = '/([a-zA-Z0-9]+)\s[v]([0-9_]+)/i';
    $link = DELIMITER . '<a href="' . SVN_REV_URL . '$1&rev=$2">$1 v$2</a>' . DELIMITER;
    $url = preg_replace($regexp,  $link, $url);

    // Replace '#/<url>' with a link to the author sandbox
    $regexp="/\#\/(\S*)/i";
    if (strpos(SERVER_BASE, '~') === false) {
        $url = preg_replace(
            $regexp, DELIMITER .
            '<a href="' . SERVER_BASE . '~' . $author . '/$1" class="sandbox-item" id="sandbox-$1">'.$author.' : $1</a>' . DELIMITER,
            $url
        );
    } else { // link on a sand box :
        $url = preg_replace(
            $regexp, DELIMITER .
            '<a href="' . SERVER_BASE . '/../~' . $author . '/$1" class="sandbox-item" id="sandbox-$1" >'.$author.' : $1</a>' . DELIMITER,
            $url
        );
    }

    $regexp="/\b(?<=mailto:)([A-Za-z0-9_\-\.\+])+\@([A-Za-z0-9_\-\.])+\.([A-Za-z]{2,4})/i";
    if(preg_match($regexp,$url)){
        $regexp="/\b(mailto:)(?=([A-Za-z0-9_\-\.\+])+\@([A-Za-z0-9_\-\.])+\.([A-Za-z]{2,4}))/i";
        $url=preg_replace($regexp,"",$url);
    }

    $regexp = "/\b([A-Za-z0-9_\-\.\+])+\@([A-Za-z0-9_\-\.])+\.([A-Za-z]{2,4})/i";
    $url = preg_replace($regexp, DELIMITER . '<a href="mailto:$0">$0</a>' . DELIMITER, $url);

    // find anything that looks like a link and add target=_blank so it will open in a new window
    $url = htmlspecialchars_decode($url);

    $url = preg_replace("/<a\s+href=\"/", "<a target=\"_blank\" href=\"" , $url);
    if (mb_detect_encoding($url, 'UTF-8', true) === FALSE) {
        $url = utf8_encode($url);
    }
    if (!$bot) {
        $url = htmlentities($url, ENT_QUOTES, "UTF-8");
    }
    $url = nl2br($url);
    $reg = '/' . DELIMITER . '.+' . DELIMITER . '/';
    $url = preg_replace_callback($reg, 'decodeDelimitedLinks', $url);

    // mentions - @username, comments and job descriptions
    $url = preg_replace(
        '/(^|[^a-z0-9_])@([a-z0-9_]+)/i',
        '$1<a href="' . WORKLIST_URL . 'user/$2">@$2</a>',
    $url);

    return $url;
}

/**
 * Auxiliar function to help decode anchors in linkify function
 */
function decodeDelimitedLinks($matches) {
    $result = preg_replace('/' . DELIMITER . '/', '', $matches[0]);
    return html_entity_decode($result, ENT_QUOTES);
}

/**
 * Return a trimmed version of the nickname
 */
function getSubNickname($nickname, $length = 18) {
    if (strlen($nickname) > $length) {
        return substr($nickname, 0, $length) . '...';
    } else {
        return $nickname;
    }
}

function getProjectList() {
    $query = "SELECT * FROM `".PROJECTS."` WHERE active=1";
    $query = mysql_query($query);
    $projects = array();
    $i = 0;
    while ($project = mysql_fetch_array($query)) {
        $projects[$i]['name'] = $project['name'];
        $projects[$i]['id']   = $project['project_id'];
        $projects[$i]['repo'] = $project['repository'];
        $i++;
    }
    return $projects;
}

/* This function is used to add <br/> to encoded strings
 */
function replaceEncodedNewLinesWithBr($string) {
    $string =  str_replace('&#13;&#10;', '<br/>', $string);
    return str_replace('&#10;', '<br/>', $string);
}

/* outputForJS
*
* Pass a variable and it'll check that it's not null so empty numeric/date vars don't error
* Returns either an empty quotes string or the variable
* takes an optional second param as an alternative replacement
*/
function outputForJS($var, $rep = "''") {
    return (is_null($var) || empty($var)) ? $rep : $var;
}

function isSpammer($ip) {
    $sql = 'SELECT `ipv4` FROM `' . BLOCKED_IP . '` WHERE (`blocktime` + `duration`) > UNIX_TIMESTAMP(NOW());';
    $result = mysql_query($sql);

    if ($result && (mysql_num_rows($result) > 0)) {
        while ($row = mysql_fetch_array($result)) {
            if ($row['ipv4'] == $ip) {
                return true;
            }
        }
    }
    return false;
}

function sendReviewNotification($reviewee_id, $type, $oReview) {
    $review = $oReview[0]['feeRange'] . " " . $oReview[0]['review'];
    $reviewee = new User();
    $reviewee->findUserById($reviewee_id);
    $worklist_link = WORKLIST_URL;

    $to = $reviewee->getNickname() . ' <' . $reviewee->getUsername() . '>';
    $body  = "<p>" . $review . "</p>";
    $nickname = $reviewee->getNickname();
    $headers = array();
    if ($type == "new") {
        $userinfo_link = WORKLIST_URL . 'user/?id=' . $reviewee->getId();
        $headers['From'] = 'worklist<donotreply@worklist.net>';
        $subject = 'New Peer Review';
        $journal = '@' . $nickname . " received a new review: " . $review;
        $body  = '<p>Hello ' . $nickname . ',</p><br />';
        $body  .= '<p>You have received a review from one of your peers in the Worklist.</p><br />';
        $body  .= '<p>To see your current user reviews, click <a href="' . $userinfo_link . '">here</a>.</p>';
        $body  .= '<p><a href="' . $userinfo_link . '">' . $userinfo_link . '</a></p><br />';
        $body  .= '<p><a href="' . WORKLIST_URL . '"jobs>worklist' . '</a></p>';
    } else if ($type == "update") {
        $subject = "A review of you has been updated";
        $journal = "A review of @" . $nickname . " has been updated: ". $review;
    } else {
        $subject = "One of your reviews has been deleted";
        $journal = "One review of @" . $nickname . " has been deleted: ". $review;
    }

    if (!send_email($to, $subject, $body, null, $headers)) {
        error_log("sendReviewNotification: send_email failed");
    }
    sendJournalNotification($journal);
}

function truncateText($text, $chars = 200, $lines = 5) {
    $truncated = false;
    $total = strlen($text);
    if ($total > $chars) {
        $text = substr($text, 0, $chars);
        $truncated = true;
    }
    $text = nl2br($text);
    $textArray = explode('<br/>', $text);
    $textArraySize = count($textArray);

    // Remove extra lines
    if ($textArraySize > $lines) {
        $count = $textArraySize - $lines;
        for ($i = 0; $i < $count; $i++) {
            array_pop($textArray);
        }
        $truncated = true;
    }

    $text = implode('<br/>', $textArray);
    if ($truncated == true) {
        $text = $text . " (...)";
    }
    return $text;
}

function getRelated($input) {
    $related = "";
    $twoIds = false;
    if (preg_match_all('/(\#[1-9][0-9]{4})(\s|[^0-9a-z]|$)/i', $input, $matches)) {
        $distinctMatches = array_unique($matches[1]);
        foreach($distinctMatches as $match) {
            $job_id = (int) substr($match, 1);
            if ($job_id != $worklist_id && WorkItem::idExists($job_id)) {
                if ($related != "") {
                    $twoIds = true;
                    $related .= ", #" . $job_id;
                } else {
                    $related = " #" . $job_id;
                }
            }
        }
    }
    if ($related != "") {
        $related .= ")";
        if ($twoIds == true) {
            $related = " (related jobs: " . $related;
        } else {
            $related = " (related job: " . $related;
        }
    }
    return $related;
}

function addRewarderBalance($userId, $points, $worklist_id = 0, $fee_id = 0) {
    //Wire off rewarder interface for the time being - gj 5/21/10
    if(true) return 1;

    defineSendLoveAPI();

    $reason = "Worklist paid you $" . $points;
    $params = array (
            'action' => 'change_balance',
            'api_key' => REWARDER_API_KEY,
            'user_id' => $userId,
            'points' => $points,
            'reason' => $reason,
            'worklist_id' => $worklist_id,
            'fee_id' => $fee_id,
                    );

    $referer = (empty($_SERVER['HTTPS'])?'http://':'https://').$_SERVER['SERVER_NAME'].$_SERVER['PHP_SELF'];
    $retval = json_decode(postRequest (REWARDER_API_URL, $params, array(CURLOPT_REFERER => $referer)), true);

    if ($retval['status'] == "ok") {
        return 1;
    } else {
        return -1;
    }
}

/*
* getRewardedPoints  - api call to rewarder to get how many
* points $giverId has given to $receiverId
*
*/
function getRewardedPoints($giverId, $receiverId) {
    defineSendLoveAPI();
    $params = array (
            'action' => 'get_points',
            'api_key' => REWARDER_API_KEY,
            'giver_id' => $giverId,
            'receiver_id' => $receiverId,
                    );

    $referer = (empty($_SERVER['HTTPS'])?'http://':'https://').$_SERVER['SERVER_NAME'].$_SERVER['PHP_SELF'];
    $retval = json_decode(postRequest (REWARDER_API_URL, $params, array(CURLOPT_REFERER => $referer)), true);

    if ($retval['status'] == "ok") {
        return $retval['data'];
    } else {
        return -1;
    }
}

/*
* rewardUser - api call to rewarder to grant
* rewarder points from $giverId to $receiverId
*
*/
function rewardUser($giverId, $receiverId, $points) {
    defineSendLoveAPI();

    $params = array (
            'action' => 'reward_user',
            'api_key' => REWARDER_API_KEY,
            'giver_id' => $giverId,
            'receiver_id' => $receiverId,
            'points' => $points,
                    );

    $referer = (empty($_SERVER['HTTPS'])?'http://':'https://').$_SERVER['SERVER_NAME'].$_SERVER['PHP_SELF'];
    $retval = json_decode(postRequest (REWARDER_API_URL, $params, array(CURLOPT_REFERER => $referer)), true);

    if ($retval['status'] == "ok") {
        return $retval['data'];
    } else {
        return -1;
    }
}

/*******************************************************
    PPHttpPost: NVP post function for masspay.
    Author: Jason (jkofoed@gmail.com)
    Date: 2010-04-01 [Happy April Fool's!]
********************************************************/
function PPHttpPost($methodName_, $nvpStr_, $credentials) {
    $environment = PAYPAL_ENVIRONMENT;
    $pp_user = $credentials['pp_api_username'];
    $pp_pass = $credentials['pp_api_password'];
    $pp_signature = $credentials['pp_api_signature'];

    $API_Endpoint = "https://api-3t.paypal.com/nvp";
    if("sandbox" === $environment || "beta-sandbox" === $environment) {
        $API_Endpoint = "https://api.$environment.paypal.com/nvp";
    }
    $version = urlencode('51.0');

    // Set the curl parameters.
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $API_Endpoint);
    curl_setopt($ch, CURLOPT_VERBOSE, 1);

    // Turn off the server and peer verification (TrustManager Concept).
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);

    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_POST, 1);

    // Set the API operation, version, and API signature in the request.
    $nvpreq = 'METHOD='.$methodName_.'&VERSION='.$version.'&PWD='.$pp_pass.'&USER='.$pp_user.'&SIGNATURE='.$pp_signature.''.$nvpStr_;

    // Set the request as a POST FIELD for curl.
    curl_setopt($ch, CURLOPT_POSTFIELDS, $nvpreq);

    // Get response from the server.
    $httpResponse = curl_exec($ch);

    if(!$httpResponse) {
        exit("$methodName_ failed: ".curl_error($ch).'('.curl_errno($ch).')');
    }

    // Extract the response details.
    $httpResponseAr = explode("&", $httpResponse);
    $httpParsedResponseAr = array();
    foreach ($httpResponseAr as $i => $value) {
        $tmpAr = explode("=", $value);
        if(sizeof($tmpAr) > 1) {
            $httpParsedResponseAr[$tmpAr[0]] = $tmpAr[1];
        }
    }
    $httpParsedResponseAr["nvpEndpoint"] = $API_Endpoint;
    $httpParsedResponseAr["nvpString"] = $nvpreq;
    if((0 == sizeof($httpParsedResponseAr)) || !array_key_exists('ACK', $httpParsedResponseAr)) {
        exit("Invalid HTTP Response for POST request($nvpreq) to $API_Endpoint.");
    }

    return $httpParsedResponseAr;
}

/*  send_email
 *
 *  send email using local mail()
 */
function send_email($to, $subject, $html, $plain = null, $headers = array()) {
    //Validate arguments
    $html= replaceEncodedNewLinesWithBr($html);
    if (empty($to) ||
        empty($subject) ||
        (empty($html) && empty($plain) ||
        !is_array($headers))) {
        error_log("attempted to send an empty or misconfigured message");
        return false;
    }

    $nameAndAddressRegex = '/(.*)<(.*)>/';
    $toIncludesNameAndAddress = preg_match($nameAndAddressRegex, $to, $toDetails);

    if ($toIncludesNameAndAddress) {
        $toName = $toDetails[1];
        $toAddress = $toDetails[2];
    } else {
        $toName = $to;
        $toAddress = $to;
    }

    // If no 'From' address specified, use default
    if (empty($headers['From'])) {
        $fromName = DEFAULT_SENDER;
        $fromAddress = DEFAULT_SENDER;
    } else {
        $fromIncludesNameAndAddress = preg_match($nameAndAddressRegex, $headers['From'], $fromDetails);
        if ($fromIncludesNameAndAddress) {
            $fromName = str_replace('"', '', $fromDetails[1]);
            $fromAddress = str_replace(' ', '-', $fromDetails[2]);
        } else {
            $fromName = $headers['From'];
            $fromAddress = str_replace(' ', '-', $headers['From']);
        }
    }

    if (!empty($html)) {
        if (empty($plain)) {
            $h2t = new Html2Text(html_entity_decode($html, ENT_QUOTES), 75);
            $plain = $h2t->convert();
        }
    } else {
        if (empty($plain)) {
            // if both HTML & Plain bodies are empty, don't send mail
            return false;
        }
    }

    $curl = new CURLHandler();
    $postArray = array(
        'from' => $fromAddress,
        'fromname' => $fromName,
        'to' => $toAddress,
        'toname' => $toName,
        'subject' => $subject,
        'html' => $html,
        'text'=> $plain,
        'api_user' => SENDGRID_API_USER,
        'api_key' => SENDGRID_API_KEY
    );

    if (!empty($headers['Reply-To'])) {
        $replyToIncludesNameAndAddress = preg_match($nameAndAddressRegex, $headers['Reply-To'], $replyToDetails);
        if ($replyToIncludesNameAndAddress) {
            $postArray['replyto'] = str_replace(' ', '-', $replyToDetails[2]);
        } else {
            $postArray['replyto'] = $headers['Reply-To'];
        }
    }
// check for copy, using bcc since cc is not present in Sendgrid api
    if (!empty($headers['Cc'])) {
        $ccIncludesNameAndAddress = preg_match($nameAndAddressRegex, $headers['Cc'], $ccDetails);
        if ($ccIncludesNameAndAddress) {
            $postArray['bcc'] = str_replace(' ', '-', $ccDetails[2]);
        } else {
            $postArray['bcc'] = $headers['Cc'];
        }
    }

    try {
        $result = CURLHandler::Get(SENDGRID_API_URL, $postArray);
    } catch(Exception $e) {
        error_log("[ERROR] Unable to send message through SendGrid API - Exception: " . $e->getMessage());
        return false;
    }

    return true;
}
/*  sendTemplateEmail - send email using email template
 *  $template - name of the template to use, for example 'confirmation'
 *  $data - array of key-value replacements for template
 */
// TODO - Marco - Include headers argument to allow ,eg, sending bcc copies
function sendTemplateEmail($to, $template, $data = array(), $from = false){
    include (dirname(__FILE__) . "/email/en.php");

    $recipients = is_array($to) ? $to : array($to);

    $replacedTemplate = !empty($data) ?
                        templateReplace($emailTemplates[$template], $data) :
                        $emailTemplates[$template];

    $subject = $replacedTemplate['subject'];
    $html = $replacedTemplate['body'];
    $plain = !empty($replacedTemplate['plain']) ?
                $replacedTemplate['plain'] :
                null;
    $xtag  = !empty($replacedTemplate['X-tag']) ?
                $replacedTemplate['X-tag'] :
                null;

    $headers = array();
    if (!empty($xtag)) {
        $headers['X-tag'] = $xtag;
    }
    if (!empty($from)) {
        $headers['From'] = $from;
    }

    $result = null;
    foreach($recipients as $recipient){
        if (! $result = send_email($recipient, $subject, $html, $plain, $headers)) {
            error_log("send_email:Template: send_email failed");
        }
    }

    return $result;
}

/* templateReplace - function to replace all occurencies of
 * {key} with value from $replacements array
 * for example: if $replacements is array('nickname' => 'John')
 * function will replace {nickname} in $templateData array with 'John'
 */

function templateReplace($templateData, $replacements){

    foreach($templateData as &$templateIndice){
        foreach($replacements as $find => $replacement){

            $pattern = array(
                        '/\{' . preg_quote($find) . '\}/',
                        '/\{' . preg_quote(strtoupper($find)) . '\}/',
                            );
            $templateIndice = preg_replace($pattern, $replacement, $templateIndice);
        }
    }

    return $templateData;
}

function getStats($req = 'table', $interval = 30) {
    if( $req == 'currentlink' ) {
        $query_b = mysql_query( "SELECT status FROM ".WORKLIST." WHERE status = 'Bidding'" );
        $query_w = mysql_query( "SELECT status FROM ".WORKLIST." WHERE status = 'Working' or status = 'Review' or status = 'Functional'" );
        $count_b = mysql_num_rows( $query_b );
        $count_w = mysql_num_rows( $query_w );
        return array(
                            'count_b' => $count_b,
                            'count_w' => $count_w
                            );

    } else if( $req == 'Bidding' ) {
        $query_b = mysql_query("SELECT id FROM ".WORKLIST." WHERE status = 'Bidding'");
        $results_b = array();
        while ($row = mysql_fetch_array($query_b, MYSQL_NUM)) {
            $results_b[] = $row[0];
        }
        return $results_b;

    } else if( $req == 'current' ) {
        $query_b = mysql_query("SELECT status FROM ".WORKLIST." WHERE status = 'Bidding'");
        $query_w = mysql_query("SELECT status FROM ".WORKLIST." WHERE status = 'Working'");
        $count_b = mysql_num_rows( $query_b );
        $count_w = mysql_num_rows( $query_w );
        $res = array( $count_b, $count_w );
        return $res;

    } else if( $req == 'fees' ) {
        // Get Average Fees in last 7 days
        $query = mysql_query( "SELECT AVG(amount) FROM ".FEES." LEFT JOIN ".WORKLIST." ON
                    ".FEES.".worklist_id = ".WORKLIST.".id WHERE date > DATE_SUB(NOW(),
                    INTERVAL 7 DAY) AND status = 'Done' AND `" . FEES . "`.`withdrawn` = 0" );

        $rt = mysql_fetch_assoc( $query );
        return $rt;

    } else if( $req == 'feeslist' ) {
        // Get Fees by person in last X days
        $interval = $interval ? $interval : 30;
        $query = mysql_query("SELECT nickname, SUM(amount) as total FROM ".FEES." ".
                    "LEFT JOIN ".WORKLIST." ON ".FEES.".worklist_id = ".WORKLIST.".id ".
                    "LEFT JOIN ".USERS." ON ".FEES.".user_id = ".USERS.".id ".
                    "WHERE date >= DATE_SUB(NOW(), INTERVAL $interval DAY) AND status = 'Done' AND `" . FEES . "`.`withdrawn` = 0 ".
                    "GROUP BY user_id ORDER BY total DESC");

        $tmpList = array();
        $feeList = array();
        while ($query && ($rt = mysql_fetch_assoc($query))) {
            $tmpList[] = array($rt['nickname'], $rt['total']);
        }

        $total = 0;
        for ($i = 0; $i < count($tmpList); $i++) {
            $total += $tmpList[$i][1];
        }
        $top10 = 0;
        for ($i = 0; $i < 10 && $i < count($tmpList); $i++) {
            $top10 += $tmpList[$i][1];
            $feeList[$i] = $tmpList[$i];
            $feeList[$i][2] = number_format($tmpList[$i][1] * 100 / $total, 2);
        }
        if (count($tmpList) > 10) {
            $feeList[10] = array('Other', number_format($total - $top10, 2), number_format(($total - $top10) * 100 / $total, 2));
        }
        return $feeList;

    } else if( $req == 'table' ) {
        // Get jobs done in last 7 days
        $fees_q = mysql_query( "SELECT `".WORKLIST."`.`id`,`summary`,`nickname` as nick,
                      (SELECT SUM(`amount`) FROM `".FEES."`
                       LEFT JOIN `".BIDS."` ON `".FEES."`.`bid_id`=`".BIDS."`.id
                       LEFT JOIN `".USERS."` ON `".USERS."`.`id`=`".FEES."`.`user_id`
                       WHERE `".BIDS."`.`worklist_id`=`".WORKLIST."`.`id`
                       AND `".USERS."`.`nickname`=`nick`) AS total,
                    TIMESTAMPDIFF(SECOND,`bid_done`,NOW()) as `delta`,`user_paid`
                    FROM `".BIDS."`
                    LEFT JOIN `".USERS."` ON `".BIDS."`.`bidder_id` = `".USERS."`.`id` LEFT JOIN `".WORKLIST."`
                    ON `".BIDS."`.`worklist_id` = `".WORKLIST."`.`id`
                    LEFT JOIN `".FEES."` ON `".FEES."`.`bid_id`=`".BIDS."`.`id`
                    WHERE `status`='Done'
                    AND `bid_done` > DATE_SUB(NOW(), INTERVAL 7 DAY)
                    AND `accepted`='1'
                    ORDER BY `delta` ASC;" );
        $fees = array();
        // Prepare json
        while( $row = mysql_fetch_assoc( $fees_q ) )    {
            $fees[] = array( $row['id'], $row['summary'], $row['nick'], $row['total'], $row['delta'], $row['user_paid'] );
        }

        return $fees;

    } else if( $req == 'runners' )  {
        // Get Top 10 runners
        $info_q = mysql_query( "SELECT nickname AS nick, (SELECT COUNT(*) FROM ".FEES." LEFT JOIN ".USERS." ON
                    ".USERS.".id = ".FEES.".user_id WHERE ".USERS.".nickname=nick AND
                    ".USERS.".is_runner=1) AS fee_no, (SELECT COUNT(*) FROM ".FEES." LEFT JOIN
                    ".USERS." ON ".USERS.".id=".FEES.".user_id LEFT JOIN ".WORKLIST." ON
                    ".WORKLIST.".id=".FEES.".worklist_id WHERE ".WORKLIST.".status='Working'
                    AND ".USERS.".nickname=nick) AS working_no FROM ".USERS." ORDER BY fee_no DESC" );

        $info = array();
        // Get user nicknames
        while( $row = mysql_fetch_assoc( $info_q ) )    {
            if( count( $info ) < 10 )   {
                if( !empty( $row['nick'] ) )    {
                    $info[] = array( $row['nick'],$row['fee_no'],$row['working_no'] );
                }
            }
        }
        return $info;

    } else if( $req == 'mechanics' ) {
        // Get Top 10 mechanics
        $info_q = mysql_query( "SELECT nickname AS nick, (SELECT COUNT(*) FROM ".BIDS." LEFT JOIN ".USERS." ON
                    ".USERS.".id = ".BIDS.".bidder_id WHERE ".USERS.".nickname=nick
                    AND `".BIDS."`.`accepted`='1') AS bid_no,
                    (SELECT COUNT(*) FROM ".WORKLIST." LEFT JOIN ".USERS." ON
                    ".WORKLIST.".mechanic_id=".USERS.".id WHERE ".USERS.".nickname=nick AND
                    ".WORKLIST.".status='Working') AS work_no FROM ".USERS." ORDER BY work_no DESC" );

        $info = array();
        // Get user nicknames
        while( $row = mysql_fetch_assoc( $info_q ) )    {
            if( count( $info ) < 10 )   {
                if( !empty( $row['nick'] ) )    {
                    $info[] = array( $row['nick'],$row['bid_no'],$row['work_no'] );
                }
            }
        }
        return $info;

    } else if( $req == 'feeadders' ) {
        // Get the top 10 fee adders
        $info_q = mysql_query( "SELECT nickname AS nick,(SELECT COUNT(*) FROM ".FEES." LEFT JOIN ".USERS." ON
                    ".USERS.".id = ".FEES.".user_id WHERE ".USERS.".nickname=nick) AS fee_no,
                    (SELECT AVG(amount) FROM ".FEES." LEFT JOIN ".USERS." ON
                    ".USERS.".id=".FEES.".user_id WHERE ".USERS.".nickname=nick) AS amount
                    FROM ".USERS." ORDER BY fee_no DESC" );

        $info = array();
        while( $row = mysql_fetch_assoc( $info_q ) )    {
            if( count( $info ) < 10 )   {
                if( !empty( $row['nick'] ) )    {
                    $info[] = array( $row['nick'],$row['fee_no'],$row['amount'] );
                }
            }
        }
        return $info;

    } else if( $req == 'pastdue' ) {
        // Get the top 10 mechanics with "Past due" fees
        $info_q = mysql_query( "SELECT nickname AS nick,(SELECT COUNT(*) FROM ".BIDS." LEFT JOIN ".USERS." ON
                    ".USERS.".id=".BIDS.".bidder_id LEFT JOIN ".WORKLIST." ON
                    ".WORKLIST.".id=".BIDS.".worklist_id WHERE ".USERS.".nickname=nick
                    AND ".WORKLIST.".status='Working' AND `".BIDS."`.`accepted`='1'
                    AND bid_done < NOW()) AS past_due
                    FROM ".USERS." ORDER BY past_due DESC" );

        $info = array();
        while( $row = mysql_fetch_assoc( $info_q ) )    {
            if( count( $info ) < 10 )   {
                if( !empty( $row['nick'] ) )    {
                    $info[] = array( $row['nick'],$row['past_due'] );
                }
            }
        }
        return $info;
    }
}

function saveAdmin($pass, $oldpass = '') {
    if (checkAdmin($oldpass) == '1') {
        $sql = "UPDATE ".PAYPAL_ADMINS." SET `password` = '".md5($pass)."' WHERE (password = '".md5($oldpass)."')";
    } else {
        $sql = "";
    }

    if ($sql != "") {
        $result = mysql_query($sql);
        return true;
    } else {
        return false;
    }
}

function checkAdmin($pass) {
    //checks admin login.
    $sql = "SELECT * FROM ".PAYPAL_ADMINS." WHERE `password` = '".md5($pass)."'";
    $result = mysql_query($sql);
    //if successful, this will be 1, otherwise 0
    return mysql_num_rows($result);
}
