<?php

/**
 * DBConnection.inc.php
 *
 * Copyright (c) 2003-2004 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package db
 *
 * Class for accessing the low-level database connection.
 * Currently integrated with ADOdb (from http://php.weblogs.com/adodb/).
 *
 * $Id$
 */

class DBConnection {
	
	/** The underlying database connection object */
	var $dbconn;
	
	/** Database connection parameters */
	var $driver;
	var $host;
	var $username;
	var $password;
	var $databaseName;
	var $persistent;
	var $debug;
	
	/**
	 * Constructor.
	 * Calls initDefaultDBConnection if no arguments are passed,
	 * otherwise calls initCustomDBConnection with custom connection parameters. 
	 */
	function DBConnection() {
		if (func_num_args() == 0) {
			$this->initDefaultDBConnection();
		} else {
			$args = func_get_args();
			call_user_func_array(array(&$this, 'initCustomDBConnection'), $args);
		}
	}
	
	/**
	 * Create new database connection with the connection parameters from the system configuration.
	 */
	function initDefaultDBConnection() {
		$this->driver = Config::getVar('database', 'driver');
		$this->host = Config::getVar('database', 'host');
		$this->username = Config::getVar('database', 'username');
		$this->password = Config::getVar('database', 'password');
		$this->databaseName = Config::getVar('database', 'name');
		$this->persistent = Config::getVar('database', 'persistent') ? true : false;
		$this->debug = Config::getVar('database', 'debug') ? true : false;
		
		$this->initConn();
	}
	
	/**
	 * Create new database connection with the specified connection parameters.
	 * @param $driver string
	 * @param $host string
	 * @param $username string
	 * @param $password string
	 * @param $debug boolean enable verbose debug output
	 */
	function initCustomDBConnection($driver, $host, $username, $password, $databaseName, $persistent = true, $debug = false) {
		$this->driver = $driver;
		$this->host = $host;
		$this->username = $username;
		$this->password = $password;
		$this->databaseName = $databaseName;
		$this->persistent = $persistent;
		$this->debug = $debug;
		
		$this->initConn();
	}
	
	/**
	 * Initialize database connection object and establish connection to the database
	 */
	function initConn() {
		require_once('adodb/adodb.inc.php');
		
		$this->dbconn = &ADONewConnection($this->driver);
		
		if (isset($this->host)) {
			if ($this->persistent) {
				@$this->dbconn->PConnect(
					$this->host,
					$this->username,
					$this->password,
					$this->databaseName
				);
				
			} else {
				@$this->dbconn->Connect(
					$this->host,
					$this->username,
					$this->password,
					$this->databaseName
				);
			}
		}
		
		if ($this->debug) {
			// Enable verbose database debugging (prints all SQL statements as they're exected)
			$this->dbconn->debug = true;
		}
	}
	
	/**
	 * Return the database connection object.
	 * @return ADONewConnection
	 */
	 function &getDBConn() {
	 	return $this->dbconn;
	 }
	
	/**
	 * Return a reference to a single static instance of the database connection.
	 * @return ADONewConnection
	 */
	function &getConn() {
		static $instance;
		if (!isset($instance)) {
			$instance = new DBConnection();
		}
		return $instance->getDBConn();
	}
	
}

?>
