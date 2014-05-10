<?php

/**
 * This class is responsible for working with notification
 * subscriptions
 */
class Notification {
    const SELF_EMAIL_NOTIFICATIONS = 64;
    const BIDDING_EMAIL_NOTIFICATIONS = 256;
    const REVIEW_EMAIL_NOTIFICATIONS = 512;
    const MY_AUTOTEST_NOTIFICATIONS = 1024;

    /**
     *  Sets flags using list of integers passed as arguments
     *
     * @return resulting integer with combined flags
     */
    public static function setFlags() {
            $result = 0;
            foreach(func_get_args() as $flag) {
                $result = $result | $flag;
            }
            return $result;
    }

    /**
     * Check if given flag is set for value
     *
     * @param $value value to check against
     * @param $flag flag to check
     * @return boolean returns true if given flag is set for value
     */
    public static function isNotified($value, $flag = self::REVIEW_EMAIL_NOTIFICATIONS) {
            $result = ($value & $flag) === $flag;
            return $result;
    }

    /**
     * Get list of users who have given flag set
     * List is returned only for active users!
     * This flags are for SMS notifications only
     * @param $flag flag to compare with
     * @return list of users with given flag
     */
    public static function getNotificationEmails($flag = self::REVIEW_EMAIL_NOTIFICATIONS, $workitem = 0) {

        $result = array();

        switch($flag) {
        case self::REVIEW_EMAIL_NOTIFICATIONS :
        case self::BIDDING_EMAIL_NOTIFICATIONS :
            $uid = getSessionUserId();
            $sql = "SELECT u.username
                FROM `" . USERS . "` u
                WHERE ((u.notifications & " . self::REVIEW_EMAIL_NOTIFICATIONS . " != 0 && u.id != " . $uid . ")
                      OR ((u.notifications & " . self::BIDDING_EMAIL_NOTIFICATIONS . " != 0 && u.id != " . $uid . ") AND (u.notifications & " . self::SELF_EMAIL_NOTIFICATIONS . ") != 0 && u.id = " . $uid . "))
                  AND u.is_active = 1";
            $res = mysql_query($sql);
            if($res) {
                while($row = mysql_fetch_row($res)) {
                    $result[] = $row[0];
                }
            }
            break;

        case self::MY_AUTOTEST_NOTIFICATIONS:
            $users=implode(",", array($workitem->getCreatorId(), $workitem->getRunnerId(), $workitem->getMechanicId()));
            $sql = "SELECT u.username
                FROM `" . USERS . "` u
                WHERE u.id IN({$users})
                  AND u.is_active = 1";
            $res = mysql_query($sql);
            if($res) {
                while($row = mysql_fetch_row($res)) {
                    $result[] = $row[0];
                }
            }
            break;
        }
        return $result;
    }

    /**
     * Notifications for workitem statuses
     *
     * @param Object $workitem instance of a Workitem class
     */
    public static function statusNotify($workitem) {
        switch($workitem->getStatus()) {
            case 'Review':
                $emails = self::getNotificationEmails(self::REVIEW_EMAIL_NOTIFICATIONS);
                $options = array('type' => 'new_review',
                    'workitem' => $workitem,
                    'emails' => $emails);
                self::workitemNotify($options);
            break;
            case 'Bidding':
                $emails = self::getNotificationEmails(self::BIDDING_EMAIL_NOTIFICATIONS);
                $options = array('type' => 'new_bidding',
                    'workitem' => $workitem,
                    'emails' => $emails);
                self::workitemNotify($options);
                break;
        }
    }

    /**
     * Async wrapper to Notification::statusNotify to avoid big delays
     * on massive notifications.
     *
     * @param object $workitem instance of a Workitem class
     */
    function massStatusNotify($workitem) {
        return CURLHandler::Post(
            SERVER_URL . 'api.php',
            array(
                'action' => 'sendNotifications',
                'api_key' => API_KEY,
                'command' => 'statusNotify',
                'workitem' => $workitem->getId()
            ),
            false,
            false,
            true
        );
    }

