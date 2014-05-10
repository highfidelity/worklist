<?php

class ProjectStatusView extends View {
	public $title = '';

	public $stylesheets = array(
		'css/legacy/worklist.css',
		'css/legacy/workitem.css',
		'css/review.css',
		'css/favorites.css',
		'css/userinfo.css',
		'css/budget.css'
	);

	public $scripts = array(
		'js/jquery/jquery.template.js',
		'js/jquery/jquery.jeditable.min.js',
		'js/jquery/jquery.tallest.js',
		'js/jquery/jquery.metadata.js',
		'js/jquery/jquery.blockUI.js',
		'js/ajaxupload/ajaxupload.js',
		'js/datepicker.js',
		'js/timepicker.js',
		'js/worklist.js',
		'js/workitem.js',
		'js/review.js',
		'js/favorites.js',
		'js/projects.js',
		'js/github.js',
		'js/projectstatus.js'
	);

	public function render() {
        $this->projectName = $this->read('projectName');
        $this->project = $this->read('project');
        $this->username = $this->read('username');
        $this->nickname = $this->read('nickname');
        $this->unixname = $this->read('unixname');
        $this->newUser = $this->read('newUser');
        $this->db_user = $this->read('db_user');
        $this->template = $this->read('templateEmail');
        $this->isGitHubConnected = $this->read('isGitHubConnected');
        $this->errorOut = $this->read('errorOut');
        return parent::render();
	}
}
