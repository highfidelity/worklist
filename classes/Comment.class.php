<?php
//  Copyright (c) 2010, LoveMachine Inc.
//  All Rights Reserved.
//  http://www.lovemachineinc.com

//  This class handles a Comment if you need more functionality don't hesitate to add it.
//  But please be as fair as you comment your (at least public) methods - maybe another developer
//  needs them too.

class Comment
{
	/**
	 * Identication of the comment
	 * @var integer
	 */
	protected $id;

	/**
	 * Parents identication
	 * @var integer
	 */
	protected $comment_id;

	/**
	 * Workitem identication
	 * @var integer
	 */
	protected $worklist_id;

	/**
	 * The User
	 * @var integer
	 */
	protected $user_id;

	/**
	 * The User Object
	 * @var User
	 */
	protected $user;

	/**
	 * Date of the comment
	 * @var integer
	 */
	protected $date;

	/**
	 * Comment
	 * @var string
	 */
	protected $comment;

	/**
	 * Avatar
	 * @var string
	 */
	protected $avatar;

	/**
	 * @return the $id
	 */
	public function getId()
	{
		return $this->id;
	}

	/**
	 * @param $id the $id to set
	 */
	public function setId($id)
	{
		$this->id = $id;
		return $this;
	}

	/**
	 * @return the $pid
	 */
	public function getComment_id()
	{
		return $this->comment_id;
	}

	/**
	 * @param $pid the $pid to set
	 */
	public function setComment_id($comment_id)
	{
		$this->comment_id = $comment_id;
		return $this;
	}

	/**
	 * @return the $wid
	 */
	public function getWorklist_id()
	{
		return $this->worklist_id;
	}

	/**
	 * @param $wid the $wid to set
	 */
	public function setWorklist_id($worklist_id)
	{
		$this->worklist_id = $worklist_id;
		return $this;
	}

	/**
	 * @return the $uid
	 */
	public function getUser_id()
	{
		return $this->user_id;
	}

	/**
	 * @param $uid the $uid to set
	 */
	public function setUser_id($user_id)
	{
		$this->user_id = $user_id;
		return $this;
	}

	/**
	 * @return the $user
	 */
	public function getUser()
	{
		if (null === $this->user) {
			$this->setUser();
		}
		return $this->user;
	}

	/**
	 * @param $uid the $user to set
	 */
	public function setUser()
	{
		$user = new User();
		$user->findUserById($this->getUser_id());
		$this->user = $user;
		return $this;
	}

	/**
	 * @return the $date
	 */
	public function getDate()
	{
		return $this->date;
	}

    public function getRelativeDate() {
        return relativeTime(strtotime($this->date) - time());
    }

	/**
	 * @param $date the $date to set
	 */
	public function setDate($date)
	{
		$this->date = $date;
		return $this;
	}

	/**
	 * @return the $comment
	 */
	public function getComment()
	{
		return $this->comment;
	}

    public function getCommentWithLinks() {
        return linkify($this->comment);
    }

	/**
	 * @param $comment the $comment to set
	 */
	public function setComment($comment)
	{
		$this->comment = trim($comment);
		return $this;
	}

	/**
	 * @return the $avatar
	 */
	public function getAvatar()
	{
		if ($this->avatar === null) {
			$this->setAvatar();
		}
		return $this->avatar;
	}

	/**
	 * Retrieves the url to the avatar
	 */
	public function setAvatar()
	{
		defineSendloveAPI();

		$params = array(
			'action' => 'getProfilePicture',
            'api_key' => SENDLOVE_API_KEY,
			'username' => $this->getUser()->getUsername(),
			'width' => 50,
			'height' => 50
		);

		$referer = (empty($_SERVER['HTTPS']) ? 'http://' : 'https://') . $_SERVER['SERVER_NAME'] . $_SERVER['PHP_SELF'];
    	$retval = json_decode(postRequest(SENDLOVE_API_URL, $params, array(CURLOPT_REFERER => $referer)), true);

    	$this->avatar = false;
    	if ($retval['success'] == true) {
    		$this->avatar = $retval['picture'];
    	}

    	return $this;
	}

