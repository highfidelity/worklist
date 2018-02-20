<?php

require_once ("models/DataObject.php");
require_once ("models/Review.php");
require_once ("models/Users_Favorite.php");
require_once ("models/Budget.php");

class UserController extends Controller {
    /**
     * Non existing method call will fall to run this method, so here
     * we guess that the requestor is trying to get a user profile.
     * Let's take it's arguments and route to the right process path.
     */
    public function __call($id, $arguments) {
        $arguments = array_merge(array($id), $arguments);
        call_user_func_array(array($this, 'info'), $arguments);
    }

    public function exists($id) {
        $this->view = null;
        $user = User::find($id);
        try {
            $ret = array(
                'success' => true,
                'exists' => ($user->getId() > 0)
            );
        } catch(Exception $e) {
            $ret = array('success' => false);
        }
        echo json_encode($ret);
    }

    public function index() {
        $this->view = null;
        $users = User::getUserList(Session::uid(), true);
        $ret = array();
        foreach ($users as $user) {
            $ret[] = array(
                'id' => $user->getId(),
                'nickname' => $user->getNickname(),
                'current' => ($user->getId() == Session::uid())
            );
        }
        echo json_encode(array('users' => $ret));
        return;
    }

    public function budget($id) {
        $this->view = null;
        $user = User::find($id);
        if (!$user) {
            echo json_encode(array('success' => false));
            return;
        }
        $ret = array(
            'active' => $user->getActiveBudgets()
        );
        if ($user->getId() == Session::uid()) {
            $ret = array_merge($ret, array(
                'feeSums' => Fee::getSums(),
                'totalManaged' => money_format('%i', $user->getTotalManaged()),
                'remainingFunds' => money_format('%i', $user->setRemainingFunds()),
                'allocated' => money_format('%i', $user->getAllocated()),
                'submitted' => money_format('%i', $user->getSubmitted()),
                'paid' => money_format('%i', $user->getPaid()),
                'transfered' => money_format('%i', $user->getTransfered()),
                'transfersDetails' => $user->getBudgetTransfersDetails(),
                'available' => $user->getBudget()
            ));
        }
        echo json_encode(array(
            'success' => true,
            'budget' => $ret
        ));
        return;
    }

