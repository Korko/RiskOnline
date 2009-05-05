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
		}
		catch(Exception $e)
		{
			// Get Out!
			die('Oust !');
		}
		
		// Check all the actions
		for($i=0; $i<size($params['action']); $i++)
		{
			// Confirm the player
			
			// Check and insert actions
			/*$result = F::i(_DBMS_SYS)->query('SELECT c1.cou_id AS cou_id1, c1.cou_name AS cou_from, c2.cou_id AS cou_id2, c2.cou_name AS cou_to FROM !prefix_adjacent a LEFT JOIN !prefix_countries c1 ON a.cou_id1 = c1.cou_id LEFT JOIN !prefix_countries c2 ON a.cou_id2 = c2.cou_id WHERE (c1.cou_name = ? AND c2.cou_name = ?) OR (c1.cou_name = ? AND c2.cou_name = ?)', array($params['from'], $params['to'], $params['to'], $params['from']));
			if( $result->getNumRows() != 0 )
			{
				$obj = $result->getObject();
				
				$from_id = ($obj->cou_from == $params['from']) ? $obj->cou_id1 : $obj->cou_id2;
				$to_id = ($obj->cou_to == $params['to']) ? $obj->cou_id2 : $obj->cou_id1;
			
				// Ok register
				//F::i(_DBMS_SYS)->exec('INSERT INTO !prefix_actions (g_id, cou_from, cou_to, a_strength, a_priority) VALUES (?, ?, ?, ?, ?)', array($params['game'], $from_id, $to_id, 1, 1));
			}*/
		}
	}
}
