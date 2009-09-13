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

import('lib.adodb.drivers.adodb-mysql');

class AdodbMysqlCompat extends ADODB_mysql {
	function AdodbMysqlCompat() {
		parent::ADODB_mysql();
	}
}
?>