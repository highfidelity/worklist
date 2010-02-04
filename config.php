<?php
//
//  Copyright (c) 2009, LoveMachine Inc.
//  All Rights Reserved. 
//  http://www.lovemachineinc.com
//

if (file_exists('server.local.php')) {
    include_once('server.local.php');
}

if (!defined("APP_NAME"))       define("APP_NAME","Worklist");
if (!defined("APP_LOCATION"))   define("APP_LOCATION",substr($_SERVER['SCRIPT_NAME'], 1, strrpos($_SERVER['SCRIPT_NAME'], "/")));
//http[s]://[[SECURE_]SERVER_NAME]/[LOCATION/]index.php   #Include a TRAILING / if LOCATION is defined
if (!defined("SERVER_NAME"))    define("SERVER_NAME","dev.sendlove.us");
if (!defined("SERVER_URL"))     define("SERVER_URL",'http://'.SERVER_NAME.'/'.APP_LOCATION); //Include [:port] for standard http traffic if not :80
//SSL Not enabled on development
//define("SECURE_SERVER_URL",'https://'.SERVER_NAME.'/'.APP_LOCATION); //Secure domain defaults to standard; Include [:port] for secure https traffic if not :443
//So clone the standard URL
if (!defined("SECURE_SERVER_URL")) define("SECURE_SERVER_URL",SERVER_URL); //Secure domain defaults to standard; Include [:port] for secure https traffic if not :443

if (!defined("DB_SERVER"))      define("DB_SERVER", "localhost");
if (!defined("DB_USER"))        define("DB_USER", "root");
if (!defined("DB_PASSWORD"))    define("DB_PASSWORD", "");
if (!defined("DB_NAME"))        define("DB_NAME", "worklist_leonty");

if (!defined("WORKLIST"))       define("WORKLIST", "worklist");
if (!defined("USERS"))          define("USERS", "users");
if (!defined("BIDS"))          define("BIDS", "bids");
if (!defined("FEES"))          define("FEES", "fees");

if (!defined("SALT"))           define("SALT", "WORKLIST");
if (!defined("SESSION_EXPIRE")) define("SESSION_EXPIRE", 1440);
if (!defined("REQUIRELOGINAFTERCONFIRM")) define("REQUIRELOGINAFTERCONFIRM", 1);

// Refresh interval for ajax updates of the history table (in seconds)
if (!defined("AJAX_REFRESH"))   define("AJAX_REFRESH", 30);

//pagination vars
if (!defined("QS_VAR"))         define("QS_VAR", "page");

if (!defined("STR_FWD"))        define("STR_FWD", "&nbsp;&nbsp;Next");
if (!defined("STR_BWD"))        define("STR_BWD", "Prev&nbsp;&nbsp;");
if (!defined("IMG_FWD"))        define("IMG_FWD", "images/left.png");
if (!defined("IMG_BWD"))        define("IMG_BWD", "images/right.png");


/*
 * Non-configuration values (CONSTANTS)
 */

// User features: bits in the users.features column
define("FEATURE_SUPER_ADMIN",	0x0001);
define("FEATURE_USER_MASK",	    0x0001);

?>
