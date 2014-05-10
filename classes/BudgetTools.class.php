<?php

class BudgetTools {
    function exportCSV($data) {
        // Create with headers
        $csv = "Worklist ID,Budget,Summary,Who,Amount,Status,Created,Paid\n";

        foreach ($data as $item) {
            $csv .= $item->id.",";
            $csv .= $item->budget_id.",";
            $csv .= str_replace(",", "", $item->summary).",";
            $csv .= str_replace(",", "", $item->who).",";
            $csv .= $item->amount.",";
            $csv .= $item->status.",";
            $csv .= $item->created.",";
            $csv .= $item->paid."\n";
        }

        // Output headers to force download
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="Report.csv"');
        echo $csv;
    }

    function exportCSV_Transferred($data) {
        // Create with headers
        $csv = "Budget ID, Budget, Notes, Who, Amount, Created\n";

        foreach ($data as $item) {
            $csv .= $item->id . ",";
            $csv .= str_replace(",", "", $item->budget_title) . ",";
            $csv .= str_replace(",", "", $item->notes) . ",";
            $csv .= str_replace(",", "", $item->who) . ",";
            $csv .= $item->amount . ",";
            $csv .= $item->created . "\n";
        }

        // Output headers to force download
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="Report.csv"');
        echo $csv;
    }

    function getAllocated($budget_id = 0, $sort = NULL, $desc = NULL) {
        if ($budget_id > 0) {
            $filter = " `budget_id`={$budget_id} AND ";
        } else {
            $filter = " `runner_id`='{$_SESSION['userid']}' AND ";
        }
        $sql = "SELECT w.`id`, w.`budget_id`, w.`summary`, w.`status`, b.`reason` " .
               " FROM " . WORKLIST . " w " .
               " LEFT JOIN " . BUDGETS . " b ON w.budget_id = b.id ".
               " WHERE {$filter} `status` IN ('functional', 'svnhold', 'working', 'review', 'completed')";

        $sql_q = mysql_query($sql) or die(mysql_error());
        $items = array();

        while ($row = mysql_fetch_assoc($sql_q)) {
            // Get fees
            $fees = self::getFees($row['id']);

            // Get people working there
            $who = self::getWho($row['id']);
            $ids = self::getWho($row['id'], true);

            // Get Date of Working
            $created = self::getDateWorking($row['id']);

            // Get payment status
            $paid = self::getPaymentStatus($row['id']);

            if ($paid['paid'] == 1) {
                // Get paid date
                $paid_date = self::getPaidDate($row['id']);
                if (!$paid_date['paid_date']) {
                    $paid_date['paid_date'] = "No Data";
                }
            } else {
                $paid_date = array('paid_date'=>"Not Paid");
            }

                $items[] = array('id'=>$row['id'], 'budget_id'=>$row['budget_id'], 'budget_title'=>$row['reason'],
                                'summary'=>$row['summary'], 'who'=>$who, 'ids'=>$ids, 'amount'=>$fees['amount'],
                                'status'=>$row['status'], 'created'=>$created['date'], 'paid'=>$paid_date['paid_date']);
        }
        // If required sort the result
        if ($sort && $desc) {
            return json_encode(self::sortItems($items, $desc, $sort));
        } else {
            return json_encode($items);
        }
    }

