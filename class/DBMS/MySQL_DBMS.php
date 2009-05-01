<?php

if( !defined('_INDEX') ) {
	header('HTTP/1.1 403 Forbidden');
	exit;
}

/**
 * MySQLi DBMS System
 *
 * @author Jeremy 'Korko' Lemesle <jeremy.lemesle@korko.fr>
 * @package ifips.framework.dbms.mysql
 */
class MySQL_DBMS extends Singleton implements IDBMS
{
	private $handle;

	/**
	 * Constructor for the DBMS class
	 * @throws DatabaseException Exception raised on Database error
	 */
	public function __construct()
	{
		$this->handle = $this->init();
	}

	public function getInsertId()
	{
		return $this->handle->insert_id;
	}
	
	/**
	 * Send a query to the database
	 *
	 * @param String $sql Query
	 * @return MySQL_DBMSResult Response
	 * @throws DatabaseException Exception raised on Database error
	 */
	public function query($sql, $params=array())
	{
		$result = $this->real_query($sql, $params);
		
		if( $result === FALSE )
			throw new DBMSError($this->handle->error);
		
		assert('!is_bool($result)');
		
		if( empty($params) )
			return new MySQL_DBMSResult($result);
		else
			return new MySQL_DBMSPreparedResult($result);
	}
	
	public function exec($sql, $params=array())
	{
		$result = $this->real_query($sql, $params);
		
		if( !$result )
		{
			throw new DBMSError($this->getError().' => '.$sql.' && '.var_export($params, TRUE));
		}
		
		return TRUE;
	}

	private function real_query($sql, $params)
	{
		$sql = Tools::parseDBMSPrefix($sql);
		
		// Basic query
		if( empty($params) )
		{
			$result = $this->handle->query($sql);
		}
		// Prepared Query
		else
		{
			$query = $this->handle->stmt_init();
			
			if( !$query->prepare($sql) )
			{
				throw new DBMSError($query->error);
			}
			
			assert('$query->param_count == count($params)');

			$s = '';
			$vars = '';
			for($i=0; $i<count($params); $i++)
			{
				$s .= 's';
				if( !empty($vars) ) $vars .= ', ';
				$vars .= '$params['.$i.']';
			}

			eval('$query->bind_param("'.$s.'", '.$vars.');');

			$result = ($query->execute() && $query->store_result()) ? $query : FALSE;
		}
		
		assert('$result !== NULL');
			
		return $result;
	}
	
	/**
	 * Escape a string in order to put it on a query without sql injection
	 * @param String $str String to parse
	 * @return String String parsed
	 */
	public function getEscapeString($str)
	{
		return $this->handle->real_escape_string($str);
	}

	/**
	 * Get the last error
	 * @return String error
	 */
	public function getError()
	{
		return $this->handle->error;
	}

	/**
	 * Begin a new transaction where you can rollback your queries
	 * Caution : This method may not be implemented in some DBMS
	 *
	 * @throws NotImplementedException If the system of Transaction cannot be implemented in the DBMS
	 * @throws DatabaseException If error happens in Database
	 * @return IDBMSTransaction New Instance for the transaction
	 */
	public function beginTransaction()
	{
		return new MySQL_DBMSTransaction($this->init());
	}

	private function init()
	{
		$handle = new MySQLi();
		$handle->init();
		$handle->options(MYSQLI_OPT_CONNECT_TIMEOUT, 5);

		if( @$handle->real_connect(_DBMS_HOST, _DBMS_LOGIN, _DBMS_PASS, _DBMS_DB) === FALSE )
		{
			throw new DatabaseException($handle->connect_error);
		}

		if( ! $handle->select_db(_DBMS_DB) )
		{
			throw new DatabaseException('Can\'t connect to Database !');
		}

		return $handle;
	}
}

/**
 * @package ifips.framework.dbms.mysql
 */
class MySQL_DBMSTransaction implements IDBMSTransaction
{
	private $handle;
	private $commit=0;

	public function __construct($handle)
	{
		// TODO Check handle
		$this->handle = $handle;
		$this->handle->autocommit(FALSE);
	}

	public function __destruct()
	{
		if( !$this->commit )
		{
			$this->rollback();
		}
	}

	public function commit()
	{
		$return = $this->handle->commit();
		$this->commit = 1;

		return $return;
	}

	public function rollback()
	{
		$this->handle->rollback();
	}

	public function query($sql, $params=array())
	{
		$this->handle->query(Tools::parseDBMSPrefix($sql), $params);
		$this->commit=0;
	}
}

/**
 * Class for DBMS Result after a Query
 * Caution ! This class must ONLY be instantiate by the DBMS class !
 *
 * @author Jeremy 'Korko' Lemesle <jeremy.lemesle@korko.fr>
 * @package ifips.framework.dbms.mysql
 */
class MySQL_DBMSResult implements IDBMSResult
{
	private $i=0;
	private $results=array();

	/**
	 * Construct an object of the DBMS_Result class for a generic DBMS Result system
	 * Permits to know what functions have to be implemented and can be used
	 * @param Object $handle DBMS Result like MySQLi_Result or else
	 */
	public function __construct($handle)
	{
		while($result = $handle->fetch_object())
		{
			$this->results[] = $result;
		}
	}

	/**
	 * Return an object of the query with fields in vars
	 * $o->toto for : SELECT toto
	 * @return Object
	 */
	public function getObject()
	{
		return ($this->i < $this->getNumRows()) ? $this->results[$this->i++] : NULL;
	}

	/**
	 * Give the number of rows in the result
	 * @return Integer
	 */
	public function getNumRows()
	{
		return count($this->results);
	}

	/**
	 * Return the given row index.
	 *
	 * @param $i the row index.
	 * @return the object represented in the given row.
	 */
	public function getRow($i)
	{
		assert('$i < $this->getNumRows()');
		
		return $this->results[$i];
	}
}

/**
 * @package ifips.framework.dbms.mysql
 */
class MySQL_DBMSPreparedResult implements IDBMSResult
{
	private $i=0;
	private $results=array();

	/**
	 * Construct an object of the DBMS_Result class for a generic DBMS Result system
	 * Permits to know what functions have to be implemented and can be used
	 * @param Object $handle DBMS Result like MySQLi_Result or else
	 */
	public function __construct($handle)
	{
		$infos = $handle->result_metadata();

		assert('$infos != NULL');

		$fields = $infos->fetch_fields();

		$values = array();

		$s = '';
		for($i=0; $i<count($fields); $i++)
		{
			if( !empty($s) ) $s .= ', ';

			$s .= '$values["'.$fields[$i]->name.'"]';
		}

		eval('$handle->bind_result('.$s.');');

		for($i=0; $i<$handle->affected_rows; $i++)
		{
			$handle->fetch();
			$obj = new stdClass; // Create new empty object

			foreach($values as $key => $value)
			{
				$obj->$key = $value;
			}

			$this->results[] = $obj;
		}
	}

	/**
	 * Return an object of the query with fields in vars
	 * $o->toto for : SELECT toto
	 * @return Object
	 */
	public function getObject()
	{
		return ($this->i < $this->getNumRows()) ? $this->results[$this->i++] : NULL;
	}

	/**
	 * Give the number of rows in the result
	 * @return Integer
	 */
	public function getNumRows()
	{
		return count($this->results);
	}

	/**
	 * Return the given row index.
	 *
	 * @param $i the row index.
	 * @return the object represented in the given row.
	 */
	public function getRow($i)
	{
		return $this->results[$i];
	}
}
