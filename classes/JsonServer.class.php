<?php
//  Copyright (c) 2010, LoveMachine Inc.
//  All Rights Reserved.
//  http://www.lovemachineinc.com

//  This class handles a Json Requests if you need more functionality don't hesitate to add it.
//  But please be as fair as you comment your methods - maybe another developer needs them too.

class JsonServer
{

    protected $output;
    protected $user;
    protected $request;

    /**
     * If an action is passed and the method exists it will call this method.
     *
     * @param (string) $action
     * @param (mixed) $arguments
     */
    public function __construct()
    {

    }

    /**
     * Get the output
     *
     * @return (string) $this->output
     */
    public function getOutput()
    {
        if ($this->output === null) {
            $this->setOutput(array(
                'success' => false,
                'message' => 'No output!'
            ));
        }
        return $this->output;
    }

    /**
     * Sets the output property and json_encodes it.
     *
     * @param (array) $output
     * @return JsonServer $this
     */
    public function setOutput(array $output)
    {
        $this->output = json_encode($output);
    }

    public function run()
    {
        $method = 'action' . ucfirst($this->getAction());
        if (!method_exists($this, $method)) {
            throw new Exception('Action does not exit!');
        }

        $this->$method();
    }

    /**
     * @return the $action
     */
    public function getAction()
    {
        return $this->getRequest()->getActionName();
    }

    /**
     * @return the $user
     */
    public function getUser()
    {
        if (null === $this->user) {
            $this->setUser();
        }
        return $this->user;
    }

    /**
     * @return the $request
     */
    public function getRequest()
    {
        if (null === $this->request) {
            $this->setRequest();
        }
        return $this->request;
    }

    /**
     * This method gets the active user
     */
    public function setUser()
    {
        $user = new User();
        $user->findUserById($_SESSION['userid']);
        $this->user = $user;
    }

    /**
     * Here we set the JsonServer_Request
     */
    public function setRequest()
    {
        $this->request = new JsonServer_Request();
    }

    /**
     * This method approves a user
     *
     * @param (array) $args
     * @TODO: It does not appear that this function is in use, perhaps it is called
     * by an API somewhere but I can't find it    - lithium
     */
    protected function actionApproveUser($args = null)
    {
        if (null === $this->getRequest()->getParam('userid')) {
            throw new Exception('User ID not set!');
        }

        if ($this->getUser()->isRunner()) {
            $user = new User();
            $user->findUserById($this->getRequest()->getParam('userid'));
            $user->setW9_status('approved');

            if ($user->save()) {
                return $this->setOutput(array(
                    'success' => true,
                    'message' => 'The user ' . $user->getNickname() . ' has been approved!'
                ));
            } else {
                return $this->setOutput(array(
                    'success' => false,
                    'message' => 'Something went wrong, try it again later.'
                ));
            }
        } else {
            return $this->setOutput(array(
                'success' => false,
                'message' => 'You are not allowed to do that!'
            ));
        }
    }

    /**
     * This method checks the approval status of a user
     * @TODO: It does not appear that this function is in use, perhaps it is called
     * by an API somewhere but I can't find it    - lithium
     */
    protected function actionApprovalStatus()
    {
        if (null === $this->getRequest()->getParam('userid')) {
            throw new Exception('User ID not set!');
        }
        $user = new User();
        $user->findUserById($this->getRequest()->getParam('userid'));

        if ($user->isW9Approved()) {
            return $this->setOutput(array(
                'success' => true,
                'approved'=> true,
                'message' => 'The user ' . $user->getNickname() . ' is approved!'
            ));
        } else {
            return $this->setOutput(array(
                'success' => true,
                'approved'=> false,
                'message' => 'The user ' . $user->getNickname() . ' is not approved!'
            ));
        }
    }

    /**
     * This method checks the approval status of a user
     */
    protected function actionIsUSCitizen()
    {
        if (null === $this->getRequest()->getParam('userid')) {
            throw new Exception('User ID not set!');
        }
        $user = new User();
        $user->findUserById($this->getRequest()->getParam('userid'));

        if ($user->isUsCitizen()) {
            return $this->setOutput(array(
                'success' => true,
                'isuscitizen'=> true,
                'message' => 'The user ' . $user->getNickname() . ' is an US citizen!'
            ));
        } else {
            return $this->setOutput(array(
                'success' => true,
                'isuscitizen'=> false,
                'message' => 'The user ' . $user->getNickname() . ' is not an US citizen!'
            ));
        }
    }

