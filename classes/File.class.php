<?php
//  Copyright (c) 2010, LoveMachine Inc.
//  All Rights Reserved.
//  http://www.lovemachineinc.com

//  This class handles one or more Files if you need more functionality don't hesitate to add it.
//  But please be as fair as you comment your methods - maybe another developer needs them too.

class File {

    protected $id;
    protected $userid;
    protected $workitem;
    protected $projectid;
    protected $mime;
    protected $title;
    protected $description;
    protected $url;
    protected $status;
    protected $is_scanned;
    protected $scan_result;
    protected $files = array();

    /**
     * With this constructor you can create a user by passing an array.
     *
     * @param array $options
     * @return User $this
     */
    public function __construct(array $options = null) {
        if (is_array($options)) {
            $this->setOptions($options);
        }
        return $this;
    }

    /**
     * This method finds a file by its id
     *
     * @param int $id
     * @return File $this
     */
    public function findFileById($id = null) {
        if (null === $id) {
            throw new Exception('No file id defined!');
        }
        $where = sprintf('`id` = %d', (int)$id);
        return $this->loadFiles($where);
    }

    /**
     * This method finds a file by its url
     *
     * @param string $url
     * @return File $this
     */
    public function findFileByUrl($url) {
        $where = sprintf("`url` = '%s'", $url);
        return $this->loadFiles($where);
    }

    /**
     * @return the $id
     */
    public function getId() {
        return $this->id;
    }

    /**
     * @param $id the $id to set
     */
    public function setId($id) {
        $this->id = $id;
        return $this;
    }

    /**
     * @return the $userid
     */
    public function getUserid() {
        return $this->userid;
    }

    /**
     * @param $workitem the $userid to set
     */
    public function setUserid($userid) {
        $this->userid = $userid;
        return $this;
    }

    /**
     * @return the $workitem
     */
    public function getWorkitem() {
        return $this->workitem;
    }

    /**
     * @param $workitem the $workitem to set
     */
    public function setWorkitem($workitem) {
        $this->workitem = $workitem;
        return $this;
    }

    /**
    * @return the $projectid
    */
    public function getProjectId() {
        return $this->projectid;
    }

    /**
    * @param $projectid to set
    */
    public function setProjectId($projectid) {
        $this->projectid = $projectid;
        return $this;
    }

    /**
     * @return the $mime
     */
    public function getMime() {
        return $this->mime;
    }

    /**
     * @param $mime the $mime to set
     */
    public function setMime($mime) {
        $this->mime = $mime;
        return $this;
    }

    /**
     * @return the $title
     */
    public function getTitle() {
        return $this->title;
    }

    /**
     * @param $title the $title to set
     */
    public function setTitle($title) {
        $this->title = $title;
        return $this;
    }

    /**
     * @return the $description
     */
    public function getDescription() {
        return $this->description;
    }

    /**
     * @param $description the $description to set
     */
    public function setDescription($description) {
        $this->description = $description;
        return $this;
    }

    /**
     * @return the $url
     */
    public function getUrl() {
        return $this->url;
    }

    /**
     * @return the scan status
     */
    public function getIs_scanned() {
        return $this->is_scanned;
    }

    /**
     * @param scan status the $is_scanned to set
     */
    public function setIs_scanned($is_scanned) {
        $this->is_scanned = $is_scanned;
        return $this;
    }

    /**
     * @param $url the $url to set
     */
    public function setUrl($url) {
        $this->url = $url;
        return $this;
    }

    /**
     * @return the $status
     */
    public function getStatus() {
        return $this->status;
    }

    /**
     * @param $status the $status to set
     */
    public function setStatus($status) {
        $this->status = $status;
    }

    /**
     * @return the $files
     */
    public function getFiles() {
        return $this->files;
    }

    /**
     * Checks if the setter for the property exists and calls it
     *
     * @param string $name Name of the property
     * @param string $value Value of the property
     * @throws Exception
     * @return void
     */
    public function __set($name, $value) {
        $method = 'set' . ucfirst($name);
        if (!method_exists($this, $method)) {
            throw new Exception('Invalid ' . __CLASS__ . ' property');
        }
        $this->$method($value);
    }

    /**
     * Checks if the getter for the property exists and calls it
     *
     * @param string $name Name of the property
     * @param string $value Value of the property
     * @throws Exception
     * @return void
     */
    public function __get($name) {
        $method = 'get' . ucfirst($name);
        if (!method_exists($this, $method)) {
            throw new Exception('Invalid ' . __CLASS__ . ' property');
        }
        $this->$method();
    }

    /**
     * Use this method to update or insert a user.
     *
     * @return (boolean)
     */
    public function save() {
        if (null === $this->getId()) {
            $id = $this->insert();
            if ($id !== false) {
                $this->setId($id);
                return true;
            }
            return false;
        } else {
            return $this->update();
        }
    }

