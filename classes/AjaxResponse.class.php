<?php

class AjaxResponse
{
    public function __construct($chat)
    {
        $this->chat = $chat;
    }
    public function noaction() {
        return array('error' => 'no action sent');
    }
    public function botnames(){
        return $this->chat->getBotNames();
    }
	/* Geramy Editted from line 34 to line 118 on 04/29/2010 */
    public function send()
    {
        $message = $_POST['message'];
        $this->logTries();

        if (empty($_SESSION['nickname']) && $this->areUrlsInMessage($message)) {
            $message = "@me echo You cannot send a message with a web address if you are not logged in.";
        } elseif (isSpammer($_SERVER['REMOTE_ADDR'])) {
            $message = "@me echo Love to you! Sorry but you aren't allowed to participate. Your IP has been blocked."
                ." If you aren't a spammer please give us a feedback with the button above.";
        } elseif (empty($_SESSION['nickname']) && (substr($message, 0, 3) == '@me' ||
            substr($message, 0, 5) == '@ping')) {
            $message = "@me echo Sorry but for you to use any of the chat commands, you need to be logged in.";
        }

        $author = isset($_SESSION['nickname']) ? $_SESSION['nickname'] : GUEST_NAME;
        $sampled = isset($_POST['sampled']) ? $_POST['sampled'] : 0;
        $data = $this->chat->sendEntry($author, $message, array('sampled'=>$sampled));
        if (!isset($data['html'])) {
            $data = array_merge($data, $this->latest_longpoll($data['messagetime']));
        }
        return($data);
    }

	public function updateAllEntryJobs() {
		$this->chat->updateAllJobIds();
		echo "<br/><br/>Jobs per Entry table updated!";
	}