    /**
     * This method removes a file from a workitem
     */
    protected function actionFileRemove()
    {
        if(isset($_SESSION['userid']) && $_SESSION['userid'] > 0) {

            $fileid = $this->getRequest()->getParam('fileid');
            $file = new File();
            $file->findFileById($fileid);

            try {
                $workitem = WorkItem::getById($file->getWorkitem());
            } catch (Exception $e) {}
            if (
              $_SESSION['is_runner'] ||
              $_SESSION['is_payer'] ||
              $_SESSION['userid'] == $file->getUserid() ||
              $_SESSION['userid'] == $workitem->getCreatorId() ||
              $_SESSION['userid'] == $workitem->getMechanicId() ||
              $_SESSION['userid'] == $workitem->getRunnerId()
            ) {
                $success = $file->remove();
            } else {
                $success = array(
                    'success' => false,
                    'message' => 'Permission denied!'
                );
            }
        } else {
            $success = array(
                'success' => false,
                'message' => 'Permission denied!'
            );
        }
        return $this->setOutput($success);
    }
    protected function actionLogoUpload() {
        // check if we have a error
        if ($_FILES['logoFile']['error'] !== UPLOAD_ERR_OK) {
            return $this->setOutput(array(
                        'success' => false,
                        'message' => File::fileUploadErrorMessage($_FILES['logoFile']['error'])
                    ));
        }

        $ext = end(explode(".", $_FILES['logoFile']['name']));
        $fileName = File::uniqueFilename($ext);
        $tempFile = $_FILES['logoFile']['tmp_name'];
        $title = basename($_FILES['logoFile']['name']);
        $path = UPLOAD_PATH . '/' . $fileName;

        if (copy($tempFile, $path)) {
            $url = SERVER_URL . 'uploads/' . $fileName;
            $projectid = $this->getRequest()->getParam('projectid');
            $projectid = (is_numeric($projectid) ? $projectid : null);

            $file = new File();
            $file->setProjectId($projectid)
                  ->setTitle($title)
                  ->setUrl($url);
            $success = $file->save();

            if ($success) {
                // move file to S3
                try {
                    File::s3Upload($file->getRealPath(), APP_ATTACHMENT_PATH . $file->getFileName());
                    $file->setUrl(APP_ATTACHMENT_URL . $file->getFileName());
                    $file->save();
                    unlink($file->getRealPath());
                } catch (Exception $e) {
                    $success = false;
                    $error = 'There was a problem uploading your file';
                    error_log(__FILE__ . ": Error uploading images to S3:\n$e");
                }
            }

            return $this->setOutput(array(
                        'success' => $success,
                        'fileid' => $file->getId(),
                        'url' => $file->getUrl(),
                        'title' => $file->getTitle(),
                        'fileName' => $file->getFileName(),
                        'description' => '',
                        'message' => $error ? $error : '')
            );
        } else {
            return $this->setOutput(array(
                        'success' => false,
                        'message' => 'An error occured while uploading the  ' . $tempFile . ',' . $path . ' please try again!'
                    ));
        }
    }
    /**
     * This method adds a file to a workitem
     */
    protected function actionFileUpload() {
        if(!isset($_SESSION['userid']) || !($_SESSION['userid'] > 0)) {
            return $this->setOutput(array(
                'success' => false,
                'message' => 'Not enough rights!'
            ));
        }

        // check if we have a error
        if ($_FILES['file']['error'] !== UPLOAD_ERR_OK) {
            return $this->setOutput(array(
                'success' => false,
                'message' => File::fileUploadErrorMessage($_FILES['file']['error'])
            ));
        }

        if ((isset($_FILES['file']['type']) && !empty($_FILES['file']['type']))) {
            $mime = $_FILES['file']['type'];
        } else {
            $mime = 'application/pdf';
        }
        $ext = end(explode(".", $_FILES['file']['name']));
        $fileName = File::uniqueFilename($ext);
        $tempFile = $_FILES['file']['tmp_name'];
        $title = basename($_FILES['file']['name']);
        $path = UPLOAD_PATH . '/' . $fileName;

        if (copy($tempFile, $path)) {
            $url = SERVER_URL . 'uploads/' . $fileName;
            $workitem = $this->getRequest()->getParam('workitem');
            $workitem = (is_numeric($workitem) ? $workitem : null);
            $projectid = $this->getRequest()->getParam('projectid');
            $projectid = (is_numeric($projectid) ? $projectid : null);

            $file = new File();
            $file->setMime($mime)
                 ->setUserid($this->getRequest()->getParam('userid'))
                 ->setWorkitem($workitem)
                 ->setProjectId($projectid)
                 ->setTitle($title)
                 ->setUrl($url);
            $success = $file->save();
            $icon = File::getIconFromMime($file->getMime());
            if ($icon === false) {
               $filetype = 'image';
               $icon = 'images/icons/default.png';
            }
            if ($workitem) {
                $workitem_attached = new WorkItem();
                $workitem_attached->loadById($workitem);
                $current_user = new User();
                $current_user->findUserById($_SESSION['userid']);
                $journal_message =
                    '@' . $current_user->getNickname() . ' uploaded an [attachment](' .
                    $file->getUrl() . ') to #' . $workitem;
                sendJournalNotification($journal_message);
            }
            return $this->setOutput(array(
               'success' => true,
               'fileid'  => $file->getId(),
               'url'       => $file->getUrl(),
               'icon'      => $icon,
               'title'      => $file->getTitle(),
               'description' => '',
               'filetype'=> (isset($filetype) ? $filetype : '')
            ));
        } else {
            return $this->setOutput(array(
                'success' => false,
                'message' => 'An error occured while uploading the  '.$tempFile.','. $path.' please try again!'
            ));
        }

    }