    /**
     * Automatically sets the options array
     * Array: Name => Value
     *
     * @param array $options
     * @return User $this
     */
    private function setOptions(array $options) {
        $methods = get_class_methods($this);
        foreach ($options as $key => $value) {
            $method = 'set' . ucfirst($key);
            if (in_array($method, $methods)) {
                $this->$method($value);
            }
        }
        return $this;
    }

    private function loadFiles($where) {
        // now we build the sql query
        $sql = 'SELECT * FROM `' . FILES . '` WHERE ' . $where . ' LIMIT 1;';
        // and get the result
        $result = mysql_query($sql);

        if ($result && (mysql_num_rows($result) == 1)) {
            $options = mysql_fetch_assoc($result);
            $this->setOptions($options);
            return $this;
        }
        return false;
    }

    private function getColumns() {
        $columns = array();
        $result = mysql_query('SHOW COLUMNS FROM `' . FILES);
        if (mysql_num_rows($result) > 0) {
            while ($row = mysql_fetch_assoc($result)) {
                $columns[] = $row;
            }
            return $columns;
        }
        return false;
    }

    private function prepareData() {
        $columns = $this->getColumns();
        $cols = array(); $values = array();
        foreach ($columns as $col) {
            $method = 'get' . ucfirst($col['Field']);
            if (method_exists($this, $method) && (null !== $this->$method())) {
                $cols[] = $col['Field'];
                if (preg_match('/(char|text|blob)/i', $col['Type']) === 1) {
                    $values[] = mysql_real_escape_string($this->$method());
                } else {
                    $values[] = $this->$method();
                }
            }
        }
        return array(
            'columns' => $cols,
            'values' => $values
        );
    }

    private function insert() {
        $data = $this->prepareData();
        $sql = 'INSERT INTO `' . FILES . '` (`' . implode('`,`', $data['columns']) . '`) VALUES ("' . implode('","', $data['values']) . '")';
        $result = mysql_query($sql);
        if ($result) {
            return mysql_insert_id();
        }
        return false;
    }

    private function update() {
        $flag = false;
        $data = $this->prepareData();
        $sql = 'UPDATE `' . FILES . '` SET ';
        foreach ($data['columns'] as $index => $column) {
            if ($column == 'id') {
                continue;
            }
            if ($flag === true) {
                $sql .= ', ';
            }
            $sql .= '`' . $column . '` = "' . $data['values'][$index] . '"';
            $flag = true;
        }
        $sql .= ' WHERE `id` = ' . (int)$this->getId() . ';';
        $result = mysql_query($sql);
        if ($result) {
            return true;
        }
        return false;
    }

    public function remove() {

        $sql = "
            DELETE FROM `" . FILES . "`
            WHERE `id` = " . (int) $this->getId();

        $result = mysql_query($sql);
        if ($result) {
            // delete from S3
            $this->s3Remove($this->getUrl());

            return array(
                'success' => true,
                'message' => "File removed:" . (int) $this->getId()
            );
        }
        return array(
            'success' => false,
            'message' => "Error, cannot remove file :" . (int)$this->getId() . " , sql:" . $sql
        );
    }

    /** Get the name of a file based on its URL **/
    public function getFileName() {
        $file_name  = pathinfo(parse_url($this->getUrl(), PHP_URL_PATH), PATHINFO_BASENAME);
        return $file_name;
    }

    /** Build the path to a file based on its filename **/
    public function getRealPath() {
        // Get the file name.
        return UPLOAD_PATH . '/' . $this->getFileName();
    }

    /** Get the safe path to a file **/
    public function getSafePath() {
        return escapeshellarg($this->getRealPath());
    }

    // Uploads a file to S3.
    // $source_filename must be a full path   (ie. '/tmp/uploads/whatever.jpg')
    // $dest_filename   can be a path as well (ie. 'images/happyface.png')
    public static function s3Upload($source_filename, $dest_filename, $public = true) {
        S3::setAuth(S3_ACCESS_KEY, S3_SECRET_KEY);
        if (S3::putObject(
            S3::inputFile($source_filename),
            S3_BUCKET,
            $dest_filename,
            ($public ? S3::ACL_PUBLIC_READ : '')
        )) {
            return true;
        } else {
            throw new Exception("S3 upload failed ({$source_filename})");
        }
    }

    public static function s3Remove($uri) {
        // get just the URI component, strip the base_url
        $uri = str_replace(S3_URL_BASE, '', $uri);
        S3::setAuth(S3_ACCESS_KEY, S3_SECRET_KEY);
        if (S3::deleteObject(
            S3_BUCKET,
            $uri
        )) {
            return true;
        } else {
            error_log('Failed to delete file with uri: ' . $uri);
        }
    }

