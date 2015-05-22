<?php

/**
 * @defgroup plugins_metadata_dc11_schema
 */

/**
 * @file plugins/metadata/dc11/schema/Dc11Schema.inc.php
 *
 * Copyright (c) 2013-2015 Simon Fraser University Library
 * Copyright (c) 2000-2015 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class Dc11Schema
 * @ingroup plugins_metadata_dc11_schema
 * @see PKPDc11Schema
 *
 * @brief OJS-specific implementation of the Dc11Schema.
 */


import('lib.pkp.plugins.metadata.dc11.schema.PKPDc11Schema');

class Dc11Schema extends PKPDc11Schema {
	/**
	 * Constructor
	 */
	function Dc11Schema() {
		// Configure the MODS schema.
		parent::PKPDc11Schema(ASSOC_TYPE_ARTICLE);
	}
}
?>
