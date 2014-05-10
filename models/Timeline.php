<?php
class Timeline extends DataObject {
    public function getHistoricalData($project = false) {
        $sql = "
            SELECT
                w.id as job_id,
                w.creator_id as creator,
                w.mechanic_id as mechanic,
                w.runner_id as runner,
                w.code_reviewer_id as reviewer,
                CONCAT((SELECT city FROM " . USERS . " WHERE id = w.creator_id), \", \", (SELECT country FROM " . USERS . " WHERE id = w.creator_id)) as creator_address,
                (SELECT SUM(amount) FROM " . FEES . " WHERE worklist_id = w.id AND user_id = w.creator_id) as creator_fee,
                CONCAT((SELECT city FROM " . USERS . " WHERE id = w.mechanic_id), \", \", (SELECT country FROM " . USERS . " WHERE id = w.mechanic_id)) as mechanic_address,
                (SELECT SUM(amount) FROM " . FEES . " WHERE worklist_id = w.id AND user_id = w.mechanic_id) as mechanic_fee,
                CONCAT((SELECT city FROM " . USERS . " WHERE id = w.runner_id), \", \", (SELECT country FROM " . USERS . " WHERE id = w.runner_id)) as runner_address,
                (SELECT SUM(amount) FROM " . FEES . " WHERE worklist_id = w.id AND user_id = w.runner_id) as runner_fee,
                CONCAT((SELECT city FROM " . USERS . " WHERE id = w.code_reviewer_id), \", \", (SELECT country FROM " . USERS . " WHERE id = w.code_reviewer_id)) as reviewer_address,
                (SELECT SUM(amount) FROM " . FEES . " WHERE worklist_id = w.id AND user_id = w.code_reviewer_id) as reviewer_fee
            FROM " . WORKLIST . " w
            WHERE w.status = 'Done'
            AND DATE(created) > '2011-01-01'
            ORDER BY created ASC
        ";
        if ($project) {
            $sql .= " AND project_id = (SELECT id FROM " . PROJECTS . " WHERE name = '{$project}')";
        }
        $objectData = array();
        $result = $this->link->query($sql);
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $objectData[] = $row;
            }
            return $objectData;
        } else {
            return false;
        }
    }

    public function getDistinctLocations() {
        $sql = '
            SELECT DISTINCT CONCAT(city, ", ", country) as address
            FROM ' . USERS . '
            WHERE
                city != "" AND
                country != "";
        ';
        $objectData = array();
        $result = $this->link->query($sql);
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $objectData[] = $row;
            }
            return $objectData;
        } else {
            return false;
        }
    }

    public function insertLocationData($location, $latlong) {
        $sql = "INSERT INTO location_latlong (location, latlong) VALUES ('{$location}','{$latlong}')";
        $result = $this->link->query($sql);
        if ($result) {
            return true;
        } else {
            return false;
        }
    }

    public function getLocationData() {
        $sql = "SELECT location, latlong FROM location_latlong";
        $objectData = array();
        $result = $this->link->query($sql);
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $objectData[] = $row;
            }
            return $objectData;
        } else {
            return false;
        }
    }

    public function getListOfMonths() {
        $sql = "
            (SELECT DISTINCT
                YEAR(created) as yearValue,
                MONTH(created) as monthValue
            FROM worklist
            WHERE
                status = 'Done'
            ORDER BY
                yearValue ASC,
                monthValue ASC
            LIMIT 1)
            UNION
            (SELECT DISTINCT
                YEAR(created) as yearValue,
                MONTH(created) as monthValue
            FROM worklist
            WHERE
                status = 'Done'
            ORDER BY
            yearValue DESC,
            monthValue DESC
            LIMIT 1)
        ";
        $result = $this->link->query($sql);
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $dateArray[] = $row['yearValue'] . "-" . $row['monthValue'] . '-01';
            }
            $time1 = strtotime($dateArray[0]);
            $time2 = strtotime($dateArray[1]);
            $my = date('n-Y', $time2);
            $mesi = array(January, February, March, April, May, June, July, August, September, October, November, December);

            //$months = array(date('F', $time1));
            $months = array();
            $f = '';

            while ($time1 < $time2) {
                if (date('n-Y', $time1) != $f) {
                    $f = date('n-Y', $time1);
                    if (date('n-Y', $time1) != $my && ($time1 < $time2)) {
                        $str_mese = $mesi[(date('n', $time1) - 1)];
                        $months[] = $str_mese . " " . date('Y', $time1);
                    }
                }
                $time1 = strtotime((date('Y-n-d', $time1) . ' +15days'));
            }

            $str_mese = $mesi[(date('n', $time2) - 1)];
            $months[] = $str_mese . " " . date('Y', $time2);
            return $months;
        } else {
            return false;
        }
    }
}

?>
