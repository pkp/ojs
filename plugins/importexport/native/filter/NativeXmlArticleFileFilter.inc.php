<?php

/**
 * @file plugins/importexport/native/filter/NativeXmlArticleFileFilter.inc.php
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2000-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class NativeXmlArticleFileFilter
 * @ingroup plugins_importexport_native
 *
 * @brief Class that converts a Native XML document to an article file.
 */

import('lib.pkp.plugins.importexport.native.filter.NativeXmlSubmissionFileFilter');

class NativeXmlArticleFileFilter extends NativeXmlSubmissionFileFilter {
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
		return 'plugins.importexport.native.filter.NativeXmlArticleFileFilter';
	}
}