    /**
     *  This function notifies selected recipients about updates of workitems
     * except for currently logged in user
     *
     * @param Array $options - Array with options:
     * type - type of notification to send out
     * workitem - workitem object with updated data
     * recipients - array of recipients of the message ('creator', 'runner', 'mechanic')
     * emails - send message directly to list of emails (array) -
     * if 'emails' is passed - 'recipients' option is ignored
     * @param Array $data - Array with additional data that needs to be passed on
     * @param boolean $includeSelf - force user receive email from self generated action
     * example: 'who' and 'comment' - if we send notification about new comment
     */
    public static function workitemNotify($options, $data = null, $includeSelf = true) {

        $recipients = isset($options['recipients']) ? $options['recipients'] : null;
        $emails = isset($options['emails']) ? $options['emails'] : array();

        $workitem = $options['workitem'];
        $userstats = isset($options['userstats']) ? $options['userstats'] : null;

        if (isset($options['project_name'])) {
            $project_name = $options['project_name'];
        } else {
            try {
                $project = new Project();
                $project->loadById($workitem->getProjectId());
                $project_name = $project->getName();
            } catch (Exception $e) {
                error_log($e->getMessage() . " Workitem: #" . $workitem->getId() . " " . " has an invalid project id:" . $workitem->getProjectId());
                $project_name = "";
            }

        }

        $revision = isset($options['revision']) ? $options['revision'] : null;

        $itemId = $workitem -> getId();
        $itemLink = '<a href="' . WORKLIST_URL . $itemId . '"">#' . $itemId . '</a>';
        $itemTitle = '#' . $itemId  . ' (' . $workitem -> getSummary() . ')';
        $itemTitleWithProject = '#' . $itemId  . ': ' . $project_name . ': (' . $workitem -> getSummary() . ')';
        $body = '';
        $subject = '#' . $itemId . ' ' . html_entity_decode($workitem -> getSummary(), ENT_QUOTES);
        $from_address = '<noreply-'.$project_name.'@worklist.net>';
        $headers=array('From' => '"'.$project_name.'-'.strtolower( $workitem -> getStatus() ).'" '.$from_address);
        switch ($options['type']) {
            case 'comment':
                $headers['From'] = '"' . $project_name . '-comment" ' . $from_address;
                $headers['Reply-To'] = '"' . $_SESSION['nickname'] . '" <' . $_SESSION['username'] . '>';
                $body  = 'New comment was added to the item ' . $itemLink . '.<br>';
                $body .= $data['who'] . ' says:<br />'
                      . $data['comment'] . '<br /><br />'
                      . 'Project: ' . $project_name . '<br />'
                      . 'Creator: ' . $workitem->getCreator()->getNickname() . '<br />';
                      if($workitem->getRunner() != '') {
                          $body .= 'Designer: ' . $workitem->getRunner()->getNickname() . '<br />';
                      }
                      if($workitem->getMechanic() != '') {
                          $body .= 'Developer: ' . $workitem->getMechanic()->getNickname()  . '<br /><br />';
                      }
                $body .= 'Notes: ' . $workitem->getNotes() . '<br /><br />'
                . 'You can view the job <a href="' . WORKLIST_URL . $itemId . '">here</a>.' . '<br /><br />'
                . '<a href="' . SERVER_URL . '">www.worklist.net</a>' ;
            break;

            case 'fee_added':
                if ($workitem->getStatus() != 'Draft') {
                $headers['From'] = '"' . $project_name . '-fee added" ' . $from_address;
                $body = 'New fee was added to the item ' . $itemLink . '.<br>'
                        . 'Who: ' . $data['fee_adder'] . '<br/>'
                        . 'Amount: ' . $data['fee_amount'] . '<br/>'
                        . '<div>Fee Notes: ' . $data['fee_desc'] . '</div><br/><br/>'
                        . 'Project: ' . $project_name . '<br/>'
                        . 'Creator: ' . $workitem->getCreator()->getNickname() . '<br />';
                        if($workitem->getRunner() != '') {
                            $body .= 'Designer: ' . $workitem->getRunner()->getNickname() . '<br />';
                        }
                        if($workitem->getMechanic() != '') {
                            $body .= 'Developer: ' . $workitem->getMechanic()->getNickname()  . '<br /><br />';
                        }
                $body .= 'Notes: ' . $workitem->getNotes() . '<br /><br />'
                . 'You can view the job <a href="' . WORKLIST_URL . $itemId . '">here</a>.' . '<br /><br />'
                . '<a href="' . SERVER_URL . '">www.worklist.net</a>' ;
                }
            break;

            case 'fee_deleted':
                if ($workitem->getStatus() != 'Draft') {
                    $headers['From'] = '"' . $project_name . '-fee deleted" ' . $from_address;
                    $body = "<p>Your fee has been deleted by: ".$_SESSION['nickname']."<br/><br/>";
                    $body .= "If you think this has been done in error, please contact the job Designer.</p>";
                    $body .= 'Project: ' . $project_name . '<br />'
                        . 'Creator: ' . $workitem->getCreator()->getNickname() . '<br />';
                    if($workitem->getRunner() != '') {
                        $body .= 'Designer: ' . $workitem->getRunner()->getNickname() . '<br />';
                    }
                    if($workitem->getMechanic() != '') {
                        $body .= 'Developer: ' . $workitem->getMechanic()->getNickname()  . '<br /><br />';
                    }
                    $body .= 'Notes: ' . $workitem->getNotes() . '<br /><br />'
                    . 'You can view the job <a href="' .  WORKLIST_URL . $itemId . '">here</a>.' . '<br /><br />'
                    . '<a href="' . SERVER_URL . '">www.worklist.net</a>' ;
                }
            break;

            case 'tip_added':
                $headers['From'] = '"' . $project_name . '-tip added" ' . $from_address;
                $body = $data['tip_adder'] . ' tipped you $' . $data['tip_amount'] . ' on job ' . $itemLink . ' for:<br><br>' . $data['tip_desc'] . '<br><br>Yay!' . '<br /><br />'
                . 'Project: ' . $project_name . '<br />'
                        . 'Creator: ' . $workitem->getCreator()->getNickname() . '<br />';
                       if($workitem->getRunner() != '') {
                           $body .= 'Designer: ' . $workitem->getRunner()->getNickname() . '<br />';
                       }
                       if($workitem->getMechanic() != '') {
                           $body .= 'Developer: ' . $workitem->getMechanic()->getNickname()  . '<br /><br />';
                       }
                $body .= 'Notes: ' . $workitem->getNotes() . '<br /><br />'
                . 'You can view the job <a href="' . WORKLIST_URL . $itemId . '">here</a>.' . '<br /><br />'
                . '<a href="' . SERVER_URL . '">www.worklist.net</a>' ;
                break;

            case 'bid_accepted':
                $headers['From'] = '"' . $project_name . '-bid accepted" ' . $from_address;
                $body = 'Your bid was accepted for ' . $itemLink . '<br/><br />'
                        . 'If this job requires you to create code, please read through and then follow our coding '
                        . 'standards which are found <a href="https://github.com/highfidelity/hifi/wiki/Coding-Standard">here</a>.<br/><br/>'
                        . 'Promised by: ' . $_SESSION['nickname'] . '<br /><br />'
                        . 'Project: ' . $project_name . '<br />'
                        . 'Creator: ' . $workitem->getCreator()->getNickname() . '<br />';
                        if($workitem->getRunner() != '') {
                            $body .= 'Designer: ' . $workitem->getRunner()->getNickname() . '<br />';
                        }
                        if($workitem->getMechanic() != '') {
                            $body .= 'Developer: ' . $workitem->getMechanic()->getNickname()  . '<br /><br />';
                        }

                $body .= 'Notes: ' . $workitem->getNotes() . '<br /><br />'
                . 'The job can be viewed <a href="' . WORKLIST_URL . $itemId . '">here</a><br /><br />';

                // render the github branch-created-sub template if necessary
                if (!empty($data) && array_key_exists('branch_name', $data)) {
                    $template = 'branch-created-sub';
                    include(dirname(__FILE__) . "/../email/en.php");

                    $replacedTemplate = !empty($data) ?
                        templateReplace($emailTemplates[$template], $data) :
                        $emailTemplates[$template];

                    $body .= $replacedTemplate['body'];
                }

                $body .= '<br /><a href="' . SERVER_URL . '">www.worklist.net</a>';

            break;

            case 'bid_placed':
            	$userstats->setItemsPerPage(3);
            	$projectId = $workitem->getProjectId();;
            	$jobsInfo = $userstats->getUserItemsForASpecificProject('Done', $projectId);
            	$lastThreeJobs = $jobsInfo['joblist'];
            	$workItemUrl = '<a href="' . WORKLIST_URL;
            	//create the last three jobs and link them to those Jobs.
            	foreach ($lastThreeJobs as $row){
            		$jobs .= $workItemUrl;
            		$jobs .= $row['id'] . '">#' . $row['id'] . '</a>' . ' - ' . $row['summary'] . '<br />';
            	}
            	//if no Jobs then display 'None'
            	if (!$jobs){
            		$jobs = 'None <br />';
            	}

            	//now get total jobs and total jobs and create links
            	$totalJobs = $workItemUrl;
            	$totalJobs .= $workitem->getId() . '?action=view&userinfotoshow=' . $_SESSION['userid'] . '">' . $userstats->getTotalJobsCount() . '</a><br />';
            	$totalActiveJobs = $workItemUrl;
            	$totalActiveJobs .= $workitem->getId() . '?action=view&userinfotoshow=' . $_SESSION['userid'] . '">' . $userstats->getActiveJobsCount() . '</a><br />';

            	$headers['From'] = '"' . $project_name . '-new bid" ' . $from_address;
                $body =  'New bid was placed for ' . $itemLink . '<br /><br />'
                    . 'Amount: $' . number_format($data['bid_amount'], 2) . '<br />'
                    . 'Done In: ' . $data['done_in'] . '<br />'
                    . 'Expires: ' . $data['bid_expires'] . '<br /><br />'
                    . 'Bidder Name: <a href="mailto:' . $_SESSION['nickname'] . '">' . $_SESSION['nickname'] . '</a><br /><br />'
                    . 'Bidder Email: <a href="mailto:' . $_SESSION['username'] . '">' . $_SESSION['username'] . '</a><br /><br />'
                    . 'Notes: ' . $data['notes'] . '<br /><br />'
                    . 'Total Jobs: ' . $totalJobs
                    . 'Active Jobs: ' . $totalActiveJobs
                    . 'Last 3 Jobs for ' . $project_name . ':<br />'
                    . $jobs . '<br />'
                    . 'Project: ' . $project_name . '<br />'
                    . 'Creator: ' . $workitem->getCreator()->getNickname() . '<br />';
                    if($workitem->getRunner() != '') {
                        $body .= 'Designer: ' . $workitem->getRunner()->getNickname() . '<br />';
                    }
                    if($workitem->getMechanic() != '') {
                        $body .= 'Developer: ' . $workitem->getMechanic()->getNickname()  . '<br /><br />';
                    }
                $body .= 'Notes: '. $workitem->getNotes() . '<br /><br />';

                $urlAcceptBid  = '<br />' . $workItemUrl;
                $urlAcceptBid .= $itemId . '?bid_id=' . $data['bid_id'] . '&action=view_bid">Click here to accept bid.</a>';
                $body .=  $urlAcceptBid;
            break;

            case 'bid_updated':
                $headers['From'] = '"' . $project_name . '-bid updated" ' . $from_address;
                $body = 'Bid updated for ' . $itemLink . '<br /><br/>'
                    . 'Amount: $' . number_format($data['bid_amount'], 2) . '<br />'
                    . 'Done In: ' . $data['done_in'] . '<br />'
                    . 'Expires: ' . $data['bid_expires'] . '<br /><br />'
                    . 'Bidder Email: <a href="mailto:' . $_SESSION['username'] . '">' . $_SESSION['username'] . '</a><br /><br />'
                    . 'Notes: ' . $data['notes'] . '<br /><br />'
                    . 'Project: ' . $project_name . '<br />'
                        . 'Creator: ' . $workitem->getCreator()->getNickname() . '<br />';
                    if($workitem->getRunner() != '') {
                        $body .= 'Designer: ' . $workitem->getRunner()->getNickname() . '<br />';
                    }
                    if($workitem->getMechanic() != '') {
                        $body .= 'Developer: ' . $workitem->getMechanic()->getNickname()  . '<br /><br />';
                        }
                $body .= 'Notes: ' . $workitem->getNotes() . '<br /><br />';
                $urlacceptbid  = '<br /><a href="./';
                $urlacceptbid .= $itemId . '?bid_id=' . $data['bid_id'] .
                                 '&action=view_bid">Click here to accept bid.</a>';
                $body .=  $urlacceptbid;
            break;

            case 'bid_discarded':
                $headers['From'] = '"' . $project_name . '-bid not accepted" ' . $from_address;
                $body = "<p>Hello " . $data['who'] . ",</p>";
                $body .= "<p>Thanks for adding your bid to <a href='".WORKLIST_URL.$itemId."'>#".$itemId."</a> '" . $workitem -> getSummary() . "'. This job has just been filled by another developer.</br></p>";
                $body .= "There is lots of work to be done so please keep checking the <a href='".SERVER_URL."'>worklist</a> and bid on another job soon!</p>";
                $body .= "<p>Hope to see you in the Workroom soon. :)</p>";
            break;

            case 'modified-functional':
                $from_changes = "";
                if (!empty($options['status_change'])) {
                    $from_changes = $options['status_change'];
                }
                if (isset($options['job_changes'])) {
                    if (count($options['job_changes']) > 0) {
                        $from_changes .= $options['job_changes'][0];
                        if (count($options['job_changes']) > 1) {
                            $from_changes .= ' +other changes';
                        }
                    }
                }

                if ($from_changes) {
                    $headers['From'] = '"' . $project_name . $from_changes . '" ' . $from_address;
                } else {
                    $status_change = '-' . strtolower($workitem->getStatus());
                    $headers['From'] = '"' . $project_name . $status_change . '" ' . $from_address;
                }
                $body = $_SESSION['nickname'] . ' updated item ' . $itemLink . '<br>'
                    . $data['changes'] . '<br /><br />'
                    . 'Project: ' . $project_name . '<br />'
                    . 'Creator: ' . $workitem->getCreator()->getNickname() . '<br />'

                    . 'Designer: ';
                if ($workitem->getRunnerId()) $body .= $workitem->getRunner()->getNickname();
                $body.= '<br />'

                    . 'Developer: ';
                if ($workitem->getMechanicId()) $body .= $workitem->getMechanic()->getNickname();

                $body.= '<br /><br />'
                    . 'Notes: ' . $workitem->getNotes() . '<br /><br />'
                    . 'You can view the job <a href="' . WORKLIST_URL . $itemId . '">here</a>.' . '<br /><br />'
                    . '<a href="' . SERVER_URL . '">www.worklist.net</a>';
            break;

            case 'modified':
                if ($workitem->getStatus() != 'Draft') {
                    $from_changes = "";
                    if (!empty($options['status_change'])) {
                        $from_changes = $options['status_change'];
                    }
                    if (isset($options['job_changes'])) {
                        if (count($options['job_changes']) > 0) {
                            $from_changes .= $options['job_changes'][0];
                            if (count($options['job_changes']) > 1) {
                                $from_changes .= ' +other changes';
                            }
                        }
                    }
                    if (!empty($from_changes)) {
                        $headers['From'] = '"' . $project_name . $from_changes . '" ' . $from_address;
                    } else {
                        $status_change = '-' . strtolower($workitem->getStatus());
                        $headers['From'] = '"' . $project_name . $status_change . '" ' . $from_address;
                    }
                    $body = $_SESSION['nickname'] . ' updated item ' . $itemLink . '<br>'
                        . $data['changes'] . '<br /><br />'
                        . 'Project: ' . $project_name . '<br />'
                        . 'Creator: ' . $workitem->getCreator()->getNickname() . '<br />';
                    if($workitem->getRunner() != '') {
                        $body .= 'Designer: ' . $workitem->getRunner()->getNickname() . '<br />';
                    }
                    if($workitem->getMechanic() != '') {
                        $body .= 'Developer: ' . $workitem->getMechanic()->getNickname()  . '<br /><br />';
                    }
                    $body .= 'Notes: '. $workitem->getNotes() . '<br /><br />'
                        . 'You can view the job <a href="' . WORKLIST_URL . $itemId . '">here</a>.' . '<br /><br />'
                        . '<a href="' . SERVER_URL . '">www.worklist.net</a>' ;
                }
            break;

            case 'new_bidding':
                $body = "Summary: " . $itemLink . ' ' . $workitem->getSummary() . '<br /><br />'
                . 'Project: ' . $project_name . '<br />'
                . 'Creator: ' . $workitem->getCreator()->getNickname() . '<br />';
                if($workitem->getRunner() != '') {
                    $body .= 'Designer: ' . $workitem->getRunner()->getNickname() . '<br />';
                }
                if($workitem->getMechanic() != '') {
                   $body .= 'Developer: ' . $workitem->getMechanic()->getNickname()  . '<br /><br />';
                }
                $body .= 'Notes: ' . $workitem->getNotes() . '<br /><br />'
                . 'You are welcome to bid the job <a href="' . WORKLIST_URL . $itemId . '">here</a>.' . '<br /><br />'
                . '<a href="' . SERVER_URL . '">www.worklist.net</a>' ;
            break;

            case 'new_review':
                $body = "New item is available for review: " . $itemLink . ' ' . $workitem->getSummary() . '<br /><br />'
                . 'Project: ' . $project_name . '<br />'
                . 'Creator: ' . $workitem->getCreator()->getNickname() . '<br />';
                if($workitem->getRunner() != '') {
                    $body .= 'Designer: ' . $workitem->getRunner()->getNickname() . '<br />';
                }
                if($workitem->getMechanic() != '') {
                    $body .= 'Developer: ' . $workitem->getMechanic()->getNickname()  . '<br /><br />';
                }
                $body .= 'Notes: ' . $workitem->getNotes() . '<br /><br />'
                . 'You can view the job <a href="' . WORKLIST_URL . $itemId . '">here</a>.' . '<br /><br />'
                . '<a href="' . SERVER_URL . '">www.worklist.net</a>' ;

            break;
            case 'new_functional':
                $body = "New item is available for functional: " . $itemLink ;
                $body.= '<br/><br/>Project: ' . $project_name;
                $body.= '<br/>Creator: ' . $workitem->getCreator()->getNickname();

                $body.= '<br/>Designer: ';
                if ($workitem->getRunnerId()) $body.= $workitem->getRunner()->getNickname();

                $body.= '<br/>Developer: ';
                if ($workitem->getMechanicId()) $body.= $workitem->getMechanic()->getNickname();

                $body.= '<br><br>Notes:<br>' .$workitem->getNotes();
                $body.= '<br><br>You can view the job <a href="' . WORKLIST_URL . $itemId . '">here</a>.' . '<br /><br />';
                $body.= '<br><br><a href="' . SERVER_URL . '">www.worklist.net</a>';

            break;
            case 'bug_found':
                $headers['From'] = '"' . $project_name . '-bug" ' . $from_address;

                $body = "<p>A bug has been reported related to item #".
                $workitem->getBugJobId().
                            " : ".$workitem->getBugJobSummary()."</p>";

                $body .= "<br/><p>New item #" . $itemId . " summary: " .
                            $workitem -> getSummary() . ".</p>";
                $body .= "<br/><p>Notes:" . $workitem->getNotes(). "</p><br/><br/>";
                $body .= '<br/><p>Project: ' . $project_name . "";
                $body .= 'Creator: ' . $workitem->getCreator()->getNickname(). "";
                if($workitem->getRunner() != '') {
                    $body .= 'Designer: ' . $workitem->getRunner()->getNickname() . "";
                }
                if($workitem->getMechanic() != '') {
                   $body .= 'Developer: ' . $workitem->getMechanic()->getNickname() . "</p><br/>";
                }
                $body .= '<br><br><a href="' . WORKLIST_URL . $itemId . '">View new item</a>.';
            break;
            case 'suggested':
                $body =  'Summary: ' . $itemLink . '<br /><br />'
                . 'Project: ' . $project_name . '<br />'
                . 'Creator: ' . $workitem->getCreator()->getNickname() . '<br />';
                if($workitem->getRunner() != '') {
                    $body .= 'Designer: ' . $workitem->getRunner()->getNickname() . '<br />';
                }
                if($workitem->getMechanic() != '') {
                    $body .= 'Developer: ' . $workitem->getMechanic()->getNickname()  . '<br /><br />';
                }
                $body .= 'Notes: ' . $workitem->getNotes() . '<br /><br />'
                . 'You can view the job <a href="' . WORKLIST_URL . $itemId . '">here</a>.' . '<br /><br />'
                . '<a href="' . SERVER_URL . '">www.worklist.net</a>' ;
            break;
            case 'suggestedwithbid':
                $body =  'Summary: ' . $itemLink . '<br /><br />'
                . 'Project: ' . $project_name . '<br />'
                . 'Creator: ' . $workitem->getCreator()->getNickname() . '<br />';
                if($workitem->getRunner() != '') {
                    $body .= 'Designer: ' . $workitem->getRunner()->getNickname() . '<br />';
                }
                if($workitem->getMechanic() != '') {
                $body .= 'Developer: ' . $workitem->getMechanic()->getNickname()  . '<br /><br />';
                }
                $body .= 'Notes: ' . $workitem->getNotes() . '<br /><br />'
                . 'You are welcome to bid the job <a href="' . WORKLIST_URL . $itemId . '">here</a>.' . '<br /><br />'
                . '<a href="' . SERVER_URL . '">www.worklist.net</a>' ;
            break;
            case 'autotestsuccess':
                $reusableString = $project_name . '(v' . $revision . ')';
                $reusableString .= '#';
                $reusableString .= $itemId;
                $reusableString .= ':';
                $reusableString .= $workitem -> getSummary();
                $headers['From'] = '"' . $project_name . '-committed" ' . $from_address;
                $body =  'Congrats!';
                $body .= '<br/><br/>Your Commit - ' . $reusableString . ' was a success!';
                $body .= '<br><br>Click <a href="';
                $body .= 'http://svn.worklist.net/revision.php?repname=';
                $body .= $project_name;
                $body .= '&rev=';
                $body .= $revision;
                $body .= '">here</a>';
                $body .= ' to see the webSVN commit notes.';
                $body .= '<br/><br/><a href="http://www.worklist.net">www.worklist.net</a>';
            break;
            case 'autotestfailure':
                $reusableString = $project_name . '(v' . $revision . ')';
                $reusableString .= '#';
                $reusableString .= $itemId;
                $reusableString .= ':';
                $reusableString .= $workitem -> getSummary();
                $headers['From'] = '"' . $project_name . '-commit fail" ' . $from_address;
                $body =  'Otto says: No Commit for you!';
                $body .= '<br/><br/>Your Commit - ';
                $body .= $reusableString;
                $body .=" failed the Autotester!";
                $body .= '<br><br>See test results <a href="http://bit.ly/jGfIkj">here</a> ';
                $body .= 'Please look at the test results and determine if you need to modify your commit.';
                $body .= 'You can type "@faq CommitTests" in the Journal for more information.';
                $body .= '<br/><br/><a href="http://www.worklist.net">www.worklist.net</a>';
            break;

            case 'invite-user':
                $headers['From'] = '"' . $project_name . '-invited" ' . $from_address;
                $body = "<p>Hello you!</p>";
                if(getSessionUserId()) {
                    $body .= "<p>You have been invited by " . $_SESSION['nickname'] . " at the Worklist to bid on ";
                } else {
                    $body .= "<p>You have been invited at the Worklist to bid on ";
                }
                $body .= "<a href='" . WORKLIST_URL . $itemId . "'>#" . $itemId . ': ' . $workitem->getSummary() . "</a>.</p>\n";
                $body .= "<p>Description:</p>";
                $body .= "<p>------------------------------</p>\n";
                $body .= "<p>" . $workitem -> getNotes() . "</p>\n";
                $body .= "<p>------------------------------</p>\n";
                $body .= "<p>To bid on that job Just follow <a href='" . WORKLIST_URL . $itemId . "'>this link</a>.</p>\n";
                $body .= "<p>Hope to see you soon.</p>\n";
            break;
            case 'invite-email':
                $headers['From'] = '"' . $project_name . '-invitation" ' . $from_address;
                $body = "<p>Well, hello there!</p>\n";
                $body .= "<p>" . $_SESSION['nickname'] . " from the Worklist thought you might be interested in bidding on this job:</p>\n";
                $body .= "<p>Summary of the job: " . $workitem -> getSummary() . "</p>\n";
                $body .= "<p>Description:</p>\n";
                $body .= "<p>------------------------------</p>\n";
                $body .= "<p>" . $workitem -> getNotes() . "</p>\n";
                $body .= "<p>------------------------------</p>\n";
                $body .= "<p>To bid on that job, follow the link, create an account (less than a minute) and set the price you want to be paid for completing it!</p>\n";
                $body .= "<p>This item is part of a larger body of work being done at Worklist. You can join our Live Workroom to ask more questions by going ";
                $body .= "<a href=\"" . SERVER_BASE . "\">here</a>. You will be our 'Guest' while there but can also create an account if you like so we can refer to you by name.</p>\n";
                $body .= "<p>If you are the type that likes to look before jumping in, here are some helpful links to get you started.</p>\n";
                $body .= "<p>[<a href=\"http://www.lovemachineinc.com/\">www.lovemachineinc.com</a> | Learn more about LoveMachine the company]<br />\n";
                $body .= "[<a href=\"http://svn.worklist.net/\">svn.worklist.net</a> | Browse our SVN repositories]<br />\n";
                $body .= "[<a href=\"https://dev.sendllove.us/\">dev.sendllove.us</a> | Play around with SendLove]<br />\n";
                $body .= "[<a href=\"" . WORKLIST_URL . "/\">" . WORKLIST_URL . "</a> | Look over all our open work items]<br />\n";
                $body .= "[<a href=\"" . JOURNAL_URL . "/\">" . JOURNAL_URL . "</a> | Talk with us in our Journal]<br />\n";
                $body .= "<p>Hope to see you soon.</p>\n";
            break;

            case 'code-review-completed':
                $headers['From'] = '"' . $project_name . '-review complete" ' . $from_address;
                $body = '<p>Hello,</p>';
                $body .= '<p>The code review on task '.$itemLink.' has been completed by ' . $_SESSION['nickname'] . '</p>';
                $body .= '<br>';
                $body .= '<p>Project: '.$project_name.'<br />';
                $body .= 'Creator: ' . $workitem->getCreator()->getNickname() . '<br />';
                $body .= 'Designer: ' . $workitem->getRunner()->getNickname() . '<br />';
                $body .= 'Developer: ' . $workitem->getMechanic()->getNickname() . '</p>';
                $body .= '<p>Notes: ' . $workitem->getNotes() . '<br /></p>';
                $body .= '<p>You can view the job <a href="'. WORKLIST_URL . $itemId . '">here</a>.' . '<br /></p>';
                $body .= '<p><a href="' . SERVER_URL . '">www.worklist.net</a></p>';
            break;

            case 'expired_bid':
                $headers['From'] = '"' . $project_name . '-expired bid" ' . $from_address;
                $body = "<p>Job " . $itemLink . "<br />";
                $body .= "Your Bid on #" . $itemId . " has expired and this task is still available for Bidding.</p>";
                $body .= "<p>Bidder: " . $data['bidder_nickname'] . "<br />";
                $body .= "Bid Amount : $" . $data['bid_amount'] . "</p>";
                $body .= '<p>Project: ' . $project_name . '<br />';
                $body .= 'Creator: ' . $workitem->getCreator()->getNickname() . '<br />';
                if ($workitem->getRunnerId()) {
                    $body .= 'Designer: ' . $workitem->getRunner()->getNickname() . '<br />';
                }
                $body .= '<p>Notes: ' . $workitem->getNotes() . '<br /></p>';
                $body .= '<p>You can view the job ';
                $body .= '<a href="' . WORKLIST_URL . $itemId . '">here</a>.<br /></p>';
                $body .= '<p><a href="' . SERVER_URL . '">www.worklist.net</a></p>';
            break;
            case 'auto-pass':
                $headers['From'] = '"' . $project_name . "- Auto PASSED" . '" ' . $from_address;
                if (isset($data['prev_status']) && $data['prev_status'] == 'Bidding') {
                    $headers['From'] = '"' . $project_name . "- BIDDING Item Auto PASSED" . '" ' . $from_address;
                    $body = "Otto has triggered an auto-PASS for job #" . $itemId . ". You may reactivate this job by updating the status or contacting an admin." . '<br/><br/>';
                } else {
                    $body = "Otto has triggered an auto-PASS for your suggested job. You may reactivate this job by updating the status or contacting an admin." . '<br/><br/>';
                }
                $body .= "Summary: " . $itemLink . ": " . $workitem->getSummary() . '<br/>'
                    . 'Project: ' . $project_name . '<br />'
                    . 'Creator: ' . $workitem->getCreator()->getNickname() . '<br />'
                    . 'Notes: '. $workitem->getNotes() . '<br /><br />'
                    . 'You can view the job <a href="' . WORKLIST_URL . $itemId . '">here</a>.' . '<br /><br />'
                    . '<a href="' . SERVER_URL . '">www.worklist.net</a>' ;
            break;

            case 'virus-found':
                $headers['From'] = '"' . $project_name . '-upload error" ' . $from_address;
                $body  = '<p>Hello, <br /><br /> The file ' . $options['file_name'] . ' (' . $options['file_title'] . ') ' .
                    'that you uploaded for this workitem was scanned and found to be containing a virus and will be quarantined. <br /><br />' .
                    'Please upload a clean copy of the file.</p>';
                $body .= '<p>Project: ' . $project_name . '<br />';
                $body .= 'Creator: ' . $workitem->getCreator()->getNickname() . '<br />';
                if ($workitem->getRunnerId()) {
                    $body .= 'Designer: ' . $workitem->getRunner()->getNickname() . '<br />';
                }
                $body .= '<p>Notes: ' . $workitem->getNotes() . '<br /></p>';
                $body .= '<p>You can view the job ';
                $body .= '<a href="' . WORKLIST_URL . $itemId . '">here</a>.<br /></p>';
                $body .= '<p><a href="' . SERVER_URL . '">www.worklist.net</a></p>';
            break;

            case 'virus-error':
                $headers['From'] = '"' . $project_name . '-upload error" ' . $from_address;
                $body  = '<p>Hello, <br /><br /> The file ' . $options['file_name'] . ' (' . $options['file_title'] . ') ' .
                    'that you uploaded for this workitem caused an unknown error during scanning. <br /><br />' .
                    'Please upload a clean copy of the file.</p>';
                $body .= '<p>Project: ' . $project_name . '<br />';
                $body .= 'Creator: ' . $workitem->getCreator()->getNickname() . '<br />';
                if ($workitem->getRunnerId()) {
                    $body .= 'Designer: ' . $workitem->getRunner()->getNickname() . '<br />';
                }
                $body .= '<p>Notes: ' . $workitem->getNotes() . '<br /></p>';
                $body .= '<p>You can view the job ';
                $body .= '<a href="' . WORKLIST_URL . $itemId . '">here</a>.<br /></p>';
                $body .= '<p><a href="' . SERVER_URL . '">www.worklist.net</a></p>';
            break;

            case 'deploy-failed':
                $subject = '#' . $itemId . ' - Deploy Error - ' . html_entity_decode($workitem -> getSummary(), ENT_QUOTES);
                $headers['From'] = '"' . $project_name . '-deploy error" ' . $from_address;
                $body  = '<p>Dear ' . $workitem->getMechanic()->getNickname() . '</p>';
                $body .= '<p>There was an error deploying ' . $project_name . ' ' . $options['commit_revision'] . ' for job ' .
                    '<a href="' . WORKLIST_URL . $itemId . '">#' . $itemId . '</a>.' .
                    ' The following error came up while minifying your JavaScript code:</p>';
                $body .= $options['error_msg'];
                break;
        }


        $current_user = new User();
        $current_user->findUserById(getSessionUserId());
        if($recipients) {
            foreach($recipients as $recipient) {
                /**
                 *  If there is need to get a new list of users
                 *  just add a get[IDENTIFIER]Id function to
                 *  workitem.class.php that returns a single user id
                 *  or an array with user ids */
                $method = 'get' . ucfirst($recipient) . 'Id';
                $recipientUsers=$workitem->$method();
                if(!is_array($recipientUsers)) {
                    $recipientUsers=array($recipientUsers);
                }
                foreach($recipientUsers as $recipientUser) {
                    if($recipientUser>0) {
                        //Does the recipient exists
                        $rUser = new User();
                        $rUser->findUserById($recipientUser);
                        if(($username = $rUser->getUsername())){
                            // Check to see if user doesn't want to be notified
                            // If user is recipient, doesn't have check on settings and not forced to receive then exclude),
                            // except for followers
                            if ( !strcmp($current_user->getUsername(), $username) && strcmp(ucfirst($recipient), 'Followers') ) {
                                if ( ! Notification::isNotified($current_user->getNotifications(), Notification::SELF_EMAIL_NOTIFICATIONS)
                                    || $includeSelf == false) {
                                    continue;
                                }
                            }

                            // check if we already sending email to this user
                            if(!in_array($username, $emails)){
                                array_push($emails, $username);
                            }
                        }
                    }
                }
            }
        }

        $emails = array_unique($emails);
        if (count($emails) > 0) {
            foreach($emails as $email) {
                // Small tweak for mails to followers on bid acceptance
                if($options['type'] == 'bid_accepted' && strcmp($email, $workitem->getMechanic()->getUsername())) {
                    $body = str_replace('Your', $workitem->getMechanic()->getNickname() . "'s", $body);
                }
                if (!send_email($email, $subject, $body, null, $headers)) {
                    error_log("Notification:workitem: send_email failed " . json_encode(error_get_last()));
                }
            }
        }
    }


