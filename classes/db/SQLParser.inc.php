<?php

/**
 * @file SQLParser.inc.php
 *
 * Copyright (c) 2003-2008 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package db
 * @class SQLParser
 *
 * Class for parsing and executing statements in SQL files.
 *
 * $Id$
 */

class SQLParser {

	/** The database driver */
	var $driver;

	/** The database connection object */
	var $dataSource;

	/** Enable debugging (print SQL statements as they are executed) */
	var $debug;

	var $errorMsg;

	/** Delimiter for SQL comments used by the data source */
	var $commentDelim;

	/** Delimiter for SQL statements used by the data source */
	var $statementDelim;

	/**
	 * Constructor.
	 * @param $driver string the database driver (currently only "mysql" is supported)
	 * @param $debug boolean echo each statement as it's executed
	 */
	function SQLParser($driver, &$dataSource, $debug = false) {
		$this->driver = $driver;
		$this->dataSource = &$dataSource;
		$this->debug = $debug;
		$this->errorMsg = array();
		$this->commentDelim = '(\-\-|#)';
		$this->statementDelim = ';';
	}

	/**
	 * Parse an SQL file and execute all SQL statements in it.
	 * @param $file string full path to the file
	 * @param $failOnError boolean stop execution if an error is encountered
	 * @return boolean true if no errors occurred, false otherwise
	 */
	function executeFile($file, $failOnError = true) {
		if (!file_exists($file) || !is_readable($file)) {
			array_push($this->errorMsg, "$file does not exist or is not readble!");
			return false;
		}

		// Read file and break up into SQL statements
		$sql = join('', file($file));
		$this->stripComments($sql);
		$statements = &$this->parseStatements($sql);

		// Execute each SQL statement
		for ($i=0, $count=count($statements); $i < $count; $i++) {
			if ($this->debug) {
				echo 'Executing: ', $statements[$i], "\n\n";
			}

			$this->dataSource->execute($statements[$i]);

			if ($this->dataSource->errorNo() != 0) {
				// An error occurred executing the statement
				array_push($this->errorMsg, $this->dataSource->errorMsg());

				if ($failOnError) {
					// Abort if fail on error is enabled
					return false;
				} else {
					$error = true;
				}
			}
		}

		return isset($error) ? false : true;
	}

	/**
	 * Strip SQL comments from SQL string.
	 * @param $sql string
	 */
	function stripComments(&$sql) {
		$sql = trim(String::regexp_replace(sprintf('/^\s*%s(.*)$/m', $this->commentDelim), '', $sql));
	}

	/**
	 * Parse SQL content into individual SQL statements.
	 * @param $sql string
	 * @return array
	 */
	function &parseStatements(&$sql) {
		$statements = array();
		$statementsTmp = explode($this->statementDelim, $sql);

		$currentStatement = '';
		$numSingleQuotes = $numEscapedSingleQuotes = 0;

		// This method for parsing the SQL statements was adapted from one used in phpBB (http://www.phpbb.com/)
		for ($i=0, $count=count($statementsTmp); $i < $count; $i++) {
			// Get total number of single quotes in string
			$numSingleQuotes += String::substr_count($statementsTmp[$i], "'");

			// Get number of escaped single quotes
			$numEscapedSingleQuotes += String::regexp_match_all("/(?<!\\\\)(\\\\\\\\)*\\\\'/", $statementsTmp[$i], $matches);

			$currentStatement .= $statementsTmp[$i];

			if (($numSingleQuotes - $numEscapedSingleQuotes) % 2 == 0) {
				// Even number of unescaped single quotes, so statement must be complete
				if (trim($currentStatement) !== '') {
					array_push($statements, trim($currentStatement));
				}
				$currentStatement = '';
				$numSingleQuotes = $numEscapedSingleQuotes = 0;

			} else {
				// The statement is not complete, the delimiter must be inside the statement
				$currentStatement .= $this->statementDelim;
			}
		}

		return $statements;
	}

	/**
	 * Return the last error message that occurred in parsing.
	 * @return string
	 */
	function getErrorMsg() {
		return count($this->errorMsg) == 0 ? null : array_pop($this->errorMsg);
	}

}

?>
