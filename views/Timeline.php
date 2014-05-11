<?php

class TimelineView extends View {
	public $title = '';

	public $stylesheets = array(
		'css/timeline.css'
	);

	public $scripts = array(
        'https://maps.googleapis.com/maps/api/js?v=3&sensor=false',
        'js/spin.js',
        'js/markerclusterer/markerclusterer.js',
        'js/timeline.js'
	);

	public function render() {
		$this->layout = new EmptyBodyLayout();
		return parent::render();
	}
}