    function getSubmitted($budget_id = 0, $sort = NULL, $desc = NULL) {
        if ($budget_id > 0) {
            $filter = " `budget_id`={$budget_id} AND ";
        } else {
            $filter = " `runner_id`='{$_SESSION['userid']}' AND ";
        }
        $sql = "SELECT w.`id`, w.`budget_id`, w.`summary`, w.`status`, b.`reason` ".
               " FROM " . WORKLIST . " w " .
               " LEFT JOIN " . BUDGETS . " b ON w.budget_id = b.id".
               " WHERE {$filter} `status`='done'";

        $sql_q = mysql_query($sql) or die(mysql_error());
        $items = array();

        while ($row = mysql_fetch_assoc($sql_q)) {
            // Get fees
            $fees = self::getFees($row['id']);

            // Get people working there
            $who = self::getWho($row['id']);
            $ids = self::getWho($row['id'], true);

            // Get Date of Working
            $created = self::getDateWorking($row['id']);

            // Get payment status
            $paid = self::getPaymentStatus($row['id']);

            if ($paid['paid'] != 1) {
                if ($fees['amount'] > 0) {
                    $paid_date = array('paid_date'=>"Not Paid");
                    $items[] = array('id'=>$row['id'], 'budget_id'=>$row['budget_id'], 'budget_title'=>$row['reason'],
                                    'summary'=>$row['summary'], 'who'=>$who, 'ids'=>$ids, 'amount'=>$fees['amount'],
                                    'status'=>$row['status'], 'created'=>$created['date'], 'paid'=>$paid_date['paid_date']);
                }
            }
        }
        // If required sort the result
        if ($sort && $desc) {
            return json_encode(self::sortItems($items, $desc, $sort));
        } else {
            return json_encode($items);
        }
    }

    function getPaid($budget_id = 0, $sort = NULL, $desc = NULL) {
        if ($budget_id > 0) {
            $filter = " `budget_id`={$budget_id} AND ";
        } else {
            $filter = " `runner_id`='{$_SESSION['userid']}' AND ";
        }
        $sql = "SELECT w.`id`, w.`budget_id`, w.`summary`, w.`status`, b.`reason` ".
               " FROM " . WORKLIST . "  w " .
               " LEFT JOIN " . BUDGETS . " b ON w.budget_id = b.id".
               " WHERE  {$filter} `status`='done'";

        $sql_q = mysql_query($sql) or die(mysql_error());
        $items = array();

        while ($row = mysql_fetch_assoc($sql_q)) {
            // Get fees
            $fees = self::getFees($row['id']);

            // Get people working there
            $who = self::getWho($row['id']);
            $ids = self::getWho($row['id'], true);

            // Get Date of Working
            $created = self::getDateWorking($row['id']);

            // Get payment status
            $paid = self::getPaymentStatus($row['id']);

            if ($paid['paid'] == 1) {
                // Get Paid date
                $paid_date = self::getPaidDate($row['id']);
                if (!$paid_date['paid_date']) {
                    $paid_date['paid_date'] = "No Data";
                }

                $items[] = array('id'=>$row['id'], 'budget_id'=>$row['budget_id'], 'budget_title'=>$row['reason'],
                                'summary'=>$row['summary'], 'who'=>$who, 'ids'=>$ids, 'amount'=>$fees['amount'],
                                'status'=>$row['status'], 'created'=>$created['date'], 'paid'=>$paid_date['paid_date']);
            }
        }
        // If required sort the result
        if ($sort && $desc) {
            return json_encode(self::sortItems($items, $desc, $sort));
        } else {
            return json_encode($items);
        }
    }

    function getTransferred($budget_id = 0, $sort = NULL, $desc = NULL) {
        $order = "";
        if ($sort === NULL) {
            $order = "id DESC";
        } else {
            if ($desc == 'true') {
                $order = "{$sort} DESC";
            } else {
                $order = "{$sort} ASC";
            }
        }

        $sql = "SELECT b.`id`, b.`reason` AS budget_title, b.`notes`, b.`transfer_date` AS created, bs.`amount_granted` AS amount, u.nickname AS who, b.receiver_id " .
               " FROM " . BUDGETS . " b " .
               " INNER JOIN " . USERS . " u ON b.receiver_id = u.id " .
               " INNER JOIN " . BUDGET_SOURCE . " bs ON b.id = bs.budget_id " .
               " WHERE bs.`source_budget_id`={$budget_id} " .
               " ORDER BY {$order} " ;

        $sql_q = mysql_query($sql) or die(mysql_error());
        $items = array();
        while ($row = mysql_fetch_assoc($sql_q)) {
            $items[] = array('id'=>$row['id'], 'budget_title'=>$row['budget_title'],
                             'notes'=>$row['notes'], 'who'=>$row['who'],
                             'receiver_id'=>$row['receiver_id'], 'amount'=>$row['amount'],
                             'created'=> substr($row['created'], 0, 10));
        }
        return json_encode($items);
    }

