<?php

use \Mustache\Mustache;

class Layout Extends AppObject {
	public $name = '';
	public $stylesheets = array();
	public $scripts = array();

    public function __construct() {
        $this->name = strtolower(preg_replace('/Layout$/', '', get_class($this)));
	}

    public function render($view) {
    	if (!is_subclass_of($this, 'Layout')) {
    		return;
    	}
    	$name = $this->name;
        $base = VIEWS_DIR . DIRECTORY_SEPARATOR . 'layout' . DIRECTORY_SEPARATOR . 'mustache';
        $partials = VIEWS_DIR . DIRECTORY_SEPARATOR . 'mustache' . DIRECTORY_SEPARATOR . 'partials';
        $mustache = new Mustache_Engine(array(
            'loader' => new Mustache_Loader_FilesystemLoader($base),
            'partials_loader' => new Mustache_Loader_FilesystemLoader($partials)
        ));
        $template = $mustache->loadTemplate($this->name);
        return $template->render($view);
    }
}
