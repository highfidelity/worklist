<?php
//  Copyright (c) 2010, LoveMachine Inc.
//  All Rights Reserved.
//  http://www.lovemachineinc.com
include ("config.php");

try {
    Session::check();
	$server = new JsonServer();
	$server->run();
	echo($server->getOutput());
} catch(Exception $e) {
	echo(json_encode(array(
		'success' => false,
		'message' => $e
	)));
}

?>
