<?php

class ProjectView extends View {
    public $layout = 'NewWorklist';
    public $title = 'Project: %s - Worklist';
    public $stylesheets = array('css/project.css');

    public $scripts = array(
        'js/jquery/jquery.tablednd_0_5.js',
        'js/jquery/jquery.template.js',
        'js/jquery/jquery.metadata.js',
        'js/jquery/jquery.jeditable.min.js',
        'js/jquery/jquery.tablesorter_desc.js',
        'js/ajaxupload/ajaxupload.js',
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
        $this->project_logo = ($project->getLogo() ? APP_IMAGE_URL . $project->getLogo() : 'images/emptyLogo.png');
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
        return $this->runnerOrOwner && $project->getTestFlightEnabled() && $project->getTestFlightTeamToken();
    }

    public function projectAvgBidFeeFormated() {
        return number_format($this->project->getAvgBidFee(), 2);
    }

    public function projectActiveJobs() {
        return $this->project->getActiveJobs();
    }

    public function projectActiveJobsCount() {
        return count($this->projectActiveJobs());
    }

    public function projectActiveJobsCountText() {
        $count = $this->projectActiveJobsCount();
        return $count . ' active job' .  ($count > 1 ? 's' : '');
    }
}
