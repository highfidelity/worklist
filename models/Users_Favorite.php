<?php
/**
 * Copyright (c) 2014, High Fidelity Inc.
 * All Rights Reserved.
 *
 * http://highfidelity.io
 */

require_once('models/DataObject.php');
require_once('classes/User.class.php');

class Users_Favorite extends DataObject {
    public $user_id;
    public $favorite_user_id;
    public $enabled;

    public $table_name;

    /**
     * Constructor
     */
    public function __construct() {
        parent::__construct();

        $this->table_name = USERS_FAVORITES;
    }

    /**
     * Destructor
     */
    public function __destruct() {
        parent::__destruct();
    }

    /* return the number of people who have this user as a favourite */
    public function getUserFavoriteCount($userid) {
        $userid = intval($userid);
        $count = $this->count(" " . USERS_FAVORITES . ".favorite_user_id={$userid} AND " . USERS_FAVORITES . ".enabled=1");
        return $count;
    }

    public function getUserFavoriteData($user_id) {
        $user_id = (int) $user_id;
        $favoriteArray = $this->dbFetchArray(" " . USERS_FAVORITES . ".user_id={$user_id} AND " . USERS_FAVORITES . ".enabled = 1");
        $userData = array();
        foreach ($favoriteArray as $favorite) {
            $user = new User();
            $user->loadById($favorite['favorite_user_id']);
            $userData[] = $user;
        }
        return $userData;
    }

    /* return an array with ids of $user_id's favorite users */
    public function getFavoriteUsers($user_id) {
        $user_id = (int)$user_id;
        $favoriteArray = $this->dbFetchArray(" " . USERS_FAVORITES . ".user_id={$user_id} AND " . USERS_FAVORITES . ".enabled = 1");
        $userData = array();
        foreach ($favoriteArray as $favorite) {
            $user = new User();
            $user->findUserById($favorite['favorite_user_id']);
            $userData[] = $user->getId();
        }
        return $userData;
    }

    public function getMyFavoriteForUser($my_userid, $userid) {
        $my_userid = intval($my_userid);
        $userid = intval($userid);
        $objectData = $this->dbFetchArray(" " . USERS_FAVORITES . ".user_id={$my_userid} AND " . USERS_FAVORITES . ".favorite_user_id={$userid} ",true);

        $ret = array();
        $ret['favorite'] = 0;
        $ret['record'] = false;
        if($objectData != null){
            if (isset($objectData['error'])) {
                $ret['error'] = $objectData['error'];
            } else {
                $length=count($objectData['data']);
                if ($length > 0) {
                    $ret['favorite'] = $objectData['data'][0]['enabled'];
                    $ret['record'] = true;
                }
            }
        } else {
            $ret['error'] = "dbFetchArray returns null value";
        }
        return $ret;
    }

    public function setMyFavoriteForUser($my_userid, $userid, $favorite) {
        $my_userid = intval($my_userid);
        $userid = intval($userid);
        $favorite = intval($favorite);

        // make sure the user isn't favoriting himself/herself
        if ($my_userid == $userid) {
            return "You cannot be a favorite of yourself!";
        }

        $current = $this->getMyFavoriteForUser($my_userid, $userid);
        if ( isset($current['error']) ) {
            return $current['error'];
        }
        if ( isset($current['record']) && $current['record'] == true ) {
            $sql = "UPDATE " . $this->table_name . " SET  enabled = {$favorite}
                        WHERE favorite_user_id = {$userid} AND user_id = {$my_userid};";
        } else {
            $sql = "INSERT INTO " . $this->table_name . " (user_id, favorite_user_id, enabled) VALUES
                        ({$my_userid}, {$userid}, {$favorite});";
        }
        if ($this->dbUpdate($sql)!== true) {
            $this->handleError($this->link->error, $sql);
        }
        return "";
    }

    public function insertNewSkill($values) {
        return $this->dbInsert($values);
    }

    public function updateSkill($sql) {
        return $this->dbUpdate($sql);
    }
}
?>
