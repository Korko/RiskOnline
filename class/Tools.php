<?php

if( !defined('_INDEX') ) {
	header('HTTP/1.1 403 Forbidden');
	exit;
}

/**
 * Tools Class of the System
 *
 * @author Jeremy 'Korko' Lemesle <jeremy.lemesle@korko.fr>
 * @package ifips.framework.tools
 */
class Tools
{
	// Display error if nothing possible to log, prevent etc
	public static $display = TRUE;

	public static function getCallingClass($i=2)
	{
		assert('$i>=1');

		$backtrace = debug_backtrace();
		$classname = $backtrace[$i]["class"];

		return $classname;
	}

	public static function passHash($login, $password)
	{
		$result = F::i(_DBMS_SYS)->query('SELECT m_salt FROM '._DBMS_PREFIX.'members WHERE m_login = ?', array(F::i(_DBMS_SYS)->getEscapeString($login)));

		$obj = $result->getObject();

		if( $obj === NULL )
		{
			throw new BadUserException('Bad login');
		}

		return self::saltHash($password, $obj->m_salt);
	}

	public static function saltHash($password, $salt)
	{
		return sha1(md5($password . $salt) . $salt);
	}

	public static function generateSalt()
	{
		return substr(str_shuffle(sha1(time())), 0, 5);
	}

