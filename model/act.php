<?php

if( !defined('_INDEX') ) {
	header('HTTP/1.1 403 Forbidden');
	exit;
}

/**
 * @package ifips.framework.mvc.models
 */
class Act extends Model
{
	const PRIVACY = _PRIVACY_MEMBER;
	
	public function getView($params, $synchrone)
	{
		try
		{
			$game = new Game($params['game']);
			
			if( $game->g_step == 0 )
			{
				throw new Exception('Game not launched');
			}
			
			if( !$game->isIn(F::i('Session')->getMid()) )
			{
				throw new Exception('You can\'t play : you are not in the game !');
			}
			
			// Strength
			
			// Priority
		}
		catch(Exception $e)
		{
			// Get Out!
			die('Oust !');
		}
		
		// Check from and to
		$result = F::i(_DBMS_SYS)->query('SELECT * FROM !prefix_adjacent WHERE (cou_id1=? AND cou_id2=?) OR (cou_id1=? AND cou_id2=?)', array($params['from'], $params['to'], $params['to'], $params['from']));
		if( $result->getNumRows() != 0 )
		{
			// Ok register
			F::i(_DBMS_SYS)->exec('INSERT INTO !prefix_actions (g_id, cou_from, cou_to, a_strength, a_priority) VALUES (?, ?, ?, ?, ?)', array($params['game'], $params['from'], $params['to'], 1, 1));
		}
	}
}
