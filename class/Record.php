<?php

if( !defined('_INDEX') ) {
	header('HTTP/1.1 403 Forbidden');
	exit;
}

/**
 * Record Class
 * Permits to log every errors or informations that may be important
 * to debug and to trace hackers
 *
 * @author Jeremy 'Korko' Lemesle <jeremy.lemesle@korko.fr>
 * @package ifips.framework.tools
 */
class Record
{
	/**
	 * Record a message in a file
	 * @param String $message Message to note
	 * @return String Error ID
	 */
	public static function note($message)
	{
		//$log_id = uniqid() . '-' . ip2long($_SERVER['REMOTE_ADDR'])  . '-' . time();
		$log_id = md5($message);

		$return = file_put_contents(_LOG_DIR . $log_id . '.error', $message, FILE_APPEND);

		if( $return == FALSE )
		{
			$log_id = NULL;
		}

		return $log_id;
	}
}
