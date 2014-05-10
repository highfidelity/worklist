<?php
//  vim:ts=4:et

//  Copyright (c) 2010, LoveMachine Inc.
//  All Rights Reserved.
//  http://www.lovemachineinc.com

class Fee
{
    public static function markPaidByList($fee_ids, $user_paid = 0, $paid_notes = '', $paid = 1, $fund_id = false) {

        $summaryData = array();
        foreach ($fee_ids as $fee_id) {

            $summary = self::markPaidById($fee_id, $user_paid, $paid_notes, $paid, true, $fund_id);

            if ($summary[0] != 0) {
                if (isset($summaryData[$summary[0]])) {
                    $summaryData[$summary[0]][0] += $summary[1];
                    $summaryData[$summary[0]][1] += $summary[2];
                } else {
                    $summaryData[$summary[0]][0] = $summary[1];
                    $summaryData[$summary[0]][1] = $summary[2];
                }
            }
        }

        return $summaryData;
    }

    public static function markPaidById($fee_id, $user_paid = 0, $paid_notes = '', $paid = 1, $summary = false, $fund_id = false) {
        $fee_id = (int) $fee_id;
        $user_paid = (int) $user_paid;
        $user_paid = $user_paid == 0 ? $_SESSION['userid'] : $user_paid;
        $paid_notes = mysql_real_escape_string($paid_notes);
        $paid = (int) $paid;
        $update_fund_id = "";
        //If no fund passed, do not update fund_id in fee or update budget. (alternate version. bail with failure if fund_id is required
        if ($fund_id) { $update_fund_id = " , `fund_id` = " . (int) $fund_id; }

        $user_id = 0;
        $amount = 0;
        $points = 0;

        //Wired REWARDER out of process while API is being rebuilt (and we are using a different process for determining rewarder now)
        $query = "SELECT `user_id`, `worklist_id`, `amount`, `paid`, `expense`, '0' as `rewarder` FROM `".FEES."` WHERE `id`=$fee_id AND `bonus` = 0";
        $rt = mysql_query($query) or error_log("failed to select fees: $query : " . mysql_error());


        if ($rt && ($row = mysql_fetch_assoc($rt))) {
            $query = "
                UPDATE
                    `".FEES."`
                SET
                    `user_paid` = {$user_paid},
                    `notes` = '{$paid_notes}',
                    `paid` = {$paid},
                    `paid_date` = NOW()
                    {$update_fund_id}
                WHERE `id` = {$fee_id}";
            $rt = mysql_query($query) or error_log("failed to mark fee paid: $query : ".mysql_error());

            /* Add rewarder points and log */
            if ($rt) {
                /* Don't do update reward point or budget:
                 *  1) for expenses,
                 *  2) for rewarder payments,
                 *  3) there is no real change.
                 */
                if (!$row['expense'] && !$row['rewarder'] && $paid != $row['paid']) {
                    $user_id = $row['user_id'];
                    $worklist_id = $row['worklist_id'];
                    $amount = $row['amount'];

                    /* Find the runner for this task so we can adjust their budget. */
                    $query = "SELECT `runner_id` FROM `".WORKLIST."` WHERE `id`=$worklist_id";
                    $rt = mysql_query($query) or error_log("Unable to select Runner: $query : " . msyql_query());
                    if ($rt && ($row = mysql_fetch_assoc($rt))) {
                        $runner_id = $row['runner_id'];
                    } else {
                        $runner_id = 0;
                    }

                    /* If we're unmarking the fee paid, deduct the points. */
//                    if ($paid == 0) {
//                        $amount = $amount * -1;
//                    }

                    $points = intval($amount);

                    addRewarderBalance($user_id, $amount, $worklist_id, $fee_id);

//                    if ($runner_id != 0) {
//                        mysql_unbuffered_query("UPDATE `".USERS."` SET `budget`=`budget`-$amount WHERE `id`=$runner_id");
//                    }

		            //  Auto populate rewarder with team members of this task
		             PopulateRewarderTeam($user_id, $worklist_id);
                    // do it with new Rewarder API
                }
            } else {
                return false;
            }
        }

        if ($summary) {
            return array($user_id, $amount, $points);
        } else {
            return !empty($rt);
        }
    }

