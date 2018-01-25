<?php
//
//  Copyright (c) 2011, LoveMachine Inc.
//  All Rights Reserved.
//  http://www.lovemachineinc.com
//

require_once('vendor/autoload.php');

//Joanne added the following line for the journal attachments
$current_dir = dirname(__FILE__) . '/';

// Use this function to not overwrite values that were previously
// specified in server.local.php
// @TODO: Migrate all constants to use this handy function :)
function defineOnce($key, $value) {
    if (!defined($key)) {
        define($key, $value);
    }
}

if (file_exists(dirname(__FILE__).'/server.local.php')) {
    include_once(dirname(__FILE__).'/server.local.php');
} else {
    header("HTTP/1.0 404 Not Found");
    die(
        '<h1>Application configuration not found</h1>' .
        '<p>Please follow the worklist wiki <a target="_blank" href="' .
        'https://github.com/highfidelity/worklist/wiki/Using-GitHub-with-the-Worklist-Project#ii--setting-up-your-sandbox-environment' .
        '">steps to set up your sandbox</a> properly.</p>'
    );
}

require_once('Zend/Config.php');
require_once('Zend/Config/Ini.php');
require_once('Zend/Registry.php');

defineOnce('MYSQL_DEBUG_LEVEL', 0);
defineOnce('MYSQL_DEBUG_MESSAGE_DEFAULT', 'General database error');

defineOnce('SENDGRID_API_URL','https://sendgrid.com/api/mail.send.json');
defineOnce('SENDGRID_API_USER','worklist-dev');
defineOnce('SENDGRID_API_KEY','38MacRUwrawRaq3');

if (!defined('DEFAULT_SENDER')) define('DEFAULT_SENDER', 'worklist@worklist.net');
if (!defined('DEFAULT_SENDER_NAME')) define('DEFAULT_SENDER_NAME', 'Worklist');
if (!defined('NOREPLY_SENDER')) define('NOREPLY_SENDER', 'Worklist <no-reply@worklist.net>');
if (!defined('SMS_SENDER'))     define('SMS_SENDER', 'worklist@worklist.net');

# Add revision (version) information
if (!defined('APP_REVISION'))   define('APP_REVISION', '$Rev$');
if (!defined('APP_VERSION'))    define('APP_VERSION', preg_replace('/\D/', '', APP_REVISION));

// this is the name of the app that will be used when
// authenticating with login service.
// change it per app.
if (!defined("SERVICE_NAME"))   define("SERVICE_NAME", 'worklistmachine');

if (!defined('APP_NAME'))       define('APP_NAME','Worklist');
if (!defined('APP_LOCATION'))   define('APP_LOCATION',substr($_SERVER['SCRIPT_NAME'], 1, strrpos($_SERVER['SCRIPT_NAME'], '/')));
if (!defined('APP_BASE'))       define('APP_BASE',substr(APP_LOCATION, 0, strrpos(APP_LOCATION, '/', -2)));
if (!defined('APP_PATH'))       define('APP_PATH', realpath(dirname(__FILE__)));
if (!defined('UPLOAD_PATH'))    define('UPLOAD_PATH', realpath(APP_PATH . '/uploads'));
if (!defined('LIB_PATH'))       define('LIB_PATH', realpath(APP_PATH . '/lib'));
if (!defined('CLASSES_PATH'))   define('CLASSES_PATH', realpath(LIB_PATH . '/classes'));

if (!defined('APP_ENV'))        define('APP_ENV', 'production');

//http[s]://[[SECURE_]SERVER_NAME]/[LOCATION/]index.php   #Include a TRAILING / if LOCATION is defined
if (!defined('SERVER_NAME'))    define('SERVER_NAME','dev.worklist.net');
if (!defined('SANDBOX_SERVER')) define('SANDBOX_SERVER','dev.worklist.net');
if (!defined('SERVER_URL'))     define('SERVER_URL','https://'.SERVER_NAME.'/'.APP_LOCATION); //Include [:port] for standard http traffic if not :80
if (!defined('SERVER_BASE'))    define('SERVER_BASE','https://'.SERVER_NAME.'/'.APP_BASE);
//SSL Not enabled on development
//Secure domain defaults to standard; Include [:port] for secure https traffic if not :443
if (!defined('SECURE_SERVER_URL')) define("SECURE_SERVER_URL",'https://'.SERVER_NAME.'/'.APP_LOCATION);
if (!defined('SVN_BASE_URL'))   define('SVN_BASE_URL', 'http://svn.worklist.net/listing.php?repname=');
if (!defined('SVN_REV_URL'))    define('SVN_REV_URL', 'http://svn.worklist.net/revision.php?repname=');
if (!defined('FEEDBACK_EMAIL')) define('FEEDBACK_EMAIL', 'contact@highfidelity.io');
if (!defined('FINANCE_EMAIL'))  define('FINANCE_EMAIL', 'finance@highfidelity.io');

if (!defined('DB_SERVER'))      define('DB_SERVER', 'dev-db.worklist.net');
if (!defined('DB_USER'))        define('DB_USER', 'dev_worklist');
if (!defined('DB_PASSWORD'))    define('DB_PASSWORD', 'unsecure');
if (!defined('DB_NAME'))        define('DB_NAME', 'dev_worklist');


//worklist tables
if (!defined('BIDS'))           define('BIDS', 'bids');
if (!defined('BUDGETS'))        define('BUDGETS', 'budgets');
if (!defined('BUDGET_SOURCE'))  define('BUDGET_SOURCE', 'budget_source');
if (!defined('COMMENTS'))       define('COMMENTS', 'comments');
if (!defined('COUNTRIES'))     define('COUNTRIES', 'countries');
if (!defined('FEES'))           define('FEES', 'fees');
defineOnce('USERS_FAVORITES', 'rel_users_favorites');
if (!defined('FILES'))          define('FILES', 'files');
if (!defined('FUNDS'))          define('FUNDS', 'funds');
if (!defined('PAYPAL_ADMINS'))  define('PAYPAL_ADMINS', 'paypal_admins');
if (!defined('PAYPAL_LOG'))     define('PAYPAL_LOG', 'paypal_log');
if (!defined('PROJECTS'))       define('PROJECTS', 'projects');
if (!defined('PROJECT_USERS'))  define('PROJECT_USERS', 'project_users');
defineOnce("PROJECT_RUNNERS", "rel_project_runners");
if (!defined('ROLES'))          define('ROLES', 'roles');
if (!defined('LABELS'))         define('LABELS', 'labels');
if (!defined('STATUS_LOG'))     define('STATUS_LOG', 'status_log');
if (!defined('TASK_FOLLOWERS')) define('TASK_FOLLOWERS', 'task_followers');
if (!defined('TOKENS'))         define('TOKENS', 'tokens');
if (!defined('WORKITEM_LABELS')) define('WORKITEM_LABELS', 'workitem_labels');
if (!defined('PROJECT_LABELS')) define('PROJECT_LABELS', 'project_labels');
if (!defined('USER_STATUS'))    define('USER_STATUS', 'user_status');
if (!defined('USERS'))          define('USERS', 'users');
if (!defined('USERS_AUTH_TOKENS')) define('USERS_AUTH_TOKENS', 'users_auth_tokens');
if (!defined('WORKLIST'))       define('WORKLIST', 'worklist');
if (!defined('WS_SESSIONS'))    define('WS_SESSIONS', 'ws_sessions');
if (!defined('REL_PROJECT_CODE_REVIEWERS'))  define('REL_PROJECT_CODE_REVIEWERS', 'rel_project_code_reviewers');
if (!defined('USER_SYSTEMS'))  define('USER_SYSTEMS', 'user_systems');
if (!defined('USERS_LOVE'))  define('USERS_LOVE', 'users_love');

//journal tables
if (!defined("RECENT_SPEAKERS")) define("RECENT_SPEAKERS","recent_speakers");
if (!defined("TYPING_STATUS"))   define("TYPING_STATUS","typing_status");
if (!defined("ENTRIES")) 	     define("ENTRIES","entries");
defineOnce("ENTRIES_ALL", "entries2");

if (!defined("TOKENS")) 	     define("TOKENS","tokens");
if (!defined("SURVEYS")) 	     define("SURVEYS","surveys");
if (!defined("SURVEY_CHOICES"))  define("SURVEY_CHOICES","survey_choices");
if (!defined("SURVEY_VOTERS"))   define("SURVEY_VOTERS","survey_voters");
if (!defined("PENALTIES"))	     define("PENALTIES", "penalties");
if (!defined("ENTRYJOBS"))	     define("ENTRYJOBS", "entry_jobs");
if (!defined("BOTDATA"))         define("BOTDATA", "botdata");
if (!defined("JOURNAL_FILES"))   define("JOURNAL_FILES", "journal_files");
if (!defined("LATENCY_LOG"))	 define("LATENCY_LOG", "latency_log");
if (!defined("BLOCKED_IP"))		 define("BLOCKED_IP", "blocked_ip");

