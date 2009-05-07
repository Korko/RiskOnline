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
		try
		{
			if( !isset($params['game']) )
				throw new Exception('No Game given');
				
			$game = new Game($params['game']);
			
			if( !$game->isIn(F::i('Session')->getMid()) )
			{
				throw new Exception('Not In');
			}

			if( !isset($params['step']) || empty($params['step']) )
				throw new Exception('Need step');
				
			if( $params['step'] ==  $game->g_step+1 )
			{
				// If all players ready
				// Solve
				echo 'FUCK';
			}
			else if( $params['step'] ==  $game->g_step )
				return 1;
			else
				throw new Exception('Not a valid step');
		}
		catch(Exception $e)
		{
			// Get Out!
			die('Oust ! : '.$e->getMessage());
		}
	}
}
