<?php
//  Copyright (c) 2014, High Fidelity Inc.
//  All Rights Reserved.
//  http://www.lovemachineinc.com

//  This class handles a User if you need more functionality don't hesitate to add it.
//  But please be as fair as you comment your (at least public) methods - maybe another developer
//  needs them too.

class User {
    protected $id;
    protected $username;
    protected $password;
    protected $added;
    protected $budget;
    protected $nickname;
    protected $confirm;
    protected $confirm_string;
    protected $about;
    protected $contactway;
    protected $payway;
    protected $timezone;
    protected $w9_status;
    protected $w9_accepted;
    protected $first_name;
    protected $last_name;
    protected $is_runner;
    protected $is_payer;
    protected $is_active;
    protected $is_admin;
    protected $is_internal;
    protected $last_seen;
    protected $journal_nick;
    protected $is_guest;
    protected $int_code;
    protected $country;
    protected $city;
    protected $has_sandbox;
    protected $unixusername;
    protected $forgot_hash;
    protected $forgot_expire;
    protected $projects;
    protected $projects_checkedout;
    protected $filter;
    protected $avatar;
    protected $annual_salary;
    protected $picture;
    protected $manager;
    protected $referred_by;
    protected $paypal;
    protected $paypal_email;
    protected $paypal_verified;
    protected $paypal_hash;
    protected $bidding_notif;
    protected $review_notif;
    protected $self_notif;
    protected $has_W2;
    protected $findus;
    protected $sound_settings;

    /**
     * All about budget
     */
    protected $remainingFunds;
    protected $allocated;
    protected $submitted;
    protected $paid;
    protected $transfered;
    protected $allFees;
    protected $managed;

    private $auth_tokens = array();

    /**
     * With this constructor you can create a user by passing an array or a user id.
     *
     * @param mixed $options
     * @return User $this
     */
    public function __construct($options = null)
    {
        if (is_array($options)) {
            $this->setOptions($options);
        } else if (is_numeric($options) && $options) {
            $this->findUserById((int) $options);
        }
        return $this;
    }

    /**
     * This method tries to fetch a user by any expression.
     *
     * @param (mixed) $expr Expression, either User object, numbers for ids, email str (usernames) and non emails for nicknames
     * @return (mixed) Either the User or false.
     */
    public static function find($expr)
    {
        $user = new User();
        if (is_object($expr) && (get_class($expr) == 'User' || is_subclass_of($expr, 'User'))) {
            $user = $expr;
        } else {
            if (is_numeric($expr)) {
                 // id 
                $user->findUserById((int) $expr);
            } else {
                if (filter_var($expr, FILTER_VALIDATE_EMAIL)) {
                    // username
                    $user->findUserByUsername($expr);
                } else {
                    // nickname
                    $user->findUserByNickname($expr);
                }
            }
        }
        return $user;
    }

    /**
     * This method fetches a user by his id.
     *
     * @param (integer) $id Id
     * @return (mixed) Either the User or false.
     */
    public function findUserById($id)
    {
        $where = sprintf('`id` = %d', (int)$id);
        return $this->loadUser($where);
    }

    /**
     * This method fetches a user by his nickname.
     *
     * @param (string) $nick Nickname
     * @return (mixed) Either the User or false.
     */
    public function findUserByNickname($nick) {
        $nick = mysql_real_escape_string((string)$nick);
        $where = sprintf('`nickname` = "%s"', $nick);
        return $this->loadUser($where);
    }

    /**
     * This method fetches a user by his username.
     *
     * @param (string) $user Username
     * @return (mixed) Either the User or false.
     */
    public function findUserByUsername($user)
    {
        $user = mysql_real_escape_string((string)$user);
        $where = sprintf('`username` = "%s"', $user);
        return $this->loadUser($where);
    }

    public function findUserByPPUsername($paypal_email, $hash) {
        $paypal_email = mysql_real_escape_string((string) $paypal_email);
        $hash = mysql_real_escape_string((string) $hash);
        $where = sprintf('`paypal_email` = "%s" && `paypal_hash` = "%s"', $paypal_email, $hash);
        return $this->loadUser($where);
    }

    /**
     * TODO:
     * I'm not sure why the __get() overload isn't always being applied, but
     * the error log is showing entries like:
     * PHP Fatal error:  Call to undefined method User::isEligible() in \
     *      .../worklist/workitem.inc on line 1718
     * Determine the cause and fix properly.
     * -Alexi 2011-11-22
     */
    public function isEligible() {
        return $this->getIsEligible();
    }