if (!defined('SALT'))            define('SALT', 'WORKLIST');
if (!defined('SESSION_EXPIRE'))  define('SESSION_EXPIRE', 365*24*60*60);
if (!defined('REQUIRELOGINAFTERCONFIRM')) define('REQUIRELOGINAFTERCONFIRM', 1);

if (!defined("LATENCY_SAMPLE")) define("LATENCY_SAMPLE", 100); // 0-100: percentage of journal entries to sample

// Penalty box timeouts in seconds
if (!defined("PB_TIMEOUT_1")) 	define("PB_TIMEOUT_1", 120);
if (!defined("PB_TIMEOUT_2")) 	define("PB_TIMEOUT_2", 300);
if (!defined("PB_TIMEOUT_3")) 	define("PB_TIMEOUT_3", 600);

// user timeout in minutes
if (!defined('USER_TIMEOUT'))    define('USER_TIMEOUT', 30);
if (!defined('USER_IDLETIME'))    define('USER_IDLETIME', 15);

if (!defined("WORKLIST_URL"))   define("WORKLIST_URL", "https://dev.worklist.net/worklist/");

//<joanne>
if (!defined("SENDLOVE_URL"))   define("SENDLOVE_URL", "https://lovemachine.sendlove.us/love");
if (!defined("REWARDER_URL"))   define("REWARDER_URL", "http://lovemachine.sendlove.us/love/tofor.php?tab=1");

//<john>
if (!defined("JOURNAL_URL")) define('JOURNAL_URL', SERVER_URL . "worklist/journal.php");

if (!defined('JOURNAL_EXISTS')) define('JOURNAL_EXISTS', 1);

if (!defined('JOURNAL_QUERY_URL')) define('JOURNAL_QUERY_URL', 'https://dev.worklist.net/worklist/aj.php');

if (!defined("SURVEYS_EMAIL")) define("SURVEYS_EMAIL","all@lovemachineinc.com");
if (!defined("JOURNAL_PICTURE_EMAIL_PREFIX")) define("JOURNAL_PICTURE_EMAIL_PREFIX","devjournalimage");
if (!defined("JOURNAL_PICTURE_EMAIL_DOMAIN")) define("JOURNAL_PICTURE_EMAIL_DOMAIN","@sendlove.us");

//<kordero>
if(!defined("ADMINS_EMAILS"))
  define("ADMINS_EMAILS",
    "ryan@lovemachineinc.com " .
    "garth.johnson@gmail.com " .
    "philip@lovemachineinc.com " .
    "tj@coffeeandpower.com " .
    "danbrown@php.net " .
    "heiberger@earthlink.net " .
    "fred@lovemachineinc.com " .
    "alexi@kostibas.com"
  );

if (!defined("OPS_EMAIL")) define("OPS_EMAIL", "ops@below92.com");
defineOnce('SUPPORT_EMAIL', 'support@worklist.net');

if(!defined('WIKI_URL')) define('WIKI_URL', 'http://wiki.worklist.net/wiki/');

// Special Accounts
if (!defined("USER_SENDLOVE"))      define("USER_SENDLOVE", "Send Love");
if (!defined("USER_SVN"))           define("USER_SVN", "SVN");
if (!defined('USER_SVN_ID'))	    define('USER_SVN_ID', 1339);
if (!defined("USER_SCHEMAUPDATE"))      define("USER_SCHEMAUPDATE", "Schema Update");
if (!defined('USER_SCHEMAUDPDATE_ID'))  define('USER_SCHEMAUPDATE_ID', 2267);
if (!defined("USER_WORKLIST"))      define("USER_WORKLIST", "Work List");
if (!defined('USER_JOURNAL'))	    define('USER_JOURNAL', 'Journal');
if (!defined('USER_JOURNAL_ID'))    define('USER_JOURNAL_ID', 1430);
if (!defined('USER_SALES'))         define('USER_SALES', 'Sales');
if (!defined('USER_SALES_ID'))      define('USER_SALES_ID', 1854);
if (!defined('USER_SITESCAN'))	    define('USER_SITESCAN','SiteScan');
if (!defined('USER_SITESCAN_ID'))   define('USER_SITESCAN_ID',462);

if (!defined('BOT_USER_ID'))    define('BOT_USER_ID', 1509);
if (!defined('GUEST_NAME'))    define('GUEST_NAME', 'Guest');

//Unspecified away text
if (!defined('NOMESSAGE'))    define('NOMESSAGE', 'Currently away');

if (!defined('BOTLIST'))        define('BOTLIST', 'him,me,love,survey,ping,');

if (!defined("SENDLOVE_API_URL")) define("SENDLOVE_API_URL", "https://dev.sendlove.us/love/api.php");
if (!defined("SENDLOVE_API_KEY")) define("SENDLOVE_API_KEY", "uierbycur4yt73467t6trtycff3rt");

defineOnce('TWITTER_USER', 'user');
defineOnce('TWITTER_PASS', 'pass');
defineOnce('TWITTER_2_USER', null);
defineOnce('TWITTER_2_PASS', null);

defineOnce('TWITTER_CONSUMER_KEY', 'consumer_key');
defineOnce('TWITTER_CONSUMER_SECRET', 'consumer_secret');
defineOnce('TWITTER_OAUTH_TOKEN', 'oauth_token');
defineOnce('TWITTER_OAUTH_SECRET', 'oauth_secret');

////How to use a local gateway
defineOnce('GOOGLE_HOST', 'localhost');
defineOnce('GOOGLE_USER', '');
defineOnce('GOOGLE_PWD', '');
defineOnce('GOOGLE_PORT', '25');
defineOnce('GOOGLE_AUTH', false);

defineOnce('S3_ACCESS_KEY', 'AKIAIXRYPAC4HCOWBXMQ');
defineOnce('S3_SECRET_KEY', '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');
defineOnce('S3_BUCKET',     'worklist-dev');
defineOnce('S3_URL_BASE',   'https://'.S3_BUCKET.'.s3.amazonaws.com/');

if (!defined("REWARDER_API_URL")) define("REWARDER_API_URL", "https://dev.sendlove.us/review/api.php");
if (!defined("REWARDER_API_KEY")) define("REWARDER_API_KEY", "dhfsfdhgdhsfg7g5fyg73ff23545f32fwd");

if (!defined("SALES_API_URL")) define("SALES_API_URL", "https://dev.sendlove.us/sales/api.php");
if (!defined("SALES_API_KEY")) define("SALES_API_KEY", "qxuakwyjhqp4zo7wt2ie");

// key to identificate api users
if (!defined("API_KEY"))    define("API_KEY", "worklist_dev_worklist_unsecure");

// update touch file
if (!defined('JOURNAL_UPDATE_TOUCH_FILE')) define('JOURNAL_UPDATE_TOUCH_FILE', '/tmp/journal_update');

// Refresh interval for ajax updates of the history table (in seconds)
if (!defined('AJAX_REFRESH'))   define('AJAX_REFRESH', 120);

//pagination vars
if (!defined('QS_VAR'))         define('QS_VAR', 'page');

if (!defined('STR_FWD'))        define('STR_FWD', '&nbsp;&nbsp;Next');
if (!defined('STR_BWD'))        define('STR_BWD', 'Prev&nbsp;&nbsp;');

if (!defined("TESTFLIGHT_API_TOKEN"))    define("TESTFLIGHT_API_TOKEN", "c5ae8c56e6ac6e6d5aefceb711070261_MTA5MzE3");

/**
 * Clickatell sms gateway settings
 */
if (defined('SMS_INI_FILE') && file_exists(SMS_INI_FILE) && is_readable(SMS_INI_FILE)) {
    $smsIni = '';//new Zend_Config_Ini(SMS_INI_FILE);
    $clickatell = $smsIni->clickatell;
    if ($clickatell !== null) {
        define('CLICKATELL_WSDL',     $clickatell->wsdl);
        define('CLICKATELL_LOCATION', $clickatell->location);
        define('CLICKATELL_API_ID',   $clickatell->api_id);
        define('CLICKATELL_USERNAME', $clickatell->username);
        define('CLICKATELL_PASSWORD', $clickatell->password);
    }
}
if (!defined('CLICKATELL_WSDL'))     define('CLICKATELL_WSDL', null);
if (!defined('CLICKATELL_LOCATION')) define('CLICKATELL_LOCATION', null);
if (!defined('CLICKATELL_API_ID'))   define('CLICKATELL_API_ID', null);
if (!defined('CLICKATELL_USERNAME')) define('CLICKATELL_USERNAME', null);
if (!defined('CLICKATELL_PASSWORD')) define('CLICKATELL_PASSWORD', null);
/*
 * Non-configuration values (CONSTANTS)
 */

