<?php

class Game
{
	private $game_obj;
	private $players_obj;
	
	public function __construct($g_id)
	{
		if( empty($g_id) )
		{
			throw new Exception('Empty Game ID');
		}
		
		$this->update($g_id);
	}
	
	public function __destruct()
	{
		$this->game_obj = NULL;
		$this->players_obj = NULL;
	}
	
	public function __get($variable)
	{
		if( isset($this->game_obj->$variable) )
		{
			return $this->game_obj->$variable;
		}
		
		return NULL;
	}
	
	public function getPlayers()
	{
		return $this->players_obj;
	}
	
	public function isIn($m_id)
	{
		$found = FALSE;
		
		for($i=0; $i<count($this->players_obj) && !$found; $i++)
		{
			if( $this->players_obj[$i]->m_id == $m_id )
				$found = TRUE;
		}
		
		return $found;
	}
	
	public function getNumPlayers()
	{
		return count($this->players_obj);
	}
	
	public static function getMaxPlayers()
	{
		// Max Colors
		static $max = -1;
		
		if( $max == -1 )
		{
			$max = F::i(_DBMS_SYS)->query('SELECT COUNT(*) AS max FROM !prefix_colors')->getObject()->max;
		}
		
		return $max;
	}
	
	public static function create($m_id, $g_name, $access_key)
	{
		F::i(_DBMS_SYS)->exec('INSERT INTO !prefix_games (g_id, m_id, g_name, g_access_key) VALUES (NULL, ?, ?, ?)', array($m_id, $g_name, $access_key));	
		$game = new Game(F::i(_DBMS_SYS)->getInsertId());
		$game->insert($m_id);
		
		return $game;
	}
	
	public function insert($m_id)
	{
		if( count($this->players_obj) >= self::getMaxPlayers() )
		{
			throw new Exception('Can\'t add more players, limit reached');
		}
		
		if( $this->g_step > 0 )
		{
			throw new Exception('Cannot add players while playing');
		}
		
		F::i(_DBMS_SYS)->exec('INSERT INTO !prefix_players (m_id, g_id, col_id) VALUES (?, ?, ?)', array($m_id, $this->g_id, Tools::getFreeColor($this->g_id)));		
		$this->update();
	}
	
	public function reject($m_id)
	{
		F::i(_DBMS_SYS)->exec('DELETE FROM !prefix_players WHERE g_id=? AND m_id=?', array($this->g_id, $m_id));
		
		if( $this->m_id == $m_id )
		{
			// Creator leaved so... Drop the game !
			F::i(_DBMS_SYS)->exec('DELETE FROM !prefix_games WHERE g_id=?', array($this->m_id));
			$this->__destruct();
		}
		else
		{
			$this->update();
		}
	}
	
	public function nextStep()
	{
		F::i(_DBMS_SYS)->exec('UPDATE !prefix_games SET g_step=1, g_start=NOW() WHERE g_id = ?', array($this->g_id));
	}
	
	private function update($g_id=NULL)
	{
		if( is_null($g_id) && !is_null($this->g_id) )
		{
			$g_id = $this->g_id;
		}
		
		$result = F::i(_DBMS_SYS)->query('SELECT * FROM !prefix_games WHERE g_id=?', array($g_id));
		
		if( $result->getNumRows() == 0 )
		{
			throw new Exception('Bad Game ID');
		}
		
		$this->game_obj = $result->getObject();
		
		$result = F::i(_DBMS_SYS)->query('SELECT p.*, col.*, m.* FROM !prefix_players p, !prefix_members m, !prefix_colors col WHERE p.m_id = m.m_id AND p.col_id = col.col_id AND p.g_id=? ORDER BY col.col_id', array($g_id));
		
		while(($obj = $result->getObject()) != NULL)
		{
			$this->players_obj[] = $obj;
		}
	}
}
