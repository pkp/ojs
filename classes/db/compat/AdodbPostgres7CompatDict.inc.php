<?php
/**
 * @file classes/db/compat/drivers/AdodbPostgres7CompatDict.inc.php
 *
 * Copyright (c) 2003-2009 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class AdodbPostgres7CompatDict
 * @ingroup db
 *
 * @brief Compatibility layer above ADOdb's postgres datadict to avoid 3rd-party software patches
 */

// $Id$

import('adodb.adodb-lib');
import('adodb.adodb-datadict');
import('adodb.datadict.datadict-postgres');
import('db.compat.AdodbDatadictCompatDelegate');

class AdodbPostgres7CompatDict extends ADODB2_postgres {
	var $delegate;

	function AdodbPostgres7CompatDict() {
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

	/**
	 * In PostgreSQL <7.3, SERIAL columns can't be used because they
	 * impose UNIQUE constraints on the column. In the best case (when
	 * we want a UNIQUE constraint), this means that the index is
	 * created twice -- once by ADODB, once by PostgreSQL -- and in
	 * the worst case, an unwanted UNIQUE condition is imposed.
	 *
	 * The makeObjectName function was ported from PostgreSQL 7.1's
	 * analyse.c.
	 */
	function makeObjectName($name1, $name2, $typename) {
		$overhead = 0;

		$name1chars = strlen($name1);
		if ($name2) {
			$name2chars = strlen($name2);
			$overhead++; /* allow for separating underscore */
		}
		else $name2chars = 0;

		if ($typename) $overhead += strlen($typename) + 1;

		$availchars = 32 - 1 - $overhead; /* --- 32 = default NAMEDATALEN in PostgreSQL --- */

		/*
		* If we must truncate, preferentially truncate the longer name. This
		* logic could be expressed without a loop, but it's simple and
		* obvious as a loop.
		*/
		while ($name1chars + $name2chars > $availchars) {
			if ($name1chars > $name2chars) $name1chars--;
			else $name2chars--;
		}

		/* Now construct the string using the chosen lengths */
		$name = substr($name1, 0, $name1chars);

		if ($name2) $name .= '_' . substr($name2, 0, $name2chars);
		if ($typename) $name .= '_' . $typename;

		return $name;
	}

	function CreateTableSQL($tabname, $flds, $tableoptions=false) {
		$sql = ADODB_DataDict::CreateTableSQL($tabname, $flds, $tableoptions);

		if (7.3 > (float) @$this->serverInfo['version']) {
			foreach ($flds as $fld) {
				$fld = _array_change_key_case($fld);

				$isAutoInc = false;
				foreach($fld as $attr => $v) switch ($attr) {
					case 'AUTOINCREMENT':
					case 'AUTO':
						$isAutoInc = true;
						break;
					case 'NAME':
						$fname = $v;
						break;
				}

				if (isset($fname) && $isAutoInc) {
					// This field is an AUTOINCREMENT. Create a sequence
					// for it.
					$sequenceName = $this->makeObjectName($tabname, $fname, 'seq');
					array_unshift($sql, "CREATE SEQUENCE $sequenceName");
					array_push($sql, "ALTER TABLE $tabname ALTER COLUMN $fname SET DEFAULT nextval('$sequenceName')");
				}
			}
		}
		return $sql;
	}

 	function _CreateSuffix($fname, &$ftype, $fnotnull, $fdefault, $fautoinc, $fconstraint) {
		// With PostgreSQL < 7.3, we cannot use the SERIAL type becauseit
		// forces the use of a unique index on that column; at best, this
		// causes duplicate indexes to be created. At worst, it causes
		// UNIQUE constraints to be put on columns that shouldn't have them.
 		if ($fautoinc && 7.3>(float)@$this->serverInfo['version']) {
			$ftype = 'INTEGER';
			return '';
		} else {
			return $this->__CreateSuffixUnpatched($fname, $ftype, $fnotnull, $fdefault, $fautoinc, $fconstraint);
 		}
 	}

 	function __CreateSuffixUnpatched($fname, &$ftype, $fnotnull, $fdefault, $fautoinc, $fconstraint) {
 		return parent::_CreateSuffix($fname, $ftype, $fnotnull, $fdefault, $fautoinc, $fconstraint);
 	}

	/*
	 * CreateDatabase with character set support
	 *
	 * NOTE: If a character set is specified, assumes the database server supports this.
	 */
	function CreateDatabase($dbname,$options=false) {
		$options = $this->_Options($options);
		$sql = array();

		$s = 'CREATE DATABASE ' . $this->NameQuote($dbname);
		if (isset($options[$this->upperName]))
			$s .= ' '.$options[$this->upperName];
		if ($this->delegate->_GetCharSetDelegate())
			$s .= sprintf(' WITH ENCODING \'%s\'', $this->delegate->_GetCharSetDelegate());
		if (7.3 <= (float) @$this->serverInfo['version'])
			$s .= ' TEMPLATE template0'; // Deal with "template1 is being accessed by other users" errors (FIXME?)
		$sql[] = $s;
		return $sql;
	}
}
?>