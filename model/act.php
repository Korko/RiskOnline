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
			if( !isset($params['game']) )
				throw new Exception('No Game given');
				
			$game = new Game($params['game']);
			
			if( $game->g_step == 0 )
				throw new ModelException('Game not launched');
			
			if( !$game->isIn(F::i('Session')->getMid()) )
				throw new ModelException('You can\'t play : you are not in the game !');
		
			$sql = array();
			
			// Check all the actions
			$params['act'] = isset($params['act']) ? $params['act'] : array();
			for($i=0; $i<count($params['act']); $i++)
			{
				list($from, $to, $strength, $priority) = explode(';', $params['act'][$i]);

				if( $strength <= 0 )
					throw new ModelException('Strength positive !');
				
				if( !in_array($priority, $GLOBALS['priorities']) )
					throw new ModelException('Priority Unavailble !');
					
				// Check actions
				$result = F::i(_DBMS_SYS)->query('SELECT c1.cou_id AS cou_id1, c1.cou_name AS cou_from, c2.cou_id AS cou_id2, c2.cou_name AS cou_to FROM !prefix_adjacent a LEFT JOIN !prefix_countries c1 ON a.cou_id1 = c1.cou_id LEFT JOIN !prefix_countries c2 ON a.cou_id2 = c2.cou_id WHERE (c1.cou_name = ? AND c2.cou_name = ?) OR (c1.cou_name = ? AND c2.cou_name = ?)', array($from, $to, $to, $from));
				if( $result->getNumRows() != 0 )
				{
					$obj = $result->getObject();
					
					// Check capacity...
					// TODO
					
					$from_id = ($obj->cou_from == $from) ? $obj->cou_id1 : $obj->cou_id2;
					$to_id = ($obj->cou_to == $to) ? $obj->cou_id2 : $obj->cou_id1;		
			
					$sql[] = array('INSERT INTO !prefix_actions (g_id, cou_from, cou_to, a_strength, a_priority) VALUES (?, ?, ?, ?, ?)', array($game->g_id, $from_id, $to_id, $strength, $priority));
				}
			}
			
			// Confirm the player
			F::i(_DBMS_SYS)->exec('UPDATE !prefix_players SET p_ready=1 WHERE m_id=? AND g_id=?', array(F::i('Session')->getMid(), $game->g_id));
			
			// Insert Actions
			F::i(_DBMS_SYS)->mexec($sql);
		}
		catch(ModelException $e)
		{
			// Get Out!
			die('Oust ! : '.$e->getMessage());
		}
	}
}
