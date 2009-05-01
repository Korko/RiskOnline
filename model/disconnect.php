<?php

if( !defined('_INDEX') ) {
	header('HTTP/1.1 403 Forbidden');
	exit;
}

/**
 * @package ifips.framework.mvc.models
 */
class Disconnect extends Model
{
	const PRIVACY = _PRIVACY_MEMBER;

	public function getView($params, $synchrone)
	{
		F::i('Session')->disconnect();
		
		Tools::redirect('?');
	}
}
