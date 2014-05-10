<?php
//  vim:ts=4:et

//  Copyright (c) 2011, LoveMachine Inc.
//  All Rights Reserved.
//  http://www.lovemachineinc.com


/*******************************************************
    Page: pp_ipn_notify.php
    Features: Paypal IPN Handler
        This page will be target of Paypal IPNs.
        Switch on the 'type' to determine what
        action(s) to take.
    Author: Jason (jkofoed@gmail.com)
    Date: 2010-04-10
********************************************************/
ini_set('display_errors', 1);
error_reporting(E_ALL);


//ob_start();

include("config.php");

// read the post from PayPal system and add 'cmd' variable
$rqstr = 'cmd=_notify-validate';
$debug_msg = '';

/**
*  watch this area for possible overloading.
*  would like to do this differently, but vars are different dependant on txn_type and
*  I can look into improving this later and suggest something if I can come up with it.
*  (Apr-27-2010 - Jason jkofoed@gmail.com)
*
*/

foreach ($_POST as $key => $value) {
    $$key = urldecode($value);
    $debug_msg .= "$".$key.": ".urldecode($value)."\r\n";
    $value = urlencode(stripslashes($value));
    $rqstr .= "&$key=$value";
}

$header = "";
// post back to PayPal system to validate
$header .= "POST /cgi-bin/webscr HTTP/1.0\r\n";
$header .= "Content-Type: application/x-www-form-urlencoded\r\n";
$header .= "Content-Length: " . strlen($rqstr) . "\r\n\r\n";
$fp = fsockopen ('ssl://www.paypal.com', 443, $errno, $errstr, 30);

/**********************************************
txn_type can be any of the following:

.                   => Credit card chargeback if the case_type variable == 'chargeback'
adjustment          => A dispute has been resolved and closed
cart                => Pmt rcvd for multi items; Express Checkout or PP Shopping Cart.
express_checkout    => Pmt received for a single item; source is Express Checkout
masspay             => Payment sent using MassPay
merch_pmt           => Monthly subscription paid for Website Payments Pro
new_case            => A new dispute was filed
recurring_payment   => Recurring payment received
recurring_payment_profile_created => Recurring payment profile created
send_money          => Payment received via the Send Money tab on the PayPal website
subscr_cancel       => Subscription canceled
subscr_eot          => Subscription expired
subscr_failed       => Subscription signup failed
subscr_modify       => Subscription modified
subscr_payment      => Subscription payment received
subscr_signup       => Subscription started
virtual_terminal    => Payment received; source is Virtual Terminal
web_accept          => Payment received; via Buy Now, Donation, or Auction Smart Logos
**********************************************/

