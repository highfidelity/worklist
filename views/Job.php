<?php

use \Michelf\Markdown;

class JobView extends View {
    public $layout = 'NewWorklist';
    public $title = '%d: %s - Worklist';

    public $stylesheets = array(
        'css/legacy/workitem.css',
        'css/review.css',
        'css/favorites.css',
        'css/budget.css',
        'css/entries.css',
        'css/job.css'
   );

    public $scripts = array(
        'js/jquery/jquery.template.js',
        'js/jquery/jquery.jeditable.min.js',
        'js/jquery/jquery.tallest.js',
        'js/jquery/jquery.metadata.js',
        'js/jquery/jquery.blockUI.js',
        'js/ajaxupload/ajaxupload.js',
        'js/dragdealer/dragdealer.js',
        'js/datepicker.js',
        'js/timepicker.js',
        'js/review.js',
        'js/favorites.js',
        'js/github.js',
        'js/skills.js',
        'js/entries.js',
        'js/job.js'
    );

    public function render() {
        $worklist = $this->worklist = $this->read('worklist');
        $this->workitem = $this->read('workitem');
        $this->user = $this->read('user');
        $this->workitem_project = $this->read('workitem_project');
        $this->title = sprintf($this->title, $worklist['id'], $worklist['summary']);

        $this->bids = $this->read('bids');
        $this->fees = $this->read('fees');

        $this->order_by = $this->read('order_by');
        $this->action = $this->read('action');
        $this->action_error = $this->read('action_error');
        $this->classEditable = $this->read('classEditable');
        $this->allowEdit = (int) $this->read('allowEdit');
        $this->userHasRights = (int) $this->read('userHasRights');
        $this->isGitHubConnected = (int) $this->read('isGitHubConnected');
        $this->message = $this->read('message');
        $this->currentUserHasBid = (int) $this->read('currentUserHasBid');
        $this->has_budget = (int) $this->read('has_budget');
        $this->is_project_runner = (int) $this->read('is_project_runner');
        $this->is_project_founder = (int) $this->read('is_project_founder');
        $this->promptForReviewUrl =  (int) $this->read('promptForReviewUrl');
        $this->status_error =  (int) $this->read('status_error');
        $this->displayDialogAfterDone =  (int) $this->read('displayDialogAfterDone');
        $this->userinfotoshow =  (int) $this->read('userinfotoshow');

        $this->erroneous = $this->read('erroneous');
        $this->the_errors = $this->read('the_errors');

        $this->reviewer = $this->read('reviewer');

        return parent::render();
    }

    public function runSandboxCheck() {
        return $this->worklist['status'] == 'Functional' && $this->currentUser['is_runner'];
    }

    public function canUpload() {
    	return $this->worklist['status'] != 'Done' && (int) $this->currentUser['id'] > 0;
    }

    public function editing() {
        return $this->action == 'edit';
    }

    public function canEditAndNotEditing() {
        return $this->allowEdit && $this->action != 'edit';
    }

    public function canEditAndEditing() {
        return $this->allowEdit && $this->action == 'edit';
    }

    public function canChangeStatus() {
        $workitem = $this->workitem;
        $worklist = $this->worklist;
        $user = $this->user;
        $is_runner = $this->currentUser['is_runner'];
        return $this->currentUser['id'] && (
            (   !$this->workitem->getIsRelRunner() 
                || ($user->getIs_admin() == 1 && $is_runner) 
                || ($worklist['mechanic_id'] == $this->currentUser['id']) &&
                $worklist['status'] != 'Done'
            ) 
          || $workitem->getIsRelRunner()
          || ($worklist['creator_id']== $this->currentUser['user_id'] && $worklist['status'] != 'Done')
        );
    }