    public function info($id) {
        $action = isset($_REQUEST['action']) ? $_REQUEST['action'] : false;
        $this->write('tab', isset($_REQUEST['tab']) ? $_REQUEST['tab'] : "");

        $reqUserId = Session::uid();
        $this->write('reqUserId', $reqUserId);
        $reqUser = new User();
        if ($reqUserId > 0) {
            $reqUser->findUserById($reqUserId);
            $budget = $reqUser->getBudget();
        }
        $this->write('reqUser', $reqUser);
        $is_runner = isset($_SESSION['is_runner']) ? $_SESSION['is_runner'] : 0;
        $is_payer = isset($_SESSION['is_payer']) ? $_SESSION['is_payer'] : 0;

        // admin posting data
        if (!empty($_POST) && ($is_runner || $is_payer) && !$action) {

            $user_id = (int) $_POST['user_id'];

            if (!empty($_POST['save-salary'])) {
                $field = 'salary';
                $value = mysql_real_escape_string($_POST['value']);
            } else {
                $field = $_POST['field'];
                $value = (int) $_POST['value'];
            }

            $updateUser = new User();

            if ($updateUser->findUserById($user_id)) {
                switch ($field) {
                    case 'salary':
                        $updateUser->setAnnual_salary($value);
                        Utils::systemNotification("A new salary has been set for @" . $updateUser->getNickname());
                        break;

                    case 'ispayer':
                        $updateUser->setIs_payer($value);
                        break;

                    case 'isrunner':
                        $updateUser->setIs_runner($value);
                        break;

                    case 'isinternal':
                        $updateUser->setIs_internal($value);
                        break;

                    case 'ispaypalverified':
                        $updateUser->setPaypal_verified($value);
                        if ($value) {
                            $updateUser->setHas_w2(false);
                        }
                        break;

                    case 'isw2employee':
                        $updateUser->setHas_w2($value);
                        if ($value) {
                            $updateUser->setPaypal_verified(false);
                            $updateUser->setw9_status('not-applicable');
                        }
                        break;

                    case 'manager':
                        $updateUser->setManager($value);
                        if ($value) {
                            $manager = new User();
                            $manager->findUserById($value);
                            // Send journal notification
                            Utils::systemNotification("The manager for @" . $updateUser->getNickname() . " is now set to @" . $manager->getNickname());
                        } else {
                            Utils::systemNotification("The manager for @" . $updateUser->getNickname() . " has been removed");
                        }
                        break;

                    case 'referrer':
                        $updateUser->setReferred_by($value);
                        if ($value) {
                            $referrer = new User();
                            $referrer->findUserById($value);

                            // Send journal notification
                            Utils::systemNotification("The referrer for @" . $updateUser->getNickname() . " is now set to @" . $referrer->getNickname());
                        } else {
                            Utils::systemNotification("The referrer for @" . $updateUser->getNickname() . " has been removed");
                        }
                        break;

                    case 'isactive':
                        $updateUser->setIs_active($value);
                        break;

                    default:
                        break;
                }

                $updateUser->save();
                $response = array(
                    'succeeded' => true,
                    'message' => 'User details updated successfully'
                );
                echo json_encode($response);
                exit(0);

            } else {
                die(json_encode(array(
                    'succeeded' => false,
                    'message' => 'Error: Could not determine the user_id'
                )));
            }
        }

        $user = new User();
        $user = User::find($id ? $id : Session::uid());
        $userId = $user->getId();

        /**
         * If we couldn't find a valid User, return an ErrorView
         */
        if (! $user->getId()) {
            $this->write('msg', 'That user doesn\'t exist.');
            $this->write('link', WORKLIST_URL);
            $this->view = new ErrorView();
            parent::run();
        }

        $this->write('userId', $userId);
        $this->write('user', $user);

        $this->write('Annual_Salary', $user->getAnnual_salary() > 0 ? $user->getAnnual_salary() : '');
        $this->write('manager', $user->getManager());
        $this->write('referred_by', $user->getReferred_by());

        if ($action =='create-sandbox') {
            $result = array();
            try {
                if (!$is_runner) {
                    throw new Exception("Access Denied");
                }
                $args = array('unixusername', 'projects');
                foreach ($args as $arg) {
                    $$arg = mysql_real_escape_string($_REQUEST[$arg]);
                }

                $projectList = explode(",",str_replace(" ","",$projects));

                // Create sandbox for user
                $sandboxUtil = new SandBoxUtil;
                $sandboxUtil->createSandbox($user -> getUsername(),
                                            $user -> getNickname(),
                                            $unixusername,
                                            $projectList);

                // If sb creation was successful, update users table
                $user->setHas_sandbox(1);
                $user->setUnixusername($unixusername);
                $user->setProjects_checkedout($projects);
                $user->save();
                // add to project_users table
                foreach ($projectList as $project) {
                    $project_id = Project::getIdFromRepo($project);
                    $user->checkoutProject($project_id);
                }
            } catch(Exception $e) {
                $result["error"] = $e->getMessage();
            }
            echo json_encode($result);
            die();
        }

        $reviewee_id = (int) $userId;
        $review = new Review();
        $this->write('reviewsList', $review->getReviews($reviewee_id,$reqUserId));
        $this->write('projects', $this->getProjectList());
        $user_projects = $user->getProjects_checkedout();
        $this->write('has_sandbox', count($user_projects) > 0);
        $users_favorite = new Users_Favorite();
        $favorite_enabled = 1;
        $favorite = $users_favorite->getMyFavoriteForUser($reqUserId, $userId);
        if (isset($favorite['favorite'])) {
            $favorite_enabled = $favorite['favorite'];
        }
        $favorite_count = $users_favorite->getUserFavoriteCount($userId);
        $this->write('favorite_count', $favorite_count);
        $this->write('favorite_enabled', $favorite_enabled);
        parent::run();
    }

    public function countries($cond) {
        $this->view = null;
        global $countrylist;
        $ret = array();
        foreach($countrylist as $code => $country) {
            $ret[] = array(
                'code' => $code,
                'name' => $country
            );
        }
        echo json_encode($ret);
    }

