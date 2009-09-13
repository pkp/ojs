<?php
/**
 * @file classes/db/compat/drivers/AdodbMysqlCompatDict.inc.php
 *
 * Copyright (c) 2003-2009 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class AdodbMysqlCompatDict
 * @ingroup db
 *
 * @brief Compatibility layer above ADOdb's mysql datadict to avoid 3rd-party software patches
 */

// $Id$

import('adodb.adodb-lib');
import('adodb.adodb-datadict');
import('adodb.datadict.datadict-mysql');
import('db.compat.AdodbDatadictCompatDelegate');

class AdodbMysqlCompatDict extends ADODB2_mysql {
	var $delegate;

	function AdodbMysqlCompatDict() {
		$this->delegate = &new AdodbDatadictCompatDelegate($this);
	}

	function __call($name, $arguments) {
		return call_user_func_array(array($this->delegate, "_${name}Delegate"), $arguments);
	}

	function RenameColumnSQL($tabname, $oldcolumn, $newcolumn, $flds = '') {
		return $this->delegate->_RenameColumnSQLDelegate($tabname, $oldcolumn, $newcolumn, $flds);
	}

	function _RenameColumnSQLUnpatched($tabname, $oldcolumn, $newcolumn, $flds = '') {
		return parent::RenameColumnSQL($tabname, $oldcolumn, $newcolumn, $flds);
	}

	function ChangeTableSQL($tablename, $flds, $tableoptions = false) {
		return $this->delegate->_ChangeTableSQLDelegate($tablename, $flds, $tableoptions);
	}

	function _ChangeTableSQLUnpatched($tablename, $flds, $tableoptions = false) {
		return parent::ChangeTableSQL($tablename, $flds, $tableoptions);
	}

	/*
	 * CreateDatabase with character set support
	 */
	function CreateDatabase($dbname, $options=false) {
		$options = $this->_Options($options);
		$sql = array();

		$s = 'CREATE DATABASE ' . $this->NameQuote($dbname);
		if (isset($options[$this->upperName]))
			$s .= ' '.$options[$this->upperName];
		if ($this->delegate->_GetCharSetDelegate())
			$s .= sprintf(' DEFAULT CHARACTER SET %s', $this->delegate->_GetCharSetDelegate());
		$sql[] = $s;
		return $sql;
	}

	/*
	 * _TableSQL with character set support
	 */
	function _TableSQL($tabname, $lines, $pkey, $tableoptions) {
		$sql = array();

		if (isset($tableoptions['REPLACE']) || isset ($tableoptions['DROP'])) {
			$sql[] = sprintf($this->dropTable,$tabname);
			if ($this->autoIncrement) {
				$sInc = $this->_DropAutoIncrement($tabname);
				if ($sInc) $sql[] = $sInc;
			}
			if ( isset ($tableoptions['DROP']) ) {
				return $sql;
			}
		}
		$s = "CREATE TABLE $tabname (\n";
		$s .= implode(",\n", $lines);
		if (sizeof($pkey)>0) {
			$s .= ",\n                 PRIMARY KEY (";
			$s .= implode(", ",$pkey).")";
		}
		if (isset($tableoptions['CONSTRAINTS']))
			$s .= "\n".$tableoptions['CONSTRAINTS'];

		if (isset($tableoptions[$this->upperName.'_CONSTRAINTS']))
			$s .= "\n".$tableoptions[$this->upperName.'_CONSTRAINTS'];

		$s .= "\n)";
		if (isset($tableoptions[$this->upperName])) $s .= $tableoptions[$this->upperName];
		if ($this->delegate->_GetCharSetDelegate())
			$s .= sprintf(' DEFAULT CHARACTER SET %s', $this->delegate->_GetCharSetDelegate());
		$sql[] = $s;

		return $sql;
	}
}
?>