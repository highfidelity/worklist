<?php
if (!defined('SL_OK')) {
    define ('SL_OK', 'ok');
}
if (!defined('SL_ERROR')) {
    define ('SL_ERROR', 'error');
}
class UserStats{

    protected $userId;
    protected $itemsPerPage = 20;

    public function __construct($userId){
        $this->setUserId($userId);
    }

    public function setUserId($userId){
        $this->userId = $userId;
    }

    public function setItemsPerPage($number){
        $this->itemsPerPage = $number;
    }

    public function getItemsPerPage(){
        return $this->itemsPerPage;
    }

    public function getTotalJobsCount(){
        $sql = "SELECT COUNT(*) FROM `" . WORKLIST . "` "
                ."WHERE (`mechanic_id` = {$this->userId} OR `creator_id` = {$this->userId})"
                ."AND `status` IN ('Working', 'Functional', 'SvnHold', 'Review', 'Completed', 'Done')";
        $res = mysql_query($sql);
        if($res && $row = mysql_fetch_row($res)){
            return $row[0];
        }
        return false;
    }

    // wrapper for getJobsCount to get number of jobs in an active status
    public function getActiveJobsCount(){
    $sql = "SELECT COUNT(*) FROM `" . WORKLIST . "` "
         . "WHERE (`mechanic_id` = {$this->userId} OR `runner_id` = {$this->userId}) "
         . "  AND `status` IN ('Working', 'Review', 'SvnHold', 'Functional')";
        $res = mysql_query($sql);
        if($res && $row = mysql_fetch_row($res)){
            return $row[0];
        }
        return false;
    }

    public function getFollowingJobs($page = 1) {
        $count = $this->getTotalJobsFollowingCount();
        $sql = "SELECT `" . WORKLIST . "`.`id`, `summary`, `status`, `mn`.`nickname` AS `mechanic_nickname`,
                    `cn`.`nickname` AS `creator_nickname`,`rn`.`nickname` AS `runner_nickname`,
                    DATE_FORMAT(`created`, '%m/%d/%Y') AS `created`
                FROM `" . WORKLIST . "`
                LEFT JOIN `" . USERS . "` AS `mn` ON `mechanic_id` = `mn`.`id`
                LEFT JOIN `" . USERS . "` AS `rn` ON `runner_id` = `rn`.`id`
                LEFT JOIN `" . USERS . "` AS `cn` ON `creator_id` = `cn`.`id`
                JOIN `" . TASK_FOLLOWERS . "` AS `tf` ON `tf`.`workitem_id` = `" . WORKLIST . "`.`id` AND `tf`.`user_id` = {$this->userId}
                ORDER BY `id` DESC
                LIMIT " . ($page-1)*$this->itemsPerPage . ", {$this->itemsPerPage}";
        $res = mysql_query($sql);
        $itemsArray = array();
        if ($res ) {
            while ($row = mysql_fetch_assoc($res)) {
                $itemsArray[] = $row;
            }
            return array(
                        'count' => $count,
                        'pages' => ceil($count/$this->itemsPerPage),
                        'page' => $page,
                        'joblist' => $itemsArray
                        );
        }
        return false;
    }

    public function getTotalJobsFollowingCount(){
            $sql = "SELECT COUNT(*) FROM `" . TASK_FOLLOWERS . "` "
                    ."WHERE (`user_id` = {$this->userId})";
            $res = mysql_query($sql);
            if ($res && $row = mysql_fetch_row($res)) {
                return $row[0];
            }
            return false;
    }


    public function getRunnerTotalJobsCount(){
        $sql = "SELECT COUNT(*) FROM `" . WORKLIST . "` "
                ."WHERE (`runner_id` = {$this->userId})"
                ."AND `status` IN ('Working', 'Functional', 'SvnHold', 'Review', 'Completed', 'Done')";
        $res = mysql_query($sql);
        if($res && $row = mysql_fetch_row($res)){
            return $row[0];
        }
        return false;
    }

