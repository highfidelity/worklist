<?php
/**
 * Worklist
 * Copyright (c) 2011 LoveMachine, LLc.
 * All rights reserved.
 */
if (!defined('REVIEWS'))   define('REVIEWS', 'reviews');

class Review extends DataObject {
    public $reviewer_id;
    public $reviewee_id;
    public $review;
    public $journal_notified;

    public $table_name;

    /**
     * Constructor
     */
    public function __construct() {
        parent::__construct();

        $this->table_name = REVIEWS;
    }

    /**
     * Destructor
     */
    public function __destructor() {
        parent::__destruct();
    }
    /**
     * Load a review by id
     */
    public function loadById($reviewer_id,$reviewee_id) {
        $objectData = $this->dbFetchArray(" `reviewee_id`={$reviewee_id} AND `reviewer_id`={$reviewer_id}  ");
        return $this->loadObject($objectData);
    }

    /**
     * Get an index for review
     */
    public function getIndex($reviewee_id) {
        $objectData = $this->dbFetchArray(" `reviewee_id`={$reviewee_id} ORDER BY `reviewer_id` ");

        if (!$objectData && is_array($objectData)) {
            return null;
        }

        return $objectData;
    }
    /**
     * get list of offers for given user id
     */
    public function getReviews($reviewee_id,$reviewer_id,$filter=''){
        $sql = "SELECT r.review,IF(r.reviewer_id = ".$reviewer_id .
            ",'y','n') AS me, COUNT(f.id) as nbFees, CASE WHEN COUNT(f.id) < 10 THEN '1+' WHEN COUNT(f.id) < 100 THEN '10+' WHEN COUNT(f.id) < 1000 THEN '100+' ELSE '1000+' END  AS feeRange FROM "
            . REVIEWS . " AS r
            INNER JOIN " . FEES . " AS f ON r.reviewer_id = f.user_id AND f.paid = 1
            WHERE reviewee_id = $reviewee_id $filter
            GROUP BY r.review,r.reviewer_id
            ORDER BY nbFees DESC ";

        $objectData = array();
        if($result = $this->link->query($sql)){
            $countSup10 = 0;
            $display_10_limit = 10;
            while ($row = $result->fetch_assoc()){
                if ($row['nbFees'] >= 10) {
                    $countSup10++;
                } else {
                    if ($countSup10 > $display_10_limit) {
                        break;
                    }
                }
                $row['nbFees'] = 0;
                $objectData[] = $row;
            }
            $result->close();
        }else{
            error_log("Review:getReviews mysql error: " . $sql . " * " . $this->link->error);
            $objectData = null;
        }
        return $objectData;
    }
    /**
     * Get reviews that have been updated on DONE and have not been notified on journal yet
     */
    public static function getReviewsWithPendingJournalNotifications() {
        $sql = 'SELECT * FROM ' . REVIEWS . ' WHERE journal_notified != 1';
        $results = array();
        if($result = mysql_query($sql)){
            while ($row = mysql_fetch_assoc($result)){
                $results[] = $row;
            }
        } else {
            return false;
        }
        return $results;
    }

    /**
     * Set the object field value to $value and also save it in the DB
     */
    public function setNotifiedAndSave($value) {
        $value = (int) $value;
        $this->journal_notified = $value;
        $sql = 'UPDATE ' . REVIEWS . ' SET journal_notified = ' . $value;
        $this->link->query($sql);
    }

    public function insertNew($values) {
        return $this->dbInsert($values);
    }

    public function updateReview($sql) {
        return $this->dbUpdate($sql);
    }
}
?>
