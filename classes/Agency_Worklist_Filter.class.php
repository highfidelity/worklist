<?php
//  Copyright (c) 2010, LoveMachine Inc.
//  All Rights Reserved.
//  http://www.lovemachineinc.com

class Agency_Worklist_Filter {
    protected $name = '.worklist';

    // Filter for worklist
    protected $user = 0;
    protected $runner = 0;
    protected $status = 'Bidding,In Progress,QA Ready,Review,Merged,Suggestion';
    protected $query = '';
    protected $sort = 'delta';
    protected $dir = 'ASC';
    protected $page = 1;
    protected $inComment = 1;
    protected $project_id = 0;
    protected $project = "";
    protected $fund_id = -1;

    // Additional filter for reports
    protected $paidstatus = 'ALL';
    protected $order = 'name';
    protected $start = '';
    protected $end = '';
    // Additional filter for type for reports page
    // 30-APR-2010 <Yani>
    protected $type = "ALL";

    // Additional filter for job in PayPal reports
    // 30-APR-2010 <Andres>
    protected $job = 0;

    // Additional filter for worklist
    // 15-JAN-2011 <Reji>
    protected $subsort = "delta";
    protected $nickname = "";
    protected $filterMobile = false;
    protected $following = false;
    protected $participated = false;

    public function getPaidstatus()
    {
        return $this->paidstatus;
    }

    public function setPaidstatus($paidStatus)
    {
        $this->paidstatus = $paidStatus;
        return $this;
    }

    public function getOrder()
    {
        return $this->order;
    }

    public function setOrder($order)
    {
        $this->order = $order;
    }

    public function getStart()
    {
        return$this->start;
    }

    public function setStart($start)
    {
        $this->start = $start;
        return $this;
    }

    public function getEnd()
    {
        return $this->end;
    }

    public function setEnd($end)
    {
        $this->end = $end;
        return $end;
    }

    // getter for $type
    // @return type of the fee
    // 30-APR-2010 <Yani>
    public function getType()
    {
        return $this->type;
    }

    // setter for $type
    // @param $type type to set
    // 30-APR-2010 <Yani>
    public function setType($type)
    {
        $this->type = $type;
    }

    // getter for $job
    // @return job_id number
    // 30-APR-2010 <Andres>
    public function getJob() {
       return $this->job;
    }

    // setter for $job
    // @param $job job id number
    // 30-APR-2010 <Andres>
    public function setJob($job) {
        if ($id = ltrim($job, '#')) {
            return $this->job = (int) $id;
        } else {
           return $this->job = (int) $job;
        }
    }

    /**
     * @return the $project
     */
    public function getProject() {
        return $this->project;
    }

    public function getInComment() {
        return $this->inComment;
    }

    public function getProjectId() {
        return $this->project_id;
    }

    public function getFund_id() {
        return $this->fund_id;
    }

    /**
     * @return the $user
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * @return the $runner
     */
    public function getRunner()
    {
        return $this->runner;
    }

    /**
     * @return the $status
     */
    public function getStatus()
    {
        return $this->status;
    }
    /**
     * @return the true if status is in the list
     */
    public function inStatus($status)
    {
        if (strpos( "/".$this->status."/","/".$status."/") === false) {
            return false;
        } else {
            return true;
        }
    }

    /**
     * @return the $query
     */
    public function getQuery()
    {
        return $this->query;
    }

    /**
     * @return the $sort
     */
    public function getSort()
    {
        return $this->sort;
    }

    /**
     * @return the $dir
     */
    public function getDir()
    {
        return $this->dir;
    }

    /**
     * @return the $page
     */
    public function getPage()
    {
        return $this->page;
    }

    /**
     * @return the $name
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param $project the $project to set
     */
    public function setProject($project)
    {
        $this->project =  $project;
        return $this;
    }

    public function setInComment($inComment) {
        $this->inComment = $inComment;
        return $this;
    }

    public function setProjectId($project_id) {
        $this->project_id = $project_id;
        return $this;
    }
    public function setFund_id($fund_id) {
        $this->fund_id = $fund_id;
        return $this;
    }

