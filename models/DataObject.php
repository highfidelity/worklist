<?php
/**
 * Copyright (c) 2014, High Fidelity Inc.
 * All Rights Reserved.
 *
 * http://highfidelity.io
 */

 /* will be moved in config.php as soon as the commit config.php will be available
 */
if (!defined('SKILLS'))         define('SKILLS', 'skills');
if (!defined('REVIEWS'))        define('REVIEWS', 'reviews');
if (!defined('TRANSACTIONS'))   define('TRANSACTIONS', 'transactions');
if (!defined('USERS_SKILLS'))   define('USERS_SKILLS', 'rel_users_skills');
if (!defined('MISSIONS_SKILLS'))   define('MISSIONS_SKILLS', 'rel_missions_skills');
if (!defined('MISSIONS_TRANSACTIONS'))   define('MISSIONS_TRANSACTIONS', 'rel_missions_transactions');



class DataObject {
    public $link;
    private $lastID;

    public function getLastID() {
        return $this->lastID;
    }
    /**
     * Initialize DB connection
     */
    public function __construct($join= array()) {
        // Establish the link with our DB
        $this->link = new mysqli(DB_SERVER, DB_USER, DB_PASSWORD, DB_NAME);

        // Error checking
        if (mysqli_connect_errno()) {
            error_log("DataObject mysql error: " . mysqli_connect_error());
            exit;
        }
        $this->init($join);
    }
    /**
     * Destructor
     */
    public function __destruct() {
        // Do NOT close the database link; other objects may be using it.
        // 2011 alexi
    }
    /**
     * Destructor
     */
    public function __destructor() {
        $this->link->close();
    }

     public function init($join) {

        if (count($join) > 0) {
            if (isset($this->notCol)) {
                foreach ($this->notCol['join'] as $joinID => $joinInfo) {
                    if ( isset($join[$joinID]) ) {
                        $this->notCol['join'][$joinID]['useIt'] = $join[$joinID];
                    }
                }
            }
        }
    }

    /** Get the join columns.
     */
    public function getJoinColumns() {

        $join_cols = array();
        if (isset($this->notCol) ) {
            foreach ($this->notCol['join'] as $joinID => $joinInfo) {
                if ( $joinInfo['useIt'] ) {
                    $join_cols[] = " ".$joinInfo['joinFields'] ." ";
                }
            }
        }
        return $join_cols;
    }
    /** Get the join tables.
     */
    public function getJoinTables() {

        $join_tables = array();
        $sql="";
        if (isset($this->notCol) ) {
            foreach ( $this->notCol['join'] as $joinID => $joinInfo) {
                if ( $joinInfo['useIt'] ) {
                    $join_tables[] =  $joinInfo['joinSQL'];
                    if (isset( $joinInfo['joinSQLWhere'])) {
                        $join_tables[] .= " " . $joinInfo['joinSQLWhere'] . " ";
                    }
                }
            }
        }
        foreach ($join_tables as $join_table) {
            $sql .= " " . $join_table . " ";
        }
        return $sql;
    }
    /** Get all the columns for this object, removing the last field
     * as that is our table identifier.
     */
    public function getColumns( $bTablePrefix=false ) {
        // It assumes that the table name is plural
        $class_name = substr_replace(strtoupper($this->table_name), "", -1);
        if (substr($class_name,0,4) == "REL_") {
            $class_name = substr($class_name,4);
        }
        $columns = get_class_vars($class_name);
        $only_cols = array();
        foreach ($columns as $key => $value) {
            if (($key != "table_name") && ($key != "link") && ($key != "lastID") && ($key != "notCol")) {
                if ($bTablePrefix === true) {
                    $only_cols[] = $this->table_name . ".`" . $key . "`";
                } else {
                    $only_cols[] =  $key ;
                }
            }
        }
        return $only_cols;
    }

    /**
     * Get columns and values
     */
    public function getObjectData() {
        // It assumes that the table name is plural
        $class_name = substr_replace(strtoupper($this->table_name), "", -1);
        $columns = get_class_vars($class_name);
        $values = array();
        foreach ($columns as $column => $value) {
            if (($column != "table_name") && ($column != "link") && ($column != "notCol")) {
                $values[$column] = $this->$column;
            }
        }
        return $values;
    }