    public function editableStatusSelect() {
        $workitem = $this->workitem;
        $worklist = $this->worklist;
        $user = $this->user;
        $is_runner = $this->currentUser['is_runner'];
        $statusListMechanic = $this->read('statusListMechanic');
        $statusListRunner = $this->read('statusListRunner');
        $statusListCreator = $this->read('statusListCreator');

        $ret = '';
        if (!
            ($workitem->getIsRelRunner() || ($user->getIs_admin() == 1 && $is_runner)) 
          &&($worklist['mechanic_id'] == $this->currentUser['id'])
          && $worklist['status'] != 'Done') 
        { 
            //mechanics
            foreach ($statusListMechanic as $status) {
                if ($status != $worklist['status']) {
                    $printStatus = $status == 'Review' ? 'Code Review' : $status;
                    $ret .= '<option value="' . $status . '">' . $printStatus  . '</option>';
                }
            }
        } else if ($workitem->getIsRelRunner() || ($user->getIs_admin() == 1 && $is_runner)) { 
            //runners and admins
            foreach ($statusListRunner as $status) {
                if ( $status != $worklist['status'] ) {
                    $printStatus = $status == 'Review' ? 'Code Review' : $status;
                    $ret .= '<option value="' .  $status . '">' . $printStatus . '</option>';
                }
            }
        } else if (
             $worklist['creator_id']== $this->currentUser['id'] 
          && $worklist['status'] != 'Working' 
          && $worklist['status'] != 'Functional' && $worklist['status'] != 'Review' 
          && $worklist['status'] != 'Completed' && $worklist['status'] != 'Done' 
        ) {
            //creator
            foreach ($statusListCreator as $status) {
                if (!($status == 'Suggested' && $worklist['status'] == 'SuggestedWithBid') && $status != $worklist['status']) {
                    $printStatus = $status == 'Review' ? 'Code Review' : $status;
                    $ret .= '<option value="' . $status . '">' . $printStatus . '</option>';
                }
            }
        }

        return $ret;
    }

    public function jobBudget() {
        $workitem = $this->workitem;
        $worklist = $this->worklist;
        $user = $this->user;
        $ret = '';
        if (
            (
                $user->isRunnerOfWorkitem($workitem) 
              || (
                    array_key_exists('userid', $_SESSION)
                  && (
                        $_SESSION['userid'] == $worklist['budget_giver_id'] 
                      || strpos(BUDGET_AUTHORIZED_USERS, "," . $_SESSION['userid'] . ",") !== false
                    ) 
                ) 
            ) 
          && !empty($worklist['budget_id'])
        ) {
            if ($this->action !="edit") {
                $ret = '<div class="job-budget">'
                    .    '<div class="project-label">Budget:</div>'
                    .    $worklist['budget_id'] . " - " . htmlspecialchars($worklist['budget_reason'])
                    . '</div>';
            }
        }
        return $ret;
    }

    public function statusDone() {
        return $this->worklist['status'] == "Done";
    }

    public function isGitProject() {
        return $this->workitem_project->getRepo_type() == 'git';
    }

    public function editableRunnerBox() {
        $workitem = $this->workitem;
        $worklist = $this->worklist;
        $user = $this->user;
        $runnerslist = Project::getAllowedRunnerlist($worklist['project_id']);
        $ret = ' ';
        if ($worklist['runner_nickname'] != 'Not funded') {
            $ret .= 
                '<a href="./user/' . $worklist['runner_id'] . '"  id="ping-r-btn"' .
                ' title="' . (isset($_SESSION['userid']) ? "Ping Designer" : "Log in to Ping Designer") . '"' .
                ' data-user-id="' . $worklist['runner_id'] . '">' .
                    substr($worklist['runner_nickname'], 0, 9) . (strlen($worklist['runner_nickname']) > 9 ? '...' : '') .
                '</a>';
        } else {
            $ret .= $worklist['runner_nickname'];
        }
        $ret .= '<span class="changeRunner"><select name="runner">';
        foreach ($runnerslist as $r) {
            $ret .= 
                '<option value="' . $r->getId() . '"' . (($worklist['runner_id'] == $r->getId()) ? ' selected="selected"' : '') . '>' . 
                    $r->getNickname() . 
                '</option>';
        }
        $ret .= 
            '</select>' .
            '<input type="button" class="smbutton" name="changerunner" value="Change Designer" />' .
            '<input type="button" class="smbutton" name="cancel" value="Cancel" />' .
            '</span>';
        return $ret;
    }

    public function nonEditableRunnerBox() {
        $worklist = $this->worklist;
        $ret = '';
        if ($worklist['runner_nickname'] != 'Not funded' && $worklist['runner_nickname'] != '') {
            $ret .= 
                '<span id="pingRunner" class="runnerName" title="' . (isset($_SESSION['userid']) ? "Ping Designer" : "Log in to Ping Designer") .'">' .
                '<a href="#">Designer:</a></span>' .
                '<a href="./user/' . $worklist['runner_id'] . '" >' . 
                    substr($worklist['runner_nickname'], 0, 9) . (strlen($worklist['runner_nickname']) > 9 ? '...' : '') . 
                '</a>';
        } else {
            $ret .= '<span class="runnerName" title="Ping Designer">Designer:</span> Not funded';
        }
        return $ret;
    }