    public function getTotalRunnerItems($page = 1){

        $count = $this->getRunnerTotalJobsCount();

        $sql = "SELECT `" . WORKLIST . "`.`id`, `summary`, `cn`.`nickname` AS `creator_nickname`,
            `rn`.`nickname` AS `runner_nickname`,
            DATE_FORMAT(`created`, '%m/%d/%Y') AS `created`
            FROM `" . WORKLIST . "`
            LEFT JOIN `" . USERS . "` AS `cn` ON `creator_id` = `cn`.`id`
            LEFT JOIN `" . USERS . "` AS `rn` ON `runner_id` = `rn`.`id`
            WHERE (`runner_id` = {$this->userId})
            AND `status` IN ('Working', 'Functional', 'SvnHold', 'Review', 'Completed', 'Done') ORDER BY `id` DESC
            LIMIT " . ($page-1)*$this->itemsPerPage . ", {$this->itemsPerPage}";

        $itemsArray = array();
        $res = mysql_query($sql);
        if($res ){
            while($row = mysql_fetch_assoc($res)){
                $itemsArray[] = $row;
            }
            return array(
                        'count' => $count,
                        'pages' => ceil($count/$this->itemsPerPage),
                        'page' => $page,
                        'joblist' => $itemsArray);
        }
        return false;
    }
    public function getRunnerActiveJobsCount(){
        $sql = "SELECT COUNT(*) FROM `" . WORKLIST . "` "
                . "WHERE (`runner_id` = {$this->userId}) AND `status` IN ('Working', 'Review', 'SvnHold', 'Functional')";
        $res = mysql_query($sql);
        if($res && $row = mysql_fetch_row($res)){
            return $row[0];
        }
        return false;
    }

    public function getActiveRunnerItems($page = 1){

        $count = $this->getRunnerActiveJobsCount();

        $sql = "SELECT `" . WORKLIST . "`.`id`, `summary`, `status`, `mn`.`nickname` AS `mechanic_nickname`, `cn`.`nickname` AS `creator_nickname`,
            `rn`.`nickname` AS `runner_nickname`,
            DATE_FORMAT(`created`, '%m/%d/%Y') AS `created`
            FROM `" . WORKLIST . "`
            LEFT JOIN `" . USERS . "` AS `mn` ON `mechanic_id` = `mn`.`id`
            LEFT JOIN `" . USERS . "` AS `rn` ON `runner_id` = `rn`.`id`
            LEFT JOIN `" . USERS . "` AS `cn` ON `creator_id` = `cn`.`id`
            WHERE (`runner_id` = {$this->userId}) AND `status` IN ('Working', 'Functional', 'SvnHold', 'Review') ORDER BY `id` DESC "
            . "LIMIT " . ($page-1)*$this->itemsPerPage . ", {$this->itemsPerPage}";
        $itemsArray = array();
        $res = mysql_query($sql);
        if ($res ) {
            while($row = mysql_fetch_assoc($res)) {
                $itemsArray[] = $row;
            }
            return array(
                'count' => $count,
                'pages' => ceil($count/$this->itemsPerPage),
                'page' => $page,
                'joblist' => $itemsArray
            );
        }
        return false;
    }

    public function getAvgJobRunTime() {
        $query = "SELECT AVG(TIME_TO_SEC(TIMEDIFF(doneDate, workingDate))) as avgJobRunTime FROM
                    (SELECT w.id, s.change_date AS doneDate,
                        ( SELECT MAX(`date`) AS workingDate FROM fees
                          WHERE worklist_id = w.id AND `desc` = 'Accepted Bid') as workingDate
                    FROM status_log s
                    LEFT JOIN worklist w ON s.worklist_id = w.id
                    WHERE s.status = 'Done' AND w.runner_id = " . $this->userId . ") AS x";
        if($result = mysql_query($query)) {
            $row = mysql_fetch_array($result);
            return ($row['avgJobRunTime'] > 0) ? relativeTime($row['avgJobRunTime'], false, true, false) : '';
        } else {
            return false;
        }
    }

