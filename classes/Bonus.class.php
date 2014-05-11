<?php
//  vim:ts=4:et

//  Copyright (c) 2010, LoveMachine Inc.
//  All Rights Reserved.
//  http://www.lovemachineinc.com

class Bonus
{
    public static function markPaidByList($ids, $user_paid = 0, $paid = 1, $summary = false, $fund_id = false) {
        $summaryData = array();
        foreach ($ids as $id) {
            if ( $single_summary = self::markPaidById($id, $user_paid, $paid, true, $fund_id) ) {
                if (isset($summaryData[$single_summary[0]])) {
                    $summaryData[$single_summary[0]][0] += $single_summary[1];
                } else {
                    $summaryData[$single_summary[0]][0] = $single_summary[1];
                }
            }
        }

        if ($summary) {
            return $summaryData;
        } else {
            return true;
        }
    }

    public static function markPaidById($id, $user_paid = 0, $paid = 1, $summary = false, $fund_id = false) {

        $id = (int) $id;
        // do not proceed if called without an id
        if (!$id) {
            return false;
        }

        $paid = (int) $paid;
        $user_paid = (int) $user_paid;
        $user_paid = $user_paid == 0 ? $_SESSION['userid'] : $user_paid;
        $update_fund_id = ((int) $fund_id) ? " `fund_id` = " . (int) $fund_id . ", " : '';

        $select_query = "
            SELECT
                user_id,
                amount
            FROM
                " . FEES . "
            WHERE
                id = {$id}
                AND bonus = 1";

        if ( $select_result = mysql_query($select_query) ) {
            $select_result = mysql_fetch_array($select_result) or error_log("Error fetching bonus: $select_query : " . mysql_error());
        } else {
            error_log("Error selecting Bonus: $select_query : " . mysql_error());
            return false;
        }

        $receiver_id = $select_result['user_id'];
        $amount      = $select_result['amount'];

        $update_query = "
            UPDATE
                " . FEES . "
            SET
                `user_paid` = {$user_paid},
                `paid` = {$paid},
                {$update_fund_id}
                `paid_date` = NOW()
            WHERE
                id = {$id} AND
                bonus = 1
            LIMIT 1
            ";

        $result = mysql_query($update_query) or error_log("Error updating Bonus: $update_query : " . mysql_error());

        if (!$result) {
            error_log("ERORR: fees table update failed! (id # ".$id);
            return false;
        }

        if ($summary) {
            return array($receiver_id, $amount);
        } else {
            return !empty($result);
        }
    }
}