	public static function handlerException($e)
	{
		// Fatal Error ! Clean Everything and display an error
		// If debug, print if not, log and print a generic message
		//ob_end_clean();

		$message = get_class($e)." thrown within the exception handler. Message: ".$e->getMessage()." on line ".$e->getLine()." :: <br /><br />".$e;

		$id = 0;

		// Try to note it in a file !
		try
		{
			$id = Record::note($message);
		}
		catch(Exception $f)
		{
			// If we can't note it
			die('Error while Recording. Please Contact the Administrator');
		}

		// If Mod Debug, then print the error message
		if( _DEBUG )
		{
			die($id . " :\n" . $message);
		}
		// If not, then display a generic message and try to send the error message to the webmaster by mail
		else
		{
			// try to send
			if( !mail() )
			{
				// if we can't send... (fatality !), ask the visitor to contact the admin
				$message = 'Error n°'.$id.'. Thanks to
							<a href="mailto:'._WEBMASTER_EMAIL.'?subject="[Site : [Error:'.$id.']">contact</a>
							webmaster with this number.';
			}
			else
			{
				// ouf ! Webmaster will receive the mail !
				$message = 'Error occured, webmaster was informed.';
			}

			if( self::$display ) die('Error n° '.$id);
		}
	}

	public static function handlerAsserts($file, $line, $code)
	{
		echo "<hr>Echec de l'assertion :
			File '$file'<br />
			Line '$line'<br />
			Code '$code'<br /><hr />";
		echo "<pre>";
		debug_print_backtrace();
		echo "</pre>";
	}

	public static function handlerError($errno, $errstr, $errfile, $errline)
	{
		$message = '';
		$final = FALSE;

		switch($errno)
		{
			case E_NOTICE:
			case E_USER_NOTICE:
			case E_STRICT:
				$message .= 'Notice: ';
				break;

			case E_WARNING:
			case E_USER_WARNING:
				$message .= 'Warning: ';
				break;

			case E_DEPRECATED:
			case E_USER_DEPRECATED:
				$message .= 'Deprecated: ';
				break;

			case E_USER_ERROR:
			case E_RECOVERABLE_ERROR:
				$message .= 'Error: ';
				$final = TRUE;
				break;

			default:
				$message .= 'Unknown '.$errno.': ';
				$final = TRUE;
		}

		$message .= $errstr . ' in ' . $errfile . ' on line ' . $errline;

		$message .= "\n\n";
		$message .= var_export(debug_backtrace(), TRUE);
		
		$errnum = Record::note($message);

		if( $errnum == NULL )
			return FALSE; // Let PHP manage this error

		if( $final )
			die('Error n° '.$errnum); // Show the name of the file

		return TRUE; // Ok PHP ! We manage successfully this error, do not manage it again
	}
	
	// Usefull when the var need to call a function to get the value
	// Tools::is_empty(function());
	public static function isEmpty($var)
	{
		return empty($var);
	}
	
	public static function unsetKey($array, $key)
	{
		if( !isset($array[$key]) )
			return $array;
			
		unset($array[$key]);
		return array_shift($array);
	}
	
	public static function parseDBMSPrefix($sql)
	{
		return str_replace('!prefix_', _DBMS_PREFIX, $sql);
	}
	
	public static function getFreeColor($g_id, $prec=NULL)
	{
		$result = F::i(_DBMS_SYS)->query('SELECT C.col_id FROM mvc_colors C WHERE NOT EXISTS(SELECT NULL FROM mvc_players P WHERE P.g_id=? AND P.col_id=C.col_id)', array($g_id));
		
		if( is_null($prec) )
			return $result->getObject()->col_id;
		else
		{
			$colors = array();
			while(($obj = $result->getObject()) != NULL)
			{
				if( $obj->col_id > $prec )
					return $obj;
				
				$colors[] = $obj;
			}
			
			return array_shift($colors);
		}
	}
	
	public static function parseOutput($output)
	{
		$output = preg_replace_callback('#(?:href|action|data|src=)|(?:url\s*:\s*)(["\'])(.+?)\\1#i', 'Tools::parseURLCallback', $output);
		$output = preg_replace_callback('#\b[A-Z0-9._%+-]+@[A-Z0-9.-]+\.[A-Z]{2,4}\b#i', 'Tools::parseEmailCallback', $output);
		
		return $output;
	}

	public static function parseEmailCallback($match)
	{
		return '<span class="email">'.Tools::parseEmail($match[0]).'</span>';
	}
		
	public static function parseURLCallback($match)
	{
		return str_replace($match[2], Tools::parseURL($match[2]), $match[0]);
	}
	
	public static function parseEmail($email)
	{
		return strrev($email);
	}
	
	public static function parseURL($url)
	{
		if( _REWRITE_MODE )
		{
			$url = preg_replace('#^\?$#', 'index.html', $url);
			$url = preg_replace('#^\?action=([a-z_-]+)&(.*)$#', '$1.html?$2', $url);
			$url = preg_replace('#^\?action=([a-z_-]+)$#', '$1.html', $url);
		}
		
		return $url;
	}
	
	public static function redirect($url)
	{
		header('Location: '.self::parseURL($url));
		F::i('Session')->close();
		die('');
	}
	
	/*
	 ** Clean GET, POST and COOKIE
	 */
	public static function clean_gpc()
	{
		// On supprime toutes les variables crées par la directive register_globals
		// On stripslashes() toutes les variables GPC pour la compatibilité DBAL
		$gpc = array('_GET', '_POST', '_COOKIE');
		$magic_quote = (get_magic_quotes_gpc()) ? TRUE : FALSE;
		$register_globals = ini_get('register_globals') ? TRUE : FALSE;

		if ($register_globals || $magic_quote)
		{
			foreach ($gpc AS $value)
			{
				if ($register_globals)
				{
					foreach ($GLOBALS[$value] AS $k => $v)
					{
						if ($k != 'debug')
						{
							unset($GLOBALS[$k]);
						}
					}
				}
				
				if ($magic_quote && isset($GLOBALS[$value]))
				{
					$GLOBALS[$value] = Tools::arrayMapRecursive('stripslashes', $GLOBALS[$value]);
				}
			}
		}
	}
	
	public static function arrayMapRecursive($callback, $ary)
	{
		foreach ($ary AS $key => $value)
		{
			if (is_array($value))
			{
				$ary[$key] = Tools::arrayMapRecursive($callback, $value);
			}
			else
			{
				$ary[$key] = $callback($value);
			}
		}
		return ($ary);
	}
}
