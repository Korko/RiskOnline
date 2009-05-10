<?php

if( !defined('_INDEX') ) {
	header('HTTP/1.1 403 Forbidden');
	exit;
}

class Cookie extends Singleton
{
	// Cookies variables
	private $cookie=array();
	private $name;
	
    public function __construct($name='')
	{
		parent::__construct();
		
		$this->name = $name;
		
		if (isset($_COOKIE[$name])) {
			foreach ($_COOKIE[$name] as $key => $value) {
				$this->cookie[$key] = $value;
			}
		}
	}
	
    // Get cookie information
    public function get($key)
    {
		return isset($this->cookie[$key]) ? $this->cookie[$key] : '';
    }
	
    // Set cookie information
    public function set($key, $value, $expire=_SESSION_LIFETIME, $path='', $domain='', $secure=FALSE, $httponly=TRUE)
	{
		if( !empty($this->name) )
		{
			$key = $this->name.'['.$key.']';
		}
		
		// Store the cookie
		setcookie($key, $value, time() + $expire, $path, $domain, $secure, $httponly);
	}
}

?>
