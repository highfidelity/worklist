<?php
/**
 *    Payments:
 *        List of users receiving payments for the
 *        period defined, Toggle-able view under each user
 *        that will show the individual jobs and payments
 *        for each / or just a one line summary with total
 *        amount.  All payments can be checked to pay, or
 *        to not pay, with a way to check all and clear all.
 *        Job Task_IDs can be moused over, as in the journal,
 *        to see the info, as well as clicked to open in a
 *        separate tab, if needed.  Pay Now button needed.
 *        Clicking pay now gives a confirm popup.
 */

class PaymentsController extends Controller {
    public function run() {
        //send non-payers back to the reports page.
        if (empty($_SESSION['is_payer'])) {
            $this->view = null;
            Utils::redirect("./reports");
        }

        $is_runner = !empty($_SESSION['is_runner']) ? 1 : 0;
        $is_payer = !empty($_SESSION['is_payer']) ? 1 : 0;
        $userId = getSessionUserId();

        $payer_id = $userId;
        // set default fund to worklist
        $fund_id = 3;

        if (isset($_REQUEST['fund_id'])) {
            $fund_id = mysql_real_escape_string($_REQUEST['fund_id']);
            // clear POST if this was just a fund change
            if (! isset($_REQUEST['action'])) {
                unset($_POST);
            }
        }

        //open db connection
        $db = @mysql_connect (DB_SERVER, DB_USER, DB_PASSWORD) or die ('I cannot connect to the database because: ' . mysql_error());
        $db = @mysql_select_db(DB_NAME);

        // get a list of projects so we can display the project name in table
        $sql_get_fund_projects_array = "
            SELECT
                project_id, name
            FROM
                ".PROJECTS."
            WHERE
                fund_id = " . $fund_id;

        // sql sub-query for limiting fees to specific fund
        $sql_get_fund_projects = "
            SELECT
                project_id
            FROM
                ".PROJECTS."
            WHERE
                fund_id = " . $fund_id;

        if ($fund_id == 0) {
            $sql_get_fund_projects = '0';
        }

        $fund_projects = array();
        $fund_projects[0] = 'none';
        $fund_projects_query = mysql_query($sql_get_fund_projects_array);
        while ($project = mysql_fetch_array($fund_projects_query)) {
            $fund_projects[$project['project_id']] = $project['name'];
        }

        $this->sql_get_fee_totals = "
            SELECT
                sum(f.amount) AS total_amount,
                u.id AS mechanic_id,
                u.nickname AS mechanic_nick,
                u.paypal_email AS mechanic_paypal_email,
                wl.summary AS worklist_item, f.bonus AS bonus, 'BONUS' AS bonus_desc
            FROM
                (".FEES." f LEFT JOIN ".USERS." u ON f.user_id = u.id)
                LEFT JOIN ".WORKLIST." wl ON f.worklist_id = wl.id
            WHERE
                wl.status = 'Done'
                AND f.paid = '0'
                AND f.withdrawn = '0'
                AND f.amount > 0
                AND u.paypal_verified = '1'
                AND u.has_W2 = 0
                AND wl.project_id IN (" . $sql_get_fund_projects . ")
            GROUP BY f.user_id
            ";

        $this->sql_get_bonus_totals = false;

        // only pull bonuses for if worklist fund chosen - temporary hardcoding
        // until we determine further solution
        if ($fund_id == 3) {
            $this->sql_get_bonus_totals = "
                SELECT
                    sum(b.amount) AS total_amount,
                    b.user_id AS mechanic_id,
                    b.desc AS worklist_item,
                    u.nickname AS mechanic_nick,
                    u.paypal_email AS mechanic_paypal_email
                FROM
                    ".FEES." b
                    LEFT JOIN ".USERS." u on u.id = b.user_id
                WHERE
                    b.paid = 0
                    AND b.withdrawn = 0
                    AND u.paypal_verified = '1'
                    AND b.bonus = 1
                    AND u.has_W2 = 0
               GROUP BY b.user_id
                ";
        }

        $action = (isset($_POST["action"])) ? $_POST["action"] : '';

        // Initialize empty arrays if no fees or bonuses were selected
        if (!isset($_POST['payfee'])) {
            $_POST['payfee'] = array();
        }
        if (!isset($_POST['paybonus'])) {
            $_POST['paybonus'] = array();
        }

        $pp_message = $httpParsedResponseAr = $alert_msg = $message = "";

        //Check action - should be confirm, pay or not set
        switch ($action) {
            case 'confirm':
                //$fees_csv = implode(',', $_POST["payfee"]);
                //pull list of payees from db based on the time span
                $payee_totals = $this->getUserTotalsArray();

            break;

            case 'pay':
                //collect confirmed payees and run paypal transaction
                //include_once("../paypal-password.php");
                if (checkAdmin($_POST['password']) == '1') {
                    error_log("Made it Admin!");
                    if(empty($_POST['pp_api_username']) || empty($_POST['pp_api_password']) || empty($_POST['pp_api_signature'])){
                        $alert_msg = "You need to provide all credentials!";
                        break;
                    }

                    //Get fee information for paypal transaction
                    $num_fees = count($_POST["payfee"]);
                    $fee_id_csv = implode(',', $_POST["payfee"]);
                    $fees_info_sql = 'SELECT
                            f.id AS fee_id,
                            f.amount AS amount,
                            f.worklist_id AS worklist_id,
                            u.id AS mechanic_id,
                            u.nickname AS mechanic_nick,
                            u.paypal_email AS mechanic_paypal_email,
                            wl.summary AS worklist_item
                        FROM
                            ('.FEES.' f LEFT JOIN '.USERS.' u ON f.user_id = u.id)
                            LEFT JOIN '.WORKLIST.' wl ON f.worklist_id = wl.id
                        WHERE
                            f.id in ('.$fee_id_csv.')';
                    $fees_info_results = mysql_query($fees_info_sql);

                    $num_bonuses = count($_POST["paybonus"]);
                    $bonus_id_csv = $num_bonuses ? implode(',', $_POST["paybonus"]) : 0 ;
                    $bonus_info_sql = '
                        SELECT
                            b.id AS fee_id,
                            b.amount AS amount,
                            "BONUS" AS worklist_id,
                            b.user_id AS mechanic_id,
                            u.nickname AS mechanic_nick,
                            u.paypal_email AS mechanic_paypal_email,
                            b.desc AS worklist_item
                        FROM
                            '.FEES.' b
                            LEFT JOIN '.USERS.' u on u.id = b.user_id
                        WHERE
                            b.id in ('.$bonus_id_csv.') and b.bonus = 1
                        ';
                    $bonus_info_results = mysql_query($bonus_info_sql) or error_log("bonussql failed: ".mysql_error()."\n$bonus_info_sql");

                    // Set request-specific fields.
                    $emailSubject = urlencode('You\'ve got money!');
                    $receiverType = urlencode('EmailAddress');
                    // TODO Other currency ('GBP', 'EUR', 'JPY', 'CAD', 'AUD') ?
                    $currency = urlencode('USD');

                    // Add request-specific fields to the request string.
                    $nvpStr="&EMAILSUBJECT=$emailSubject&RECEIVERTYPE=$receiverType&CURRENCYCODE=$currency";

                    //build payment data array
                    $message .= "<pre>";
                    $receiversArray = array();
                    $totalFees = 0; //log data
                    if (mysql_num_rows($fees_info_results)) {
                        $message .= "Fees:\n";
                        while ($fees_data = mysql_fetch_array($fees_info_results)) {
                            $receiversArray[] = array(
                                'receiverEmail' => $fees_data["mechanic_paypal_email"],
                                'amount' => $fees_data["amount"],
                                'uniqueID' => $fees_data["fee_id"],
                                'note' => 'Worklist #'.$fees_data["worklist_id"].' - '.$fees_data["worklist_item"]);
                            $totalFees = $totalFees + $fees_data["amount"];
                            $message .= "    ".$fees_data['mechanic_paypal_email']." - $".$fees_data['amount']."\n";
                        }
                    }
                    if (mysql_num_rows($bonus_info_results) > 0) {
                        $message .= "Bonuses:\n";
                        while ($fees_data = mysql_fetch_array($bonus_info_results)) {
                            $receiversArray[] = array(
                                'receiverEmail' => $fees_data["mechanic_paypal_email"],
                                'amount' => $fees_data["amount"],
                                'uniqueID' => $fees_data["fee_id"],
                                'note' => $fees_data["worklist_id"].' - '.$fees_data["worklist_item"]);
                            $totalFees = $totalFees + $fees_data["amount"];
                            $message .= "    ".$fees_data['mechanic_paypal_email']." - $".$fees_data['amount']."\n";
                        }
                    }
                    $message .= "</pre>";

                    //build nvp string
                    foreach($receiversArray as $i => $receiverData) {
                        $receiverEmail = urlencode($receiverData['receiverEmail']);
                        $amount = urlencode($receiverData['amount']);
                        $uniqueID = urlencode($receiverData['uniqueID']);
                        $note = urlencode($receiverData['note']);
                        $nvpStr .= "&L_EMAIL$i=$receiverEmail&L_Amt$i=$amount&L_UNIQUEID$i=$uniqueID&L_NOTE$i=$note";
                    }

                    // Execute the API operation; see the PPHttpPost function
                    $httpParsedResponseAr = PPHttpPost('MassPay', $nvpStr, $_POST);
                    #$httpParsedResponseAr = array("ACK" => "SUCCESS");

                    if("SUCCESS" == strtoupper($httpParsedResponseAr["ACK"]) || "SUCCESSWITHWARNING" == strtoupper($httpParsedResponseAr["ACK"])) {
                        $pp_message = '<p>MassPay Completed Successfully! - $'.$totalFees.' Paid.</p>';
                        if (isset($_GET["debug"])) {
                            $pp_message .= '<p><pre>'.print_r($httpParsedResponseAr, true).'</pre></p>';
                        }
                        //$fee_sql_update = "UPDATE ".FEES." SET paid=1, paid_date='".date("Y-m-d H:i:s")."' WHERE id in (".$fees_csv.")";
                        //$update_fees_paid = mysql_query($fee_sql_update);

                        $summaryData = Fee::markPaidByList(explode(',', $fee_id_csv), $user_paid = 0, $paid_notes='', $paid = 1, $fund_id);
                        if ($bonus_id_csv) {
                            Bonus::markPaidByList(explode(',', $bonus_id_csv),  $user_paid = 0, $paid = 1, false, $fund_id);
                        }

                    } else  {
                        $alert_msg = "MassPay Failure";
                        $pp_message = '<p>MassPay failed:</p><p><pre>' . print_r($httpParsedResponseAr, true).'</pre></p>';
                        if(!send_email('finance@lovemachineinc.com', 'Masspay Fail', $pp_message)) {
                            error_log("view-payments:MassPayFailure: send_email failed");
                        }
                    }

                } else {
                    $error_msg = 'Invalid MassPay Authentication<br />';
                    $error_msg .= 'IP: '. $_SERVER['REMOTE_ADDR'].'<br />';
                    $error_msg .= 'UserID: '.$userId;
                    if (!send_email("finance@lovemachineinc.com", "Masspay Invalid Auth Attempt", $error_msg)) {
                        error_log("view-payments:MassPayAuth: send_email failed");
                    }
                    $alert_msg = "Invalid Authentication";
                }
                break;

            default:
                //pull list of payees from db based on the time span
                $payee_totals = $this->getUserTotalsArray();
            break;
        }

        $this->write('fund_id', $fund_id);
        $this->write('message', $message);
        $this->write('pp_message', $pp_message);
        $this->write('alert_msg', $alert_msg);
        $this->write('payee_totals', $payee_totals);
        $this->write('fund_projects', $fund_projects);
        $this->write('sql_get_fund_projects', $sql_get_fund_projects);
        $this->write('input', array(
            'action' => isset($_POST['action']) ? $_POST['action'] : '',
            'order' => isset($_GET["order"]) ? 'order='.$_GET["order"] : ''
        ));
        parent::run();
    }

