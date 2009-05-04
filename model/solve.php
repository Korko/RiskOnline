<?php

if( !defined('_INDEX') ) {
	header('HTTP/1.1 403 Forbidden');
	exit;
}

class Solve extends Model
{
	const PRIVACY = _PRIVACY_MEMBER;
	
	public function getView($params, $synchrone)
	{
		
	}
}
