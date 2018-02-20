<?php

use \Michelf\Markdown;

class JobView extends View {
    public $title = '%d: %s - Worklist';
    public $og_description = 'Worklist job: %s';

    public $stylesheets = array(
        'css/legacy/workitem.css',
        'css/entries.css',
        'css/job.css'
   );

    public $scripts = array(
        'js/jquery/jquery.template.js',
        'js/jquery/jquery.jeditable.min.js',
        'js/jquery/jquery.tallest.js',
        'js/jquery/jquery.metadata.js',
        'js/jquery/jquery.blockUI.js',
        'js/dragdealer/dragdealer.js',
        'js/filedrop/filedrop-min.js',
        'js/spin.js/spin.min.js',
        'js/datepicker.js',
        'js/timepicker.js',
        'js/entries.js',
        'js/github.js',
        'js/job.js'
    );

    public function render() {
        $worklist = $this->worklist = $this->read('worklist');
        $this->workitem = $this->read('workitem');
        $this->user = $this->read('user');
        $this->workitem_project = $this->read('workitem_project');
        $this->title = sprintf($this->title, $worklist['id'], $worklist['summary']);
        $this->og_description = sprintf($this->og_description, $worklist['summary']);

        $this->bids = $this->read('bids');
        $this->fees = $this->read('fees');

        $this->order_by = $this->read('order_by');
        $this->action = $this->read('action');
        $this->action_error = $this->read('action_error');
        $this->classEditable = $this->read('classEditable');
        $this->allowEdit = (int) $this->read('allowEdit');
        $this->userHasCodeReviewRights = (int) $this->read('userHasCodeReviewRights');
        $this->isGitHubConnected = (int) $this->read('isGitHubConnected');
        $this->message = $this->read('message');
        $this->currentUserHasBid = (int) $this->read('currentUserHasBid');
        $this->has_budget = (int) $this->read('has_budget');
        $this->is_project_runner = (int) $this->read('is_project_runner');
        $this->is_project_founder = (int) $this->read('is_project_founder');
        $this->promptForReviewUrl =  (int) $this->read('promptForReviewUrl');
        $this->status_error =  (int) $this->read('status_error');
        $this->userinfotoshow =  (int) $this->read('userinfotoshow');

        $this->erroneous = $this->read('erroneous');
        $this->the_errors = $this->read('the_errors');

        $this->reviewer = $this->read('reviewer');

        $this->views = $this->read('views');
        $this->viewCount = $this->read('viewCount');
        return parent::render();
    }

    public function runSandboxCheck() {
        return $this->worklist['status'] == 'QA Ready' && $this->currentUser['is_runner'];
    }

    public function canUpload() {
    	return $this->worklist['status'] != 'Done' && (int) $this->currentUser['id'] > 0;
    }

    public function internalChecked() {
        return $this->workitem->isInternal() ? 'checked="checked"' : '';
    }

    public function userIsInternal() {
        return $this->user->isInternal() ? true : false;
    }

    public function editing() {
        return $this->action == 'edit';
    }

    public function canEditAndNotEditing() {
        return $this->allowEdit;
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
        $selectedCurrentStatus = false;
        $statusList = array();
        $ret = '';
        if (!($workitem->getIsRelRunner() || ($user->getIs_admin() == 1 && $is_runner))
          && $worklist['mechanic_id'] > 0 &&  ($worklist['mechanic_id'] == $this->currentUser['id'])
          && $worklist['status'] != 'Done') {
            //mechanics
            $statusList = $statusListMechanic;
        } else if ($workitem->getIsRelRunner() || ($user->getIs_admin() == 1 && $is_runner)) {
            //runners and admins
            $statusList = $statusListRunner;

        } else if ($worklist['creator_id'] == $this->currentUser['id']
          && $worklist['status'] != 'In Progress'
          && $worklist['status'] != 'QA Ready' && $worklist['status'] != 'Review'
          && $worklist['status'] != 'Merged' && $worklist['status'] != 'Done'
          && $worklist['creator_id'] > 0
        ) {
            //creator
            $statusList = $statusListCreator;
        }

        foreach ($statusList as $status) {
            $printStatus = $status == 'Review' ? 'Code Review' : $status;
            $actualStatus = $status == 'Code Review' ? 'Review' : $status;
            if ($actualStatus != $worklist['status'] ) {
                $ret .= '<li data-status="'. $status .'"><a>' . $printStatus  . '</a></li>';
            } else if ($actualStatus == $worklist['status']) {
                $selectedCurrentStatus = true;
                $ret .= '<li class="job-status-selected" data-status="'. $status .'"><a>' . $printStatus . '</a></li>';
            }
        }
        if (!$selectedCurrentStatus) {
            $printStatus = $worklist['status'] == 'Review' ? 'Code Review' : $worklist['status'];
            $ret .= '<li class="job-status-selected" data-status="'. $worklist['status'] .'"><a>' . $printStatus  . '</a></li>';
        }

        return $ret;
    }