    public function mechanicBox() {
        $worklist = $this->worklist;
        $fees = $this->fees;
        $mech = '';

        if( count($fees) >0 ) {
            foreach( $fees as $fee) {
                if ($fee['desc'] == 'Accepted Bid') {
                    $mech = $fee['nickname'];
                }
            }
        }
        if ($mech == '') {
            $mech = '<span class="mechanicName">Developer:</span>Not assigned';
        } else {
            $tooltip = isset($_SESSION['userid']) ? "Ping Developer" : "Log in to Ping Developer";
            $mech = 
                '<span id ="pingMechanic" class="mechanicName" title="' . $tooltip . '" >' . 
                  '<a href="#">Developer:</a>' . 
                '</span>' . 
                '<a id="ping-btn" href="./user/' . $worklist['mechanic_id'] . '" >' . $mech . '</a>';
        }
        return $mech;
    }

    public function canEditSummary() {
        $workitem = $this->workitem;
        $worklist = $this->worklist;
        $user = $this->user;
        $is_runner = $this->currentUser['is_runner'];

       return (
             (($workitem->getIsRelRunner() || ($user->getIs_admin() == 1 && $is_runner)) && $worklist['status']!='Done') 
          || (
                 $worklist['creator_id'] == $this->currentUser['id']  
              && ($worklist['status']=='Suggested' || $worklist['status']=='SuggestedWithBid') 
              && is_null($worklist['runner_id'])
            )
        );
    }

    public function statusInfo() {
        $workitem = $this->workitem;
        $worklist = $this->worklist;
        $user = $this->user;
        $is_runner = $this->currentUser['is_runner'];
        $statusListRunner = $this->read('statusListRunner');
        $statusListCreator = $this->read('statusListCreator');

        $ret = '';
        if ($workitem->getIsRelRunner() || ($user->getIs_admin() == 1 && $is_runner) || ($this->currentUser['id'] == $worklist['runner_id'])) {
            $ret .= '<select id="status" name="status">';
            foreach ($statusListRunner as $status) {
                $ret .= '<option value="' . $status . '"' . ($status == $worklist['status'] ? ' selected="selected"' : '') .'>' . $status . '</option>';
            }
            $ret .= '</select>';
        } else if ($worklist['creator_id'] == $this->currentUser['id'] && $mechanic_id == $user_id) {
            $ret .= '<select id="status" name="status">';
            foreach ($statusListCreator as $status) {
                $ret .= '<option value="' . $status . '"' . ($status == $worklist['status'] ? ' selected="selected"' : '') . '>' . $status . '</option>';
            }
            $ret .= '</select>';
        } else if ($worklist['creator_id'] == $this->currentUser['id']) {
            $ret .= '<select id="status" name="status">';
            foreach ($statusListCreator as $status) {
                $ret .= '<option value="' . $status . '" ' . ($status == $worklist['status'] ? ' selected="selected"' : '') . '>' .  $status . '</option>';
            }
            $ret .= '</select>';
        } else { 
            $ret .= $worklist['status'] . ' <input type="hidden" id="status" name="status" value="' . $worklist['status'] . '" />';
        }
        return $ret;
    }


    public function canEditNotes() {
        $worklist = $this->worklist;
        $is_project_runner = $this->read('is_project_runner');
        $user = $this->user;
        $is_runner = $this->currentUser['is_runner'];
        return (($is_project_runner || ($user->getIs_admin() == 1 && $is_runner) || $worklist['creator_id'] == $this->currentUser['id']) && ($worklist['status'] != 'Done'));
    }

    public function notesHtml() {
        $worklist = $this->worklist;
        return replaceEncodedNewLinesWithBr($worklist['notes']);
    }

    public function notesHtmlWithLinks() {
        $worklist = $this->worklist;
        return replaceEncodedNewLinesWithBr(linkify($worklist['notes']));
    }

