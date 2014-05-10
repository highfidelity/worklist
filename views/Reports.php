<?php

class ReportsView extends View {
    public $layout = 'NewWorklist';

    public $title = 'Reports - Worklist';

    public $stylesheets = array(
        'css/datepicker.css',
        'css/reports.css'
    );

    public $scripts = array(
        'js/jquery/jquery.tablednd_0_5.js',
        'js/jquery/jquery.metadata.js',
        'js/raphael/raphael-min.js',
        'js/timeline-chart.js',
        'js/reports.js'
    );

    public function render() {
        $this->activeUsers = $this->read('activeUsers');
        $this->activeRunners = $this->read('activeRunners');
        $this->activeProjects = $this->read('activeProjects');
        $this->w2_only = $this->read('w2_only');
        $this->showTab = $this->read('showTab');

        return parent::render();
    }

    public function userSelectBox() {
        $filter = $this->read('filter');
        $activeUsers = $this->read('activeUsers');
        return $filter->getUserSelectbox($activeUsers, 'ALL');
    }

    public function projectSelectBox() {
        $filter = $this->read('filter');
        $activeProjects = $this->read('activeProjects');
        return $filter->getProjectSelectbox($activeProjects, 'ALL');
    }

    public function statusSelectBox() {
        $filter = $this->read('filter');
        return $filter->getStatusSelectbox(true);
    }

    public function runnerSelectBox() {
        $filter = $this->read('filter');
        $activeRunners = $this->read('activeRunners');
        return $filter->getRunnerSelectbox($activeRunners, 'ALL');
    }

    public function fundSelectBox() {
        $filter = $this->read('filter');
        return $filter->getFundSelectbox(true);
    }

    public function filterPaidStatusAll() {
        $filter = $this->read('filter');
        return $filter->getPaidstatus() == 'ALL';
    }

    public function filterPaidStatusPaid() {
        $filter = $this->read('filter');
        return $filter->getPaidstatus() == '1';
    }

    public function filterPaidStatusUnpaid() {
        $filter = $this->read('filter');
        return $filter->getPaidstatus() == '0';
    }

    public function filterTypeAll() {
        $filter = $this->read('filter');
        return $filter->getType() == 'ALL';
    }

    public function filterTypeFee() {
        $filter = $this->read('filter');
        return $filter->getType() == 'Fee';
    }

    public function filterTypeBonus() {
        $filter = $this->read('filter');
        return $filter->getType() == 'Bonus';
    }

    public function filterTypeExpense() {
        $filter = $this->read('filter');
        return $filter->getType() == 'Expense';
    }

    public function filterOrderedByName() {
        $filter = $this->read('filter');
        return $filter->getOrder() == 'name';
    }

    public function filterOrderedByDate() {
        $filter = $this->read('filter');
        return $filter->getOrder() == 'date';
    }

    public function filterStartDate() {
        $filter = $this->read('filter');
        return $filter->getStart();
    }

    public function filterEndDate() {
        $filter = $this->read('filter');
        return $filter->getEnd();
    }

    public function filterDirAsc() {
        $filter = $this->read('filter');
        return $filter->getDir() == 'ASC' ? 'true' : 'false';
    }
}