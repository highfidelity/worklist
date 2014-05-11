<?php
include ("../config.php");

mysql_connect(DB_SERVER, DB_USER, DB_PASSWORD) or die(mysql_error());;
mysql_select_db(DB_NAME) or die(mysql_error());

$id = intval($_GET['id']);
if($id <= 0)
{
       die('no image');
}
    $files = new Files();
    $result = $files->get($id);

    if (mysql_num_rows($result) == 0)
        die('no image');

    list($ext, $image_time, $data) = mysql_fetch_row($result);

    $send_304 = false;
    if (php_sapi_name() == 'apache') {
        // if our web server is apache
        // we get check HTTP
        // If-Modified-Since header
        // and do not send image
        // if there is a cached version

        $ar = apache_request_headers();
        if (isset($ar['If-Modified-Since']) && // If-Modified-Since should exists
            ($ar['If-Modified-Since'] != '') && // not empty
            (strtotime($ar['If-Modified-Since']) >= $image_time)) // and grater than
            $send_304 = true;                                     // image_time
    }
    if ($send_304)
    {
        // Sending 304 response to browser
        // "Browser, your cached version of image is OK
        // we're not sending anything new to you"
        header('Last-Modified: '.gmdate('D, d M Y H:i:s', $ts).' GMT', true, 304);

        exit(); // bye-bye
    }

    // outputing Last-Modified header
    header('Last-Modified: '.gmdate('D, d M Y H:i:s', $image_time).' GMT',
            true, 200);

    // Set expiration time +1 year
    // We do not have any photo re-uploading
    // so, browser may cache this photo for quite a long time
    header('Expires: '.gmdate('D, d M Y H:i:s',  $image_time + 86400*365).' GMT',
            true, 200);

    // outputing HTTP headers
    header('Content-Length: '.strlen($data));
    header("Content-type: image/{$ext}");

    // outputing image
    echo $data;
    exit();