// User features: bits in the users.features column
define('FEATURE_SUPER_ADMIN',       0x0001);
define('FEATURE_USER_MASK',         0x0001);

define('SMS_FLAG_JOURNAL_ALERTS',   0x0001);
define('SMS_FLAG_BID_ALERTS',       0x0002);

// Configuration Array
// New keys can be added to this array, for configuration of features
// and retrieved where needed using Zend_Registry::get('config');
$config = array(
    'database' => array(
        'name'    => 'Worklist',
        'adapter' => 'mysqli',
        'params'  => array(
            'host'     => DB_SERVER,
            'username' => DB_USER,
            'password' => DB_PASSWORD,
            'dbname'   => DB_NAME,
        )
    ),
    'email' => array(
        'mailFrom'              => DEFAULT_SENDER,
        'mailReplyTo'           => defined('DEFAULT_REPLYTO')?DEFAULT_REPLYTO:DEFAULT_SENDER,
        'host'                  => GOOGLE_HOST,
        'port'                  => GOOGLE_PORT,
        'username'              => GOOGLE_USER,
        'password'              => GOOGLE_PWD,
        'auth'                  => GOOGLE_AUTH

    ),
    'sms' => array(
        'mailFrom'              => SMS_SENDER,
        'mailReplyTo'           => defined('SMS_REPLYTO')?SMS_REPLYTO:SMS_SENDER,
        'clickatellApiWSDL'     => CLICKATELL_WSDL,
        'clickatellApiLocation' => CLICKATELL_LOCATION,
        'clickatellApiId'       => CLICKATELL_API_ID,
        'clickatellUsername'    => CLICKATELL_USERNAME,
        'clickatellPassword'    => CLICKATELL_PASSWORD
    ),
    'twitter' => array(
        array(
        'twitterUsername'       => TWITTER_USER,
        'twitterPassword'       => TWITTER_PASS,
        ),
        array(
        'twitterUsername'       => TWITTER_2_USER,
        'twitterPassword'       => TWITTER_2_PASS,
        )
    ),
    'websvn' => array(
        'baseUrl'           => 'http://svn.worklist.net',
        'repLinkFragment'   => 'listing.php?repname='
    )
);
// New config object, allows additional merging
Zend_Registry::set('config', new Zend_Config($config, true));
// Database Connection Establishment String
mysql_connect(DB_SERVER, DB_USER, DB_PASSWORD);
// Database Selection String
mysql_select_db(DB_NAME);

// time constants
if (! defined('BID_EXPIRE_WARNING')) define('BID_EXPIRE_WARNING', 7200);

/** Journal Timer **/
if (!defined("RELOAD_WINDOW_TIMER")) define("RELOAD_WINDOW_TIMER", 7200 ); // 2 hours in s = 10800 = 2 * 60 * 60
/** File uploads / S3 settings **/
/* These allow upload of profile images to S3 */
if (!defined('S3_ACCESS_KEY')) define('S3_ACCESS_KEY', 'AKIAIXRYPAC4HCOWBXMQ');
if (!defined('S3_SECRET_KEY')) define('S3_SECRET_KEY', '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');
defineOnce('S3_BUCKET', 'worklist-dev');
if (!defined('S3_URL_BASE')) define('S3_URL_BASE', 'https://'.S3_BUCKET.'.s3.amazonaws.com/');
defineOnce('APP_IMAGE_PATH', 'image/');
defineOnce('APP_ATTACHMENT_PATH', 'attachment/');
defineOnce('APP_INTERNAL_PATH', 'internal/');
defineOnce('APP_IMAGE_URL', S3_URL_BASE . APP_IMAGE_PATH);
defineOnce('APP_ATTACHMENT_URL', S3_URL_BASE . APP_ATTACHMENT_PATH);
defineOnce('APP_INTERNAL_URL', 'https://s3.amazonaws.com/' . S3_BUCKET . '/' . APP_INTERNAL_PATH);

if (! defined('VIRUS_SCAN_CMD')) define('VIRUS_SCAN_CMD', '/usr/bin/clamscan');

// max thumbnail size
if (!defined('MAX_THUMB_SIZE')) define('MAX_THUMB_SIZE', 500);

/*  budget authorized users
    Only Ryan (2), Philip (1020) & Fred (1918) can add projects! In order to work on the add projects page in your sb,
    your userid must be included below. Just remove when done!
    Adding alexi and jeskad - alexi 2011-10-26
*/
if (!defined('BUDGET_AUTHORIZED_USERS')) define('BUDGET_AUTHORIZED_USERS', ",2,1020,1918,");

// Delimiter used in linkify to separate encoded html from uncoded one.
defineOnce('DELIMITER', 'xxxXXXxxxYYYxxxXXXxxx');

defineOnce('REVIEW_NOTIFICATIONS_CRON_FILE', '/tmp/reviewNotificationsCron.dat');

// journal sound settings
defineOnce('JOURNAL_CHAT_SOUND',        0x10);
defineOnce('JOURNAL_SYSTEM_SOUND',      0x08);
defineOnce('JOURNAL_BOT_SOUND',         0x04);
defineOnce('JOURNAL_PING_SOUND',        0x02);
defineOnce('JOURNAL_EMERGENCY_ALERT',   0x01);

//Hipchat settings
defineOnce('HIPCHAT_API_AUTH_URL', "https://api.hipchat.com/v1/rooms/list?auth_token=");
defineOnce('HIPCHAT_API_MESSAGE_URL', "https://api.hipchat.com/v1/rooms/message?format=json&auth_token=");

//Tower api settings
defineOnce('TOWER_API_URL', " https://a-tower.below92.com/");

// Google Analytics settings
defineOnce('GOOGLE_ANALYTICS_TOKEN', '1/kTlFYUDNtShl_ejOORk1v8fAKhmj3FmIam1i-NTMyqE');
defineOnce('GOOGLE_ANALYTICS_PROFILE_ID', '46390018');