    protected function actionScanFile() {
        $scanner = new ScanAssets();
        $file_id = $this->getRequest()->getParam('fileid');

        $file = new File();
        $file->findFileById($file_id);
        $success = false;
        $icon = File::getIconFromMime($file->getMime());
        if ($scanner->scanFile($file->getId())) {
            $success = true;
        }
        if ($icon === false) {
            $icon = $file->getUrl();
        }

        if ($success) {
            // we need to reload the file because scanner might have updated fields
            // and our object is out of date
            $file->findFileById($file_id);

            // move file to S3
            try {
                File::s3Upload($file->getRealPath(), APP_ATTACHMENT_PATH . $file->getFileName());
                $file->setUrl(APP_ATTACHMENT_URL . $file->getFileName());
                $file->save();
                // delete the physical file now it is in S3
                unlink($file->getRealPath());
            } catch (Exception $e) {
                $success = false;
                $error = 'There was a problem uploading your file';
                error_log(__FILE__.": Error uploading images to S3:\n$e");
            }
        }


        return $this->setOutput(array(
            'success' => $success,
            'error'   => isset($error) ? $error : '',
            'fileid'  => $file->getId(),
            'url'     => $file->getUrl(),
            'icon'    => $icon
        ));
    }


    protected function actionChangeFileTitle()
    {
        if(isset($_SESSION['userid']) && $_SESSION['userid'] > 0) {
            $fileid = $this->getRequest()->getParam('fileid');
            $title = $this->getRequest()->getParam('value');

            $file = new File();
            $file->findFileById($fileid);

            if (is_numeric($file->getWorkitem())) {
                $workitem = WorkItem::getById($file->getWorkitem());
                if (
                    $_SESSION['is_runner'] ||
                    $_SESSION['is_payer'] ||
                    $_SESSION['userid'] == $file->getUserid() ||
                    $_SESSION['userid'] == $workitem->getCreatorId() ||
                    $_SESSION['userid'] == $workitem->getMechanicId() ||
                    $_SESSION['userid'] == $workitem->getRunnerId()
                ) {
                    $saveit = true;
                }
            } else {
                if (
                    $_SESSION['is_runner'] ||
                    $_SESSION['is_payer'] ||
                    $_SESSION['userid'] == $file->getUserid()
                ) {
                    $saveit = true;
                }
            }
            if ($saveit) {
                $file->setTitle((string)$title);
                $success = $file->save();
            }
        }
        die(isset($title) ? $title : '');
    }

    protected function actionChangeFileDescription()
    {
        if(isset($_SESSION['userid']) && $_SESSION['userid'] > 0) {
            $fileid = $this->getRequest()->getParam('fileid');
            $description = $this->getRequest()->getParam('value');

            $file = new File();
            $file->findFileById($fileid);
            $saveit = false;
            if (is_numeric($file->getWorkitem())) {
                $workitem = WorkItem::getById($file->getWorkitem());
                if (
                    $_SESSION['is_runner'] ||
                    $_SESSION['is_payer'] ||
                    $_SESSION['userid'] == $file->getUserid() ||
                    $_SESSION['userid'] == $workitem->getCreatorId() ||
                    $_SESSION['userid'] == $workitem->getMechanicId() ||
                    $_SESSION['userid'] == $workitem->getRunnerId()
                ) {
                    $saveit = true;
                }
            } else {
                if (
                    $_SESSION['is_runner'] ||
                    $_SESSION['is_payer'] ||
                    $_SESSION['userid'] == $file->getUserid()
                ) {
                    $saveit = true;
                }
            }
            if ($saveit) {
                $file->setDescription((string)$description);
                $success = $file->save();
            }
        }
        die(isset($description) ? $description : '');
    }

    protected function actionChangeFileStatus()
    {
        if(isset($_SESSION['userid']) && $_SESSION['userid'] > 0) {
            $fileid = $this->getRequest()->getParam('fileid');
            $status = $this->getRequest()->getParam('status');

            $file = new File();
            $file->findFileById($fileid);

            $workitem = WorkItem::getById($file->getWorkitem());
            if (
              $_SESSION['is_runner'] ||
              $_SESSION['is_payer'] ||
              $_SESSION['userid'] == $file->getUserid() ||
              $_SESSION['userid'] == $workitem->getCreatorId() ||
              $_SESSION['userid'] == $workitem->getMechanicId() ||
              $_SESSION['userid'] == $workitem->getRunnerId()
            ) {
                $file->setStatus((int)$status);
                $success = $file->save();
            }
        } else {
            $success = false;
        }
        return $this->setOutput(array(
            'success' => $success
        ));
    }

