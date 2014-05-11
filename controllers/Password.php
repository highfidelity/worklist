<?php

class PasswordController extends Controller {
    public function run() {
        checkLogin();

        $msg = array();
        if (!empty($_POST['oldpassword'])) {
            if((!empty($_POST['newpassword'])) && ($_POST['newpassword'] == $_POST['confirmpassword'])){

                $password = '{crypt}' . Utils::encryptPassword($_POST['newpassword']);

                $sql = "
                    UPDATE " . USERS . "
                    SET password = '" . mysql_real_escape_string($password) . "'
                    WHERE id ='" . $_SESSION['userid'] . "'";

                if (mysql_query($sql)) {

                    $msg[] = array("text" => "Password updated successfully!");
                    $to = $_SESSION['username'];
                    $subject = "Password Change";
                    $body  = "<p>Congratulations!</p>";
                    $body .= "<p>You have successfully updated your password with ".SERVER_NAME.".";
                    $body .= "</p><p>Love,<br/>Philip and Ryan</p>";
                    if (!send_email($to, $subject, $body)) {
                        error_log("PasswordController: send_email failed");
                    }
                } else {
                    $msg[] = array("text" => "Failed to update your password");
                }
            } else {
                $msg[] = array("text" => "New passwords don't match!");
            }
        }
        $this->write('msg', $msg);
        parent::run();
    }
}
