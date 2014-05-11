<?php

class Error {

    protected $error;
    protected $errorMessage;
    public function __construct(){
        $this->setErrorFlag(false);
    }
    public function setError($msg){
        $this->setErrorFlag(true);
        $this->setErrorMessage($msg);
        return $this;
    }
    public function setErrorMessage($msg){
        if(is_array($msg)){
            foreach($msg as $m){
                $this->errorMessage[] = $m;
            }
        }else{
            $this->errorMessage[] = $msg;
        }
        return $this;
    }
    public function setErrorFlag($f){
        $this->error = $f;
        return $this;
    }
    public function getErrorFlag(){
        return $this->error;
    }
    public function getErrorMessage(){
        return $this->errorMessage;
    }
    public function __isset($a=null) { // $a=null is an override to fix breakage on 08-FEB-2011 @ 10:12 EST <danbrown>
        return getErrorFlag();
    }
}
