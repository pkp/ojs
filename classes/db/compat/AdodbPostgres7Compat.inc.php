<?php
/**
 * @file classes/db/compat/drivers/AdodbPostgres7Compat.inc.php
 *
 * Copyright (c) 2003-2009 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class AdodbPostgres7Compat
 * @ingroup db
 *
 * @brief Compatibility layer above ADOdb's postgres driver to avoid 3rd-party software patches
 */

// $Id$

import('adodb.drivers.adodb-postgres7');
import('db.compat.AdodbConnectionCompatDelegate');

class AdodbPostgres7Compat extends ADODB_postgres7 {
	var $delegate;

	function __get($name) {
		return $this->delegate->$name;
	}

	function __set($name, $value) {
		$this->delegate->$name = $value;
	}

	function AdodbPostgres7Compat() {
		$this->delegate = &new AdodbConnectionCompatDelegate($this);

		parent::ADODB_postgres7();
	}

	function &Execute($sql, $inputarr = false) {
		return $this->delegate->_ExecuteDelegate($sql, $inputarr);
	}

	function &_ExecuteUnpatched($sql, $inputarr = false) {
		return parent::Execute($sql, $inputarr);
	}

	function _query($sql, $inputarr) {
		// pg_query_params may incorrectly format
		// doubles using localized number formats, i.e.
		// , instead of . for floats, violating the
		// SQL standard. Format it locally.
		if (is_array($inputarr)) {
			$localedata = localeconv();
			foreach($inputarr as &$v) {
				if (gettype($v) == 'double') {
					$v = (string) $v;
					$v = str_replace(array($localedata['thousands_sep'], $localedata['decimal_point']), array('', '.'), $v);
				}
			}
		}
		return $this->__queryUnpatched($sql, $inputarr);
	}

	function __queryUnpatched($sql, $inputarr) {
		return parent::_query($sql, $inputarr);
	}

	function &NewDataDictionary() {
		return $this->delegate->_NewDataDictionaryDelegate('AdodbPostgres7CompatDict');
	}
}
?>