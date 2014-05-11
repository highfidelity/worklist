<?php

class ResendView extends View {
    public $title = 'Recover Password - Worklist';

    public $stylesheets = array(
        'css/legacy/worklist.css'
    );

    public $scripts = array(
        'js/resend.js'
    );

    public function render() {
        $this->msg = $this->read('msg');
        return parent::render();
    }
}
