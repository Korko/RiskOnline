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
				
			$view = View::setFile('confirm', View::JSON_FILE);
		
			if( $params['step'] ==  $game->g_step+1 )
			{
				// If all players ready
				$result = F::i(_DBMS_SYS)->query('SELECT m_id FROM !prefix_players WHERE g_id=? AND p_ready=0', array($game->g_id));
				if( $result->getNumRows() == 0 )
				{
					// If somebody is solving the game
					// Dangerous Part so ask the database to have the LAST info
					$result = F::i(_DBMS_SYS)->query('SELECT g_resolving FROM !prefix_games WHERE g_id=?', array($game->g_id));
					if( $result->getObject()->g_resolving == 0 )
					{
						// Let's Solve !
						F::i(_DBMS_SYS)->mexec(array(
							array('UPDATE !prefix_games SET g_resolving=1 WHERE g_id=?', array($game->g_id)),
							array('SELECT PROC_SOLVEATTACKS(?)', array($game->g_id)),
							array('UPDATE !prefix_players SET p_ready=0 WHERE g_id=?', array($game->g_id)),
							array('UPDATE !prefix_games SET g_step=g_step+1 WHERE g_id=?', array($game->g_id))
						));
					}
					
					$view->setValue('confirm', 1);
				}
				else
				{
					$view->setValue('confirm', 0);
				}
			}
			else if( $params['step'] ==  $game->g_step )
				$view->setValue('confirm', 1);
			else
				throw new Exception('Not a valid step');
		}
		catch(Exception $e)
		{
			// Get Out!
			die('Oust ! : '.$e->getMessage());
		}
		
		return $view->getContent();
	}
}