    /**
     * Insert the given values into the DB for this model
     */
    public function dbInsert($values) {
	// If we don't have any valid columns to insert, fail
        if (empty($values) || count($values) == 0) { return false; }
        $sql = "INSERT INTO `" . $this->table_name . "` (";

	// Get columns for this table (don't insert things that can't be)
        $columns = $this->getColumns();

        $_columns = '';
        $_values = '';
        // Add given values to the query
        foreach ($values as $key => $value) {
            // Add each valid column name to the sql statement
            if (array_search($key,$columns) !== false) {
                $_columns .= "`". $key ."`,";
                if ($value == "NOW()"  || !is_string($value)) {
                    $_values .= $value .",";
                    continue;
                }
                $_values .= "'" . $this->mysqli_real_escape_string($value)  . "',";
            }
        }
        // If we don't have any valid columns to insert, fail
        if (empty($_columns)) {
            var_dump($_columns);
            return false;
        }

        // Add union
        $_columns = substr_replace($_columns, "", -1);
        $_values = substr_replace($_values, "", -1);
        $sql .= $_columns.") VALUES (".$_values.")";

        // Execute our query and send the result back

        $result = $this->link->prepare($sql) or error_log("prepare failed $sql");
        if ($result && $result->execute()) {
            $this->lastID =  mysqli_insert_id($this->link);
            $result->close();
            return true;
        } else {
            var_dump($sql . " * " . $this->link->error);
            if ($result) {
                $result->close();
            }
            return false;
        }
    }
    /** Get the join oder.
     */
    public function getJoinOrder() {

        $join_cols = "";
        if (isset($this->notCol) ) {
            foreach ($this->notCol['join'] as $joinID => $joinInfo) {
                if ( $joinInfo['useIt'] && isset ($joinInfo['joinOrder'])) {
                    $join_cols = " ORDER BY " . $joinInfo['joinOrder'] . " ";
                }
            }
        }
        return $join_cols;
    }

    /**
     * Get associative array for the specified columns
     * where @condition is true
     */
    public function dbFetchArray($condition, $bError = false, $limit = null, $myColumns = null) {
        $sql = "SELECT ";
        $data = array();

        if (is_null($myColumns)) {
            $columns = $this->getColumns(true);
        } else {
            $columns = $myColumns;
        }

        // Add each column name to the sql statement
        foreach ($columns as $column) {
            $sql .= " " . $column . " ,";
        }
        $columns = $this->getJoinColumns();
        // Add each column name to the sql statement
        foreach ($columns as $column) {
            $sql .= " " . $column . " ,";
        }
        $sql = substr_replace($sql, "", -1);
        $joinTables = $this->getJoinTables();

        $joinOrder = $this->getJoinOrder();

        $sql .= " FROM `" . $this->table_name . "` " . $joinTables . " WHERE " . $condition . $joinOrder;
        if ($limit !== null) {
            $sql .= $limit;
        }

        if ($result = $this->link->query($sql)) {

            while ($row = $result->fetch_assoc()) {
                $data[] = $row;
            }
            // Close our connection
            $result->close();

            // Return our data array
            if ($bError) {
                return array('data' => $data);
            } else {
                return $data;
            }
        } else {
            //die($sql);
            // Dump the error
            if (!$bError) {
                $this->handleError($this->link->error, $sql);
            }
        }
        // If we couldn't return our full array, return null
        if ($bError) {
            $this->handleError($this->link->error, $sql);
        } else {
            return null;
        }
    }

    /**
     * Get the number of rows on this table
     */
    public function count($condition = '') {
        $sql = "SELECT COUNT(*) FROM `" . $this->table_name . "`";

        if ($condition != '') {
            $sql .= " WHERE " . $condition;
        }

        if ($result = $this->link->query($sql)) {
            $row = $result->fetch_row();
            return $row[0];
        }
        return null;
    }
    /**
     * Remove a row when @condition is true
     */
    public function removeRow($condition) {
        $sql = "DELETE FROM `" . $this->table_name . "` WHERE {$condition}";

        // If removed successfully return true
        if ($result = $this->link->query($sql)) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Load the object by automated data from @objectData
     */
    public function loadObject($objectData) {
        if (!$objectData && is_array($objectData)) {
            return false;
        }

        foreach ($objectData[0] as $key => $val) {
            $this->$key = $val;
        }
        return true;
    }

    /**
     * Saves all the object contents to the db
     * limited to @limiter row
     */
    public function save($limiter,$limiter2=null) {
        $sql = "UPDATE `" . $this->table_name . "` SET ";

        $columns = $this->getObjectData();

        // Add each column name to the sql statement
        array_shift($columns);
        foreach ($columns as $column => $value) {
            // If the value for the field is not set at all, just skip it
            if (!$value && !is_numeric($value)) {
                continue;
            }
            if ($value == "NOW()" || !is_string($value)) {
                $sql .= "`". $column . "` = {$value},";
                continue;
            }
            $sql .= "`". $column . "` = '". $this->mysqli_real_escape_string($value) . "',";
        }
        $sql = substr_replace($sql, "", -1);

        $limiter_value = $this->$limiter;

        // Limit the query to the current user
        $sql .= " WHERE `{$limiter}` = '{$limiter_value}'";

        if ($limiter2 !== null) {
            $limiter_value = $this->$limiter2;

            // Limit the query to the current user
            $sql .= " AND `{$limiter2}` = '{$limiter_value}' ";
        }
        // Execute our query and send the result back
        $result = $this->link->prepare($sql);
        if ($result && $result->execute()) {
            $result->close();
            return true;
        } else {
            error_log("DataObject:save mysql error: " . $sql . " * " . $this->link->error);
            $result->close();
            return false;
        }
    }

	public function dbUpdate($sql) {
		if ($this->link->query($sql)) {
            return true;
        } else {
            // Dump the error
            var_dump($sql . " * " .$this->link->error);
        }
	}

	public function mysqli_real_escape_string($str) {
		return mysqli_real_escape_string($this->link,$str);
	}
}
?>
