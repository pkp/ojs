<?php

/**
 * @defgroup plugins_citationLookup_isbndb
 */

/**
 * @file plugins/citationLookup/isbndb/IsbndbCitationLookupPlugin.inc.php
 *
 * Copyright (c) 2013-2015 Simon Fraser University Library
 * Copyright (c) 2003-2015 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class IsbndbCitationLookupPlugin
 * @ingroup plugins_citationLookup_isbndb
 *
 * @brief ISBNdb citation database connector plug-in.
 */


import('lib.pkp.plugins.citationLookup.isbndb.PKPIsbndbCitationLookupPlugin');

class IsbndbCitationLookupPlugin extends PKPIsbndbCitationLookupPlugin {
	/**
	 * Constructor
	 */
	function IsbndbCitationLookupPlugin() {
		parent::PKPIsbndbCitationLookupPlugin();
	}
}

?>
