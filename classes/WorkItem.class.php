<?php

/**
 * Workitem
 *
 * @package Workitem
 */
class WorkItem {
    protected $id;
    protected $summary;
    protected $creatorId;
    protected $creator;
    protected $runnerId;
    protected $runner;
    protected $mechanicId;
    protected $mechanic;
    protected $status;
    protected $notes;
    protected $sandbox;
    protected $project_id;
    protected $project_name;
    protected $bug_job_id;
    protected $is_bug;
    protected $code_reviewer_id;
    protected $code_review_started;
    protected $code_review_completed;
    protected $budget_id;
    protected $is_internal;

    var $status_changed;

    var $skills = array();

    protected $origStatus = null;

    public function __construct($id = null)
    {
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

    static public function getById($id)
    {
        $workitem = new WorkItem();
        $workitem->loadById($id);
        return $workitem;
    }

    public function loadById($id)
    {
        return $this->load($id);
    }

    protected function load($id = null)
    {
        if ($id === null && !$this->id) {
            throw new Exception('Missing workitem id.');
        } elseif ($id === null) {
            $id = $this->id;
        }
        $query = "
                    SELECT
                        w.id,
                        w.summary,
                        w.creator_id,
                        w.runner_id,
                        w.mechanic_id,
                        w.status,
                        w.project_id,
                        w.notes,
                        w.sandbox,
                        w.bug_job_id,
                        w.is_bug,
                        w.is_internal,
                        w.code_reviewer_id,
                        w.code_review_started,
                        w.code_review_completed,
                        w.status_changed,
                        w.budget_id,
                        p.name AS project_name
                    FROM  ".WORKLIST. " as w
                    LEFT JOIN ".PROJECTS." AS p ON w.project_id = p.project_id
                    WHERE w.id = '" . (int)$id . "'";
        $res = mysql_query($query);
        if (!$res) {
            throw new Exception('MySQL error.');
        }
        $row = mysql_fetch_assoc($res);
        if (!$row) {
            throw new Exception('Invalid workitem id.');
        }
        $this->setId($row['id'])
             ->setSummary($row['summary'])
             ->setCreatorId($row['creator_id'])
             ->setRunnerId($row['runner_id'])
             ->setMechanicId($row['mechanic_id'])
             ->setStatus($row['status'])
             ->setProjectId($row['project_id'])
             ->setNotes($row['notes'])
             ->setSandbox($row['sandbox'])
             ->setBugJobId($row['bug_job_id'])
             ->setIs_internal($row['is_internal'])
             ->setIs_bug($row['is_bug'])
             ->setBudget_id($row['budget_id'])
             ->setCReviewerId($row['code_reviewer_id'] == "" ? 0 : $row['code_reviewer_id'])
             ->setCRStarted($row['code_review_started'])
             ->setCRCompleted($row['code_review_completed'])
             ->setWorkitemSkills();
        $this->status_changed = $row['status_changed'];
        $this->project_name = $row['project_name'];
        return true;
    }

    public function idExists($id)
    {
        $query = '
            SELECT COUNT(*)
            FROM `' . WORKLIST . '`
            WHERE `id` = ' . (int) $id;
        $res = mysql_query($query);
        if (!$res) {
            throw new Exception('MySQL error.');
        }
        $row = mysql_fetch_row($res);
        return (boolean) $row[0];
    }

    public function setId($id)
    {
        $this->id = (int)$id;
        return $this;
    }

    public function getId()
    {
        return $this->id;
    }

    public function setSummary($summary)
    {
        $this->summary = $summary;
        return $this;
    }

    public function getSummary()
    {
        return $this->summary;
    }
    
    public function setBugJobId($id) {
        $this->bugJobId = intval($id);
        return $this;
    }
    public function getBugJobId() {
        return $this->bugJobId;
    }
    

    public function setCreatorId($creatorId)
    {
        $this->creatorId = (int)$creatorId;
        $this->setCreator();
        return $this;
    }

    public function getCreatorId()
    {
        return $this->creatorId;
    }

    public function setRunnerId($runnerId)
    {
        $this->runnerId = (int)$runnerId;
        $this->setRunner();
        return $this;
    }

    /**
     *
     * Get users with fees in work item
     *
     * @return ARRAY list of users id
     */
    public function getUsersWithFeesId() {
    
        $query = " SELECT f.`user_id`
            FROM `" . FEES . "` f INNER JOIN `" . USERS . "` u ON u.`id` = f.`user_id`  
            WHERE f.`worklist_id` = " . $this->id . " AND u.`is_active` = 1";
        $result_query = mysql_query($query);
        if($result_query) {
            $temp_array = array();
            while($row = mysql_fetch_assoc($result_query)) {
                $temp_array[] = $row['user_id'];
            }
            return $temp_array;
        } else {
        	return null;
        }
    }
    
    /**
     *
     * Get users with bids in work item
     *
     * @return ARRAY list of users id
     */
    public function getUsersWithBidsId() {
    
        $query = "SELECT b.`bidder_id`
            FROM `" . BIDS . "` f INNER JOIN `" . USERS . "` u ON u.`id` = b.`bidder_id`  
            WHERE b.`worklist_id` = " . $this->id . " AND u.`is_active` = 1";
        $result_query = mysql_query($query);
        if($result_query) {
            $temp_array = array();
            while($row = mysql_fetch_assoc($result_query)) {
                $temp_array[] = $row['bidder_id'];
            }
            return $temp_array;
        } else {
            return null;
        }
    }
    
    public function getRunnerId()
    {
        return $this->runnerId;
    }

    public function setMechanicId($mechanicId)
    {
        $this->mechanicId = (int)$mechanicId;
        $this->setMechanic();
        return $this;
    }

    public function getMechanicId()
    {
        return $this->mechanicId;
    }
    
    protected function setCreator()
    {
        $user = new User();
        $this->creator = $user->findUserById($this->getCreatorId());
        return $this;
    }
    
    protected function setRunner()
    {
        $user = new User();
        $this->runner = $user->findUserById($this->getRunnerId());
        return $this;
    }
    
    protected function setMechanic()
    {
        $user = new User();
        $this->mechanic = $user->findUserById($this->getMechanicId());
        return $this;
    }
    
    public function getCreator()
    {
        return $this->creator;
    }
    
    public function getRunner()
    {
        return $this->runner;
    }
    
    public function getMechanic()
    {
        return $this->mechanic;
    }

    public function setStatus($status)
    {
        $this->status = $status;
        return $this;
    }
    
    public function resetCRFlags() {
        $this->code_reviewer_id = 0;
        $this->code_review_started = 0;
        $this->code_review_completed = 0;
        return $this;
    }

    public function getStatus()
    {
        return $this->status;
    }

    public function setProjectId($project_id)
    {
        $this->project_id = $project_id;
        return $this;
    }

    public function getProjectId()
    {
        return $this->project_id;
    }

    public function setNotes($notes)
    {
        $this->notes = $notes;
        return $this;
    }

    public function getNotes()
    {
        return $this->notes;
    }
	
    public function setIs_bug($is_bug)
    {
        $this->is_bug = $is_bug;
        return $this;
    }

    public function getIs_bug()
    {
        return $this->is_bug;
    }
	
    public function setBudget_id($budget_id)
    {
        $this->budget_id = $budget_id;
        return $this;
    }

    public function getBudget_id()
    {
        if (!isset($this->budget_id)) {
            $this->budget_id = 0;
        }
        return $this->budget_id;
    }

	
    public function setSandbox($sandbox)
    {
        $this->sandbox = $sandbox;
        return $this;
    }

    public function getSandbox()
    {
        return $this->sandbox;
    }
    
    public function setCReviewerId($code_reviewer_id) {
        $this->code_reviewer_id = $code_reviewer_id;
        return $this;
    }
    
    public function getCReviewerId() {
        return $this->code_reviewer_id;
    }
    
    public function setCRStarted($cr_status) {
        $this->code_review_started = $cr_status;
        return $this;
    }

    public function getCRStarted() {
        return $this->code_review_started;
    }

    public function setCRCompleted($cr_status) {
        $this->code_review_completed = $cr_status;
        return $this;
    }

    public function getCRcompleted() {
        return $this->code_review_completed;
    }
        
    public function setWorkitemSkills($skills = false) {
        // if no array provided, get skill from db
        if (! $skills) {
            $query = "SELECT s.skill
                      FROM ".SKILLS." AS s, ".WORKITEM_SKILLS." AS ws
                      WHERE s.id = ws.skill_id AND ws.workitem_id = " . $this->getId();

            $result = mysql_query($query);
            if (mysql_num_rows($result)) {
                while ($row = mysql_fetch_assoc($result)) {
                    $this->skills[] = $row['skill'];
                }
            }
            
        } else {
            $this->skills = $skills;
        }
    }
    
    public function saveSkills() {
        // clear current skills
        if ($this->getId()) {
            $query = "DELETE FROM ".WORKITEM_SKILLS." WHERE workitem_id=" . $this->getId();
            $result = mysql_query($query);
            foreach ($this->skills as $skill) {
                $query = "INSERT INTO ".WORKITEM_SKILLS." (workitem_id, skill_id)
                          SELECT ".$this->getId().", id FROM ".SKILLS." WHERE skill='". trim($skill) ."'";
                mysql_query($query) || die('There was an error ' . mysql_error() . ' QUERY: ' . $query);
            }
            
            return true;
        } else {
            return false;
        }
    
    }

    public function getSkills() {
        return $this->skills;
    }

    /**
     * A method to check if this job is internal / for the hifi team.
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

    /**
     * @return bool Whether the operation was successful
     */
    public function toggleInternal($user_id) {
    
        $user = new User();
        $user->findUserById($user_id);
        
        if ($user->isInternal()){
            if ($this->isInternal()) {
                $this->setIs_internal(false);
            } else {
                $this->setIs_internal(true);
            }
        } else {
            return false;
        }

        $this->save();

        return true;
    }

    public static function getStates()
    {
        $states = array();
        $query = 'SELECT DISTINCT `status` FROM `' . WORKLIST . '` WHERE `status` != "Draft" LIMIT 0 , 30';
        $result = mysql_query($query);
        if ($result) {
            while ($row = mysql_fetch_assoc($result)) {
                $states[] = $row['status'];
            }
        }
        return $states;
    }

    public function getRepository() {
        $query = "SELECT `repository` FROM `projects` WHERE `project_id` = " . $this->getProjectId();
        $rt = mysql_query($query);
        if (mysql_num_rows($rt)) {
            $row = mysql_fetch_array($rt);
            $repository = $row['repository'];
            return $repository;
        } else {
            return false;
        }
    }
    
    protected function insert()
    {
        $query = "INSERT INTO ".WORKLIST." (summary, creator_id, runner_id, status,".
                 "project_id, notes, bug_job_id, created, is_bug, status_changed, is_internal) ".
            " VALUES (".
            "'".mysql_real_escape_string($this->getSummary())."', ".
            "'".mysql_real_escape_string($this->getCreatorId())."', ".
            "'".mysql_real_escape_string($this->getRunnerId())."', ".
            "'".mysql_real_escape_string($this->getStatus())."', ".
            "'".mysql_real_escape_string($this->getProjectId())."', ".
            "'".mysql_real_escape_string($this->getNotes())."', ".
            "'".intval($this->getBugJobId())."', ".
            "NOW(), ".
            "'".$this->getIs_bug()."', ".
            "NOW(), ".
            mysql_real_escape_string($this->getIs_internal()) . ")";
        $rt = mysql_query($query);

        $this->id = mysql_insert_id();

        /* Keep track of status changes including the initial one */
        $status = mysql_real_escape_string($this->status);
        $query = "INSERT INTO ".STATUS_LOG." (worklist_id, status, user_id, change_date) VALUES (".$this->getId().", '$status', ".$_SESSION['userid'].", NOW())";
        mysql_unbuffered_query($query);

        if($this->status == 'Bidding') {
            $this->tweetNewJob();
        }

        return $rt ? 1 : 0;
    }

    protected function update()
    {
        /* Keep track of status changes */
        if ($this->origStatus != $this->status) {
            if ($this->status == 'Bidding') {
                $this->tweetNewJob();
            }
                if ($this->status == 'Code Review') {
                $this->status = 'Review';
                } else {
                $status = mysql_real_escape_string($this->status);
                }
            if(isset($_SESSION['userid']) && !empty($_SESSION['userid'])) {
                $user_id = $_SESSION['userid'];
            } else {
                $user_id = 0 ; // this means auto pass script has changed the status to PASS
            }
            
            $query = "INSERT INTO ".STATUS_LOG." (worklist_id, status, user_id, change_date) VALUES (".$this->getId().", '$status', ".$user_id.", NOW())";
            mysql_unbuffered_query($query);
        }

        $query = 'UPDATE '.WORKLIST.' SET
            summary= "'. mysql_real_escape_string($this->getSummary()).'",
            notes="'.mysql_real_escape_string($this->getNotes()).'",
            project_id="'.mysql_real_escape_string($this->getProjectId()).'",
            status="' .mysql_real_escape_string($this->getStatus()).'",
            status_changed=NOW(),
            runner_id="' .intval($this->getRunnerId()). '",
            bug_job_id="' .intval($this->getBugJobId()).'",
            is_internal=' . (int) $this->getIs_internal() . ',
            is_bug='.($this->getIs_bug() ? 1 : 0).',
            budget_id='.$this->getBudget_id().',
            code_reviewer_id=' . $this->getCReviewerId() . ',
            code_review_started='.$this->getCRStarted().',
            code_review_completed='.$this->getCRCompleted().',
            sandbox ="' .mysql_real_escape_string($this->getSandbox()).'"';
        $query .= ' WHERE id='.$this->getId();
        $result_query = mysql_query($query);
        if($result_query) {
            return 1;
        }
        error_log($query);
        return 0;
    }

    protected function tweetNewJob()
    {
        /*
        if (!defined('TWITTER_OAUTH_SECRET') || TWITTER_OAUTH_SECRET=='' ) {
            return false;
        }
         
        if (empty($_SERVER['HTTPS']))
        {
            $prefix    = 'http://';
            $port    = ((int)$_SERVER['SERVER_PORT'] == 80) ? '' :  ":{$_SERVER['SERVER_PORT']}";
        }
        else
        {
            $prefix    = 'https://';
            $port    = ((int)$_SERVER['SERVER_PORT'] == 443) ? '' :  ":{$_SERVER['SERVER_PORT']}";
        }
        $link = $prefix . $_SERVER['HTTP_HOST'] . $port . '/rw/?' . $this->id;
        $summary_max_length = 140-strlen('New job: ')-strlen($link)-1;
        $summary = substr(html_entity_decode($this->summary, ENT_QUOTES), 0, $summary_max_length);
        
        $connection = new TwitterOAuth(TWITTER_CONSUMER_KEY, TWITTER_CONSUMER_SECRET, TWITTER_OAUTH_TOKEN, TWITTER_OAUTH_SECRET);
        $content = $connection->get('account/verify_credentials');
         
        $message='New job: ' . $summary . ' ' . $link;
        $connection->post('statuses/update', array('status' => $message));
        */
    }


    public function save() {
        if(isset($this->id)){
            if ($this->idExists($this->getId())) {
                if ($this->update()) {
                    $this->saveSkills($this->skills);
                    return true;
                } else {
                    error_log("error1 in update, save function");
                }
            } else {
                if ($this->insert()) {
                    $this->saveSkills($this->skills);
                    return true;
                } else {
                    error_log("error2 in insert, save function");
                }
            }
        } else {
            if ($this->insert()) {
                $this->saveSkills($this->skills);
            } else {
                error_log("error3 in insert, save function");
            }
        }

        return false;
    }

    /**
     * @param int $worklist_id
     * @return array|null
     */
    public function getWorkItem($worklist_id)
    {
        $query = "SELECT w.id, w.summary,w.creator_id,w.runner_id, w.mechanic_id, ".
                 " u.nickname AS runner_nickname, u.id AS runner_id,".
                 " uc.nickname AS creator_nickname, um.nickname AS mechanic_nickname, w.status, w.notes, ".
                 " w.project_id, p.name AS project_name, p.repository AS repository, p.website AS p_website,
                  w.sandbox, w.bug_job_id, w.is_bug, w.budget_id, b.reason AS budget_reason, b.giver_id AS budget_giver_id
                  FROM  " . WORKLIST . " as w
                  LEFT JOIN " . USERS . " as uc ON w.creator_id = uc.id
                  LEFT JOIN " . USERS . " as um ON w.mechanic_id = um.id
                  LEFT JOIN " . USERS . " as u ON w.runner_id = u.id
                  LEFT JOIN " . PROJECTS . " AS p ON w.project_id = p.project_id
                  LEFT JOIN " . BUDGETS . " AS b ON w.budget_id = b.id
                  WHERE w.id = '$worklist_id'";
        $result_query = mysql_query($query);
        $row =  $result_query ? mysql_fetch_assoc($result_query) : null;
        return !empty($row) ? $row : null;
    }

    /**
     * @param int $worklist_id
     * @param bool $expired If true, return expired bids
     * @return array|null
     */
    public function getBids($worklist_id, $expired = true) {
        $having = '';
        // code is here in case we want to start including expired bids
        // default behaviour is to ignore expired bids
        // Demenza 22 of August 2011 - Something needs to be done here, to change the behavior of true/false to the if(true/false) show espired bids.

        $query = "
            SELECT bids.`id`, bids.`bidder_id`, bids.`email`, u.`nickname`, bids.`bid_amount`, bids.`accepted`,
                UNIX_TIMESTAMP(bids.`bid_created`) AS `unix_bid_created`,
                TIMESTAMPDIFF(SECOND, NOW(), bids.`bid_expires`) AS `expires`,
                TIMESTAMPDIFF(SECOND, NOW(), bids.`bid_done`) AS `future_delta`,
                bids.`bid_done_in` AS done_in,
                DATE_FORMAT(bids.`bid_done`, '%m/%d/%Y') AS `bid_done`,
                UNIX_TIMESTAMP(`bid_done`) AS `unix_done_by`,
                UNIX_TIMESTAMP(bids.`bid_done`) AS `unix_done_full`,
                bids.`notes`,
                UNIX_TIMESTAMP(f.`date`) AS `unix_bid_accepted`,
                UNIX_TIMESTAMP(NOW()) AS `unix_now`,
                bids.`bid_created` AS `bid_created_full`
            FROM `".BIDS. "` AS bids
                INNER JOIN `".USERS."` AS u ON (u.id = bids.bidder_id)
                LEFT JOIN ".FEES." AS f ON (f.bid_id=bids.id)
            WHERE bids.worklist_id={$worklist_id}
                AND bids.withdrawn = 0
            $having
            ORDER BY bids.`id` DESC";
        $result_query = mysql_query($query);
        if ($result_query) {
            $temp_array = array();
            while ($row = mysql_fetch_assoc($result_query)) {
                // skip expired bids if they have not been accepted
                // Doesn't skip expired bids anymore - Demenza 22 of August 2011
                if (! empty($row['unix_bid_accepted'])) {
                    $row['expires'] = null;
                    $temp_array[] = $row;
                } else if (empty($row['expires'])) {
                    // take any bid with bid_expires written as 0000-00-00 00:00:00 -mika - Mar 31 2013
                    $temp_array[] = $row;
                } else if (! empty($row['expires']) && empty($row['unix_bid_accepted'])) {
                    // skip expired bids that are not accepted;
                    // Had to change this, because of oddness of this if() statement
                    // The true/false in the top doesn't work at all, see note at the top.
                    if ($row['expires'] < 0) {
                        // the bid has expired, include it only if $expired is true
                        if ($expired) {
                            $temp_array[] = $row;
                        }
                    } else {
                        $temp_array[] = $row;
                    }
                } else if (! $expired && ($row['future_delta'] < 0 || $row['expires'] < 0)) {
                    // do not return this bid, it has expired
                } else {
                    $temp_array[] = $row;
                }
            }
            return $temp_array;
        } else {
            return null;
        }
    }

    public function getProjectRunnersId() {
        // Fix for #15353: Only select active runners 20-SEP-2011 <danS>
        $query = " SELECT DISTINCT(w.`runner_id`) as runner
            FROM `" . WORKLIST . "` AS w INNER JOIN `" . USERS . "` u ON u.`id` = w.`runner_id`
            WHERE w.`project_id` = " . $this->getProjectId() . " 
              AND u.`is_runner` = 1 
              AND u.`is_active` = 1";
        $result_query = mysql_query($query);
        if($result_query) {
            $temp_array = array();
            while($row = mysql_fetch_assoc($result_query)) {
                $temp_array[] = $row['runner'];
            }
            return $temp_array;
        } else {
            return null;
        }
    }
    
    public function getIsRelRunner() {
        $query = "SELECT r.`runner_id` as relrunner
                  FROM `" . PROJECT_RUNNERS . "` as r 
                  WHERE r.`project_id` = " . $this->getProjectId() . "
                  AND r.`runner_id` = " . $_SESSION['userid'] . " ";
        $result = mysql_query($query);
        
        if ($result && mysql_num_rows($result) > 0) {
    
            return true;
        }
        return false;
    
    }
        
    public function getFees($worklist_id)
    {
        $query = "SELECT fees.`id`, fees.`amount`, u.`nickname`, fees.`desc`,fees.`user_id`, DATE_FORMAT(fees.`date`, '%m/%d/%Y') as date, fees.`paid`, fees.`bid_notes`
            FROM `".FEES. "` as fees LEFT OUTER JOIN `".USERS."` u ON u.`id` = fees.`user_id`
            WHERE worklist_id = ".$worklist_id."
            AND fees.withdrawn = 0 ";

        $result_query = mysql_query($query);
        if($result_query){
            $temp_array = array();
            while($row = mysql_fetch_assoc($result_query)) {
            
                // this is to make sure to remove extra slashes 11-MAR-2011 <webdev>
                $row['desc'] = stripslashes($row['desc']);
                
                $temp_array[] = $row;
            }
            return $temp_array;
        } else {
            return null;
        }
    }

    public function placeBid($mechanic_id, $username, $itemid, $bid_amount, $done_in, $expires, $notes) {
        if($this->status == 'Bidding') {
            $bid_expires = strtotime($expires);
            $query =  "INSERT INTO `".BIDS."` (`id`, `bidder_id`, `email`, `worklist_id`, `bid_amount`, `bid_created`, `bid_expires`, `bid_done_in`, `notes`)
                       VALUES (NULL, '$mechanic_id', '$username', '$itemid', '$bid_amount', NOW(), FROM_UNIXTIME('$bid_expires'), '$done_in', '$notes')";
        }
        else if($this->status == 'Suggestion') {
            $query =  "INSERT INTO `".BIDS."` (`id`, `bidder_id`, `email`, `worklist_id`, `bid_amount`, `bid_created`, `bid_expires`, `bid_done_in`, `notes`)
                       VALUES (NULL, '$mechanic_id', '$username', '$itemid', '$bid_amount', NOW(), '1 years', '$done_in', '$notes')";
            mysql_unbuffered_query("UPDATE `" . WORKLIST . "` SET  `" . WORKLIST . "` . `status` = 'Bidding',
                                        `status_changed`=NOW() WHERE  `" . WORKLIST . "` . `id` = '$itemid'");
             }
        return mysql_query($query) ? mysql_insert_id() : null;
    
        }
    
    public function updateBid($bid_id, $bid_amount, $done_in, $bid_expires, $timezone, $notes) {
        $bid_expires = strtotime($bid_expires);
        if ($bid_id > 0) {
        $query =  "UPDATE `".BIDS."` SET `bid_amount` = '".$bid_amount."' ,`bid_done_in` = '$done_in', `bid_expires` = FROM_UNIXTIME({$bid_expires}), `notes` = '".$notes."' WHERE id = '".$bid_id."'";
            mysql_query($query);
        }

        return $bid_id;
    }
    
    public function getUserDetails($mechanic_id)
    {
        $query = "SELECT nickname, username FROM ".USERS." WHERE id='{$mechanic_id}'";
        $result_query = mysql_query($query);
        return  $result_query ?  mysql_fetch_assoc($result_query) : null;
    }
// look for getOwnerSummary !!!
    public function getRunnerSummary($worklist_id) {
        $query = "SELECT `" . USERS . "`.`id` as id, `username`, `summary`"
          . " FROM `" . USERS . "`, `" . WORKLIST . "`"
          . " WHERE `" . WORKLIST . "`.`runner_id` = `" . USERS . "`.`id` AND `" . WORKLIST . "`.`id` = ".$worklist_id;
        $result_query = mysql_query($query);
        return $result_query ? mysql_fetch_assoc($result_query) : null ;
    }

    public function getSumOfFee($worklist_id)
    {
        $query = "SELECT SUM(`amount`) FROM `".FEES."` WHERE worklist_id = ".$worklist_id . " and withdrawn = 0 ";
        $result_query = mysql_query($query);
        $row = $result_query ? mysql_fetch_row($result_query) : null;
        return !empty($row) ? $row[0] : 0;
    }

    /**
     * Given a bid_id, get the corresponding worklist_id. If this is loaded compare the two ids
     * and throw an error if the don't match.  If not loaded, load the item.
     *
     * @param int $bidId
     * @return int
     */
    public function conditionalLoadByBidId($bid_id)
    {
        $query = "SELECT `worklist_id` FROM `".BIDS."` WHERE `id` = ".(int)$bid_id;
        $res = mysql_query($query);
        if (!$res || !($row = mysql_fetch_row($res))) {
            throw new Exception('Bid not found.');
        }
        if ($this->id && $this->id != $row[0]) {
            throw new Exception('Bid belongs to another work item.');
        } else if (!$this->id) {
            $this->load($row[0]);
        }
    }
				
    public function loadStatusByBidId($bid_id)
    {
        $query = "SELECT `worklist_id`," . WORKLIST . ".status FROM `".BIDS."` LEFT JOIN " . WORKLIST . " ON " . BIDS. ".worklist_id = " . WORKLIST . ".id WHERE " . BIDS . ".`id` = ".(int)$bid_id;
        $res = mysql_query($query);
        if (!$res || !($row = mysql_fetch_row($res))) {
            throw new Exception('Bid not found.');
        }
        return $row[1];
    }

    /**
     * Checks if a workitem has any accepted bids
     *
     * @param int $worklistId
     * @return boolean
     */
    public function hasAcceptedBids()
    {
        $query = "SELECT COUNT(*) FROM `".BIDS."` ".
            "WHERE `worklist_id`=".(int)$this->id." AND `accepted` = 1 AND `withdrawn` = 0";
        $res   = mysql_query($query);
        if (!$res) {
            throw new Exception('Could not retrieve result.');
        }
        $row = mysql_fetch_row($res);
        return ($row[0] > 0);
    }

    /**
     * If a given bid is accepted, the method returns TRUE.
     *
     * @param int $bidId
     * @return boolean
     */
    public function bidAccepted($bidId)
    {
        $query = 'SELECT COUNT(*) FROM `' . BIDS . '` WHERE `id` = ' . (int)$bidId . ' AND `accepted` = 1';
        $res   = mysql_query($query);
        if (!$res) {
            throw new Exception('Could not retrieve result.');
        }
        $row = mysql_fetch_row($res);
        return ($row[0] == 1);
    }

    // Accept a bid given it's Bid id
    public function acceptBid($bid_id, $budget_id = 0, $is_mechanic = true) {
        $this->conditionalLoadByBidId($bid_id);
        /*if ($this->hasAcceptedBids()) {
            throw new Exception('Can not accept an already accepted bid.');
        }*/
        
        
        $user_id = isset($_SESSION['userid']) ? (int)$_SESSION['userid'] : 0;
        $is_runner = isset($_SESSION['is_runner']) ? (int)$_SESSION['is_runner'] : 0;
        
        // If a bid is being accepted, and the runner for the workitem does not exist (incase a bid went from suggested straight
        // to working), then we should set the person accepting the bid as the runner;
        if (!$this->getRunnerId()) {
            $this->setRunnerId($user_id);
        }
        
        $res = mysql_query('SELECT * FROM `'.BIDS.'` WHERE `id`=' . $bid_id);
        $bid_info = mysql_fetch_assoc($res);
        $workitem_info = $this->getWorkItem($bid_info['worklist_id']);
        


        // Get bidder information
        $bidder = new User;
        if (! $bidder->findUserById($bid_info['bidder_id'])) {
            // If bidder doesn't exist, return false. Don't want to throw an
            // exception because it would kill multiple bid acceptances
            return false;
        }

        $bid_info['nickname'] = $bidder->getNickname();

        
        $project = new Project($this->getProjectId());
        
        // Get the repo for this project
        $repository = $this->getRepository();
        $job_id = $this->getId();
        /* Verify whether the user already has this repo forked on his account
        If not create the fork
        Check for existing unix account in dev.  If new, make call to create account
        */
        $GitHubUser = new User($bid_info['bidder_id']);
        $url = TOWER_API_URL;
        $fields = array(
                'action' => 'create_unixaccount',
                'nickname' => $bidder->getNickname()
        );
        $result = CURLHandler::Post($url, $fields);
        if (!$GitHubUser->verifyForkExists($project)) {
            $forkStatus = $GitHubUser->createForkForUser($project);
            $bidderEmail = $bidder->getUsername();
            $emailTemplate = 'forked-repo';
            $data = array(
                'project_name' => $forkStatus['data']['full_name'],
                'nickname' => $bidder->getNickname(),
                'users_fork' => $forkStatus['data']['git_url'],
                'master_repo' => str_replace('https://', 'git://', $project->getRepository())
            );
            $senderEmail = 'Worklist <contact@worklist.net>';
            sendTemplateEmail($bidderEmail, $emailTemplate, $data, $senderEmail);
            sleep(10);
        }
        // Create a branch for the user
        if (!$forkStatus['error']) {
            $branchStatus = $GitHubUser->createBranchForUser($job_id, $project);
            $bidderEmail = $bidder->getUsername();
            $emailTemplate = 'branch-created';
            $data = array(
                'branch_name' => $job_id,
                'nickname' => $bidder->getNickname(),
                'users_fork' => $forkStatus['data']['git_url'],
                'master_repo' => str_replace('https://', 'git://', $project->getRepository())
            );

            $bid_info = array_merge($data, $bid_info);
        }
        if (!$branchStatus['error']) {
            $bid_info['sandbox'] = $branchStatus['branch_url'];
        }
        
        $bid_info['bid_done'] = strtotime('+' . $bid_info['bid_done_in'], time());

        // Adding transaction wrapper around steps
        if (mysql_query('BEGIN')) {

            // changing mechanic of the job
            $sql = "UPDATE `" . WORKLIST  ."` SET " .
                ($is_mechanic ? "`mechanic_id` =  '" . $bid_info['bidder_id'] . "', " : '') .
                ($is_runner && $user_id > 0 && $workitem_info['runner_id'] == 0 ? "`runner_id` =  '".$user_id."', " : '') .
                " `status` = 'In Progress',`status_changed`=NOW(),`sandbox` = '" . $bid_info['sandbox'] . "',`budget_id` = " . $budget_id .
                " WHERE `" . WORKLIST . "`.`id` = " . $bid_info['worklist_id'];
            if (! $myresult = mysql_query($sql)) {
                error_log("AcceptBid:UpdateMechanic failed: ".mysql_error());
                mysql_query("ROLLBACK");
                return false;
            }
            // marking bid as "accepted"
            if (! $result = mysql_query("UPDATE `".BIDS."` SET `accepted` =  1, `bid_done` = FROM_UNIXTIME('".$bid_info['bid_done']."') WHERE `id` = ".$bid_id)) {
                error_log("AcceptBid:MarkBid failed: ".mysql_error());
                mysql_query("ROLLBACK");
                return false;
            }
        
            // adding bid amount to list of fees
            if (! $result = mysql_query("INSERT INTO `".FEES."` (`id`, `worklist_id`, `amount`, `user_id`, `desc`, `bid_notes`, `date`, `bid_id`) VALUES (NULL, ".$bid_info['worklist_id'].", '".$bid_info['bid_amount']."', '".$bid_info['bidder_id']."', 'Accepted Bid', '".mysql_real_escape_string($bid_info['notes'])."', NOW(), '$bid_id')")) {
                error_log("AcceptBid:Insert Fee failed: ".mysql_error());
                mysql_query("ROLLBACK");
                return false;
            }

            $creator_fee = 0;
            $creator_fee_desc = 'Creator';
            $creator_fee_added = false;

            $runner_fee = 0;
            $runner_fee_desc = 'Runner';
            $runner_fee_added = false;

            $accepted_bid_amount = $bid_info['bid_amount'];

            $fee_category = '';
            $is_expense = '';
            $is_rewarder = '';

            $fees = $this->getFees($this->getId());

            foreach ($fees as $fee) {
                // find the accepted bid amount
                if ($fee['desc'] == 'Accepted Bid') {
                    $accepted_bid_amount = $fee['amount'];
                }

                if (preg_match($reviewer_fee_desc, $fee['desc'])) {
                    $reviewer_fee_added = true;
                }
                
                if ($fee['desc'] == $creator_fee_desc) {
                    $creator_fee_added = true;
                }
                if ($fee['desc'] == $runner_fee_desc) {
                    $runner_fee_added = true;
                }                
            }

            // get project creator role settings, if not available, no fee is added
            // and will need to be added manually if applicable
            $project = new Project();
            $project_roles = $project->getRoles($this->getProjectId(), "role_title = 'Creator'");

            if (count($project_roles) != 0 && ! $creator_fee_added) {
                // fees are not automatically created for internal users
                if (! $this->getCreator()->isInternal()) {

                    $creator_role = $project_roles[0];
                    if ($creator_role['percentage'] !== null && $creator_role['min_amount'] !== null) {

                        $creator_fee = ($creator_role['percentage'] / 100) * $accepted_bid_amount;
                        if ((float) $creator_fee < $creator_role['min_amount']) {
                            $creator_fee = $creator_role['min_amount'];
                        }

                        // add the fee
                        
                        /**
                         * @TODO - We call addfees and then deduct from budget
                         * seems we should add the deduction process to the AddFee
                         * function
                         * 
                         */
                        AddFee($this->getId(), $creator_fee, $fee_category, $creator_fee_desc, $this->getCreatorId(), $is_expense, $is_rewarder);
                        // and reduce the runners budget
                        $myRunner = new User();
                        $myRunner->findUserById($this->getRunnerId());
                        $myRunner->updateBudget(-$creator_fee, $this->getBudget_id());
                    }
                }
            }

            $project_roles = $project->getRoles($this->getProjectId(), "role_title = 'Runner'");
            if (count($project_roles) != 0 && ! $runner_fee_added) {
                    
                error_log("[FEES] we have a role for runner");
                $runner_role = $project_roles[0];

                // fees are not automatically created for internal users
                if (! $this->getRunner()->isInternal()) {
                    if ($runner_role['percentage'] !== null && $runner_role['min_amount'] !== null) {

                        $runner_fee = ($runner_role['percentage'] / 100) * $accepted_bid_amount;
                        if ((float) $runner_fee < $runner_role['min_amount']) {
                            $runner_fee = $runner_role['min_amount'];
                        }
                        // add the fee 
                        AddFee($this->getId(), $runner_fee, $fee_category, $runner_fee_desc, $this->getRunnerId(), $is_expense, $is_rewarder);
                        // and reduce the runners budget
                        $myRunner = new User();
                        $myRunner->findUserById($this->getRunnerId());
                        $myRunner->updateBudget(-$runner_fee, $this->getBudget_id());
                    }
                }
            }

            // add an entry to the status log
            $status_sql = "
                INSERT INTO " . STATUS_LOG . " (worklist_id, status, user_id, change_date)
                VALUES({$bid_info['worklist_id']}, 'Working', {$_SESSION['userid']}, NOW())";
            if (! $result = mysql_query($status_sql)) {
                error_log("AcceptedBid:Insert status log failed: " . mysql_error());
                mysql_query("ROLLBACK");
                return false;
            }

            // When we get this far, commit and return bid_info
            if (mysql_query('COMMIT')) {
                $bid_info['summary'] = getWorkItemSummary($bid_info['worklist_id']);
                $this -> setMechanicId($bid_info['bidder_id']);
                return $bid_info;
            } else {
                return false;
            }
        } else {
            return false;
        }
    }

    public function validateAction($action, $action_error) {
        
        switch ($action) {
            case 'withdraw_bid':
                if ($this->getStatus() == 'Done') {
                    $action_error = 'Cannot withdraw bid when status is Done';
                    return false;
                }
                
                return $action;
                break;

            case 'decline_bid':
                if ($this->getStatus() == 'Done') {
                    $action_error = 'Cannot decline bid when status is Done';
                    return false;
                }
                
                return $action;
                break;
				
            case 'save_workitem':
                if ($this->getStatus() == 'Done') {
                    return false;
                }
                
                return $action;
                break;

            case 'place_bid':
                if ($this->getStatus() != 'Bidding') {
                    if ($this->getStatus() != 'Suggestion') {
                            $action_error = 'Cannot place bid when workitem is in this status';
                            return false;
                    }
                }
                    
                return $action;
                break;
            
            case 'edit_bid':
                if ($this->getStatus() != 'Bidding') {
                    if ($this->getStatus() != 'Suggestion') {
                    $action_error = 'Cannot edit bid for this workitem status';
                    return false;
                    }
                }
                    
                return $action;
                break;
            
            case 'add_fee':
                if ($this->getStatus() == 'Done') {
                    $action_error = 'Cannot add fee when status is Done';
                    return false;
                }
                
                return $action;
                break;

            case 'add_tip':
                if ($this->getStatus()== 'Done' || $this->getStatus() == 'Pass' || $this->getStatus() == 'Suggestion') {
                    $action_error = 'Cannot add tip when status is Done, Pass or Suggestion';
                    return false;
                }
                
                return $action;
                break;
                
            case 'view_bid':
                if ($this->getStatus() != 'Bidding') {
                    if ($this->getStatus() != 'Suggestion') {
                    $action_error = 'Cannot accept bid when status is not Bidding';
                    return false;
                    }
                }
                
                return $action;
                break;
                
            case 'accept_bid':
                if ($this->getStatus() != 'Bidding') {
                    if ($this->getStatus() != 'Suggestion') {
                    $action_error = 'Cannot accept bid when status is not Bidding';
                    return false;
                    }
                }
                
                return $action;
                break;

            case 'accept_multiple_bid':
                if ($this->getStatus() != 'Bidding') {
                    if ($this->getStatus() != 'Suggestion') {
                    $action_error = 'Cannot accept bid when status is not Bidding';
                    return false;
                    }
                }
                
                return $action;
                break;

            case 'status-switch':
                return $action;
                break;
                
            case 'save-review-url':
                if ($this->getStatus() == 'Done') {
                    $action_error = 'Cannot change review URL when status is Done';
                    return false;
                }
                
                return $action;
                break;
                
            case 'new-comment':
                if ($this->getStatus() == 'Done') {
                    $action_error = 'Cannot add comment when status is Done';
                    return false;
                }
                
                return $action;
                break;
                
            case 'edit':
                return $action;
                break;

            case 'cancel_codereview':
                return $action;
                break;
                
            case 'finish_codereview':
                return $action;
                break;

            default:
                $action_error = 'Invalid action';
                return false;
        }
    }

    function isUserFollowing($id) {
        $query = 'SELECT COUNT(*) FROM ' . TASK_FOLLOWERS . ' WHERE user_id = ' . (int)$id . ' AND workitem_id=' . $this->id;
        $res = mysql_query($query);
        if (!$res) {
            throw new Exception('Could not retrieve result.');
        }
        $row = mysql_fetch_row($res);
        return (boolean)$row[0];
    }

    function toggleUserFollowing($user_id) {
        $isFollowing = $this->isUserFollowing($user_id);
        if($isFollowing == true) {
            $query = 'DELETE FROM ' . TASK_FOLLOWERS . ' WHERE user_id=' . $user_id . ' AND workitem_id = ' . $this->id;
        } else {
            $query = 'INSERT INTO ' . TASK_FOLLOWERS . ' (user_id, workitem_id) VALUES (' . $user_id . ' , ' . $this->id . ')';
        }
//		return $query;
        $res = mysql_query($query);
        if (!$res) {
            throw new Exception('Could not retrieve result.');
        }
        return !$isFollowing;
    }

    function getFollowersId() {
        $followers = array();
        $query = ' SELECT f.`user_id` 
            FROM `' . TASK_FOLLOWERS . '` f INNER JOIN `' . USERS . '` u ON u.`id` = f.`user_id` 
            WHERE f.`workitem_id` = ' . $this->id . '
              AND u.`is_active` = 1';
        $res = mysql_query($query);
        while($row = mysql_fetch_row($res)) {
            $followers[]= $row[0];
        }
        
        return $followers;
    }
    function getReviewNotifsId() {
    $user_id = $_SESSION['userid'];
        $reviewNotifs = array();
        $query = 'SELECT u.`id`
            FROM `' . USERS . '` u
            WHERE ((`review_notif` = 1
            AND `id` != ' . $user_id . ')
            OR (self_notif = 1 and `id` = ' . $user_id . ')
            AND `is_active` = 1)';
        $res = mysql_query($query);
        while($row = mysql_fetch_row($res)) {
            $reviewNotifs[]= $row[0];
        }
            return $reviewNotifs;
    }
    public function getSandboxPath() {

        $url_array = parse_url($this->sandbox);

        if ($url_array['path']) {
            $path_array = explode('/', $url_array['path']);
            if (count($path_array) > 2 && strpos($path_array[1], '~') == 0) {
                $path = substr($path_array[1], 1, strlen($path_array[1]) - 1);
                $path .= DIRECTORY_SEPARATOR . 'public_html' . DIRECTORY_SEPARATOR . $path_array[2];
                return $path;
            }
        }
        return '';
    }

    public function startCodeReview($reviewer_id) {
        if ($this->status != 'Review' || $this->code_review_started != 0) {
            return null; // CR is only allowed for REVIEW items without the CR started
        }

        $this->setCRStarted(1);
        $this->setCReviewerId($reviewer_id);
        $this->save();

        return true;
    }

    public function addFeesToCompletedJob($include_review = false) {
        // workitem is DONE, calculate the creator fee based on project roles
        // and accepted bid
        if ($this->hasAcceptedBids()) {
            $reviewer_fee = 0;
            $reviewer_fee_desc = '/^Code Review - comment/';
            $reviewer_fee_added = false;


            $fees = $this->getFees($this->getId());
            foreach ($fees as $fee) {
                // find the accepted bid amount
                if ($fee['desc'] == 'Accepted Bid') {
                    $accepted_bid_amount = $fee['amount'];
                }

                if (preg_match($reviewer_fee_desc, $fee['desc'])) {
                    $reviewer_fee_added = true;
                }
            }
            
            if (!$reviewer_fee_added && $include_review) {
                $project = new Project();
                $project_roles = $project->getRoles($this->getProjectId(), "role_title = 'Reviewer'");
                if (count($project_roles) != 0) {
                    error_log("[FEES] we have a role for reviewer");
                    $reviewer_role = $project_roles[0];
                    if ($reviewer_role['percentage'] !== null && $reviewer_role['min_amount'] !== null) {
                        $reviewer_fee = ($reviewer_role['percentage'] / 100) * $accepted_bid_amount;
                        if ((float) $reviewer_fee < $reviewer_role['min_amount']) {
                            $reviewer_fee = $reviewer_role['min_amount'];
                        }
                        // add the fee
                        $reviewer_fee_detail = 'Code Review - comment';
                        AddFee($this->getId(), 
                               $reviewer_fee, 
                               $fee_category, 
                               $reviewer_fee_detail, 
                               $this->getCReviewerId(), 
                               $is_expense, 
                               $is_rewarder);
                        // and reduce the runners budget
                        $myRunner = new User();
                        $myRunner->findUserById($this->getRunnerId());
                        $myRunner->updateBudget(-$runner_fee, $this->getBudget_id());
                    }
                }
            }
        }
    }
    function flagAll0FeesAsPaid() {
        $query = "UPDATE " . FEES . " SET paid = 1, paid_date=NOW(), user_paid =" . $this->getRunnerId() 
            . " WHERE worklist_id = " . $this->id . " AND amount = 0 AND withdrawn = 0";
        if (! $result = mysql_query($query)) {
            return false;
        }
        return true;
    }
}// end of the class
