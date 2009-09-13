<?php
/**
 * @file classes/db/compat/drivers/AdodbDatadictCompatDelegate.inc.php
 *
 * Copyright (c) 2003-2009 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class AdodbDatadictCompatDelegate
 * @ingroup db
 *
 * @brief Compatibility layer for ADODB_DataDict to avoid 3rd-party software patches
 */

// $Id$

class AdodbDatadictCompatDelegate {
	// Our data dictionary strategy
	var $adodbDict;

	// Character set support
	var $charSet = false;

	function AdodbDatadictCompatDelegate(&$adodbDict) {
		$this->adodbDict = &$adodbDict;
	}

	function _RenameColumnSQLDelegate($tabname, $oldcolumn, $newcolumn, $flds = '') {
		if ($flds) {
			return $this->adodbDict->_RenameColumnSQLUnpatched($tabname, $oldcolumn, $newcolumn, $flds);
		} else {
			return array(sprintf($this->adodbDict->renameColumn, $this->adodbDict->TableName($tabname), $this->adodbDict->NameQuote($oldcolumn), $this->adodbDict->NameQuote($newcolumn), ''));
		}
	}

	/**
	 * Functions managing the database character encoding
	 */
	function _GetCharSetDelegate() {
		return $this->charSet;
	}

	function _SetCharSetDelegate($charset_name) {
		$this->charSet = $charset_name;
	}
}
?>