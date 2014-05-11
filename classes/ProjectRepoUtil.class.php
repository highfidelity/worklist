<?php
// Class to work with sandbox
//
//  vim:ts=4:et
class ProjectRepoUtil {

    public function __construct() {
        if (!mysql_connect(DB_SERVER, DB_USER, DB_PASSWORD)) {
            throw new Exception('Error: ' . mysql_error());
        }
        if (!mysql_select_db(DB_NAME)) {
            throw new Exception('Error: ' . mysql_error());
        }
    }

    public function createRepo($repository, $project) {
        $this->validateRepoName($repository);
        if ($this->ensureNonExistentRepo($repository)) {
            $command = "/usr/local/bin/wl-create-project-repo " . $repository;
            $result = 0;
            $output = array();
            error_log("command: ".$command);
            exec($command, $output, $result);
            error_log("output: " . print_r($output, true));
            error_log("result: " . print_r($result, true));
            $this->notifyJournal($repository, $project);
        }
    }

    /**
    * Check if Repository Name is valid
    *
    */
    private function validateRepoName($repository) {
        if (!preg_match('/^[A-Za-z0-9_-]+$/', $repository)) {
            throw new Exception('Invalid repository name');
        }
    }

    /**
     * Returns true if the supplied $repo_name exists
     *
    */
    public function ensureNonExistentRepo($repository) {
        $project = new Project();
        if (!empty($repository) && $project->getIdFromRepo($repository)) {
            throw new Exception("Project repository already exists!");
        }
        return true;
    }

    /**
    * Add a notification to the Journal
    *
    */
    private function notifyJournal($repository, $project) {
        $journal_message = "Repository created for #" . $project . " (" . $repository . ")";

        //sending journal notification
        sendJournalNotification($journal_message);
    }

}
