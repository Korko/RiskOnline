<?php

if( !defined('_INDEX') ) {
	header('HTTP/1.1 403 Forbidden');
	exit;
}

/**
 * Benchmark Class of the System
 *
 * @author Jeremy 'Korko' Lemesle <jeremy.lemesle@korko.fr>
 * @package ifips.framework.tools
 */
class Benchmark
{
	private $steps = array();
	
	public function __construct()
	{
		$this->addStep('Begin');
	}
	
	public function addStep($tag='')
	{
		$this->steps[] = array('time' => microtime(TRUE), 'tag' => $tag);
	}
	
	public function getTotal()
	{
		return microtime(TRUE)-$this->steps[0]['time'];
	}
	
	public function __toString()
	{
		
	}
	
	public function write($filename)
	{
		
	}
}
