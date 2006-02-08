<?php

/**
 * DAO.inc.php
 *
 * Copyright (c) 2003-2005 The Public Knowledge Project
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
	function DAO($dataSource = null, $callHooks = true) {
		if ($callHooks === true && checkPhpVersion('4.3.0')) {
			$trace = debug_backtrace();
			// Call hooks based on the calling entity, assuming
			// this method is only called by a subclass. Results
			// in hook calls named e.g. "SessionDAO::Constructor"
			if (HookRegistry::call($trace[1]['class'] . '::Constructor', array(&$this, &$dataSource))) {
				return;
			}
		}
		
		if (!isset($dataSource)) {
			$this->_dataSource = &DBConnection::getConn();
		} else {
			$this->_dataSource = $dataSource;
		}
	}
	
	/**
	 * Execute a SELECT SQL statement.
	 * @param $sql string the SQL statement
	 * @param $params array parameters for the SQL statement
	 * @return ADORecordSet
	 */
	function &retrieve($sql, $params = false, $callHooks = true) {
		if ($callHooks === true && checkPhpVersion('4.3.0')) {
			$trace = debug_backtrace();
			// Call hooks based on the calling entity, assuming
			// this method is only called by a subclass. Results
			// in hook calls named e.g. "SessionDAO::getSession"
			$value = null;
			if (HookRegistry::call($trace[1]['class'] . '::' . $trace[1]['function'], array(&$sql, &$params, &$value))) {
				return $value;
			}
		}
		
		$result = &$this->_dataSource->execute($sql, $params !== false && !is_array($params) ? array($params) : $params);
		if ($this->_dataSource->errorNo()) {
			// FIXME Handle errors more elegantly.
			fatalError('DB Error: ' . $this->_dataSource->errorMsg());
		}
		return $result;
	}

	/**
	 * Execute a cached SELECT SQL statement.
	 * @param $sql string the SQL statement
	 * @param $params array parameters for the SQL statement
	 * @return ADORecordSet
	 */
	function &retrieveCached($sql, $params = false, $secsToCache = 3600, $callHooks = true) {
		if ($callHooks === true && checkPhpVersion('4.3.0')) {
			$trace = debug_backtrace();
			// Call hooks based on the calling entity, assuming
			// this method is only called by a subclass. Results
			// in hook calls named e.g. "SessionDAO::getSession"
			$value = null;
			if (HookRegistry::call($trace[1]['class'] . '::' . $trace[1]['function'], array(&$sql, &$params, &$secsToCache, &$value))) {
				return $value;
			}
		}

		static $cacheDir;
		if (!isset($cacheDir)) {
			import('cache.CacheManager');
			$cacheDir = CacheManager::getFileCachePath() . '/_db';
			$this->setCacheDir($cacheDir);
		}

		$result = &$this->_dataSource->CacheExecute($secsToCache, $sql, $params !== false && !is_array($params) ? array($params) : $params);
		if ($this->_dataSource->errorNo()) {
			// FIXME Handle errors more elegantly.
			fatalError('DB Error: ' . $this->_dataSource->errorMsg());
		}
		return $result;
	}
	
	/**
	 * Execute a SELECT SQL statement with LIMIT on the rows returned.
	 * @param $sql string the SQL statement
	 * @param $params array parameters for the SQL statement
	 * @param $numRows int maximum number of rows to return in the result set
	 * @param $offset int row offset in the result set
	 * @return ADORecordSet
	 */
	function &retrieveLimit($sql, $params = false, $numRows = false, $offset = false, $callHooks = true) {
		if ($callHooks === true && checkPhpVersion('4.3.0')) {
			$trace = debug_backtrace();
			// Call hooks based on the calling entity, assuming
			// this method is only called by a subclass. Results
			// in hook calls named e.g. "SessionDAO::getSession"
			$value = null;
			if (HookRegistry::call($trace[1]['class'] . '::' . $trace[1]['function'], array(&$sql, &$params, &$numRows, &$offset, &$value))) {
				return $value;
			}
		}
		
		$result = &$this->_dataSource->selectLimit($sql, $numRows === false ? -1 : $numRows, $offset === false ? -1 : $offset, $params !== false && !is_array($params) ? array($params) : $params);
		if ($this->_dataSource->errorNo()) {
			fatalError('DB Error: ' . $this->_dataSource->errorMsg());
		}
		return $result;
	}

	/**
	 * Execute a SELECT SQL statment, returning rows in the range supplied.
	 * @param $sql string the SQL statement
	 * @param $params array parameters for the SQL statement
	 * @param $dbResultRange object the DBResultRange object describing the desired range
	 */
	function &retrieveRange($sql, $params = false, $dbResultRange = null, $callHooks = true) {
		if ($callHooks === true && checkPhpVersion('4.3.0')) {
			$trace = debug_backtrace();
			// Call hooks based on the calling entity, assuming
			// this method is only called by a subclass. Results
			// in hook calls named e.g. "SessionDAO::getSession"
			$value = null;
			if (HookRegistry::call($trace[1]['class'] . '::' . $trace[1]['function'], array(&$sql, &$params, &$dbResultRange, &$value))) {
				return $value;
			}
		}
		
		if (isset($dbResultRange) && $dbResultRange->isValid()) {
			$result = &$this->_dataSource->PageExecute($sql, $dbResultRange->getCount(), $dbResultRange->getPage(), $params);
			if ($this->_dataSource->errorNo()) {
				fatalError('DB Error: ' . $this->_dataSource->errorMsg());
			}
		}
		else {
			$result = &$this->retrieve($sql, $params, false);
		}
		return $result;
	}
	
	/**
	 * Execute an INSERT, UPDATE, or DELETE SQL statement.
	 * @param $sql the SQL statement the execute
	 * @param $params an array of parameters for the SQL statement
	 * @return boolean
	 */
	function update($sql, $params = false, $callHooks = true) {
		if ($callHooks === true && checkPhpVersion('4.3.0')) {
			$trace = debug_backtrace();
			// Call hooks based on the calling entity, assuming
			// this method is only called by a subclass. Results
			// in hook calls named e.g. "SessionDAO::updateSession"
			$value = null;
			if (HookRegistry::call($trace[1]['class'] . '::' . $trace[1]['function'], array(&$sql, &$params, &$value))) {
				return $value;
			}
		}
		
		$this->_dataSource->execute($sql, $params !== false && !is_array($params) ? array($params) : $params);
		if ($this->_dataSource->errorNo()) {
			fatalError('DB Error: ' . $this->_dataSource->errorMsg());
		}
		return $this->_dataSource->errorNo() == 0 ? true : false;
	}
	
	/**
	 * Return the last ID inserted in an autonumbered field.
	 * @param $table string table name
	 * @param $id string the ID/key column in the table
	 * @return int
	 */
	function getInsertId($table = '', $id = '', $callHooks = true) {
		return $this->_dataSource->po_insert_id($table, $id);
	}
	
	/**
	 * Configure the caching directory for database results
	 * NOTE: This is implemented as a GLOBAL setting and cannot
	 * be set on a per-connection basis.
	 * @param $path string
	 */
	function setCacheDir($path) {
		global $ADODB_CACHE_DIR;
		$ADODB_CACHE_DIR = $path;
	}
	
	/**
	 * Return datetime formatted for DB insertion.
	 * @param $dt int/string *nix timestamp or ISO datetime string
	 * @return string
	 */
	function datetimeToDB($dt) {
		return $this->_dataSource->DBTimeStamp($dt);
	}
	
	/**
	 * Return date formatted for DB insertion.
	 * @param $d int/string *nix timestamp or ISO date string
	 * @return string
	 */
	function dateToDB($d) {
		return $this->_dataSource->DBDate($d);
	}
	
	/**
	 * Return datetime from DB as ISO datetime string.
	 * @param $dt string datetime from DB
	 * @return string
	 */
	function datetimeFromDB($dt) {
		if ($dt === null) return null;
		return $this->_dataSource->UserTimeStamp($dt, 'Y-m-d H:i:s');
	}
	/**
	 * Return date from DB as ISO date string.
	 * @param $d string date from DB
	 * @return string
	 */
	function dateFromDB($d) {
		if ($d === null) return null;
		return $this->_dataSource->UserDate($d, 'Y-m-d');
	}
}

?>
