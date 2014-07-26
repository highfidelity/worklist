<?php

class AddJobView extends View {
    public $title = 'Add job - Worklist';
    public $stylesheets = array(
        'css/addjob.css'
    );
    public $scripts = array(
        'js/filedrop/filedrop-min.js',
        'js/spin.js/spin.min.js',
        'js/addjob.js'
    );

    public function render() {
        $this->first_selected = empty($current);
        return parent::render();
    }

    public function userIsInternal() {
        $user = new User($this->currentUser['id']);

        return $user->isInternal();
    }

    public function projects() {
        $user = $this->currentUser;
        $current = 'hifi';
        $activeOnly = !($user['is_runner'] || $user['is_admin'] || $user['is_payer']);
        $projects = Project::getProjects($activeOnly);
        $ret = array();
        foreach ($projects as $project) {
            $ret[] = array(
                'id'        => $project['project_id'],
                'name'      => $project['name'],
                'current'   => ($project['name'] == $current) ? true : false
            );
        }
        return $ret;
    }

    public function skills() {
        $skill = new SkillModel();
        return $skill->loadAll();
    }

    public function internalUsers() {
        return User::getInternals();
    }
}
