<?php

if( !defined('_INDEX') ) {
	header('HTTP/1.1 403 Forbidden');
	exit;
}

/**
 * This class will regroup instances in order to have Singleton system.
 * Each file can extends Singleton an call parent::__construct()
 * to Disable calls from an other class than Factory
 *
 * @author Jeremy 'Korko' Lemesle <jeremy.lemesle@korko.fr>
 * @package ifips.framework
 */
class Factory
{
	private static $instances = array();

	public static function include_class($class)
	{
		if( class_exists($class) ) return;

		$path = explode(':', _PATH);

		$filenotfound = TRUE;
		for($i=0; $i<count($path) && $filenotfound; $i++)
		{
			if( is_file($path[$i].$class.'.php') )
			{
				$filenotfound = FALSE;
				@include_once($path[$i].$class.'.php');
			}
		}

		if( $filenotfound || !class_exists($class, FALSE) )
		{
			throw new FileNotFoundException($class.'.php');
		}
	}

	/**
	 * instanciate($class, $params, $superclass)
	 * instanciate($class, $params)
	 */
	private static function instanciate($class, $params, $superclass)
	{
		assert('!isset(self::$instances[$class])');

		self::include_class($class);

		if( !is_null($superclass) && !is_subclass_of($class, $superclass) )
		{
			throw new ClassExtendException("$class => $superclass");
		}

		self::$instances[$class] = new $class($params);
	}

	public static function getInstance($class, $params=array(), $superclass=NULL)
	{
		if( !isset(self::$instances[$class]) )
		{
			self::instanciate($class, $params, $superclass);
		}

		return self::$instances[$class];
	}
}

/**
 * This class is just a shortcut to the Factory class and the function getInstance
 * @see Factory
 */
class F {
	/**
	 * @see Factory::getInstance
	 */
	public static function i($class, $params=array(), $superclass=NULL)
	{
		return Factory::getInstance($class, $params, $superclass);
	}
}
