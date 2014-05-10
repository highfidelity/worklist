<?php
/*
 * Copyright (c) 2011, LoveMachine Inc.
 * All Rights Reserved.
 * http://www.lovemachineinc.com
 *
 * Bid class
 */
class Bid {
    private $_row = null;

    /*
     * Constructor
     */
    public function __construct($id = null) {
        if ($id) {
            $this->findBidById($id);
        }
    }

    /**
     * This method calls loadBid.
     *
     * @param (integer) $id Id
     * @return (mixed) Either sets the Bid or false.
     */
    public function findBidById($id) {
        $id = (int)$id;
        if (!$this->_row) {
            $this->_row = $this->loadBid($id);
        }
    }

    private function loadBid($item) {
        $sql = "SELECT `id`, `bidder_id`, `worklist_id`, `email`, `bid_expires`, UNIX_TIMESTAMP(`bid_expires`) AS unix_expires, `bid_done_in`,
                `bid_amount`, `notes`, UNIX_TIMESTAMP(`bid_done`) AS `done_by`, `accepted`, UNIX_TIMESTAMP(NOW()) as now, `bid_created`,
                TIMESTAMPDIFF(SECOND, NOW(), `bid_done`) AS `future_delta`
                FROM `" . BIDS . "` WHERE `id` = '$item'";

        // and get the result
        $result = mysql_query($sql);

        if ($result && (mysql_num_rows($result) == 1)) {
            $row = mysql_fetch_assoc($result);
            $row['done_by'] = getUserTime($row['done_by']);
            return $row;
        }
        return false;
    }

    public function setAnyAccepted($val) {
        if ($this->_row) {
            $this->_row['any_accepted'] = $val;
        }
    }

    public function toArray() {
        return $this->_row;
    }

    public function __get($name) {
        if (($this->_row) && (array_key_exists($name, $this->_row))) {
            return $this->_row[$name];
        } else {
            return null;
        }
    }

    public function getBid_amount() {
        return $this->_row['bid_amount'];
    }

}