    /**
     * With this constructor you can create a user by passing an array.
     *
     * @param array $options
     * @return Comment $this
     */
    public function __construct(array $options = null)
    {
        if (is_array($options)) {
            $this->setOptions($options);
        }
    }

	/**
	 * Use this method to update or insert a comment.
	 *
	 * @return (boolean)
	 */
	public function save()
	{
		if (!$this->validate()) {
			throw new Exception('Comment is not valid!');
		}
		if (null === $this->getId()) {
			$id = $this->insert();
			if ($id !== false) {
				$this->setId($id);
				return $id;
			}
			return false;
		} else {
			return $this->update();
		}
	}

    /**
     * This method finds a file by its id
     *
     * @param int $id
     * @return File $this
     */
    public function findCommentById($id = null)
    {
    	if (null === $id) {
    		throw new Exception('Now comment id defined!');
    	}
    	$where = sprintf('`id` = %d', (int)$id);
		return $this->loadComment($where);
    }

    public static function findCommentsForWorkitem($id = null)
    {
    	if (null === $id) {
    		throw new Exception('No workitem defined!');
    	}
    	$sql = 'SELECT `id` FROM `' . COMMENTS . '` WHERE `comment_id` IS NULL AND `worklist_id` = ' . (int)$id . ' ORDER BY `date` ASC';
    	$result = mysql_query($sql);
    	$list = array();
    	while ($row = mysql_fetch_assoc($result)) {
			$list[] = $row['id'];
    	}
    	$comments = self::getCommentsRecursive($list, 0);
    	return $comments;
    }

    public static function getCommentsRecursive($list, $depth)
    {
    	$_list = array();
    	foreach ($list as $comment) {
    		$commentObject = new Comment();
    		$commentObject->findCommentById($comment);
    		$_list[] = array(
    			'id' => $comment,
    			'depth' => $depth,
    			'comment' => $commentObject
    		);
    		$children = self::getCommentsRecursive(self::getChildCommentsById($comment), $depth + 1);
    		foreach ($children as $child) {
    			$_list[] = $child;
    		}
    	}
    	return $_list;
    }

    public static function getChildCommentsById($id)
    {
    	$sql = 'SELECT `id` FROM `' . COMMENTS . '` WHERE `comment_id` = ' . (int)$id . ' ORDER BY `date` ASC';
    	$result = mysql_query($sql);
    	$comments = array();
    	while ($row = mysql_fetch_assoc($result)) {
    		$comments[] = $row['id'];
    	}
    	return $comments;
    }

    /**
     * Checks if the setter for the property exists and calls it
     *
     * @param string $name Name of the property
     * @param string $value Value of the property
     * @throws Exception
     * @return void
     */
    public function __set($name, $value)
    {
        $method = 'set' . ucfirst($name);
        if (!method_exists($this, $method)) {
            throw new Exception('Invalid ' . __CLASS__ . ' property');
        }
        $this->$method($value);
    }

    /**
     * Checks if the getter for the property exists and calls it
     *
     * @param string $name Name of the property
     * @param string $value Value of the property
     * @throws Exception
     * @return void
     */
    public function __get($name)
    {
        $method = 'get' . ucfirst($name);
        if (!method_exists($this, $method)) {
            throw new Exception('Invalid ' . __CLASS__ . ' property');
        }
        $this->$method();
    }

    /**
     * Automatically sets the options array
     * Array: Name => Value
     *
     * @param array $options
     * @return User $this
     */
	private function setOptions(array $options)
	{
        $methods = get_class_methods($this);
        foreach ($options as $key => $value) {
            $method = 'set' . ucfirst($key);
            if (in_array($method, $methods)) {
                $this->$method($value);
            }
        }
        return $this;
	}