    public static function s3AuthenticatedURL($uri) {
        S3::setAuth(S3_ACCESS_KEY, S3_SECRET_KEY);

        //Save logging status
        $save_logging = error_reporting();
        // Turn off logging for S3
        error_reporting(0);

        $authUrl = S3::getAuthenticatedURL(S3_BUCKET, $uri, 10 * 24 * 60 * 60, false, true);  //10 days

        // Restore logging status
        error_reporting($save_logging);

        //Debug results from Auth access
        //error_log("s3Auth: $uri $authUrl");
        return $authUrl;
    }

    public static function uniqueFilename($strExt = 'tmp') {
        // explode the IP of the remote client into four parts
        $arrIp = explode('.', $_SERVER['REMOTE_ADDR']);
        // get both seconds and microseconds parts of the time
        list($usec, $sec) = explode(' ', microtime());
        // fudge the time we just got to create two 16 bit words
        $usec = (integer) ($usec * 65536);
        $sec = ((integer) $sec) & 0xFFFF;
        // fun bit--convert the remote client's IP into a 32 bit
        // hex number then tag on the time.
        // Result of this operation looks like this xxxxxxxx-xxxx-xxxx
        $strUid = sprintf("%08x-%04x-%04x", ($arrIp[0] << 24) | ($arrIp[1] << 16) | ($arrIp[2] << 8) | $arrIp[3], $sec, $usec);
        // tack on the extension and return the filename
        return $strUid . '.' . $strExt;
    }

    public static function isAllowed($filestatus = 3, $user = null) {
        if (null === $user) {
            return false;
        }

        switch ($filestatus) {
            case 3:
                if ($user->isRunner && $user->isPayer) {
                    return true;
                }
                break;
            case 2:
                if ($user->isRunner) {
                    return true;
                }
                break;
            case 1:
                if ($user->isPayer) {
                    return true;
                }
                break;
            case 0:
                return true;
                break;
        }
        return false;
    }
    /**
     * This method fetches all files for a workitem
     *
     * @param int $workitem
     * @return array $files
     */
    public static function fetchAllFilesForWorkitem($workitem = null) {
        if (null === $workitem) {
            throw new Exception('You have to define a workitem!');
        }
        $sql = 'SELECT `id` FROM `' . FILES . '` WHERE `workitem` = ' . (int) $workitem . ' AND `scan_result` = 0';
        $result = mysql_query($sql);
        $files = array();
        while ($row = mysql_fetch_assoc($result)) {
            $file = new File();
            $file->findFileById($row['id']);
            $files[] = $file;
        }
        return $files;
    }

    /**
     * This method fetches all files for a project
     *
     * @param int $projectid
     * @return array $files
     */
    public static function fetchAllFilesForProject($projectid = null) {
        if (null === $projectid) {
            throw new Exception('You have to define a projectid!');
        }
        $sql = 'SELECT `id` FROM `' . FILES . '` WHERE `workitem` IS NULL and `projectid`=' . (int) $projectid;
        $result = mysql_query($sql);
        $files = array();
        while ($row = mysql_fetch_assoc($result)) {
            $file = new File();
            $file->findFileById($row['id']);
            $files[] = $file;
        }
        return $files;
    }

    public static function fileUploadErrorMessage($error_code) {
        switch ($error_code) {
            case UPLOAD_ERR_INI_SIZE:
                return 'The uploaded file exceeds the max filesize of 2 MB.';
            case UPLOAD_ERR_FORM_SIZE:
                return 'The uploaded file exceeds the max filesize of 2 MB.';
            case UPLOAD_ERR_PARTIAL:
                return 'The uploaded file was only partially uploaded, please try again later.';
            case UPLOAD_ERR_NO_FILE:
                return 'No file was uploaded.';
            case UPLOAD_ERR_NO_TMP_DIR:
                return 'Missing a temporary folder, please try again later.';
            case UPLOAD_ERR_CANT_WRITE:
                return 'Failed to write file to disk, please try again later.';
            case UPLOAD_ERR_EXTENSION:
                return 'File upload stopped by extension, please try again later.';
            default:
                return 'Unknown upload error, please try again later.';
        }
    }

    public static function getIconFromMime($mime = '') {
        $mimes = array(
            'application/pdf'           => 'images/icons/pdf.png',
            'application/x-download'    => 'images/icons/pdf.png',
            'text/plain'                => 'images/icons/txt.png',
            'text/rtf'                  => 'images/icons/rtf.png',
            'image/jpeg'                => false,
            'image/pjpeg'               => false,
            'image/gif'                 => false,
            'image/png'                 => false,
            'image/x-png'               => false
        );

        if (isset($mimes[$mime])) {
            return $mimes[$mime];
        }
        return 'images/icons/default.png';
    }

}
