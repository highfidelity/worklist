<?php
//  Copyright (c) 2010, LoveMachine Inc.
//  All Rights Reserved.
//  http://www.lovemachineinc.com

//  This class handles a Json Requests if you need more functionality don't hesitate to add it.
//  But please be as fair as you comment your methods - maybe another developer needs them too.

class JsonServer_Request
{
	/**
	 * Has the action been dispatched?
	 * @var boolean
	 */
	protected $_dispatched = false;

	/**
	 * Action
	 * @var string
	 */
	protected $_action;

	/**
	 * Action key for retrieving action from params
	 * @var string
	 */
	protected $_actionKey = 'action';

	/**
	 * Request parameters
	 * @var array
	 */
	protected $_params = array();

	/**
	 * Retrieve the action name
	 *
	 * @return string
	 */
	public function getActionName()
	{
		if (null === $this->_action) {
			$this->_action = $this->getParam($this->getActionKey());
		}

		return $this->_action;
	}

	/**
	 * Set the action name
	 *
	 * @param string $value
	 * @return JsonServer_Request
	 */
	public function setActionName($value)
	{
		$this->_action = $value;

		if (null === $value) {
			$this->setParam($this->getActionKey(), $value);
		}
		return $this;
	}

	/**
	 * Retrieve the action key
	 *
	 * @return string
	 */
	public function getActionKey()
	{
		return $this->_actionKey;
	}

	/**
	 * Set the action key
	 *
	 * @param string $key
	 * @return JsonServer_Request
	 */
	public function setActionKey($key)
	{
		$this->_actionKey = (string) $key;
		return $this;
	}

	/**
	 * Get an action parameter
	 *
	 * @param string $key
	 * @param mixed $default Default value to use if key not found
	 * @return mixed
	 */
	public function getParam($key, $default = null)
	{
		$key = (string) $key;
		if (isset($this->_params[$key])) {
			return $this->_params[$key];
		}

		return $default;
	}

	/**
	 * Set an action parameter
	 *
	 * A $value of null will unset the $key if it exists
	 *
	 * @param string $key
	 * @param mixed $value
	 * @return JsonServer_Request
	 */
	public function setParam($key, $value)
	{
		$key = (string) $key;

		if ((null === $value) && isset($this->_params[$key])) {
			unset($this->_params[$key]);
		} elseif (null !== $value) {
			$this->_params[$key] = $value;
		}

		return $this;
	}

	/**
	 * Get all action parameters
	 *
	 * @return array
	 */
	public function getParams()
	{
		return $this->_params;
	}

	/**
	 * Set action parameters en masse; does not overwrite
	 *
	 * Null values will unset the associated key.
	 *
	 * @param array $array
	 * @return JsonServer_Request
	*/
	public function setParams(array $array)
	{
		$this->_params = $this->_params + (array) $array;

		foreach ($this->_params as $key => $value) {
			if (null === $value) {
				unset($this->_params[$key]);
			}
		}

		return $this;
	}

	/**
	 * Set flag indicating whether or not request has been dispatched
	 *
	 * @param boolean $flag
	 * @return JsonServer_Request
	 */
	public function setDispatched($flag = true)
	{
		$this->_dispatched = $flag ? true : false;
		return $this;
	}

	/**
	 * Determine if the request has been dispatched
	 *
	 * @return boolean
	 */
	public function isDispatched()
	{
		return $this->_dispatched;
	}

	public function __construct()
	{
		$request = array_merge($_GET, $_POST);
		if (!isset($request[$this->getActionKey()])) {
			throw new Exception('No action set!');
		}
		$this->setActionName($request[$this->getActionKey()]);
		$request[$this->getActionKey()] = null;
		$this->setParams($request);
	}

}
