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
	var $connectionCharset;
	var $debug;
	
	
	/** @var boolean indicate connection status */
	var $connected;
	
	/**
	 * Constructor.
	 * Calls initDefaultDBConnection if no arguments are passed,
	 * otherwise calls initCustomDBConnection with custom connection parameters. 
	 */
	function DBConnection() {
		$this-> connected = false;
		
		if (func_num_args() == 0) {
			$this->initDefaultDBConnection();
		} else {
			$args = func_get_args();
			call_user_func_array(array(&$this, 'initCustomDBConnection'), $args);
		}
	}
	
	/**
	 * Create new database connection with the connection parameters from the system configuration.
	 * @return boolean
	 */
	function initDefaultDBConnection() {
		$this->driver = Config::getVar('database', 'driver');
		$this->host = Config::getVar('database', 'host');
		$this->username = Config::getVar('database', 'username');
		$this->password = Config::getVar('database', 'password');
		$this->databaseName = Config::getVar('database', 'name');
		$this->persistent = Config::getVar('database', 'persistent') ? true : false;
		$this->connectionCharset = Config::getVar('i18n', 'connection_charset');
		$this->debug = Config::getVar('database', 'debug') ? true : false;
		
		return $this->initConn();
	}
	
	/**
	 * Create new database connection with the specified connection parameters.
	 * @param $driver string
	 * @param $host string
	 * @param $username string
	 * @param $password string
	 * @param $databaseName string
	 * @param $persistent boolean use persistent connections
	 * @param $connectionCharset string character set to use for the connection
	 * @param $debug boolean enable verbose debug output
	 * @return boolean
	 */
	function initCustomDBConnection($driver, $host, $username, $password, $databaseName, $persistent = true, $connectionCharset = false, $debug = false) {
		$this->driver = $driver;
		$this->host = $host;
		$this->username = $username;
		$this->password = $password;
		$this->databaseName = $databaseName;
		$this->persistent = $persistent;
		$this->connectionCharset = $connectionCharset;
		$this->debug = $debug;
		
		return $this->initConn();
	}
	
	/**
	 * Initialize database connection object and establish connection to the database
	 * @return boolean
	 */
	function initConn() {
		require_once('adodb/adodb.inc.php');
		
		$this->dbconn = &ADONewConnection($this->driver);
		
		if (isset($this->host)) {
			if ($this->persistent) {
				$this->connected = @$this->dbconn->PConnect(
					$this->host,
					$this->username,
					$this->password,
					$this->databaseName
				);
				
			} else {
				$this->connected = @$this->dbconn->Connect(
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
		
		if ($this->connected && $this->connectionCharset)
		{
			// Set client/connection character set
			// NOTE: Only supported on some database servers and versions
			$this->dbconn->SetCharSet($this->connectionCharset);
		}
		
		return $this->connected;
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
	
	/**
	 * Check if a database connection has been established
	 * @return boolean
	 */
	function isConnected() {
		return $this->connected;
	}
	
}

?>