    protected function actionStartCodeReview() {
        $workitem_id = $this->getRequest()->getParam('workitem');
        $user_id = $this->getRequest()->getParam('userid');
        $workItemToCheckCodeReview = new WorkItem($workitem_id);

        // This loop waits its turn to check for review. Shared memory is used for this
        // 2nd, third etc reviewers code will halt here
        do {
            $sem_id = shmop_open($workitem_id, "n", 0644, 10);
        } while ($sem_id === false);

        $workItem = new WorkItem($workitem_id);

        $user = new User();
        $user->findUserById($user_id);

        $status = $workItem->startCodeReview($user_id);

        sleep(4); // we want a 3 sec delay to ensure that database update statement executes
        shmop_delete($sem_id); // Delete shared memory

        if ($status === null) {
            return $this->setOutput(array('success' => false, 'data' => nl2br('Code Review not available right now')));
        } else if ($status === true || (int)$status == 0) {
            $journal_message = '@' . $user->getNickname() . ' has started a code review for #' . $workitem_id. ' ';
            sendJournalNotification($journal_message);

            $options = array(
                'type' => 'code-review-started',
                'workitem' => $workItem,
            );
            $data = array(
                'nick' => $user->getNickname()
            );
            Notification::workitemNotifyHipchat($options, $data);

            return $this->setOutput(array('success' => true,'data' => $journal_message));
        } else {
            $workItem->setStatus('SvnHold');
            $workItem->save();

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

            $user_is_mechanic = $workItem->getMechanic()->getId() == $user_id;
            require_once('Notification.class.php');

            //post comment
            $comment = new Comment();
            $comment->setWorklist_id((int)$workitem_id);
            $comment->setUser_id((int) $user_id);
            $comment->setComment($message);
            $comment->save();

            $journalMessage = str_replace("\n", '', $message);
            sendJournalNotification("@Otto could not authorize sandbox for #" . $workitem_id . $journalMessage . ' Status set to *SvnHold*');
            return $this->setOutput(array(
                    'success' => false,
                    'data' => 'Sandbox verification failed. Alerting developer to resolve.'
                )
            );
        }
    }

    protected function actionCancelCodeReview() {
        $workitem_id = $this->getRequest()->getParam('workitem');
        $user_id = $this->getRequest()->getParam('userid');
        $workitem = new WorkItem();
        $workitem->loadById($workitem_id);
        $user = new User();
        $user->findUserById($user_id);
        $workitem->setCRStarted(0);
        $workitem->setCReviewerId(0);
        $workitem->save();
        $journal_message = '@' . $user->getNickname() . ' has canceled their code review for #' . $workitem_id;
        sendJournalNotification($journal_message);

        $options = array(
            'type' => 'code-review-canceled',
            'workitem' => $workitem,
        );
        $data = array(
            'nick' => $user->getNickname(),
        );
        Notification::workitemNotifyHipchat($options, $data);

        return $this->setOutput(array('success' => true,'data' => $journal_message));
    }

    protected function actionGetFilesForWorkitem() {
        $workitem_id = $this->getRequest()->getParam('workitem');
        $files = File::fetchAllFilesForWorkitem($workitem_id);
        $user = new User();
        $user->findUserById($this->getRequest()->getParam('userid'));
        $workitem = WorkItem::getById($workitem_id);

        $data = array(
            'images' => array(),
            'documents' => array()
        );
        foreach ($files as $file) {
            if (!File::isAllowed($file->getStatus(), $user)) {
                continue;
            }
            if ($file->getIs_scanned() == 0) {
                $fileUrl = 'javascript:;';
                $errorMsg = "This file is awaiting verification, please try again after sometime.";
                $iconUrl = 'images/icons/default.png';
            } else {
                $fileUrl = $file->getUrl();
                $iconUrl = $file->getUrl();
            }
            if (
              $_SESSION['is_runner'] ||
              $_SESSION['is_payer'] ||
              $_SESSION['userid'] == $file->getUserid() ||
              $_SESSION['userid'] == $workitem->getCreatorId() ||
              $_SESSION['userid'] == $workitem->getMechanicId() ||
              $_SESSION['userid'] == $workitem->getRunnerId()
            ) {
                $can_delete = true;
            } else {
                $can_delete = false;
            }

            $icon = File::getIconFromMime($file->getMime());
            if(! isset($_SESSION['userid']) || $_SESSION['userid'] <= 0) {
                $fileUrl = "javascript:;";
                $errorMsg = "Please login to view this file.";
            } else {
                $errorMsg = "";
            }
            if ($icon === false) {
                array_push($data['images'], array(
                    'fileid'=> $file->getId(),
                    'url'    => $fileUrl,
                    'error' => $errorMsg,
                    'can_delete' => $can_delete,
                    'icon'    => $iconUrl,
                    'title' => $file->getTitle(),
                    'description' => $file->getDescription()
                ));
            } else {
                array_push($data['documents'], array(
                    'fileid'=> $file->getId(),
                    'url'    => $fileUrl,
                    'error' => $errorMsg,
                    'can_delete' => $can_delete,
                    'icon'    => $icon,
                    'title' => $file->getTitle(),
                    'description' => $file->getDescription()
                ));
            }
        }

        return $this->setOutput(array(
            'success' => true,
            'data' => $data
        ));
    }