    function getFees($id) {
        $sql = "SELECT SUM(".FEES.".`amount`) AS `amount` ".
               "FROM ".FEES.
               " WHERE ".FEES.".`worklist_id`={$id} AND ".FEES.".`withdrawn`!=1";
        $sql_q = mysql_query($sql) or die(mysql_error());
        return mysql_fetch_array($sql_q);
    }

    function getWho($id, $getIds=false) {
        $who = "";
        $sql = "SELECT ".USERS.".`id`,`nickname` FROM ".FEES.
               " LEFT JOIN ".USERS." ON ".USERS.".`id`=".FEES.".`user_id`".
               " WHERE ".FEES.".`worklist_id` = {$id} AND ".FEES.".`withdrawn`=0 GROUP BY `nickname`";

        $sql_q = mysql_query($sql) or die(mysql_error());

        if ($getIds) {
            $ids = array();
            while($row = mysql_fetch_assoc($sql_q)) {
                $ids[] = $row['id'];
            }
            return $ids;
        } else {
           while($row = mysql_fetch_assoc($sql_q)) {
               $who .= $row['nickname'].", ";
           }
           return substr($who, 0, -2);
        }
    }

    function getDateWorking($id) {
        $sql = "SELECT DATE_FORMAT(".FEES.".`date`, '%m/%d/%Y') as `date` ".
               "FROM ".FEES." LEFT JOIN ".WORKLIST." ON ".WORKLIST.".`id`=".FEES.".`worklist_id` ".
               "WHERE ".FEES.".`user_id`=".WORKLIST.".`mechanic_id` AND ".WORKLIST.".`id`={$id} LIMIT 1";
        $sql_q = mysql_query($sql) or die(mysql_error());

        $data = mysql_fetch_array($sql_q);
        if ($data['date']) {
           return $data;
        } else {
           $data['date'] = "No Data";
           return $data;
        }
    }

    function getPaymentStatus($id) {
        $sql = "SELECT ".FEES.".`paid` ".
               "FROM ".FEES." LEFT JOIN ".WORKLIST." ON ".WORKLIST.".`id`=".FEES.".`worklist_id` ".
               "WHERE ".FEES.".`worklist_id` = {$id} AND `withdrawn`!=1 AND `amount`>0 GROUP BY ".FEES.".`paid`";
        $sql_q = mysql_query($sql) or die(mysql_error());
        return mysql_fetch_array($sql_q);
    }

    function getPaidDate($id) {
        $sql = "SELECT DATE_FORMAT(".FEES.".`paid_date`, '%m/%d/%Y') as `paid_date` ".
               "FROM ".FEES." LEFT JOIN ".WORKLIST." ON ".WORKLIST.".`id`=".FEES.".`worklist_id` ".
               "WHERE ".FEES.".`worklist_id` = {$id} GROUP BY ".FEES.".`paid_date`";
        $sql_q = mysql_query($sql) or die(mysql_error());
        return mysql_fetch_array($sql_q);
    }

    function sortItems($items, $desc, $sort) {
        $order = SORT_ASC;
        if ($desc) {
            $order = SORT_DESC;
        }
        foreach($items as $key => $key_row) {
            $sortby_idx[$key] = $key_row[$sort];
        }
        $type = SORT_STRING;
        if ($sort == 'id' || $sort == 'amount' || $sort == 'created' || $sort == 'paid') {
            $type = SORT_NUMERIC;
        }
        array_multisort($sortby_idx, $order, $type, $items);
        return $items;
    }
}
