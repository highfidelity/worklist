<?php

class UsersController extends Controller {
    public $view = null;

    public function run($action) {
        list($action, $format) = preg_split('/\./', $action, 2);
        switch($action) {
            case 'dashbard':
                $method = 'auth'
            case 'index':
            default:
                $method = 'index';
                break;
        }
        $this->$method($format);
    }

    // default method
    public function index($format) {
        $users = User::getUserList(getSessionUserId(), true);
        $ret = array();
        foreach ($users as $user) {
            $ret[] = array(
                'id' => $user->getId(),
                'nickname' => $user->getNickname()
            );
        }

        switch($format) {
            case 'json':
            default:
                echo json_encode(array('users' => $ret));
        }
    }

    public function dashboard() {
        $this->view = new UsersDashBoardView();
        parent::run();
    }
}
