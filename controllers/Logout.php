<?php

class LogoutController extends Controller {
	public function run() {
		$this->view = null;

		unset($_SESSION['username']);
		unset($_SESSION['userid']);
		unset($_SESSION['confirm_string']);
		unset($_SESSION['nickname']);
		unset($_SESSION['running']);
		unset($_SESSION['access_token']);
		if (isset($_COOKIE[session_name()])) {
		    setcookie(session_name(), '', time() - 42000, '/');
		}

		session_destroy();

		Utils::redirect($_SESSION['redirect_url']);		
	}
}