	public function blockip()
	{
	    if ($_SESSION['is_runner'] == 0) {
	        return(array(
	            'success' => false,
	            'message' => 'You are not allowed to do that!'
	        ));
	    }

	    if (empty($_REQUEST['entryid']) || empty($_REQUEST['hours'])) {
	        return(array(
	            'success' => false,
	            'message' => 'Not enough information!'
	        ));
	    }

	    $id = (int)$_REQUEST['entryid'];
	    $hours = (int)$_REQUEST['hours'];
	    $secs = ($hours * 3600);

	    $sql = 'SELECT `ip` FROM `'.ENTRIES.'` WHERE `id` = "' . $id . '";';
	    $result = mysql_query($sql);

	    if (!$result) {
	        return(array(
	            'success' => false,
	            'message' => 'Database error: ' . mysql_error()
	        ));
	    }
	    $ip = mysql_fetch_assoc($result);

		if( $ip== $_SERVER['REMOTE_ADDR'] ) {
	        return(array(
	            'success' => false,
	            'message' => 'You cannot block your own IP!'
	        ));
		}

	    $sql = 'INSERT INTO `' . BLOCKED_IP . '` VALUES (NULL, "' . $ip['ip'] . '", UNIX_TIMESTAMP(NOW()), ' . $secs . ');';
	    $result = mysql_query($sql);
	    if (!$result) {
	        return(array(
	            'success' => false,
	            'message' => 'Database error: ' . mysql_error()
	        ));
	    }

        $days = (int)($hours / 24);
        $hours = (($days == 0) ? $hours : (int)($hours % $days));

	    return(array(
	        'success' => true,
	        'message' => 'IP: '.$ip['ip'].' successfully blocked',
	        'info' => $sql
	    ));
	}
	public function unblockip()
	{
	    if ($_SESSION['is_runner'] == 0) {
	        return(array(
	            'success' => false,
	            'message' => 'You are not allowed to do that!'
	        ));
	    }

	    if (empty($_REQUEST['ipv4'])) {
	        return(array(
	            'success' => false,
	            'message' => 'Not enough information!'
	        ));
	    }

	    $ip = mysql_real_escape_string($_REQUEST['ipv4']);
	    $sql = 'DELETE FROM `' . BLOCKED_IP . '` WHERE `ipv4` = "' . $ip . '";';
	    $result = mysql_query($sql);
	    if (!$result) {
	        return(array(
	            'success' => false,
	            'message' => 'Database error: ' . mysql_error()
	        ));
	    }

	    $message = 'The IP (' . $ip . ') has been successfully unblocked.';

	    // Send entry
	    $info = $this->chat->sendEntry(USER_JOURNAL, $message, array('userid' => USER_JOURNAL_ID));

	    return(array(
	        'success' => true,
	        'message' => $message,
	        'info' => $info
	    ));
	}
	public function markspam()
	{
	    if ($_SESSION['is_runner'] == 0) {
	        return(array(
	            'success' => false,
	            'message' => 'You are not allowed to do that!'
	        ));
	    }

	    if (empty($_REQUEST['entryid'])) {
	        return(array(
	            'success' => false,
	            'message' => 'Not enough information!'
	        ));
	    }

	    $id = (int)$_REQUEST['entryid'];
	    $sql = 'UPDATE `'.ENTRIES.'` SET `visible` = 0 WHERE `id` = ' . $id . ';';
	    $result = mysql_query($sql);
	    if (!$result) {
	        return(array(
	            'success' => false,
	            'message' => 'Database error: ' . mysql_error()
	        ));
	    }
	    return(array(
	        'success' => true,
	        'message' => 'The entry has be marked as spam.'
	    ));
	}
	public function typing()
	{
		$data = $this->chat->setTypingStatus($_POST['status'], isset($_SESSION['userid']) ? $_SESSION['userid'] : 0);
		return($data);
	}
	public function earliest()
	{
		$data = $this->chat->getEarliestDate();
		return($data);
	}
	public function latest($toTime = null, $prevNext = null) {
		$count = (isset($_POST['count'])) ? (int)$_POST['count'] : 1;
		if ($count > 100) $count == 100;

		// see if we need to retrieve system messages
		$filter = isset($_POST['filter']) ? $_POST['filter'] : 'all';
		$query = isset($_POST['query']) ? $_POST['query'] : null;
		$exclude = '';
		if($filter == 'system'){
			$exclude = "WHERE ".$this->chat->getSystemWhere(1, 0);
		}

		$entries_result = $this->chat->loadEntries(0, array('query' => $query,
			'toTime' => $toTime,
			'prevNext' => $prevNext,
			'filter' => $filter,
			'system_count' => $count,
		  	'count' => $count,
			'query' => $query,
        ));
		$lastId = $entries_result['lastId'];
		$firstDate = $entries_result['firstDate'];
		$lastDate = $entries_result['lastDate'];
		$entries = $entries_result['entries'];
		$system_entries = $entries_result['system_entries'];

		/* Let bots handle entries.  These are history entries, so they can only skip entries. */
		if (!empty($_SESSION['nickname'])) {
	        	$tEntries = $entries;
		        $entries = array();
		        foreach ($tEntries as $entry) {
	        		$rsp = Bot::notifyOf('entry', array($_SESSION['nickname'], $entry));
				if ($rsp['status'] == 'skip') continue;
				$entries[] = $entry;
			}
		}

		$data = array();
		$count = count($entries);
		$system_count = count($system_entries);
		// use old way if json argument not defined for backwards compatibility
		if (empty($_POST["json"])) {
		    $html = $system_html = '';
		    if ($count > 0) $html = $this->chat->formatEntries($entries, $exclude);
		    if ($system_count > 0) $system_html = $this->chat->formatEntries(array_reverse($system_entries), null, false);
			$data = array('html'=>$html, 'system_html' => $system_html);
		} else {
			// new improved json way!
			$data = array('entries'=>$entries, 'system_entries' => $system_entries);
		}

		$data = array_merge($data, array('count'=>$count, 'system_count'=>$system_count, 'lastId'=>$lastId, 'firstDate'=>$firstDate, 'lastDate'=>$lastDate));
		return ($data);
	}

	// A-la danbrown (for interfacing with api.php to handle output for #13424)
	public function latestForNickname($nick,$num) {
		$sql = "SELECT * FROM ".ENTRIES." WHERE user_id IN (SELECT id FROM ".USERS." WHERE nickname='".mysql_real_escape_string($nick)."') ORDER BY id DESC LIMIT 0,".mysql_real_escape_string((int)$num);
		$result = mysql_query($sql) or die(mysql_error());

                $entries = array();
		while($row = mysql_fetch_assoc($result)) {
			$entries[] = $row;
		}

		$data = array();
                $count = count($entries);

                // use old way if json argument not defined for backwards compatibility
                if (empty($_POST["json"])) {
                    $html = $system_html = '';
                    if ($count > 0) $html = $this->chat->formatEntries($entries,null,false);
                    $data = array('html'=>$html, 'system_html' => null);
                } else {
                        // new improved json way!
                        $data = array('entries'=>$entries, 'system_entries' => null);
                }

                $data = array_merge($data, array('count'=>$count, 'system_count'=>0,));// 'lastId'=>$lastId, 'firstDate'=>$firstDate, 'lastDate'=>$lastDate));
                return $data;
	}

