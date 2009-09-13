<?php
/**
 * @file classes/db/compat/drivers/AdodbXmlschemaCompat.inc.php
 *
 * Copyright (c) 2003-2009 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class AdodbXmlschemaCompat
 * @ingroup db
 *
 * @brief Compatibility layer above ADOdb's xmlschema to avoid 3rd-party software patches
 */

// $Id$

import('adodb.adodb-xmlschema');

class AdodbXmlschemaCompat extends adoSchema {
	function AdodbXmlschemaCompat(&$db, $charSet = false) {
		parent::adoSchema($db);

		// User our own implementation of NewDataDictionary to
		// get the compatibility layer in between.
		$this->dict = $this->db->NewDataDictionary();
		if ($charSet) {
			$this->dict->SetCharSet( $charSet);
		}
	}
}
?>
