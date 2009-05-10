<?php

/**
 * @package ifips.framework
 */

// Control access FROM index ONLY
define('_INDEX', true);

// Require configuration
require('config.php');

// Require libraries
for($i=0; $i<count($GLOBALS['libraries']); $i++)
{
	if( !file_exists(_LIB_DIR . $GLOBALS['libraries'][$i]) )
	{
		throw new FileNotFoundException('Library '.$GLOBALS['libraries'][$i]);
	}
	require(_LIB_DIR . $GLOBALS['libraries'][$i]);
}

// Activate Assertions if mode DEBUG
assert_options(ASSERT_ACTIVE, _DEBUG);
//assert_options(ASSERT_QUIET_EVAL, TRUE);
assert_options(ASSERT_BAIL, TRUE);

date_default_timezone_set('Europe/Paris');
set_error_handler('Tools::handlerError');
assert_options(ASSERT_CALLBACK, 'Tools::handlerAsserts');

Tools::clean_gpc();

if( _DEBUG )
	F::i('Benchmark')->addStep('init');
	
try
{
	if( !_DEBUG )
		ob_start();
	
	// What the visitor want to do ?
	$action = (isset($_GET['action']) && !empty($_GET['action'])) ? $_GET['action'] : _DEFAULT_ACTION;

	// Initiate Cookies
	Factory::getInstance('Cookie', _COOKIE_NAME);
	
	// Initiate Session
	Factory::getInstance('Session');
	
	// Initiate Lang
	Factory::getInstance('Lang');

	// What Params ? Priority to the requested
	$params = $_GET + $_POST;

	try
	{
		// Control !
		$output = Controller::check($action, $params);
	}
	catch(FileNotFoundException $e)
	{
		// 404
		header('HTTP/1.1 404 File Not Found');
		exit;
	}
	
	F::i('Session')->close();
	
	$output = Tools::parseOutput($output);
	
	if( !_DEBUG )
		ob_end_clean();
}
catch(DatabaseException $e)
{
	Record::note($e->__toString());
	die('DB Error : '.$e->getMessage());
}
catch(SessionException $e)
{
	// Hacking attempt
	Record::note($e->__toString());
	die('Hacking Attempt : '.$e->getMessage());
}
catch(ControllerException $e)
{
	// Bad Access
	Record::note($e->__toString());
	die('Bad Access : '.$e->getMessage());
}
catch(ModelException $e)
{
	// Error while generating
	Record::note($e->__toString());
	die('Error while generating : '.$e->getMessage());
}
catch(ViewException $e)
{
	// Error while displaying
	Record::note($e->__toString());
	die('Error while displaying : '.$e->getMessage());
}
catch(Exception $e)
{
	// Unexpected Exception so STOP !
	trigger_error('Unexcepted Exception : '.$e->__toString(), E_USER_ERROR);
}

echo($output);