$countryurllist = array(
    'AF'=>'Afghanistan',
    'AL'=>'Albania',
    'ag'=>'Algeria',
    'Aq'=>'American Samoa',
    'An'=>'Andorra',
    'AO'=>'Angola',
    'Av'=>'Anguilla',
    'Ay'=>'Antarctica',
    'Ac'=>'Antigua and Barbuda',
    'AR'=>'Argentina',
    'AM'=>'Armenia',
    'aa'=>'Aruba',
    'As'=>'Australia',
    'Au'=>'Austria',
    'Aj'=>'Azerbaijan',
    'Bf'=>'Bahamas',
    'ba'=>'Bahrain',
    'Bg'=>'Bangladesh',
    'BB'=>'Barbados',
    'Bo'=>'Belarus',
    'BE'=>'Belgium',
    'Bh'=>'Belize',
    'Bn'=>'Benin',
    'Bd'=>'Bermuda',
    'BT'=>'Bhutan',
    'Bl'=>'Bolivia',
    'Bk'=>'Bosnia and Herzegovina',
    'Bc'=>'Botswana',
    'BV'=>'Bouvet Island',
    'BR'=>'Brazil',
    'io'=>'British Indian Ocean Territory',
    'bx'=>'Brunei Darussalam',
    'Bu'=>'Bulgaria',
    'uv'=>'Burkina Faso',
    'By'=>'Burundi',
    'cb'=>'Cambodia',
    'CM'=>'Cameroon',
    'CA'=>'Canada',
    'CV'=>'Cape Verde',
    'cj'=>'Cayman Islands',
    'ct'=>'Central African Republic',
    'cd'=>'Chad',
    'Ci'=>'Chile',
    'Ch'=>'China',
    'kt'=>'Christmas Island',
    'ck'=>'Cocos (Keeling) Islands',
    'CO'=>'Colombia',
    'cn'=>'Comoros',
    'cf'=>'Congo',
    'cg'=>'Congo (DR)',
    'cw'=>'Cook Islands',
    'cs'=>'Costa Rica',
    'iv'=>'Cote D\'Ivoire',
    'HR'=>'Croatia',
    'CU'=>'Cuba',
    'CY'=>'Cyprus',
    'ez'=>'Czech Republic',
    'Da'=>'Denmark',
    'DJ'=>'Djibouti',
    'Do'=>'Dominica',
    'Dr'=>'Dominican Republic',
    'EC'=>'Ecuador',
    'EG'=>'Egypt',
    'es'=>'El Salvador',
    'ek'=>'Equatorial Guinea',
    'ER'=>'Eritrea',
    'en'=>'Estonia',
    'ET'=>'Ethiopia',
    'FK'=>'Falkland Islands (Malvinas)',
    'FO'=>'Faroe Islands',
    'FJ'=>'Fiji',
    'FI'=>'Finland',
    'FR'=>'France',
    'GF'=>'French Guiana',
    'fp'=>'French Polynesia',
    'fs'=>'French Southern Territories',
    'gb'=>'Gabon',
    'ga'=>'Gambia',
    'gg'=>'Georgia',
    'gm'=>'Germany',
    'GH'=>'Ghana',
    'GI'=>'Gibraltar',
    'GR'=>'Greece',
    'GL'=>'Greenland',
    'Gj'=>'Grenada',
    'GP'=>'Guadeloupe',
    'gq'=>'Guam',
    'GT'=>'Guatemala',
    'gv'=>'Guinea',
    'pu'=>'Guinea-Bissau',
    'GY'=>'Guyana',
    'ha'=>'Haiti',
    'HM'=>'Heard Island / Mcdonald Islands',
    'Vt'=>'Holy See (Vatican City State)',
    'Ho'=>'Honduras',
    'HK'=>'Hong Kong',
    'HU'=>'Hungary',
    'ic'=>'Iceland',
    'IN'=>'India',
    'ID'=>'Indonesia',
    'ir'=>'Iran, Islamic Republic of',
    'Iz'=>'Iraq',
    'ei'=>'Ireland',
    'Is'=>'Israel',
    'IT'=>'Italy',
    'JM'=>'Jamaica',
    'Ja'=>'Japan',
    'JO'=>'Jordan',
    'KZ'=>'Kazakhstan',
    'KE'=>'Kenya',
    'Kr'=>'Kiribati',
    'Ku'=>'Kuwait',
    'KG'=>'Kyrgyzstan',
    'LA'=>"Lao People's Democratic Republic",
    'Lg'=>'Latvia',
    'Le'=>'Lebanon',
    'Lt'=>'Lesotho',
    'Li'=>'Liberia',
    'LY'=>'Libyan Arab Jamahiriya',
    'ls'=>'Liechtenstein',
    'Lh'=>'Lithuania',
    'LU'=>'Luxembourg',
    'mc'=>'Macao',
    'mk'=>'Macedonia',
    'Ma'=>'Madagascar',
    'Mi'=>'Malawi',
    'MY'=>'Malaysia',
    'MV'=>'Maldives',
    'ML'=>'Mali',
    'MT'=>'Malta',
    'rm'=>'Marshall Islands',
    'MQ'=>'Martinique',
    'MR'=>'Mauritania',
    'Mp'=>'Mauritius',
    'mf'=>'Mayotte',
    'MX'=>'Mexico',
    'FM'=>'Micronesia, Federated States of',
    'md'=>'Moldova, Republic of',
    'Mn'=>'Monaco',
    'Mg'=>'Mongolia',
    'Mj'=>'Montserrat',
    'Mo'=>'Morocco',
    'MZ'=>'Mozambique',
    'MM'=>'Myanmar',
    'wa'=>'Namibia',
    'NR'=>'Nauru',
    'NP'=>'Nepal',
    'NL'=>'Netherlands',
    'nl'=>'Netherlands Antilles',
    'NC'=>'New Caledonia',
    'NZ'=>'New Zealand',
    'Nu'=>'Nicaragua',
    'Ng'=>'Niger',
    'Ni'=>'Nigeria',
    'Ne'=>'Niue',
    'NF'=>'Norfolk Island',
    'KP'=>'North Korea',
    'cq'=>'Northern Mariana Islands',
    'NO'=>'Norway',
    'mu'=>'Oman',
    'PK'=>'Pakistan',
    'Ps'=>'Palau',
    'PS'=>'Palestine',
    'Pm'=>'Panama',
    'Pp'=>'Papua New Guinea',
    'Pa'=>'Paraguay',
    'PE'=>'Peru',
    'rp'=>'Philippines',
    'pc'=>'Pitcairn',
    'PL'=>'Poland',
    'Po'=>'Portugal',
    'rq'=>'Puerto Rico',
    'QA'=>'Qatar',
    'RE'=>'Reunion',
    'RO'=>'Romania',
    'Rs'=>'Russian Federation',
    'RW'=>'Rwanda',
    'SH'=>'Saint Helena',
    'sc'=>'Saint Kitts and Nevis',
    'st'=>'Saint Lucia',
    'sb'=>'Saint Pierre and Miquelon',
    'VC'=>'Saint Vincent / Grenadines',
    'WS'=>'Samoa',
    'SM'=>'San Marino',
    'tp'=>'Sao Tome and Principe',
    'SA'=>'Saudi Arabia',
    'sg'=>'Senegal',
    'ri'=>'Serbia and Montenegro',
    'se'=>'Seychelles',
    'SL'=>'Sierra Leone',
    'Sn'=>'Singapore',
    'lo'=>'Slovakia',
    'SI'=>'Slovenia',
    'bp'=>'Solomon Islands',
    'SO'=>'Somalia',
    'sf'=>'South Africa',
    'sx'=>'South Georgia and The South Sandwich Islands',
    'KR'=>'South Korea',
    'sp'=>'Spain',
    'ce'=>'Sri Lanka',
    'su'=>'Sudan',
    'ns'=>'Suriname',
    'sv'=>'Svalbard and Jan Mayen',
    'wz'=>'Swaziland',
    'sw'=>'Sweden',
    'sz'=>'Switzerland',
    'SY'=>'Syrian Arab Republic',
    'TW'=>'Taiwan',
    'TJ'=>'Tajikistan',
    'TZ'=>'Tanzania, United Republic of',
    'TH'=>'Thailand',
    'tt'=>'Timor-Leste',
    'to'=>'Togo',
    'tl'=>'Tokelau',
    'tn'=>'Tonga',
    'td'=>'Trinidad and Tobago',
    'ts'=>'Tunisia',
    'tu'=>'Turkey',
    'tx'=>'Turkmenistan',
    'tk'=>'Turks and Caicos Islands',
    'TV'=>'Tuvalu',
    'UG'=>'Uganda',
    'up'=>'Ukraine',
    'AE'=>'United Arab Emirates',
    'uk'=>'United Kingdom',
    'US'=>'United States',
    'um'=>'United States Minor Outlying Islands',
    'UY'=>'Uruguay',
    'UZ'=>'Uzbekistan',
    'nh'=>'Vanuatu',
    'VE'=>'Venezuela',
    'vm'=>'Viet Nam',
    'vq'=>'Virgin Islands, UK',
    'vq'=>'Virgin Islands, US',
    'WF'=>'Wallis and Futuna',
    'wi'=>'Western Sahara',
    'ym'=>'Yemen',
    'za'=>'Zambia',
    'zi'=>'Zimbabwe',
);

$countryurllist = array_flip($countryurllist); // Flip the URL and CIA.gov code/url