    public function avatar($id, $size) {
        $this->view = null;
        $user = User::find($id);
        $size = preg_split('/x/', $size, 2);
        $width = count($size) == 2 ? $size[0] : $size[0];
        $height = count($size) == 2 ? $size[1] : $size[0];
        $thumb = imagecreatetruecolor($width, $height);
        $filename = $user->getAvatar();
        list($orig_width, $orig_height) = getimagesize($filename);
        $source = imagecreatefromstring(file_get_contents($filename));
        imagecopyresized($thumb, $source, 0, 0, 0, 0, $width, $height, $orig_width, $orig_height);
        header('Content-type: image/jpeg');
        imagejpeg($thumb);
    }

    public function following($id, $page = 1, $itemsPerPage = 10) {
        $this->view = null;
        $user = User::find($id);
        $page = (is_numeric($page) ? $page : 1);
        $itemsPerPage = (is_numeric($itemsPerPage) ? $itemsPerPage : 10);
        echo json_encode($user->followingJobs($page, $itemsPerPage));
    }

    public function designerJobs($id, $type = 'total', $page = 1, $itemsPerPage = 10) {
        $this->view = null;
        $user = User::find($id);
        $page = (is_numeric($page) ? $page : 1);
        $itemsPerPage = (is_numeric($itemsPerPage) ? $itemsPerPage : 10);
        if ($type == 'active') {
            $status = array('In Progress', 'Review', 'QA Ready');
        } else {
            $status = array('In Progress', 'QA Ready', 'Review', 'Merged', 'Done');
        }
        echo json_encode($user->jobsAsDesigner($status, $page, $itemsPerPage));
    }

    public function activeJobs($id, $page = 1, $itemsPerPage = 10) {
        $this->view = null;
        $user = User::find($id);
        $status = array('In Progress', 'QA Ready', 'Review');
        $page = (is_numeric($page) ? $page : 1);
        $itemsPerPage = (is_numeric($itemsPerPage) ? $itemsPerPage : 10);
        echo json_encode($user->jobs($status, $page, $itemsPerPage));
    }

    public function workingJobs($id, $page = 1, $itemsPerPage = 10) {
        $this->view = null;
        $user = User::find($id);
        $page = (is_numeric($page) ? $page : 1);
        $itemsPerPage = (is_numeric($itemsPerPage) ? $itemsPerPage : 10);
        echo json_encode($user->jobs('In Progress', $page, $itemsPerPage));
    }

    public function reviewJobs($id, $page = 1, $itemsPerPage = 10) {
        $this->view = null;
        $user = User::find($id);
        $page = (is_numeric($page) ? $page : 1);
        $itemsPerPage = (is_numeric($itemsPerPage) ? $itemsPerPage : 10);
        echo json_encode($user->jobs('Review', $page, $itemsPerPage));
    }

    public function completedJobs($id, $page = 1, $itemsPerPage = 10) {
        $this->view = null;
        $user = User::find($id);
        $page = (is_numeric($page) ? $page : 1);
        $itemsPerPage = (is_numeric($itemsPerPage) ? $itemsPerPage : 10);
        echo json_encode($user->jobs('Merged', $page));
    }

    public function doneJobs($id, $page = 1, $itemsPerPage = 10) {
        $this->view = null;
        $user = User::find($id);
        $page = (is_numeric($page) ? $page : 1);
        $itemsPerPage = (is_numeric($itemsPerPage) ? $itemsPerPage : 10);
        echo json_encode($user->jobs('Done', $page, $itemsPerPage));
    }

    public function totalJobs($id, $page = 1, $itemsPerPage = 10) {
        $this->view = null;
        $user = User::find($id);
        $page = (is_numeric($page) ? $page : 1);
        $itemsPerPage = (is_numeric($itemsPerPage) ? $itemsPerPage : 10);
        echo json_encode($user->jobs(array('In Progress', 'QA Ready', 'Review', 'Merged', 'Done'), $page, $itemsPerPage));
    }

    public function latestEarnings($id) {
        $this->view = null;
        $user = User::find($id);
        echo json_encode($user->latestEarningsJobs(30));
    }

