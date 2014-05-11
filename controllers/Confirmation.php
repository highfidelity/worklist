<?php

class ConfirmationController extends Controller {
    public $view = null;

    public function run() {
        $msg = "";
        $to = 1;
        $user = new User();

        if (isset($_REQUEST['str'])) {
            $email = mysql_real_escape_string(base64_decode($_REQUEST['str']));
            $confirm_string = substr($_REQUEST['cs'], 0, 10);

            // verify the email belongs to a user
            if ($user->findUserByUsername($email) && substr($user->getConfirm_string(), 0, 10) == $confirm_string) {
                $sql = "
                    UPDATE " . USERS . "
                    SET
                        confirm_string = '',
                        confirm = 1,
                        is_active = 1
                    WHERE username = '" . $email . "'";

                mysql_query($sql);

                // send welcome email
                sendTemplateEmail($user->getUsername(), 'welcome', array('nickname' => $user->getNickname()), 'Worklist <contact@worklist.net>');
                User::login($user, false); //Optionally can login with confirm URL
                $jobs = new JobsController();
                $jobs->view->jumbotron =
                    "<h2>Welcome to Worklist!</h2>
                    <p>
                      Click on a job and add your bid, or come join us in our
                      <a href='https://gitter.im/highfidelity/worklist' target='_blank'>public chat room</a>.
                      Questions? Check out the <a href='./help'>help tab</a>.
                    </p>
                    ";
                $jobs->run();
                return;
            } else {
                Utils::redirect('./');
            }
        } elseif (isset($_REQUEST['ppstr'])) {
            // paypal address confirmation
            $paypal_email = mysql_real_escape_string(base64_decode($_REQUEST['ppstr']));
            $hash = mysql_real_escape_string($_REQUEST['pp']);

            // verify the email belongs to a user
            if (! $user->findUserByPPUsername($paypal_email, $hash)) {
                // hacking attempt, or some other error
                Utils::redirect('./');
            } else {
                $user->setPaypal_verified(true);
                $user->setPaypal_hash('');
                $user->save();
                Utils::redirect('./settings?ppconfirmed');
            }
        } elseif (isset($_REQUEST['emstr'])) {
            // new email address confirmation
            $new_email = mysql_real_escape_string(base64_decode($_REQUEST['emstr']));

            if (! $user->findUserByUsername($_SESSION['username'])) {
                Utils::redirect('login'); //we are not logged in
            }
            //save new email
            $user->setUsername($new_email);
            $user->save();
            $_SESSION['username'] = $new_email;
            Utils::redirect('./settings?emconfirmed');
        }

        Utils::redirect('./settings');
    }
}
