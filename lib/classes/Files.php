<?php

/**
* All File table related operations are handled by this class
*
*/
require_once(dirname(__FILE__)."/../config.php");

class Files
{
    function Files()
    {
    }

    /**
    * Adds a file, and returns it's ID
    *
    */
    function add($ext, $data)
    {
      $sql = "INSERT INTO ". JOURNAL_FILES . " set ext='$ext', data='$data' ";
      $result = mysql_query($sql);
      if($result)
      {
        $result = mysql_insert_id();
      }
      return  $result;
    }

    /**
    * Gets a file's contents
    *
    */
    function get($id)
    {
      $result = mysql_query("SELECT ext, UNIX_TIMESTAMP(uploaded_time), data
                  FROM ". JOURNAL_FILES. "
                  WHERE id=$id LIMIT 1");
      return $result;
    }
}
