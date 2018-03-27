<?php

class FileController extends JsonController {
    public function run($action, $param = '') {
        $method = '';
        switch($action) {
            case 'add':
            case 'scan':
            case 'remove':
            case 'listForJob':
                $method = $action;
                break;
            default:
                if (is_numeric($action)) {
                    $method = 'view';
                    $param = (int) $action;
                } else {
                    Utils::redirect('./');
                }
                break;
        }
        $params = preg_split('/\//', $param);
        call_user_func_array(array($this, $method), $params);
    }

    public function view($id) {

    }

    public function add($reference = '', $isW9 = false) {
        try {
            $user = User::find(Session::uid());
            if (!$user->getId()) {
                return $this->setOutput(array(
                    'success' => false,
                    'message' => 'Not enough rights!'
                ));
            }

            // Upload data can be POST'ed as raw form data or uploaded via <iframe> and <form>
            // using regular multipart/form-data enctype (which is handled by PHP $_FILES).
            if (!empty($_FILES['fd-file']) and is_uploaded_file($_FILES['fd-file']['tmp_name'])) {
                // Regular multipart/form-data upload.
                $name = $_FILES['fd-file']['name'];
                $source = fopen($_FILES['fd-file']['tmp_name'], 'r');
                $ext = end(explode(".", $name));
                $fileName = File::uniqueFilename($ext);
            } else {
                // Raw POST data.
                $name = urldecode(@$_SERVER['HTTP_X_FILE_NAME']);
                $source = fopen('php://input', 'r');
                $ext = end(explode(".", $name));
                $fileName = File::uniqueFilename($ext);
            }
            if (!is_dir(VIRUS_SCAN_DIR)) {
                mkdir(VIRUS_SCAN_DIR, 0750);
            }
            $path = VIRUS_SCAN_DIR . '/' . $fileName;

            $dest = fopen($path, 'w');
            while (!feof($source)) {
                $chunk = fread($source, 1024);
                fwrite($dest, $chunk);
            }
            fclose($source);
            fclose($dest);

            $finfo = new finfo(FILEINFO_MIME_TYPE);
            $mime = $finfo->file($path);

            $title = basename($name);
            $url = SERVER_URL . 'uploads/' . $fileName;
            $workitem = is_numeric($reference) ? (int) $reference : null;
            $projectid = null;
            if (is_null($workitem) && strlen(trim($reference))) {
                $project = new Project();
                if ($project->loadByName(trim($reference))) {
                    $projectid = $project->getProjectId();
                }
            }

            $file = new File();
            $file->setMime($mime)
                 ->setUserid($_SESSION['userid'])
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
                $journal_message =
                    '@' . $user->getNickname() . ' uploaded an [attachment](' .
                    $file->getUrl() . ') to #' . $workitem;
                Utils::systemNotification($journal_message);
            }

            $isW9 = (bool) $isW9;
            if ($isW9) {
                Notification::sendW9Request($user, $file->getUrl());
                $user->setW9_status('pending-approval');
                $user->save();
            }

            return $this->setOutput(array(
               'success'        => true,
               'fileid'         => $file->getId(),
               'url'            => $file->getUrl(),
               'icon'           => $icon,
               'title'          => $file->getTitle(),
               'description'    => '',
               'filetype'       => (isset($filetype) ? $filetype : ''),
               'can_delete'     => $isW9 ? false : true
            ));
        } catch (Exception $e) {
            error_log($e->getMessage());
            return $this->setOutput(array(
                'success' => false,
                'message' => 'An error occured while uploading to ' . $path . ' please try again!'
            ));
        }
    }

    public function scan($id) {
        $scanner = new ScanAssets();
        $file_id = (int) $id;

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
                File::s3Upload($file->getRealPath(), APP_ATTACHMENT_PATH . $file->getFileName(), true, $file->getTitle());
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

    public function remove($id) {
        try {
            $user = User::find(Session::uid());
            if (!$user->getId()) {
                throw new Exception('Not enough rights');
            }
            $file = new File();
            $file->findFileById($id);
            if ($file->getWorkitem()) {
                $workitem = WorkItem::getById($file->getWorkitem());
                $userInvolved =
                    $user->getId() == $file->getUserid() || $user->getId() == $workitem->getCreatorId() ||
                    $user->getId() == $workitem->getMechanicId() || $user->getId() == $workitem->getRunnerId();
            } else {
                $userInvolved = false;
            }
            if (!$user->isRunner() && !$user->isPayer() && !$userInvolved) {
                throw new Exception('Permission denied');
            }
            $success = $file->remove();
            return $this->setOutput(array(
                'success' => true,
                'message' => 'Attachment removed'
            ));
        } catch (Exception $e) {
            return $this->setOutput(array(
                'success' => false,
                'message' => $e->getMessage()
            ));
        }
    }

    public function listForJob($job_id) {
        try {
            $files = File::fetchAllFilesForWorkitem($job_id);
            $user = User::find(Session::uid());
            if (!$user->getId()) {
                throw new Exception('Not enough rights');
            }
            $job = WorkItem::getById($job_id);
            $data = array();
            foreach ($files as $file) {
                if (!File::isAllowed($file->getStatus(), $user) || !$file->getIs_scanned()) {
                    continue;
                }
                $fileUrl = $file->getUrl();
                $iconUrl = $file->getUrl();
                $userInvolved =
                    $user->getId() == $file->getUserid() || $user->getId() == $job->getCreatorId() ||
                    $user->getId() == $job->getMechanicId() || $user->getId() == $job->getRunnerId();
                $icon = File::getIconFromMime($file->getMime());
                $data[] = array(
                    'fileid'        => $file->getId(),
                    'url'           => $fileUrl,
                    'can_delete'    => $user->isRunner() || $user->isPayer() || $userInvolved,
                    'title'         => $file->getTitle(),
                    'description'   => $file->getDescription()
                );
            }
            return $this->setOutput(array(
                'success' => true,
                'data' => $data
            ));
        } catch (Exception $e) {
            return $this->setOutput(array(
                'success' => false,
                'message' => $e->getMessage()
            ));
        }
    }
}