    private function getUserTotalsArray() {
        // Retuns an array with the total amount owed to each user including
        // worklist payments and bonuses. Fields in the array are:
        //  total_amount, mechanic_id, worklist_item, mechanic_nick, mechanic_payal_email
        // Creating 1 SQL statement to combine FEES and BONUS_PAYMENTS blew my mind
        // so I'm combining it in PHP. -Alexi 2011-03-03
        $sql_get_fee_totals = $this->sql_get_fee_totals;
        $sql_get_bonus_totals = $this->sql_get_bonus_totals;

        $fee_totals_query   = mysql_query($sql_get_fee_totals);

        $totals_array = array();
        while ($fees_array = mysql_fetch_array($fee_totals_query, MYSQL_ASSOC)) {
            $totals_array[] = $fees_array;
        }

        if ($sql_get_bonus_totals) {
            $bonus_totals_query = mysql_query($sql_get_bonus_totals);

            if (mysql_num_rows($bonus_totals_query) == 0) {
                // If there are no bonuses, return now
                return $totals_array;
            }

            while ($bonus_array = mysql_fetch_array($bonus_totals_query, MYSQL_ASSOC)) {
                $bonus_applied = false;
                foreach ($totals_array as $t_id => $fee_payee) {
                    // Loop through payee fees to try to add the bonus total there
                    if ($bonus_array['mechanic_id'] == $fee_payee['mechanic_id']) {
                        $totals_array[$t_id]['total_amount'] = sprintf("%01.2f",
                            $totals_array[$t_id]['total_amount'] + $bonus_array['total_amount']);
                        $bonus_applied = true;
                    }
                }
                if (!$bonus_applied) {
                    // If no existing payments found for the user, append them to the array
                    $totals_array[] = $bonus_array;
                }
            }
        }

        return $totals_array;
    }

}