    public function getDevelopersForRunner() {
        $query = "SELECT u.id, u.nickname, count(w.id) AS totalJobCount, sum(f.amount) AS totalEarnings FROM users u
                  LEFT OUTER JOIN fees f ON f.user_id = u.id
                  LEFT OUTER JOIN worklist w ON f.worklist_id = w.id
                  WHERE f.paid =1 AND f.withdrawn = 0 AND f.expense = 0
                  AND w.runner_id = {$this->userId} AND u.id <> w.runner_id
                  GROUP BY u.id
                  ORDER BY totalEarnings DESC";
        if($result = mysql_query($query)) {
            if(mysql_num_rows($result) > 0) {
                while($row = mysql_fetch_assoc($result)) {
                    $developers[$row['id']] = $row;
                }
            } else {
                return false;
            }
        } else {
            return false;
        }
        return $developers;
    }

    public function getProjectsForRunner() {
        $query = "SELECT p.project_id, p.name, count(distinct w.id) AS totalJobCount, sum(f.amount) AS totalEarnings FROM " .  PROJECTS . " p
                  LEFT OUTER JOIN " . WORKLIST . " w ON w.project_id = p.project_id
                  LEFT OUTER JOIN " . FEES . " f ON f.worklist_id = w.id
                  WHERE w.runner_id = {$this->userId}
                  AND w.status IN ('Working', 'Functional', 'SvnHold', 'Review', 'Completed', 'Done')
                  AND f.paid = 1 AND f.withdrawn = 0 AND f.expense = 0
                  GROUP BY p.project_id order by totalEarnings DESC";
        if($result = mysql_query($query)) {
            if(mysql_num_rows($result) > 0) {
                while($row = mysql_fetch_assoc($result)) {
                    $projects[$row['project_id']] = $row;
                }
            } else {
                return false;
            }
        } else {
            return false;
        }
        return $projects;
    }

    public function getRunJobsCount() {
        $sql = "
            SELECT
                COUNT(*)
            FROM " . WORKLIST . "
            WHERE runner_id = {$this->userId}";

        $res = mysql_query($sql);
        if ($res && $row = mysql_fetch_row($res)) {
            return $row[0];
        }

        return false;
    }

    public function getMechanicJobCount() {
        $sql = "
            SELECT
                COUNT(*)
            FROM " . WORKLIST . "
            WHERE mechanic_id = {$this->userId}";

        $res = mysql_query($sql);
        if ($res && $row = mysql_fetch_row($res)) {
            return $row[0];
        }

        return false;
    }

    public function getTimeToPayAvg() {
        $sql = "
            SELECT ROUND(AVG(diff)) AS average FROM (
                SELECT TIME_TO_SEC(TIMEDIFF(dateDone, dateCompleted)) AS diff FROM (
                    SELECT
                        (SELECT MAX(change_date) FROM " . STATUS_LOG . " WHERE worklist_id = w.id AND status = 'Completed') AS dateCompleted,
                        (SELECT MAX(change_date) FROM " . STATUS_LOG . " WHERE worklist_id = w.id AND status = 'Done') AS dateDone
                    FROM " . STATUS_LOG . " sl
                    LEFT JOIN " . WORKLIST . " w ON w.id = sl.worklist_id
                    WHERE
                        w.runner_id = {$this->userId}
                        AND w.status = 'Done'
                    GROUP BY worklist_id) AS dates
                WHERE dateCompleted IS NOT null) AS diffs";

        $res = mysql_query($sql);
        if ($res && $row = mysql_fetch_assoc($res)) {
            return $row['average'];
        }
        return false;
    }

    public function getTimeBidAcceptedAvg() {
        $sql = "
            SELECT ROUND(AVG(diff)) AS average FROM (
                SELECT TIME_TO_SEC(TIMEDIFF(dateWorking, firstBid)) AS diff FROM (
                    SELECT
                        (SELECT MAX(`date`) FROM " . FEES . " WHERE worklist_id = w.id AND `desc` = 'Accepted Bid') AS dateWorking,
                        (SELECT MIN(bid_created) FROM " . BIDS . " WHERE worklist_id = w.id) AS firstBid
                    FROM " . STATUS_LOG . " sl
                    LEFT JOIN " . WORKLIST . " w ON w.id = sl.worklist_id
                    WHERE
                        w.runner_id = {$this->userId}
                    GROUP BY worklist_id) AS dates
                WHERE dateWorking IS NOT null) AS diffs";

        $res = mysql_query($sql);
        if ($res && $row = mysql_fetch_assoc($res)) {
            return $row['average'];
        }
        return false;
    }

