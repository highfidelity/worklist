<?php
class ScanAssets {

    public function scanAll() {
        error_reporting(E_ALL | E_STRICT);
        set_time_limit(15*60);
        $scan_files = array();
        $sql_get_files = 'SELECT `id`, `userid`,(SELECT `username` FROM `' . USERS . '` where `id`=files.userid)
        AS `useremail`, files.workitem AS `worklist_id`, `url`, `title`, `description`
        FROM `' . FILES . '` WHERE `is_scanned` = 0 LIMIT 10';
        $result = mysql_query($sql_get_files);
        // Read the file names of all the files available.
        while ($row = mysql_fetch_assoc($result)) {

            $file_name  = pathinfo(parse_url($row['url'],PHP_URL_PATH),PATHINFO_BASENAME);
            $scan_files[] = $file_name;

            // Get the full path and prepare it for the command line.
            $real_path = realpath (dirname(__FILE__) .'/uploads/' . $file_name);
            $safe_path = escapeshellarg($real_path);
            // Reset the values.
            $return = -1;
            $out = '';
            $cmd = VIRUS_SCAN_CMD . ' ' . $safe_path;
            $workitem = null;
            if (!empty($safe_path) && file_exists($real_path) && filesize($real_path) > 0 ) {
                // Execute the command.
                exec ($cmd, $out, $return);

                if ($return == 0) { //if clean update db
                    $sql = 'UPDATE `' . FILES . '` SET is_scanned = 1, scan_result = 0 WHERE `id` = ' . $row['id'];
                    $notify = '';
                } else {
                    $workitem = new WorkItem();
                    $workitem->loadById($row['worklist_id']);

                    if ($return == 1) { // If the file contains a virus send email to the user and update db.
                        $notify = 'virus-found';
                        $sql = 'UPDATE `' . FILES . '` SET is_scanned = 1, scan_result = 1 WHERE `id` = ' . $row['id'];
                    } else {
                        // unknown error
                        $notify = 'virus-error';
                        $sql = 'UPDATE `' . FILES . '` SET is_scanned = 1, scan_result = 2 WHERE `id` = ' . $row['id'];
                    }
                }
            } else {
                // If the file does not exist/0 bytes, mark it with a new status so we don't keep iterating on it - garth
                error_log("failed test: $safe_path " . file_exists($safe_path) . " " .filesize($safe_path));
                $sql = 'UPDATE `' . FILES . '` SET is_scanned = 4, scan_result = 0 WHERE `id` = ' . $row['id'];
                $out = '<p>File->' . $safe_path .' not found.</p>';
            }

            mysql_query($sql) or die(mysql_error() .':' . $sql);

            //send mail if there's a problem
            if (! empty($notify)) {
                Notification::workitemNotify(array(
                    'type' => $notify,
                    'workitem' => $workitem,
                    'emails' => array($row['useremail']),
                    'file_name' => $file_name,
                    'file_title' => $row['title']
                ));
            }
            print('<br>' . $row['title'] . ' <p>Upload Report:' .
                (empty($notify) ? 'Ok' : ($notify == 'virus-found') ? 'Infected' : 'Error').'</p>');
            print_r($out);
        }
    } //End of method
    //This method gets passed a filename directly
    public function _scanFile($file_name) {
        set_time_limit(15*60);

        // Get the full path and prepare it for the command line.
        $safe_path = escapeshellarg($file_name);
        // Reset the values.
        $return = -1;
        $out = '';
        $cmd = VIRUS_SCAN_CMD . ' ' . $safe_path;
        $fct_return = false;

        if (!empty($safe_path) && file_exists($file_name) && filesize($file_name) > 0 ) {
            // Execute the command.
            //error_log("scanning with $cmd");
            exec ($cmd, $out, $return);
        }
        //error_log( "results: $return " . print_r($out, true) );
        return $return;
    } //End of method
    //This method finds the filename by id in the database
    public function scanFile($id) {
        set_time_limit(15*60);

        //scan_files = array();
        $sql_get_files = 'SELECT `id`, `userid`,(SELECT `username` FROM `' . USERS . '` where `id`=files.userid)
        AS `useremail`, files.workitem AS `worklist_id`, `url`, `title`, `description`
        FROM `' . FILES . '` WHERE id=' . $id;
        $result = mysql_query($sql_get_files);
        $row = mysql_fetch_assoc($result);

        // Get the file name.
        $file_name  = pathinfo(parse_url($row['url'],PHP_URL_PATH),PATHINFO_BASENAME);

        // Get the full path and prepare it for the command line.
        $real_path = VIRUS_SCAN_DIR . '/' . $file_name;
        $safe_path = escapeshellarg($real_path);
        // Reset the values.
        $return = -1;
        $out = '';
        $cmd = VIRUS_SCAN_CMD . ' ' . $safe_path;
        $fct_return = false;

        if (!empty($safe_path) && file_exists($real_path) && filesize($real_path) > 0 ) {
            // Execute the command.
            exec ($cmd, $out, $return);
            if ($return == 0) { //if clean update db
                $sql = 'UPDATE `' . FILES . '` SET is_scanned = 1, scan_result = 0 WHERE `id` = ' . $id;
                $notify = '';
                $fct_return = true;
            } else {
                $workitem = new WorkItem();
                $workitem->loadById($row['worklist_id']);

                if ($return == 1) { // If the file contains a virus send email to the user and update db.
                    $notify = 'virus-found';
                    $sql = 'UPDATE `' . FILES . '` SET is_scanned = 1, scan_result = 1 WHERE `id` = ' . $id;
                } else {
                    // <unknown error
                    $notify = 'virus-error';
                    $sql = 'UPDATE `' . FILES . '` SET is_scanned = 1, scan_result = 2 WHERE `id` = ' . $id;
                }
            }

            if (mysql_query($sql)) {
                // send mail if there's a problem
                if (! empty($notify)) {
                   Notification::workitemNotify(array(
                       'type' => $notify,
                       'workitem' => $workitem,
                       'emails' => array($row['useremail']),
                       'file_name' => $file_name,
                       'file_title' => $row['title']
                   ));
                    if(!Utils::send_email($row['title'], $subject, $message)) {
                        //Don't fail silently if we can't send the message also
                        error_log("cron ScanAssets: Utils::send_email failed, msg: " . $message);
                    }
                }
            } else {
                error_log('error SQL');
            }
        }

        return $fct_return;
    } //End of method
} //End of Class
