<?php

/**
 * @file plugins/importexport/native/filter/AuthorNativeXmlFilter.inc.php
 *
 * Copyright (c) 2014-2019 Simon Fraser University
 * Copyright (c) 2000-2019 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class AuthorNativeXmlFilter
 * @ingroup plugins_importexport_native
 *
 * @brief Class that converts a Author to a Native XML document.
 */

import('lib.pkp.plugins.importexport.native.filter.PKPAuthorNativeXmlFilter');

class AuthorNativeXmlFilter extends PKPAuthorNativeXmlFilter {
	/**
	 * Constructor
	 * @param $filterGroup FilterGroup
	 */
	function __construct($filterGroup) {
		parent::__construct($filterGroup);
	}


	//
	// Implement template methods from PersistableFilter
	//
	/**
	 * @copydoc PersistableFilter::getClassName()
	 */
	function getClassName() {
		return 'plugins.importexport.native.filter.AuthorNativeXmlFilter';
	}
}


