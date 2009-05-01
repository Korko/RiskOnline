<?php

if( !defined('_INDEX') ) {
	header('HTTP/1.1 403 Forbidden');
	exit;
}

/**
 * System of Session
 *
 * @author Jeremy 'Korko' Lemesle <jeremy.lemesle@korko.fr>
 * @package ifips.framework.tools
 */
class Session extends Singleton
{
	private $sid;
	private $mid=_ID_VISITOR;
	private $auth=_AUTH_VISITOR;

	public function __construct()
	{
		parent::__construct();

		session_set_save_handler(
			array(&$this, "_open"),
			array(&$this, "_close"),
			array(&$this, "_read"),
			array(&$this, "_write"),
			array(&$this, "_destroy"),
			array(&$this, "_gc")
		);

		session_start();
		//session_regenerate_id(false);
	}

	public function close()
	{
		session_write_close();
		F::i(_DBMS_SYS)->exec('UPDATE !prefix_sessions SET m_id = ? WHERE s_id = ?', array($this->mid, $this->sid));
	}

	private function setId($sid)
	{
		$this->sid = $sid;

		session_id($sid);
	}

	public function getAuth()
	{
		return $this->auth;
	}

	/**
	 * Get the member id
	 *
	 * @return integer
	 */
	public function getMid()
	{
		return $this->mid;
	}

	/**
	 * The user is connected ?
	 *
	 * @return boolean
	 */
	public function isConnected()
	{
		return ($this->mid != _ID_VISITOR );
	}

	/**
	 * Does the user can access the page
	 * Which requires some access
	 */
	public function canAccess($privacy)
	{
		$access = FALSE;

		assert('$privacy != 0');

		if( $privacy>0 && $this->auth>=$privacy )
		{
			$access = TRUE;
		}
		else if( $privacy<0 && $this->auth == -$privacy )
		{
			$access = TRUE;
		}

		return $access;
	}

	/**
	 * Lot of test and same result...
	 * Do distincts tests cause it must be interesting to know the cause
	 * @param String $sid Session ID to check
	 * @return Boolean Correct or not
	 * @throws SessionException Hacking attempt
	 * @throws DatabaseException Database Error
	 */
	private function checkSid($sid)
	{
		$check = TRUE;
		
		// First, if it's null...
		if( empty($sid) )
		{
			throw new NullSIDException('Null SID');
		}

		// Second, check if the sid is correct
		// Hexadecimal required, 32 length (md5)
		else if( preg_match('#^[^a-f0-9]{32}$#i', $sid) )
		{
			// It's not a error, it's more an hacking attempt...
			throw new BadSIDException('Incorrect SID');
		}
		else
		{
			if( strcmp(F::i(_DBMS_SYS)->getEscapeString($sid), $sid) != 0 )
			{
				// What ?? Need to escape chars ?! It's not normal !!
				// It's not a error, it's more an hacking attempt...
				throw new BadSIDException('SID to escape');
			}

			$result = F::i(_DBMS_SYS)->query('SELECT * FROM !prefix_sessions WHERE s_id = ? AND v_ip = ?', array($sid, ip2long($_SERVER['REMOTE_ADDR'])));

			if( $result->getNumRows() == 0 )
			{
				// Bad couple s_id / v_ip
				$check = FALSE;
			}
			else
			{
				if( (time()-strtotime($result->getObject()->s_date, time())) > _SESSION_LIFETIME )
				{
					$check = FALSE;
				}
			}
		}

		return $check;
	}

	/**
	 * Authentificate user with his login and password
	 *
	 * @param String $login
	 * @param String $password
	 * @return integer member id
	 * @throws DatabaseException Database Error
	 * @throws BadUserException Bad Login/password
	 */
	public function connect($login, $password)
	{
		if( $this->isConnected() ) throw new AccessException('You must not be connected');

		$login = F::i(_DBMS_SYS)->getEscapeString($login);
		$password = Tools::passHash($login, $password);

		$result = F::i(_DBMS_SYS)->query('SELECT m_id, m_auth FROM !prefix_members WHERE m_login = ? AND m_password = ?', array($login, $password));

		if( $result->getNumRows() == 0 )
		{
			throw new BadUserException('Bad login/password');
		}
		else
		{
			$obj = $result->getObject();

			$this->mid = intval($obj->m_id);
			$this->auth = intval($obj->m_auth);

			return $this->mid;
		}
	}