    public function canSeeBudgetArea() {
        $workitem = $this->workitem;
        $worklist = $this->worklist;
        $user = $this->user;
        return (
            $user->isRunnerOfWorkitem($workitem) 
          || $_SESSION['userid'] == $worklist['budget_giver_id']
          || strpos(BUDGET_AUTHORIZED_USERS, "," . $_SESSION['userid'] . ",") !== false
        );
    }

    public function isRunnerOfWorkitem() {
        $workitem = $this->workitem;
        $user = $this->user;
        return $user->isRunnerOfWorkitem($workitem);
    }

    public function getBudgetCombo() {
        $worklist = $this->worklist;
        $user = $this->user;
        return $user->getBudgetCombo($worklist['budget_id']);
    }

    public function canEditSandboxUrlOnEdit() {
        $workitem = $this->workitem;
        $worklist = $this->worklist;
        $user = $this->user;
        $is_runner = $this->currentUser['is_runner'];
        return (($workitem->getIsRelRunner() || $worklist['creator_id'] == $this->currentUser['id'] || ($user->getIs_admin() == 1 && $is_runner)) && ($worklist['status'] != 'Done'));
    }


    public function canEditSandboxUrl() {
        $workitem = $this->workitem;
        $worklist = $this->worklist;
        $user = $this->user;
        $is_runner = $this->currentUser['is_runner'];
        return (
             (strcasecmp($worklist['status'], 'Working') == 0 || strcasecmp($worklist['status'], 'Review') == 0 || strcasecmp($worklist['status'], 'Functional') == 0) 
          && ($workitem->getIsRelRunner() || ($user->getIs_admin() == 1 && $is_runner) ||($worklist['mechanic_id'] == $this->currentUser['id']))
        );
    }

    public function canViewDiff() {
        $workitem = $this->workitem;
        $worklist = $this->worklist;
        $user = $this->user;
        $is_project_founder = $this->read('is_project_founder');
        return (
             ($worklist['status'] == 'Functional' || $worklist['status'] == 'Review')
          && $worklist['sandbox'] != 'N/A' 
          || ($worklist['status'] == 'Working' && ($user->isRunnerOfWorkitem($workitem) || $is_project_founder || $user->getId() == $worklist['mechanic_id']))
        );
    }

    public function canEditProject() {
        $is_project_runner = $this->read('is_project_runner');
        $worklist = $this->worklist;
        return (
             ($is_project_runner || $worklist['creator_id'] == $this->currentUser['id'] || ($this->currentUser['is_admin'] && $this->currentUser['is_runner'])) 
          && ($worklist['status'] != 'Done')
        );
    }

    public function editableProjectSelect() {
        $worklist = $this->worklist;
        $filter = new Agency_Worklist_Filter();
        $filter->setProjectId($worklist['project_id']);
        return $filter->getProjectSelectbox('Select Project', 0, 'project_id', 'project_id');
    }

    public function projectUrl() {
        $worklist = $this->worklist;
        return Project::getProjectUrl($worklist['project_id']);
    }

    public function projectWebsiteUrl() {
        $worklist = $this->worklist;
        $project = new Project($worklist['project_id']);
        return $project->getWebsiteUrl();
    }

    public function activeBidsCount() {
        return count($this->read('activeBids'));
    }

    public function canComment() {
        return $this->currentUser['id'] && ($this->worklist['status'] != 'Done');
    }

    public function comments() {
        if ($this->order_by != 'DESC') {
            return $this->read('comments');
        } else {
            return array_reverse($this->read('comments'), true);
        }
        
    }

    public function isDescOrder() {
        return $this->order_by == 'DESC';
    }

    public function skillsCount() {
        return count($this->workitem->getSkills());
    }

    public function commaSeparatedSkills() {
        return implode(', ', $this->workitem->getSkills());
    }

    public function isBug() {
        return $this->workitem->getBugJobId() > 0 || $this->workitem->getIs_bug() == 1;
    }

    public function canReview() {
        $workitem = $this->workitem;
        $worklist = $this->worklist;
        $user = $this->user;
        $is_runner = $this->currentUser['is_runner'];
        return (
             ($this->currentUser['id'] > 0 && $user->isEligible() && $worklist['mechanic_id'] != $this->currentUser['id'])
          && (
                 $worklist['status'] == 'Review' 
              && (! $workitem->getCRCompleted())
              && (
                     (! $workitem->getCRStarted()) 
                  || $this->currentUser['id'] == $workitem->getCReviewerId() 
                  || $workitem->getIsRelRunner() 
                  || ($user->getIs_admin() == 1 && $is_runner)
                )
            )
        );
    }