    public static function getSums($type = '') {
        $sum = array();
        $sum["error"] = 0;

        if ($type == "weekly") {
            if (!empty($_SESSION['userid'])) {
                $result = mysql_query("SELECT amount, worklist_id AS task
                                       FROM `".FEES."`
                                       WHERE `user_id` = {$_SESSION['userid']} AND
                                       `worklist_id` IN (SELECT `id` FROM `".WORKLIST."` WHERE `status` = 'Done') AND
                                       YEAR(DATE) = YEAR(NOW()) AND
                                       WEEK(`date`) = WEEK(NOW()) AND
                                       withdrawn != 1") or exit (mysql_error());
                $output = '
                <table class="table-bids">
                  <thead>
                    <tr class="table-hdng">
                      <td>Task ID</td>
                      <td>Fee</td>
                    </tr>
                  </thead>
                  <tbody>
                ';
                $c = 0;
                while($row = mysql_fetch_object($result)) {
                    $c++;
                    $output .= '
                    <tr>
                      <td><a href="./'.$row->task.'">'.$row->task.'</a></td>
                      <td style="color: #6F6F6F; font-weight:bold;">$'.$row->amount.'</td>
                    </tr>
                    ';
                }
                if($c == 0){
                    $output .= '
                    <tr>
                      <td colspan="2">There is nothing to show.</td>
                    </tr>
                    ';
                }
                $output .= '
                  </tbody>
                </table>
                ';
                $sum['output'] = $output;
            } else {
                $sum["error"] = 1;
            }
        } else if ($type == "monthly") {
            if (!empty($_SESSION['userid'])) {
                $result = mysql_query("SELECT amount, worklist_id as task
                                       FROM `".FEES."`
                                       WHERE `user_id` = {$_SESSION['userid']} AND
                                       `worklist_id` IN (SELECT `id` FROM `".WORKLIST."` WHERE `status` = 'Done') AND
                                       YEAR(DATE) = YEAR(NOW()) AND
                                       MONTH(`date`) = MONTH(NOW()) AND
                                       withdrawn != 1") or exit (mysql_error());
                $output = '
                <table class="table-bids">
                  <thead>
                    <tr class="table-hdng">
                      <td>Task ID</td>
                      <td>Fee</td>
                    </tr>
                  </thead>
                  <tbody>
                ';
                $c = 0;
                while($row = mysql_fetch_object($result)) {
                    $c++;
                    $output .= '
                    <tr>
                      <td><a href="./'.$row->task.'">'.$row->task.'</a></td>
                      <td style="color: #6F6F6F; font-weight:bold;">$'.$row->amount.'</td>
                    </tr>
                    ';
                }
                if($c == 0){
                    $output .= '
                    <tr>
                      <td colspan="2">There is nothing to show.</td>
                    </tr>
                    ';
                }
                $output .= '
                  </tbody>
                </table>
                ';
                $sum['output'] = $output;
            } else {
                $sum["error"] = 1;
            }
        } else {
            if (!empty($_SESSION['userid'])) {
                $r = mysql_query ("SELECT SUM(`amount`) AS `sum_amount` FROM `".FEES."` WHERE `user_id` = {$_SESSION['userid']} AND
                                  `worklist_id` IN (SELECT `id` FROM `".WORKLIST."` WHERE `status` = 'Done') AND YEAR(DATE) = YEAR(NOW()) AND
                                   MONTH(`date`) = MONTH(NOW()) AND withdrawn != 1;") or exit (mysql_error());
                $sum['month'] = mysql_fetch_object($r)->sum_amount;
                if (is_numeric($sum['month'])) {
                    $sum['month'] = number_format($sum['month']);
                } else {
                    $sum['month'] = '0';
                }

                $r = mysql_query ("SELECT SUM(`amount`) AS `sum_amount` FROM `".FEES."` WHERE `user_id` = {$_SESSION['userid']} AND
                                  `worklist_id` IN (SELECT `id` FROM `".WORKLIST."` WHERE `status` = 'Done') AND YEAR(DATE) = YEAR(NOW()) AND
                                   WEEK(`date`) = WEEK(NOW()) AND withdrawn != 1;") or exit (mysql_error());
                $sum['week'] = mysql_fetch_object($r)->sum_amount;
                if (is_numeric($sum['week'])) {
                    $sum['week'] = number_format($sum['week']);
                } else {
                    $sum['week'] = '0';
                }
            } else {
                $sum['month'] = '0';
                $sum['week'] = '0';
            }
        }
        return $sum;
    }

}
