<?php

/**
 * DBDataXMLParser.inc.php
 *
 * Copyright (c) 2003-2004 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package db
 *
 * Class to import and export database data from an XML format.
 * See dbscripts/xml/dtd/xmldata.dtd for the XML schema used.
 *
 * $Id$
 */

import('xml.XMLParser');

class DBDataXMLParser {

	/** @var XMLParser the parser to use */
	var $parser;
	
	/** @var ADOConnection the underlying database connection */
	var $dbconn;
	
	/** @var array the array of parsed SQL statements */
	var $sql;

	/**
	 * Constructor.
	 */
	function DBDataXMLParser() {
		$this->parser = &new XMLParser();
		$this->sql = array();
	}
	
	/**
	 * Set the database connection to use for executeData() and extractData().
	 * If the connection is not set, the default system database connection will be used.
	 * @param $dbconn ADOConnection the database connection
	 */
	function setDBConn(&$dbconn) {
		$this->dbconn = &$dbconn;
	}

	/**
	 * Parse an XML data file into SQL statements.
	 * @param $file string path to the XML file to parse
	 * @return array the array of SQL statements parsed
	 */
	function parseData($file) {		
		$this->sql = array();
		$tree = $this->parser->parse($file);
		if ($tree !== false) {
			foreach ($tree->getChildren() as $table) {
				if ($table->getName() == 'table') {
					// Match table element
					foreach ($table->getChildren() as $row) {
						if ($row->getName() == 'row') {
							// Match a row element
							$fieldNames = array();
							$fieldValues = array();
							
							foreach ($row->getChildren() as $field) {
								// Get the field names and values for this INSERT
								$fieldNames[] = $field->getAttribute('name');
								$value = $field->getValue();
								if ($value === null) {
									$value = 'NULL';
								} else if (!is_numeric($value)) {
									$value = $this->quoteString($value);
								}
								$fieldValues[] = $value;
							}
							
							if (count($fieldNames) > 0) {
								$this->sql[] = sprintf(
										'INSERT INTO %s (%s) VALUES (%s)',
										$table->getAttribute('name'),
										join(', ', $fieldNames),
										join(', ', $fieldValues)
									);
							}
						}
					}
				
				} else if ($table->getName() == 'sql') {
					// Match sql element (set of SQL queries)
					foreach ($table->getChildren() as $query) {
						$this->sql[] = $query->getValue();	
					}
				}
			}
		}
		return $this->sql;
	}
	
	/**
	 * Execute the parsed SQL statements.
	 * @param $continueOnError boolean continue to execute remaining statements if a failure occurs
	 * @return boolean success
	 */
	function executeData($continueOnError = false) {
		$this->errorMsg = null;
		$dbconn = $this->dbconn == null ? DBConnection::getConn() : $this->dbconn;
		foreach ($this->sql as $stmt) {
			$dbconn->execute($stmt);
			if (!$continueOnError && $dbconn->errorNo() != 0) {
				return false;
			}
		}
		return true;
	}
	
	/**
	 * Extract data from the database into an XML file.
	 * TODO: To be implemented
	 */
	function extractData() {
	}
	
	/**
	 * Return the parsed SQL statements.
	 * @return array
	 */
	function getSQL() {
		return $this->sql;
	}
	
	/**
	 * Quote a string to be appear as a value in an SQL INSERT statement.
	 * @param $str string
	 * @return string
	 */
	function quoteString($str) {
		return '\'' . str_replace('\'', '\\\'', str_replace('\\', '\\\\', $str)) . '\'';
	}
	
	/**
	 * Perform required clean up for this object.
	 */
	function destroy() {
		$this->parser->destroy();
		unset($this);
	}
}

?>
