<?php

class ProjectController extends Controller {
    public function run($action, $param = '') {
        $method = '';
        switch($action) {
            case 'view':
            case 'add':
            case 'addDesigner':
            case 'designers':
            case 'removeDesigner':
            case 'addCodeReviewer':
            case 'codeReviewers':
            case 'removeCodeReviewer':
                $method = $action;
                break;
            default:
                $method = 'view';
                $param = $action;
        }
        $params = preg_split('/\//', $param);
        call_user_func_array(array($this, $method), $params);
    }

    public function view($id) {
        try {
            $project = Project::find($id);
        } catch(Exception $e) {
            $error  = $e->getMessage();
            die($error);
        }

        $is_runner = !empty($_SESSION['is_runner']) ? 1 : 0;
        $is_payer = !empty($_SESSION['is_payer']) ? 1 : 0;

        //get the project owner
        $project_user = new User();
        $project_user->findUserById($project->getOwnerId());
        $this->write('project_user', $project_user);

        $userId = getSessionUserId();
        if ($userId > 0) {
            initUserById($userId);
            $user = new User();
            $user->findUserById($userId);
            // @TODO: this is overwritten below..  -- lithium
            $nick = $user->getNickname();
            $userbudget =$user->getBudget();
            $budget = number_format($userbudget);
            $is_owner = $project->isOwner($user->getId());
            $is_admin = $user->getIs_admin();
        } else {
            $is_owner = false;
            $is_admin = false;
        }

        $runners = $project->getRunners();

        if (isset($_REQUEST['save_project']) && ( $is_runner || $is_payer || $is_owner)) {
            $project
                ->setDescription($_REQUEST['description'])
                ->setShortDescription($_REQUEST['short_description']);

            $project->setWebsite($_REQUEST['website']);
            $cr_anyone = ($_REQUEST['cr_anyone']) ? 1 : 0;
            $cr_3_favorites = ($_REQUEST['cr_3_favorites']) ? 1 : 0;
            $cr_project_admin = isset($_REQUEST['cr_project_admin']) ? 1 : 0;
            $cr_users_specified = isset($_REQUEST['cr_users_specified']) ? 1 : 0;
            $cr_job_runner = isset($_REQUEST['cr_job_runner']) ? 1 : 0;
            $internal = isset($_REQUEST['internal']) ? 1 : 0;
            $require_sandbox = isset($_REQUEST['require_sandbox']) ? 1 : 0;
            $hipchat_enabled = isset($_REQUEST['hipchat_enabled']) ? 1 : 0;
            $project->setCrAnyone($cr_anyone);
            $project->setCrFav($cr_3_favorites);
            $project->setCrAdmin($cr_project_admin);
            $project->setCrRunner($cr_job_runner);
            $project->setCrUsersSpecified($cr_users_specified);
            $project->setHipchatEnabled($hipchat_enabled);
            $project->setHipchatNotificationToken($_REQUEST['hipchat_notification_token']);
            $project->setHipchatRoom($_REQUEST['hipchat_room']);
            $project->setHipchatColor($_REQUEST['hipchat_color']);
            
            if ($user->getIs_admin()) {
                $project->setInternal($internal);
            }
            if ($user->getIs_admin()) {
                $project->setRequireSandbox($require_sandbox);
            }
            if ($_REQUEST['logoProject'] != "") {
                $project->setLogo(basename($_REQUEST['logoProject']));
            }
            $project->save();
            // we clear post to prevent the page from redirecting
            $_POST = array();
        }

        $project_id = $project->getProjectId();
        $hide_project_column = true;

        // save,edit,delete roles <mikewasmie 16-jun-2011>
        if ($is_runner || $is_payer || $project->isOwner($userId)) {
            if ( isset($_POST['save_role'])) {
                $args = array('role_title', 'percentage', 'min_amount');
                foreach ($args as $arg) {
                    $$arg = mysql_real_escape_string($_POST[$arg]);
                }
                $role_id = $project->addRole($project_id,$role_title,$percentage,$min_amount);
            }

            if (isset($_POST['edit_role'])) {
                $args = array('role_id','role_title', 'percentage', 'min_amount');
                foreach ($args as $arg) {
                    $$arg = mysql_real_escape_string($_POST[$arg]);
                }
                $res = $project->editRole($role_id, $role_title, $percentage, $min_amount);
            }

            if (isset($_POST['delete_role'])) {
                $role_id = mysql_real_escape_string($_POST['role_id']);
                $res = $project->deleteRole($role_id);
            }
            
        }

        /* Prevent reposts on refresh */
        if (! empty($_POST)) {
            unset($_POST);
            header('Location: ' . $projectName);
            exit();
        }

        $edit_mode = false;
        if (isset($_REQUEST['action']) && $_REQUEST['action'] == 'edit' && ( $is_runner || $is_payer || $is_owner)) {
            $edit_mode = true;
        }

        $this->write('project', $project);
        $this->write('edit_mode', $edit_mode);
        $this->write('is_owner', $is_owner);

        parent::run();
    }

