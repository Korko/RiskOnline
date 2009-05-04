<?php

if( !defined('_INDEX') ) {
	header('HTTP/1.1 403 Forbidden');
	exit;
}

/**
 * Abstract Class for the Singleton System
 * Need PHP 5.3 for the 'get_called_class' function
 *
 * @author Jeremy 'Korko' Lemesle <jeremy.lemesle@korko.fr>
 * @package ifips.framework
 */
abstract class Singleton
{
	/**
	 * System of Singleton, Constructor have to be
	 * called only by the class Factory !!
	 * (in order to be working on php < 5.3)
	 */
    public function __construct()
	{
		// 1 => Singleton
		// 2 => Called Class
		// 3 => Factory ! (or have to be)
		$calling = Tools::getCallingClass(3);
		assert('$calling == "Factory"');
	}

	/**
	 * Need to desactivate the possibility to clone the instance
	 */
    final public function __clone() {}

	/**
	 * Explicitely desactive serialization
	 */
	final public function __sleep() {}
	final public function __wakeup() {}
}
