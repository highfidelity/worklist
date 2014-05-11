<?php

class ReportsController extends Controller {
    public function run() {
        /* This page is only accessible to runners. */
        if (empty($_SESSION['is_runner']) && empty($_SESSION['is_payer']) && isset($_POST['paid'])) {
            $this->view = null;
            Utils::redirect("jobs");
            return;
        }

        if (!empty($_REQUEST['payee'])) {
            $payee = new User();
            $payee->findUserByNickname($_REQUEST['payee']);
            $_REQUEST['user'] = $payee->getId();
        }

        $showTab = 0;
        if (!empty($_REQUEST['view'])) {
            if ($_REQUEST['view'] == 'chart') {
                $showTab = 1;
            }
            if ($_REQUEST['view'] == 'payee') {
                $showTab = 2;
            }
        }
        $this->write('showTab', $showTab);

        $w2_only = 0;
        if (! empty($_REQUEST['w2_only'])) {
            if ($_REQUEST['w2_only'] == 1) {
                $w2_only = 1;
            }
        }
        $this->write('w2_only', $w2_only);

        $_REQUEST['name'] = '.reports';

        $this->write('activeUsers', isset($_REQUEST['activeUsers']) ? (int) $_REQUEST['activeUsers'] : 1);
        $this->write('activeRunners', isset($_REQUEST['activeRunners']) ? (int) $_REQUEST['activeRunners'] : 1);
        $this->write('activeProjects', isset($_REQUEST['activeProjects']) ? (int) $_REQUEST['activeProjects'] : true);

        $filter = new Agency_Worklist_Filter($_REQUEST, true);
        if (!$filter->getStart()) {
            $filter->setStart(date("m/d/Y",strtotime('-90 days', time())));
        }

        if (!$filter->getEnd()) {
            $filter->setEnd(date("m/d/Y",time()));
        }

        $this->write('filter', $filter);

        if(isset($_POST['paid']) && !empty($_POST['paidList']) && !empty($_SESSION['is_payer'])) {

            // we need to decide if we are dealing with a fee or bonus and call appropriate routine
            $fees_id = explode(',', trim($_POST['paidList'], ','));
            foreach($fees_id as $id) {
                $query = "SELECT `id`, `bonus` FROM `".FEES."` WHERE `id` = $id ";
                $result = mysql_query($query);
                $row = mysql_fetch_assoc($result);
                if($row['bonus']) {
                    bonus::markPaidById($id,$user_paid=0, $paid=1, true, $fund_id=false);
                } else {
                    Fee::markPaidById($id, $user_paid=0, $paid_notes='', $paid=1, true, $fund_id=false);
                }
            }
        }

        parent::run();
    }

}