$countrylist = array(
    'AF'=>'Afghanistan',
    'AL'=>'Albania',
    'DZ'=>'Algeria',
    'AS'=>'American Samoa',
    'AD'=>'Andorra',
    'AO'=>'Angola',
    'AI'=>'Anguilla',
    'AQ'=>'Antarctica',
    'AG'=>'Antigua and Barbuda',
    'AR'=>'Argentina',
    'AM'=>'Armenia',
    'AW'=>'Aruba',
    'AU'=>'Australia',
    'AT'=>'Austria',
    'AZ'=>'Azerbaijan',
    'BS'=>'Bahamas',
    'BH'=>'Bahrain',
    'BD'=>'Bangladesh',
    'BB'=>'Barbados',
    'BY'=>'Belarus',
    'BE'=>'Belgium',
    'BZ'=>'Belize',
    'BJ'=>'Benin',
    'BM'=>'Bermuda',
    'BT'=>'Bhutan',
    'BO'=>'Bolivia',
    'BA'=>'Bosnia and Herzegovina',
    'BW'=>'Botswana',
    'BV'=>'Bouvet Island',
    'BR'=>'Brazil',
    'BI'=>'British Indian Ocean Territory',
    'BN'=>'Brunei Darussalam',
    'BG'=>'Bulgaria',
    'BF'=>'Burkina Faso',
    'BI'=>'Burundi',
    'KH'=>'Cambodia',
    'CM'=>'Cameroon',
    'CA'=>'Canada',
    'CV'=>'Cape Verde',
    'KY'=>'Cayman Islands',
    'CF'=>'Central African Republic',
    'TD'=>'Chad',
    'CL'=>'Chile',
    'CN'=>'China',
    'CX'=>'Christmas Island',
    'CC'=>'Cocos (Keeling) Islands',
    'CO'=>'Colombia',
    'KM'=>'Comoros',
    'CG'=>'Congo',
    'CD'=>'Congo (DR)',
    'CK'=>'Cook Islands',
    'CR'=>'Costa Rica',
    'CI'=>'Cote D\'Ivoire',
    'HR'=>'Croatia',
    'CU'=>'Cuba',
    'CY'=>'Cyprus',
    'CZ'=>'Czech Republic',
    'DK'=>'Denmark',
    'DJ'=>'Djibouti',
    'DM'=>'Dominica',
    'DO'=>'Dominican Republic',
    'EC'=>'Ecuador',
    'EG'=>'Egypt',
    'SV'=>'El Salvador',
    'GQ'=>'Equatorial Guinea',
    'ER'=>'Eritrea',
    'EE'=>'Estonia',
    'ET'=>'Ethiopia',
    'FK'=>'Falkland Islands (Malvinas)',
    'FO'=>'Faroe Islands',
    'FJ'=>'Fiji',
    'FI'=>'Finland',
    'FR'=>'France',
    'GF'=>'French Guiana',
    'PF'=>'French Polynesia',
    'TF'=>'French Southern Territories',
    'GA'=>'Gabon',
    'GM'=>'Gambia',
    'GE'=>'Georgia',
    'DE'=>'Germany',
    'GH'=>'Ghana',
    'GI'=>'Gibraltar',
    'GR'=>'Greece',
    'GL'=>'Greenland',
    'GD'=>'Grenada',
    'GP'=>'Guadeloupe',
    'GU'=>'Guam',
    'GT'=>'Guatemala',
    'GN'=>'Guinea',
    'GW'=>'Guinea-Bissau',
    'GY'=>'Guyana',
    'HT'=>'Haiti',
    'HM'=>'Heard Island / Mcdonald Islands',
    'VA'=>'Holy See (Vatican City State)',
    'HN'=>'Honduras',
    'HK'=>'Hong Kong',
    'HU'=>'Hungary',
    'IS'=>'Iceland',
    'IN'=>'India',
    'ID'=>'Indonesia',
    'IR'=>'Iran, Islamic Republic of',
    'IQ'=>'Iraq',
    'IE'=>'Ireland',
    'IL'=>'Israel',
    'IT'=>'Italy',
    'JM'=>'Jamaica',
    'JP'=>'Japan',
    'JO'=>'Jordan',
    'KZ'=>'Kazakhstan',
    'KE'=>'Kenya',
    'KI'=>'Kiribati',
    'KW'=>'Kuwait',
    'KG'=>'Kyrgyzstan',
    'LA'=>"Lao People's Democratic Republic",
    'LV'=>'Latvia',
    'LB'=>'Lebanon',
    'LS'=>'Lesotho',
    'LR'=>'Liberia',
    'LY'=>'Libyan Arab Jamahiriya',
    'LI'=>'Liechtenstein',
    'LT'=>'Lithuania',
    'LU'=>'Luxembourg',
    'MO'=>'Macao',
    'MK'=>'Macedonia',
    'MG'=>'Madagascar',
    'MW'=>'Malawi',
    'MY'=>'Malaysia',
    'MV'=>'Maldives',
    'ML'=>'Mali',
    'MT'=>'Malta',
    'MH'=>'Marshall Islands',
    'MQ'=>'Martinique',
    'MR'=>'Mauritania',
    'MU'=>'Mauritius',
    'YT'=>'Mayotte',
    'MX'=>'Mexico',
    'FM'=>'Micronesia, Federated States of',
    'MD'=>'Moldova, Republic of',
    'MC'=>'Monaco',
    'MN'=>'Mongolia',
    'MS'=>'Montserrat',
    'MA'=>'Morocco',
    'MZ'=>'Mozambique',
    'MM'=>'Myanmar',
    'NA'=>'Namibia',
    'NR'=>'Nauru',
    'NP'=>'Nepal',
    'NL'=>'Netherlands',
    'AN'=>'Netherlands Antilles',
    'NC'=>'New Caledonia',
    'NZ'=>'New Zealand',
    'NI'=>'Nicaragua',
    'NE'=>'Niger',
    'NG'=>'Nigeria',
    'NU'=>'Niue',
    'NF'=>'Norfolk Island',
    'KP'=>'North Korea',
    'MP'=>'Northern Mariana Islands',
    'NO'=>'Norway',
    'OM'=>'Oman',
    'PK'=>'Pakistan',
    'PW'=>'Palau',
    'PS'=>'Palestine',
    'PA'=>'Panama',
    'PG'=>'Papua New Guinea',
    'PY'=>'Paraguay',
    'PE'=>'Peru',
    'PH'=>'Philippines',
    'PN'=>'Pitcairn',
    'PL'=>'Poland',
    'PT'=>'Portugal',
    'PR'=>'Puerto Rico',
    'QA'=>'Qatar',
    'RE'=>'Reunion',
    'RO'=>'Romania',
    'RU'=>'Russian Federation',
    'RW'=>'Rwanda',
    'SH'=>'Saint Helena',
    'KN'=>'Saint Kitts and Nevis',
    'LC'=>'Saint Lucia',
    'PM'=>'Saint Pierre and Miquelon',
    'VC'=>'Saint Vincent / Grenadines',
    'WS'=>'Samoa',
    'SM'=>'San Marino',
    'ST'=>'Sao Tome and Principe',
    'SA'=>'Saudi Arabia',
    'SN'=>'Senegal',
    'CS'=>'Serbia and Montenegro',
    'SC'=>'Seychelles',
    'SL'=>'Sierra Leone',
    'SG'=>'Singapore',
    'SK'=>'Slovakia',
    'SI'=>'Slovenia',
    'SB'=>'Solomon Islands',
    'SO'=>'Somalia',
    'ZA'=>'South Africa',
    'GS'=>'South Georgia and The South Sandwich Islands',
    'KR'=>'South Korea',
    'ES'=>'Spain',
    'LK'=>'Sri Lanka',
    'SD'=>'Sudan',
    'SR'=>'Suriname',
    'SJ'=>'Svalbard and Jan Mayen',
    'SZ'=>'Swaziland',
    'SE'=>'Sweden',
    'CH'=>'Switzerland',
    'SY'=>'Syrian Arab Republic',
    'TW'=>'Taiwan',
    'TJ'=>'Tajikistan',
    'TZ'=>'Tanzania, United Republic of',
    'TH'=>'Thailand',
    'TL'=>'Timor-Leste',
    'TG'=>'Togo',
    'TK'=>'Tokelau',
    'TO'=>'Tonga',
    'TT'=>'Trinidad and Tobago',
    'TN'=>'Tunisia',
    'TR'=>'Turkey',
    'TM'=>'Turkmenistan',
    'TC'=>'Turks and Caicos Islands',
    'TV'=>'Tuvalu',
    'UG'=>'Uganda',
    'UA'=>'Ukraine',
    'AE'=>'United Arab Emirates',
    'GB'=>'United Kingdom',
    'US'=>'United States',
    'PU'=>'United States Minor Outlying Islands',
    'UY'=>'Uruguay',
    'UZ'=>'Uzbekistan',
    'VU'=>'Vanuatu',
    'VE'=>'Venezuela',
    'VN'=>'Viet Nam',
    'VG'=>'Virgin Islands, UK',
    'VI'=>'Virgin Islands, US',
    'WF'=>'Wallis and Futuna',
    'EH'=>'Western Sahara',
    'YE'=>'Yemen',
    'ZM'=>'Zambia',
    'ZW'=>'Zimbabwe'
);