    /**
     *  This function sends notification to HipChat about updates of workitems
     *
     * @param Array $options - Array with options:
     * type - type of notification to send out
     * workitem - workitem object with updated data
     * @param Array $data - Array with additional data that needs to be passed on
     */
    public static function workitemNotifyHipchat($options, $data = null) {
        $workitem = $options['workitem'];

        try {
            $project = new Project();
            $project->loadById($workitem->getProjectId());
            $project_name = $project->getName();
        } catch (Exception $e) {
            error_log($e->getMessage()." Workitem: #".$workitem->getId()." has an invalid project id:".$workitem->getProjectId());
            return;
        }

        if (!$project->getHipchatEnabled()) {
            return;
        }

        $bugJournalMessage = '';
        if (array_key_exists('bug_journal_message', $data)) {
            $bugJournalMessage = $data['bug_journal_message'];
        }

        $itemId = $workitem->getId();
        $itemLinkShort = '<a href="' . WORKLIST_URL . $itemId . "'>#{$itemId}</a>";
        $itemLink = "{$itemLinkShort}{$bugJournalMessage}";

        $message = null;
        $message_format = 'html';
        $notify = 0;

        switch ($options['type']) {
            case 'comment':
                $nick = $data['who'];
                $related = $data['related'];
                $message = "{$nick} posted a comment on issue {$itemLink}{$related}";
            break;

            case 'fee_added':
                $mechanic_id = $data['mechanic_id'];
                $fee_amount = $data['fee_amount'];
                $nick = $data['nick'];

                if ($mechanic_id == $_SESSION['userid']) {
                    $message = "{$nick} added a fee of \${$fee_amount} to item {$itemLink}";
                } else {
                    $rt = mysql_query("SELECT nickname FROM ".USERS.
                        " WHERE id='".(int)$mechanic_id."'");
                    if ($rt) {
                        $row = mysql_fetch_assoc($rt);
                        $nickname = $row['nickname'];
                    } else {
                        $nickname = "unknown-{$mechanic_id}";
                    }

                    $message = "{$nick} on behalf of {$nickname} added a fee of \${$fee_amount} to item {$itemLink}";
                }
            break;

            case 'fee_deleted':
                $nick = $data['nick'];
                $fee_nick = $data['fee_nick'];
                $message = "{$nick} deleted a fee from {$fee_nick} on item {$itemLink}";
            break;

            case 'tip_added':
                $nick = $data['nick'];
                $tipped_nickname = $data['tipped_nickname'];
                $message = "{$nick} tipped {$tipped_nickname} on job {$itemLink}";
            break;

            case 'bid_accepted':
                $nick = $data['nick'];
                $bid_amount = $data['bid_amount'];
                $nickname = $data['nickname'];
                $message = "{$nick} accepted {$bid_amount} from {$nickname} on item {$itemLink}. Status set to Working.";
            break;

            case 'bid_placed':
                $message = "A bid was placed on item {$itemLink}.";

                $new_update_message = $data['new_update_message'];
                if ($new_update_message) {
                    $message .= " {$new_update_message}";
                }
            break;

            case 'bid_updated':
                $message = "Bid updated on item {$itemLink}";
            break;

            case 'workitem-add':
                $nick = $data['nick'];
                $status = $data['status'];
                $message = "{$nick} added job {$itemLink}. Status set to {$status}";
            break;

            case 'code-review-completed':
                $nick = $data['nick'];
                $message = "{$nick} has completed their code review for {$itemLink}";
            break;

            case 'workitem-update':
                $nickname = $data['nick'];
                $new_update_message = $data['new_update_message'];
                $related = $data['related'];

                $message = "{$nickname} updated item {$itemLink}.{$new_update_message}{$related}";
            break;

            case 'status-notify':
                $nick = $data['nick'];
                $status = $data['status'];
                $message = "{$nick} updated item {$itemLink}. Status set to {$status}";
            break;

            case 'code-review-started':
                $nick = $data['nick'];
                $message = "{$nick} has started a code review for {$itemLink}";
            break;

            case 'code-review-canceled':
                $nick = $data['nick'];
                $message = "{$nick} has canceled their code review for {$itemLink}";
            break;

            case 'ping':
                $nickname = $data['nick'];
                $receiver_nick = $data['receiver_nick'];
                $msg = $data['msg'];

                $message = "{$nickname} sent a ping to {$receiver_nick} about item {$itemLink}: {$msg}";
            break;
        }

        if ($message) {
            $project->sendHipchat_notification($message, $message_format, $notify);
        }
    }

