<?php

class ResendController extends Controller {
    public function run() {
        extract($_REQUEST);
        if(!empty($_POST['username'])) {
            $res = mysql_query("select id, confirm, confirm_string from ".USERS." where username ='".mysql_real_escape_string($_POST['username'])."'");
            if(mysql_num_rows($res) > 0 ) {
                $row = mysql_fetch_array($res);
                $to = $_POST['username'];
                // Email user
                $subject = "Worklist Registration Confirmation";
                $body = "<p>You are only one click away from completing your registration with Worklist!</p><p>Click the link below or copy into your browser's window to verify your email address and activate your account. <br/>";
                $body .= "&nbsp;&nbsp;&nbsp;&nbsp;".SECURE_SERVER_URL."confirmation?cs=".$row['confirm_string']."&str=".base64_encode($_POST['username'])."</p>";
                $body .= "<p>Looking forward to seeing you in the Workroom! :)</p>";
                if(!send_email($to, $subject, $body)) {
                    error_log("ResendController: send_email failed");
                }
                $msg = "An email containing a link to confirm your email address is being sent to ".$to;
            } else {
                $msg = "Sorry, your email address doesn't match";
            }
        }
        $this->write('msg', $msg);
        parent::run();
    }
}