    public function getDoneOnTimePercentage() {
        $sql = "
            SELECT
                b.worklist_id,
                b.bid_done,
                ( CASE sl.status WHEN  'Functional' THEN MAX(sl.change_date) END) AS endTime
            FROM bids b, status_log sl
            WHERE
                sl.worklist_id = b.worklist_id
                AND sl.user_id = {$this->userId}
                AND b.bidder_id = sl.user_id
            GROUP BY sl.worklist_id
            HAVING endTime <= b.bid_done
            ";

        $res = mysql_query($sql);
        $functional_onTime_count = mysql_num_rows($res);
        $sql = "
            SELECT
                ( CASE sl.status WHEN  'Functional' THEN MAX(sl.change_date) END) AS endTime
            FROM bids b, status_log sl
            WHERE
                sl.worklist_id = b.worklist_id
                AND sl.user_id = {$this->userId}
                AND b.bidder_id = sl.user_id
            GROUP BY sl.worklist_id
            HAVING endTime IS NOT NULL
            ";

        $res = mysql_query($sql);
        $functional_all_count = mysql_num_rows($res);

        $onTimePercentage = $functional_all_count <> 0 ? round(($functional_onTime_count / $functional_all_count) * 100, 2) : 0;
        return $onTimePercentage;
    }

    public function getTimeCompletedAvg() {
        $sql = "
            SELECT ROUND(AVG(bidTime - realTime)) AS average FROM (
                SELECT
                    TIME_TO_SEC(TIMEDIFF(dateCompleted, dateAccepted)) AS realTime,
                    TIME_TO_SEC(TIMEDIFF(dateCompleted, dateToBeDone)) AS bidTime
                FROM (
                    SELECT
                        (SELECT MAX(change_date) FROM status_log WHERE worklist_id = w.id AND status = 'Completed') AS dateCompleted,
                        (SELECT MAX(`date`) FROM " . FEES . " WHERE worklist_id = w.id AND `desc` = 'Accepted Bid') AS dateAccepted,
                        (SELECT bid_done FROM " . BIDS . " WHERE worklist_id = w.id AND accepted = 1) AS dateToBeDone
                    FROM " . STATUS_LOG . " sl
                    LEFT JOIN " . WORKLIST . " w ON w.id = sl.worklist_id
                    WHERE
                        w.mechanic_id = {$this->userId}
                        AND w.status IN ('Completed', 'Done')
                        GROUP BY worklist_id) AS dates
                WHERE dateCompleted IS NOT null) AS diffs";

        $res = mysql_query($sql);
        if ($res && $row = mysql_fetch_assoc($res)) {
            return $row['average'];
        }
        return false;
    }

    public function getTotalEarnings(){
        $sql = "SELECT SUM(amount) FROM `fees` "
                . "WHERE `paid` = 1 AND `withdrawn`=0 AND `expense`=0 "
                . "AND `user_id` = {$this->userId}";
        $res = mysql_query($sql);
        if($res && $row = mysql_fetch_row($res)){
            return (int) $row[0];
        }
        return false;
    }

    // gets sum of paid earnings between specified period
    // start date and end date are included
    public function getEarningsForPeriod($startDate, $endDate){

        $startDate = date("Y-m-d", $startDate);
        $endDate = date("Y-m-d", $endDate);

        $sql = "SELECT SUM(amount) FROM `" . FEES . "` "
                . "WHERE `paid` = 1 AND `withdrawn`=0 AND `expense`=0 "
                . "AND `paid_date` >= '$startDate' AND `paid_date` <= '$endDate'"
                . "AND `user_id` = {$this->userId}";

        $res = mysql_query($sql);
        if($res && $row = mysql_fetch_row($res)){
            return (int) $row[0];
        }
        return false;
    }

    // gets earning for a number of days back
    // getLatestEarnings(30) will give earnings (paid) for last 30 days
    public function getLatestEarnings($daysCount){
        return $this->getEarningsForPeriod(strtotime("- $daysCount days"), time());
    }

