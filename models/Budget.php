<?php
/**
 * Worklist
 * Copyright (c) 2011 LoveMachine, LLc.
 * All rights reserved.
 */
if (!defined('BUDGETS'))   define('BUDGETS', 'budgets');

class Budget extends DataObject {
    public $id;
    public $giver_id;
    public $receiver_id;
    public $amount;
    public $remaining;
    public $original_amount;
    public $reason;
    public $notes;
    public $transfer_date;
    public $source_data;
    public $active;
    public $seed;

    public $table_name;

    /**
     * Constructor
     */
    public function __construct() {
        parent::__construct();

        $this->table_name = BUDGETS;
    }

    /**
     * Destructor
     */
    public function __destructor() {
        parent::__destruct();
    }


    /**
     * Load a review by id
     */
    public function loadById($budget_id) {
        $objectData = $this->dbFetchArray(" `id`={$budget_id} ");
        return $this->loadObject($objectData);
    }

    /**
     * Get an index for review
     */
    public function getIndex($reviewee_id) {
        $objectData = $this->dbFetchArray(" `id`={$budget_id} ");

        if (!$objectData && is_array($objectData)) {
            return null;
        }

        return $objectData;
    }

    public function updateSources($source_id, $amount) {
        $sql = "UPDATE " . BUDGET_SOURCE . " SET original_amount = amount_granted, amount_granted = amount_granted + {$amount}
            WHERE id = {$source_id};";
        $result = mysql_query($sql);
        return $result;
    }

    public function loadSources($orderBy = " ORDER BY s.transfer_date ") {
        $sql = "SELECT b.reason,
            b.id AS budget_id,
            s.id AS source_id,
            s.source_data,
            s.amount_granted,
            DATE_FORMAT(s.transfer_date, '%Y-%m-%d') AS transfer_date,
            u.nickname,
            s.giver_id
            FROM " . BUDGET_SOURCE . " AS s
            LEFT JOIN " . $this->table_name . " AS b ON s.source_budget_id = b.id
            INNER JOIN " . USERS . " AS u ON u.id = s.giver_id
            WHERE s.budget_id = " . $this->id . "
            {$orderBy} ";

        $objectData = array();
        if ($result = $this->link->query($sql)){
            while ($row = $result->fetch_assoc()) {
                $objectData[] = $row;
            }
            $result->close();
        } else {
            $objectData = null;
        }
        return $objectData;
    }

    public function getAllocatedFunds() {
        $allocatedFunds = -1;
        $sql = 'SELECT SUM(`' . FEES . '`.`amount`) AS `allocated` FROM `' . FEES . '`, `' . WORKLIST . '` WHERE `' .
                WORKLIST . '`.`budget_id` = ' . $this->id . ' AND `' . FEES . '`.`worklist_id` = `' .
                WORKLIST . '`.`id` AND `' . WORKLIST . '`.`status` IN ("Working", "SvnHold", "Functional", "Review", "Completed") AND `' .
                FEES . '`.`withdrawn` != 1;';
        $result = mysql_query($sql);
        if ($result && (mysql_num_rows($result) == 1)) {
            $row = mysql_fetch_assoc($result);
            $allocatedFunds = $row['allocated'];
        }
        return $allocatedFunds;
    }

