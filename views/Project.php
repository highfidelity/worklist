<?php

class ProjectView extends View {
    public $title = 'Project: %s - Worklist';
    public $stylesheets = array('css/project.css');

    public $scripts = array(
        'js/jquery/jquery.tablednd_0_5.js',
        'js/jquery/jquery.template.js',
        'js/jquery/jquery.metadata.js',
        'js/jquery/jquery.jeditable.min.js',
        'js/jquery/jquery.tablesorter_desc.js',
        'js/filedrop/filedrop-min.js',
        'js/spin.js/spin.min.js',
        'js/paginator.js',
        'js/timepicker.js',
        'js/paginator.js',
        'js/project.js'
    );

    public function render() {
        $project = $this->project = $this->read('project');
        $this->title = sprintf($this->title, $project->getName());
        $project = $this->project = $this->read('project');
        $this->project_user = $this->read('project_user');
        $this->project_id = $project->getProjectId();
        $this->project_logo = ($project->getLogo() ? 'uploads/' . $project->getLogo() : 'images/emptyLogo.png');
        $this->edit_mode = $this->read('edit_mode');
        $this->is_owner = $this->read('is_owner');
        return parent::render();
    }

    public function runnerOrOwner() {
        return $this->currentUser['is_runner'] 
            || $this->read('is_owner');
    }

    public function runnerOrPayerOrOwner() {
        return $this->currentUser['is_runner'] 
            || $this->currentUser['is_payer'] 
            || $this->read('is_owner');
    }

    public function adminOrOwner() {
        return $this->currentUser['is_admin'] 
            || $this->read('is_owner');
    }

    public function projectDescription () {
        return replaceEncodedNewLinesWithBr(linkify($this->project->getDescription()));
    }

    public function projectShortDescription () {
        return replaceEncodedNewLinesWithBr(linkify($this->project->getShortDescription()));
    }

    public function projectTotalFees() {
        return $this->project->getTotalFees($this->project->getProjectId());
    }

    public function projectRoles() {
        return $this->project->getRoles($this->project->getProjectId());
    }

    public function projectHipChatColorSelect() {
        $project = $this->project;
        $ret = '';
        foreach ($project->getHipchatColorsArray() as $color) {
            $selected = '';
            if ($project->getHipchatColor() == $color) {
                $selected = 'checked="checked"';
            }
            $ret .= 
                '<div><label>' .
                '<input name="hipchat_color" type="radio" ' . $selected . ' value="' . $color . '" />' .
                  $color .
                '</label></div>';
        }
        return $ret;
    }

    public function showTestFlightButton() {
        $project = $this->project;
        return $this->runnerOrOwner() && $project->getTestFlightEnabled() && $project->getTestFlightTeamToken();
    }

    public function projectAvgBidFeeFormated() {
        return number_format($this->project->getAvgBidFee(), 2);
    }

    public function projectActiveJobs() {
        return $this->project->getActiveJobs(!$this->currentUser['is_internal']);
    }

    public function projectActiveJobsCount() {
        return count($this->projectActiveJobs());
    }

    public function projectActiveJobsCountText() {
        $count = $this->projectActiveJobsCount();
        return $count . ' active job' .  ($count > 1 ? 's' : '');
    }

    public function codeReviewAdmin() {
        $user = User::find($this->currentUser['id']);
        $crAdmins = preg_split('/,/', CODE_REVIEW_ADMINS);
        return $this->adminOrOwner() || in_array($user->getNickname(), $crAdmins);
    }
}
