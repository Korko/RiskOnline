<?php

if( !defined('_INDEX') ) {
	header('HTTP/1.1 403 Forbidden');
	exit;
}

/**
 * @package ifips.framework.mvc.models
 */
class Map extends Model
{
	public function getView($params, $synchrone)
	{
		try
		{
			$game = new Game($params['game']);
		}
		catch(Exception $e)
		{
			// Get Out!
			die('Oust !');
		}
		
		switch($params['mode'])
		{
			case 'continents':
				$mode = 'continents';
				break;
				
			case 'owner':
			default:
				$mode = 'owner';
		}
		
		$view = View::setFile('map', View::SVG_FILE);
		
		/**
		 * Territories
		 */
		$result = F::i(_DBMS_SYS)->query('SELECT cou.cou_name, cou.cou_troops_x, cou.cou_troops_y, cou.cou_d, con.con_name, l.m_id, l.l_strength FROM !prefix_continents con, !prefix_countries cou, !prefix_lands l WHERE l.g_id = ? AND l.cou_id = cou.cou_id AND cou.con_id = con.con_id', array($game->g_id));
		while(($obj = $result->getObject()) != NULL)
		{
			$view->setGroupValues('territories', array(
				'cou_name' => $obj->cou_name,
				'con_name' => $obj->con_name,
				'cou_d' => $obj->cou_d,
				'm_id' => $obj->m_id,
				'troops' => $obj->l_strength,
				'troops_x' => $obj->cou_troops_x,
				'troops_y' => $obj->cou_troops_y
			));
		}
		
		/**
		 * Display Mode
		 */	
		if( $mode == 'owner' )
		{
			$result = F::i(_DBMS_SYS)->query('SELECT p.m_id, m.m_login, col.col_code FROM !prefix_players p, !prefix_members m, !prefix_colors col WHERE p.m_id = m.m_id AND p.col_id = col.col_id AND p.g_id = ?', array($game->g_id));
			while(($obj = $result->getObject()) != NULL)
			{
				$view->setGroupValues('styles', array(
					'style_name' => 'g.player_'.$obj->m_id,
					'style_code' => 'fill: #'.$obj->col_code
				));
				
				/**
				 * Legend
				 */
				$view->setGroupValues('legend', array(
					'id' => 'legend_player_'.$obj->m_id,
					'text' => $obj->m_login,
					'col_code' => $obj->col_code
				));
			}
		}
		else if( $mode == 'continents' )
		{
			// con_id = col_id... Just to have a color but normaly, no link lol
			$result = F::i(_DBMS_SYS)->query('SELECT con.con_name, con.con_id, col.col_code FROM !prefix_continents con, !prefix_colors col WHERE con.con_id = col.col_id');
			while(($obj = $result->getObject()) != NULL)
			{
				$view->setGroupValues('styles', array(
					'style_name' => '.'.$obj->con_name,
					'style_code' => 'fill: #'.$obj->col_code
				));
				
				/**
				 * Legend
				 */
				$view->setGroupValues('legend', array(
					'class' => 'legend_continent_'.$obj->con_id,
					'text' => $obj->con_name,
					'col_code' => $obj->col_code
				));
			}
		}
		
		return $view->getContent();
	}
}
