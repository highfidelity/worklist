<?php

class SettingsController extends Controller {
    public function run() {
        checkLogin();

        $userId = getSessionUserId();
        $user = new User();
        if ($userId) {
            $user->findUserById($userId);
        }
        $this->write('user', $user);

        $msg = "";
        $company = "";

        $saveArgs = array();
        $messages = array();
        $errors = 0;
        $error = new Error();
        $settings_link = SECURE_SERVER_URL . "settings";
        $worklist_link = SECURE_SERVER_URL . "jobs";
        $returned_json = array();


        // process updates to user's settings
        if (isset($_REQUEST['save_account'])) {

            $updateNickname = false;
            $updatePassword = false;

            if (isset($_REQUEST['timezone'])) {
                $timezone = mysql_real_escape_string(trim($_REQUEST['timezone']));
                $saveArgs = array('timezone' => 0);

                $notifications = 0;
                $my_bids_notify = !empty($_REQUEST['my_bids_notify']) ? Notification::MY_BIDS_NOTIFICATIONS : 0;
                $ping_notify = !empty($_REQUEST['ping_notify']) ? Notification::PING_NOTIFICATIONS : 0;
                $review_notify = !empty($_REQUEST['review_notify']) ? Notification::REVIEW_NOTIFICATIONS : 0;
                $bidding_notify = !empty($_REQUEST['bidding_notify']) ? Notification::BIDDING_NOTIFICATIONS : 0;
                $my_review_notify = !empty($_REQUEST['my_review_notify']) ? Notification::MY_REVIEW_NOTIFICATIONS : 0;
                $my_completed_notify = !empty($_REQUEST['my_completed_notify']) ? Notification::MY_COMPLETED_NOTIFICATIONS : 0;
                $self_email_notify = !empty($_REQUEST['self_email_notify']) ? Notification::SELF_EMAIL_NOTIFICATIONS : 0;
                $bidding_email_notify = !empty($_REQUEST['bidding_email_notify']) ? Notification::BIDDING_EMAIL_NOTIFICATIONS : 0;
                $review_email_notify = !empty($_REQUEST['review_email_notify']) ? Notification::REVIEW_EMAIL_NOTIFICATIONS : 0;

                $notifications = Notification::setFlags(
                    $review_notify,
                    $bidding_notify,
                    $my_review_notify,
                    $my_completed_notify,
                    $my_bids_notify,
                    $ping_notify,
                    $self_email_notify,
                    $bidding_email_notify,
                    $review_email_notify
                );

                $saveArgs['notifications'] = 0;

                // if user is new - create an entry for him
                // clear $saveArgs so it won't be updated for the second time
                // @TODO: Follow-up. Is this for the first creation of user in the worklist database (as opposed to
                // logon db?  -- lithium
                if (!empty($_SESSION['new_user'])) {

                    $user_id = (int) $_SESSION['userid'];
                    $username = $_SESSION['username'];
                    $nickname = $_SESSION['nickname'];

                    $sql = "
                        INSERT INTO " . USERS . "
                        (`id`, `username`, `nickname`, `timezone`, `country`, `notifications`, `is_active`, `confirm`)
                        VALUES ('$user_id', '$username', '$nickname', '$timezone', '$country', '$notifications', '1', '1')";
                    mysql_unbuffered_query($sql);
                    $_SESSION['new_user'] = '';
                    $saveArgs = array();
                } else {
                      // we need to check if settings have changed
                      // so as to send correct message in mail
                    if ($user->getCity() != $_REQUEST['city'] || $user->getCountry() != $_REQUEST['country'] || $user->getTimezone() != $_REQUEST['timezone']) {
                          $messages[] = "Your settings have been updated.";
                    }
                }
            }

            // has the nickname changed? update the database
            $nickname = trim($_REQUEST['nickname']);
            if($nickname != $_SESSION['nickname']) {
                $oldNickname = $_SESSION['nickname'];
                $user = new User();
                $user->findUserByNickname($nickname);

                if ($user->getId() != null && $user->getId() != intval($_SESSION['userid'])) {
                    die(json_encode(array(
                        'error' => 1,
                        'message' => "Update failed, nickname already exists!"
                    )));
                }


                if (!$_SESSION['new_user']) {
                    $sql = "
                        UPDATE " . USERS . "
                        SET nickname = '" . mysql_real_escape_string($nickname) . "' WHERE id ='" . $_SESSION['userid'] . "'";

                    if (mysql_query($sql)) {
                        $_SESSION['nickname'] = $nickname;
                        $messages[] = "Your nickname is now '$nickname'.";
                    } else {
                        $error->setError("Error updating nickname in Worklist");
                    }
                }

                if ($error->getErrorFlag()) {
                    $errormsg = implode(', ', $error->getErrorMessage());
                    $body = 'Nickname update failed for user with id='. intval($_SESSION['userid']) . ". \n";
                    $body .= "Old nickname: '" . $_SESSION['nickname'] . "'\n" ;
                    $body .= "New nickname: '" . $nickname . "'\n" ;
                    $body .= "Error message: '" . $errormsg;
                    send_email(FEEDBACK_EMAIL, 'Update nickname for user failed!', nl2br($body), $body);
                    die(json_encode(array(
                        'error' => 1,
                        'messsage' => $errormsg
                    )));
                } else {
                    sendJournalNotification("The new nickname for *" . $oldNickname . "* is @" . $nickname);
                }
            }

            // has the email changed? send confirm.
            $username = trim($_REQUEST['username']);
            if ($username != $_SESSION['username']) {
                //we need to check if the username exists
                if ( $user->findUserByUsername($username)) {
                    die(json_encode(array(
                    'error' => 1,
                       'message' => "This e-mail address is already linked to a Worklist account."
                    )));
               }

                $user->findUserByUsername($_SESSION['username']);
                //send out confirm email
                $email_hash = md5(date('r', time()));;

                // generate email for confirm to new email address
                $subject = "Your email has changed.";

                $link = WORKLIST_URL . "confirmation?emstr=" . base64_encode($username);

                $body  = '<p>Dear ' . $user->getNickname() . ',</p>';
                $body .= '<p>Please confirm your new email address in the <a href="' . $worklist_link . '">Worklist</a>.</p>';
                $body .= '<p><a href=' . $link . '>Click here to confirm your email address</a></p>';
               $body .= '<p><br/>You can view your settings <a href=' . $settings_link . '>here</a></p>';
               $body .= '<p><a href=' . $worklist_link . '>www.worklist.net</a></p>';


                $plain  = 'Dear ' . $user->getNickname() . ',' . "\n\n";
                $plain .= 'Please confirm your new email address in the Worklist.' . "\n\n";
                $plain .= $link . "\n\n";

                $confirm_email = "An email containing a confirmation link was sent to your email address.<br/>";
                $confirm_email .= "Please click on that link to verify your email address.";

                $returned_json['confirm_email'] = $confirm_email;

                if (! send_email($username, $subject, $body, $plain)) {
                   error_log("SettingsController: send_email failed");
                    $confirm_txt = "There was an issue sending email. Please try again or notify ". SUPPORT_EMAIL;
                }

                // generate email to current email address
                $subject = "Account email updated.";
                $body  = '<p>Hello you!,</p>';
                $body .= '<p>We received a request to update your email address for your Worklist.net account.</p>';
                $body .= '<p>If you did not make this request, please contact ' . SUPPORT_EMAIL . ' immediately.</p>';
                $body .= '<p>See you at the <a href='.SERVER_URL.'>Worklist</a></p>';

                $plain  = 'Hello you! ,' . "\n\n";
                $plain .= 'We received a request to update your email address for your Worklist.net account.' . "\n\n";
                $plain .= 'If you did not make this request, please contact ' . SUPPORT_EMAIL . ' immediately.' . "\n\n";
                $plain .= 'See you in the Worklist' . "\n\n";

                if (! send_email($_SESSION['username'], $subject, $body, $plain)) {
                    error_log("SettingsController: send_email failed");
                    $confirm_txt = 'There was an issue sending email. Please try again or notify ' . SUPPORT_EMAIL;
                }
                $messages[] = "We receieved your request to modify your email.";
            }

        } else if (isset($_REQUEST['save_personal'])) {
            $about = isset($_REQUEST['about']) ? strip_tags(substr($_REQUEST['about'], 0, 150)) : "";
            $skills = isset($_REQUEST['skills']) ? strip_tags($_REQUEST['skills']) : "";
            $contactway = isset($_REQUEST['contactway']) ? strip_tags($_REQUEST['contactway']) : "";

            $saveArgs = array('about'=>1, 'skills'=>1, 'contactway'=>1);
            $messages[] = "Your personal information has been updated.";
        } else if (isset($_REQUEST['save_payment'])) {
            $paypal = 0;
            $paypal_email = '';
            // defaulting to paypal at this stage
            $payway = 'paypal';
            if ($_REQUEST['paytype'] == 'paypal') {
                $paypal = 1;
                $payway = "paypal";
                $paypal_email = isset($_REQUEST['paypal_email']) ? mysql_real_escape_string($_REQUEST['paypal_email']) : "";
            } else if ($_REQUEST['paytype'] == 'other') {
                $payway = '';
            }

            $saveArgs = array('paypal' => 0, 'paypal_email' => 0, 'payway' => 1);
            $messages[] = "Your payment information has been updated.";

            if (!$user->getW9_accepted() && $user->getCountry() == 'US') {
                $w9_accepted = 'NOW()';
                $saveArgs['w9_accepted'] = 0;
            }

            $paypalPrevious = $user->getPaypal_email();

            // user deleted paypal email, deactivate
            if (empty($paypal_email)) {
                $user->setPaypal_verified(false);
                $user->setPaypal_email('');
                $user->save();
            // user changed paypal address
            } else if ($paypalPrevious != $paypal_email) {
                $paypal_hash = md5(date('r', time()));;
                // generate email
                $subject = "Your payment details have changed";

                $link = SECURE_SERVER_URL . "confirmation?pp=" . $paypal_hash . "&ppstr=" . base64_encode($paypal_email);

                $body  = '<p>Dear ' . $user->getNickname() . ',</p>';
                $body .= '<p>Please confirm your payment email address to activate payments on your account and enable you to start placing bids in the <a href="' . $worklist_link . '">Worklist</a>.</p>';
                $body .= '<p><a href="' . $link . '">Click here to confirm your payment address</a></p>';

                $plain  = 'Dear ' . $user->getNickname() . ',' . "\n\n";
                $plain .= 'Please confirm your payment email address to activate payments on your accounts and enable you to start placing bids in the Worklist.' . "\n\n";
                $plain .= $link . "\n\n";

                $confirm_txt = "An email containing a confirmation link was sent to your payment email address. Please click on that link to verify your payment email address and activate your account.";
                if (! send_email($paypal_email, $subject, $body, $plain)) {
                    error_log("SettingsController: send_email failed");
                    $confirm_txt = 'There was an issue sending email. Please try again or notify ' . SUPPORT_EMAIL ;
                }

                $user->setPaypal_verified(false);
                $user->setPaypal_hash($paypal_hash);
                $user->setPaypal_email($paypal_email);
                $user->save();
            }
        } else if (isset($_REQUEST['save_w9Name'])) {
            $first_name = isset($_REQUEST['first_name']) ? mysql_real_escape_string($_REQUEST['first_name']) : "";
            $last_name = isset($_REQUEST['last_name']) ? mysql_real_escape_string($_REQUEST['last_name']) : "";
            $saveArgs = array('first_name'=>1, 'last_name'=>1);
        }

        // do we have data to update?
        if (!empty($saveArgs)) {

            $sql = "UPDATE `" . USERS . "` SET ";
            foreach ($saveArgs as $arg => $esc) {

                if ($esc) {
                    $$arg = mysql_real_escape_string(htmlspecialchars($$arg));
                }

                if (is_int($$arg) || ($arg == "w9_accepted" && $$arg == 'NOW()')) {
                    $sql .= "`$arg` = " . $$arg . ",";
                } else {
                    $sql .= "`$arg` = '" . $$arg ."',";
                }
            }

            $sql = rtrim($sql, ',');
            $sql .= " WHERE id = {$_SESSION['userid']}";
            $res = mysql_query($sql);

            if (!$res) {
                error_log("Error in saving settings: " . mysql_error() . ':' . $sql);
                die("Error in saving settings. " );
            }

        // Email user
            if (!empty($messages)) {
                $to = $_SESSION['username'];
                $subject = "Settings";
                $body  = "<p>Congratulations!</p>";
                $body .= "<p>You have successfully updated your settings with Worklist <br/>";
                foreach ($messages as $msg) {
                    $body .= "&nbsp;&nbsp;$msg<br/>";
                }
                $body .= '<p><br/>You can view your settings <a href=' . $settings_link . '>here</a></p>';
                $body .= '<p><a href=' . $worklist_link . '>www.worklist.net</a></p>';

                if(!send_email($to, $subject, $body)) { error_log("SettingsController: send_email failed"); }

                $msg="Account updated successfully!";
            }


            if (isset($_REQUEST['timezone'])) {
              $_SESSION['timezone'] = trim($_REQUEST['timezone']);
            }

            if (isset($confirm_txt) && ! empty($confirm_txt)) {
                echo $confirm_txt;
                exit;
            }
            echo json_encode($returned_json);
            // exit on ajax post - if we experience issues with a blank settings page, need to look at the ajax submit functions
            exit;
        }

        // getting userInfo to prepopulate fields
        $userInfo = array();
        if(empty($_SESSION['new_user'])) {
            $qry = "SELECT * FROM ".USERS." WHERE id='".$_SESSION['userid']."'";
            $rs = mysql_query($qry);
            if ($rs) {
                $userInfo = mysql_fetch_array($rs);
            }
        }

        $this->write('new_user', (bool) $_SESSION['new_user']);
        $this->write('nickname', $_SESSION['nickname']);
        $this->write('userInfo', $userInfo);
        parent::run();
    }
}