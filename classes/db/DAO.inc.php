<?php

/**
 * DAO.inc.php
 *
 * Copyright (c) 2003-2004 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package db
 *
 * Data Access Object base class.
 * Operations for retrieving and modifying objects from a database.
 *
 * $Id$
 */

class DAO {

	/** The database connection object */
	var $_dataSource;
	
	/**
	 * Constructor.
	 * Initialize the database connection.
	 */
	function DAO() {
		$this->_dataSource = &DBConnection::getConn();
	}
	
	/**
	 * Execute a SELECT SQL statement.
	 * @param $sql string the SQL statement
	 * @param $params array parameters for the SQL statement
	 * @return ADORecordSet
	 */
	function &retrieve($sql, $params = false) {
		$result = &$this->_dataSource->execute($sql, $params !== false && !is_array($params) ? array($params) : $params);
		return $this->_dataSource->errorNo() == 0 ? $result : null;
	}
	
	/**
	 * Execute an INSERT, UPDATE, or DELETE SQL statement.
	 * @param $sql the SQL statement the execute
	 * @param $params an array of parameters for the SQL statement
	 * @return boolean
	 */
	function update($sql, $params = false) {
		$this->_dataSource->execute($sql, $params !== false && !is_array($params) ? array($params) : $params);
		if ($this->_dataSource->errorNo()) {
			die('DB Error: ' . $this->_dataSource->errorMsg());
		}
		return $this->_dataSource->errorNo() == 0 ? true : false;
	}
	
	/**
	 * Return the last ID inserted in an autonumbered field.
	 * @param $table string table name
	 * @param $id string the ID/key column in the table
	 * @return int
	 */
	function getInsertId($table = '', $id = '') {
		return $this->_dataSource->po_insert_id($table, $id);
	}
	
	/**
	 * Will select, getting $count rows from $offset.
	 * @param $sql string the SQL statement
	 * @param $count number of rows to return
	 * @param $offset row to start calculating from
	 * @param $params array parameters for the SQL statement
	 * @return ADORecordSet
	 */
	function &selectLimit($sql, $count = -1, $offset = -1, $params = false) {
		$result = &$this->_dataSource->selectLimit($sql, $count, $offset, $params !== false && !is_array($params) ? array($params) : $params);
		return $this->_dataSource->errorNo() == 0 ? $result : null;
	}
	
}

?>
