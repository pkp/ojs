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

import('lib.adodb.drivers.adodb-postgres7');

class AdodbPostgres7Compat extends ADODB_postgres7 {
	function AdodbPostgres7Compat() {
		parent::ADODB_postgres7();
	}
}
?>