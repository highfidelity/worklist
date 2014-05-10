<?php
require_once("config.php");
if(!isset($_SESSION)) { session_start(); }

Session::check();
checkLogin();

if (empty($_SESSION['is_payer'])) {
   header("Location:./reports");
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if ($_POST['action'] == 'check') {
	if (checkAdmin($_POST['password'])=='1') {
	    echo '1';
	} else {
	    echo '0';
	}
    }
    if ($_POST['action'] == 'change') {
	if (saveAdmin($_POST['password'], $_POST['old_password'])) {
	    echo 'Password Changed!';
	} else {
	    echo 'Error - Password NOT Changed.';
	}
    }
}

//only display the form if the page is accessed stand-alone (testing and updating only)
if (basename($_SERVER['PHP_SELF'])=='paypal-password.php') {
?>
<html>
<head><title>LM PayPal Admin Password</title></head>
<body>
<h2>Change Password</h2>
<form action="paypal-password.php" method="POST">
    <input type="hidden" name="action" value="change" />
    <div><label for="old_password">Current Password:</label><br /><input type="password" name="old_password" value="" /></div>
    <div><label for="password">New Password:</label><br /><input type="password" name="password" value="" /></div>
    <div><input type="submit" name="submit" value="Update Password" />
</form>
<?php if (isset($_GET['action']) && $_GET['action']== 'checkpass') { ?>
<h2>Check Password</h2>
<form action="paypal-password.php" method="POST">
    <input type="hidden" name="action" value="check" />
    <div><label for="password">Password:</label><br /><input type="password" name="password" value="" /></div>
    <div><input type="submit" name="submit" value="Check Password" />
</form>
<?php } ?>
</body>
</html>
<?php } ?>