    public function projectHistory($id, $project, $page = 1, $itemsPerPage = 10) {
        $this->view = null;
        $user = User::find($id);
        $project = Project::find($project);
        $page = (is_numeric($page) ? $page : 1);
        $itemsPerPage = (is_numeric($itemsPerPage) ? $itemsPerPage : 10);
        echo json_encode($user->jobsForProject('Done', $project->getProjectId(), $page, $itemsPerPage));
    }

    public function counts($id) {
        $this->view = null;
        $user = User::find($id);
        setlocale(LC_MONETARY,'en_US');
        $totalEarnings = $user->totalEarnings();
        $bonusPayments = $user->bonusPaymentsTotal();
        $latestEarnings = $user->latestEarnings(30);
        echo json_encode(array(
            'total_jobs' => $user->jobsCount(array('In Progress', 'QA Ready', 'Review', 'Merged', 'Done')),
            'active_jobs' => $user->jobsCount(array('In Progress', 'QA Ready', 'Review')),
            'total_earnings' => preg_replace('/\.[0-9]{2,}$/','',money_format('%n',round($totalEarnings))),
            'latest_earnings' => preg_replace('/\.[0-9]{2,}$/','',money_format('%n',$latestEarnings)),
            'bonus_total' => preg_replace('/\.[0-9]{2,}$/','',money_format('%n',round($bonusPayments))),
            'bonus_percent' => round((($bonusPayments + 0.00000001) / ($totalEarnings + 0.000001)) * 100,2) . '%'
        ));
    }

    public function suggestMentions($startsWith = '_', $maxLimit = 10) {
        $this->view = null;
        $user = User::find(Session::uid());
        echo json_encode(User::suggestMentions($user, $startsWith, $maxLimit));
    }

    public function review($id) {
        $this->view = null;
        try {
            Utils::checkLogin();
            $user = User::find($id);
            $currentUser = User::find(Session::uid());
            if (!$user->getId() || $user->getId() == $currentUser->getId()) {
                throw new Exception('Invalid user id');
            }
            $review = new Review();
            if ($_SERVER['REQUEST_METHOD'] == 'POST') {
                $userReview = trim($_POST['userReview']);
                if ($review->loadById($currentUser->getId(), $user->getId())) {
                    if (!$userReview) {
                        $oReview = $review->getReviews($user->getId(), $currentUser->getId(), ' AND r.reviewer_id=' . $reviewer_id);
                        $cond = 'reviewer_id = ' . $currentUser->getId() . ' AND reviewee_id = ' . $user->getId();
                        if (!$review->removeRow($cond)) {
                            throw new Exception('Cannot delete review! Please retry later');
                        }
                        Utils::sendReviewNotification($user->getId(), "delete", $oReview);
                        $message = 'Review deleted';
                    } else {
                        if (!strcmp($review->review, $userReview)) {
                            throw new Exception('No changes made');
                        }
                        $review->review = $userReview;
                        $review->journal_notified = 0;
                        if (!$review->save('reviewer_id', 'reviewee_id')) {
                            throw new Exception('Cannot update review! Please retry later');
                        }
                        $message = 'Review updated';
                    }
                } else {
                    if (!$userReview) {
                        throw new Exception('New empty review is not saved');
                    }
                    if (!$review->insertNew(array(
                        'reviewer_id' => $currentUser->getId(),
                        'reviewee_id' => $user->getId(),
                        'review' => $userReview,
                        'journal_notified' => -1
                    ))) {
                        throw new Exception('Cannot create new review! Please retry later');
                    }
                    $myReview = $review->getReviews($user->getId(), $currentUser->getId(), ' AND r.reviewer_id = ' . $currentUser->getId());
                    if (count($myReview) == 0) {
                        $cond = 'reviewer_id = ' . $currentUser->getId() . ' AND reviewee_id = ' . $user->getId();
                        $review->removeRow($cond);
                        throw new Exception('Review with no paid fee is not allowed');
                    }
                    $message = 'Review saved';
                }
            } else {
                $userReview = $message = '';
                if ($review->loadById($currentUser->getId(), $user->getId())) {
                    $userReview = $review->review;
                }
            }
            echo json_encode(array(
                'success' => true,
                'message' => $message,
                'myReview' => $userReview
            ));
        } catch (Exception $e) {
            echo json_encode(array(
                'success' => false,
                'message' => $e->getMessage()
            ));
        }
    }

