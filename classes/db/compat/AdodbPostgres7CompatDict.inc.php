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