	public function disconnect()
	{
		$this->mid	= _ID_VISITOR;
		$this->auth	= _AUTH_VISITOR;
	}

	public function getUsername()
	{
		try
		{
			$result = F::i(_DBMS_SYS)->query('SELECT m_login FROM !prefix_members WHERE m_id = ?', array($this->mid));

			$obj = $result->getObject();

			if( $result->getNumRows() == 0 )
			{
				throw new ErrorException('Empty');
			}
			else
			{
				return $obj->m_login;
			}
		}
		catch(ErrorException $e)
		{
			return '';
		}
	}

	/**
	 * Session handler functions
	 * @throws DatabaseException Database Error
	 */
	public function _open($save_path, $session_name)
	{
		$sid = isset($_COOKIE[_COOKIE_NAME]) ? $_COOKIE[_COOKIE_NAME] : '';

		$ip = $_SERVER['REMOTE_ADDR'];

		$check = FALSE;
		
		try
		{
			$check = $this->checkSid($sid);
		}
		catch(BadSIDException $e)
		{
			// Hacking attempt... Say no and log !
			$check = FALSE;
			Record::note($e->__toString());
		}
		catch(NullSIDException $e)
		{
			// Nothing important...
			$check = FALSE;
		}

		// If DatabaseException is raised, it's more important, let it throws !

		// If Check return FALSE, sid is incorrect !
		if( !$check )
		{
			// Create a new session
			// 0.00000000000000000000000000000000000000001
			// chance of having 2 sessions with the same id !
			// I hope never happen
			$sid = md5(uniqid(time()) . $ip);
			$this->mid = _ID_VISITOR;

			// Register it
			F::i(_DBMS_SYS)->exec('INSERT INTO !prefix_sessions (s_id, m_id, v_ip, s_date) VALUES (?, ?, ?, NOW())', array($sid, $this->mid, ip2long($ip)));
			//die('INSERT INTO !prefix_sessions (s_id, m_id, v_ip, s_date) VALUES ("'.$sid.'", "'.$this->mid.'", "'.ip2long($ip).'", NOW())');
		}
		else
		{
			// Update it
			F::i(_DBMS_SYS)->exec('UPDATE !prefix_sessions SET s_date=NOW() WHERE s_id = ?', array($sid));

			$result = F::i(_DBMS_SYS)->query('SELECT m.m_id, m.m_auth FROM !prefix_sessions s, !prefix_members m WHERE m.m_id = s.m_id AND s.s_id = ?', array($sid));
			$obj = $result->getObject();

			if( $obj !== NULL )
			{
				$this->mid = intval($obj->m_id);
				$this->auth = intval($obj->m_auth);
			}
		}

		$this->setId($sid);

		// Send changes to the visitor by cookies
		// If error... too bad !
		setcookie(_COOKIE_NAME, $sid, time() + _SESSION_LIFETIME);

		return TRUE;
	}

	public function _close()
	{
		// Bybye !
		return TRUE;
	}

	// No datas stored !
	public function _read($id)
	{
		return array();
	}

	public function _write($id, $sess_data)
	{
		return TRUE;
	}

	public function _destroy($id)
	{
		// If Check return FALSE, sid is incorrect !
		if( !$this->checkSid($id) )
		{
			return FALSE;
		}

		return F::i(_DBMS_SYS)->exec('DELETE FROM !prefix_sessions WHERE s_id = ?', array($id));
	}

	public function _gc($maxlifetime)
	{
		return F::i(_DBMS_SYS)->exec('DELETE FROM !prefix_sessions WHERE (TIMESTAMPDIFF(SECOND, s_date, NOW()) > ?)', array(intval($maxlifetime)));
	}
}
