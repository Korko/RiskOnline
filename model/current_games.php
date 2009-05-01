<?php

if( !defined('_INDEX') ) {
	header('HTTP/1.1 403 Forbidden');
	exit;
}

/**
 * @package ifips.framework.mvc.models
 */
class Current_Games extends Model
{
	const PRIVACY = _PRIVACY_MEMBER; // Only Members can play

	public function getView($params, $synchrone)
	{
		// Get all the games of the player
		$result = F::i(_DBMS_SYS)->query('SELECT g.g_id, g_name, g_step, COUNT(*) AS g_total_players, TIMESTAMPDIFF(HOUR, g_start, NOW()) AS lifetime, m_login AS g_owner FROM !prefix_members m, !prefix_players AS p, !prefix_games AS g, !prefix_players AS p2 WHERE p.m_id = ? AND g.m_id = m.m_id AND p.g_id = g.g_id AND g.g_id = p2.g_id GROUP BY g.g_id', array(F::i('Session')->getMid()));

		$view = View::setFile('list_games', View::HTML_FILE);
		$view->setValue('form', '');
		
		if( $result->getNumRows() == 0 )
		{
			$view->setSwitch('no_games', TRUE);
		}
		
		for($i=0; $i<$result->getNumRows(); $i++)
		{
			$obj = $result->getObject();
			
			if( $obj->g_step == 0 )
			{
				$link = '?action=wait_game&game='.$obj->g_id;
				$name = '<span style="font-style: italic;">'.$obj->g_name.'</span>';
			}
			else
			{
				$link = '?action=play&game='.$obj->g_id;
				$name = '<span style="font-weight: bold;">'.$obj->g_name.'</span>';
			}
			
			$view->setGroupValues('games', array('link' => $link, 'name' => $name, 'total_players' => $obj->g_total_players, 'owner' => $obj->g_owner, 'lifetime' => $obj->lifetime));
		}
		
		return parent::setBody($view, F::i('Lang')->getKey('title_current_games'));
	}
}