    public function add($name) {
        $this->view = null;
        try {
            $user = User::find(getSessionUserId());
            if (!$user->getId() || !$user->getIs_admin()) {
                throw new Exception('Action not allowed.');
            }
            if (!ctype_alnum($name) || !ctype_alpha($name[0])) {
                throw new Exception('The name of the project can only contain letters (A-Z) and numbers (0-9) and must start with a letter.');
            }
            try {
                $project = Project::find($name);
            } catch (Exception $e) {}

            if (is_object($project) && $project->getProjectId($name)) {
                throw new Exception('Project with the same name already exists!');
            }

            $file = new File();
            $logo = '';
            if (!empty($_POST['logo'])) {
                $file->findFileById($_POST['logo']);
                $logo = basename($file->getUrl());
            }

            $project = new Project();
            $project->setName($name);
            $project->setDescription($_POST['description']);
            $project->setWebsite($_POST['website']);
            $project->setContactInfo($user->getUsername());
            $project->setOwnerId($user->getId());
            $project->setActive(true);
            $project->setInternal(true);
            $project->setRequireSandbox(true);
            $project->setLogo($logo);
            $project->setRepo_type('git');
            $project->setRepository($_POST['github_repo_url']);
            $project->setGithubId($_POST['github_client_id']);
            $project->setGithubSecret($_POST['github_client_secret']);
            $project->save();

            if ($file->getId()) {
                $file->setProjectId($project->getProjectId());
                $file->save();
            }

            $journal_message = '@' . $user->getNickname() . ' added project *' . $name . '*';
            sendJournalNotification($journal_message);
            echo json_encode(array(
                'success' => true,
                'message' => $journal_message
            ));
        } catch (Exception $e) {
            $error = $e->getMessage();
            echo json_encode(array(
                'success' => false,
                'message' => $error
            ));
        }
    }

    public function addCodeReviewer($id, $user_id) {
        $this->view = null;
        try {
            $project = Project::find($id);
            $user = User::find($user_id);
            $request_user = User::find(getSessionUserId());
            if (! $project->getProjectId()) {
                throw new Exception('Not a project in our system');
            }
            if (!$request_user->getIs_admin() && !$project->isOwner($request_user->getId())) {
                throw new Exception('Not enough rights');
            }
            if (!$user->getId()) {
                throw new Exception('Not a user in our system');
            }
            if ($project->isProjectCodeReviewer($user->getId())) {
                throw new Exception('Entered user is already a Code Reviewer for this project');
            }
            if (! $project->addCodeReviewer($user->getId())) {
                throw new Exception('Could not add the user as a designer for this project');
            }
            $founder = User::find($project->getOwnerId());
            $founderUrl = SECURE_SERVER_URL . 'jobs#userid=' . $founder->getId();
            $data = array(
                'nickname' => $user->getNickname(),
                'projectName' => $project->getName(),
                'projectUrl' => Project::getProjectUrl($project->getProjectId()),
                'projectFounder' => $founder->getNickname(),
                'projectFounderUrl' => $founderUrl
            );
            if (! sendTemplateEmail($user->getUsername(), 'project-codereviewer-added', $data)) {
                error_log("ProjectController:addCodeReviewer: send email to user failed");
            }
            // Add a journal notification
            $journal_message = '@' . $user->getNickname() . ' has been granted *Review* rights for project **' . $project->getName() . '**';
            sendJournalNotification($journal_message);
            echo json_encode(array(
                'success' => true,
                'data' => 'Code Reviewer added successfully'
            ));
        } catch (Exception $e) {
            $error = $e->getMessage();
            echo json_encode(array(
                'success' => false,
                'data' => $error
            ));
        }
    }

    public function addDesigner($id, $user_id) {
        $this->view = null;
        try {
            $project = Project::find($id);
            $user = User::find($user_id);
            $request_user = User::find(getSessionUserId());
            if (! $project->getProjectId()) {
                throw new Exception('Not a project in our system');
            }
            if (!$request_user->getIs_admin() && !$project->isOwner($request_user->getId())) {
                throw new Exception('Not enough rights');
            }
            if (!$user->getId()) {
                throw new Exception('Not a user in our system');
            }
            if ($project->isProjectRunner($user->getId())) {
                throw new Exception('Entered user is already a designer for this project');
            }
            if (! $project->addRunner($user->getId())) {
                throw new Exception('Could not add the user as a designer for this project');
            }
            $founder = User::find($project->getOwnerId());
            $founderUrl = SECURE_SERVER_URL . 'jobs#userid=' . $founder->getId();
            $data = array(
                'nickname' => $user->getNickname(),
                'projectName' => $project->getName(),
                'projectUrl' => Project::getProjectUrl($project->getProjectId()),
                'projectFounder' => $founder->getNickname(),
                'projectFounderUrl' => $founderUrl
            );
            if (! sendTemplateEmail($user->getUsername(), 'project-runner-added', $data)) {
                error_log("ProjectController:addRunner: send email to user failed");
            }
            // Add a journal notification
            $journal_message = '@' . $user->getNickname() . ' has been granted Designer rights for project **' . $project->getName() . '**';
            sendJournalNotification($journal_message);
            echo json_encode(array(
                'success' => true,
                'data' => 'Designer added successfully'
            ));
        } catch (Exception $e) {
            $error = $e->getMessage();
            echo json_encode(array(
                'success' => false,
                'data' => $error
            ));
        }
    }