    // HOME PAGE CONTACT/ADD PROJECT FORM EMAIL
    public function emailContactForm($name, $email, $phone, $proj_name, $proj_desc, $website){
        $subject = "Worklist - Add Project Contact Form";
        $html = "<html><head><title>Worklist - Add Project Contact Form</title></head><body>";
        $html .= "<h2>Project Contact Information:</h2>";
        $html .= "<p><strong>Name:</strong> " . $name . "</p>";
        $html .= "<p><strong>Email:</strong> " . $email . "</p>";
        $html .= "<p><strong>Phone #:</strong> " . $phone . "</p>";
        $html .= "<p><strong>Project Name:</strong> " . $proj_name . "</p>";
        $html .= "<p><strong>Website:</strong> " . $website . "</p>";
        $html .= "<p><strong>Project Description:</strong><br />" . nl2br($proj_desc) . "</p>";
        $html .= "</body></html>";
        if(send_email("contact@worklist.net", $subject, $html)){
            return true;
        }
        return false;
    }

    public static function autoTestNofications($workItemId,$result,$revision) {
        $workItem = new WorkItem;
        $workItem->loadById($workItemId);
        $project = new Project();
        $project->loadById($workItem->getProjectId());
        $emails = self::getNotificationEmails(self::MY_AUTOTEST_NOTIFICATIONS,$workItem);
        $typeOfNotification = ($result=='success') ? 'autotestsuccess' : 'autotestfailure';
        $options = array('type' => $typeOfNotification,
        'workitem' => $workItem,
        'emails' => $emails,
        'project_name' => $project->getName(),
        'revision' => $revision);
        self::workitemNotify($options);
    }

