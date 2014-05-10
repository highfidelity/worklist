<?php

class HomeController extends Controller {
	public function run() {
        $this->view = null;
        if (isset($_SESSION['userid']) && $_SESSION['userid']) {
            $controller = 'Jobs';
        } else {
            $controller = 'Welcome';
        }
        $controller .= 'Controller';
        $Controller = new $controller(false);
        $Controller->run();
	}
}