    public function designers($id) {
        $this->view = null;
        try {
            $data = array();
            $project = Project::find($id);
            if ($designers = $project->getRunners()) {
                foreach ($designers as $designer) {
                    $data[] = array(
                        'id'=> $designer['id'],
                        'nickname' => $designer['nickname'],
                        'totalJobCount' => $designer['totalJobCount'],
                        'lastActivity' => $project->getRunnersLastActivity($designer['id']),
                        'owner' => $designer['owner']
                    );
                }
            }
            echo json_encode(array(
                'success' => true,
                'data' => array('designers' => $data)
            ));
        } catch (Exception $e) {
            $error = $e->getMessage();
            echo json_encode(array(
                'success' => false,
                'data' => $error
            ));
        }
    }

    public function removeDesigner($id) {
        $this->view = null;
        try {
            $data = array();
            $project = Project::find($id);
            if (! $project->getProjectId()) {
                throw new Exception('Not a project in our system');
            }
            $request_user = User::find(getSessionUserId());
            if (!$request_user->getIs_admin() && !$project->isOwner($request_user->getId())) {
                throw new Exception('Not enough rights');
            }
            $runners = array_slice(func_get_args(), 1);
            $deleted_runners = array();
            foreach($runners as $runner) {
                if ($project->deleteRunner($runner)) {
                    $deleted_runners[] = $runner;
                    $user = User::find($runner);
                    $founder = User::find($project->getOwnerId());
                    $founderUrl = SECURE_SERVER_URL . 'jobs#userid=' . $founder->getId();
                    $data = array(
                        'nickname' => $user->getNickname(),
                        'projectName' => $project->getName(),
                        'projectUrl' => Project::getProjectUrl($project->getProjectId()),
                        'projectFounder' => $founder->getNickname(),
                        'projectFounderUrl' => $founderUrl
                    );
                    if (! sendTemplateEmail($user->getUsername(), 'project-runner-removed', $data)) {
                        error_log("ProjectController::removeDesigner: send_email to user failed");
                    }
                }
            }
            echo json_encode(array(
                'success' => true,
                'data' => array('deleted_runners' => $deleted_runners)
            ));
        } catch (Exception $e) {
            $error = $e->getMessage();
            echo json_encode(array(
                'success' => false,
                'data' => $error
            ));
        }
    }

    public function codeReviewers($id) {
        $this->view = null;
        try {
            $data = array();
            $project = Project::find($id);
            if ($codeReviewers = $project->getCodeReviewers()) {
                foreach ($codeReviewers as $codeReviewer) {
                    $data[] = array(
                        'id'=> $codeReviewer['id'],
                        'nickname' => $codeReviewer['nickname'],
                        'totalJobCount' => $codeReviewer['totalJobCount'],
                        'lastActivity' => $project->getCodeReviewersLastActivity($codeReviewer['id']),
                        'owner' => $codeReviewer['owner']
                    );
                }
            }
            echo json_encode(array(
                'success' => true,
                'data' => array('codeReviewers' => $data)
            ));
        } catch (Exception $e) {
            $error = $e->getMessage();
            echo json_encode(array(
                'success' => false,
                'data' => $error
            ));
        }
    }

    public function removeCodeReviewer($id) {
        $this->view = null;
        try {
            $data = array();
            $project = Project::find($id);
            if (! $project->getProjectId()) {
                throw new Exception('Not a project in our system');
            }
            $request_user = User::find(getSessionUserId());
            if (!$request_user->getIs_admin() && !$project->isOwner($request_user->getId())) {
                throw new Exception('Not enough rights');
            }
            $codeReviewers = array_slice(func_get_args(), 1);
            $deleted_codeReviewers = array();
            foreach($codeReviewers as $codeReviewer) {
                if ($project->deleteCodeReviewer($codeReviewer)) {
                    $deleted_codeReviewers[] = $codeReviewer;
                    $user = User::find($codeReviewer);
                    $founder = User::find($project->getOwnerId());
                    $founderUrl = SECURE_SERVER_URL . 'jobs#userid=' . $founder->getId();
                    $data = array(
                        'nickname' => $user->getNickname(),
                        'projectName' => $project->getName(),
                        'projectUrl' => Project::getProjectUrl($project->getProjectId()),
                        'projectFounder' => $founder->getNickname(),
                        'projectFounderUrl' => $founderUrl
                    );
                    if (! sendTemplateEmail($user->getUsername(), 'project-codereview-removed', $data)) {
                        error_log("ProjectController::removeCodeReviewer: send_email to user failed");
                    }
                }
            }
            echo json_encode(array(
                'success' => true,
                'data' => array('deleted_codereviewers' => $deleted_codeReviewers)
            ));
        } catch (Exception $e) {
            $error = $e->getMessage();
            echo json_encode(array(
                'success' => false,
                'data' => $error
            ));
        }
    }
 }