    public function latestFromTask($item_id) {

        // see if we need to retrieve system messages
        $filter = 'all';
        $query = (int) $item_id;
        $order = 'ASC';
        $reverse = null;
        $exclude = '';

        $entries_result = $this->chat->loadTaskEntries(0, array(
            'query' => $query,
            'toTime' => "UNIX_TIMESTAMP()",
            'prevNext' => 'prev',
            'filter' => $filter,
            'system_count' => 0,
            'count' => 0,
            'query' => $query,
            'order' => $order,
            'reverse' => $reverse
        ));
        $lastId = $entries_result['lastId'];
        $firstDate = $entries_result['firstDate'];
        $lastDate = $entries_result['lastDate'];
        $entries = $entries_result['entries'];
        $system_entries = $entries_result['system_entries'];

        /* Let bots handle entries.  These are history entries, so they can only skip entries. */
        if (!empty($_SESSION['nickname'])) {
            $tEntries = $entries;
            $entries = array();
            foreach ($tEntries as $entry) {
                $rsp = Bot::notifyOf('entry', array($_SESSION['nickname'], $entry));
                if ($rsp['status'] == 'skip') {
                    continue;
                }
                $entries[] = $entry;
            }
        }

        $data = array();
        $count = count($entries);
        $system_count = count($system_entries);
        // use old way if json argument not defined for backwards compatibility
        if (empty($_POST["json"])) {
            $html = $system_html = '';
            if ($count > 0) {
                $html = $this->chat->formatEntries($entries, $exclude);
            }
            if ($count == 0) {
                $html = '<div class="entry" id="entry-empty"><div class="entry-text">No entries found</div></div>';
            }
            if ($system_count > 0) {
                $system_html = $this->chat->formatEntries($system_entries, null, false);
            }
            if ($system_count == 0) {
                $system_html = '<div class="entry" id="entry-empty"><div class="entry-text">No entries found</div></div>';
            }
            $data = array('html'=>$html, 'system_html' => $system_html);
        } else {
            // new improved json way!
            $data = array('entries'=>$entries, 'system_entries' => $system_entries);
        }

        $data = array_merge($data, array('count' => $count, 'system_count' => $system_count, 'lastId' => $lastId, 'firstDate' => $firstDate, 'lastDate' => $lastDate));
        return ($data);
    }