if ($fp) {
    //open db connection
    $db = @mysql_connect(DB_SERVER, DB_USER, DB_PASSWORD) or die ('I cannot connect to the database because: ' . mysql_error());
    $db = @mysql_select_db(DB_NAME);


    fputs($fp, $header . $rqstr);
    $message = "";
    while (!feof($fp)) {
        $res = fgets ($fp, 1024);
        if (strcmp($res, "VERIFIED") == 0) {
            // check the payment_status is Completed
            // check that txn_id has not been previously processed
            // check that receiver_email is your Primary PayPal email
            // check that payment_amount/payment_currency are correct
            // process payment
            $message .= "\r\n Verified.";
            switch ($txn_type) {
                case 'masspay':
                    /**
                    *   $payment_status = 'Completed'|'Processed'|'Denied'|'Pending'
                    *   Canceled_Reversal: A reversal has been canceled. For example,
                    *       you won a dispute with the customer, and the funds for the
                    *       transaction that was reversed have been returned to you.
                    *   Completed: The payment has been completed, and the funds have
                    *       been added successfully to your account balance.
                    *   Created: A German ELV payment is made using Express Checkout.
                    *   Denied: You denied the payment. This happens only if the payment
                    *       was previously pending because of possible reasons described
                    *       for the pending_reason variable or the Fraud_Management_Filters_x variable.
                    *   Expired: This authorization has expired and cannot be captured.
                    *   Failed: The payment has failed. This happens only if the payment was made from your customer.s bank account.
                    *   Pending: The payment is pending. See pending_reason for more information.
                    *   Refunded: You refunded the payment.
                    *   Reversed: A payment was reversed due to a chargeback or other type of reversal.
                    *       The funds have been removed from your account balance and returned to the buyer.
                    *       The reason for the reversal is specified in the ReasonCode element.
                    *   Processed: A payment has been accepted.
                    *   Voided: This auth has been voided and returned to the payer
                    */


                    // set counter, using dynamic variables to parse POST vars
                    $n = 1;
                    $fee_id = 'unique_id_'.$n;

                    //set status_reason if status is pending.
                    $mp_status_reason = NULL;
                    if (isset($pending_reason)) { $mp_status_reason = $pending_reason; }

                    while (isset($$fee_id)) {
                        // Needed to use the counter as a part of a dynamic var to grab the correct
                        // variable from the IPN POST.  if there is a unique_id set
                        $receiver_email = 'receiver_email_'.$n;
                        $mc_currency = 'mc_currency_'.$n;
                        $masspay_txn_id = 'masspay_txn_id_'.$n;
                        $fee_id = 'unique_id_'.$n;
                        $status = 'status_'.$n; // 'Completed, Failed, Reversed, or Unclaimed'
                        $mc_gross = 'mc_gross_'.$n;
                        $mc_fee = 'mc_fee_'.$n;

                        //if a log doesn't exist for this fee, create one, otherwise update.
                        $log_sql = "SELECT * FROM ".PAYPAL_LOG." WHERE fee_id = ".$$fee_id;
                        $log_exists = mysql_num_rows(mysql_query($log_sql));
                        if ($log_exists == 0) {
                            $mp_sql = "INSERT INTO ".PAYPAL_LOG." (fee_id, payment_gross, payment_fee, status, masspay_txn_id, txn_verify, masspay_run_status, masspay_status_reason, currency, payee_paypal_email, date_created) VALUES (";
                            $mp_sql .= "'".mysql_real_escape_string($$fee_id)."', ";
                            $mp_sql .= "'".mysql_real_escape_string($$mc_gross)."', ";
                            $mp_sql .= "'".mysql_real_escape_string($$mc_fee)."', ";
                            $mp_sql .= "'".mysql_real_escape_string($$status)."', ";
                            $mp_sql .= "'".mysql_real_escape_string($$masspay_txn_id)."', ";
                            $mp_sql .= "'".mysql_real_escape_string($verify_sign)."', ";
                            $mp_sql .= "'".mysql_real_escape_string($payment_status)."', ";
                            $mp_sql .= "'".mysql_real_escape_string($mp_status_reason)."', ";
                            $mp_sql .= "'".mysql_real_escape_string($$mc_currency)."', ";
                            $mp_sql .= "'".mysql_real_escape_string($$receiver_email)."', ";
                            $mp_sql .= "'".date("Y-m-d H:i:s")."')";
                        } else {
                            $mp_sql  = "UPDATE ".PAYPAL_LOG." SET ";
                            $mp_sql .= "payment_gross ='".mysql_real_escape_string($$mc_gross)."', ";
                            $mp_sql .= "payment_fee ='".mysql_real_escape_string($$mc_fee)."', ";
                            $mp_sql .= "status ='".mysql_real_escape_string($$status)."', ";
                            $mp_sql .= "masspay_txn_id ='".mysql_real_escape_string($$masspay_txn_id)."', ";
                            $mp_sql .= "txn_verify ='".mysql_real_escape_string($verify_sign)."', ";
                            $mp_sql .= "masspay_run_status ='".mysql_real_escape_string($payment_status)."', ";
                            $mp_sql .= "masspay_status_reason = '".mysql_real_escape_string($mp_status_reason)."', ";
                            $mp_sql .= "currency ='".mysql_real_escape_string($$mc_currency)."', ";
                            $mp_sql .= "payee_paypal_email ='".mysql_real_escape_string($$receiver_email)."', ";
                            $mp_sql .= "date_updated = '".date("Y-m-d H:i:s")."' ";
                            $mp_sql .= "WHERE fee_id = ".mysql_real_escape_string($$fee_id);
                        }

                        //record IPN results in db
                        $mp_results = mysql_query($mp_sql);

                        //get next id to check.
                        $n++;
                        $fee_id = 'unique_id_'.$n;
                    }
                    break;

                case "web_accept":
                    $ch = curl_init('http://dev.sendlove.us/~jason/lovemachine/html/paypal-function.php?action=compPPTxn');
                    curl_setopt ($ch, CURLOPT_POST, 1);
                    curl_setopt ($ch, CURLOPT_POSTFIELDS, $rqstr);
                    curl_exec ($ch);
                    curl_close ($ch);
                    break;
            }
        } else if (strcmp ($res, "INVALID") == 0) {
            // If returns INVALID, log for manual investigation
            //set up ERROR email
            $to      =  'finance@lovemachineinc.com';
            $subject =  'PayPal IPN FAIL - '.date("Y-m-d H:i:s");
            $email_headers =  'From: pp-ipn-handler@lovemachineinc.com' . "\r\n" .
                        'Reply-To: pp-ipn-handler@lovemachineinc.com' . "\r\n" .
                        'X-Mailer: PHP/' . phpversion();
            $message .=  $message."\r\nData Dump:\r\n";
            $message .= 'Masspay Verify: '.$verify_sign."\r\n";
            $message .= 'Payment Status: '.$payment_status."\r\n";
            $message .= "\r\n Invalid IPN.";
            $message .= $debug_msg;
            //This is not using the mail mechanism and may not work in all installations
            mail($to, $subject, $message, $email_headers);
        }
    }
    fclose ($fp);

} else {

    //set up ERROR email
    $to      =  'finance@lovemachineinc.com';
    $subject =  'PayPal IPN FAIL - '.date("Y-m-d H:i:s");
    $email_headers =  'From: pp-ipn-handler@lovemachineinc.com' . "\r\n" .
                'Reply-To: pp-ipn-handler@lovemachineinc.com' . "\r\n" .
                'X-Mailer: PHP/' . phpversion();
    $message .=  $message."\r\nData Dump:\r\n";
    $message .= 'Masspay Verify: '.$verify_sign."\r\n";
    $message .= 'Payment Status: '.$payment_status."\r\n";
    $message .= $debug_msg;
            //This is not using the mail mechanism and may not work in all installations
    mail($to, $subject, $message, $email_headers);
}


?>
