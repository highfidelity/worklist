<?php

class PasswordView extends View {
    public $layout = 'NewWorklist';
    public $title = 'Change Password - Worklist';
    public $stylesheets = array(); // no specific styling
    public $scripts = array('js/password.js');

    public function render() {
        $this->msg = $this->read('msg');
        return parent::render();
    }
}