    protected function actionGetFilesForProject() {
        $files = File::fetchAllFilesForProject($this->getRequest()->getParam('projectid'));
        $user = new User();
        $user->findUserById($this->getRequest()->getParam('userid'));
        $data = array(
            'images' => array(),
            'documents' => array()
        );
        foreach ($files as $file) {
            if (!File::isAllowed($file->getStatus(), $user)) {
                continue;
            }
            if ($file->getIs_scanned() == 0) {
                $fileUrl = 'javascript:;';
                $iconUrl = 'images/icons/default.png';
            } else {
                $fileUrl = $file->getUrl();
                $iconUrl = $file->getUrl();
            }

            $icon = File::getIconFromMime($file->getMime());
            if ($icon === false) {
                array_push($data['images'], array(
                    'fileid'=> $file->getId(),
                    'url'    => $fileUrl,
                    'icon'    => $iconUrl,
                    'title' => $file->getTitle(),
                    'description' => $file->getDescription()
                ));
            } else {
                array_push($data['documents'], array(
                    'fileid'=> $file->getId(),
                    'url'    => $fileUrl,
                    'icon'    => $icon,
                    'title' => $file->getTitle(),
                    'description' => $file->getDescription()
                ));
            }
        }

        return $this->setOutput(array(
            'success' => true,
            'data' => $data
        ));
    }

    protected function actionGetCodeReviewersProject() {
        $data = array();
        $project = new Project();
        try {
            $project->loadById($this->getRequest()->getParam('projectid'));

            if($codeReviewers = $project->getCodeReviewers()) {
                foreach($codeReviewers as $codeReviewer) {
                    $data[] = array(
                        'id'=> $codeReviewer['id'],
                        'nickname' => $codeReviewer['nickname'],
                        'totalJobCount' => $codeReviewer['totalJobCount'],
                        'lastActivity' => $project->getCodeReviewersLastActivity($codeReviewer['id']),
                        'owner' => $codeReviewer['owner']
                    );
                }
            }

            return $this->setOutput(array(
                'success' => true,
                'data' => array('codeReviewers' => $data)
            ));
        } catch (Exception $e) {
            $error = $e->getMessage();
            return $this->setOutput(array(
                'success' => false,
                'data' => $error
            ));
        }
    }

    protected function actionGetRunnersForProject() {
        $data = array();
        $project = new Project();
        try {
            $project->loadById($this->getRequest()->getParam('projectid'));

            if($runners = $project->getRunners()) {
                foreach($runners as $runner) {
                    $data[] = array(
                        'id'=> $runner['id'],
                        'nickname' => $runner['nickname'],
                        'totalJobCount' => $runner['totalJobCount'],
                        'lastActivity' => $project->getRunnersLastActivity($runner['id']),
                        'owner' => $runner['owner']
                    );
                }
            }

            return $this->setOutput(array(
                'success' => true,
                'data' => array('runners' => $data)
            ));
        } catch (Exception $e) {
            $error = $e->getMessage();
            return $this->setOutput(array(
                'success' => false,
                'data' => $error
            ));
        }
    }

    protected function actionRemoveRunnersFromProject() {
        $data = array();
        $project = new Project();

        try {
            $request_user = $this->getUser();

            $project->loadById($this->getRequest()->getParam('projectid'));
            if (! $project->getProjectId()) {
                throw new Exception('Not a project in our system');
            }
            if (!$request_user->getIs_admin() && !$project->isOwner($request_user->getId())) {
                throw new Exception('Not enough rights');
            }

            $runners = preg_split('/;/', $this->getRequest()->getParam('runners'));
            $deleted_runners = array();
            foreach($runners as $runner) {
                if ($project->deleteRunner($runner)) {
                    $deleted_runners[] = $runner;
                    $user = new User();
                    $user->findUserById($runner);
                    $founder = new User();
                    $founder->findUserById($project->getOwnerId());
                    $founderUrl = SECURE_SERVER_URL . 'jobs#userid=' . $founder->getId();
                    $data = array(
                        'nickname' => $user->getNickname(),
                        'projectName' => $project->getName(),
                        'projectUrl' => Project::getProjectUrl($project->getProjectId()),
                        'projectFounder' => $founder->getNickname(),
                        'projectFounderUrl' => $founderUrl
                    );
                    if (! sendTemplateEmail($user->getUsername(), 'project-runner-removed', $data)) {
                        error_log("JsonServer:actionRemoveRunnersFromProject: send_email to user failed");
                    }
                }
            }

            return $this->setOutput(array(
                'success' => true,
                'data' => array('deleted_runners' => $deleted_runners)
            ));
        } catch (Exception $e) {
            $error = $e->getMessage();
            return $this->setOutput(array(
                'success' => false,
                'data' => $error
            ));
        }
    }