    public static function deployErrorNotification($work_item_id, $error_msg, $commit_revision) {
        $error_msg = '<pre>' . $error_msg . '</pre>';
        $journal_message = "Deploy failed for rev." . $commit_revision . " job ";
        if ($work_item_id > 0) {
            $workItem = new WorkItem();
            $workItem->loadById($work_item_id);
            $project = new Project();
            $project->loadById($workItem->getProjectId());
            $emails = self::getNotificationEmails(self::MY_AUTOTEST_NOTIFICATIONS, $workItem);
            $options = array('type' => 'deploy-failed',
                'workitem' => $workItem,
                'emails' => $emails,
                'project_name' => $project->getName(),
                'error_msg' => $error_msg,
                'commit_revision' => $commit_revision);
            self::workitemNotify($options);
            $journal_message .= "#" . $work_item_id;

        } else {
            $headers['From'] = DEFAULT_SENDER;
            $subject = "Deploy failed for rev." . $commit_revision;
            $body = '<p>Deploy for commit (rev.' . $commit_revision . ') without assigned commit number has failed:</p>';
            $body .= '<p>' . $error_msg . '</p>';

            if (!send_email(OPS_EMAIL, $subject, $body, null, $headers)) {
                error_log("Notification:workitem: send_email failed " . json_encode(error_get_last()));
            }
            $journal_message .= "unknown";
        }
        sendJournalNotification($journal_message);
    }