$smslist = array(
    'US'=>array(    /* United States */
        '3 River Wireless'=>'{n}@sms.3rivers.net',
        '7-11 Speakout'=>'{n}@cingularme.com',
        'Advantage Communications'=>'{n}@advantagepaging.com',
        'Airtouch Pagers'=>'{n}@airtouch.net',
        'Airtouch Pagers'=>'{n}@airtouch.net',
        'Airtouch Pagers'=>'{n}@airtouchpaging.com',
        'Airtouch Pagers'=>'{n}@alphapage.airtouch.com',
        'Airtouch Pagers'=>'{n}@myairmail.com',
        'AllTel'=>'{n}@message.alltel.com',
        'Alltel PCS'=>'{n}@message.alltel.com',
        'Alltel'=>'{n}@alltelmessage.com',
        'AirVoice'=>'{n}@mmode.com',
        'Ameritech'=>'{n}@pagin.acswireless.com',
        'Aql'=>'{n}@text.aql.com',
        'Arch Pagers (PageNet)'=>'{n}@archwireless.net',
        'Arch Pagers (PageNet)'=>'{n}@epage.arch.com',
        'AT&T Wireless'=>'{n}@txt.att.net',
        'Bell South'=>'{n}@blsdcs.net',
        'Bell South Blackberry'=>'{n}@bellsouthtips.com',
        'Bell South Mobility'=>'{n}@blsdcs.net',
        'Bell South SMS'=>'{n}@sms.bellsouth.com',
        'Bell South Wireless'=>'{n}@wireless.bellsouth.com',
        'Bluegrass Cellular'=>'{n}@sms.bluecell.com',
        'Boost Mobile'=>'{n}@myboostmobile.com',
        'Boost'=>'{n}@myboostmobile.com',
        'CallPlus'=>'{n}@mmode.com',
        'Carolina Mobile Communications'=>'{n}@cmcpaging.com',
        'Cellular One'=>'{n}@message.cellone-sf.com',
        'Cellular One East Coast'=>'{n}@phone.cellone.net',
        'Cellular One Mobile'=>'{n}@mobile.celloneusa.com',
        'Cellular One PCS'=>'{n}@paging.cellone-sf.com',
        'Cellular One SBC'=>'{n}@sbcemail.com',
        'Cellular One South West'=>'{n}@swmsg.com',
        'Cellular One West'=>'{n}@mycellone.com',
        'Cellular South'=>'{n}@csouth1.com',
        'Central Vermont Communications'=>'{n}@cvcpaging.com',
        'CenturyTel'=>'{n}@messaging.centurytel.net',
        'Cingular (GSM)'=>'{n}@cingularme.com',
        'Cingular (TDMA)'=>'{n}@mmode.com',
        'Cingular Wireless'=>'{n}@mobile.mycingular.net',
        'Cingular'=>'{n}@cingularme.com',
        'Communication Specialists'=>'{n}@pageme.comspeco.net',
        'Cook Paging'=>'{n}@cookmail.com',
        'Corr Wireless Communications'=>'{n}@corrwireless.net',
        'Cricket'=>'{n}@sms.mycricket.com',
        'Dobson Communications Corporation'=>'{n}@mobile.dobson.net',
        'Dobson-Alex Wireless / Dobson-Cellular One'=>'{n}@mobile.cellularone.com',
        'Edge Wireless'=>'{n}@sms.edgewireless.com',
        'Galaxy Corporation'=>'{n}@sendabeep.net',
        'GCS Paging'=>'{n}@webpager.us',
        'Globalstart'=>'{n}@msg.globalstarusa.com',
        'GTE'=>'{n}@gte.pagegate.net',
        'GTE'=>'{n}@messagealert.com',
        'GrayLink / Porta-Phone'=>'{n}@epage.porta-phone.com',
        'Houston Cellular'=>'{n}@text.houstoncellular.net',
        'Illinois Valley Cellular'=>'{n}@ivctext.com',
        'Inland Cellular Telephone'=>'{n}@inlandlink.com',
        'Iridium'=>'{n}@msg.iridium.com',
        'JSM Tele-Page'=>'{n}@jsmtel.com',
        'Lauttamus Communication'=>'{n}@e-page.net',
        'MCI Phone'=>'{n}@mci.com',
        'MCI'=>'{n}@pagemci.com',
        'Metro PCS'=>'{n}@mymetropcs.com',
        'Metrocall'=>'{n}@page.metrocall.com',
        'Metrocall 2-way'=>'{n}@my2way.com',
        'Midwest Wireless'=>'{n}@clearlydigital.com',
        'Mobilecom PA'=>'{n}@page.mobilcom.net',
        'Mobilfone'=>'{n}@page.mobilfone.com',
        'MobiPCS'=>'{n}@mobipcs.net',
        'Morris Wireless'=>'{n}@beepone.net',
        'Nextel'=>'{n}@messaging.nextel.com',
        'NPI Wireless'=>'{n}@npiwireless.com',
        'Ntelos'=>'{n}@pcs.ntelos.com',
        'O2'=>'{n}@mobile.celloneusa.com',
        'Omnipoint'=>'{n}@omnipoint.com',
        'Omnipoint'=>'{n}@omnipointpcs.com',
        'OnlineBeep'=>'{n}@onlinebeep.net',
        'Orange'=>'{n}@mobile.celloneusa.com',
        'PCS One'=>'{n}@pcsone.net',
        'Pacific Bell'=>'{n}@pacbellpcs.net',
        'PageMart'=>'{n}@pagemart.net',
        'PageOne NorthWest'=>'{n}@page1nw.com',
        'Pioneer / Enid Cellular'=>'{n}@msg.pioneerenidcellular.com',
        'Price Communications'=>'{n}@mobilecell1se.com',
        'ProPage'=>'{n}@page.propage.net',
        'Public Service Cellular'=>'{n}@sms.pscel.com',
        'Qualcomm'=>'name@pager.qualcomm.com',
        'Qwest'=>'{n}@qwestmp.com',
        'RAM Page'=>'{n}@ram-page.com',
        'Rogers Wireless'=>'{n}@pcs.rogers.com',
        'Safaricom'=>'{n}@safaricomsms.com',
        'Satelindo GSM'=>'{n}@satelindogsm.com',
        'Satellink'=>'{n}.pageme@satellink.net',
        'Simple Freedom'=>'{n}@text.simplefreedom.net',
        'Skytel Pagers'=>'{n}@email.skytel.com',
        'Skytel Pagers'=>'{n}@skytel.com',
        'Smart Telecom'=>'{n}@mysmart.mymobile.ph',
        'Southern LINC'=>'{n}@page.southernlinc.com',
        'Southwestern Bell'=>'{n}@email.swbw.com',
        'Sprint PCS'=>'{n}@messaging.sprintpcs.com',
        'ST Paging'=>'pin@page.stpaging.com',
        'SunCom'=>'{n}@tms.suncom.com',
        'Surewest Communications'=>'{n}@mobile.surewest.com',
        'T-Mobile'=>'{n}@tmomail.net',
        'Teleflip'=>'{n}@teleflip.com',
        'Teletouch'=>'{n}@pageme.teletouch.com',
        'Telus'=>'{n}@msg.telus.com',
        'The Indiana Paging Co'=>'{n}@pager.tdspager.com',
        'Tracfone'=>'{n}@mmst5.tracfone.com',
        'Triton'=>'{n}@tms.suncom.com',
        'TIM'=>'{n}@timnet.com',
        'TSR Wireless'=>'{n}@alphame.com',
        'TSR Wireless'=>'{n}@beep.com',
        'US Cellular'=>'{n}@email.uscc.net',
        'USA Mobility'=>'{n}@mobilecomm.net',
        'Unicel'=>'{n}@utext.com',
        'Verizon'=>'{n}@vtext.com',
        'Verizon PCS'=>'{n}@myvzw.com',
        'Verizon Pagers'=>'{n}@myairmail.com',
        'Virgin Mobile'=>'{n}@vmobl.com',
        'WebLink Wireless'=>'{n}@pagemart.net',
        'West Central Wireless'=>'{n}@sms.wcc.net',
        'Western Wireless'=>'{n}@cellularonewest.com',
        'Wyndtell'=>'{n}@wyndtell.com',
        ),
    'AR'=>array(    /* Argentina */
        'CTI'=>'{n}@sms.ctimovil.com.ar',
        'Movicom'=>'{n}@sms.movistar.net.ar',
        'Nextel'=>'TwoWay.11{n}@nextel.net.ar',
        'Personal'=>'{n}@alertas.personal.com.ar',
        ),
    'AW'=>array(    /* Aruba */
        'Setar Mobile'=>'297+{n}@mas.aw',
        ),
    'AU'=>array(    /* Australia */
        'Blue Sky Frog'=>'{n}@blueskyfrog.com',
        'Optus Mobile'=>'0{n}@optusmobile.com.au',
        'SL Interactive'=>'{n}@slinteractive.com.au',
        ),
    'AT'=>array(    /* Austria */
        'MaxMobil'=>'{n}x@max.mail.at',
        'One Connect'=>'{n}@onemail.at',
        'Provider'=>'E-mail to SMS address format',
        'T-Mobile'=>'43676{n}@sms.t-mobile.at',
        ),
    'BE'=>array(    /* Belgium */
        'Mobistar'=>'{n}@mobistar.be',
        ),
    'BM'=>array(    /* Bermuda */
        'Mobility'=>'{n}@ml.bm',
        ),
    'BR'=>array(    /* Brazil */
        'Claro'=>'{n}@clarotorpedo.com.br',
        'Nextel'=>'{n}@nextel.com.br',
        ),
    'BG'=>array(    /* Bulgaria */
        'Globul'=>'{n}@sms.globul.bg',
        'Mtel'=>'{n}@sms.mtel.net',
        ),
    'CA'=>array(    /* Canada */
        'Aliant'=>'{n}@wirefree.informe.ca',
        'Bell Mobility'=>'{n}@txt.bellmobility.ca',
        'Fido'=>'{n}@fido.ca',
        'Koodo Mobile'=>'{n}@msg.koodomobile.com',
        'Microcell'=>'{n}@fido.ca',
        'MTS Mobility'=>'{n}@text.mtsmobility.com',
        'NBTel'=>'{n}@wirefree.informe.ca',
        'PageMart'=>'{n}@pmcl.net',
        'PageNet'=>'{n}@pagegate.pagenet.ca',
        'Presidents Choice'=>'{n}@mobiletxt.ca',
        'Rogers Wireless'=>'{n}@pcs.rogers.com',
        'Sasktel Mobility'=>'{n}@pcs.sasktelmobility.com',
        'Telus'=>'{n}@msg.telus.com',
        'Virgin Mobile'=>'{n}@vmobile.ca',
        ),
    'CL'=>array(    /* Chile */
        'Bell South'=>'{n}@bellsouth.cl',
        ),
    'CO'=>array(    /* Columbia */
        'Comcel'=>'{n}@comcel.com.co',
        'Moviastar'=>'{n}@movistar.com.co',
        ),
    'CZ'=>array(    /* Czech Republic */
        'Eurotel'=>'+ccaa@sms.eurotel.cz',
        'Oskar'=>'{n}@mujoskar.cz',
        ),
    'DK'=>array(    /* Denmark */
        'Sonofon'=>'{n}@note.sonofon.dk',
        'Tele Danmark Mobil'=>'{n}@sms.tdk.dk',
        'Telia Denmark'=>'{n}@gsm1800.telia.dk',
        ),
    'EE'=>array(    /* Estonia */
        'EMT'=>'{n}@sms.emt.ee',
        ),
    'FR'=>array(    /* France */
        'SFR'=>'{n}@sfr.fr',
        ),
    'DE'=>array(    /* Germany */
        'E-Plus'=>'0{n}.sms@eplus.de',
        'Mannesmann Mobilefunk'=>'0{n}@d2-message.de',
        'O2'=>'0{n}@o2online.de',
        'T-Mobile'=>'0{n}@t-d1-sms.de',
        'Vodafone'=>'0{n}@vodafone-sms.de',
        ),
    'HU'=>array(    /* Hungary */
        'PGSM'=>'3620{n}@sms.pgsm.hu',
        ),
    'IS'=>array(    /* Iceland */
        'OgVodafone'=>'{n}@sms.is',
        'Siminn'=>'{n}@box.is',
        ),
    'IN'=>array(    /* India */
        'Andhra Pradesh AirTel'=>'91{n}@airtelap.com',
        'Andhra Pradesh Idea Cellular'=>'9848{n}@ideacellular.net',
        'BPL mobile'=>'{n}@bplmobile.com',
        'Chennai Skycell / Airtel'=>'919840{n}@airtelchennai.com',
        'Chennai RPG Cellular'=>'9841{n}@rpgmail.net',
        'Delhi Airtel'=>'919810{n}@airtelmail.com',
        'Delhi Hutch'=>'9811{n}@delhi.hutch.co.in',
        'Gujarat Idea Cellular'=>'9824{n}@ideacellular.net',
        'Gujarat Airtel'=>'919898{n}@airtelmail.com',
        'Gujarat Celforce / Fascel'=>'9825{n}@celforce.com',
        'Goa Airtel'=>'919890{n}@airtelmail.com',
        'Goa BPL Mobile'=>'9823{n}@bplmobile.com',
        'Goa Idea Cellular'=>'9822{n}@ideacellular.net',
        'Haryana Airtel'=>'919896{n}@airtelmail.com',
        'Haryana Escotel'=>'9812{n}@escotelmobile.com',
        'Himachal Pradesh Airtel'=>'919816{n}@airtelmail.com',
        'Idea Cellular'=>'{n}@ideacellular.net',
        'Karnataka Airtel'=>'919845{n}@airtelkk.com',
        'Kerala Airtel'=>'919895{n}@airtelkerala.com',
        'Kerala Escotel'=>'9847{n}@escotelmobile.com',
        'Kerala BPL Mobile'=>'9846{n}@bplmobile.com',
        'Kolkata Airtel'=>'919831{n}@airtelkol.com',
        'Madhya Pradesh Airtel'=>'919893{n}@airtelmail.com',
        'Maharashtra Airtel'=>'919890{n}@airtelmail.com',
        'Maharashtra BPL Mobile'=>'9823{n}@bplmobile.com',
        'Maharashtra Idea Cellular'=>'9822{n}@ideacellular.net',
        'Mumbai Airtel'=>'919892{n}@airtelmail.com',
        'Mumbai BPL Mobile'=>'9821{n}@bplmobile.com',
        'Orange'=>'{n}@orangemail.co.in',
        'Punjab Airtel'=>'919815{n}@airtelmail.com',
        'Pondicherry BPL Mobile'=>'9843{n}@bplmobile.com',
        'Tamil Nadu Airtel'=>'919894{n}@airtelmail.com',
        'Tamil Nadu BPL Mobile'=>'919843{n}@bplmobile.com',
        'Tamil Nadu Aircel'=>'9842{n}@airsms.com',
        'Uttar Pradesh West Escotel'=>'9837{n}@escotelmobile.com',
        ),
    'IE'=>array(    /* Ireland */
        'Meteor'=>'{n}@sms.mymeteor.ie',
        'Meteor MMS'=>'{n}@mms.mymeteor.ie',
        ),
    'IT'=>array(    /* Italy */
        'Telecom Italia Mobile'=>'33{n}@posta.tim.it',
        'Vodafone'=>'{n}@sms.vodafone.it',
        'Vodafone Omnitel'=>'34{n}@vizzavi.it',
        ),
    'JP'=>array(    /* Japan */
        'AU by KDDI'=>'{n}@ezweb.ne.jp',
        'NTT DoCoMo'=>'{n}@docomo.ne.jp',
        'Vodafone Chuugoku/Western'=>'{n}@n.vodafone.ne.jp',
        'Vodafone Hokkaido'=>'{n}@d.vodafone.ne.jp',
        'Vodafone Hokuriko/Central North'=>'{n}@r.vodafone.ne.jp',
        'Vodafone Kansai/West, including Osaka'=>'{n}@k.vodafone.ne.jp',
        'Vodafone Kanto/Koushin/East, including Tokyo'=>'{n}@t.vodafone.ne.jp',
        'Vodafone Kyuushu/Okinawa'=>'{n}@q.vodafone.ne.jp',
        'Vodafone Shikoku'=>'{n}@s.vodafone.ne.jp',
        'Vodafone Touhoku/Niigata/North'=>'{n}@h.vodafone.ne.jp',
        'Vodafone Toukai/Central'=>'{n}@c.vodafone.ne.jp',
        'Willcom'=>'{n}@pdx.ne.jp',
        'Willcom di'=>'{n}@di.pdx.ne.jp',
        'Willcom dj'=>'{n}@dj.pdx.ne.jp',
        'Willcom dk'=>'{n}@dk.pdx.ne.jp',
        ),
    'LV'=>array(    /* Latvia */
        'Kyivstar'=>'{n}@smsmail.lmt.lv',
        'LMT'=>'9{n}@smsmail.lmt.lv',
        'Tele2'=>'{n}@sms.tele2.lv',
        ),
    'LB'=>array(    /* Lebanon */
        'Cellis / LibanCell'=>'9613{n}@ens.jinny.com.lb',
        ),
    'LU'=>array(    /* Luxembourg */
        'P&amp;T Luxembourg'=>'{n}@sms.luxgsm.lu',
        ),
    'MY'=>array(    /* Malaysia */
        'Celcom'=>'019{n}@sms.celcom.com.my',
        ),
    'MU'=>array(    /* Mauritius */
        'Emtel'=>'{n}@emtelworld.net',
        ),
    'MX'=>array(    /* Mexico */
        'Iusacell'=>'{n}@rek2.com.mx',
        ),
    'NI'=>array(    /* Nicaragua */
        'Claro'=>'{n}@ideasclaro-ca.com',
        ),
    'NP'=>array(    /* Nepal */
        'Mero Mobile'=>'{n}@sms.spicenepal.com',
        ),
    'NL'=>array(    /* Netherlands */
        'Dutchtone / Orange-NL'=>'{n}@sms.orange.nl',
        'T-Mobile'=>'31{n}@gin.nl',
        ),
    'NO'=>array(    /* Norway */
        'Netcom'=>'{n}@sms.netcom.no',
        'Telenor'=>'{n}@mobilpost.no',
        ),
    'PA'=>array(    /* Panama */
        'Cable and Wireless'=>'{n}@cwmovil.com',
        ),
    'PL'=>array(    /* Poland */
        'Orange Polska'=>'{n}@orange.pl',
        'Plus GSM'=>'+4860{n}@text.plusgsm.pl',
        ),
    'PT'=>array(    /* Portugal */
        'Telcel'=>'91{n}@sms.telecel.pt',
        'Optimus'=>'93{n}@sms.optimus.pt',
        'TMN'=>'96{n}@mail.tmn.pt',
        ),
    'RU'=>array(    /* Russia */
        'BeeLine GSM'=>'{n}@sms.beemail.ru',
        'MTS'=>'7{n}x@sms.mts.ru',
        'Personal Communication'=>'sms@pcom.ru (number in subject line)',
        'Primtel'=>'{n}@sms.primtel.ru',
        'SCS-900'=>'{n}@scs-900.ru',
        'Uraltel'=>'{n}@sms.uraltel.ru',
        'Vessotel'=>'{n}@pager.irkutsk.ru',
        'YCC'=>'{n}@sms.ycc.ru',
        ),
    'CS'=>array(    /* Serbia and Montenegro */
        'Mobtel Srbija'=>'{n}@mobtel.co.yu',
        ),
    'SG'=>array(    /* Singapore */
        'M1'=>'{n}@m1.com.sg',
        ),
    'SI'=>array(    /* Slovenia */
        'Mobitel'=>'{n}@linux.mobitel.si',
        'Si Mobil'=>'{n}@simobil.net',
        ),
    'ZA'=>array(    /* South Africa */
        'MTN'=>'{n}@sms.co.za',
        'Vodacom'=>'{n}@voda.co.za',
        ),
    'ES'=>array(    /* Spain */
        'Telefonica Movistar'=>'{n}@movistar.net',
        'Vodafone'=>'{n}@vodafone.es',
        ),
    'LK'=>array(    /* Sri Lanka */
        'Mobitel'=>'{n}@sms.mobitel.lk',
        ),
    'SE'=>array(    /* Sweden */
        'Comviq GSM'=>'467{n}@sms.comviq.se',
        'Europolitan'=>'4670{n}@europolitan.se',
        'Tele2'=>'0{n}@sms.tele2.se',
        ),
    'CH'=>array(    /* Switzerland */
        'Sunrise Mobile'=>'{n}@freesurf.ch',
        'Sunrise Mobile'=>'{n}@mysunrise.ch',
        'Swisscom'=>'{n}@bluewin.ch',
        ),
    'TZ'=>array(    /* Tanzania */
        'Mobitel'=>'{n}@sms.co.tz',
        ),
    'UA'=>array(    /* Ukraine */
        'Golden Telecom'=>'{n}@sms.goldentele.com',
        'Kyivstar'=>'{n}x@2sms.kyivstar.net',
        'UMC'=>'{n}@sms.umc.com.ua',
        ),
    'GB'=>array(    /* United Kingdom */
        'BigRedGiant Mobile'=>'{n}@tachyonsms.co.uk',
        'O2'=>'44{n}@mobile.celloneusa.com',
        'O2 (M-mail)'=>'44{n}@mmail.co.uk',
        'Orange'=>'0{n}@orange.net',
        'T-Mobile'=>'0{n}@t-mobile.uk.net',
        'Virgin Mobile'=>'0{n}@vmobl.com',
        'Vodafone'=>'0{n}@vodafone.net',
    ),
);

