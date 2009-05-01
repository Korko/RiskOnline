<?php

if( !defined('_INDEX') ) {
	header('HTTP/1.1 403 Forbidden');
	exit;
}

/**
 * @package ifips.framework.mvc.models
 */
class Join_Game extends Model
{
	const PRIVACY = _PRIVACY_MEMBER; // Only Members can play

	private $search_fields = array(
		'search' => array(
			'type' => 'text'
		)
	);
	
	private $access_fields = array(
		'access_key' => array(
			'type' => 'password',
			'check' => 'notempty'
		)
	);

	public function getView($params, $synchrone)
	{
		$search_form = new Form($this->search_fields, $params);
		$access_form = new Form($this->access_fields, $params);
		$title = '';
		
		$insert = TRUE;
		try
		{
			$game = new Game($params['game']);
			
			if( $game->isIn(F::i('Session')->getMid()) )
			{
				throw new Exception('Already In');
			}
		}
		catch(Exception $e)
		{
			$insert = FALSE;
		}
		
		if( $insert )
		{
			if( $game->g_access_key == 'NULL' || ($access_form->isSubmitting() && Tools::isEmpty($access_form->getErrors()) && ($game->g_access_key == Tools::saltHash($params['access_key']))) )
			{
				$game->insert(F::i('Session')->getMid());
				Tools::redirect('?action=wait_game&game='.$game->g_id);
			}
			else
			{
				// CHECK
				if( $access_form->isSubmitting() )
				{
					// Error
					die('bad access key');
				}
				else
				{
					$view = View::setFile('formular', View::HTML_FILE);

					$errors = $access_form->getErrors();
					// Errors in the filling
					if( !empty($errors) )
					{
						$view->setSwitch('form_errors', TRUE);
						foreach($errors as $field => $error)
						{
							$view->setGroupValues('form_errors', array('error' => F::i('Lang')->getKey('error_'.$field.'_'.$error)));
						}
					}
					
					$view->setValue('form', $access_form->getHTML(F::i('Lang')->getKey('access_key'), '#', 'POST', 'tabbed_form'));
				}
			}
		}
		else
		{
			$sql = 'SELECT g.g_id, g_name, TIMESTAMPDIFF(HOUR, g_start, NOW()) AS lifetime, COUNT(*) AS g_total_players, m_login AS g_owner 
					FROM !prefix_members m, !prefix_games AS g, !prefix_players AS p 
					WHERE g.m_id = m.m_id 
						AND p.g_id = g.g_id 
						AND ? NOT IN (
							SELECT m_id FROM !prefix_players WHERE g_id = g.g_id
						)';
			$array = array(F::i('Session')->getMid());
			
			// If search is defined, add condition
			if( isset($params['search']) )
			{
				$sql .= ' AND g_name LIKE ?';
				$array[] = '%'.$params['search'].'%';
			}
				
			$sql .= ' GROUP BY g.g_id ORDER BY g_start DESC';
	
			// Get all the games
			$result = F::i(_DBMS_SYS)->query($sql, $array);
	
			$view = View::setFile('list_games', View::HTML_FILE);
		
			$view->setValue('form', $search_form->getHTML('', '#', 'POST', 'inline'));
			
			if( $result->getNumRows() == 0 )
			{
				$view->setSwitch('no_games', TRUE);
			}
		
			for($i=0; $i<$result->getNumRows(); $i++)
			{
				$obj = $result->getObject();
				$view->setGroupValues('games', array('link' => '?action=join_game&game='.$obj->g_id, 'name' => stripslashes($obj->g_name), 'total_players' => $obj->g_total_players, 'owner' => $obj->g_owner, 'lifetime' => $obj->lifetime));
			}
			$title = F::i('Lang')->getKey('title_join_game');
		}
		
		return parent::setBody($view);
	}
}