    // gets list of fees and jobs associated with them for the preiod
    // start date and end date are included
    public function getEarningsJobsForPeriod($startDate, $endDate, $page = 1){

        $startDate = date("Y-m-d", $startDate);
        $endDate = date("Y-m-d", $endDate);

        $count = 0;
        $sql = "SELECT COUNT(*) FROM `" . FEES . "` "
                . "WHERE `paid` = 1 AND `withdrawn`=0 AND `expense`=0
                        AND `paid_date` >= '$startDate' AND `paid_date` <= '$endDate'
                        AND `user_id` = {$this->userId}";

        $res = mysql_query($sql);
        if($res && $row = mysql_fetch_row($res)){
            $count = $row[0];
        }

        $sql = "SELECT DISTINCT `worklist_id`, `amount`, `summary`, `paid_date`,
                    DATE_FORMAT(`paid_date`, '%m/%d/%Y') AS `paid_formatted`,
                    `cn`.`nickname` AS `creator_nickname`, `rn`.`nickname` AS `runner_nickname`
                        FROM `" . FEES . "`
                        LEFT JOIN `" . WORKLIST . "` ON `worklist_id` = `worklist`.`id`
                        LEFT JOIN `" . USERS . "` AS `cn` ON `creator_id` = `cn`.`id`
                        LEFT JOIN `" . USERS . "` AS `rn` ON `runner_id` = `rn`.`id`
                        WHERE `" . FEES . "`.`paid` = 1 AND `withdrawn`=0 AND `expense`=0
                        AND `paid_date` >= '$startDate' AND `paid_date` <= '$endDate'
                        AND `user_id` = {$this->userId} ORDER BY `paid_date` DESC
                        LIMIT " . ($page-1)*$this->itemsPerPage . ", {$this->itemsPerPage}";

        $itemsArray = array();
        $res = mysql_query($sql);
        if($res ){
            while($row = mysql_fetch_assoc($res)){
                $itemsArray[] = $row;
            }
            return array(
                        'count' => $count,
                        'pages' => ceil($count/$this->itemsPerPage),
                        'page' => $page,
                        'joblist' => $itemsArray);
        }
        return false;
    }

    // gets list of fees and jobs associated with them for a number of days back
    // works similar to getLatestEarnings(30) - will give earnings with jobs (paid) for last 30 days
    public function getLatestEarningsJobs($daysCount, $page = 1){
        return $this->getEarningsJobsForPeriod(strtotime("- $daysCount days"), time(), $page);
    }

    // get number of total love received by user using sendlove api
    public function getLoveCount(){
        $data = $this->sendloveApiRequest('getcount');
        if($data){
            return (int) $data['count'];
        }
        return false;
    }

    public function getUniqueLoveCount(){
       $data = $this->sendloveApiRequest('getuniquecount');
	    if($data){
            return (int) $data['count'];
        }
        return false;
    }


    // get total love received by user using sendlove api
    public function getTotalLove($page = 1){
        $data = $this->sendloveApiRequest('getlove', $page);
        if($data){
            return $data;
        }
        return false;
    }

    // sends a request to sendlove api and returns data in case of success
    // false - if something went wrong
    public function sendloveApiRequest($action, $page = 1){

        $user = new User();
        $user->findUserById($this->userId);

        $params = array (
            'action' => $action,
            'api_key' => SENDLOVE_API_KEY,
            'page' => $page,
            'perpage' => $this->itemsPerPage,
            'username' => $user->getUsername());
        $referer = (empty($_SERVER['HTTPS'])?'http://':'https://').$_SERVER['SERVER_NAME'].$_SERVER['PHP_SELF'];
        $sendlove_rsp = postRequest (SENDLOVE_API_URL, $params, array(CURLOPT_REFERER => $referer));
        $rsp = json_decode ($sendlove_rsp, true);

        if($rsp['status'] == SL_OK){
            return $rsp['data'];
        }else{
            return false;
        }
    }