    public function latest_longpoll($justupdated = false) {
        $count = (isset($_POST['count'])) ? (int) $_POST['count'] : 0;
        if ($count > 100) {
            $count = 100;
        }
        if (!$justupdated) {
            $timeout = (!empty($_POST['timeout'])) ? (int) $_POST['timeout'] : 20;
            $delay = 250; /* ms */
            $lastTouch = (isset($_POST['lasttouched'])) ? $_POST['lasttouched'] : 0;
            $touched = file_get_contents(JOURNAL_UPDATE_TOUCH_FILE);
            $i = 0;
            if ($lastTouch != 0) {
                while($touched == $lastTouch && ++$i < ($timeout * 1000) / $delay) {
                    usleep($delay * 1000);
                    $touched = file_get_contents(JOURNAL_UPDATE_TOUCH_FILE);
                }
            }
        } else {
            $touched = $justupdated;
        }
        // see if we need to retrieve system messages
        $filter = isset($_POST['filter']) ? $_POST['filter'] : 'all';
        $lastStatus = isset($_POST['laststatus']) ? $_POST['laststatus'] : '';
        $lastId = isset($_POST['lastid']) ? (int)$_POST['lastid'] : 0;
        $entries_result = $this->chat->loadEntries($lastId, array(
            'query' => '',
            'toTime' => '',
            'prevNext' => '',
            'filter' => $filter,
            'system_count' => $count,
            'count' => $count
        ), false);
//Garth in krumch task #13576 - don't log notices on empty results
        $entries = array_key_exists('entries',$entries_result)? $entries_result['entries']:array();

        $entry_count = count($entries);
        $system_entries = array_key_exists('system_entries',$entries_result)? $entries_result['system_entries']:array();
        $system_count = count($system_entries);
        if ($entry_count == 0 && $system_count == 0) {
            return array(
                'count' => 0,
                'lasttouched' => $touched,
                'updates'=> 0,
                'typingstatus' => $this->chat->getGlobalTypingStatus(),
            );
        }
        $lastId = $entries_result['lastId'];
        $firstDate = $entries_result['firstDate'];
        $lastDate = $entries_result['lastDate'];

        $last_private = !empty($_POST['last_private']) ? $_POST['last_private'] : null; // whether the client last displayed a private message
        if ($last_private == "null") {
            $last_private = null;
        }
        $data = Array('updates' => 1);
        // get the speakers
        //$data['speakers'] = $this->speakerList(1);
        //$entries = array_merge($entries, $this->speakerNotes($data['speakers']));

        $entries_array = array();
        // use old way if json argument not defined for backwards compatibility
        if (empty($_POST["json"])) {
            $html = $system_html = $newentries = $newsystementries = '';
            if($count > 0) {
                $html = $this->chat->formatEntries($entries, '', true, $last_private, true, true);
                $system_html = $this->chat->formatEntries(array_reverse($system_entries), null, false, null, true, true);
            } else {
                $newentries = $this->chat->formatEntries($entries, '', true, $last_private, 0, true);
                $newsystementries = $this->chat->formatEntries($system_entries, null, false, null, 0, true);
            }
            $entries_array = array('html'=>$html, 'system_html'=>$system_html, 'newentries'=>$newentries, 'newsystementries'=>$newsystementries);
        } else {
            // new improved json way!
            if ($count > 0) {
                $entries_array = array('entries'=>$entries, 'system_entries'=>$system_entries, 'newentries'=>'', 'newsystementries'=>'');
            } else {
                $entries_array = array('entries'=>'', 'system_entries'=>'', 'newentries'=>$entries, 'newsystementries'=>$system_entries);
            }
        }
        $botdata = array('ping' => 0, 'emergency' => 0, 'system' => 0);
        foreach ($entries as $entry) {
            if (!empty($entry['botdata']['ping'])) {
                $botdata['ping']++;
            } elseif (!empty($entry['botdata']['emergency'])) {
            $botdata['emergency']++;
        } elseif (!empty($entry['botdata']['system'])) {
            $botdata['system']++;
        }
    }
    $data = array_merge($data, $entries_array, array('count'=>$entry_count, 'system_count' => $system_count,
        'lastId'=>$lastId,'time'=>time(), 'firstDate'=>$firstDate,'lastDate'=>$lastDate,'botdata'=>$botdata));

    $data['lasttouched'] = $touched;

    // We want typing notification to have low latency:
    $data['typingstatus'] = $this->chat->getGlobalTypingStatus();
    return $data;
    }