    /**
     * @param $user the $user to set
     */
    public function setUser($user)
    {
        $this->user = (int) $user;
        return $this;
    }

    /**
     * @param $runner the $runner to set
     */
    public function setRunner($runner)
    {
        $this->runner = (int) $runner;
        return $this;
    }

    /**
     * @param $status the $status to set
     */
    public function setStatus($status)
    {
        $this->status = $status;
        return $this;
    }

    /**
     * @param $query the $query to set
     */
    public function setQuery($query)
    {
        $this->query = $query;
        return $this;
    }

    /**
     * @param $page the $page to set
     */
    public function setPage($page)
    {
        $this->page = (int)$page;
        return $this;
    }

    /**
     * @param $name the $name to set
     */
    public function setName($name)
    {
        $this->name = (string)$name;
        return $this;
    }

    /**
     * @param $sort the $sort to set
     */
    public function setSort($sort)
    {
        switch (strtoupper($sort)) {
            case 'WHO':
                $sort = 'creator_nickname';
                break;
            case 'SUMMARY':
                $sort = 'summary';
                break;
            case 'STATUS':
                $sort = 'status';
                break;
            case 'PROJECT':
                $sort = 'project_name';
                break;
            case 'COMMENTS':
                $sort = 'comments';
                break;
            // Allowing sort by ID
            // 21-MAY-2010 <Yani>
            case 'ID':
                $sort = 'id';
                break;
            case 'PRIORITY':
                $sort = 'priority';
                break;
            case 'WHEN':
            default:
                $sort = 'delta';
                break;
        }
        $this->sort = $sort;
        return $this;
    }

    /**
     * @param $dir the $dir to set
     */
    public function setDir($dir)
    {
        if (strtoupper($dir) == 'DESC') {
            $this->dir = 'DESC';
        } else {
            $this->dir = 'ASC';
        }
        return $this;
    }

    // getter for $subsort
    // 15-JAN-2011 <Reji>
    public function getSubSort() {
       return $this->subsort;
    }
    // setter for $subsort
    // 15-JAN-2011 <Reji>
    public function setSubSort($subsort) {
        $this->subsort = $subsort;
        return $this;
    }
    public function getProjectSelectbox($initialMessage = 'ALL',  $active = 1, $projectId = 'projectCombo', $projectName = 'project') {
        $box = '<select id="' . $projectId . '" name="' . $projectName . '" class="project-dropdown" data-placeholder="Select project" data-live-search="true">';
        $box .= '<option value="0"' . (($this->getProjectId() == "") ? ' selected="selected"' : '') . '> ' . $initialMessage . '</option>';

        $options = '';
        $found = false;

        $project = new Project();
        foreach ($project->getProjects((bool) $active, array('name', 'project_id'), false, true) as $project) {
            if ($this->getProjectId() && $this->getProjectId() == $project['project_id']) {
                $found = true;
            }

            // handle long project names
            $project_name = $project['name'];
            $options .= '<option value="' . $project['project_id'] . '"' .
                (($this->getProjectId() == $project['project_id']) ? ' selected="selected"' : '') . '>' . $project_name . '</option>';
        }

        if (! $found && $this->getProjectId()) {
            $options = '';

            foreach ( Project::getProjects(false, array('name', 'project_id')) as $project) {
                // handle long project names
                $project_name = $project['name'];
                $options .= '<option value="' . $project['project_id'] . '"' .
                    (($this->getProjectId() == $project['project_id']) ? ' selected="selected"' : '') . '>' . $project_name . '</option>';
            }
        }

        $box .= ($options . '</select>');
        return $box;
    }

    public function getFundSelectbox($fromReport=false, $display=true, $active=false) {
        $allDisplay = ($fromReport) ? "ALL" : "All Funds";
        $box = '<select id="fundCombo" name="fund" class="project-dropdown" ' . ($display ? '' : 'style="display: none;"') . '>';
        $box .= '    <option value="-1" ' . ($this->getFund_id() == -1 ? 'selected="selected"' : '') . '> ' . $allDisplay . '</option>';
        foreach (Fund::getFunds() as $fund) {
            $box .= '<option value="' . $fund['id'] . '" ' . ($this->getFund_id() == $fund['id'] ? 'selected="selected"' : '') . '>' . $fund['name'] . '</option>';
        }

        $box .= '<option value="0" ' .($this->getFund_id() == 0 ? 'selected="selected"' : '') . '>Not funded</option>';
        $box .= '</select>';

        return $box;
    }