    public function getJobsCount(){

        $count = 0;
        $sql = "SELECT COUNT(*) FROM `" . WORKLIST . "` "
                . "WHERE `mechanic_id` = {$this->userId} OR `creator_id` = {$this->userId} "
                ."AND `status` IN ('Working', 'Functional', 'Review', 'Completed', 'Done')
                AND `creator_id` != `mechanic_id` ";

        $res = mysql_query($sql);
        if($res && $row = mysql_fetch_row($res)){
            $count = $row[0];
        }
        return $count;
    }
    public function getJobsCountForASpecificProject($project){

    	$count = 0;
    	$sql = "SELECT COUNT(*) FROM `" . WORKLIST . "` "
    	. "WHERE `mechanic_id` = {$this->userId} OR `creator_id` = {$this->userId} "
    	."AND `status` IN ('Working', 'Functional', 'Review', 'Completed', 'Done')
    	AND `creator_id` != `mechanic_id` AND project_id = " . $project;

    	$res = mysql_query($sql);
    	if ($res && $row = mysql_fetch_row($res)){
    		$count = $row[0];
    	}
    	return $count;
    }

    public function getCompletedJobsWithStats($page = 1) {
        $sql = "
            SELECT w.id, w.summary, f.cost, DATEDIFF(d2.change_date, b.date) days
            FROM " . WORKLIST . " w
            # Code is commented out as there is not a reliable 'Working' status date due to issues
            # in the acceptdBid method. This method has been updated.
            # LEFT JOIN " . STATUS_LOG . " d1 ON d1.worklist_id = w.id AND d1.status = 'Working'
            LEFT JOIN " . STATUS_LOG . " d2 ON d2.worklist_id = w.id AND d2.status = 'Completed'
            # And temporarily using the creation date of the Accepted Bid
            LEFT JOIN " . FEES . " b ON b.worklist_id = w.id AND b.desc = 'Accepted Bid'
            LEFT JOIN (
                SELECT SUM(amount) cost, worklist_id
                FROM " . FEES . "
                GROUP BY worklist_id
            ) f ON f.worklist_id = w.id
            WHERE
                (`mechanic_id` = {$this->userId} OR `creator_id` = {$this->userId}) AND
                w.`status` IN ('Completed', 'Done')
            GROUP BY w.`id`
            ORDER BY w.`id` DESC
            LIMIT 5";

        $res = mysql_query($sql);
        if ($res) {
            $itemsArray = array();
            while ($row = mysql_fetch_assoc($res)) {
                $itemsArray[] = $row;
            }

            return array(
                'joblist' => $itemsArray,
                'pages' => 1,
                'page' => $page
            );
        }

        return false;
    }

