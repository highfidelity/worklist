<?php
//  vim:ts=4:et
//  Copyright (c) 2011, LoveMachine Inc.
//  All Rights Reserved.
//  http://www.lovemachineinc.com

class ProjectStatusController extends Controller {
    public function run() {
        $project =  isset($_REQUEST['project']) ? $_REQUEST['project'] : null;
        $errorOut = false;
        $userId = getSessionUserId();
        if (!$userId) {
            $errorOut = "You must be logged in to access this page";
        }
        $user = new User();
        $user->findUserById($userId);
        $nickname = $user->getNickname();
        $username = $user->getUsername();
        $unixname = $user->getUnixusername() ? $user->getUnixusername() : $nickname;
        $db_user = (strlen($project) > 9) ? substr($project, 0, 9) . date('w') . date('s') : $project;
        $userHasSandbox = $user->getHas_sandbox();
        if ($userHasSandbox) {
            $newUser = false;
            $templateEmail = "project-created-existingsb";
        } else {
            $newUser = true;
            $templateEmail = "project-created-newsb";
        }
        if (empty($project) || empty($username) || empty($nickname) || empty($unixname)) {
            $errorOut = "Not all information required to create the project is available.";
        }

        $projectObj = new Project();
        $projectObj->loadByName($project);
        $isGithubProject = $projectObj->getRepo_type() == 'git' ? true : false;
        $isGitHubConnected = $user->isGithub_connected($projectObj->getGithubId());

        if ($isGithubProject) {
            $templateEmail = "project-created-github";
        }

        $this->write('projectName', $project);
        $this->write('project', $projectObj);
        $this->write('username', $username);
        $this->write('nickname', $nickname);
        $this->write('unixname',  $unixname);
        $this->write('newUser',  $newUser);
        $this->write('db_user', $db_user);
        $this->write('templateEmail', $templateEmail);
        $this->write('isGitHubConnected', $isGitHubConnected);
        $this->write('errorOut', $errorOut);
        parent::run();
    }
}

?>