    public function getIsEligible() {
        if ($this->getHas_W2()) {
            return true;
        }

        if ($this->isUsCitizen()) {
            // Quick and dirty fix to disable w9 verification - leo@lovemachineinc.com
            if (! $this->isW9Approved()) {
                return false;
            }
        }
    
        if ($this->isPaypalVerified()) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Use this method to update or insert a user.
     *
     * @return (boolean)
     */
    public function save() {
        if (null === $this->getId()) {
            $id = $this->insert();
            if ($id !== false) {
                $this->setId($id);
                return true;
            }
            return false;
        } else {
            return $this->update();
        }
    }

    /**
     * A method to check if this user is a US citizen.
     *
     * @return (boolean)
     */
    public function isUsCitizen() {
        if ($this->getCountry() === 'US') {
            return true;
        }
        return false;
    }

    /**
     * A method to check if this user has a W9 approval.
     *
     * @return (boolean)
     */
    public function isW9Approved() {
        if ($this->getW9_status() === 'approved') {
            return true;
        }
        return false;
    }

    /**
     * A method to check if this user is a Runner.
     *
     * @return (boolean)
     */
    public function isRunner()
    {
        if ((int)$this->getIs_runner() === 1) {
            return true;
        }
        return false;
    }

    /**
     * A method to check if this user is a payer.
     *
     * @return (boolean)
     */
    public function isPayer()
    {
        if ((int)$this->getIs_payer() === 1) {
            return true;
        }
        return false;
    }

    /**
     * A method to check if this user is an internal / hifi team member.
     *
     * @return (boolean)
     */
    public function isInternal()
    {
        if ((int) $this->getIs_internal() == 1) {
            return true;
        }

        return false;
    }

    /**
     * A method to check if this user is active or not.
     * Attention a user can also be secured and it would return false!
     *
     * @return (boolean)
     */
    public function isActive()
    {
        if ((int)$this->getIs_active() === 1) {
            return true;
        }
        return false;
    }
    
    /**
     * Authenticates against given password
     *
     * @param string $password Cleartext password
     *
     * @throws User_Exception
     * @return boolean
     */
    public function authenticate($password) {
        if (substr($this->getPassword(), 0, 7) == '{crypt}') {
            $encrypted = substr($this->getPassword(), 7);
            return ($encrypted == crypt($password, $encrypted));
        } else {
            return (sha1($password) == $this->getPassword());
        }
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
     * 
     * TODO: Determine if this is worth keeping
     * What value does this provide? If you try to access a property that
     * doesn't exist, you'll get an exception anyway. This also adds a layer
     * of confusion to developers who don't know that we've overridden
     * the -> operator and that they need to name their function
     * getWhatever(), but call it by a different name: $this->whatever().
     * -alexi 2011-11-22
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
    private function setOptions(array $options) {
        foreach ($options as $key => $value) {
            $method = 'set' . ucfirst($key);
            if (method_exists($this, $method)) {
                $this->$method($value);
            }
        }
        return $this;
    }

    /**
     * @return the $id
     */
    public function getId() {
        return $this->id;
    }
    
    public function getPicture() {
         return $this->picture;
    }
    
    public function setPicture($picture) {
         $this->picture = $picture;
    }

    /**
     * @param $id the $id to set
     */
    public function setId($id) {
        $this->id = $id;
    }

    /**
     * @return the $username
     */
    public function getUsername() {
        return $this->username;
    }

    /**
     * @param $username the $username to set
     */
    public function setUsername($username) {
        $this->username = $username;
        return $this;
    }

    /**
     * @return the $password
     */
    public function getPassword() {
        return $this->password;
    }

    /**
     * @param $password the $password to set
     */
    public function setPassword($password) {
        $this->password = $password;
        return $this;
    }

    /**
     * @return the $added
     */
    public function getAdded() {
        return $this->added;
    }

    /**
     * @param $added the $added to set
     */
    public function setAdded($added) {
        $this->added = $added;
        return $this;
    }

    public function getBudget() {
        return $this->budget;
    }

    public function setBudget($budget) {
        $this->budget = $budget;
        return $this;
    }
    
    public function updateBudget($amount, $budget_id = 0, $budgetDepletedMessage = true) {
        $budgetDepletedSent = false;
        if ($budget_id > 0) {
            $budget = new Budget();
            if ($budget->loadById($budget_id) ) {
                $remainingFunds = $budget->getRemainingFunds();
                $budget->remaining = $remainingFunds;
                $budget->save("id");
                if ($remainingFunds <= 0 && $budgetDepletedMessage == true) {
                    $runnerNickname = $this->getNickname();                    
                    $subject = "Depleted - Budget " . $budget_id . " (For " . $budget->reason . ")";
                    $link = SECURE_SERVER_URL . "team?showUser=".$this->getId() . "&tab=tabBudgetHistory";
                    $body  = '<p>Hi ' . $runnerNickname . '</p>';
                    $body .= "<p>Budget " . $budget_id . " for " . $budget->reason . "<br/> is now depleted.</p>";
                    $body .= '<p>If your budget has gone under 0.00, you will need to ask the user who ' .
                            'granted you the Budget to close out this budget for you.</p>';
                    $body .= '<p>To go to the Team Page, click <a href="' . $link . '">here</a></p>';
                    $body .= '<p>- Worklist.net</p>';               
                    $plain  = 'Hi ' . $runnerNickname . '\n\n';
                    $plain .= "Budget " . $budget_id . " for " . $budget->reason . "\n is now depleted.\n\n";
                    $plain .= 'If your budget has gone under 0.00, you will need to ask the user who ' .
                            'granted you the Budget to close out this budget for you.\n\n';
                    $plain .= 'To go to the Team Page, click ' . $link . "\n\n";
                    $plain .= '- Worklist.net\n\n';                
                    if (!send_email($this->getUsername(), $subject, $body, $plain)) { 
                        error_log("User.class.php: send_email failed on depleted Runner warning");
                    }
                    $budgetDepletedSent = true;
                }
            } else {
                error_log("User.class.php: send_email failed on depleted budget Runner warning - invalid budget id:" . $budget_id);
            }
        }
        
        $this->setBudget($this->setRemainingFunds());
        $this->save();
       
    }

    public function getRemainingFunds()
    {
        if (null === $this->remainingFunds) {
            $this->setRemainingFunds();
        }
        return $this->remainingFunds;
    }

    public function setRemainingFunds()
    {
        
        $this->remainingFunds = 0;
        $remaining = null;
        $remainingFunds = 0;

        $budget_filter = " AND " . WORKLIST . ".budget_id > 0 AND " . BUDGETS . ".id = " . WORKLIST . ".budget_id AND " .
            BUDGETS . ".active = 1  ";
        $budget_filter2 = " AND " . FEES . ".budget_id > 0 AND " . BUDGETS . ".id = " . FEES . ".budget_id AND " .
            BUDGETS . ".active = 1  ";

        $allocatedFunds = 0;
        $sql = 'SELECT SUM(`' . FEES . '`.`amount`) AS `allocated` FROM `' . FEES . '`, `' . WORKLIST . '`, `' . BUDGETS . '` WHERE `' . 
                WORKLIST . '`.`runner_id` = ' . $this->getId() . ' AND `' . FEES . '`.`worklist_id` = `' . 
                WORKLIST . '`.`id` AND `' . WORKLIST . '`.`status` IN ("In Progress", "QA Ready", "Review", "Merged") AND `' .
                FEES . '`.`withdrawn` != 1 ' . $budget_filter;
        $result = mysql_query($sql);
        if ($result && (mysql_num_rows($result) == 1)) {
            $row = mysql_fetch_assoc($result);
            $allocatedFunds = $row['allocated'];
        }

        $submittedFunds = 0;
        $sql = 'SELECT SUM(`' . FEES . '`.`amount`) AS `submitted` FROM `' . FEES . '`, `' . WORKLIST . '`, `' . BUDGETS . '` WHERE `' . 
                WORKLIST . '`.`runner_id` = ' . $this->getId() . ' AND `' . FEES . '`.`worklist_id` = `' . WORKLIST . 
                '`.`id` AND `' . WORKLIST . '`.`status` IN ("Done") AND `' . FEES . '`.`paid` = 0 AND `' . FEES . '`.`withdrawn` != 1 ' . $budget_filter;
        $result = mysql_query($sql);
        if ($result && (mysql_num_rows($result) == 1)) {
            $row = mysql_fetch_assoc($result);
            $submittedFunds = $row['submitted'];
        }
        $sql = 'SELECT SUM(`' . FEES . '`.`amount`) AS `submitted` FROM `' . FEES . '`, `' . BUDGETS . '` WHERE `' . 
                FEES . '`.`payer_id` = ' . $this->getId() . ' AND `' . FEES . '`.`worklist_id` = 0 AND `' . FEES . '`.`paid` = 0 AND `' . FEES . '`.`withdrawn` != 1 ' . $budget_filter2;
        $result = mysql_query($sql);
        if ($result && (mysql_num_rows($result) == 1)) {
            $row = mysql_fetch_assoc($result);
            $submittedFunds = $submittedFunds + $row['submitted'];
        }
        
        $paidFunds = 0;
        $sql = 'SELECT SUM(`' . FEES . '`.`amount`) AS `paid` FROM `' . FEES . '`, `' . WORKLIST . '`, `' . BUDGETS . '` WHERE `' . 
                WORKLIST . '`.`runner_id` = ' . $this->getId() . ' AND `' . FEES . '`.`worklist_id` = `' . WORKLIST . 
                '`.`id` AND `' . WORKLIST . '`.`status` IN ("Done") AND `' . FEES . '`.`paid` = 1 AND `' . FEES . '`.`withdrawn` != 1 ' . $budget_filter;
        $result = mysql_query($sql);
        if ($result && (mysql_num_rows($result) == 1)) {
            $row = mysql_fetch_assoc($result);
            $paidFunds = $row['paid'];
        }
        $sql = 'SELECT SUM(`' . FEES . '`.`amount`) AS `paid` FROM `' . FEES . '`, `' . BUDGETS . '` WHERE `' . 
                FEES . '`.`payer_id` = ' . $this->getId() . ' AND `' . FEES . '`.`worklist_id` = 0 AND `' . FEES . '`.`paid` = 1 AND `' . FEES . '`.`withdrawn` != 1 ' . $budget_filter2;
        $result = mysql_query($sql);
        if ($result && (mysql_num_rows($result) == 1)) {
            $row = mysql_fetch_assoc($result);
            $paidFunds = $paidFunds + $row['paid'];
        }
        
        $transferedFunds = 0;
        $sql = 'SELECT SUM(s.`amount_granted`) AS `transfered` FROM ' . BUDGET_SOURCE . " AS s " .
                "INNER JOIN " . BUDGETS . " AS b ON s.budget_id = b.id  AND b.active = 1 " .
                ' WHERE s.`giver_id` = ' . $this->getId() ;
        $result = mysql_query($sql);
        if ($result && (mysql_num_rows($result) == 1)) {
            $row = mysql_fetch_assoc($result);
            $transferedFunds = $row['transfered'];
        }
        
        $receivedFunds = 0;
        $sql = 'SELECT SUM(`' . BUDGETS . '`.`amount`) AS `received` FROM `' . BUDGETS . '` WHERE `' . 
                BUDGETS . '`.`receiver_id` = ' . $this->getId() . " AND " . BUDGETS . ".active = 1  ";
        $result = mysql_query($sql);
        if ($result && (mysql_num_rows($result) == 1)) {
            $row = mysql_fetch_assoc($result);
            $receivedFunds = $row['received'];
        }
        
        $remainingFunds = 0;
        $sql = 'SELECT SUM(`' . BUDGETS . '`.`remaining`) AS `remaining` FROM `' . BUDGETS . '` WHERE `' . 
                BUDGETS . '`.`receiver_id` = ' . $this->getId() . " ";
        $result = mysql_query($sql);
        if ($result && (mysql_num_rows($result) == 1)) {
            $row = mysql_fetch_assoc($result);
            $remainingFunds = $row['remaining'];
        }

        $this->setAllocated($allocatedFunds);
        $this->setSubmitted($submittedFunds);
        $this->setPaid($paidFunds);
        $this->setTransfered($transferedFunds);
        $remaining = $receivedFunds - $allocatedFunds - $submittedFunds - $paidFunds - $transferedFunds;
        $this->remainingFunds = $this->getBudget();

        return $remainingFunds;
    }

    public function getTransfered()
    {
        if (null === $this->transfered) {
            $this->setTransfered(0);
        }
        return $this->transfered;
    }

    public function getAllocated()
    {
        if (null === $this->allocated) {
            $this->setAllocated(0);
        }
        return $this->allocated;
    }

    public function setAllocated($value)
    {
        $this->allocated = $value;
        return $this;
    }

    public function setTransfered($value)
    {
        $this->transfered = $value;
        return $this;
    }

    public function getSubmitted()
    {
        if (null === $this->submitted) {
            $this->setSubmitted(0);
        }
        return $this->submitted;
    }

    public function setSubmitted($value)
    {
        $this->submitted = $value;
        return $this;
    }

    public function getPaid()
    {
        if (null === $this->paid) {
            $this->setPaid(0);
        }
        return $this->paid;
    }

    public function setPaid($value)
    {
        $this->paid = $value;
        return $this;
    }

    public function getActiveBudgets()
    {
        // Query to get User's Budget entries
        $query =  ' SELECT amount, remaining, reason, id '
                . ' FROM ' . BUDGETS 
                . ' WHERE receiver_id = ' . $this->getId()
                . ' AND active = 1 '
                . ' ORDER BY id DESC ';
        $result = mysql_query($query);
        $ret = "";
        if ($result) {
            while ($row = mysql_fetch_assoc($result)) {
                $ret[] = $row;
            }
        }
        return $ret;
    }

    public function getBudgetCombo($budget_id = 0)
    {
        $userid = isset($_SESSION['userid']) ?  $_SESSION['userid'] : 0;
// Query to get User's Budget entries
        $query =  ' SELECT amount, remaining, reason, id '
                . ' FROM ' . BUDGETS 
                . ' WHERE receiver_id = ' . $userid
                . ' AND active = 1 '
                . ' ORDER BY id DESC ';
        $result = mysql_query($query);
        $ret = "";
        if ($result) {
            while ($row = mysql_fetch_assoc($result)) {
                if (isset($budget_id) && $budget_id == $row['id']) {
                    $selected = "selected='selected'";
                } else {
                    $selected = "";
                }
                $ret .= '<option value="' . $row['id'] . '" ' . $selected . ' data-amount="' . $row['remaining'] . '">' . 
                        $row['reason'] . ' ($' . $row['remaining'] . ")</option>\n";
            }
        }
        return $ret;
    }
   
    public function getTotalManaged() {
        $sql = 'SELECT SUM(`amount`) AS `managed`
                FROM `' . BUDGETS . '`
                WHERE `receiver_id` = ' . $_SESSION['userid'] . ' ';

        $res = mysql_query($sql);
            if ($res && $row = mysql_fetch_assoc($res)) {
                return $row['managed'];
            }
        return false;
    }
    /**
     * @return the $nickname
     */
    public function getNickname() {
        return $this->getSubNickname($this->nickname);
    }

    /**
     * @param $nickname the $nickname to set
     */
    public function setNickname($nickname) {
        $this->nickname = $nickname;
        return $this;
    }

    /**
     * @return the $nickname
     */
    public function getCity() {
        return $this->city;
    }

    /**
     * @param $nickname the $nickname to set
     */
    public function setCity($city) {
        $this->city = $city;
        return $this;
    }

    /**
     * @return the $confirm
     */
    public function getConfirm() {
        return $this->confirm;
    }

    /**
     * @param $confirm the $confirm to set
     */
    public function setConfirm($confirm) {
        $this->confirm = $confirm;
        return $this;
    }

    /**
     * @return the $confirm_string
     */
    public function getConfirm_string() {
        return $this->confirm_string;
    }

    /**
     * @param $confirm_string the $confirm_string to set
     */
    public function setConfirm_string($confirm_string) {
        $this->confirm_string = $confirm_string;
        return $this;
    }

    public function getForgot_hash() {
        return $this->forgot_hash;
    }

    /**
     * @param $token
     */
    public function setForgot_hash($token) {
        $this->forgot_hash = $token;
        return $this;
    }

    /**
     * @return the $about
     */
    public function getAbout() {
        return $this->about;
    }

    /**
     * @param $about the $about to set
     */
    public function setAbout($about) {
        $this->about = $about;
        return $this;
    }

    public function getSystems() {
        $system = new UserSystemModel();
        return $system->getUserSystems($this->getId());
    }

    public function getSystemsCount() {
        $system = new UserSystemModel();
        return $system->numberOfUserSystems($this->getId());
    }

    /**
     * @return the $findus
     */
    public function getFindus() {
        return $this->findus;
    }

    /**
     * @param $findus to set
     */
    public function setFindus($findus) {
        $this->findus = $findus;
        return $this;
    }
    
    /**
     * @return the $sound_settings
     */
    public function getSound_settings() {
        return $this->sound_settings;
    }

    /**
     * @param $sound_settings to set
     */
    public function setSound_settings($sound_settings) {
        $this->sound_settings = $sound_settings;
        return $this;
    }

    /**
     * @return the $contactway
     */
    public function getContactway() {
        return $this->contactway;
    }

    /**
     * @param $contactway the $contactway to set
     */
    public function setContactway($contactway) {
        $this->contactway = $contactway;
        return $this;
    }

    /**
     * @return the $payway
     */
    public function getPayway() {
        return $this->payway;
    }

    /**
     * @param $payway the $payway to set
     */
    public function setPayway($payway) {
        $this->payway = $payway;
        return $this;
    }

    /**
     * @return the $timezone
     */
    public function getTimezone() {
        return $this->timezone;
    }

    /**
     * @param $timezone the $timezone to set
     */
    public function setTimezone($timezone) {
        $this->timezone = $timezone;
        return $this;
    }

    public function getW9_status() {
        return $this->w9_status;
    }

    public function setW9_status($status) {
        $this->w9_status = $status;
        return $this;
    }
    
    /**
     * @return the $w9_accepted
     */
    public function getW9_accepted() {
        return $this->w9_accepted;
    }

    /**
     * @param $w9_accepted the $w9_accepted to set
     */
    public function setW9_accepted($w9_accepted) {
        $this->w9_accepted = $w9_accepted;
        return $this;
    }
    
    /**
     * @return the $first_name
     */
    public function getFirst_name() {
        return $this->first_name;
    }

    /**
     * @param $first_name the $first_name to set
     */
    public function setFirst_name($first_name) {
        $this->first_name = $first_name;
        return $this;
    }    

    /**
     * @return the $last_name
     */
    public function getLast_name() {
        return $this->last_name;
    }

    /**
     * @param $last_name the $last_name to set
     */
    public function setLast_name($last_name) {
        $this->last_name = $last_name;
        return $this;
    }

    /**
     * @param $gitHubId
     * @return bool if user has authorized the app with github, false otherwise
     */
    public function isGithub_connected($gitHubId = GITHUB_OAUTH2_CLIENT_ID) {
        $userId = getSessionUserId();
        if ($userId == 0) {
            return false;
        }

        $sql = "SELECT COUNT(*) AS count FROM `" . USERS_AUTH_TOKENS . "`
                WHERE user_id = " . (int)$userId . " AND github_id = '" . mysql_real_escape_string($gitHubId) . "'";

        $result = mysql_query($sql);
        if ($result && mysql_num_rows($result) > 0) {
            $row = mysql_fetch_assoc($result);
            return (int)$row['count'] > 0;
        } else {
            return false;
        }
    }

    /**
     * @return the $is_active
     */
    public function getIs_active() {
        return $this->is_active;
    }

    /**
     * @param $is_active the $is_active to set
     */
    public function setIs_active($is_active) {
        $this->is_active = $is_active;
        return $this;
    }
    
    public function getLast_seen() {
        return $this->last_seen;
    }
    
    public function setLast_seen($last_seen) {
        $this->last_seen = $last_seen;
        return $this;
    }
    
    public function getTimeLastSeen() {
        $sql = "SELECT TIMESTAMPDIFF(SECOND, NOW(), `last_seen`) AS last_seen FROM " . USERS ." WHERE id = " . $this->getId();
        $query = mysql_query($sql);
        
        if ($query && mysql_num_rows($query) > 0) {
            $row = mysql_fetch_assoc($query);
            return $row['last_seen'];
        } else {
            return false;
        }
    }

    /**
     * @return the $is_runner
     */
    public function getIs_runner() {
        return $this->is_runner;
    }

    /**
     * @param $is_runner the $is_runner to set
     */
    public function setIs_runner($is_runner) {
        $this->is_runner = $is_runner;
        return $this;
    }

    /**
     * @return the $is_admin
     */
    public function getIs_admin() {
        return $this->is_admin;
    }

    /**
     * @param $is_admin the $is_admin to set
     */
    public function setIs_admin($is_admin) {
        $this->is_admin = $is_admin;
        return $this;
    }

    /**
     * @return bool $is_internal
     */
    public function getIs_internal() {
        return $this->is_internal;
    }

    /**
     * @param bool $is_internal the $is_internal to set
     */
    public function setIs_internal($is_internal) {
        $this->is_internal = $is_internal;

        return $this;
    }

    public function getPaypal() {
        return $this->paypal;
    }

    public function setPaypal($paypal) {
        $this->paypal = $paypal;
        return $this;
    }

    public function getPaypal_email() {
        return $this->paypal_email;
    }

    public function setPaypal_email($paypal_email) {
        $this->paypal_email = $paypal_email;
        return $this;
    }

    public function getPaypal_hash() {
        return $this->paypal_hash;
    }

    public function setPaypal_hash($paypal_hash) {
        $this->paypal_hash = $paypal_hash;
        return $this;
    }

    public function getPaypal_verified() {
        return $this->paypal_verified;
    }
    
    public function isPaypalVerified() {
        if ((int)$this->getPaypal_verified() === 1) {
            return true;
        }
        return false;
    }

    public function setPaypal_verified($paypal_verified) {
        $this->paypal_verified = $paypal_verified;
        return $this;
    }

    /**
     * @return the $is_payer
     */
    public function getIs_payer() {
        return $this->is_payer;
    }

    /**
     * @param $is_payer the $is_payer to set
     */
    public function setIs_payer($is_payer) {
        $this->is_payer = $is_payer;
        return $this;
    }
    /**
     * @return the $unixusername
     */
    public function getAnnual_salary() {
        return $this->annual_salary;
    }

    /**
     * @param $unixusername: unix username to set
     */
    public function setAnnual_salary($annual_salary) {
        $this->annual_salary = $annual_salary;
        return $this;
    }
    /**
     * @return the $journal_nick
     */
    public function getJournal_nick() {
        return $this->journal_nick;
    }

    /**
     * @param $journal_nick the $journal_nick to set
     */
    public function setJournal_nick($journal_nick) {
        $this->journal_nick = $journal_nick;
        return $this;
    }

    /**
     * @return the $is_guest
     */
    public function getIs_guest() {
        return $this->is_guest;
    }

    /**
     * @param $is_guest the $is_guest to set
     */
    public function setIs_guest($is_guest) {
        $this->is_guest = $is_guest;
        return $this;
    }

    /**
     * @return string
     */
    public function getInt_code()
    {
        return $this->int_code;
    }

    /**
     * @param string $intCode
     * @return User
     */
    public function setInt_code($intCode)
    {
        $this->int_code = $intCode;
        return $this;
    }

    /**
     * @return the $country
     */
    public function getCountry() {
        return $this->country;
    }

    /**
     * @param $country the $country to set
     */
    public function setCountry($country) {
        $this->country = $country;
        return $this;
    }
    
    /**
     * @return the $manager
     */
    public function getManager() {
        return $this->manager;
    }
    
    /**
     * @param $manager the $manager to set
     */
    public function setManager($manager) {
        $this->manager = $manager;
        return $this;
    }

    /**
     * @return the $referrer
     */
    public function getReferred_by() {
        return $this->referred_by;
    }
    
    /**
     * @param $referred_by the $referred_by to set
     */
    public function setReferred_by($referred_by) {
        $this->referred_by = $referred_by;
        return $this;
    }
    /**
     * @return the $has_sandbox
     */
    public function getHas_sandbox() {
        return $this->has_sandbox;
    }

    /**
     * @param $sendbox_status: status of the sandbox
     */
    public function setHas_sandbox($sendbox_status) {
        $this->has_sandbox = $sendbox_status;
        return $this;
    }

    /**
     * @return the $unixusername
     */
    public function getUnixusername() {
        return $this->unixusername;
    }

    /**
     * @param $unixusername: unix username to set
     */
    public function setUnixusername($unixusername) {
        $this->unixusername = $unixusername;
        return $this;
    }

    /**
     * Given a user's chosen nickname, generate their unixusername. 
     * This is done by:
     *  - lowercasing their nickname
     *  - stripping non-alphanumeric
     *  - verifying uniqueness in passwd file & user table
     *  - if not unique, append a number :/
     *      (not the greatest, but it can be changed later)
     *
     */
    public function generateUnixUsername($nickname) {
        // lowercase
        $unixname = strtolower($nickname);

        // find alphanumeric-only parts to use as unixname
        $disallowed_characters = "/[^a-z0-9]/";
        $unixname = preg_replace($disallowed_characters, "", $unixname);

        // make sure first character is alpha character (can't start w/ a #)
        if (preg_match("/^[a-z]/", $unixname) == 0) {
            // lets not be fancy.. just prepend an "a" to their name.
            $unixname = "a".$unixname;
        }

        // append numbers to the end of the name if it's not unique
        // to both the password file AND the user table
        // Test SanboxUtil last since that could be a remote call
        $attempted_unixname = $unixname;
        $x = 0;
        while (User::unixusernameExists($attempted_unixname) ||
               SandBoxUtil::inPasswdFile($attempted_unixname)) {
               
            $x++;
            $attempted_unixname = $unixname.$x;
        }
        $unixname = $attempted_unixname;

        return $unixname;
    }

    /**
     * @return true if the supplied username is in the database
     *
     */
    public function unixusernameExists($username) {
        $username = mysql_real_escape_string($username);
        $query_string = "
            SELECT
                id
            FROM
                ".USERS."
            WHERE
                unixusername='".$username."'";
        
        $query = mysql_query($query_string);
        
        if (mysql_num_rows($query) > 0) {
            return true;
        }
        return false;
    }


    /**
     * @return the $projects_checkedout
     */
    public function getProjects_checkedout() {
        $query = mysql_query("SELECT `project_id`, `checked_out` FROM `".PROJECT_USERS."`
            WHERE `user_id`=" . $this->getId() . "
            AND `checked_out` = 1");
            
        if ($query && mysql_num_rows($query)) {
            while ($row = mysql_fetch_assoc($query)) {
                $this->projects[] = $row;
            }
        
        } else {
            return null;
        }
        return $this->projects;
    }
    
    public function getProjects() {
        return $this->projects ;
    }

    /**
     * @param $projects_checkedout: projects checked out for user
     */
    public function setProjects_checkedout($projects_checkedout) {
        $this->projects_checkedout = $projects_checkedout;
        return $this;
    }

    public function isProjectCheckedOut($project_id) {
        foreach ($this->projects as $project) {
            if ($project['project_id'] == $project_id) {
                if ($project['checked_out'] == 1) {
                    return true;
                } else {
                    return false;
                }
            }
        }
    }

    public function checkoutProject($project_id) {
    
        $query = mysql_query("INSERT INTO `".PROJECT_USERS."` VALUES ('', ".$this->getId().", ".$project_id.", 1)");
        if ($query) {
            return mysql_insert_id();
        } else {
            return false;
        }
    
    }

    /**
     * @return the $filter
     */
    public function getFilter() {
        return $this->filter;
    }
    
    /**
     * Get a list of active users.
     * 
     * @param $active int Show only active users if 1
     * @param $active int Show only runner users if 1
     * @param $populate int Populate a user by id
     * @return array Userlist
     *
     */
    public static function getUserList($populate = 0, $active = 0, $runner = 0, $namesOnly = false) {
        $sql = "";
        if ($active) {
            $user_where = "( users.id = runner_id  OR users.id = mechanic_id  OR users.id = creator_id )";
            $sql .= "SELECT DISTINCT " . USERS . ".* FROM " . USERS . "," . WORKLIST . "
                WHERE
                    " . WORKLIST . ".status_changed > DATE_SUB(NOW(), INTERVAL 30 DAY) AND 
                    {$user_where}";
            $sql .= $runner ? ' AND `is_runner` = 1' : '';
            $sql .= " UNION
                SELECT DISTINCT " . USERS . ".* FROM " . USERS . "
                WHERE
                    " . USERS . ".added > DATE_SUB(NOW(), INTERVAL 15 DAY) ";
            
            $sql .= $runner ? ' AND `is_runner` = 1' : '';
            if ($populate <> 0) {
                $sql .= " UNION SELECT users.* FROM users WHERE users.id = {$populate}";
            }

            // Final Query: wrap unioned queries and sort by nickname
            $sql = "SELECT DISTINCT * FROM ({$sql}) DistinctUsers ORDER BY nickname ASC";
        }
        else {
        	$sql .= "SELECT users.* FROM users
                WHERE (users.is_active > 0 AND users.confirm = 1";
            $sql .= $runner ? ' AND `is_runner` = 1)' : ')';

            if ($populate <> 0) {
                $sql .= " OR users.id = {$populate}";
            }

            $sql .= " ORDER BY nickname ASC";
        }
        $result = mysql_query($sql);
        $i =  (int) $populate > 0 ? (int) 1 : 0;
        while ($result && ($row = mysql_fetch_assoc($result))) {
            $user = new User();
            if ($namesOnly) {
                $user->id = $row['id'];
                $user->nickname = $row['nickname'];
            } else {
                $user = $user->setOptions($row);
            }
            if ($populate != $row['id']) {
                $userlist[$i++] = $user;
            } else {
                $userlist[0] = $user;
            }
        }
        return ((!empty($userlist)) ? $userlist : false);
    }
    
    public static function getRunnerlist() {
        $runnerlist = array();
        $sql = 'SELECT `' . USERS . '`.`id` FROM `' . USERS . '` WHERE `' . USERS . '`.`is_runner` = 1
                OR `' . USERS . '`.`id` IN (SELECT `runner_id` FROM `' . PROJECT_RUNNERS . '`)';
        $result = mysql_query($sql);
        while ($result && ($row = mysql_fetch_assoc($result))) {
            $user = new User();
            $runnerlist[] = $user->findUserById($row['id']);
        }
        return ((!empty($runnerlist)) ? $runnerlist : false);
    }
    
    public static function getRelRunnerlist($project_id) {
        $relrunnerlist = array();
        $sql = 'SELECT `runner_id` FROM `' . PROJECT_RUNNERS . '` WHERE `project_id` = ' . $project_id . ' ';
        $result = mysql_query($sql);
        while ($result && ($row = mysql_fetch_assoc($result))) {
            $user = new User();
            $relrunnerlist[] = $user->findUserById($row['id']);
        }
        return ((!empty($relrunnerlist)) ? $relrunnerlist : false);
    }
   
    public static function getPayerList() {
        $payerlist = array();
        $sql = 'SELECT `' . USERS . '`.`id` FROM `' . USERS . '` WHERE `' . USERS . '`.`is_payer` = 1;';
        $result = mysql_query($sql);
        while ($result && ($row = mysql_fetch_assoc($result))) {
            $user = new User();
            $payerlist[] = $user->findUserById($row['id']);
        }
        return ((!empty($payerlist)) ? $payerlist : false);
    }

    /**
     * @param $filter the $filter to set
     */
    public function setFilter($filter) {
        $this->filter = $filter;
    }

    protected function loadUser($where)
    {
        // now we build the sql query
        $sql = 'SELECT * FROM `' . USERS . '` WHERE ' . $where . ' LIMIT 1;';
        // and get the result
        $result = mysql_query($sql);

        if ($result && (mysql_num_rows($result) == 1)) {
            $options = mysql_fetch_assoc($result);
            $this->setOptions($options);
            return $this;
        }
        return false;
    }

    protected function loadUsers($where)
    {
        $sql = 'SELECT `id` FROM `' . USERS . '` WHERE ' . $where;
        if ($result = mysql_query($sql)) {
            $ret = array();
            while (($row = mysql_fetch_assoc($result)) !== false) {
                $ret[] = User::find($row['id']);
            }
            return $ret;
        }
        return false;
    }

    private function getUserColumns()
    {
        $columns = array();
        $result = mysql_query('SHOW COLUMNS FROM `' . USERS . '`');
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
        $columns = $this->getUserColumns();
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
        $sql = 'INSERT INTO `' . USERS . '` (`' . implode('`,`', $data['columns']) . '`) VALUES ("' . implode('","', $data['values']) . '")';
        $result = mysql_query($sql);
        if ($result) {
            return mysql_insert_id();
        }
        return false;
    }

    private function update()
    {
        $flag = false;
        $data = $this->prepareData();
        $sql = 'UPDATE `' . USERS . '` SET ';
        foreach ($data['columns'] as $index => $column) {
            if ($column == 'id') {
                continue;
            }
            if ($flag === true) {
                $sql .= ', ';
            }
            if( $column == "w9_accepted" && $data['values'][$index] == "NOW()" ){
                $sql .= '`' . $column . '` = ' . $data['values'][$index];
            } else {
                $sql .= '`' . $column . '` = "' . $data['values'][$index] . '"';
            }
            $flag = true;
        }
        $sql .= ' WHERE `id` = ' . (int)$this->getId() . ';';
        $result = mysql_query($sql);
        if ($result) {
            return true;
        }
        return false;
    }

//Garth
    /**
     * Checks to see if image exists in the cloud
     * @param string $imageName
     * @return bool
     */
    protected function imageExistsS3($imageName) {
       //Don't look for resizeds since we already looked in the db
       if (strpos($imageName,'w:')) { error_log("S3: don't look for thumbnails $imageName"); return false; }

       S3::setAuth(S3_ACCESS_KEY, S3_SECRET_KEY);

       try {
           if (! $result = S3::getObject(S3_BUCKET,'image/'.$imageName,false)) {
               error_log("image not found on s3");
               return false;
           } ;
           //Use to Debug S3 filecheck
           //error_log("imageExistsS3: $imageName . ".print_r($result->code,true));
           if ($result->code==200) {
               return true;
           }
           return false;
       } catch ( Exception $e ) {
           throw new Exception("imageExistsS3:getObject caught: $e imageName");
       }
    }

    /**
     * Determine the avatar for the user from the `picture` field
     *
     *   - If no picture is set, return placeholder image
     *   - If picture is URL, return it as is
     *    - Otherwise, preprend the APP_IMAGE_URL
     *
     * @return the $avatar
     */
    public function getAvatar($w = 50, $h = 50)
    {
        if (empty($this->picture)) {
            return SERVER_URL ."thumb.php?src=no_picture.png&h=".$h."&w=".$w."&zc=0";
        } else {
            if ((!(substr($this->picture, 0, 7) == 'http://')) && (!(substr($this->picture, 0, 8) == 'https://'))) {
                return APP_IMAGE_URL . $this->picture;
            } else {
                return $this->picture;
            }
        }
    }
    
    /**
     * Retrieves the url to the avatar
     */
    public function setAvatar()
    {
        $this->avatar = $this->picture;
        return $this;
    }
    /**
     * @return the $notifications
     */
     public function getBidding_notif() {
        return $this->bidding_notif;
    }
    public function getReview_notif() {
        return $this->review_notif;
    }
    public function getSelf_notif() {
        return $this->self_notif;
    }

    /**
     * @param set notifications
     */
    public function setBidding_notif($bidding_notif) {
        $this->bidding_notif = $bidding_notif;
        return $this;
    }
    public function setReview_notif($review_notif) {
        $this->review_notif = $review_notif;
        return $this;
    }
    public function setSelf_notif($self_notif) {
        $this->self_notif = $self_notif;
        return $this;
    }
    
    /**
     * Return a trimmed version of the nickname
     */
    public function getSubNickname($nickname, $length = 13) {
        if (strlen($nickname) > $length) {
            return substr($nickname, 0, $length) . '...';
        } else {
            return $nickname;
        }
    }
    /**
     * @return the $has_W2
     */
    public function getHas_W2() {
        return $this->has_W2;
    }

    /**
     * @param $has_W2 the $has_W2 to set
     */
    public function setHas_W2($has_W2) {
        $this->has_W2 = $has_W2;
        return $this;
    }

    public function isRunnerOfWorkitem($workitem, $exclude_assigned = false) {
        if ($this->getId() == 0) {
            return false;
        }
        $runner_id = $workitem->getRunner() ? $workitem->getRunner()->getId() : 0;
        $is_runner = ($this->getId() == $runner_id);
        $is_assigned = ($this->getId() == $workitem->getAssigned_id());
        return $is_runner || ($is_assigned && !$exclude_assigned);
    }

/* Updates the current calling user status, saves it to the database and sends a message to the journal
 * @param status is the text status submitted to be udpated
 */
    public static function update_status($status = "") {
		error_log('update_satus ' . $status);
        if (isset($_SESSION['userid'])){
            if ($status != "") {
                $journal_message =  '@' . $_SESSION['nickname'] . ' is *' . $status . '*';

            // Insert new status to the database
                $insert = "INSERT INTO " . USER_STATUS . "(id, status, timeplaced) VALUES(" . $_SESSION['userid'] . ", '" .  mysql_real_escape_string($status) . "', NOW())";
                if (!mysql_query($insert)) {
                    error_log("update_status.mysq: ".mysql_error());
                }

            //Send message to the Journal
                $journal_message = sendJournalNotification($journal_message);
                if ($journal_message != 'ok') {
                    error_log("failed to send notification ".$journal_message);
                    return;
                }
            }
        }
        return;
    }
    
    /*
     * Return a list of all admin users
     */
    public function getAdminEMails() {
        $adminEmails = array();
        $sql = "SELECT username FROM users WHERE is_admin = 1";
        if ($result = mysql_query($sql)) {
            while ($row = mysql_fetch_assoc($result)) {
                $adminEmails[] = $row['username'];
            }
        }
        return $adminEmails;
    }
    
    public function isTwilioSupported($forced = false) {
        if (!defined("TWILIO_SID") || !defined("TWILIO_TOKEN") || !Utils::validPhone($this->phone)) {
            return false;
        }
        if ($forced) {
            return true;
        } else {
            $sql = 
                ' SELECT COUNT(*) AS c ' .
                ' FROM ' . COUNTRIES .
                ' WHERE country_phone_prefix = ' . $this->int_code .
                '   AND country_twilio_enabled = 1';
            if (!$result = mysql_query($sql)) {
                return null;
            }
            $row = mysql_fetch_assoc($result); 
            if ($row['c'] == 0) {
                return false;
            }
        }
        return substr($this->phone_verified, 0, 10) != '0000-00-00' 
            && substr($this->phone_rejected, 0, 10) == '0000-00-00';  
    }
    
    public function getBudgetTransfersDetails(){
        $sql = 'SELECT s.id, b.reason, s.transfer_date, b.receiver_id, u.nickname, s.amount_granted'
            . ' FROM ' . BUDGET_SOURCE . ' AS `s` '
            . ' INNER JOIN ' . BUDGETS .' AS `b` ON s.budget_id = b.id  AND b.active = 1' 
            . ' INNER JOIN ' . USERS . ' AS `u` ON b.receiver_id = u.id'
            . ' WHERE s.giver_id = ' .  $this->getId();
        if (!$result = mysql_query($sql)) {
            return null;
        }
        $ret = array();
        while($row = mysql_fetch_assoc($result)) {
            $ret[] = $row;
        }
        return $ret;
    }

    /**
     * returns user's github authorization token for GitHub application
     * @param $github_id
     * @return null|mixed
     */
    public function authTokenForGitHubId($github_id) {

        if (isset($this->auth_tokens[$github_id])) {
            return $this->auth_tokens[$github_id];
        }

        $sql = "SELECT auth_token FROM " . USERS_AUTH_TOKENS . "
            WHERE github_id = '" . mysql_real_escape_string($github_id) . "'
            AND user_id = " . (int)$this->id;
        if (!$result = mysql_query($sql)) {
            return null;
        }
        $row = mysql_fetch_assoc($result);
        $this->auth_tokens[$github_id] = $row['auth_token'];
        return $this->auth_tokens[$github_id];
    }

    public function findUserByAuthToken($token, $github_id = GITHUB_OAUTH2_CLIENT_ID) {
        $cond = 
            '`id` = (
                SELECT t.user_id 
                FROM `' . USERS_AUTH_TOKENS . "` t
                WHERE t.github_id = '%s' 
                  AND t.auth_token = '%s' 
            )";
        $where = sprintf($cond, $github_id, $token);
        return $this->loadUser($where);       
    }

    public function processConnectResponse(Project $project) {
        $error = isset($_REQUEST['error']) ? true : false;
        $message = $error ? $_REQUEST['error'] : false;
        $data = false;
        if (!$error) {
            // We should have a temporal code, lets verify that and get the actual token
            if (!$error && isset($_REQUEST['code'])) {
                $params = array(
                    'code' => $_REQUEST['code'],
                    'state' => $_REQUEST['state']
                );
                return $project->makeApiRequest('login/oauth/access_token', 'POST', false, $params);
            } else {
                return array(
                    'error' => true,
                    'message' => $message,
                    'data' => $data);
            }
        }
    }
    
    public function storeCredentials($gitHubToken, $gitHubId = GITHUB_OAUTH2_CLIENT_ID) {
        $sql = "INSERT INTO `" . USERS_AUTH_TOKENS . "` (`user_id`, `github_id`, `auth_token`)
            VALUES ('" . (int)$this->id . "',
            '" . mysql_real_escape_string($gitHubId) . "',
            '" . mysql_real_escape_string($gitHubToken) . "')";
        $result = mysql_query($sql);
        if ($result) {
            return true;
        }

        // token already exists - update it
        $sql = "UPDATE `" . USERS_AUTH_TOKENS . "`
            SET `auth_token` = '" . mysql_real_escape_string($gitHubToken) . "'
            WHERE `user_id` = " . (int)$this->id . " AND `github_id` = '" . mysql_real_escape_string($gitHubId) . "'";
        mysql_query($sql);
        return false;
    }
    
    public function verifyForkExists(Project $project) {
        $repoDetails = $project->extractOwnerAndNameFromRepoURL();
        $listOfRepos = $this->getListOfReposForUser($project);
        if ($listOfRepos === null) {
            return false;
        }
        $userRepos = $listOfRepos['data'];
        $i = 0;
        while ($i < count($userRepos)) {
            if ($userRepos[$i]['name'] == $repoDetails['name'] && $userRepos[$i]['fork'] == '1') {
                return true;
            }
            $i++;
        }
        return false;
    }
    
    public function createForkForUser(Project $project) {
        $token = $this->authTokenForGitHubId($project->getGithubId());
        $repoDetails = $project->extractOwnerAndNameFromRepoURL();
        $path = 'repos/' . $repoDetails['owner'] . '/' . $repoDetails['name'] . '/forks';
        return $project->makeApiRequest($path, 'POST', $token, false);
    }
    
    public function getListOfReposForUser(Project $project) {
        $token = $this->authTokenForGitHubId($project->getGithubId());
        if ($token == null) {
            return null;
        }
        return $project->makeApiRequest('user/repos', 'GET', $token, false);
    }
    
    public function getListOfBranchesForUsersRepo(Project $project) {
        $listOfBranches = array();
        $latestMasterCommit = false;
        $data = array();
        $i = 0;
        $token = $this->authTokenForGitHubId($project->getGithubId());
        $repoDetails = $project->extractOwnerAndNameFromRepoURL();
        $userDetails = $this->getGitHubUserDetails($project);
        $gitHubUsername = $userDetails['data']['login'];
        $path = 'repos/' . $gitHubUsername . '/' . $repoDetails['name'] . '/branches';
        $rawOutput = $project->makeApiRequest($path, 'GET', $token, false);
        while ($i < count($rawOutput['data'])) {
            $listOfBranches[] = $rawOutput['data'][$i]['name'];
            $i++;
        }
        $path2 = 'repos/' . $repoDetails['owner'] . '/' . $repoDetails['name'] . '/branches';
        $rawOutput2 = $project->makeApiRequest($path2, 'GET', $token, false);
        $ii = 0;
        while ($ii < count($rawOutput2['data'])) {
            if ($rawOutput2['data'][$ii]['name'] == 'master') {
                $latestMasterCommit = $rawOutput2['data'][$ii]['commit']['sha'];
            }
            $ii++;
        }
        $data['branches'] = $listOfBranches;
        $data['latest_master_commit'] = $latestMasterCommit;
        return $data;
    }
    
    public function getGitHubUserDetails(Project $project) {
        $token = $this->authTokenForGitHubId($project->getGithubId());
        return $project->makeApiRequest('user', 'GET', $token, false);
    }
    
    public function createBranchForUser($branch_name, Project $project) {
        $branchDetails = array();
        $token = $this->authTokenForGitHubId($project->getGithubId());
        $repoDetails = $project->extractOwnerAndNameFromRepoURL();
        $listOfBranches = $this->getListOfBranchesForUsersRepo($project);
        $latestCommit = $listOfBranches['latest_master_commit'];
        $userDetails = $this->getGitHubUserDetails($project);
        $gitHubUsername = $userDetails['data']['login'];
        // Verify whether theres a branch named $branch_name already
        if (!in_array($branch_name, $listOfBranches['branches'])) {
            $path = 'repos/' . $gitHubUsername . '/' . $repoDetails['name'] . '/git/refs';
            $params = array(
                'ref' => 'refs/heads/' . $branch_name,
                'sha' => $latestCommit);
            $branchStatus = $project->makeApiRequest($path, 'POST', $token, $params, true);
            if (!$branchStatus['error']) {
                $branchDetails['error'] = false;
                $branchDetails['data'] = $branchStatus['data'];
                $branchDetails['branch_url'] = 'https://github.com/' . $gitHubUsername . "/" . $repoDetails['name'] . '/tree/' . $branch_name;
                return $branchDetails;
            }
        }
        return false;
    }
    
    public function createPullRequest($branch_name, Project $project) {
        $token = $this->authTokenForGitHubId($project->getGithubId());
        $repoDetails = $project->extractOwnerAndNameFromRepoURL();
        $userDetails = $this->getGitHubUserDetails($project);
        $gitHubUsername = $userDetails['data']['login'];
        $path = 'repos/' . $repoDetails['owner'] . '/' . $repoDetails['name'] . '/pulls';
        $params = array(
            'title' => 'Code Review for Job #' . $branch_name,
            'body' => 'Code Review for Job #' . $branch_name . " - Workitem available at https://www.worklist.net/" . $branch_name,
            'head' => $gitHubUsername . ':' . $branch_name,
            'base' => 'master'
        );
        $pullRequestStatus = $project->makeApiRequest($path, 'POST', $token, $params, true);
        return $pullRequestStatus;
    }

    public static function signup($username, $nickname, $password, $access_token, $country) {
        $sql = "
            INSERT 
            INTO " . USERS  . " (username, nickname, password, confirm_string, added, w9_status, country, is_active)
            VALUES(
                '" . mysql_real_escape_string($username) . "', 
                '" . mysql_real_escape_string($nickname) . "',
                '{crypt}" . mysql_real_escape_string(Utils::encryptPassword($password)) . "',
                '" . uniqid() . "',
                NOW(),
                'not-applicable',
                '" . mysql_real_escape_string($country) . "',
                0
            )";
        $res = mysql_query($sql);
        $user_id = mysql_insert_id();
        if (!$user_id) {
            return false;
        }
        $ret = new User($user_id);
        if ($ret->getId() && !$ret->isGithub_connected()) {
            $ret->storeCredentials($access_token);
        }
        return $ret;
    }

    public static function login($user, $redirect_url = './') {
        $userObject = User::find($user);
        $id = $userObject->getId();
        $username = $userObject->getUsername();
        $nickname = $userObject->getNickname();
        $admin = $userObject->getIs_admin();
        Utils::setUserSession($id, $username, $nickname, $admin);
        if (is_string($redirect_url)) {
            Utils::redirect($redirect_url);
        }
    }

    public function completedJobsWithStats() {
        $sql = "
            SELECT w.id, w.summary, f.cost,
            DATEDIFF ((SELECT MAX(change_date) FROM " .STATUS_LOG. " WHERE `status` = 'Merged'
            AND `worklist_id` = w.id), b.date) days
            FROM " . WORKLIST . " w
            LEFT JOIN " . FEES . " b ON b.worklist_id = w.id AND b.desc = 'Accepted Bid'
            LEFT JOIN (
                SELECT SUM(amount) cost, worklist_id
                FROM " . FEES . "
                WHERE withdrawn = 0
                GROUP BY worklist_id
            ) f ON f.worklist_id = w.id
            WHERE
                (`mechanic_id` = " . $this->getId() . " OR `creator_id` = " . $this->getId() . ") AND
                w.`status` IN ('Merged', 'Done')
            GROUP BY w.`id`
            ORDER BY w.`id` DESC
            LIMIT 5";

        $res = mysql_query($sql);
        if ($res) {
            $ret = array();
            while ($row = mysql_fetch_assoc($res)) {
                $ret[] = $row;
            }
            return $ret;
        }
        return false;
    }

    public function followingJobs($page = 1, $itemsPerPage = 10) {
        $ret = array();
        $count = $this->followingJobsCount();
        $sql = "
            SELECT
              `w`.`id`,
              `w`.`summary`,
              `w`.`status`,
              `mn`.`nickname` AS `mechanic`,
              `cn`.`nickname` AS `creator`,`rn`.`nickname` AS `designer`,
              DATE_FORMAT(`w`.`created`, '%m/%d/%Y') AS `created`
            FROM `" . WORKLIST . "` `w`
              LEFT JOIN `" . USERS . "` AS `mn`
                ON `w`.`mechanic_id` = `mn`.`id`
              LEFT JOIN `" . USERS . "` AS `rn`
                ON `w`.`runner_id` = `rn`.`id`
              LEFT JOIN `" . USERS . "` AS `cn`
                ON `w`.`creator_id` = `cn`.`id`
              JOIN `" . TASK_FOLLOWERS . "` AS `tf`
                ON `tf`.`workitem_id` = `w`.`id` AND `tf`.`user_id` = " . $this->getId() . "
            ORDER BY `w`.`id` DESC
            LIMIT " . ($page-1) * $itemsPerPage . ", {$itemsPerPage}";
        if ($res = mysql_query($sql)) {
            while ($row = mysql_fetch_assoc($res)) {
                $ret[] = $row;
            }
            return array(
                'count' => $count,
                'pages' => ceil($count/$itemsPerPage),
                'page' => $page,
                'jobs' => $ret
            );
        }
        return false;
    }

    public function followingJobsCount() {
        $ret = array();
        $sql = "
            SELECT COUNT(*)
            FROM `" . TASK_FOLLOWERS . "` `f`
            WHERE `f`.`user_id` = " . $this->getId() ;
        if ($res = mysql_query($sql)) {
            $row = mysql_fetch_row($res);
            return $row[0];
        }
        return false;
    }

    public function jobsAsDesigner($status = '', $page = 1, $itemsPerPage = 10) {
        $ret = array();
        if (!$status) {
            $status = array('In Progress', 'QA Ready', 'Review', 'Merged', 'Done');
        }
        if (is_array($status)) {
            $statusCond = "`w`.`status` IN ('" . implode("', '", $status) . "')";
        } else {
            $statusCond = "`status` = '{$status}'";
        }
        $count = $this->jobsAsDesignerCount($status);
        $sql = "
            SELECT
              `w`.`id`,
              `w`.`summary`,
              `w`.`status`,
              `mn`.`nickname` AS `mechanic`,
              `cn`.`nickname` AS `creator`,
              `rn`.`nickname` AS `designer`,
              DATE_FORMAT(`w`.`created`, '%m/%d/%Y') AS `created`
            FROM `" . WORKLIST . "` `w`
              LEFT JOIN `" . USERS . "` AS `mn`
                ON `w`.`mechanic_id` = `mn`.`id`
              LEFT JOIN `" . USERS . "` AS `rn`
                ON `w`.`runner_id` = `rn`.`id`
              LEFT JOIN `" . USERS . "` AS `cn`
                ON `w`.`creator_id` = `cn`.`id`
            WHERE `w`.`runner_id` = " . $this->getId() . "
              AND {$statusCond}
            ORDER BY `w`.`id` DESC
            LIMIT " . ($page-1)*$itemsPerPage . ", {$itemsPerPage}";
        $ret = array();
        if ($res = mysql_query($sql)) {
            while($row = mysql_fetch_assoc($res)) {
                $ret[] = $row;
            }
            return array(
                'count' => $count,
                'pages' => ceil($count/$itemsPerPage),
                'page' => $page,
                'jobs' => $ret
            );
        }
        return false;
    }

    public function jobsAsDesignerCount($status = '') {
        if (!$status) {
            $status = array('In Progress', 'QA Ready', 'Review', 'Merged', 'Done');
        }
        if (is_array($status)) {
            $statusCond = "`w`.`status` IN ('" . implode("', '", $status) . "')";
        } else {
            $statusCond = "`status` = '{$status}'";
        }
        $sql = "
            SELECT COUNT(*)
            FROM `" . WORKLIST . "` `w`
            WHERE `w`.`runner_id` = " . $this->getId() . "
              AND {$statusCond}";
        if ($res = mysql_query($sql)){
            $row = mysql_fetch_row($res);
            return $row[0];
        }
        return false;
    }

    public function jobs($status = '', $page = 1, $itemsPerPage = 10) {
        $ret = array();
        if (!$status) {
            $status = array('In Progress', 'QA Ready', 'Review', 'Merged', 'Done');
        }
        if (is_array($status)) {
            $statusCond = "`w`.`status` IN ('" . implode("', '", $status) . "')";
        } else {
            $statusCond = "`status` = '{$status}'";
        }
        $count = $this->jobsCount($status);
        $sql = "
            SELECT
              `w`.`id`,
              `w`.`summary`,
              `cre`.`nickname` AS `creator`,
              `des`.`nickname` AS `designer`,
              `dev`.`nickname` AS `developer`,
              DATE_FORMAT(`created`, '%m/%d/%Y') AS `created`,
              `w`.`status`
            FROM `" . WORKLIST . "` `w`
              LEFT JOIN `" . USERS . "` `cre`
                ON `w`.`creator_id` = `cre`.`id`
              LEFT JOIN `" . USERS . "` `des`
                ON `w`.`runner_id` = `des`.`id`
              LEFT JOIN `" . USERS . "` `dev`
                ON `w`.`mechanic_id` = `dev`.`id`
            WHERE (`w`.`mechanic_id` = " . $this->getId() . " OR `w`.`creator_id` = " . $this->getId() . ")
              AND {$statusCond}
            ORDER BY `id` DESC
            LIMIT " . ($page-1)*$itemsPerPage . ", {$itemsPerPage}";

        if ($res = mysql_query($sql)) {
            while($row = mysql_fetch_assoc($res)){
                $ret[] = $row;
            }
            return array(
                'count' => $count,
                'pages' => ceil($count/$itemsPerPage),
                'page' => $page,
                'jobs' => $ret
            );
        }
        return false;
    }

    public function jobsCount($status = '') {
        if (!$status) {
            $status = array('In Progress', 'QA Ready', 'Review', 'Merged', 'Done');
        }
        if (is_array($status)) {
            $statusCond = "`w`.`status` IN ('" . implode("', '", $status) . "')";
        } else {
            $statusCond = "`status` = '{$status}'";
        }
        $sql = "
            SELECT COUNT(*)
            FROM `" . WORKLIST . "` `w`
            WHERE (`w`.`mechanic_id` = " . $this->getId() . " OR `w`.`creator_id` = " . $this->getId() . ")
              AND {$statusCond}";

        $res = mysql_query($sql);
        if($res && $row = mysql_fetch_row($res)){
            return $row[0];
        }
        return false;
    }

    public function avgJobRunTime() {
        $query = "
            SELECT AVG(TIME_TO_SEC(TIMEDIFF(`x`.`doneDate`, `x`.`workingDate`))) AS `avgJobRunTime`
            FROM (
              SELECT
                `w`.id,
                `s`.change_date AS doneDate,
                (
                  SELECT MAX(`date`) AS `workingDate`
                  FROM `fees`
                  WHERE `worklist_id`=`w`.`id`
                    AND `desc` = 'Accepted Bid'
                ) as `workingDate`
              FROM `status_log` `s`
                LEFT JOIN `" . WORKLIST ."` `w`
                  ON `s`.`worklist_id`=`w`.`id`
              WHERE `s`.`status` = 'Done'
                AND `w`.`runner_id` = " . $this->getId() . "
            ) `x`";
        if ($result = mysql_query($query)) {
            $row = mysql_fetch_array($result);
            return ($row['avgJobRunTime'] > 0)
                ? relativeTime($row['avgJobRunTime'], false, true, false)
                : '';
        }
        return false;
    }

    public function totalEarnings() {
        $sql = "
            SELECT SUM(amount)
            FROM `fees`
            WHERE `paid` = 1
              AND `withdrawn` = 0
              AND `expense` = 0
              AND `user_id` = " . $this->getId();
        if($res = mysql_query($sql)) {
            $row = mysql_fetch_row($res);
            return (int) $row[0];
        }
        return false;
    }

    public function bonusPaymentsTotal() {
        $sql = "
            SELECT
              IFNULL(`rewarder`.`sum`,0) AS `bonus_tot`
            FROM `".USERS."`
              LEFT JOIN (
                SELECT `user_id`, SUM(amount) AS `sum`
                FROM `".FEES."`
                WHERE (`withdrawn` = 0 AND `paid` = 1 AND `user_id` = " . $this->getId() . ")
                  AND (`rewarder` = 1 OR `bonus` = 1)
                GROUP BY `user_id`
              ) AS `rewarder` ON `".USERS."`.`id` = `rewarder`.`user_id`
            WHERE `id` = " . $this->getId();

        if ($res = mysql_query($sql)) {
            $row = mysql_fetch_row($res);
            return (int) $row[0];
        }
        return false;
    }

    // gets earning for a number of days back
    // latestEarnings(30) will give earnings (paid) for last 30 days
    public function latestEarnings($daysCount) {
        $startDate = date("Y-m-d", strtotime("- $daysCount days"));
        $endDate = date("Y-m-d", time());
        $sql = "
            SELECT SUM(amount)
            FROM `" . FEES . "`
            WHERE `paid` = 1
              AND `withdrawn`= 0
              AND `expense`= 0
              AND `paid_date` >= '$startDate'
              AND `paid_date` <= '$endDate'
              AND `user_id` = " . $this->getId();

        if ($res = mysql_query($sql)) {
            $row = mysql_fetch_row($res);
            return (int) $row[0];
        }
        return false;
    }

    // gets list of fees and jobs associated with them for a number of days back
    // works similar to latestEarnings(30) - will give earnings with jobs (paid) for last 30 days
    public function latestEarningsJobs($daysCount, $page = 1, $itemsPerPage = 10) {
        $ret = array();
        $startDate = date("Y-m-d", strtotime("- $daysCount days"));
        $endDate = date("Y-m-d", time());

        $count = 0;
        $sql = "
            SELECT COUNT(*)
            FROM `" . FEES . "`
            WHERE `paid` = 1
              AND `withdrawn` = 0
              AND `expense` = 0
              AND `paid_date` >= '$startDate' AND `paid_date` <= '$endDate'
              AND `user_id` = " . $this->getId();

        $res = mysql_query($sql);
        if($res && $row = mysql_fetch_row($res)){
            $count = $row[0];
        }

        $sql = "
            SELECT DISTINCT
              `f`.`worklist_id`,
              `f`.`amount`,
              `w`.`summary`,
              `f`.`paid_date`,
              DATE_FORMAT(`f`.`paid_date`, '%m/%d/%Y') AS `paid_formatted`,
              `cn`.`nickname` AS `creator`,
              `rn`.`nickname` AS `designer`
            FROM `" . FEES . "` `f`
              LEFT JOIN `" . WORKLIST . "` `w`
                ON `f`.`worklist_id` = `w`.`id`
              LEFT JOIN `" . USERS . "` AS `cn`
                ON `w`.`creator_id` = `cn`.`id`
              LEFT JOIN `" . USERS . "` AS `rn`
                ON `w`.`runner_id` = `rn`.`id`
            WHERE `f`.`paid` = 1
              AND `f`.`withdrawn` = 0
              AND `f`.`expense` = 0
              AND `f`.`paid_date` >= '$startDate'
              AND `f`.`paid_date` <= '$endDate'
              AND `f`.`user_id` = " . $this->getId() . "
            ORDER BY `paid_date` DESC
            LIMIT " . ($page-1)*$itemsPerPage . ", {$itemsPerPage}";

        if ($res = mysql_query($sql)) {
            while($row = mysql_fetch_assoc($res)){
                $ret[] = $row;
            }
            return array(
                'count' => $count,
                'pages' => ceil($count/$itemsPerPage),
                'page' => $page,
                'jobs' => $ret
            );
        }
        return false;
    }

    // get total love received by user using sendlove api
    public function totalLove($page = 1) {
        $data = $this->sendloveApiRequest('getlove', $page);
        if($data){
            return $data;
        }
        return false;
    }

    // get number of total love received by user using sendlove api
    public function loveCount() {
        $data = $this->sendloveApiRequest('getcount');
        if ($data){
            return (int) $data['count'];
        }
        return false;
    }

    public function uniqueLoveCount() {
       $data = $this->sendloveApiRequest('getuniquecount');
        if ($data){
            return (int) $data['count'];
        }
        return false;
    }

    // sends a request to sendlove api and returns data in case of success
    // false - if something went wrong
    private function sendloveApiRequest($action, $page = 1, $itemsPerPage = 10) {
        $params = array (
            'action' => $action,
            'api_key' => SENDLOVE_API_KEY,
            'page' => $page,
            'perpage' => $itemsPerPage,
            'username' => $this->getUsername());
        $referer = (empty($_SERVER['HTTPS'])?'http://':'https://').$_SERVER['SERVER_NAME'].$_SERVER['PHP_SELF'];
        $sendlove_rsp = postRequest (SENDLOVE_API_URL, $params, array(CURLOPT_REFERER => $referer));
        $rsp = json_decode ($sendlove_rsp, true);
        if ($rsp['status'] == SL_OK){
            return $rsp['data'];
        } else {
            return false;
        }
    }

    public function jobsForProject($status, $project, $page = 1, $itemsPerPage = 10) {
        $ret = array();
        if (!$status) {
            $status = array('In Progress', 'QA Ready', 'Review', 'Merged', 'Done');
        }
        if (is_array($status)) {
            $statusCond = "`w`.`status` IN ('" . implode("', '", $status) . "')";
        } else {
            $statusCond = "`status` = '{$status}'";
        }
        $count = $this->jobsForProjectCount($status, $project);
        $sql = "
            SELECT
              `w`.`id`,
              `w`.`summary`,
              `cn`.`nickname` AS `creator`,
              `rn`.`nickname` AS `designer`,
              DATE_FORMAT(`w`.`created`, '%m/%d/%Y') AS `created`
            FROM `" . WORKLIST . "` `w`
                LEFT JOIN `" . USERS . "` AS `cn` ON `w`.`creator_id` = `cn`.`id`
                LEFT JOIN `" . USERS . "` AS `rn` ON `w`.`runner_id` = `rn`.`id`
            WHERE (`w`.`mechanic_id` = " . $this->getId() . " OR `w`.`creator_id` = " . $this->getId() . ")
              AND {$statusCond}
              AND `w`.project_id = ". $project . "
            ORDER BY `w`.`id` DESC
            LIMIT " . ($page-1)*$itemsPerPage . ", {$itemsPerPage}";

        if ($res = mysql_query($sql)) {
            while($row = mysql_fetch_assoc($res)) {
                $ret[] = $row;
            }
            return array(
                'count' => $count,
                'pages' => ceil($count/$itemsPerPage),
                'page' => $page,
                'jobs' => $ret
            );
        }
        return false;
    }

    public function jobsForProjectCount($status, $project) {
        if (!$status) {
            $status = array('In Progress', 'QA Ready', 'Review', 'Merged', 'Done');
        }
        if (is_array($status)) {
            $statusCond = "`w`.`status` IN ('" . implode("', '", $status) . "')";
        } else {
            $statusCond = "`status` = '{$status}'";
        }

        $count = 0;
        $sql = "
            SELECT COUNT(*)
            FROM `" . WORKLIST . "` `w`
            WHERE (`w`.`mechanic_id` = " . $this->getId() . " OR `w`.`creator_id` = " . $this->getId() . ")
              AND {$statusCond}
              AND `w`.`project_id` = " . $project;

        if ($res = mysql_query($sql)) {
            $row = mysql_fetch_row($res);
            $count = $row[0];
        }
        return $count;
    }

    public static function newUserStats() {
        $sql = "
            SELECT (
              SELECT COUNT(DISTINCT(users.id))
              FROM " . USERS . "
                INNER JOIN " . FEES . "
                  ON users.id = fees.user_id AND users.added > DATE_SUB(CURDATE(), INTERVAL 1 MONTH)
            ) AS newUsersWithFees, (
              SELECT COUNT(DISTINCT(users.id))
              FROM " . USERS . "
              INNER JOIN " . BIDS . " ON users.id = bids.bidder_id AND users.added > DATE_SUB(CURDATE(), INTERVAL 1 MONTH)
            ) AS newUsersWithBids, (
              SELECT COUNT(*)
              FROM " . USERS . "
              WHERE added > DATE_SUB(CURDATE(), INTERVAL 1 MONTH)
            ) AS newUsers, (
              SELECT COUNT(*)
              FROM " . USERS . "
              WHERE added > DATE_SUB(CURDATE(), INTERVAL 1 MONTH)
                AND last_seen > added
            ) AS newUsersLoggedIn";

        if ($res = mysql_query($sql)) {
            $row = mysql_fetch_assoc($res);
            return $row;
        }
        return false;
    }

    public function developersForDesigner() {
        $ret = array();
        $query = "
            SELECT
              `u`.`id`,
              `u`.`nickname`,
              count(`w`.`id`) AS `totalJobCount`,
              sum(`f`.`amount`) AS `totalEarnings`
            FROM `" . USERS . "` `u`
              LEFT OUTER JOIN `" . FEES . "` `f`
                ON `f`.`user_id` = `u`.`id`
              LEFT OUTER JOIN `" . WORKLIST . "` `w`
                ON `f`.`worklist_id` = `w`.`id`
            WHERE `f`.`paid` = 1
              AND `f`.`withdrawn` = 0
              AND `f`.`expense` = 0
              AND `w`.`runner_id` = " . $this->getId() . "
              AND `u`.`id` <> `w`.`runner_id`
            GROUP BY `u`.`id`
            ORDER BY `totalEarnings` DESC";
        if ($result = mysql_query($query)) {
            if (mysql_num_rows($result) > 0) {
                while ($row = mysql_fetch_assoc($result)) {
                    $ret[$row['id']] = $row;
                }
            } else {
                return false;
            }
        } else {
            return false;
        }
        return $ret;
    }

    public function projectsForRunner() {
        $ret = array();
        $query = "
            SELECT
              `p`.`project_id`,
              `p`.`name`,
              COUNT(DISTINCT `w`.`id`) AS `totalJobCount`,
              SUM(`f`.`amount`) AS `totalEarnings`
            FROM `" .  PROJECTS . "` `p`
              LEFT OUTER JOIN `" . WORKLIST . "` `w`
                ON `w`.`project_id` = `p`.`project_id`
              LEFT OUTER JOIN `" . FEES . "` `f`
                ON `f`.`worklist_id` = `w`.`id`
            WHERE `w`.`runner_id` = " . $this->getId() . "
              AND `w`.`status` IN ('In Progress', 'QA Ready', 'Review', 'Merged', 'Done')
              AND `f`.`paid` = 1
              AND `f`.`withdrawn` = 0 AND `f`.`expense` = 0
            GROUP BY `p`.`project_id`
            ORDER by `totalEarnings` DESC";
        if ($result = mysql_query($query)) {
            if (mysql_num_rows($result) > 0) {
                while ($row = mysql_fetch_assoc($result)) {
                    $ret[$row['project_id']] = $row;
                }
            } else {
                return false;
            }
        } else {
            return false;
        }
        return $ret;
    }

    /**
     * Find users by nickname or full name (first_name, last_name)
     *
     * @param string $contains
     */
    public function mentionsList($contains) {

        $likeString =  'LIKE "%' . mysql_real_escape_string($contains) . '%"';

        $query = "
            SELECT id, nickname, username, picture, first_name, last_name
            FROM " . USERS . "
            WHERE (
                nickname {$likeString} OR
                first_name {$likeString} OR
                last_name {$likeString}
                ) AND
                picture IS NOT NULL AND picture LIKE \"%http%\"
            ORDER BY nickname LIMIT 0, 10
        ";

        $result = mysql_query($query);

        return $result;
    }

    public function budgetHistory($giver = null, $page = 1, $itemsPerPage = 10) {
        $ret = array();
        $count = $this->budgetHistoryCount($giver);
        $sql = "
            SELECT
                DATE_FORMAT(`b`.`transfer_date`, '%Y-%m-%d') AS `date`,
                `b`.`amount`,
                CASE WHEN `b`.`active` = 1 THEN `b`.`remaining` ELSE 0.00 END AS `remaining`,
                `b`.`reason`,
                `b`.`active`,
                `b`.`notes`,
                `b`.`seed`,
                `b`.`id` AS `budget_id`,
                CASE WHEN
                    (
                        SELECT COUNT(DISTINCT giver_id)
                        FROM " . BUDGET_SOURCE . " AS s
                        JOIN `" . USERS . "` AS `u` ON `u`.`id` = `s`.`giver_id`            #
                        WHERE s.budget_id = b.id                                            # multiple givers?
                    ) > 1 THEN                                                              #
                        (
                            SELECT GROUP_CONCAT(DISTINCT `u`.`nickname` SEPARATOR ',')      #
                            FROM `" . BUDGET_SOURCE . "` AS `s`                             # then bring list of users (CSV)
                            JOIN `" . USERS . "` AS `u` ON `u`.`id` = `s`.`giver_id`        #
                            WHERE `s`.`budget_id` = `b`.`id`
                            GROUP BY `s`.`budget_id`
                        )
                        ELSE
                        (                                                                   #
                            SELECT `u`.`nickname`                                           # otherwise bring the one referenced
                            FROM `" . USERS . "` AS `u`                                     # by budget.giver.id
                            WHERE `u`.`id` = `b`.`giver_id`                                 #
                        )
                END AS `giver_nickname`
            FROM `" . BUDGETS . "` AS `b`
            WHERE `b`.`receiver_id` = " . $this->getId() . "
                " . ($giver ? 'AND `b`.`giver_id` = ' . $giver : '') . "
            ORDER BY `b`.`id` DESC
            LIMIT " . ($page-1)*$itemsPerPage . ", {$itemsPerPage}";
        if ($res = mysql_query($sql)) {
            while($row = mysql_fetch_assoc($res)) {
                $givers = array();
                foreach(preg_split('/,/', $row['giver_nickname']) as $nickname) {
                    $givers[] = array('nickname' => $nickname);
                }
                $ret[] = array_merge($row, array(
                    'givers' => $givers
                ));
            }
            return array(
                'count' => $count,
                'pages' => ceil($count/$itemsPerPage),
                'page' => $page,
                'items' => $ret
            );
        }
        return false;

    }

    public function budgetHistoryCount($giver = null) {
        $count = 0;
        $sql = "
            SELECT COUNT(*)
            FROM " . BUDGETS . " AS b
            WHERE `b`.`receiver_id` = " . $this->getId() . "
                " . ($giver ? 'AND `b`.`giver_id` = ' . $giver : '');

        if ($res = mysql_query($sql)) {
            $row = mysql_fetch_row($res);
            $count = $row[0];
        }
        return $count;
    }

    public static function getInternals() {
        $user = new User();
        return $user->loadUsers('`is_internal` = 1');
    }
}