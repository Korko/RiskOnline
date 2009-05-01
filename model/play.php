<?php

if( !defined('_INDEX') ) {
	header('HTTP/1.1 403 Forbidden');
	exit;
}

class Play extends Model
{
	const PRIVACY = _PRIVACY_MEMBER;
	
	public function getView($params, $synchrone)
	{
		try
		{
			$game = new Game($params['game']);
			
			if( !$game->isIn(F::i('Session')->getMid()) )
			{
				throw new Exception('Not In');
			}
		}
		catch(Exception $e)
		{
			// Get Out!
			die('Oust !');
		}
		
		$view = View::setFile('map', View::HTML_FILE);
		$view->setValue('game', $params['game']);
		$view->setValue('mode', (isset($params['mode']) ? $params['mode'] : 'owner'));
		return parent::setBody($view, '', TRUE);
	}
}