    public function canEndReview() {
        $workitem = $this->workitem;
        $user = $this->user;
        $is_runner = $this->currentUser['is_runner'];
        return 
             $this->read('userHasRights') 
          && (
                 $this->currentUser['id'] == $workitem->getCReviewerId() 
              || $workitem->getIsRelRunner() 
              || ($user->getIs_admin() == 1 && $is_runner)
            );
    }

    public function canBid() {
        $worklist = $this->worklist;
        return $worklist['status'] == 'Bidding' 
          || $worklist['status'] == 'SuggestedWithBid' 
          || ($worklist['status'] == 'Suggested' && $worklist['creator_id'] == $this->currentUser['id']);
    }

    public function userIsEligible() {
        return $this->user->isEligible();
    }

    public function canAcceptBids() {
        $bids = $this->read('bids');
        $workitem = $this->workitem;
        $user = $this->user;
        $is_runner = $this->currentUser['is_runner'];
        return (
             (!empty($bids)) 
          && ($workitem->getIsRelRunner() || ($user->getIs_admin() == 1 && $is_runner) || $this->currentUser['id'] == $workitem->getRunnerId()) 
          && count($bids) >1 
          && !$workitem->hasAcceptedBids()
          && ((($workitem->getStatus()) == "Bidding") || $workitem->getStatus() == "SuggestedWithBid")
        );
    }

    public function bidsList() {
        $bids = $this->read('bids');
        $workitem = $this->workitem;
        $worklist = $this->worklist;
        $user = $this->user;
        $is_runner = $this->currentUser['is_runner'];
        $is_project_runner = $this->read('is_project_runner');

        $ret = '';
        foreach($bids as $bid) {
            $biddings = array();

            if ($user->getId() != $bid['bidder_id'] && $bid['expires'] < 0) {
                continue;
            }

            if ($user->getId() == $bid['bidder_id'] && $bid['expires'] < 0) {
                $expired_class = ' expired_warn';
            } else {
                $expired_class = '';
            }
            $canSeeBid = $user->getIs_admin() == 1 || $is_project_runner || $user->isRunnerOfWorkitem($workitem) ||
                         $user->getId() == $bid['bidder_id'] || ($worklist['status'] == 'SUGGESTEDwithBID' && $workitem->getIsRelRunner());
            $row_class = "";
            $row_class .= ($this->currentUser['id']) ? 'row-bidlist-live ' : '' ;
            $row_class .= ($this->read('view_bid_id') == $bid['id']) ? ' view_bid_id ' : '' ;
            $row_class .= 'biditem';
            $row_class .= ($canSeeBid)
                        ? "-" . $bid['id'] . ' clickable'
                        : '';
            $row_class .= $expired_class;
            $ret .= '<tr class="' . $row_class . '">';

            // store bid info into jquery metadata so we won't have to fetch it again on user click
            // but only if user is runner or creator 15-MAR-2011 <godka>
            $notes = addcslashes(preg_replace("/\r?\n/", "<br />", $bid['notes']),"\\\'\"&\n\r<>");

            if ($canSeeBid) {
                $ret .= 
                    "<script type='data'>".
                        "{id: {$bid['id']}, " .
                        "nickname: '{$bid['nickname']}', " .
                        "email: '{$bid['email']}', " .
                        "amount: '{$bid['bid_amount']}', " .
                        "bid_accepted: '{$bid['bid_accepted']}', " .
                        "bid_created: '{$bid['bid_created']}', " .
                        "bid_expires: '" . ($bid['expires'] ? relativeTime($bid['expires']) : "Never") . "', " .
                        "time_to_complete: '{$bid['time_to_complete']}', " .
                        "done_in: '{$bid['done_in']}', " .
                        "bidder_id: {$bid['bidder_id']}, " .
                        "notes: '" .  replaceEncodedNewLinesWithBr($notes) . "'}" .
                    "</script>";
            }
            $ret .= 
                 '<td>'
                . (
                    $canSeeBid 
                      ? '<a href="./user/' . $bid['bidder_id'] . '" bidderId="' . $bid['bidder_id'] . '">' . getSubNickname($bid['nickname']) . '</a>' 
                      : $bid['nickname']
                  )
                .'</td>'
                .'<td class="money">$ ' . $bid['bid_amount'] . '</td>'
                .'<td class="money">' .$bid['done_in'] . '</td>';

            $ret .= '</tr>';
        }
        if (!$ret) {
            $ret = '<tr><td colspan="3">No bids yet.</td></tr>';
        }
        return $ret;
    }

