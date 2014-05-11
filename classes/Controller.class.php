<?php

class Controller extends AppObject {
    protected $internal = false;
    protected $internal_values = array();
    protected $view = null;

    public function __construct($internal = false) {
        $this->internal = $internal;
        if (!$this->internal) {
            Session::check();
        }
        $viewName = get_class($this);
        if (strlen($viewName) > 10 && substr($viewName, -10) == 'Controller') {
            $viewName = substr($viewName, 0, -10);
        }
        $viewName .= 'View';
        if (class_exists($viewName)) {
            $this->view = new $viewName();
        }
    }

    public function __destruct() {
        if (!$this->internal && !is_null($this->view)) {
            echo $this->view->render();
        }
    }

    public function run() {
        return $this->internal ? $this->internal_values : true;
    }

    public function write($key, $value) {
        if ($this->internal) {
            $ret = array_key_exists($key, $this->internal_values) ? true : 1;
            try {
                $this->internal_values[$key] = $value;
            } catch (Exception $e) {
                $ret = false;
            }
            return $ret;
        } else {
            parent::write($key, $value);
        }
    }

    protected function redirect($url) {
        $this->view = null;
        Utils::redirect($url);
        die;
    }
}