    /*
     * Function getUserSelectbox Get a combobox containing all the users
     *
     * @param active If true will return users with at least a fee on the last
     *               45 days.
     * @return html containg the checkbox for active users and the combobox
     *
     * Notes: A reference to utils.js should be included for the auto refreshing
     *        behavior to work properly.
     *        <script type="text/javascript" src="js/utils.js"></script>
     *
     *        Also a global variable named filterName should be set to the
     *        filter name assigned on the php code. This variable needs to
     *        be initialized before including the script above.
     */
    public function getUserSelectbox($active = 1, $allMessage = false, $id = 'userCombo', $name = 'user') {
        $allDisplay = ($allMessage !== false) ? $allMessage : "All Users";
        $users = User::getUserList(getSessionUserId(), $active, 0, true);
        $box = '<select name="' . $name . '" id="' . $id . '" data-placeholder="Select user" data-live-search="true">';
        if ($allMessage !== false) {
            $box .= '<option value="0"' . (($this->getUser() == 0) ? ' selected="selected"' : '') . '> ' . $allMessage . '</option>';
        }
        foreach ($users as $user) {
            $box .= '<option value="' . $user->getId() . '"' . (($this->getUser() == $user->getId()) ? ' selected="selected"' : '') . '>' . $user->getNickname() . '</option>';
        }
        $box .= '</select>';

        return $box;
    }

    public function getRunnerSelectbox($active = 1, $allMessage = false, $id = 'runnerCombo', $name = 'runner') {
        $allDisplay = ($allMessage !== false) ? $allMessage : "All Users";
        $users = User::getUserList(getSessionUserId(), $active, 1, true);
        $box = '<select name="' . $name . '" id="' . $id . '" >';
        if ($allMessage !== false) {
            $box .= '<option value="0"' . (($this->getRunner() == 0) ? ' selected="selected"' : '') . '> ' . $allMessage . '</option>';
        }
        foreach ($users as $user) {
            $box .= '<option value="' . $user->getId() . '"' . (($this->getRunner() == $user->getId()) ? ' selected="selected"' : '') . '>' . $user->getNickname() . '</option>';
        }
        $box .= '</select>';

        return $box;
    }


    /**
     * Gets the manager user selection box with style
     */
    public function getManagerUserSelectboxS($style = "", $active = 1) {
        $users = User::getUserList(getSessionUserId(), $active, 0, true);
        $box = '<select style="'.$style.'" id="manager" name="manager">';
        $box .= '<option value="0" selected="selected">None</option>';
        foreach ($users as $user) {
            $box .= '<option value="' . $user->getId() . '"' . (($this->getUser() == $user->getId()) ? ' selected="selected"' : '') . '>' . $user->getNickname() . '</option>';
        }
        $box .= '</select>';

        return $box;
    }

/**
     * Gets the referrer user selection box with style
     */
    public function getReferrerUserSelectboxS($style = "", $active = 1) {
        $users = User::getUserList(getSessionUserId(), $active, 0, true);
        $box = '<select style="'.$style.'" id="referrer" name="referrer">';
        $box .= '<option value="0" selected="selected">None</option>';
        foreach ($users as $user) {
            $box .= '<option value="' . $user->getId() . '"' . (($this->getUser() == $user->getId()) ? ' selected="selected"' : '') . '>' . $user->getNickname() . '</option>';
        }
        $box .= '</select>';

        return $box;
    }

    public function getStatusSelectbox($fromReport=false)
    {
        $allDisplay = ($fromReport) ? "ALL" : "All Status";
        $status_array = WorkItem::getStates();
        $box = '<select id="status" name="status" data-placeholder="Pick statuses">';
        //$box .= '<option value="ALL"' . (($this->getStatus() == "ALL") ? ' selected="selected"' : '') . '> ' . $allDisplay . '</option>';
        foreach ($status_array as $status) {
            $selected = '';
            if ($this->getStatus() == $status) {
                $selected = ' selected="selected"';
            }
            $box .= '<option value="' . $status . '"' . $selected . '>' . $status . '</option>';
        }
        $box .= '</select>';
        return $box;
    }

