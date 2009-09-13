<?php
/**
 * @file classes/db/compat/drivers/AdodbMysqlCompat.inc.php
 *
 * Copyright (c) 2003-2009 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class AdodbMysqlCompat
 * @ingroup db
 *
 * @brief Compatibility layer above ADOdb's mysql driver to avoid 3rd-party software patches
 */

// $Id$

import('adodb.drivers.adodb-mysql');
import('db.compat.AdodbConnectionCompatDelegate');

class AdodbMysqlCompat extends ADODB_mysql {
	var $delegate;

	function __get($name) {
		return $this->delegate->$name;
	}

	function __set($name, $value) {
		$this->delegate->$name = $value;
	}

	function AdodbMysqlCompat() {
		$this->delegate = &new AdodbConnectionCompatDelegate($this);

		parent::ADODB_mysql();
	}

	function &Execute($sql, $inputarr = false) {
		return $this->delegate->_ExecuteDelegate($sql, $inputarr);
	}

	function &_ExecuteUnpatched($sql, $inputarr = false) {
		return parent::Execute($sql, $inputarr);
	}

	function &NewDataDictionary() {
		return $this->delegate->_NewDataDictionaryDelegate('AdodbMysqlCompatDict');
	}

	/*
	 * Functions for managing client encoding
	 */
	function GetCharSet() {
		$result = $this->Query("SHOW VARIABLES LIKE 'character_set_client'");
		if ($result) {
			$result = &$result->GetAssoc(false, true);
			$this->charSet = $result['character_set_client'];
		} else {
			$this->charSet = false;
		}

		return $this->charSet;
	}

	function SetCharSet($charset_name) {
		if ($this->Execute('SET NAMES ?', array($charset_name))) {
			$this->charSet = $charset_name;
			return true;
		} else {
			return false;
		}
	}
}
?>