    protected function actionRemoveCodeReviewersFromProject() {
        $data = array();
        $project = new Project();

        try {
            $request_user = $this->getUser();

            $project->loadById($this->getRequest()->getParam('projectid'));
            if (! $project->getProjectId()) {
                throw new Exception('Not a project in our system');
            }
            if (!$request_user->getIs_admin() && !$project->isOwner($request_user->getId())) {
                throw new Exception('Not enough rights');
            }

            $codeReviewers = preg_split('/;/', $this->getRequest()->getParam('codeReviewers'));
            $deleted_codeReviewers = array();
            foreach($codeReviewers as $codeReviewer) {
                if ($project->deleteCodeReviewer($codeReviewer)) {
                    $deleted_codeReviewers[] = $codeReviewer;
                    $user = new User();
                    $user->findUserById($codeReviewer);
                    $founder = new User();
                    $founder->findUserById($project->getOwnerId());
                    $founderUrl = SECURE_SERVER_URL . 'jobs#userid=' . $founder->getId();
                    $data = array(
                        'nickname' => $user->getNickname(),
                        'projectName' => $project->getName(),
                        'projectUrl' => Project::getProjectUrl($project->getProjectId()),
                        'projectFounder' => $founder->getNickname(),
                        'projectFounderUrl' => $founderUrl
                    );
                    if (! sendTemplateEmail($user->getUsername(), 'project-codereview-removed', $data)) {
                        error_log("JsonServer:actionRemoveCodeReviewersFromProject: send_email to user failed");
                    }
                }
            }

            return $this->setOutput(array(
                'success' => true,
                'data' => array('deleted_codereviewers' => $deleted_codeReviewers)
            ));
        } catch (Exception $e) {
            $error = $e->getMessage();
            return $this->setOutput(array(
                'success' => false,
                'data' => $error
            ));
        }
    }

    /**
     * Allows you to add a reviewer to the project
     */
    protected function actionAddCodeReviewerToProject() {
        $project = new Project();
        $user = new User();
        $founder = new User();
        try {
            $request_user = $this->getUser();

            $project->loadById($this->getRequest()->getParam('projectid'));
            if (! $project->getProjectId()) {
                throw new Exception('Not a project in our system');
            }
            if (!$request_user->getIs_admin() && !$project->isOwner($request_user->getId())) {
                throw new Exception('Not enough rights');
            }
            $user->findUserByNickname($this->getRequest()->getParam('nickname'));
            if (!$user->getId()) {
                throw new Exception('Not a user in our system');
            }
            if ($project->isProjectCodeReviewer($user->getId())) {
                throw new Exception('Entered user is already a Code Reviewer for this project');
            }

            if (! $project->addCodeReviewer($user->getId())) {
                throw new Exception('Could not add the user as a designer for this project');
            }

            $founder->findUserById($project->getOwnerId());
            $founderUrl = SECURE_SERVER_URL . 'jobs#userid=' . $founder->getId();
            $data = array(
                'nickname' => $user->getNickname(),
                'projectName' => $project->getName(),
                'projectUrl' => Project::getProjectUrl($project->getProjectId()),
                'projectFounder' => $founder->getNickname(),
                'projectFounderUrl' => $founderUrl
            );
            if (! sendTemplateEmail($user->getUsername(), 'project-codereviewer-added', $data)) {
                error_log("JsonServer:actionAddCodeReviewerToProject: send email to user failed");
            }
            // Add a journal notification
            $journal_message = '@' . $user->getNickname() . ' has been granted *Review* rights for project **' . $project->getName() . '**';
            sendJournalNotification($journal_message);

            return $this->setOutput(array(
                'success' => true,
                'data' => 'Code Reviewer added successfully'
            ));
        } catch (Exception $e) {
            $error = $e->getMessage();
            return $this->setOutput(array(
                'success' => false,
                'data' => $error
            ));
        }
    }