    public function __construct(array $options = array(), $fromReport=false)
    {
        $this->setStatus( ($fromReport) ? "Done" : "Bidding");
        if (!empty($options) && (empty($options['reload']) || $options['reload'] == 'false')) {
            $this->setOptions($options);
        } elseif (!empty($options['name'])) {
            $this->setName($options['name'])
                 ->initFilter();
        }
    }

    public function initFilter()
    {
        if (getSessionUserId() > 0) {
            $this->initByDatabase();
        } else {
            $this->initByCookie();
        }
    }

    private function setOptions($options)
    {
        if (!empty($options['name'])) {
            $this->setName($options['name']);
        } elseif (!empty($options['id'])) {
            $options='';
        } elseif (isset($options[$this->getName()])) {
            $options = $options[$this->getName()];
        } else {
            $options = array();
        }
        $cleanOptions = array();
        $methods = get_class_methods($this);
        if (!empty($options)) {
            foreach ($options as $key => $value) {
                $method = 'set' . ucfirst($key);
                if (in_array($method, $methods)) {
                    $this->$method($value);
                    if ($key != 'name') {
                        $cleanOptions[$key] = $value;
                    }
                } elseif ($method == 'setProject_id') {
                    $this->setProjectId($value);
                    $cleanOptions['project_id'] = $value;
                }
            }
        }
        if (!isset($options['save']) || $options['save'] != 'false') {
            $this->save($cleanOptions);
        }
        return $this;
    }

    private function saveToDatabase($cleanOptions)
    {
        $user = new User();
        $user->findUserById(getSessionUserId());
        $filter = unserialize($user->getFilter());

        $filter[$this->getName()] = $cleanOptions;

        $user->setFilter(serialize($filter));
        $user->save();
    }

    private function saveToCookie($cleanOptions)
    {
        if (isset($_COOKIE['FilterCookie'])) {
            $filter = unserialize($_COOKIE['FilterCookie']);
        } else {
            $filter = array();
        }
        $filter[$this->getName()] = $cleanOptions;
        $setcookie = setcookie('FilterCookie', serialize($filter), time() + 3600, '/', SERVER_NAME, false, false);
        if ($setcookie === false) {
            throw new Exception('Cookie could not be set!');
        }
    }

    private function save($cleanOptions)
    {
        if (getSessionUserId() > 0) {
            $this->saveToDatabase($cleanOptions);
        } else {
            $this->saveToCookie($cleanOptions);
        }
    }

    private function initByDatabase()
    {
        $user = new User();
        $user->findUserById(getSessionUserId());
        if ($user->getFilter()) {
            $this->setOptions(unserialize($user->getFilter()));
        }
    }

    private function initByCookie()
    {
        if (isset($_COOKIE['FilterCookie'])) {
            $this->setOptions(unserialize($_COOKIE['FilterCookie']));
        }
    }

    /**
     * @return the $nickname
     */
    public function getNickname()
    {
        return $this->nickname;
    }

    /**
     * @param $nickname the $nickname to set
     */
    public function setNickname($nickname)
    {
        $this->nickname = (string)$nickname;
        return $this;
    }

    /**
     * @return the $filterMobile
     */
    public function getFilterMobile()
    {
        return $this->filterMobile;
    }

    /**
     * @param $filterMobile the $filterMobile to set
     */
    public function setfilterMobile($filterMobile)
    {
        $this->filterMobile = (boolean)$filterMobile;
        return $this;
    }

    /**
     * @return the $following
     */
    public function getFollowing()
    {
        return $this->following;
    }

    /**
     * @param $following the $following to set
     */
    public function setFollowing($following)
    {
        $this->following = (boolean)$following;
        return $this;
    }

    /**
     * @return the $participated
     */
    public function getParticipated()
    {
        return $this->participated;
    }

    /**
     * @param $participated the $participated to set
     */
    public function setParticipated($participated)
    {
        $this->participated = (boolean)$participated;
        return $this;
    }

}
