<?php

class Login {
    /**
     * @var array
     */
    private $params;
    /**
     * @var Response
     */
    protected $response;
    /**
     * @var Database
     */
    protected $database;

    public function __construct(){
        $this->params = array("app" => SERVICE_NAME, "key" => API_KEY);
    }
    public function setDatabase($db){
        $this->database = $db;
        return $this;
    }
    public function getDatabase(){
        if(! isset($this->database)){
            $this->setDatabase(new Database());
        }
        return $this->database;
    }
    public function setResponse($response){
        $this->response = $response;
        return $this;
    }
    public function getResponse(){
        if(! isset($this->response)){
            $this->setResponse(new Response());
        }
        return $this->response;
    }
    public function saveToken($token){
        $this->getDatabase()->insert(TOKENS, array('token' => $token, 'completed' => 0), array('%s', '%d'));
    }
    public function updateToken($token){
        $this->getDatabase()->update(TOKENS, array('completed' => 1), array('token' => $token), array('%d'), array('%s'));
    }
    public function checkToken($token){
        $res = $this->getDatabase()->query("SELECT completed FROM ".TOKENS." WHERE token = '" . sprintf('%s', $token) . "'");
        $ret = mysql_fetch_object($res);

        $found = mysql_num_rows($res);
        if($found > 0 && $ret->completed == 0){
            return true;
        }else{
            return false;
        }
    }
    public function signup(){
        if(! isset($_REQUEST["username"])){
            $this->getResponse()->getError()->setError("Username field is missing.");
        }else if(! isset($_REQUEST["password"]) || empty($_REQUEST["password"])){
            $this->getResponse()->getError()->setError("Password field is missing.");
        }else if(! isset($_REQUEST["confirm_string"])){
            $this->getResponse()->getError()->setError("Confirm string is missing.");
        }else{
            $token = uniqid();
            $this->saveToken($token);
            $this->params["username"] = $_REQUEST["username"];
            $this->params["password"] = $_REQUEST["password"];
            if(isset($_REQUEST["nickname"])){
                $this->params["nickname"] = $_REQUEST["nickname"];
            }
            $this->params["token"] = $token;
            $this->params["confirm_string"] = $_REQUEST["confirm_string"];

            ob_start();
            // send the request
            echo  CURLHandler::Post(LOGIN_APP_URL . 'create', $this->params, false, true);
            $result = ob_get_contents();
            ob_end_clean();
			error_log("logonApi Result:".$result);
            $result = json_decode($result);
            if(!empty($result->error) && $result->error == 1){
                $this->getResponse()->getError()->setError($result->message);
            }else{
                if(isset($result->token) && $this->checkToken($result->token) && $token == $result->token){
                    $this->updateToken($result->token);
                    $this->getResponse()->addParams($result);
                }else{
                    error_log('Invalid Token aka Malicious attempt on function signup');
                }
            }
        }
    }
    public function loginrequest(){
        if(! isset($_REQUEST["username"])){
            $this->getResponse()->getError()->setError("Username field is missing.");
        }else if(! isset($_REQUEST["password"])){
            $this->getResponse()->getError()->setError("Password field is missing.");
        }else{
            $token = uniqid();
            $this->saveToken($token);
            $this->params["username"] = $_REQUEST["username"];
            $this->params["password"] = $_REQUEST["password"];
            $this->params["token"] = $token;
            ob_start();
            // send the request
            echo  CURLHandler::Post(LOGIN_APP_URL . 'login', $this->params, false, true);
            $result = ob_get_contents();
            ob_end_clean();
            $result = json_decode($result);
            if($result->error == 1){
                $this->getResponse()->getError()->setError($result->message);
            } else {
                if($this->checkToken($result->token) && $token == $result->token){
                    $this->updateToken($result->token);
                    $this->getResponse()->addParams($result);
                } else {
                    error_log('Invalid Token aka Malicious attempt on function loginrequest');
                }
            }
        }
    }

    public function getUserData(){
        if(!isset($_REQUEST["user_id"])){
            $this->getResponse()->getError()->setError("No user id set.");
        } else if(!isset($_SESSION["userid"])) {
            $this->getResponse()->getError()->setError("You are not logged in.");
        } else {
            $user_id = (int)$_REQUEST["user_id"];
            $admin_id = (int)$_SESSION["userid"];
            $token = uniqid();
            $this->saveToken($token);
            $this->params["user_id"] = $user_id;
            $this->params["admin_id"] = $admin_id;
            $this->params["token"] = $token;
            ob_start();
            // send the request
            echo CURLHandler::Post(LOGIN_APP_URL . 'getuserdata', $this->params, false, true);
            $result = ob_get_contents();
            ob_end_clean();
            $result = json_decode($result);
            if($result->error == 1){
                $this->getResponse()->getError()->setError($result->message);
            }else{
                if($this->checkToken($result->token) && $token == $result->token){
                    $this->updateToken($result->token);
                    $this->getResponse()->addParams($result);
                }else{
                    error_log('Invalid Token aka Malicious attempt on function getUserData');
                }
            }
        }
    }