    function userIsMechanic() {
        return $this->worklist['mechanic_id'] == $this->currentUser['id'];
    }

    function feesList() {
        $fees = $this->fees;
        $workitem = $this->workitem;
        $worklist = $this->worklist;
        $user = $this->user;
 
        $feeTotal = 0;
        $ret = '';
        
        foreach($fees as $fee) {
            $paid = (bool) $fee['paid'];
            $feeTotal += (float) $fee['amount'];
            $date = explode("/", $fee['date']);
            $ret .= 
                '<tr class="row-feelist-live">' .
                    '<script type="data">' .
                        "{id: {$fee['id']}, " .
                        "nickname: '{$fee['nickname']}', " .
                        "user_id: '{$fee['user_id']}', " .
                        "amount: '{$fee['amount']}', " .
                        "fee_created: '{$fee['date']}', " .
                        "desc:\"" .  $fee['desc'] . "\"}" .
                    '</script>' .
                    '<td class="nickname who">' .
                        '<a href="./user/' . $fee['user_id'] . '"  title="' . $fee['nickname'] . '">' .
                            getSubNickname($fee['nickname'], 8) .
                        '</a>' .
                    '</td>' .
                    '<td class="fee">' .
                        '$' . $fee['amount'] . 
                    '</td>' .
                    '<td class="pre fee-description what">&nbsp;</td>' .
                    '<td class="when">' .
                        date( "M j", mktime(0, 0, 0, $date[0], $date[1], $date[2])) .
                    '</td>' .
                    '<td class="paid">' .
                        (
                            $this->currentUser['is_payer']
                                ?
                                    '<a href="#" class = "paid-link" id="feeitem-' . $fee['id'] . '">' .
                                        ($paid ? "Yes" : "No") .
                                    '</a>'
                                :
                                    ($paid ? "Yes" : "No")
                        ) . ' ' .
                        (
                            (
                                 $worklist['status'] != 'Done'
                              && (
                                    (
                                         $workitem->getIsRelRunner() 
                                      || ($user->getIs_admin() == 1 && $this->currentUser['is_runner'])
                                      || $this->currentUser['id'] == $workitem->getRunnerId() 
                                      || $this->currentUser['id'] == $fee['user_id']
                                    ) 
                                  && ($this->currentUser['id'] && !$paid)
                                )
                            )
                                ? '<a href="#" id="wd-' . $fee['id'] . '" class="wd-link" title="Delete Entry">delete</a>' : ''
                        ) .
                    '</td>' .
                '</tr>' .
                '<tr>' .
                    '<td colspan="5" class="bid-notes">' .
                        '<p>' . $fee['desc'] . '</p>' .
                        (
                            ($fee['desc'] == 'Accepted Bid' && ($worklist['status'] == 'Review' || $worklist['status'] == 'Completed' || $worklist['status'] == 'Done'))
                                ? "<p><strong>Bid Notes:</strong>\n" . $fee['bid_notes'] . '</p>' : ''
                        ) .
                        '</td>' .
                    '</tr>';
        }
        if ($ret) {
            $ret .=
                '<tr id="job-total">' .
                    '<td colspan="5">' .
                            '<h5>Job Total :</h5>' .
                            '<span class="data">$ ' . number_format($feeTotal, 2)  . '</span>' .
                    '</td>' .
                '</tr>';            
        } else {
            $ret = '<tr><td colspan="5">No fees yet.</td></tr>';
        }
        return $ret;
    }

    public function userIsFollowing() {
        return (int) $this->workitem->isUserFollowing($this->currentUser['id']);
    }

    public function showAcceptBidButton() {
        $worklist = $this->worklist;
        $is_project_runner = $this->read('is_project_runner');
        $user = $this->user;
        return (int) (
            $is_project_runner 
          || ($user->getIs_admin() == 1 && $this->currentUser['is_runner']) 
          || (isset($worklist['runner_id']) && $this->currentUser['id'] == $worklist['runner_id'])
        );
    }

