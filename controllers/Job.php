<?php
/* TODO (joanne)
 * as time permits we need to compare the journal messages here to those in notification class
 * and clean out whatever code is no longer in use.
 */
require_once('models/DataObject.php');
require_once('models/Budget.php');
require_once('models/Users_Favorite.php');

class JobController extends Controller {
    public function run($job_id) {
        $this->write('statusListRunner', array("Draft", "Suggested", "SuggestedWithBid", "Bidding", "Working", "Functional", "Code Review", "Completed", "Done", "Pass"));
        $statusListMechanic = array("Working", "Functional", "Code Review", "Completed", "Pass");
        $this->write('statusListMechanic', $statusListMechanic);
        $this->write('statusListCreator', array("Suggested", "Pass"));

        if (! defined("WORKITEM_URL")) { define("WORKITEM_URL", SERVER_URL); }
        if (! defined("WORKLIST_REDIRECT_URL")) { define("WORKLIST_REDIRECT_URL", SERVER_URL); }
        $worklist_id = intval($job_id);
        $is_runner = isset($_SESSION['is_runner']) ? $_SESSION['is_runner'] : 0;
        $currentUsername = isset($_SESSION['username']) ? $_SESSION['username'] : '';

        //initialize user accessing the page
        $userId = getSessionUserId();
        $user = new User();
        if ($userId > 0) {
            $user->findUserById($userId);
        } else {
            $user->setId(0);
        }
        $this->write('user', $user);

        // TODO: Would be good to take out all the checks for isset($_SESSION['userid'] etc. and have them use $user instead, check $user->getId() > 0.
        if (empty($worklist_id)) {
            $this->view = null;
            return;
        }
        //Set an empty variable for $journal_message to avoid errors/warnings with .=
        $journal_message = null;

        //initialize the workitem class
        $workitem = new WorkItem();
        try {
            $workitem->loadById($worklist_id);
        } catch(Exception $e) {
            $error  = $e->getMessage();
            $this->view = null;
            die($error);
        }
        $this->write('workitem', $workitem);

        // we need to be able to grant runner rights to a project founder for all jobs for their project
        $workitem_project = Project::getById($workitem->getProjectId());
        $is_project_founder = false;
        if($workitem_project->getOwnerId() == $_SESSION['userid']){
            $is_project_founder = true;
        }
        $this->write('workitem_project', $workitem_project);
        $this->write('is_project_founder', $is_project_founder);

        $this->write('isGitHubConnected', $user->isGithub_connected($workitem_project->getGithubId()));

        //used for is_project_runner rights
        $is_project_runner = false;
        if($workitem->getIsRelRunner() == 1){
            $is_project_runner = true;
        }
        $this->write('is_project_runner', $is_project_runner);

        $redirectToDefaultView = false;

        $promptForReviewUrl = true;
        $runner_budget = $user->getBudget();

        $action = isset($_REQUEST['action']) ? $_REQUEST['action'] : 'view';
        if ($workitem->getStatus() == 'Done' && $action == 'edit') {
            $action = 'view';
        }

        $view_bid_id = 0;

        if (isset($_REQUEST['withdraw_bid'])) {
            $action = "withdraw_bid";
        } else if(isset($_REQUEST['decline_bid'])) {
            $action = "decline_bid";
        } else if(isset($_REQUEST['save_workitem'])) {
            $action = "save_workitem";
        } else if(isset($_REQUEST['place_bid'])) {
            $action = "place_bid";
        } else if(isset($_REQUEST['swb'])) {
            $action = "swb";
        } else if(isset($_REQUEST['edit_bid'])) {
            $action = "edit_bid";
        } else if(isset($_REQUEST['add_fee'])) {
            $action = "add_fee";
        } else if (isset($_REQUEST['add_tip'])) {
            $action = "add_tip";
        } else if(isset($_REQUEST['accept_bid'])) {
            $action = "accept_bid";
        } else if(isset($_REQUEST['accept_multiple_bid'])) {
            $action = "accept_multiple_bid";
        } else if(isset($_REQUEST['status-switch'])) {
            $action = "status-switch";
        } else if(isset($_REQUEST['save-review-url'])) {
            $action = "save-review-url";
        } else if(isset($_REQUEST['invite-people'])) {
            $action = "invite-people";
        } else if (isset($_REQUEST['newcomment'])) {
            $action = 'new-comment';
        } else if (isset($_REQUEST['start_codereview'])) {
            $action = "start_codereview";
        } else if (isset($_REQUEST['finish_codereview'])) {
            $action = "finish_codereview";
        } else if (isset($_REQUEST['cancel_codereview'])) {
            $action = "cancel_codereview";
        }

        if ($action == 'view_bid') {
            $action = "view";
            $this->write('view_bid_id', isset($_REQUEST['bid_id']) ? $_REQUEST['bid_id'] : 0);
        }

        // for any other action user has to be logged in
        if ($action != 'view' && $action != 'invite-people') {
            checkLogin();
            $action_error = '';
            $action = $workitem->validateAction($action, $action_error);
        }
        $this->write('action', $action);

        // Save WorkItem was requested. We only support Update here
        $notifyEmpty = true;
        $job_changes = array();
        $status_change = '';
        if ($action =='save_workitem') {
            $args = array(
                'summary',
                'notes',
                'status',
                'project_id',
                'sandbox',
                'is_bug',
                'bug_job_id',
                'budget-source-combo'
            );

            foreach ($args as $arg) {
                if (!empty($_REQUEST[$arg])) {
                    $$arg = $_REQUEST[$arg];
                } else {
                    $$arg = '';
                }
            }

            // code to add specifics to journal update messages
            $new_update_message='';
            $is_bug = empty($_REQUEST['is_bug'])? 0 : 1;
            $budget_id = !empty($_REQUEST['budget-source-combo'])? (int) $_REQUEST['budget-source-combo'] : 0;
            // First check to see if this is marked as a bug
            if ($workitem->getIs_bug() != $is_bug) {
                error_log("bug changed it");
                if($is_bug) {
                    $new_update_message .= 'Marked as a bug. ';
                } else {
                    $new_update_message .= 'Marked as not being a bug. ';
                }
                $job_changes[] = '-bug';
            }
            $workitem->setIs_bug($is_bug);
            $old_budget_id = -1;
            if ($workitem->getBudget_id() != $budget_id) {
                $new_update_message .= 'Budget changed. ';
                $old_budget_id = (int) $workitem->getBudget_id();
                $workitem->setBudget_id($budget_id);
            }
            // summary
            if (isset($_REQUEST['summary']) && $workitem->getSummary() != $_REQUEST['summary']) {
                $summary = $_REQUEST['summary'];
                $workitem->setSummary($summary);
                $new_update_message .= "Summary changed. ";
                if ($workitem->getStatus() != 'Draft') {
                    $job_changes[] = '-summary';
                }
            }

            if (isset($_REQUEST['skills'])) {
                $skillsArr = explode(',', $_REQUEST['skills']);
                // remove empty values
                foreach ($skillsArr as $key => $value) {
                    $skillsArr[$key] = trim($value);
                    if (empty($value)) {
                        unset($skillsArr[$key]);
                    }
                }
                // get current skills
                $skillsCur = $workitem->getSkills();
                // have skills been updated?
                $skillsDiff = array_diff($skillsArr, $skillsCur);
                if (is_array($skillsDiff) && ! empty($skillsDiff)) {
                    if ($workitem->getStatus() != 'Draft') {
                        $new_update_message .= 'Skills updated: ' . implode(', ', $skillsArr);
                    }
                    // remove nasty end comma
                    $new_update_message = rtrim($new_update_message, ', ') . '. ';
                    $job_changes[] = '-skills';
                }
                $workitem->setWorkitemSkills($skillsArr);
            }

            // status
            if ($is_project_runner
                || $userId == $workitem->getRunnerId()
                || (in_array($status, $statusListMechanic))) {

                if ($workitem->getStatus() != $status && !empty($status) && $status != 'Draft') {
                    if ($this->changeStatus($workitem, $status, $user)) {
                        if (!empty($new_update_message)) {  // add commas where appropriate
                            $new_update_message .= ", ";
                        }
                        $status_change = '-' . ucfirst(strtolower($status));
                        $new_update_message .= "Status set to *$status*. ";
                        if ($status == 'Done') {
                            $displayDialogAfterDone = true;
                        }
                    }
                }
            }
            $related = "";
            if ($workitem->getNotes() != $notes && isset($_REQUEST['notes'])) {
                $workitem->setNotes($notes);
                $new_update_message .= "Notes changed. ";
                $job_changes[] = '-notes';
                $related = getRelated($notes);
            }
            // project

            if ($project_id && $workitem->getProjectId() != $project_id) {
                $workitem->setProjectId($project_id);
                if ($workitem->getStatus() != 'Draft') {
                    $new_update_message .= "Project changed. ";
                    $job_changes[] = '-project';
                }
            }
            // Sandbox
            if ($workitem->getSandbox() != $sandbox) {
                $workitem->setSandbox($sandbox);
                $new_update_message .= "Sandbox changed. ";
                $job_changes[] = '-sandbox';
            }
            // Send invites
            if (!empty($_REQUEST['invite'])) {
                $people = explode(',', $_REQUEST['invite']);
                invitePeople($people, $workitem);
                $new_update_message .= "Invitations sent. ";
                $job_changes[] = '-invitation';
            }
            //Check if bug_job_id has changed and send notifications if it has
            if($workitem->getBugJobId() != $bug_job_id) {
                //Bug job Id changed
                $workitem->setBugJobId($bug_job_id);
                $new_update_message .= "Bug job Id changed. ";
                $job_changes[] = '-bug job id';
                if($bug_job_id > 0) {
                    //Load information about original job and notify
                    //users with fees and runner
                    Notification::workitemNotify(array('type' => 'bug_found',
                                                    'workitem' => $workitem,
                                                    'recipients' => array('runner', 'usersWithFeesBug')));
                }
            }

            //if job is a bug, notify to journal
            if($bug_job_id > 0) {
                $workitem->setIs_bug(1);
                $bugJournalMessage= " (bug of #" . $workitem->getBugJobId() . ")";
            } elseif (isset($_REQUEST['is_bug']) && $_REQUEST['is_bug'] == 'on') {
                $bugJournalMessage = " (which is a bug)";
            } elseif (isset($is_bug) && $is_bug == 1) {
                $bugJournalMessage = " (which is a bug)";
            } else {
                $bugJournalMessage= "";
            }

            if (empty($new_update_message)) {
                $new_update_message = " No changes.";
            } else {
                $workitem->save();
                if ($old_budget_id > 0) {
                    $budget = new Budget();
                    if ($budget->loadById($old_budget_id)) {
                        $budget->recalculateBudgetRemaining();
                    } else {
                        error_log("Old budget id not found: " . $old_budget_id);
                    }
                    if ($budget->loadById($workitem->getBudget_id())) {
                        $budget->recalculateBudgetRemaining();
                    } else {
                        error_log("New budget id not found: " . $workitem->getBudget_id());
                    }
                }
                $new_update_message = " Changes: $new_update_message";
                $notifyEmpty = false;
            }

            $redirectToDefaultView = true;
            if ($workitem->getStatus() != 'Draft') {
                $journal_message .= '\\#' . $worklist_id . ' updated by @' . $_SESSION['nickname'] .
                                    $bugJournalMessage .
                                    $new_update_message . $related;

                $options = array(
                    'type' => 'workitem-update',
                    'workitem' => $workitem
                );
                $data = array(
                    'nick' => $_SESSION['nickname'],
                    'bug_journal_message' => $bugJournalMessage,
                    'new_update_message' => $new_update_message,
                    'related' => $related
                );
                Notification::workitemNotifyHipchat($options, $data);
            }
        }

        if ($action == 'new-comment') {
            if ((isset($_REQUEST['worklist_id']) && !empty($_REQUEST['worklist_id'])) &&
                (isset($_REQUEST['user_id'])     && !empty($_REQUEST['user_id']))     &&
                (isset($_REQUEST['comment'])     && !empty($_REQUEST['comment']))) {

                if (isset($_REQUEST['comment_id']) && !empty($_REQUEST['comment_id'])) {
                    $parent_comment = (int) $_REQUEST['comment_id'];
                } else {
                    $parent_comment = NULL;
                }
                $worklist_id = (int) $_REQUEST['worklist_id'];
                $user_id = (int) $_REQUEST['user_id'];
                $comment = $_REQUEST['comment'];
                $rt = $this->addComment($worklist_id,
                    $user_id,
                    $comment,
                    $parent_comment);

                // Send journal notification
                if ($workitem->getStatus() != 'Draft') {
                    $related = getRelated($comment);
                    $journal_message .= '@' . $_SESSION['nickname'] . ' posted a comment on #' . $worklist_id . $related;

                    $options = array(
                        'type' => 'comment',
                        'workitem' => $workitem,
                        'recipients' => array('creator', 'runner', 'mechanic', 'followers'),
                        'emails' => $rt['correspondent']
                    );
                    $data = array(
                        'who' => $_SESSION['nickname'],
                        // removed nl2br as it's cleaner to be able to choose if this is used on output
                        'comment' => $comment,
                        'related' => $related
                    );

                    Notification::workitemNotify($options, $data, false);
                    Notification::workitemNotifyHipchat($options, $data);

                    // workitem mentions
                    $matches = array();
                    if (preg_match_all(
                        '/@(\w+)/',
                        $comment,
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
                                    'text' => $comment,
                                    'link' => '<a href="' . WORKLIST_URL . $workitem->getId() . '">See the comment</a>'
                                );

                                $senderEmail = 'Worklist <contact@worklist.net>';
                                sendTemplateEmail($recipient->getUsername(), $emailTemplate, $data, $senderEmail);
                            }
                        }
                    }

                }
                sendJournalNotification($journal_message);
                $comment = new Comment();
                $comment->findCommentById((int) $rt['id']);
                $result = array('success' => true,
                                'id' => $rt['id'],
                        'comment' => replaceEncodedNewLinesWithBr(linkify($comment->getComment())),
                        'avatar' =>  $comment->getUser()->getAvatar(),
                        'nickname' => $comment->getUser()->getNickname(),
                        'userid' => $comment->getUser()->getId(),
                        'date' => relativeTime(strtotime($comment->getDate()) - time()));
                ob_start();
                $json = json_encode($result);
            } else {
                $json = json_encode(array('success' => false));
            }
            $this->view = null;
            echo $json;
            ob_end_flush();
            exit;
        }

        if($action =='invite-people') {

            // Send invitation
            $people = explode(',', $_REQUEST['invite']);
            $nonExistingPeople = invitePeople($people, $workitem);
            $json = json_encode($nonExistingPeople);
            $this->view = null;
            echo $json;
            exit;

        }
        if($action == 'start_codereview') {
            if(!($user->isEligible() && $userId == $workitem->getMechanicId())) {
                error_log("Input forgery detected for user $userId: attempting to $action.");
            } else {
                $workitem->setCRStarted(1);
                $workitem->save();
                $journal_message = '@' . $_SESSION['nickname'] . ' has started a code review for #' . $worklist_id;

                $options = array(
                    'type' => 'code-review-started',
                    'workitem' => $workitem
                );
                $data = array(
                    'nick' => $_SESSION['nickname']
                );
                Notification::workitemNotifyHipchat($options, $data);
            }
        }

        if($action == 'finish_codereview') {
            // ensure user is alowed to end review, and review is open
            if(!($user->isEligible() &&
            $workitem->getCRStarted() == 1 &&
            $workitem->getCRCompleted() != 1 &&
            $this->hasRights($userId, $workitem))) {
                error_log("Input forgery detected for user $userId: attempting to $action.");
            } else {
                $args = array('itemid', 'crfee_amount', 'fee_category', 'crfee_desc', 'is_expense', 'is_rewarder');
                foreach ($args as $arg) {
                    if (isset($_REQUEST[$arg])) {
                           $$arg = mysql_real_escape_string($_REQUEST[$arg]);
                    } else {
                        $$arg = '';
                    }
                }
                if($crfee_desc == '') {
                    $crfee_desc = 'Code Review';
                } else {
                    $crfee_desc = 'Code Review - '. $crfee_desc;
                }
                $journal_message = AddFee($itemid, $crfee_amount, $fee_category, $crfee_desc, $workitem->getCReviewerId(), $is_expense, $is_rewarder);
                sendJournalNotification($journal_message);
                $workitem->setCRCompleted(1);
                $workitem->save();

                $myRunner = new User();
                $myRunner->findUserById($workitem->getRunnerId());
                $myRunner->updateBudget(-$crfee_amount, $workitem->getBudget_id());

                $journal_message = '@' . $_SESSION['nickname'] . ' has completed their code review for #' . $worklist_id;

                $options = array(
                    'type' => 'code-review-completed',
                    'workitem' => $workitem,
                    'recipients' => array('runner', 'mechanic', 'followers')
                );
                Notification::workitemNotify($options);

                $data = array(
                    'nick' => $_SESSION['nickname']
                );
                Notification::workitemNotifyHipchat($options, $data);
            }
        }

        if($action == 'cancel_codereview') {
            // ensure user is allowed to cancel review, and review is open
            if(!($user->isEligible() &&
            $workitem->getCRStarted() == 1 &&
            $workitem->getCRCompleted() != 1 &&
            $this->hasRights($userId, $workitem))) {
                error_log("Input forgery detected for user $userId: attempting to $action.");
            } else {
                $workitem->setCRStarted(0);
                $workitem->save();
                $journal_message = '@' . $_SESSION['nickname'] . ' has canceled their code review for #' . $worklist_id;

                $options = array(
                    'type' => 'code-review-canceled',
                    'workitem' => $workitem,
                );
                $data = array(
                    'nick' => $_SESSION['nickname'],
                );
                Notification::workitemNotifyHipchat($options, $data);
            }
        }

        if($action =='save-review-url') {
            if(!($is_project_runner ||
            ($mechanic_id == $user_id) &&
            ($worklist['status'] != 'Done'))) {
                error_log("Input forgery detected for user $userId: attempting to $action.");
            } else {
                $sandbox = (!empty($_REQUEST['sandbox-url'])) ? $_REQUEST['sandbox-url'] : $workitem->getSandbox();
                $notes = (!empty($_REQUEST['review-notes'])) ? $_REQUEST['review-notes'] : null;

                $status_review = $_REQUEST['quick-status-review'];
                $status_error = '';

                if(! empty($status_review) && $workitem->getStatus() != $status_review) {
                    $old_status = $workitem->getStatus();

                    $status = $this->changeStatus($workitem, $status_review, $user);

                    if ($status !== true) {
                        // status change failed due to sandbox issues
                        $message = '';
                        if ($status & 4) { //sandbox not updated
                            $message .= " - Sandbox is not up-to-date\n";
                        }
                        if ($status & 8) { //sandbox has conflicts
                            $message .= " - Sandbox contains conflicted files\n";
                        }
                        if ($status & 16) { //sandbox has not-included files
                            $message .= " - Sandbox contains 'not-included' files\n";
                        }

                        $status_error = "Sandbox verification failed. " . $message;
                        // revert to the old status, but still save the sandbox change
                        $workitem->setStatus($old_status);
                    }
                }
                $workitem->setSandbox($sandbox);
                $workitem->save();
                if (!$status_error) {
                    $new_update_message = " sandbox url : $sandbox ";
                    if(!empty($status_review)) {
                        $new_update_message .= " Status set to {$status_review}. ";
                        $status_change = '-' . ucfirst(strtolower($status_review));
                        Notification::massStatusNotify($workitem);
                    } else {
                        $job_changes[] = '-sandbox';
                    }
                    if ($notes) {
                        //add review notes
                        $fee_amount = 0.00;
                        $fee_desc = 'Review Notes:'. $notes;
                        $mechanic_id = $user->getId();
                        $itemid = $workitem->getId();
                        $is_expense = 1;
                        $fee_category = '';
                        AddFee($itemid, $fee_amount, $fee_category, $fee_desc, $mechanic_id, $is_expense);
                    }
                    $notifyEmpty = false;
                    if ($status_review == 'FUNCTIONAL') {
                        $status_change = '-functional';
                        Notification::workitemNotify(array(
                            'type' => 'modified-functional',
                            'workitem' => $workitem,
                            'status_change' => $status_change,
                            'job_changes' => $job_changes,
                            'recipients' => array('runner', 'creator', 'mechanic', 'followers')),
                            array('changes' => $new_update_message));
                      $notifyEmpty = true;
                    }

                    $journal_message = '\\#' . $worklist_id . ' updated by @' . $_SESSION['nickname'] . ' ' . $new_update_message;
                }

                $promptForReviewUrl = false;
            }
        }

        if ($action =='status-switch') {
            $status = $_REQUEST['quick-status'];
            $status_error = '';
            if ($status == 'Done' && $workitem->getProjectId() == 0) {
                $status_error = "No project associated with workitem. Could not set to DONE.";
            } else {
                if ($this->changeStatus($workitem, $status, $user)) {
                    if ($workitem->save() == false) {
                        $status_error = "Error in save workitem process. Could not change the status.";
                    } else {
                        if ($status == 'Completed') {
                            $workitem->addFeesToCompletedJob();
                        }

                        if ($status == 'Done') {
                            $displayDialogAfterDone = true;
                        }

                        if($status == 'Code Review') {
                            Notification::massStatusNotify($workitem);
                        }

                        if ($status != 'Draft') {
                            $new_update_message = "Status set to *$status*. ";
                            $notifyEmpty = false;
                            $status_change = '-' . ucfirst(strtolower($status));
                            if ($status == 'Functional') {
                                Notification::workitemNotify(array('type' => 'modified-functional',
                                'workitem' => $workitem,
                                'status_change' => $status_change,
                                'job_changes' => $job_changes,
                                'recipients' => array('runner', 'creator', 'mechanic', 'followers')),
                                array('changes' => $new_update_message));
                                $notifyEmpty = true;
                            }
                            $journal_message = '\\#' . $worklist_id . ' updated by @' . $_SESSION['nickname'] . ' ' . $new_update_message;
                        }
                    }
                } else {

                    $message = '';
                    if ($status & 4) { //sandbox not updated
                        $message .= " - Sandbox is not up-to-date\n";
                    }
                    if ($status & 8) { //sandbox has conflicts
                        $message .= " - Sandbox contains conflicted files\n";
                    }
                    if ($status & 16) { //sandbox has not-included files
                        $message .= " - Sandbox contains 'not-included' files\n";
                    }

                    $status_error = "Sandbox verification failed. " . $message;
                }
            }
        }

        if (!$notifyEmpty) {
            $options = array(
                'type' => 'modified',
                'workitem' => $workitem,
                'status_change' => $status_change,
                'job_changes' => $job_changes,
                'recipients' => array('runner', 'creator', 'mechanic', 'followers')
            );
            $data = array(
                'changes' => $new_update_message
            );
            Notification::workitemNotify($options, $data);
        }

        if ($action == "place_bid") {
            //Escaping $notes with mysql_real_escape_string is generating \n\r instead of <br>
            //a new variable is used to send the unenscaped notes in email alert.
            //so it can parse the new line as <BR>   12-Mar-2011 <webdev>

            $args = array('bid_amount', 'done_in', 'bid_expires', 'notes', 'mechanic_id');
            foreach ($args as $arg) {
                $$arg = mysql_real_escape_string($_REQUEST[$arg]);
            }
            $bid_amount = (int) $bid_amount;
            $mechanic_id = (int) $mechanic_id;

            if ($_SESSION['timezone'] == '0000') $_SESSION['timezone'] = '+0000';
            $summary = getWorkItemSummary($worklist_id);

            if($mechanic_id != getSessionUserId()) {
                $row = $workitem->getUserDetails($mechanic_id);
                if (! empty($row)) {
                    $nickname = $row['nickname'];
                    $username = $row['username'];
                } else {
                    $username = "unknown-{$username}";
                    $nickname = "unknown-{$mechanic_id}";
                }
            } else {
                $mechanic_id = $_SESSION['userid'];
                $username = $_SESSION['username'];
                $nickname = $_SESSION['nickname'];
            }

            if ($user->isEligible()) {
                $bid_id = $workitem->placeBid($mechanic_id, $username, $worklist_id, $bid_amount, $done_in, $bid_expires, $notes);
                // Journal notification
                $journal_message = 'A bid was placed on #' . $worklist_id;
                //sending email to the runner of worklist item

                $row = $workitem->getRunnerSummary($worklist_id);
                if(!empty($row)) {
                $id = $row['id'];
                    $summary = $row['summary'];
                    $username = $row['username'];
                }

                $options = array(
                     'type' => 'bid_placed',
                     'workitem' => $workitem,
                     'recipients' => array('runner'),
                     'userstats' => new UserStats($_SESSION['userid'])
                );
                $data = array(
                     'done_in' => $done_in,
                     'bid_expires' => $bid_expires,
                     'bid_amount' => $bid_amount,
                     'notes' => replaceEncodedNewLinesWithBr($notes),
                     'bid_id' => $bid_id,
                );

                // notify runner of new bid
                Notification::workitemNotify($options, $data);

                $status=$workitem->loadStatusByBidId($bid_id);
                if ($status == "SuggestedWithBid") {
                    if ($this->changeStatus($workitem, $status, $user)) {
                        $new_update_message = 'Status set to *' . $status . '*. ';
                        $notifyEmpty = false;
                        $journal_message .= $new_update_message;
                    }
                }

                $data['new_update_message'] = $new_update_message;
                Notification::workitemNotifyHipchat($options, $data);

                if(!$notifyEmpty) {
                    $options = array(
                        'type' => 'suggestedwithbid',
                        'workitem' => $workitem,
                        'recipients' => array('projectRunners')
                    );
                    $data = array('notes' => $notes);
                    Notification::workitemNotify($options, $data);
                }
            } else {
                error_log("Input forgery detected for user $userId: attempting to $action.");
            }

            $redirectToDefaultView = true;
        }

        // Edit Bid
        if ($action =="edit_bid") {
            if (! $user->isEligible() ) {
                error_log("Input forgery detected for user $userId: attempting to $action (isEligible in job)");
            } else {
                //Escaping $notes with mysql_real_escape_string is generating \n\r instead of <br>
                //a new variable is used to send the unenscaped notes in email alert.
                //so it can parse the new line as <BR>   12-Mar-2011 <webdev>

                $args = array('bid_id', 'bid_amount', 'done_in_edit', 'bid_expires_edit', 'notes');
                foreach ($args as $arg) {
                    $$arg = mysql_real_escape_string($_REQUEST[$arg]);
                }

                $bid_amount = (int) $bid_amount;
                $mechanic_id = (int) $mechanic_id;

                if ($_SESSION['timezone'] == '0000') $_SESSION['timezone'] = '+0000';
                $summary = getWorkItemSummary($worklist_id);
                $bid_id = $workitem->updateBid($bid_id, $bid_amount, $done_in_edit, $bid_expires_edit, $_SESSION['timezone'], $notes);

                // Journal notification
                $journal_message = 'Bid updated on #' . $worklist_id;

                //sending email to the runner of worklist item
                $row = $workitem->getRunnerSummary($worklist_id);
                if(!empty($row)) {
                $id = $row['id'];
                    $summary = $row['summary'];
                    $username = $row['username'];
                }

                $options = array(
                    'type' => 'bid_updated',
                    'workitem' => $workitem,
                    'recipients' => array('runner')
                );
                $data = array(
                    'done_in' => $done_in_edit,
                    'bid_expires' => $bid_expires_edit,
                    'bid_amount' => $bid_amount,
                    'notes' => replaceEncodedNewLinesWithBr($notes),
                    'bid_id' => $bid_id
                );

                // notify runner of new bid
                Notification::workitemNotify($options, $data);
                Notification::workitemNotifyHipchat($options, $data);

            }
            $redirectToDefaultView = true;
        }
        // Request submitted from Add Fee popup
        if ($action == "add_fee") {
            if(! $user->isEligible()) {
                error_log("Input forgery detected for user $userId: attempting to $action.");
            } else {
                $args = array('itemid', 'fee_amount', 'fee_desc', 'mechanic_id', 'is_expense', 'is_rewarder');
                foreach ($args as $arg) {
                    if (isset($_REQUEST[$arg]))  {
                       $$arg = mysql_real_escape_string($_REQUEST[$arg]);
                    }
                    else {
                        $$arg = '';
                    }
                }
                $itemid = (int) $itemid;
                $fee_amount = (float) $fee_amount;
                $mechanic_id = (int) $mechanic_id;

                $journal_message = AddFee($itemid, $fee_amount, '', $fee_desc, $mechanic_id, '', '');

                if ($workitem->getStatus() != 'Draft') {
                    $options = array(
                        'type' => 'fee_added',
                        'workitem' => $workitem,
                        'recipients' => array('runner')
                    );
                    $data = array(
                        'fee_adder' => $user->getNickname(),
                        'fee_amount' => $fee_amount,
                        'fee_desc' => $fee_desc,
                        'mechanic_id' => $mechanic_id,
                    );

                    Notification::workitemNotify($options, $data);

                    $data['nick'] = $_SESSION['nickname'];
                    Notification::workitemNotifyHipchat($options, $data);

                    // update budget
                    $runner = new User();
                    $runner->findUserById($workitem->getRunnerId());
                    $runner->updateBudget(-$fee_amount, $workitem->getBudget_id());

                }
                $redirectToDefaultView = true;
            }
        }

        if ($action == "add_tip") {
            $args = array('itemid', 'tip_amount', 'tip_desc', 'mechanic_id');
            foreach ($args as $arg) {
                if (isset($_REQUEST[$arg])) {
                    $$arg = mysql_real_escape_string($_REQUEST[$arg]);
                } else {
                    $$arg = '';
                }
            }

            $itemid = (int) $itemid;
            $fee_amount = (float) $tip_amount;
            $mechanic_id = (int) $mechanic_id;

            // is the logged in user the mechanic on the task?
            if ($workitem->getMechanicId() == getSessionUserId()) {
                $journal_message = AddTip($itemid, $tip_amount, $tip_desc, $mechanic_id);

                // notify recipient of new tip
                $recipient = new User();
                $recipient->findUserById($mechanic_id);

                $options = array(
                    'type' => 'tip_added',
                    'workitem' => $workitem,
                    'emails' => array($recipient->getUsername())
                );
                $data = array(
                    'tip_adder' => $user->getNickname(),
                    'tip_desc' => $tip_desc,
                    'tip_amount' => $tip_amount
                );

                Notification::workitemNotify($options, $data);

                $data['nick'] = $_SESSION['nickname'];
                $data['tipped_nickname'] = $recipient->getNickname();
                Notification::workitemNotifyHipchat($options, $data);
            }

            $redirectToDefaultView = true;
        }

        // Accept a bid
        if ($action == 'accept_bid') {
            if (!isset($_REQUEST['bid_id']) ||
                !isset($_REQUEST['budget_id'])) {
                $_SESSION['workitem_error'] = "Missing parameter to accept a bid!";
            } else {
                $bid_id = intval($_REQUEST['bid_id']);
                $budget_id = intval($_REQUEST['budget_id']);

                $budget = new Budget();
                if (!$budget->loadById($budget_id) ) {
                    $_SESSION['workitem_error'] = "Invalid budget!";
                }
                // only runners can accept bids
                if (($is_project_runner || $workitem->getRunnerId() == $_SESSION['userid'] || ($user->getIs_admin() == 1
                     && $is_runner) && !$workitem->hasAcceptedBids() &&
                    $workitem->getStatus() == "Bidding" || $workitem->getStatus() == "SuggestedWithBid")) {
                    // query to get a list of bids (to use the current class rather than breaking uniformity)
                    // I could have done this quite easier with just 1 query and an if statement..
                    $bids = (array) $workitem->getBids($workitem->getId());
                    $exists = false;
                    foreach ($bids as $array) {
                        if ($array['id'] == $bid_id) {
                            $exists = true;
                            $bid_amount = $array["bid_amount"];
                            break;
                        }
                    }

                    if ($exists) {
                        $remainingFunds = $budget->getRemainingFunds();
                        if($bid_amount <= $remainingFunds) {
                            $bid_info = $workitem->acceptBid($bid_id, $budget_id);
                            $budget->recalculateBudgetRemaining();

                            // Journal notification
                            $journal_message .= '@' . $_SESSION['nickname'] .
                                " accepted {$bid_info['bid_amount']} from ".
                                $bid_info['nickname'] . " on #" .$bid_info['worklist_id'] ." Status set to Working";

                            $options = array(
                                'type' => 'bid_accepted',
                                'workitem' => $workitem,
                                'recipients' => array('mechanic', 'followers')
                            );

                            // mail notification - including any data returned from acceptBid
                            Notification::workitemNotify($options, $bid_info);

                            $data = $bid_info;
                            $data['nick'] = $_SESSION['nickname'];
                            Notification::workitemNotifyHipchat($options, $data);

                            $bidder = new User();
                            $bidder->findUserById($bid_info['bidder_id']);

                            // Update Budget
                            $runner = new User();
                            $runner->findUserById($workitem->getRunnerId());
                            $runner->updateBudget(-$bid_amount, $workitem->getBudget_id());

                            // Send email to not accepted bidders
                            $this->sendMailToDiscardedBids($worklist_id);
                        } else {
                            $overBudget = money_format('%i', $bid_amount - $remainingFunds);
                            $_SESSION['workitem_error'] = "Failed to accept bid. Accepting this bid would make you " . $overBudget . " over your budget!";
                        }
                    } else {
                        $_SESSION['workitem_error'] = "Failed to accept bid, bid has been deleted!";
                    }
                } else {
                    if ($workitem->getIsRelRunner() || $workitem->getRunnerId() == $_SESSION['userid']) {
                        if ($workitem->hasAcceptedBids()) {
                            $_SESSION['workitem_error'] = "Failed to accept bid on task with an accepted bid!";
                        } else {
                            $_SESSION['workitem_error'] = "Accept Bid Failed, unknown task state!";
                        }
                    }
                }
            }
            $redirectToDefaultView = true;
        }

        // Accept Multiple  bid
        if ($action=='accept_multiple_bid') {
            if (!isset($_REQUEST['budget_id'])) {
                $_SESSION['workitem_error'] = "Missing budget to accept a bid!";
            } else {
                $bid_id = $_REQUEST['chkMultipleBid'];
                $mechanic_id = $_REQUEST['mechanic'];
                $budget_id = intval($_REQUEST['budget_id']);
                $budget = new Budget();
                if (!$budget->loadById($budget_id) ) {
                    $_SESSION['workitem_error'] = "Invalid budget!";
                }
                if (count($bid_id) > 0) {
                //only runners can accept bids
                    if (($is_project_runner || $workitem->getRunnerId() == getSessionUserId() ||
                         ($user->getIs_admin() == 1 && $is_runner)
                        ) &&
                        !$workitem->hasAcceptedBids() &&
                        (
                            $workitem->getStatus() == "Bidding" ||
                            $workitem->getStatus() == "SuggestedWithBid"
                        )) {
                        $total = 0;
                        foreach ($bid_id as $bid) {
                            $currentBid = new Bid();
                            $currentBid->findBidById($bid);
                            $total = $total + $currentBid->getBid_amount();
                        }

                        $remainingFunds = $budget->getRemainingFunds();
                        if ($total <= $remainingFunds) {
                            foreach ($bid_id as $bid) {
                                $bids = (array) $workitem->getBids($workitem->getId());
                                $exists = false;
                                foreach ($bids as $array) {
                                    if ($array['id'] == $bid) {
                                        if ($array['bidder_id'] == $mechanic_id) {
                                            $is_mechanic = true;
                                        } else {
                                            $is_mechanic = false;
                                        }
                                        $exists = true;
                                        break;
                                    }
                                }
                                if ($exists) {
                                    $bid_info = $workitem->acceptBid($bid, $budget_id, $is_mechanic);
                                    // Journal notification
                                    $journal_message .= '@' . $_SESSION['nickname'] . " accepted {$bid_info['bid_amount']} from ".
                                        $bid_info['nickname'] . " " . ($is_mechanic ? ' as Developer ' : '') .
                                        "on #".$bid_info['worklist_id']. " Status set to Working";
                                    // mail notification
                                    Notification::workitemNotify(array('type' => 'bid_accepted',
                                                 'workitem' => $workitem,
                                                 'recipients' => array('mechanic', 'followers')));
                                } else {
                                    $_SESSION['workitem_error'] = "Failed to accept bid, bid has been deleted!";
                                }
                            }
                            // Send email to not accepted bidders
                            $this->sendMailToDiscardedBids($worklist_id);

                            $runner = new User();
                            $runner->findUserById($workitem->getRunnerId());
                            $runner->updateBudget(-$total, $workitem->getBudget_id());
                        } else {
                            $overBudget = money_format('%i', $total - $remainingFunds);
                            $_SESSION['workitem_error'] = "Failed to accept bids. Accepting this bids would make you " . $overBudget . " over your budget!";
                        }
                    }
                }
            }
            $redirectToDefaultView = true;
        }
        //Withdraw a bid
        if ($action == "withdraw_bid") {
            if (isset($_REQUEST['bid_id'])) {
                withdrawBid(intval($_REQUEST['bid_id']), $_REQUEST['withdraw_bid_reason']);
            } else {
                $fee_id = intval($_REQUEST['fee_id']);
                $res = mysql_query('SELECT f.bid_id, f.amount, w.runner_id FROM `' . FEES . '` AS f, ' . WORKLIST . ' AS w WHERE f.`id`=' . $fee_id . ' AND f.worklist_id = w.id');
                $fee = mysql_fetch_object($res);
                if ((int)$fee->bid_id !== 0) {
                    withdrawBid($fee->bid_id, $_REQUEST['withdraw_bid_reason']);
                } else {
                    deleteFee($fee_id);
                }

                // Update Runner's Budget
                $runner = new User();
                $runner->findUserById($fee->runner_id);
                $runner->updateBudget($fee->amount, $workitem->getBudget_id());
            }
            $redirectToDefaultView = true;
        }

        //Decline a bid
        if ($action == "decline_bid") {
            if (isset($_REQUEST['bid_id'])) {
                withdrawBid(intval($_REQUEST['bid_id']), $_REQUEST['decline_bid_reason']);
            } else {
                $fee_id = intval($_REQUEST['fee_id']);
                $res = mysql_query('SELECT f.bid_id, f.amount, w.runner_id FROM `' . FEES . '` AS f, ' . WORKLIST . ' AS w WHERE f.`id`=' . $fee_id . ' AND f.worklist_id = w.id');
                $fee = mysql_fetch_object($res);
                if ((int)$fee->bid_id !== 0) {
                    withdrawBid($fee->bid_id, $_REQUEST['decline_bid_reason']);
                } else {
                    deleteFee($fee_id);
                }

                // Update Runner's Budget
                $runner = new User();
                $runner->findUserById($fee->runner_id);
                $runner->updateBudget($fee->amount, $workitem->getBudget_id());
            }
            $redirectToDefaultView = true;
        }

        // we have a Journal message, send it to Journal - except for DRAFTS
        if(isset($journal_message) && $workitem->getStatus() != 'Draft') {
            sendJournalNotification($journal_message);
            //$postProcessUrl = WORKITEM_URL . $worklist_id . "?msg=" . $journal_message;
        }

        if ($redirectToDefaultView) {
            $this->redirect('./' . $worklist_id);
        }

        // handle the makeshift error I made..
        $erroneous = false;
        if (isset($_SESSION['workitem_error'])) {
            $erroneous = true;
            $the_errors = $_SESSION['workitem_error'];
            unset($_SESSION['workitem_error']);
            $this->write('erroneous', $erroneous);
            $this->write('the_errors', $the_errors);
        }
        // Process the request normally and display the page.

        //get workitem from db
        $worklist = $workitem->getWorkItem($worklist_id);
        $this->write('worklist', $worklist);

        //get bids
        $bids = $workitem->getBids($worklist_id);
        // get only those bids that have not expired, used to determine whether
        // runner can edit the job notes
        $this->write('activeBids', (array) $workitem->getBids($workitem->getId(), false));

        //Findout if the current user already has any bids.
        // Yes, it's a String instead of boolean to make it easy to use in JS.
        // Suppress names if not is_runner, or creator of Item. Still show if it's user's bid.

        $currentUserHasBid = "false";
        if(!empty($bids) && is_array($bids)) {
            foreach ($bids as &$bid) {
                if($bid['email'] == $currentUsername ) {
                    $currentUserHasBid = "true";
                    //break;
                }

                if (!($user->getId() == $bid['bidder_id']
                 || $user->isRunnerOfWorkitem($workitem) || ($worklist['status'] == 'SuggestedWithBid' && $workitem->getIsRelRunner())))  {
                    if ($user->getIs_admin() == 0) {
                        $bid['nickname'] = '*name hidden*';
                        $bid['bid_amount'] = '***';
                        $bid['email'] = '********';
                        $bid['notes'] = '********';
                    }
                }
                $bid['bid_created'] = convertTimezone($bid['unix_bid_created']);
                if ($bid['unix_bid_accepted'] > 0) {
                    $bid['bid_accepted'] = convertTimezone($bid['unix_bid_accepted']);
                } else {
                    $bid['bid_accepted'] = '';
                }
                if ($bid['unix_done_full'] > 0 && !empty($bid['unix_done_full'])) {
                    $bid['unix_done_full'] = convertTimezone($bid['unix_done_full']);
                } else {
                    $bid['unix_done_full'] = '';
                }
                // calculate Total Time to Complete
                if (isset($bid['unix_done_by']) && $bid['unix_done_by'] != 0) {
                    $timeToComplete = (int) $bid['unix_done_by'] - (int) $bid['unix_bid_created'];
                    if ($bid['unix_bid_accepted'] > 0) {
                        $timeElapsed = (int) $bid['unix_now'] - (int) $bid['unix_bid_accepted'];
                        $timeToComplete -= $timeElapsed;
                    }
                    $fullDays    = floor($timeToComplete/(60*60*24));
                    $fullHours   = floor(($timeToComplete-($fullDays*60*60*24))/(60*60));
                    $fullMinutes = floor(($timeToComplete-($fullDays*60*60*24)-($fullHours*60*60))/60);
                    $bid['time_to_complete']= $fullDays . ($fullDays==1?" day, ":" days, ").$fullHours. ($fullHours==1?" hour and ":" hours and ").$fullMinutes.($fullMinutes==1?" minute.":" minutes.");
                } else {
                    $bid['time_to_complete'] = null;
                }
            }
        }
        // break reference to $bid
        unset($bid);
        //get fees
        $fees = $workitem->getFees($worklist_id);
        $this->write('fees', $fees);

        //total fee
        $total_fee = $workitem->getSumOfFee($worklist_id);

        //accepted bid amount
        $accepted_bid_amount = 0;
        foreach ($fees as $fee){
            if ($fee['desc'] == 'Accepted Bid') {
                $accepted_bid_amount = $fee['amount'];
            }
        }

        //review fees
        $project = new Project();
        $project_roles = $project->getRoles($workitem->getProjectId(), "role_title = 'Reviewer'");
        $crFee = 0;
        if (count($project_roles) != 0) {
            $crRole = $project_roles[0];
            if ($crRole['percentage'] !== null && $crRole['min_amount'] !== null) {
                $crFee = ($crRole['percentage'] / 100) * $accepted_bid_amount;
                if ((float) $crFee < $crRole['min_amount']) {
                   $crFee = $crRole['min_amount'];
                }
            }
        }
        $this->write('crFee', $crFee);

        $user_id = (isset($_SESSION['userid'])) ? $_SESSION['userid'] : "";
        $is_runner = isset($_SESSION['is_runner']) ? $_SESSION['is_runner'] : 0;
        $is_admin = isset($_SESSION['is_admin']) ? $_SESSION['is_admin'] : 0;
        $is_payer = isset($_SESSION['is_payer']) ? $_SESSION['is_payer'] : 0;
        $creator_id = isset($worklist['creator_id']) ? $worklist['creator_id'] : 0;
        $mechanic_id = isset($worklist['mechanic_id']) ? $worklist['mechanic_id'] : 0;

        $has_budget = 0;
        if (! empty($user_id)) {
            $user = new User();
            $user->findUserById($user_id);
            if ($user->getBudget() > 0) {
                $has_budget = 1;
            }
        }

        $workitem = WorkItem::getById($worklist['id']);
        if ($worklist['project_id']) {
            $workitem_project = new Project($worklist['project_id']);
        }
        $projects = Project::getProjects();

        $allowEdit = false;
        $classEditable = "";
        if (($workitem->getIsRelRunner() || ($user->getIs_admin() == 1 && $is_runner)) ||
            ($creator_id == $user_id && $worklist['status'] == 'Suggested' && is_null($worklist['runner_id'])) ||
            ($creator_id == $user_id && $worklist['status'] == 'SuggestedWithBid' && is_null($worklist['runner_id']))) {
            $allowEdit = true;
            if ($action !="edit") {
                $classEditable = " editable";
            }
        }
        $this->write('classEditable', $classEditable);
        $this->write('allowEdit', $allowEdit);

        $hideFees = false;
        if ($worklist['status'] == 'Bidding' || $worklist['status'] == 'Suggested' || $worklist['status'] == 'SuggestedWithBid') {
            $hideFees = true;
        }
        $this->write('hideFees', $hideFees);
        $this->write('bids', $bids);

        $this->write('userHasRights', $this->hasRights($user_id, $workitem));

        $this->write('mechanic', $workitem->getUserDetails($worklist['mechanic_id']));

        global $displayDialogAfterDone;
        if ($displayDialogAfterDone == true && $worklist['mechanic_id'] > 0) {
            $_SESSION['displayDialogAfterDone'] = false;
            $this->write('displayDialogAfterDone', 1);
        } else {
            $this->write('displayDialogAfterDone', 0);
        }

        $reviewer = new User();
        $reviewer->findUserById($workitem->getCReviewerId());
        $this->write('reviewer', $reviewer);

        $this->write('action_error', isset($action_error) ? $action_error : '');

        $this->write('comments', Comment::findCommentsForWorkitem($worklist['id']));
        $this->write('entries', $this->getTaskPosts($worklist['id']));
        $this->write('message', isset($message) ? $message : '');
        $this->write('currentUserHasBid', $currentUserHasBid);
        $this->write('has_budget', $has_budget);
        $this->write('promptForReviewUrl', $promptForReviewUrl);
        $this->write('status_error', $status_error);
        $this->write('{{userinfotoshow}}', (isset($_REQUEST['userinfotoshow']) && isset($_SESSION['userid'])) ? $_REQUEST['userinfotoshow'] : 0);

        parent::run();
    }

    function hasRights($userId, $workitem) {
        $project = new Project();
        $project->loadById($workitem->getProjectId());
        $users_favorite = new Users_Favorite();

        if($project->getCrUsersSpecified()) { // if only specified users are allowed
            if($project->isProjectCodeReviewer($userId)){
                return true;
            }
            return false;
        } else {
            if ($project->getCrAnyone()) {
                return true;
            } else if ($project->getCrAdmin()) {
                $admin_fav = $users_favorite->getMyFavoriteForUser($project->getOwnerId(), $userId);
                if ($admin_fav['favorite']) {
                    return true;
                }
            } else if ($project->getCrFav() && $users_favorite->getUserFavoriteCount($userId) >= 3) {
                return true;
            } else if ($project->getCrRunner()) {
                $runner_fav = $users_favorite->getMyFavoriteForUser($workitem->getRunnerId(),$userId);
                if ($runner_fav['favorite']) {
                    return true;
                }
            } else if($project->isProjectCodeReviewer($userId)){
                return true;
            }
        }
        return false;
    }

    function sendMailToDiscardedBids($worklist_id)    {
        // Get all bids marked as not accepted
        $query = "SELECT bids.email, u.nickname FROM ".BIDS." as bids
                        INNER JOIN ".USERS." as u on (u.id = bids.bidder_id)
                        WHERE bids.worklist_id=$worklist_id AND bids.withdrawn = 0 AND bids.accepted = 0";
        $result_query = mysql_query($query);
        $bids = array();
        while($row = mysql_fetch_assoc($result_query)) {
            $bids[] = $row;
        }

        $workitem = new WorkItem($worklist_id);
        $mechanic = $workitem->getMechanic()->getUsername();
        foreach ( $bids as $bid ) {
            // Make sure the mechanic is not sent a discarded email
            if ($mechanic != $bid['email']){
                Notification::workitemNotify(
                    array(
                        'type' => 'bid_discarded',
                        'workitem' => $workitem,
                        'emails' => array($bid['email'])
                    ),
                    array(
                        'who' => $bid['nickname']
                    ));
            }
        }
    }

    function changeStatus($workitem, $newStatus, $user) {

        $allowable = array("Draft", "Suggested", "SuggestedWithBid", "Code Review", "Functional", "Pass", "Completed");

        if ($workitem->getIsRelRunner() || ($user->getIs_admin() == 1 && ($is_runner))) {
            if($newStatus == 'Bidding' && in_array($workitem->getStatus(), $allowable)) {
                $workitem->setRunnerId($user->getId());
            }
        }

        if ($newStatus == 'Done' && $workitem->getProjectId() == 0) {
            return false;
        }
    // Set all 0.00 fees as paid
        if ($newStatus == 'Done' ) {
            if (!$workitem->flagAll0FeesAsPaid()) {
                return false;
            }
        }
        $workitem->setStatus($newStatus);
        $projectId = $workitem->getProjectId();
        $thisProject = new Project($projectId);
        $repoType = $thisProject->getRepo_type();

        // Generate diff and send to pastebin if we're in REVIEW
        if ($newStatus == "Code Review") {
            //reset code_review flags
            $workitem->resetCRFlags();
            if ($repoType == 'svn') {
                if (substr($workitem->getSandbox(), 0, 4) == "http") {

                    // Sandbox URLs look like:
                    // https://dev.worklist.net/~johncarlson21/worklist
                    // 0     12               3              4
                    $sandbox_array = explode("/", $workitem->getSandbox());

                    $username = isset($sandbox_array[3]) ? $sandbox_array[3] : "~";
                    $username = substr($username, 1); // eliminate the tilde

                    $sandbox = isset($sandbox_array[4]) ? $sandbox_array[4] : "";

                    try {
                        $result = SandBoxUtil::pasteSandboxDiff($username, $workitem->getId(), $sandbox);
                        $comment = "Code Review available here:\n$result";
                        $rt = $this->addComment($workitem->getId(), $user->getId(), $comment);
                    } catch (Exception $ex) {
                        error_log("Could not paste diff: \n$ex");
                    }
                }
            } elseif ($repoType == 'git') {
                $GitHubUser = new User($workitem->getMechanicId());
                $pullResults = $GitHubUser->createPullRequest($workitem->getId(), $thisProject);

                if (!$pullResults['error'] && !isset($pullResults['data']['errors'])) {
                    $codeReviewURL = $pullResults['data']['html_url'] . '/files';
                    $comment = "Code Review available here:\n" . $codeReviewURL;
                } else {
                    $comment = $pullResults['error']
                        ? "We had problems making your request to GitHub\n"
                        : "The following error was returned when making your pull request:\n";
                    $comment .= isset($pullResults['data']['errors'])
                        ? $pullResults['data']['errors'][0]['message']
                        : "Unknown error";
                }
                $rt = $this->addComment($workitem->getId(), $user->getId(), $comment);
            }
        }

        if ($newStatus == 'Functional' && $repoType == 'git') {
            $runner = $workitem->getRunnerId();
            $GitHubUser = new User($runner);
            $runnerEmail = $GitHubUser->getUsername();
            $GitHubBidder = new User($workitem->getMechanicId());
            $githubDetails = $GitHubBidder->getGitHubUserDetails($thisProject);
            $gitHubUsername = $githubDetails['data']['login'];
            $repoDetails = $thisProject->extractOwnerAndNameFromRepoURL();
            $usersFork = 'https://github.com/' . $gitHubUsername . "/" . $repoDetails['name'] . ".git";
            $emailTemplate = 'functional-howto';
            $data = array(
                'branch_name' => $workitem->getId(),
                'runner' => $GitHubUser->getNickname(),
                'users_fork' => $usersFork,
                'master_repo' => str_replace('https://', 'git://', $thisProject->getRepository())
            );
            $senderEmail = 'Worklist <contact@worklist.net>';
            sendTemplateEmail($runnerEmail, $emailTemplate, $data, $senderEmail);
        } else if ($newStatus =='Functional' && ! ($workitem->getIsRelRunner() || ($user->getIs_admin() == 1 ))) {
            return true;
        }

        if ($newStatus == 'Working') {
            $thisProject->setActive(1);
            $thisProject->save();
        }

        // notifications for subscribed users
        Notification::massStatusNotify($workitem);

        if ($newStatus != 'SuggestedWithBid') {
            $options = array(
                'type' => 'status-notify',
                'workitem' => $workitem,
            );
            $data = array(
                'nick' => $user->getNickname(),
                'status' => $newStatus,
            );
            Notification::workitemNotifyHipchat($options, $data);
        }

        return true;
    }

    function addComment($worklist_id, $user_id, $comment_text, $parent_comment_id) {
        // in case the comment is a reply to another comment,
        // we'll fetch the original comment's email <mikewasmike>
        $comment = new Comment();
        $comment->setWorklist_id((int) $worklist_id);
        $comment->setUser_id((int) $user_id);
        $correspondent = null;

        if (isset($parent_comment_id)) {
            $comment->setComment_id((int) $parent_comment_id);
            $originalComment = new Comment();
            $originalComment->findCommentById((int) $parent_comment_id);
            $cuser = new User();
            $cuser->findUserById($originalComment->getUser_id());
            // add the author of the parent comment, as long as it's not the
            // same as the logged in user, in order to prevent email notification
            // to the author of the new comment
            if ($cuser->isActive() && ($cuser->getId() != getSessionUserId())) {
                $correspondent = array($cuser->getUsername());
            } else {
                $correspondent = array();
            }
        }

        $comment->setComment($comment_text);

        try {
            $id = $comment->save();
        } catch(Exception $e) {
            error_log("Failure saving comment:\n".$e);
        }
        $redirectToDefaultView = true;
        $result = array('correspondent' => $correspondent, 'id' => $id);
        return $result;
    }

    function  getTaskPosts($item_id) {
        $entry = new EntryModel();
        return $entry->latestFromTask($item_id);
    }
}
