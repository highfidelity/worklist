<?php

class JsonView extends View {
    public $layout = null;

    public function __construct() {
        // no need to inherit constructor from parent class to initialize any data,
        // this view simply prints json encoded data passed from controllers
    }

    public function render() {
        $output = $this->read('output');
        return json_encode($output ? $output : array());
    }
}