    protected function actionAddRunnerToProject() {
        $project = new Project();
        $user = new User();
        $founder = new User();
        try {
            $request_user = $this->getUser();

            $project->loadById($this->getRequest()->getParam('projectid'));
            if (! $project->getProjectId()) {
                throw new Exception('Not a project in our system');
            }
            if (!$request_user->getIs_admin() && !$project->isOwner($request_user->getId())) {
                throw new Exception('Not enough rights');
            }
            $user->findUserByNickname($this->getRequest()->getParam('nickname'));
            if (!$user->getId()) {
                throw new Exception('Not a user in our system');
            }
            if ($project->isProjectRunner($user->getId())) {
                throw new Exception('Entered user is already a designer for this project');
            }

            if (! $project->addRunner($user->getId())) {
                throw new Exception('Could not add the user as a designer for this project');
            }

            $founder->findUserById($project->getOwnerId());
            $founderUrl = SECURE_SERVER_URL . 'jobs#userid=' . $founder->getId();
            $data = array(
                'nickname' => $user->getNickname(),
                'projectName' => $project->getName(),
                'projectUrl' => Project::getProjectUrl($project->getProjectId()),
                'projectFounder' => $founder->getNickname(),
                'projectFounderUrl' => $founderUrl
            );
            if (! sendTemplateEmail($user->getUsername(), 'project-runner-added', $data)) {
                error_log("JsonServer:actionAddRunnerToProject: send email to user failed");
            }
            // Add a journal notification
            $journal_message = '@' . $user->getNickname() . ' has been granted Designer rights for project **' . $project->getName() . '**';
            sendJournalNotification($journal_message);

            return $this->setOutput(array(
                'success' => true,
                'data' => 'Designer added successfully'
            ));
        } catch (Exception $e) {
            $error = $e->getMessage();
            return $this->setOutput(array(
                'success' => false,
                'data' => $error
            ));
        }
    }


    /**
     * This method handles the upload of the W9 form
     *
     */
    protected function actionW9Upload() {
        // check if we have a file
        if (empty($_FILES)) {
            return $this->setOutput(array(
                'success' => false,
                'message' => 'No file uploaded!'
            ));
        }

//TODO:REVIEW - garth: is there ever a time that we would need to trust $_GET['userid'] here?
//This is a partial step resolving a code conflict and I don't want to break/loose these changes
//$userid = $this->getUser()->getId();
        $user = new User();
        $user->findUserById($this->getRequest()->getParam('userid'));
        $tempFile = $_FILES['Filedata']['tmp_name'];
        //Garth -- Make these files easy to organize
        $newFile = sprintf("%s_%s_%s_%s_%s_W9.pdf",$user->getNickname(),$user->getId(),$user->getLast_name(),$user->getFirst_name(),date("Ymd"));
        $path = APP_INTERNAL_PATH . $newFile;
        $url = APP_INTERNAL_URL . $newFile;

//TODO:REVIEW - I think this file still needs to be scanned, check context - garth

        // move file to S3
        try {
            File::s3Upload($tempFile, $path, false);
            $url = File::s3AuthenticatedURL($path);
            $subject = "W-9 Form from " . $user->getNickname();

            $body = "<p>Hi there,</p>";
            $body .= "<p>" . $user->getNickname() . " just uploaded his/her W-9 Form.</p>";
            $body .= "<p>When it's tax time, you'll need to know that "
                  . $user->getNickname() . " is " . $user->getFirst_name() . " " . $user->getLast_name() . "</p>";
            $body .= "<p>You can download and approve it from this URL:</p>";

            $body .= "<p><a href=\"{$url}\">Click here</a></p>";

            if(! send_email(FINANCE_EMAIL, $subject, $body)) {
                error_log("JsonServer:w9Upload: send_email to admin failed");
            }

            // send approval email to user
            $subject = 'Worklist.net: W9 Received';

            $body = "<p>Hello you!</p>";
            $body .= "<p>Thanks for uploading your W9 to our system.
                One of our staff will verify the receipt and then activate
                your account for bidding within the next 24 hours.<br/>
                Until then, you are welcome to browse the jobs list,
                take a look at the open source code via the links at
                the bottom of any worklist page and ask questions in our Chat.
                <br /><br />
                See you in the Worklist!
                <br /><br />
                - the Worklist.net team";

            if(! send_email($user->getUsername(), $subject, $body)) {
                error_log("JsonServer:w9Upload: send_email to user failed");
            }

            $user->setW9_status('pending-approval');
            $user->save();
            return $this->setOutput(array(
                'success' => true,
                'message' => 'The file ' . basename( $_FILES['Filedata']['name']) . ' has been uploaded.'
            ));

        } catch (Exception $e) {
            $success = false;
            $error = 'There was a problem uploading your file';
            error_log(__FILE__.": Error uploading W9 form to S3:\n$e");

            return $this->setOutput(array(
                'success' => false,
                'message' => 'An error occured while uploading the file, please try again!'
            ));
        }


    }

    protected function actionChangeUserStatus()
    {
        $aUser = $this->getUser();
        if ($aUser->isRunner()) {
            $user = new User();
            $user->findUserById($this->getRequest()->getParam('userid'));
            $user->setIs_active($this->getRequest()->getParam('status'));
            $user->save();
            return $this->setOutput(array(
                'success' => true
            ));
        }
        return $this->setOutput(array(
            'success' => false
        ));

    }

