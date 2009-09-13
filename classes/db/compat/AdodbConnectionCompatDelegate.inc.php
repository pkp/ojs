<?php
/**
 * @file classes/db/compat/drivers/AdodbConnectionCompatDelegate.inc.php
 *
 * Copyright (c) 2003-2009 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class AdodbConnectionCompatDelegate
 * @ingroup db
 *
 * @brief Compatibility layer for ADODBConnection to avoid 3rd-party software patches
 */

// $Id$

class AdodbConnectionCompatDelegate {
	// Our database access strategy
	var $adodbConnection;

	// Counts queries on execute
	var $numQueries = 0;

	function AdodbConnectionCompatDelegate(&$adodbConnection) {
		$this->adodbConnection = &$adodbConnection;
	}

	function &_ExecuteDelegate($sql, $inputarr = false) {
		$this->numQueries++;
		return $this->adodbConnection->_ExecuteUnpatched($sql, $inputarr);
	}

	function &_NewDataDictionaryDelegate($driver) {
		import('db.compat.' . $driver);

		$dict = new $driver();

		$dict->dataProvider = $this->adodbConnection->dataProvider;
		$dict->connection = &$this->adodbConnection;
		$dict->upperName = strtoupper($driver);
		$dict->quote = $this->adodbConnection->nameQuote;
		if (!empty($this->adodbConnection->_connectionID))
			$dict->serverInfo = $this->adodbConnection->ServerInfo();

		return $dict;
	}
}
?>