    public function statusDone() {
        return $this->worklist['status'] == "Done";
    }

    public function isGitProject() {
        return $this->workitem_project->getRepo_type() == 'git';
    }

    public function nonEditableRunnerBox() {
        $worklist = $this->worklist;
        $ret = '';
        if ($worklist['runner_nickname'] != 'Not funded' && $worklist['runner_nickname'] != '') {
            $ret .=
                '<span class="job-info-heading">' .
                'Designer:</span>' .
                '<a href="./user/' . $worklist['runner_id'] . '" >' . $worklist['runner_nickname'] . '</a>';
        } else {
            $ret .= '<span class="job-info-heading">Designer:</span> Not funded';
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
            $mech = '<span class="job-info-heading">Developer:</span><span id="job-info-not-assigned">Not assigned</span>';
        } else {
            $tooltip = isset($_SESSION['userid']) ? "Ping Developer" : "Log in to Ping Developer";
            $mech =
                '<span  class="job-info-heading" title="' . $tooltip . '" >' .
                  'Developer:' .
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
              && ($worklist['status']=='Suggestion') && is_null($worklist['runner_id'])
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
        } else if ($worklist['creator_id'] == $this->currentUser['id'] && $mechanic_id == $this->currentUser['user_id']) {
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
        return str_replace(array('\n\r','\r\n','\n','\r'), '<br/>', $worklist['notes']);
    }

    public function notesHtmlWithLinks() {
        $worklist = $this->worklist;
        return str_replace(array('\n\r','\r\n','\n','\r'), '<br/>', Utils::linkify($worklist['notes']));
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
             (strcasecmp($worklist['status'], 'In Progress') == 0 || strcasecmp($worklist['status'], 'Review') == 0 || strcasecmp($worklist['status'], 'QA Ready') == 0)
          && ($workitem->getIsRelRunner() || ($user->getIs_admin() == 1 && $is_runner) ||($worklist['mechanic_id'] == $this->currentUser['id']))
        );
    }

