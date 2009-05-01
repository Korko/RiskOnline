<?php

/**
 * @package ifips.framework
 */

if( !defined('_INDEX') ) {
	header('HTTP/1.1 403 Forbidden');
	exit;
}

// Database
define('_DBMS_PREFIX', 'mvc_');
define('_DBMS_HOST', 'localhost');
define('_DBMS_LOGIN','root');
define('_DBMS_PASS','');
define('_DBMS_DB','mvc');
define('_DBMS_SYS', 'MySQL_DBMS');

// Default
define('_DEFAULT_ACTION', 'index');
define('_DEFAULT_LANG', 'fr');

define('_SITE_TITLE', 'RiskOnline');

// Cookie
define('_COOKIE_NAME', 'RiskOnline');

// Session
define('_SESSION_LIFETIME', 3600);

// Mode DEBUG
define('_DEBUG', TRUE);

// Chatbox
define('_LIMIT_LAST_CHATBOX', 10);

// URL Rewriting (.php?action= => .html)
define('_REWRITE_MODE', TRUE);












/********************************
 **** Don't touch under this ****
 *********************************/

// Dirs (constants)
define('_LIB_DIR', './class/');
define('_LANG_DIR', './lang/');
define('_LOG_DIR', './error/');
define('_CONTROLLER_DIR', './controller/');
define('_VIEW_DIR', './view/');
define('_MODEL_DIR', './model/');
define('_DBMS_DIR', './class/DBMS/');

// Libraries
$_GLOBALS['libraries'][] = 'Exception.php';
$_GLOBALS['libraries'][] = 'Singleton.php';
$_GLOBALS['libraries'][] = 'Factory.php';
$_GLOBALS['libraries'][] = 'Model.php';
$_GLOBALS['libraries'][] = 'View.php';
$_GLOBALS['libraries'][] = 'Controller.php';
$_GLOBALS['libraries'][] = 'IDBMS.php';
$_GLOBALS['libraries'][] = 'Tools.php';
$_GLOBALS['libraries'][] = 'Record.php';
$_GLOBALS['libraries'][] = 'Form.php';
$_GLOBALS['libraries'][] = 'Benchmark.php';

// Risk Librairies
$_GLOBALS['libraries'][] = 'Game.php';

// Class Path
define('_PATH', _LIB_DIR.':'._CONTROLLER_DIR.':'._MODEL_DIR.':'._DBMS_DIR);

// Id
define('_ID_VISITOR', 1);

// Auth
define('_AUTH_VISITOR', 1);
define('_AUTH_MEMBER', 2);
define('_AUTH_ADMIN', 3);

// Privacies
// <0 => ONLY those with _AUTH == |_PRIVACY|
// >0 => ALL those with _AUTH >= _PRIVACY
define('_PRIVACY_VISITOR_ONLY', -1);
define('_PRIVACY_ALL', 1);
define('_PRIVACY_MEMBER', 2);
define('_PRIVACY_ADMIN', 3);