    public function getUserItems($status, $page = 1){

        $count = $this->getJobsCount($status);

        $sql = "SELECT `" . WORKLIST . "`.`id`, `summary`, `cn`.`nickname` AS `creator_nickname`,
            `rn`.`nickname` AS `runner_nickname`,
            DATE_FORMAT(`created`, '%m/%d/%Y') AS `created`
            FROM `" . WORKLIST . "`
            LEFT JOIN `" . USERS . "` AS `cn` ON `creator_id` = `cn`.`id`
            LEFT JOIN `" . USERS . "` AS `rn` ON `runner_id` = `rn`.`id`
            WHERE (`mechanic_id` = {$this->userId} OR `creator_id` = {$this->userId})
            AND `status` IN ('Working', 'Functional', 'SvnHold', 'Review', 'Completed', 'Done') ORDER BY `id` DESC
            LIMIT " . ($page-1)*$this->itemsPerPage . ", {$this->itemsPerPage}";

        $itemsArray = array();
        $res = mysql_query($sql);
        if($res ){
            while($row = mysql_fetch_assoc($res)){
                $itemsArray[] = $row;
            }
            return array(
                        'count' => $count,
                        'pages' => ceil($count/$this->itemsPerPage),
                        'page' => $page,
                        'joblist' => $itemsArray);
        }
        return false;
    }
    public function getUserItemsForASpecificProject($status, $project, $page = 1){

    	$count = $this->getJobsCountForASpecificProject($project);

    	$sql = "SELECT `" . WORKLIST . "`.`id`, `summary`, `cn`.`nickname` AS `creator_nickname`,
    	`rn`.`nickname` AS `runner_nickname`,
    	DATE_FORMAT(`created`, '%m/%d/%Y') AS `created`
    	FROM `" . WORKLIST . "`
    	LEFT JOIN `" . USERS . "` AS `cn` ON `creator_id` = `cn`.`id`
    	LEFT JOIN `" . USERS . "` AS `rn` ON `runner_id` = `rn`.`id`
    	WHERE (`mechanic_id` = {$this->userId} OR `creator_id` = {$this->userId})
    	AND `status` IN ('Working', 'Functional', 'Review', 'Completed', 'Done')
    	AND project_id = ". $project . " ORDER BY `id` DESC
    	LIMIT " . ($page-1)*$this->itemsPerPage . ", {$this->itemsPerPage}";

    	$itemsArray = array();
    	$res = mysql_query($sql);
    	if ($res){
    	    while($row = mysql_fetch_assoc($res)){
    	        $itemsArray[] = $row;
    	    }
    	    return array(
    	        'count' => $count,
    	        'pages' => ceil($count/$this->itemsPerPage),
    	        'page' => $page,
    	        'joblist' => $itemsArray);
    	}
    	return false;
    }
    public function getActiveUserItems($status, $page = 1){

        $count = $this->getActiveJobsCount($status);

        $sql = "SELECT `" . WORKLIST . "`.`id`, `summary`, `status`, `mn`.`nickname` AS `mechanic_nickname`, `cn`.`nickname` AS `creator_nickname`,
            `rn`.`nickname` AS `runner_nickname`,
            `" . WORKLIST . "`.`sandbox` AS `sandbox`,
            DATE_FORMAT(`created`, '%m/%d/%Y') AS `created`
            FROM `" . WORKLIST . "`
            LEFT JOIN `" . USERS . "` AS `mn` ON `mechanic_id` = `mn`.`id`
            LEFT JOIN `" . USERS . "` AS `rn` ON `runner_id` = `rn`.`id`
            LEFT JOIN `" . USERS . "` AS `cn` ON `creator_id` = `cn`.`id`
            WHERE (`mechanic_id` = {$this->userId} OR `runner_id` = {$this->userId})
              AND `status` IN ('Working', 'Functional', 'SvnHold', 'Review')
            ORDER BY `id` DESC "
            . "LIMIT " . ($page-1)*$this->itemsPerPage . ", {$this->itemsPerPage}";

        $itemsArray = array();
        $res = mysql_query($sql);
        if ($res) {
            while($row = mysql_fetch_assoc($res)) {
                $itemsArray[] = $row;
            }
            return array(
                'count' => $count,
                'pages' => ceil($count/$this->itemsPerPage),
                'page' => $page,
                'joblist' => $itemsArray
            );
        }
        return false;
    }

    public function getBonusPaymentsTotal() {

    $sql = "
        SELECT
            IFNULL(`rewarder`.`sum`,0) AS `bonus_tot`
        FROM `".USERS."`
        LEFT JOIN (SELECT `user_id`, SUM(amount) AS `sum` FROM `".FEES."` WHERE (`withdrawn`=0 AND `paid` = 1 AND `user_id` = {$this->userId}) AND (`rewarder`=1 OR `bonus`=1) GROUP BY `user_id`) AS `rewarder` ON `".USERS."`.`id` = `rewarder`.`user_id`
        WHERE `id` = {$this->userId}";
        $res = mysql_query($sql);
                if($res && $row = mysql_fetch_row($res)){
                    return (int) $row[0];
                }
                return false;
    }

    public static function getNewUserStats() {
        $sql = "
            SELECT (
                SELECT COUNT(DISTINCT(users.id))
                FROM " . USERS . "
                INNER JOIN " . FEES . " ON users.id = fees.user_id AND users.added > DATE_SUB(CURDATE(), INTERVAL 1 MONTH)
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
                WHERE
                    added > DATE_SUB(CURDATE(), INTERVAL 1 MONTH)
                    AND last_seen > added
            ) AS newUsersLoggedIn";

        $res = mysql_query($sql);
        if ($res && $row = mysql_fetch_assoc($res)) {
            return $row;
        }
        return false;
    }
}
