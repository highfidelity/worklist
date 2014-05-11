<?php
require_once(dirname(__FILE__)."/../config.php");

class Database {
    /**
     * @var resource
     */
    protected $link;

    public function __destruct(){
        if($this->link){
            mysql_close($this->link);
        }
    }
    public function establishConnection(){
        $link = mysql_connect(DB_SERVER, DB_USER, DB_PASSWORD);
        if(! $link){
            die('Could not connect: ' . mysql_error());
        }
        mysql_select_db(DB_NAME, $link);
        $this->setLink($link);
    }
    public function getError(){
         return mysql_error($this->getLink());
    }
    public function setLink($link){
        $this->link = $link;
        return $this;
    }
    public function getLink(){
        if(! $this->link){
            $this->establishConnection();
        }
        return $this->link;
    }
    public function query($sql){
        return mysql_query($sql, $this->getLink());
    }
    public function get_var($sql, $row = 0){
        $result = $this->query($sql);
        return mysql_result($result, $row);
    }
    public function get_row($sql, $output_type = 0){
        $result = $this->query($sql);
        switch($output_type){
            case 1:
                return mysql_fetch_assoc($result);
                break;
            case 2:
                return mysql_fetch_array($result, MYSQL_NUM);
                break;
            default:
                return mysql_fetch_object($result);
        }
    }
    public function get_results($sql, $output_type = 0){
        $result = $this->query($sql);
        switch($output_type){
            case 1:
                return mysql_fetch_assoc($result);
                break;
            case 2:
                return mysql_fetch_array($result, MYSQL_NUM);
                break;
            default:
                return mysql_fetch_object($result);
        }
    }
    public function insert($table, $data, $format){
        $sql = "INSERT INTO " . $table . " ";
        $col = "(";
        $val = " VALUES(";
        $c = 0;
        foreach($data as $column => $value){
            $col .= "`" . $column . "`,";
            $val .= "'" . sprintf($format[$c], $value) . "',";
            $c++;
        }
        $col = substr($col, 0, (strlen($col) - 1));
        $col .= ")";
        $val = substr($val, 0, (strlen($val) - 1));
        $val .= ")";
        $sql .= $col;
        $sql .= $val;
        $this->query($sql);
    }
    public function update($table, $data, $where, $format = null, $where_format = null){
        $sql = "UPDATE " . $table . " ";
        $set = "SET";
        $w = " WHERE";

        $c = 0;
        foreach($data as $column => $value){
            if(isset($format)){
                $set .= " " . $column . " = '" . sprintf($format[$c], $value) . "',";
            }else{
                $set .= " " . $column . " = '" . $value . "',";
            }
            $c++;
        }

        $c = 0;
        foreach($where as $column => $value){
            if(isset($where_format)){
                $w .= " " . $column . " = '" . sprintf($where_format[$c], $value) . "' AND";
            }else{
                $w .= " " . $column . " = '" . $value . "' AND";
            }
            $c++;
        }
        $set = substr($set, 0, (strlen($set) - 1));
        $w = substr($w, 0, (strlen($w) - 4));
        $sql .= $set;
        $sql .= $w;
        $this->query($sql);
    }
}
