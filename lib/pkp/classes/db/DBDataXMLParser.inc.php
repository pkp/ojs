<?php

/**
 * @file classes/db/DBDataXMLParser.inc.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2000-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class DBDataXMLParser
 * @ingroup db
 *
 * @brief Class to import and export database data from an XML format.
 * See dbscripts/xml/dtd/xmldata.dtd for the XML schema used.
 */


import('lib.pkp.classes.xml.XMLParser');

class DBDataXMLParser {
	/** @var ADOConnection the underlying database connection */
	var $dbconn;

	/** @var array the array of parsed SQL statements */
	var $sql;

	/**
	 * Constructor.
	 */
	function __construct() {
		$this->sql = array();
	}

	/**
	 * Set the database connection to use for executeData().
	 * If the connection is not set, the default system database connection will be used.
	 * @param $dbconn ADOConnection the database connection
	 */
	function setDBConn(&$dbconn) {
		$this->dbconn =& $dbconn;
	}

	/**
	 * Parse an XML data file into SQL statements.
	 * @param $file string path to the XML file to parse
	 * @return array the array of SQL statements parsed
	 */
	function parseData($file) {
		$this->sql = array();
		$parser = new XMLParser();
		$tree = $parser->parse($file);
		if (!$tree) return array();

		$allTables = $this->dbconn->MetaTables();
		foreach ($tree->getChildren() as $type) switch($type->getName()) {
			case 'table':
				$fieldDefaultValues = array();

				// Match table element
				foreach ($type->getChildren() as $row) {
					switch ($row->getName()) {
						case 'field_default':
							// Match a default field element
							list($fieldName, $value) = $this->_getFieldData($row);
							$fieldDefaultValues[$fieldName] = $value;
							break;

						case 'row':
							// Match a row element
							$fieldValues = array();

							foreach ($row->getChildren() as $field) {
								// Get the field names and values for this INSERT
								list($fieldName, $value) = $this->_getFieldData($field);
								$fieldValues[$fieldName] = $value;
							}

							$fieldValues = array_merge($fieldDefaultValues, $fieldValues);

							if (count($fieldValues) > 0) {
								$this->sql[] = sprintf(
										'INSERT INTO %s (%s) VALUES (%s)',
										$type->getAttribute('name'),
										join(', ', array_keys($fieldValues)),
										join(', ', array_values($fieldValues)));
							}
							break;

						default:
							assert(false);
					}
				}
				break;
			case 'sql':
				// Match sql element (set of SQL queries)
				foreach ($type->getChildren() as $child) switch ($child->getName()) {
					case 'drop':
						if (!isset($dbdict)) {
							$dbdict = @NewDataDictionary($this->dbconn);
						}
						$table = $child->getAttribute('table');
						$column = $child->getAttribute('column');
						if ($column) {
							// NOT PORTABLE; do not use this
							$this->sql[] = $dbdict->DropColumnSql($table, $column);
						} else {
							$this->sql[] = $dbdict->DropTableSQL($table);
						}
						break;
					case 'rename':
						if (!isset($dbdict)) {
							$dbdict = @NewDataDictionary($this->dbconn);
						}
						$table = $child->getAttribute('table');
						$column = $child->getAttribute('column');
						$to = $child->getAttribute('to');
						if ($column) {
							// Make sure the target column does not yet exist.
							// This is to guarantee idempotence of upgrade scripts.
							$run = false;
							if (in_array($table, $allTables)) {
								$columns =& $this->dbconn->MetaColumns($table, true);
								if (!isset($columns[strtoupper($to)])) {
									// Only run if the column has not yet been
									// renamed.
									$run = true;
								}
							} else {
								// If the target table does not exist then
								// we assume that another rename entry will still
								// rename it and we should run after it.
								$run = true;
							}

							if ($run) {
								$colId = strtoupper($column);
								$flds = '';
								if (isset($columns[$colId])) {
									$col = $columns[$colId];
									if ($col->max_length == "-1") {
										$max_length = '';
									} else {
										$max_length = $col->max_length;
									}
									$fld = array('NAME' => $col->name, 'TYPE' => $dbdict->MetaType($col), 'SIZE' => $max_length);
									if ($col->primary_key) $fld['KEY'] = 'KEY';
									if ($col->auto_increment) $fld['AUTOINCREMENT'] = 'AUTOINCREMENT';
									if ($col->not_null) $fld['NOTNULL'] = 'NOTNULL';
									if ($col->has_default) $fld['DEFAULT'] = $col->default_value;
									$flds = array($colId => $fld);
								} else assert(false);

								$this->sql[] = $dbdict->RenameColumnSQL($table, $column, $to, $flds);
							}
						} else {
							// Make sure the target table does not yet exist.
							// This is to guarantee idempotence of upgrade scripts.
							if (!in_array($to, $allTables)) {
								$this->sql[] = $dbdict->RenameTableSQL($table, $to);
							}
						}
						break;
					case 'query':
						$driver = $child->getAttribute('driver');
						if (empty($driver) || $this->dbconn->databaseType === $driver) {
							$this->sql[] = $child->getValue();
						}
						break;
				}
				break;
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
		return $this->dbconn->qstr($str);
	}


	//
	// Private helper methods
	//
	/**
	 * retrieve a field name and value from a field node
	 * @param $fieldNode XMLNode
	 * @return array an array with two entries: the field
	 *  name and the field value
	 */
	function _getFieldData($fieldNode) {
		$fieldName = $fieldNode->getAttribute('name');
		$fieldValue = $fieldNode->getValue();

		// Is this field empty? If so: do we want NULL or
		// an empty string?
		$isEmpty = $fieldNode->getAttribute('null');
		if (!is_null($isEmpty)) {
			assert(is_null($fieldValue));
			switch($isEmpty) {
				case 1:
					$fieldValue = null;
					break;

				case 0:
					$fieldValue = '';
					break;
			}
		}

		// Translate null to 'NULL' for SQL use.
		if (is_null($fieldValue)) {
			$fieldValue = 'NULL';
		} else {
			// Quote the value.
			if (!is_numeric($fieldValue)) {
				$fieldValue = $this->quoteString($fieldValue);
			}
		}

		return array($fieldName, $fieldValue);
	}
}

?>