    public function notifyBudgetAddFunds($amount, $giver, $receiver, $grantor, $add_funds_to_budget) {
        if (!$amount || $amount < 0.01 || ! $giver || ! $receiver || ! $grantor) {
            return false;
        }

        $subject = "Worklist - Budget Funds Added!";
        $html = "<html><head><title>Worklist - Budget Funds Added!</title></head><body>";
        $html .= "<h2>You've Got Budget Funds!</h2>";
        $html .= "<p>Hello " . $receiver->getNickname() . ",<br />Your Budget grant from " .
            $grantor->getNickname() . " has been increased by $" . number_format($amount, 2) .
            " (add funds by " . $giver->getNickname() . ").</p>";
        $html .= "<p>Budget id: " . $add_funds_to_budget->id . "</p>";
        $html .= "<p>Reason: " . $add_funds_to_budget->reason . "</p>";
        $html .= "<p>Remaining amount: $" . number_format($amount + $add_funds_to_budget->remaining, 2) . "</p>";
        $html .= "<p>- Worklist.net</p>";
        $html .= "</body></html>";

        if (!send_email($receiver->getUsername(), $subject, $html)) {
            error_log("Notification:workitem: send_email failed " . json_encode(error_get_last()));
        }
    }

    public function notifyBudget($amount, $reason, $giver, $receiver) {
        if (!$amount || $amount < 0.01 || ! $giver || ! $receiver) {
            return false;
        }

        $subject = "Worklist - You've Got Budget!";
        $html = "<html><head><title>Worklist - You've Got Budget!</title></head><body>";
        $html .= "<h2>You've Got Budget!</h2>";
        $html .= "<p>Hello " . $receiver->getNickname() . ",<br />" . $giver->getNickname() . " granted you $" . number_format($amount, 2) .
        " of budget for: " . $reason . "</p>";
        $html .= "<p>Don't spend it all in one place!</p><p>- Worklist.net</p>";
        $html .= "</body></html>";

        if (!send_email($receiver->getUsername(), $subject, $html)) {
            error_log("Notification:workitem: send_email failed " . json_encode(error_get_last()));
        }
    }