    public function setUserData(){
        if(!isset($_REQUEST["user_data"])){
            $this->getResponse()->getError()->setError("No user data set.");
        } else if(!isset($_SESSION["userid"])) {
            $this->getResponse()->getError()->setError("You are not logged in.");
        } else {
            $user_data = $_REQUEST["user_data"];
            $admin_id = (int)$_SESSION["userid"];
            $token = uniqid();
            $this->saveToken($token);
            foreach($user_data as $key=>$value){
                $this->params["user_data"][$key] = $value;
            }
            $this->params["admin_id"] = $admin_id;
            $this->params["token"] = $token;
            ob_start();
            // send the request
            echo CURLHandler::Post(LOGIN_APP_URL . 'setuserdata', $this->params, false, true);
            $result = ob_get_contents();
            ob_end_clean();
            $result = json_decode($result);
            if($result->error == 1){
                $this->getResponse()->getError()->setError($result->message);
            }else{
                if($this->checkToken($result->token) && $token == $result->token){
                    $this->updateToken($result->token);
                    $this->getResponse()->addParams($result);
                }else{
                    error_log('Invalid Token aka Malicious attempt on function setUserData');
                }
            }
        }
    }
    public function update(){
        if(!isset($_REQUEST["user_data"])){
            $this->getResponse()->getError()->setError("No user data set.");
        } else if(!isset($_REQUEST["sid"])){
            $this->getResponse()->getError()->setError("Session id is not set");
        } else {
            session::initById($_REQUEST["sid"]);
            if(!isset($_SESSION["userid"])) {
                $this->getResponse()->getError()->setError("You are not logged in.");
            }else {
                $user_data = $_REQUEST["user_data"];
                $token = uniqid();
                $this->saveToken($token);
                foreach($user_data as $key=>$value){
                    $this->params["user_data"][$key] = $value;
                }
                $this->params["user_data"]["userid"] = $_SESSION["userid"];
                $this->params["token"] = $token;
                ob_start();
                // send the request
                echo CURLHandler::Post(LOGIN_APP_URL . 'update', $this->params, false, true);
                $result = ob_get_contents();
                ob_end_clean();
                $result = json_decode($result);
                if($result->error == 1){
                    $this->getResponse()->getError()->setError($result->message);
                }else{
                    if($this->checkToken($result->token) && $token == $result->token){
                        $this->updateToken($result->token);
                        $this->getResponse()->addParams($result);
                    }else{
                    error_log('Invalid Token aka Malicious attempt on function update');
                    }
                }
            }
        }
    }

    public function resetUserPassword(){
        if(!isset($_REQUEST["user_id"])){
            $this->getResponse()->getError()->setError("No user id set.");
        } else if(!isset($_SESSION["userid"])) {
            $this->getResponse()->getError()->setError("You are not logged in.");
        } else {
            $user_id = (int)$_REQUEST["user_id"];
            $admin_id = (int)$_SESSION["userid"];
            $token = uniqid();
            $this->saveToken($token);
            $this->params["user_id"] = $user_id;
            $this->params["admin_id"] = $admin_id;
            $this->params["token"] = $token;
            ob_start();
            // send the request
            echo CURLHandler::Post(LOGIN_APP_URL . 'adminresettoken', $this->params, false, true);
            $result = ob_get_contents();
            ob_end_clean();
            $result = json_decode($result);
            if($result->error == 1){
                $this->getResponse()->getError()->setError($result->message);
            }else{
                if($this->checkToken($result->token) && $token == $result->token){
                    $this->updateToken($result->token);
                    $resetUrl = SECURE_SERVER_URL . 'resetpass?un=' . base64_encode($result->username) . '&amp;token=' . $result->confirm_string;
                    $resetUrl = '<a href="' . $resetUrl . '" title="Password Recovery">' . $resetUrl . '</a>';
                    sendTemplateEmail($result->username, 'recovery', array('url' => $resetUrl));
                    $this->getResponse()->addParams($result);
                }else{
                    error_log('Invalid Token aka Malicious attempt on function resetUserPassword');
                }
            }
        }
    }

    public function notify($user_id, $session_id){
        $token = uniqid();
        $this->saveToken($token);
        $this->params["userid"] = $user_id;
        $this->params["sessionid"] = $session_id;
        $this->params["token"] = $token;
        ob_start();
        // send the request
        echo CURLHandler::Post(LOGIN_APP_URL . 'notify', $this->params, false, true);
        $result = ob_get_contents();
        ob_end_clean();

        $result = json_decode($result);
        if($result->error == 1){
            $this->getResponse()->getError()->setError($result->message);
        }else{
            if($this->checkToken($result->token) && $token == $result->token){
                $this->updateToken($result->token);
                $this->getResponse()->addParams($result);
            }else{
                error_log('Invalid Token aka Malicious attempt on function notify');
            }
        }
    }
}