    public function hasAcceptedBids() {
        return (int) $this->workitem->hasAcceptedBids();
    }

    public function insufficientRightsToEdit() {
        $worklist = $this->worklist;
        return (int) (
            (!$worklist['status'] == 'Suggested' || !$worklist['status'] == 'SuggestedWithBid') 
          && !$worklist['creator_id'] == $this->currentUser['id']
        );
    }

    public function showPingBidderButton() {
        $worklist = $this->worklist;
        $is_project_runner = $this->read('is_project_runner');
        $user = $this->user;

        return (int) (
            ($worklist['status'] == 'Bidding' && ($is_project_runner || ($user->getIs_admin() == 1 && $this->currentUser['is_runner']))
          ||(isset($worklist['runner_id']) && $user_id == $worklist['runner_id']))
        );
    }

    public function showWithdrawOrDeclineButtons() {
        $worklist = $this->worklist;
        return (int) (
             $worklist['status'] != 'Done' 
          && $worklist['status'] != 'Working' 
          && $worklist['status'] != 'Functional' 
          && $worklist['status'] != 'Review' 
          && $worklist['status'] != 'Completed'
        );
    }

    public function showReviewUrlPopup() {
        $workitem = $this->workitem;
        $worklist = $this->worklist;
        $user = $this->user;
        return (int) (
            ($workitem->getIsRelRunner() || ($user->getIs_admin() == 1 && $this->currentUser['is_runner']) || ($worklist['mechanic_id'] == $this->currentUser['id'])) &&
            (strcasecmp($worklist['status'], 'Done') != 0 && strcasecmp($worklist['status'], 'Completed') != 0)
        );
    }

    public function canReassignRunner() {
        $workitem = $this->workitem;
        $user = $this->user;
        return (int) ($this->action == "edit" && ($workitem->getIsRelRunner() || ($user->getIs_admin() == 1 && $this->currentUser['is_runner'])));
    }

    public function crFee() {
        return number_format($this->read('crFee'), 2);
    }

    public function statusSuggested() {
        return $this->worklist['status'] == "Suggested";
    }

    public function statusSuggestedAny() {
        return $this->worklist['status'] == "Suggested" || $this->worklist['status'] == "SuggestedWithBid";
    }

    public function canFeeOthers() {
        return ($this->currentUser['is_runner'] || $this->is_project_founder || $this->is_project_runner);
    }

    public function userFeeSelectBox() {
        $filter = new Agency_Worklist_Filter();
        return $filter->getUserSelectbox(1, false, 'mechanicFee', 'mechanicFee');
    }

    public function userTipSelectBox() {
        $filter = new Agency_Worklist_Filter();
        echo $filter->getUserSelectbox(1, false, 'mechanicTip', 'mechanicTip');
    }

    public function showBidderStatistics() {
        return ($this->user->getIs_admin() == 1 && $this->currentUser['is_runner'] || $this->is_project_founder || $this->is_project_runner);
    }

    public function showBudgetSelect() {
        return ($this->currentUser['is_runner'] || $this->is_project_founder || $this->workitem->getIsRelRunner());
    }

    public function maxTip() {
        $max_tip = 0;
        foreach ($this->read('fees') as $fee) {
            if ($fee['desc'] == 'Accepted Bid') {
                $max_tip = $fee['amount'];
                break;
            }
        }
        return $maxTip;
    }

    public function mechanicNickname() {
        $row = $this->workitem->getUserDetails($this->worklist['mechanic_id']);
        return empty($row) ? '' : $nickname = $row['nickname'];
    }

    public function addBidMsg() {
        return $this->read('isGitHubConnected') ? 'Add my bid' : 'Authorize GitHub app';
    }