$timezoneTable = array(
    "-1200" => "(GMT -12:00) Eniwetok, Kwajalein",
    "-1100" => "(GMT -11:00) Midway Island, Samoa",
    "-1000" => "(GMT -10:00) Hawaii",
    "-0900" => "(GMT -9:00) Alaska",
    "-0800" => "(GMT -8:00) Pacific Time (US & Canada)",
    "-0700" => "(GMT -7:00) Mountain Time (US & Canada)",
    "-0600" => "(GMT -6:00) Central Time (US & Canada), Mexico City",
    "-0500" => "(GMT -5:00) Eastern Time (US & Canada), Bogota, Lima",
    "-0400" => "(GMT -4:00) Atlantic Time (Canada), Caracas, La Paz",
    "-0330" => "(GMT -3:30) Newfoundland",
    "-0300" => "(GMT -3:00) Brazil, Buenos Aires, Georgetown",
    "-0200" => "(GMT -2:00) Mid-Atlantic",
    "-0100" => "(GMT -1:00 hour) Azores, Cape Verde Islands",
    "+0000" => "(GMT) Western Europe Time, London, Lisbon, Casablanca",
    "+0100" => "(GMT +1:00 hour) Brussels, Copenhagen, Madrid, Paris",
    "+0200" => "(GMT +2:00) Kaliningrad, South Africa",
    "+0300" => "(GMT +3:00) Baghdad, Riyadh, Moscow, St. Petersburg",
    "+0330" => "(GMT +3:30) Tehran",
    "+0400" => "(GMT +4:00) Abu Dhabi, Muscat, Baku, Tbilisi",
    "+0430" => "(GMT +4:30) Kabul",
    "+0500" => "(GMT +5:00) Ekaterinburg, Islamabad, Karachi, Tashkent",
    "+0530" => "(GMT +5:30) Bombay, Calcutta, Madras, New Delhi",
    "+0600" => "(GMT +6:00) Almaty, Dhaka, Colombo",
    "+0700" => "(GMT +7:00) Bangkok, Hanoi, Jakarta",
    "+0800" => "(GMT +8:00) Beijing, Perth, Singapore, Hong Kong",
    "+0900" => "(GMT +9:00) Tokyo, Seoul, Osaka, Sapporo, Yakutsk",
    "+0930" => "(GMT +9:30) Adelaide, Darwin",
    "+1000" => "(GMT +10:00) Eastern Australia, Guam, Vladivostok",
    "+1100" => "(GMT +11:00) Magadan, Solomon Islands, New Caledonia",
    "+1200" => "(GMT +12:00) Auckland, Wellington, Fiji, Kamchatka"
);