    public function getSubmittedFunds() {
        $submittedFunds = -1;
        $sql = 'SELECT SUM(`' . FEES . '`.`amount`) AS `submitted` FROM `' . FEES . '`, `' . WORKLIST . '` WHERE `' .
                WORKLIST . '`.`budget_id` = ' . $this->id . ' AND `' . FEES . '`.`worklist_id` = `' . WORKLIST .
                '`.`id` AND `' . WORKLIST . '`.`status` IN ("Done") AND `' . FEES . '`.`paid` = 0 AND `' . FEES . '`.`withdrawn` != 1;';
        $result = mysql_query($sql);
        if ($result && (mysql_num_rows($result) == 1)) {
            $row = mysql_fetch_assoc($result);
            $submittedFunds = $row['submitted'];
        }
        $sql = 'SELECT SUM(`' . FEES . '`.`amount`) AS `submitted` FROM `' . FEES . '` WHERE `' .
                FEES . '`.`budget_id` = ' . $this->id . ' AND `' . FEES . '`.`worklist_id` = 0 AND `' .
                FEES . '`.`paid` = 0 AND `' . FEES . '`.`withdrawn` != 1;';
        $result = mysql_query($sql);
        if ($result && (mysql_num_rows($result) == 1)) {
            $row = mysql_fetch_assoc($result);
            $submittedFunds = $submittedFunds + $row['submitted'];
        }
        return $submittedFunds;
    }

    public function getPaidFunds() {
        $paidFunds = -1;
        $sql = 'SELECT SUM(`' . FEES . '`.`amount`) AS `paid` FROM `' . FEES . '`, `' . WORKLIST . '` WHERE `' .
                WORKLIST . '`.`budget_id` = ' . $this->id . ' AND `' . FEES . '`.`worklist_id` = `' . WORKLIST .
                '`.`id` AND `' . WORKLIST . '`.`status` IN ("Done") AND `' . FEES . '`.`paid` = 1 AND `' . FEES . '`.`withdrawn` != 1;';
        $result = mysql_query($sql);
        if ($result && (mysql_num_rows($result) == 1)) {
            $row = mysql_fetch_assoc($result);
            $paidFunds = $row['paid'];
        }
        $sql = 'SELECT SUM(`' . FEES . '`.`amount`) AS `paid` FROM `' . FEES . '` WHERE `' .
                FEES . '`.`budget_id` = ' . $this->id . ' AND `' . FEES . '`.`worklist_id` = 0 AND `' .
                FEES . '`.`paid` = 1 AND `' . FEES . '`.`withdrawn` != 1;';
        $result = mysql_query($sql);
        if ($result && (mysql_num_rows($result) == 1)) {
            $row = mysql_fetch_assoc($result);
            $paidFunds = $paidFunds + $row['paid'];
        }
        return $paidFunds;
    }

    public function getTransferedFunds() {
        $transferedFunds = -1;
        $sql = 'SELECT SUM(s.`amount_granted`) AS `transfered` FROM `' . BUDGET_SOURCE . '` AS s ' .
            " WHERE s.source_budget_id = " . $this->id ;
        $result = mysql_query($sql);
        if ($result && (mysql_num_rows($result) == 1)) {
            $row = mysql_fetch_assoc($result);
            $transferedFunds = $row['transfered'];
        }
        return $transferedFunds;
    }

    public function getRemainingFunds() {
        $allocated = $this->getAllocatedFunds();
        $submitted = $this->getSubmittedFunds();
        $paid = $this->getPaidFunds();
        $transfered = $this->getTransferedFunds();
        //$transfered = 0;
        $remaining = $this->amount - $allocated - $submitted - $paid - $transfered;
        return $remaining;
    }
    /**
    Return the sum of budget amounts that are not already closed for all the children of a specific budget
    **/
    public function getChildrenNotClosed($budget_id) {
        $query = "SELECT SUM(b.`amount`) FROM `" . BUDGETS . " AS b " .
                "INNER JOIN " . BUDGET_SOURCE . " AS s ON s.budget_id = b.id AND s.source_budget_id = " . $budget_id ;
                "` WHERE active = 1 ";
        $result_query = mysql_query($query);
        $row = $result_query ? mysql_fetch_row($result_query) : null;
        return !empty($row) ? $row[0] : null;
    }

    public function recalculateBudgetRemaining() {
        $this->remaining = $this->getRemainingFunds();
        return $this->save("id");
    }

    public function insertNew($values) {
        return $this->dbInsert($values);
    }
}
?>
