<?php

if( !defined('_INDEX') ) {
	header('HTTP/1.1 403 Forbidden');
	exit;
}

/**
 * @package ifips.framework.mvc.models
 */
class Launch_Game extends Model
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
			
			if( $game->m_id != F::i('Session')->getMid() )
			{
				throw new Exception('You are not the owner of this game !');
			}
			
			if( $game->getNumPlayers() == 1 )
			{
				throw new Exception('Cannot launch a game with a single player');
			}
		}
		catch(Exception $e)
		{
			// Get Out!
			die('Oust !');
		}
		
		// Give countries to players
		/*
		 * 42 countries
		 * /2 => 21
		 * /3 => 14
		 * /4 => 10.5 (10 + 2)
		 * /5 => 8.4 (8 + 2)
		 * /6 => 7
		 * /7 => 6
		 * /8 => 5.25 (5 + 2)
		 * /9 => 4.67 (4 + 6)
		 * /10 => 4.2 (4 + 2)
		 */

		$players = $game->getPlayers();
		$count_players = count($players);
		
		$result = F::i(_DBMS_SYS)->query('SELECT cou_id FROM !prefix_countries');
		
		$countries = array();
		while(($obj = $result->getObject()) != NULL)
		{
			$countries[] = $obj->cou_id;
		}
		
		$count_countries = count($countries);
		$countries_per_player = intval($count_countries/$count_players);
		shuffle($countries);
		$lands = array_chunk($countries, $countries_per_player);
		
		// What to do with the last countries ? Give...
		if( count($lands) > $count_players ) $remaining = $lands[$count_players];
		shuffle($players);
		for($i=0; $i<count($remaining); $i++)
		{
			$lands[$i][] = $remaining[$i];
		}
		
		// Save and Go to step 1
		for($i=0; $i<$count_players; $i++)
		{
			for($j=0; $j<count($lands[$i]); $j++)
			{
				F::i(_DBMS_SYS)->exec('INSERT INTO !prefix_lands (g_id, cou_id, m_id) VALUES (?, ?, ?)', array($params['game'], $lands[$i][$j], $players[$i]->m_id));
			}
		}
		
		$game->nextStep();
		
		Tools::redirect('?action=play&game='.$params['game']);
	}
}
