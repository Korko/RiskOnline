<?php

if( !defined('_INDEX') ) {
	header('HTTP/1.1 403 Forbidden');
	exit;
}

/**
 * @package ifips.framework.mvc.models
 */
class Leave_Game extends Model
{
	const PRIVACY = _PRIVACY_MEMBER;

	public function getView($params, $synchrone)
	{
		try
		{
			$game = new Game($params['game']);
			
			if( !$game->isIn(F::i('Session')->getMid()) )
			{
				throw new Exception('You are not part of this Game');
			}
		}
		catch(Exception $e)
		{
			// Get Out!
			die('Oust !');
		}
		
		$game->reject(F::i('Session')->getMid());
		
		Tools::redirect('?action=current_games');
	}
}
