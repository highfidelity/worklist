<?php
//
//  Copyright (c) 2011, LoveMachine Inc.
//  All Rights Reserved.
//  http://www.lovemachineinc.com
//
require_once('FundException.php');

/*
**
 * Fund
 *
 * @package Fund
 * @version $Id$
 */
class Fund{
    protected $fund_id;
    protected $name;
    protected $pp_enabled;
    protected $pp_login_email;
    protected $pp_API_password;
    protected $pp_API_key;

    public function __construct($id = null) {
        if (!mysql_connect(DB_SERVER, DB_USER, DB_PASSWORD)) {
            throw new Exception('Error: ' . mysql_error());
        }
        if (!mysql_select_db(DB_NAME)) {
            throw new Exception('Error: ' . mysql_error());
        }
        if ($id !== null) {
            $this->load($id);
        }
    }

    static public function getById($fund_id) {
        $fund= new Fund();
        $fund->loadById($fund_id);
        return $fund;
    }

    public function loadById($id) {
        return $this->load($id);
    }

    protected function load($fund_id = null) {

        if ($fund_id === null && ! $this->fund_id) {
            throw new Fund_Exception('Missing fund id.');
        } elseif ($fund_id === null) {
            $fund_id = $this->fund_id;
        }

        $query = "
            SELECT f.id, f.name, f.pp_enabled, f.pp_login_email, f.pp_API_password, f.pp_API_key
            FROM  " . FUNDS . " as f
            WHERE f.id = " . (int) $fund_id;
        $res = mysql_query($query);

        if (! $res) {
            throw new Fund_Exception('MySQL error.');
        }

        $row = mysql_fetch_assoc($res);
        if (! $row) {
            throw new Fund_Exception('Invalid fund id.');
        }

        $this->setFundId($row['id'])
             ->setName($row['name'])
             ->setPPEnabled($row['pp_enabled'])
             ->setPPLoginEmail($row['pp_login_email'])
             ->setPPAPIPassword($row['pp_API_password'])
             ->setPPAPIKey($row['pp_API_key']);
        return true;
    }

    public function idExists($fund_id) {
        $query = "
            SELECT id
            FROM " . FUNDS . "
            WHERE id = " . (int) $fund_id;

        $res = mysql_query($query);
        if (!$res) {
            throw new Fund_Exception('MySQL error.');
        }
        $row = mysql_fetch_row($res);
        return (boolean) $row[0];
    }

    public function setFundId($fund_id) {
        $this->fund_id = (int) $fund_id;
        return $this;
    }

    public function getFundId() {
        return $this->fund_id;
    }

    public function setName($name) {
        $this->name = $name;
        return $this;
    }

    public function getName() {
        return $this->name;
    }

    public function setPPEnabled($pp_enabled) {
        $this->pp_enabled = (int) $pp_enabled;
        return $this;
    }

    public function getPPEnabled() {
        return $this->pp_enabled;
    }

    public function setPPLoginEmail($pp_login_email) {
        $this->pp_login_email = $pp_login_email;
        return $this;
    }

    public function getPPLoginEmail() {
        return $this->pp_login_email;
    }

    public function setPPAPIPassword($pp_API_password) {
        $this->pp_API_password = $pp_API_password;
        return $this;
    }

    public function getPPAPIPassword() {
        return $this->pp_API_Password;
    }

    public function setPPAPIKey($pp_API_key) {
        $this->pp_API_key = $pp_API_key;
        return $this;
    }

    public function getPPAPIKey() {
        return $this->pp_API_key;
    }

    public function getFunds() {
        $query = "
            SELECT *
            FROM `" . FUNDS . "`
            ORDER BY `name`";

        $result = mysql_query($query);

        if (mysql_num_rows($result)) {
            $funds = array();
            while ($fund = mysql_fetch_assoc($result)) {
                $funds[$fund['id']] = $fund;
            }
            return $funds;
        }
        return false;
    }

}