    public function canViewDiff() {
        $workitem = $this->workitem;
        $worklist = $this->worklist;
        $user = $this->user;
        $is_project_founder = $this->read('is_project_founder');
        return (
             ($worklist['status'] == 'QA Ready' || $worklist['status'] == 'Review')
          && $worklist['sandbox'] != 'N/A'
          || ($worklist['status'] == 'In Progress' && ($user->isRunnerOfWorkitem($workitem) || $is_project_founder || $user->getId() == $worklist['mechanic_id']))
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

    public function labelsCount() {
        return count($this->workitem->getLabels());
    }

    public function commaSeparatedLabels() {
        return implode(', ', $this->workitem->getLabels());
    }

    public function canReview() {
        $workitem = $this->workitem;
        $worklist = $this->worklist;
        $user = $this->user;
        $is_runner = $this->currentUser['is_runner'];
        return (
             (
                   $this->currentUser['id'] > 0
                && $user->isEligible()
                && $worklist['mechanic_id'] != $this->currentUser['id']
                && $this->read('userHasCodeReviewRights')
              )
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
             $workitem->getCRStarted()
          && (
                 $this->currentUser['id'] == $workitem->getCReviewerId()
              || $workitem->getIsRelRunner()
              || ($user->getIs_admin() == 1 && $is_runner)
            )
          && !$workitem->getCRCompleted();
    }

    public function codeReviewStarted() {
        $workitem = $this->read('workitem');
        return $workitem->getCRStarted();
    }

    public function codeReviewFeeAmount() {
        return number_format($this->read('crFee'), 2);
    }

    public function canBid() {
        $worklist = $this->worklist;
        return $worklist['status'] == 'Bidding'
          || ($worklist['status'] == 'Suggestion' && $worklist['creator_id'] == $this->currentUser['id']);
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
          && ((($workitem->getStatus()) == "Bidding"))
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
        $now = 0;
        foreach($bids as $bid) {
            $biddings = array();

            if (!$now) {
                $now = strtotime(Model::now());
            }

            $created = Utils::relativeTime(strtotime($bid['bid_created']) - $now, true, true, true, false);
            $accepted = $bid['bid_accepted'] ? Utils::relativeTime(strtotime($bid['bid_accepted']) - $now, true, true, true, false) : '';
            $expires = $bid['expires'] ? Utils::relativeTime($bid['expires'], true, true, true, false) : 'Never';

            if ($user->getId() != $bid['bidder_id'] && $bid['expires'] < 0) {
                continue;
            }

            if ($user->getId() == $bid['bidder_id'] && $bid['expires'] < 0) {
                $expired_class = ' expired_warn';
            } else {
                $expired_class = '';
            }
            $canSeeBid = ($workitem->getRunnerId($workitem) == 0 && $is_project_runner)
                         || $user->getIs_admin() == 1
                         || $user->isRunnerOfWorkitem($workitem)
                         || $user->getId() == $bid['bidder_id'];
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
                        "bid_accepted: '" . $accepted . "', " .
                        "bid_created: '" . $created . "', " .
                        "bid_expires: '" . $expires . "', " .
                        "time_to_complete: '{$bid['time_to_complete']}', " .
                        "done_in: '{$bid['done_in']}', " .
                        "bidder_id: {$bid['bidder_id']}, " .
                        "notes: '" .  str_replace(array('\n\r','\r\n','\n','\r'), '<br/>', $notes) . "'}" .
                    "</script>";
            }
            $ret .=
                 '<td>'
                . (
                    $canSeeBid
                      ? '<a href="./user/' . $bid['bidder_id'] . '" bidderId="' . $bid['bidder_id'] . '">' . $bid['nickname'] . '</a>'
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
                            $fee['nickname'] .
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
                            ($fee['desc'] == 'Accepted Bid' && ($worklist['status'] == 'Review' || $worklist['status'] == 'Merged' || $worklist['status'] == 'Done'))
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
          || (isset($worklist['assigned_id']) && $this->currentUser['id'] == $worklist['assigned_id'])
        );
    }

    public function hasAcceptedBids() {
        return (int) $this->workitem->hasAcceptedBids();
    }

    public function showPingBidderButton() {
        $worklist = $this->worklist;
        $is_project_runner = $this->read('is_project_runner');
        $user = $this->user;

        return (int) (
            ($worklist['status'] == 'Bidding' && ($is_project_runner || ($user->getIs_admin() == 1 && $this->currentUser['is_runner']))
          ||(isset($worklist['runner_id']) && $this->currentUser['id'] == $worklist['runner_id']))
        );
    }

    public function showWithdrawOrDeclineButtons() {
        $worklist = $this->worklist;
        return (int) (
             $worklist['status'] != 'Done'
          && $worklist['status'] != 'In Progress'
          && $worklist['status'] != 'QA Ready'
          && $worklist['status'] != 'Review'
          && $worklist['status'] != 'Merged'
        );
    }

    public function showReviewUrlPopup() {
        $workitem = $this->workitem;
        $worklist = $this->worklist;
        $user = $this->user;
        return (int) (
            ($workitem->getIsRelRunner() || ($user->getIs_admin() == 1 && $this->currentUser['is_runner']) || ($worklist['mechanic_id'] == $this->currentUser['id'])) &&
            (strcasecmp($worklist['status'], 'Done') != 0 && strcasecmp($worklist['status'], 'Merged') != 0)
        );
    }

    public function canReassignRunner() {
        $workitem = $this->workitem;
        $user = $this->user;
        return (int) ($this->action == "edit" && ($workitem->getIsRelRunner() || ($user->getIs_admin() == 1 && $this->currentUser['is_runner'])));
    }

    public function statusSuggestion() {
        return $this->worklist['status'] == "Suggestion";
    }

    public function canFeeOthers() {
        return ($this->currentUser['is_runner'] || $this->is_project_founder || $this->is_project_runner);
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
        $row_class .= ($this->read('place_bid_id') == $$itemId) ? ' place_bid_id ' : '' ;
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
            if (is_object($entry) && get_class($entry) == 'EntryModel') {
                $id = $entry->id;
                $type = 'worklist';
                $date = strtotime($entry->date);
                $content = self::formatEntry($entry);

                $ret .=
                      '<li entryid="' . $id . '" date="' . $date  . '" type="' . $type .  '">'
                    .     '<h4>' . Utils::relativeTime($date - $now) . '</h4>'
                    .     $content
                    . '</li>';
            } else {
                foreach($entry['content'] as $comment) {
                    $commentObj = $comment['comment'];
                    $ret .=
                        '<li id="comment-' . $comment['id'] . '" class="depth-' . $comment['depth'] . '">' .
                        '    <div class="comment">' .
                        '        <a class="commenter-avatar" href="./user/' . $commentObj->getUser()->getId() . '">' .
                        '            <img class="picture profile-link" src="' . $commentObj->getUser()->getAvatar() . '" title="Profile Picture - ' . $commentObj->getUser()->getNickname() . '" />' .
                        '        </a>' .
                        '        <div class="comment-container">' .
                        '            <div class="comment-info">' .
                        '                <a class="author profile-link" href="./user/' . $commentObj->getUser()->getId() . '">' .
                        '                    ' . $commentObj->getUser()->getNickname() .
                        '                </a>' .
                        '                <a class="date" href="./' . $this->worklist['id'] . '#comment-' . $comment['id'] . '">' .
                        '                ' . $commentObj->getRelativeDate() .
                        '                </a>' .
                        '            </div>' .
                        '            <div class="comment-text">' .
                        '              ' . $commentObj->getCommentWithLinks() .
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
        if (is_object($a) && get_class($a) == 'EntryModel') {
            $date_a = strtotime($a->date);
        } else { // comment group
            $date_a = strtotime($a['date']);
        }
        if (is_object($b) && get_class($b) == 'EntryModel') {
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
            $mention_regex = '/(^|\s)@([a-zA-Z0-9][a-zA-Z0-9-]+)/';
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

    function internalUsers() {
        $worklist = $this->worklist;
        $users = User::getInternals();
        $ret = array();
        foreach($users as $index => $user) {
            $ret[] = array(
                'id' => $user->getId(),
                'nickname' => $user->getNickname(),
                'current' => $worklist['assigned_id'] == $user->getId()
            );
        }
        return $ret;
    }

    function assigneeNickname() {
        $worklist = $this->worklist;
        $assignedUser = User::find($worklist['assigned_id']);
        return $assignedUser->getNickname();
    }

    public function labels() {
        $labels = $this->workitem->getlabels();
        return $labels;
    }

    public function canEdit() {
        $workitem = $this->workitem;
        $worklist = $this->worklist;
        $user = $this->user;
        $is_runner = $this->currentUser['is_runner'];

       return (
             (($workitem->getIsRelRunner() || ($user->getIs_admin() == 1 && $is_runner)) && $worklist['status']!='Done')
          || (
                 $worklist['creator_id'] == $this->currentUser['id']
              && ($worklist['status']=='Suggestion') && is_null($worklist['runner_id'])
            )
        );
    }
}
