<?php

if( !defined('_INDEX') ) {
	header('HTTP/1.1 403 Forbidden');
	exit;
}

/**
 * @package ifips.framework.mvc.models
 */
class Wait_Game extends Model
{
	const PRIVACY = _PRIVACY_MEMBER;

	public function getView($params, $synchrone)
	{
		try
		{
			$game = new Game($params['game']);
			
			if( $game->g_step > 0 )
			{
				throw new Exception('Game already launched');
			}
			
			if( !$game->isIn(F::i('Session')->getMid()) )
			{
				throw new Exception('You are not in this game');
			}
		}
		catch(Exception $e)
		{
			// Get Out!
			die('Oust ! : '.$e);
		}

		if($synchrone)
		{
			$view = View::setFile('wait_game', View::HTML_FILE);
			$view->setValue('u_js', '?action=wait_game&game='.$game->g_id);
			$view->setValue('u_leave', '?action=leave_game&game='.$game->g_id);
			
			$view->setValue('game', stripslashes($game->g_name));
			
			if( $game->m_id == F::i('Session')->getMid() )
			{
				$view->setSwitch('creator', TRUE);
				$view->setValue('u_launch', '?action=launch_game&game='.$game->g_id);
			}
			
			return parent::setBody($view, F::i('Lang')->getKey('title_wait_game'));
		}
		else
		{
			$view = View::setFile('wait_game', View::JSON_FILE);
		
			$players = $game->getPlayers();
			
			for($i=0; $i<count($players); $i++)
			{
				$view->setGroupValues('players', array('name' => $players[$i]->m_login, 'color' => $players[$i]->col_code, 'col_name' => $players[$i]->col_name));
			}
				
			return $view->getContent();
		}
	}
}