    public function payBonus($id) {
        $this->view = null;
        try {
            $is_runner = isset($_SESSION['is_runner']) ? $_SESSION['is_runner'] : 0;
            $is_payer = isset($_SESSION['is_payer']) ? $_SESSION['is_payer'] : 0;
            // user must be logged in
            if (!Session::uid() || (!$is_runner && !$is_payer)) {
                throw new Exception('error: unauthorized');
            }
            $giver = User::find(Session::uid());
            $budget = $giver->getBudget();
            // validate required fields
            if (empty($_POST['budget']) || empty($_POST['amount'])) {
                throw new Exception('error: args');
            }
            $budget_source_combo = (int) $_POST['budget'];
            $budgetSource = new Budget();
            if (!$budgetSource->loadById($budget_source_combo) ) {
                throw new Exception('Invalid budget!');
            }
            $amount = floatval($_POST['amount']);
            $stringAmount = number_format($amount, 2);
            $receiver = User::find($id);
            $reason = $_POST['reason'];
            $remainingFunds = $budgetSource->getRemainingFunds();
            if (!($amount <= $budget && $amount <= $remainingFunds)) {
                throw new Exception('You do not have enough budget available to pay this bonus.');
            }
            if (!$this->payBonusToUser($receiver->getId(), $amount, $reason, $budget_source_combo)) {
                throw new Exception('There was a problem while processing the payment.');
            }
            // deduct amount from balance
            $giver->updateBudget(-$amount, $budget_source_combo);
            Utils::sendTemplateEmail($receiver->getUsername(), 'bonus_received', array('amount' => $stringAmount, 'reason' => $reason));
            Utils::systemNotification('@' . $receiver->getNickname() . ' received a bonus of $' . $stringAmount);
            echo json_encode(array(
                'success' => true,
                'message' => 'Paid ' . $receiver->getNickname() . ' a bonus of $' . $stringAmount
            ));
        } catch (Exception $e) {
            echo json_encode(array(
                'success' => false,
                'message' => $e->getMessage()
            ));
        }
    }

    public function setW9Status($id, $status = 'rejected') {
        $this->view = null;
        try {
            $user = User::find($id);
            $currentUser = User::find(Session::uid());
            if (!$currentUser->getIs_runner() && !$getIs_runner->getIs_admin() && !$getIs_runner->getIs_payer()) {
                throw new Exception('Not enough rights');
            }

            if (!$user->getId()) {
                throw new Exception('Specified user does not exists');
            }

            $data = array();
            if ($status == 'rejected') {
                $data['reason'] = strip_tags($_POST['reason']);
            }

            if (! Utils::sendTemplateEmail($user->getUsername(), 'w9-' . $status, $data)) {
                error_log("UserController::setW9Status: Utils::send_email failed on w9 notification");
            }
            $user->setW9_status($status);
            $user->save();
            echo json_encode(array(
                'success' => true,
                'message' => 'W9 status updated'
            ));
        } catch (Exception $e) {
            echo json_encode(array(
                'success' => false,
                'message' => $e->getMessage()
            ));
        }
    }

    public function budgetHistory($id, $page = 1, $itemsPerPage = 10) {
        $this->view = null;
        $user = User::find($id);
        if (isset($_REQUEST['giver'])) {
            $giver = User::find($_REQUEST['giver']);
            $giver_id = $giver->getId();
        } else {
            $giver_id = 0;
        }
        $page = (is_numeric($page) ? $page : 1);
        $itemsPerPage = (is_numeric($itemsPerPage) ? $itemsPerPage : 10);
        echo json_encode($user->budgetHistory($giver_id, $page, $itemsPerPage));
    }

    public function isUSCitizen($id) {
        $this->view = null;
        try {
            $user = User::find($id);
            if (!$user->getId()) {
                throw new Exception('Invalid or non existent user');
            }
            echo json_encode(array(
                'success' => true,
                'isuscitizen' => $user->isUsCitizen()
            ));
        } catch (Exception $e) {
            echo json_encode(array(
                'success' => false,
                'message' => $e->getMessage()
            ));
        }
    }

    private function getProjectList() {
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

    private function payBonusToUser($user_id, $amount, $notes, $budget_id) {
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

}