    public function notifySeedBudget($amount, $reason, $source, $giver, $receiver) {
        if (!$amount || $amount < 0.01 || ! $giver || ! $receiver) {
            return false;
        }

        $subject = "Seed Budget Granted";
        $html = "<html><head><title>Seed Budget Granted</title></head><body>";
        $html .= "<h2>Seed Budget Granted by " . $giver->getNickname() . "</h2>";
        $html .= "<p>To: " . $receiver->getNickname() .
                "<br />From: " . $giver->getNickname() .
                "<br />Amount: $" . number_format($amount, 2) .
                "<br />For: " . $reason  .
                "<br />Source: " . $source . "</p>";
        $html .= "</body></html>";

        $emailReceiver = new User();
        $emailReceiverArray = explode(",", BUDGET_AUTHORIZED_USERS);
        for ($i = 1 ; $i < sizeof($emailReceiverArray) - 1 ; $i++) {
            if ($emailReceiver->findUserById($emailReceiverArray[$i])) {
                if (!send_email($emailReceiver->getUsername(), $subject, $html)) {
                    error_log("Notification:workitem: send_email failed " . json_encode(error_get_last()));
                }
            } else {
                error_log("Notification:workitem: send_email failed, invalid receiver id " .
                    $emailReceiverArray[$i]);
            }
        }
    }

