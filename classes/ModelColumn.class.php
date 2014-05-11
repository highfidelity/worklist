<?php

class ModelColumn extends AppObject {
	public $name = '';
	public $type = null;
	public $touched = false;
	protected $value = null;

	public function __construct($name, $type)
	{
		//parent::__construct();
		$this->name = $name;
		$this->type = $type;
		$this->touched = false;
	}

	public function __get($name)
	{
		if ($name == 'value') {
			return $this->value;
		}
	}

	public function __set($name, $value)
	{
		if ($name == 'value') {
			$this->value = $value;
			$this->touched = true;
		}
	}

	public function reset() {
		$this->touched = false;
		$this->value = null;
	}
}