defineOnce('DEFAULT_TIMEZONE', 'UTC');

set_include_path(get_include_path() . PATH_SEPARATOR . dirname(__FILE__));
date_default_timezone_set(DEFAULT_TIMEZONE);

defineOnce('GITHUB_API_URL', 'https://api.github.com/');
defineOnce('GITHUB_ORGANIZATION', 'dev-worklist-org');

defineOnce('GITHUB_OAUTH2_CLIENT_ID', '495b642ee85e89184f2a');
defineOnce('GITHUB_OAUTH2_CLIENT_SECRET', 'acb111f00815cdff51aa6f4ac8a41bafcafa9d67');

defineOnce('GITHUB_AUTHORIZE_URL', 'https://github.com/login/oauth/authorize');
defineOnce('GITHUB_TOKEN_URL', 'https://github.com/login/oauth/access_token');

defineOnce('MODELS_DIR', dirname(__FILE__) . '/models');
defineOnce('VIEWS_DIR', dirname(__FILE__) . '/views');
defineOnce('CONTROLLERS_DIR', dirname(__FILE__) . '/controllers');
defineOnce('MUSTACHE_DIR', VIEWS_DIR . '/mustache');
defineOnce('TEMP_DIR', dirname(__FILE__) . '/tmp');
defineOnce('CACHE_DIR', TEMP_DIR . '/cache');

defineOnce('DEFAULT_CONTROLLER_NAME', 'Home');
defineOnce('DEFAULT_CONTROLLER_METHOD', 'run');

defineOnce('PAYPAL_ENVIRONMENT', 'sandbox'); // 'sandbox' or 'beta-sandbox' or 'live'

// hifi,stack-manager,hifi-docs,hifi-website,worklist,data,base8,baseios,DSDNS,love
defineOnce('PROJECT_LISTING_PRIORITY', '181,231,230,215,99,229,211,217,218,80');

require_once('lib/Core.php');