    // get list of expired bids
    public function emailExpiredBids(){
        $qry = "SELECT w.id worklist_id, b.email bid_email, b.id as bid_id, b.bid_amount, r.username runner_email, u.nickname bidder_nickname
            FROM " . WORKLIST . " w
              LEFT JOIN " . BIDS . " b ON w.id = b.worklist_id
              LEFT JOIN " . USERS . " u ON u.id = b.bidder_id
              LEFT JOIN " . USERS . " r ON r.id = w.runner_id
              WHERE w.status = 'Bidding'
              AND b.expired_notify = 0
              AND b.bid_expires <> '0000-00-00 00:00:00'
              AND b.bid_expires < NOW()
              AND u.is_active = 1
              AND b.withdrawn = 0
            ORDER BY b.worklist_id DESC";
        $worklist = mysql_query($qry);
        $wCount = mysql_num_rows($worklist);
        if($wCount > 0){
            while ($row = mysql_fetch_assoc($worklist)) {
                $options = array();
                $options['emails'] = array($row['bid_email'], $row['runner_email']);
                $options['workitem'] = new WorkItem();
                $options['workitem']->loadById($row['worklist_id']);
                $options['type'] = "expired_bid";
                $data = array('bid_amount' => $row['bid_amount'], 'bidder_nickname' => $row['bidder_nickname']);

                self::workitemNotify($options, $data);

                $bquery = "UPDATE " . BIDS . " SET expired_notify = 1 WHERE id = " . $row['bid_id'];
                mysql_query($bquery);
            }
        }
    }

}
