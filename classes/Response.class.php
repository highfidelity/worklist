<?php

class Response {
    /***
     * @var array Response string
     */
    protected $body;
    protected $error;

    public function __construct(){
        $this->setError(new Error());
    }

    public function setError($error){
        $this->error = $error;
        return $this;
    }
    public function getError(){
        if(! isset($this->error)){
            $this->setError(new Error());
        }
        return $this->error;
    }
    public function addParams($params){
        foreach($params as $name => $value){
            $this->body[$name] = $value;
        }
        return $this;
    }
    public function sendResponse(){
        if($this->getError()->getErrorFlag()){
            $output = array("error" => 1);
            foreach($this->getError()->getErrorMessage() as $message){
                $output["message"][] = $message;
            }
            echo json_encode($output);
            exit(0);
        }else{
            $output = array("error" => 0);
            foreach($this->body as $name => $value){
                $output[$name] = $value;
            }
            echo json_encode($output);
            exit(0);
        }
    }
}