    public function taskEntries() {
        // we don't need comments from status entries cause 
        // we are mixing them with real comments
        $worklist_entries = self::removeCommentsEntries($this->read('entries'));

        // let's group reply comments so they get rendered toghether, no matter their
        // date according to the rest of the entries of other groups (only the first 
        // level comment date is taken for orfering)
        $comments = self::groupComments($this->read('comments'));

        $entries = array_merge($worklist_entries, $comments);
        usort($entries, array('JobView', 'sortEntries'));
        $ret = '';
        $now = 0;
        foreach($entries as $entry) {
            if (!$now) {
                $now = strtotime(Model::now());
            }
            if (get_class($entry) == 'EntryModel') {
                $id = $entry->id;
                $type = 'worklist';
                $date = strtotime($entry->date);
                $content = self::formatEntry($entry);

                $ret .= 
                      '<li entryid="' . $id . '" date="' . $date  . '" type="' . $type .  '">'
                    .     '<h4>' . relativeTime($date - $now) . '</h4>'
                    .     $content
                    . '</li>';
            } else {
                foreach($entry['content'] as $comment) {
                    $commentObj = $comment['comment'];
                    $ret .=
                        '<li id="comment-' . $comment['id'] . '" class="depth-' . $comment['depth'] . '">' .
                        '    <div class="comment">' .
                        '        <a href="./user/' . $commentObj->getUser()->getId() . '">' .
                        '            <img class="picture profile-link" src="' . $commentObj->getUser()->getAvatar() . '" title="Profile Picture - ' . $commentObj->getUser()->getNickname() . '" />' .
                        '        </a>' .
                        '        <div class="comment-container">' .
                        '            <div class="comment-info">' .
                        '                <a class="author profile-link" href="./user/' . $commentObj->getUser()->getId() . '">' .
                        '                    ' . $commentObj->getUser()->getNickname() .
                        '                </a>' .
                        '                <span class="date">' . $commentObj->getRelativeDate() . '</span>' .
                        '            </div>' .
                        '            <div class="comment-text">' .
                        '              ' . $commentObj->getCommentWithLinks() .
                        (
                            ($this->currentUser['id'] && !$this->statusDone())
                                ? '<div class="reply-lnk"><a href="#commentform" onClick="return reply(' . $comment['id'] . ');">Reply</a></div>' 
                                : ''
                        ) . 
                        '            </div>' .
                        '        </div>' .
                        '    </div>' .
                        '</li>';
                }
            }
        }
        return $ret;
    }

    static function sortEntries($a, $b) {
        if (get_class($a) == 'EntryModel') {
            $date_a = strtotime($a->date);
        } else { // comment group
            $date_a = strtotime($a['date']);
        }
        if (get_class($b) == 'EntryModel') {
            $date_b = strtotime($b->date);
        } else { // comment group
            $date_b = strtotime($b['date']);
        }
        return ($date_a == $date_b) ? 0 : ($date_a > $date_b ? +1 : -1);
    }

    static function groupComments($commentsList) {
        $ret = array();
        $currentGroup = array();
        foreach($commentsList as $comment) {
            if ($comment['depth'] == 0) {
                if (!empty($currentGroup)) {
                    $ret[] = $currentGroup;
                }
                $currentGroup = array(
                    'date' => $comment['comment']->getDate(), 
                    'content' => array()
                );
            }
            $currentGroup['content'][] = $comment;
        }
        $ret[] = $currentGroup;
        return $ret;
    }

    /**
     * Gets rid of comments entries from a given list
     */
    static function removeCommentsEntries($entries) {
        $pattern = '/^@.+posted\sa\scomment\son\s+#\d+.*/';
        $ret = array();
        foreach($entries as $entry) {
            if (preg_match($pattern, $entry->entry)) {
                continue;
            }
            $ret[] = $entry;
        }
        return $ret;
    }

    /**
     * same than StatusView::formatWorklistEntry
     * @todo: integrate with StatusView::formatWorklistEntry
     */
    static function formatEntry($entry) {
        $ret = $entry->entry;

        // will only process new entries since #19490 deployment
        // @todo, remove this condition once history is removed/older
        if (strtotime($entry->date) > strtotime('2014-03-06 00:00:00')) {
            // linkify mentions and tasks references
            $ret = $ret;
            $mention_regex = '/(^|\s)@(\w+)/';
            $task_regex = '/(^|\s)\*\*#(\d+)\*\*/';
            $ret = preg_replace($mention_regex, '\1[\2](./user/\2)', $entry->entry);
            $ret = preg_replace($task_regex, '\1[\\\\#\2](./\2)', $ret);
            // proccesed entries are returned as markdown-processed html
            $ret = Markdown::defaultTransform($ret);
        }

        return $ret;
    }

    function currentStatus() {
        return $this->worklist['status'] == 'Review' ? 'Code Review' : $this->worklist['status'];
    }
}
