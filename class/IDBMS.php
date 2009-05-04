<?php

if( !defined('_INDEX') ) {
	header('HTTP/1.1 403 Forbidden');
	exit;
}

/**
 * Common database management system.
 *
 * @author Julien Peeters <jj.peeters@gmail.com>
 * @package ifips.framework.dbms
 */

interface IDBMS
{
	/**
	 * Send a query to the database.
	 *
	 * @param String $sql the SQL request.
	 * @return IDBMSResult Result of the query
	 */
	public function query($sql, $params=array());

	/**
	 * Escape a string in order to protect against SQL Injections
	 *
	 * @param String $str String to escape
	 * @return String Same string but escaped
	 */
	public function getEscapeString($str);

	/**
	 * Get the last Error
	 * @return String
	 */
	public function getError();

	/**
	 * Begin a new transaction where you can rollback your queries
	 * Caution : This method may not be implemented in some DBMS
	 *
	 * @throws NotImplementedException If the system of Transaction cannot be implemented in the DBMS
	 * @return IDBMSTransaction New Instance for the transaction
	 */
	public function beginTransaction();
	
	/**
	 * Get the ID of the last insert rows
	 * @return int
	 */
	public function getInsertId();
}

interface IDBMSTransaction
{
	/**
	 * @see IDBMS::query()
	 */
	public function query($sql, $params=array());

	/**
	 * Rollback on delete the object if no commit
	 */
	public function __destruct();

	/**
	 * Commit changes in a transaction.
	 * May be not implemented in some DBMS.
	 */
	public function commit();

	/**
	 * Rollback in a transaction.
	 * May be not implemented in some DBMS.
	 */
	public function rollback();
}

interface IDBMSResult
{
	/**
	 * Get the rnumber of rows.
	 *
	 * @return the number of rows in the result.
	 */
	public function getNumRows();

	/**
	 * Return the given row index.
	 *
	 * @param $index the row index.
	 * @return the object represented in the given row.
	 */
	public function getRow($index);

	public function getObject();
}
