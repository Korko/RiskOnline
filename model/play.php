<?php

if( !defined('_INDEX') ) {
	header('HTTP/1.1 403 Forbidden');
	exit;
}

class Play extends Model
{
	const PRIVACY = _PRIVACY_MEMBER;
	
	public function getView($params, $synchrone)
	{
		try
		{
			$game = new Game($params['game']);
			
			if( !$game->isIn(F::i('Session')->getMid()) )
			{
				throw new Exception('Not In');
			}
		}
		catch(Exception $e)
		{
			// Get Out!
			die('Oust !');
		}
		
		$view = View::setFile('map', View::HTML_FILE);
		
		/**
		 * Links
		 */
		$result = F::i(_DBMS_SYS)->query('SELECT cou1.cou_name AS cou1, cou2.cou_name AS cou2 FROM !prefix_adjacent a, !prefix_countries cou1, !prefix_countries cou2 WHERE a.cou_id1 = cou1.cou_id AND a.cou_id2 = cou2.cou_id');
		while(($obj = $result->getObject()) != NULL)
		{
			$view->setGroupValues('adjacents', array(
				'from' => $obj->cou1,
				'to' => $obj->cou2
			));
		}
		
		$view->setValue('game', $params['game']);
		$view->setValue('mode', (isset($params['mode']) ? $params['mode'] : 'owner'));
		$view->setValue('m_id', F::i('Session')->getMid());
		
		return parent::setBody($view, '', TRUE);
	}
}