	function speakerNotes($speakers)
	{
		$entries = array();
	    /* Watch for new speakers to come online so we can handle @me notify watches. */
	    if (!empty($_SESSION['nickname'])) {
	        $rsp = Bot::notifyOf('speaker', array($_SESSION['nickname'], $speakers));
	        if ($rsp['status'] != 'ignore') {
	            $entries[] = $rsp['entry'];
	        }
	    }
		return $entries;
	}
	function speakerList($justSpeakers = false)
	{
	    $speakers = $this->chat->listSpeakers();
		if ($justSpeakers)
		{
			return $speakers;
		}
	    $entries = $this->speakerNotes($speakers);
	    $data=array();
	    $data['time'] = time();
	    $data['speakers'] = $speakers;
		$data['current_user'] = !empty($_SESSION['userid']) ? $_SESSION['userid'] : 0;
		//$data['entries'] =$entries;
		return $data;
	}
	function time()
	{
	    $toTime = (isset($_POST['time'])) ? (int)$_POST['time'] : time();
	    $prevNext = (isset($_POST['prevnext'])) ? $_POST['prevnext'] : '';
	    /* Fall through */
			return $this->latest($toTime, $prevNext);
	}
	function speakeronline()
	{
        $userid = isset($_SESSION['userid']) ? $_SESSION['userid'] : 0;
		$idle = $this->chat->addSpeaker($userid);
        $message = ($idle) ? "Currently idle <a class=\"idleback\">come back from idle</a>" : '';
        return array('idle' => $idle, 'message' => $message);
	}
	function isspeakeraway()
	{
        $nickname = isset($_SESSION['nickname']) ? $_SESSION['nickname'] : false;
        if(!$nickname) {
            array('away' => false);
        }
		$check = $this->chat->isSpeakerAway($nickname);
		$away = !empty($check['away']);
        $message = "Currently away {$check['link']}";
        return array('away' => $away, 'message' => $message);
	}
	function speakeroffline()
	{
	 	$this->chat->offlineSpeaker($_POST['author_id']);
		return array();
	}
	function speakerunidle()
	{
        $userid = isset($_SESSION['userid']) ? $_SESSION['userid'] : 0;
	 	$this->chat->unidleSpeaker($userid);
		return array();
	}
	public function saveSample()
	{
        $this->chat->saveSample($_POST['start_time'], $_POST['receive_time']);
        return array();
    }
    private function logTries()
    {
		/* Log retries so we can monitor performance */
		$tries = (!empty($_POST['tries'])) ? (int)$_POST['tries'] : 0;
		if ($tries > 0) {
			$fp = fopen("/tmp/journal_retries.log", "a");
			fwrite($fp, time().":".$tries.":".$_POST['author'].":".$message."\n");
			fclose($fp);
		}
    }
    private function areUrlsInMessage($message)
    {
        if (
			strpos($message, 'http') > -1 || strpos($message, 'http://') > -1 || strpos($message, '.com') > -1 ||
			strpos($message, '.net') > -1 || strpos($message, '.org') > -1 || strpos($message, '.info') > -1 ||
			strpos($message, '.biz') > -1 || strpos($message, '.me') > -1 || strpos($message, 'www.') > -1 ||
			strpos($message, '.us') > -1 || strpos($message, '.edu') > -1 || strpos($message, '.uk') > -1 || strpos($message, '.to') > -1 ||
			strpos($message, '.ch') > -1 || strpos($message, '.fr') > -1 || strpos($message, '.jp') > -1 ||
			strpos($message, '.de') > -1 || strpos($message, '.ru') > -1 || strpos($message, '.it') > -1 ||
			strpos($message, '.mil') > -1 || strpos($message, '.gov') > -1 || strpos($message, '.au') > -1 || strpos($message, '.cc') > -1 ||
			strpos($message, '.ca') > -1 || strpos($message, '.coop') > -1 || strpos($message, '.dk') > -1 || strpos($message, '.bt') > -1 ||
			strpos($message, '.at') > -1 || strpos($message, '.as') > -1 || strpos($message, '.az') > -1 || strpos($message, '.be') > -1 ||
			strpos($message, '.cn') > -1 || strpos($message, '.ac') > -1 || strpos($message, '.af') > -1 || strpos($message, '.al') > -1 ||
			strpos($message, '.am') > -1 || strpos($message, '.cx') > -1 || strpos($message, '.cz') > -1 || strpos($message, '.dz') > -1 ||
			strpos($message, '.ec') > -1 || strpos($message, '.ee') > -1 || strpos($message, '.eg') > -1 || strpos($message, '.es') > -1 ||
			strpos($message, '.fo') > -1 || strpos($message, '.ga') > -1 || strpos($message, '.gf') > -1 || strpos($message, '.gl') > -1 ||
			strpos($message, '.gr') > -1 || strpos($message, '.gs') > -1 || strpos($message, '.hk') > -1 || strpos($message, '.il') > -1 ||
			strpos($message, '.in') > -1 || strpos($message, '.io') > -1 || strpos($message, '.is') > -1 || strpos($message, '.li') > -1 ||
			strpos($message, '.lu') > -1 || strpos($message, '.ly') > -1 || strpos($message, '.kr') > -1 || strpos($message, '.kz') > -1 ||
			strpos($message, '.mc') > -1 || strpos($message, '.mm') > -1 || strpos($message, '.ms') > -1 || strpos($message, '.mx') > -1 ||
			strpos($message, '.nl') > -1 || strpos($message, '.no') > -1 || strpos($message, '.nu') > -1 || strpos($message, '.nz') > -1 ||
			strpos($message, '.pl') > -1 || strpos($message, '.pt') > -1 || strpos($message, '.ro') > -1 || strpos($message, '.se') > -1 ||
			strpos($message, '.sg') > -1 || strpos($message, '.sh') > -1 || strpos($message, '.sk') > -1 || strpos($message, '.so') > -1 ||
			strpos($message, '.st') > -1 || strpos($message, '.tc') > -1 || strpos($message, '.tf') > -1 || strpos($message, '.th') > -1 ||
			strpos($message, '.tj') > -1 || strpos($message, '.tm') > -1)
	    {
	        return true;
	    }
	    return false;
    }
}

?>