	/**
	 * Function to load a comment from the database.
	 */
	private function loadComment($where)
	{
		// now we build the sql query
		$sql = 'SELECT * FROM `' . COMMENTS . '` WHERE ' . $where . ' LIMIT 1;';
		// and get the result
		$result = mysql_query($sql);

		if ($result && (mysql_num_rows($result) == 1)) {
			$options = mysql_fetch_assoc($result);
			$this->setOptions($options);
			return $this;
		}
		return false;
	}

	private function getCommentColumns()
	{
		$columns = array();
		$result = mysql_query('SHOW COLUMNS FROM `' . COMMENTS);
		if (mysql_num_rows($result) > 0) {
		    while ($row = mysql_fetch_assoc($result)) {
		        $columns[] = $row;
		    }
			return $columns;
		}
		return false;
	}

	private function prepareData()
	{
		$columns = $this->getCommentColumns();
		$cols = array(); $values = array();
		foreach ($columns as $col) {
			$method = 'get' . ucfirst($col['Field']);
			if (method_exists($this, $method) && (null !== $this->$method())) {
				$cols[] = $col['Field'];
				if (preg_match('/(char|text|blob)/i', $col['Type']) === 1) {
					$values[] = mysql_real_escape_string($this->$method());
				} else {
					$values[] = $this->$method();
				}
			}
		}
		return array(
			'columns' => $cols,
			'values' => $values
		);
	}

	private function insert()
	{
		$data = $this->prepareData();
		$sql = 'INSERT INTO `' . COMMENTS . '` (`' . implode('`,`', $data['columns']) . '`) VALUES ("' . implode('","', $data['values']) . '")';
		$result = mysql_query($sql);
		if ($result) {
			return mysql_insert_id();
		}
		return false;
	}

	private function validate()
	{
		$valid = false;
		if ((null !== $this->worklist_id) && (null !== $this->user_id) && (null !== $this->comment) && (!empty($this->comment))) {
			$valid = true;
		}
		return $valid;
	}

	private function update()
	{
		$flag = false;
		$data = $this->prepareData();
		$sql = 'UPDATE `' . COMMENTS . '` SET ';
		foreach ($data['columns'] as $index => $column) {
			if ($column == 'id') {
				continue;
			}
			if ($flag === true) {
				$sql .= ', ';
			}
			$sql .= '`' . $column . '` = "' . $data['values'][$index] . '"';
			$flag = true;
		}
		$sql .= ' WHERE `id` = ' . (int)$this->getId() . ';';
		$result = mysql_query($sql);
		if ($result) {
			return true;
		}
		return false;
	}

	/**
	 * inserts What on position of where
	 * notice that Position is the index of the array, so it starts with 0!
	 * notice further that when using an array the values will be merged, so when inserting an array that itself should
	 * stay intact use array(array('your','untouched','values','here'))
	 *
	 * @author Rene Stephan Dettelbacher
	 * @param array $Where - the array where to insert a value
	 * @param integer $Position - the position in the array where $What should be inserted, starting with 0!
	 * @param mixed $What - a basic datatype or an array
	 * @return array - the modified array
	 */
	public static function array_insert(array $Where, $Position, $What) {
		if ($Position === 0) {
			if (is_array($What)) {
				$What = array_reverse($What);
				foreach ($What as $value) {
					array_unshift($Where, $value);
				}
			} else {
				array_unshift($Where, $What);
			}
			return $Where;
		} else if ($Position > count($Where)) {
			if (is_array($What)) {
				return array_merge($Where, $What);
			} else {
				$Where[] = $What;
				return $Where;
			}
		} else {
			$whereFirst = array_slice($Where, 0, $Position);
			if (is_array($What)) {
				$whereFirst = array_merge($whereFirst, $What);
			} else {
				$whereFirst[] = $What;
			}
			return array_merge($whereFirst, array_slice($Where, $Position));
		}
	}

}
