<?php
require_once (dirname(__FILE__)."/../config.php");

Session::check();

// check for referer
if(!checkReferer()){
    die(json_encode(array('error' => 'Wrong referer')));
}
// check if we access this page from the script
if($_POST['csrf_token'] != $_SESSION['csrf_token']){
    die(json_encode(array('error' => 'Invalid token')));
}

mysql_connect(DB_SERVER, DB_USER, DB_PASSWORD) or die(mysql_error());;
mysql_select_db(DB_NAME) or die(mysql_error());

require_once(dirname(__FILE__)."/../chat.class.php");

if(!empty($_SESSION['username']) && empty($_SESSION['nickname']))
{
  // setting up session.
  initSessionData($_SESSION['username']);
}

	$error = "";
	$msg = "";
	$fileElementName = 'attachment';
	if (empty($_SESSION['username'])) {
	    $error = 'You need to be logged in to upload a file';
	}
	else if(!empty($_FILES[$fileElementName]['error']))
	{
		switch($_FILES[$fileElementName]['error'])
		{

			case '1':
				$error = 'The uploaded file exceeds the upload_max_filesize directive in php.ini';
				break;
			case '2':
				$error = 'The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form';
				break;
			case '3':
				$error = 'The uploaded file was only partially uploaded';
				break;
			case '4':
				$error = 'No file was uploaded.';
				break;

			case '6':
				$error = 'Missing a temporary folder';
				break;
			case '7':
				$error = 'Failed to write file to disk';
				break;
			case '8':
				$error = 'File upload stopped by extension';
				break;
			case '999':
			default:
				$error = 'No error code avaiable';
		}
	}elseif(empty($_FILES[$fileElementName]['tmp_name']) || $_FILES[$fileElementName]['tmp_name'] == 'none')
	{
		$error = 'No file was uploaded..';
	}else
	{
			//$msg .= " File Name: " . $_FILES[$fileElementName]['name'] . ", ";
			//$msg .= " File Size: " . @filesize($_FILES[$fileElementName]['tmp_name']);
			$fileName = $_FILES[$fileElementName]['name'];
			@list(, , $imtype, ) = getimagesize($_FILES[$fileElementName]['tmp_name']);
			// Get image type.
			// We use @ to omit errors
			if ($imtype == 3) // cheking image type
			    $ext="png";   // to use it later in HTTP headers
			elseif ($imtype == 2)
			    $ext="jpeg";
			elseif ($imtype == 1)
			    $ext="gif";
			else
			    $msg = 'Error: unknown file format';

			if (isset($ext)) // If there was no error
			{
				    $tmpName = $_FILES[$fileElementName]['tmp_name'];
				    /*$fp      = fopen($tmpName, 'r');
				    $content = fread($fp, filesize($tmpName));
				    $content = addslashes($content);
				    fclose($fp);

				    if(!get_magic_quotes_gpc())
				    {
					$fileName = addslashes($fileName);
				    }
*/
				    $data = file_get_contents($_FILES[$fileElementName]['tmp_name']);
				    $data = mysql_real_escape_string($data);
				    $files = new Files();
				    $result = $files->add($ext,$data);



				    if($result) {
				      $image_id = $result;

				      if($image_id > 0) {
					  $author = $_SESSION['nickname'];
					  $href = ATTACHMENT_URL . "?id=$image_id";
					  $message = '<a href="' . $href .'" class="attachment">' . $href . '</a>';
					  $from = "$author";
					  if($author) {
					   $chat = new Chat();
					   $result = $chat->sendEntry($from, $message, array(),true);
					  }
				      }
				    }

			}

			//for security reasons, remove all uploaded files
 			@unlink($_FILES[$fileElementName]);
	}
	echo "{";
	echo				"error: '" . $error . "',\n";
	echo				"msg: '" . $msg . "'\n";
	echo "}";

?>