    /**
     * This method checks if the user is allowed to bid in the W9 context
     */
    protected function actionCheckUserForW9()
    {
        $user = new User();
        $user->findUserById($this->getRequest()->getParam('userid'));

        // If user is no US citizen we don't need the 10099
        if (!$user->isUsCitizen() || $user->isW9Approved()) {
            return $this->setOutput(array(
                'success' => true,
                'message' => 'The user ' . $user->getNickname() . ' is not a US Citizen or has been approved earlier!'
            ));
        }

        // Now we need to get the fee amount
        $sql     =     'SELECT SUM(`amount`) AS `sum_amount` FROM `' . FEES . '` WHERE ';
        // Get the right userfees
        $sql    .=    '`user_id` = ' . $user->getId() . ' ';
        // Only fees that haven't been withdrawn
        $sql    .=    'AND `withdrawn` = 0 ';
        // Status should be DONE
        $sql    .=    'AND `worklist_id` IN (SELECT `id` FROM `' . WORKLIST . '` WHERE `status` = "Done") ';
        // We only need this year
        $sql    .=    'AND YEAR(`date`) = YEAR(NOW()) ';

        // now we fetch the sum
        $result = mysql_query($sql);
        $fees = mysql_fetch_object($result)->sum_amount;
        if (!is_numeric($fees)) {
            return $this->setOutput(array(
                'success' => true,
                'message' => 'The users ' . $user->getNickname() . ' fees are not numeric, which means he has no paid fees.'
            ));
        } else if (((int)$fees + (int)$this->getRequest()->getParam('amount')) < 600) {
            return $this->setOutput(array(
                'success' => true,
                'message' => 'The users ' . $user->getNickname() . ' amount ($' . ((int)$fees + (int)$this->getRequest()->getParam('amount')) . ') does not exceed $600.'
            ));
        }

        return $this->setOutput(array(
            'success' => false,
            'message' => 'The users ' . $user->getNickname() . ' amount ($' . ((int)$fees + (int)$this->getRequest()->getParam('amount')) . ') does exceed $600.'
        ));
    }

    protected function actionChangeRunner()
    {
        $workitem = (int)$this->getRequest()->getParam('workitem');
        $runner_id = (int) $this->getRequest()->getParam('runner');
        $runner = new User();

        if ($this->getUser()->isRunner()) {
            $workitem = new WorkItem($workitem);
            if ($runner->findUserById($runner_id) && $runner->isRunner()
                && Project::isAllowedRunnerForProject($runner_id, $workitem->getProjectId()) ) {
                $oldRunner = $workitem->getRunner();
                $workitem->setRunnerId($runner->getId())
                         ->save();
                $project = new Project();
                $project->loadById($workitem->getProjectId());
                $project_name = $project->getName();
                $from_address = '<noreply-' . $project_name . '@worklist.net>';
                $headers = array('From' => '"' . $project_name . '-designer changed" ' . $from_address);

                // This should eventually be moved to Notification->workitemNotify - dans
                $subject = '#' . $workitem->getId() . ' '. $workitem->getSummary();
                $body = "<p>Hi there,</p>";
                $body .= "<p>I just wanted to let you know that the Job #" . $workitem->getId() . " (" . $workitem->getSummary() . ") has been reassigned to Designer " . $runner->getNickname() . ".</p>";
                $body .= "<p>See you in the Workroom!</p>";

                if ($oldRunner) {
                    if(!send_email($oldRunner->getNickname() . ' <' . $oldRunner->getUsername() . '>', $subject, $body, null, $headers)) { error_log("JsonServer:changeOldRunner failed"); }
                }
                if ($workitem->getRunner()) {
                    if(!send_email($workitem->getRunner()->getNickname() . ' <' . $workitem->getRunner()->getUsername() . '>', $subject, $body, null, $headers)) { error_log("JsonServer:changeGetRunner: send_email failed"); }
                }
                if ($workitem->getCreator()) {
                    if(!send_email($workitem->getCreator()->getNickname() . ' <' . $workitem->getCreator()->getUsername() . '>', $subject, $body, null, $headers)) { error_log("JsonServer:changeCreator: send_email failed"); }
                }
                if ($workitem->getMechanic()) {
                    if(!send_email($workitem->getMechanic()->getNickname() . ' <' . $workitem->getMechanic()->getUsername() . '>', $subject, $body, null, $headers)) { error_log("JsonServer:changeMechanic: send_email failed"); }
                }

                sendJournalNotification('\#' . $workitem->getId() . ' updated by @' . $this->getUser()->getNickname() .' Designer reassigned to @' . $workitem->getRunner()->getNickname());

                return $this->setOutput(array(
                    'success' => true,
                    'nickname' => $runner->getNickname()
                ));
            } else {
                return $this->setOutput(array(
                    'success' => false,
                    'message' => 'The user specified is not allowed designer for this project!'
                ));
            }
        } else {
            return $this->setOutput(array(
                'success' => false,
                'message' => 'You are not allowed to do that!'
            ));
        }
    }

    protected function actionToggleFollowing() {
        $workitem_id = (int) $this->getRequest()->getParam('workitem');
        $user_id     = (int) $this->getRequest()->getParam('userid');

        $workitem = new WorkItem($workitem_id);

        $resp = $workitem->toggleUserFollowing($user_id);

        return $this->setOutput(array(
            'success' => true,
            'message' => 'Following Toggled' . $resp
        ));
    }
}
