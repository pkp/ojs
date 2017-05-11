<?php

/**
 * @defgroup plugins_citationLookup_isbndb_filter ISBNDB Filter Plugin
 */

/**
 * @file plugins/citationLookup/isbndb/filter/IsbndbNlm30CitationSchemaFilter.inc.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2000-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class IsbndbNlm30CitationSchemaFilter
 * @ingroup plugins_citationLookup_isbndb_filter
 *
 * @brief Abstract filter that wraps the ISBNdb web service.
 */


define('ISBNDB_WEBSERVICE_URL', 'http://isbndb.com/api/books.xml');

import('lib.pkp.plugins.metadata.nlm30.filter.Nlm30CitationSchemaFilter');
import('lib.pkp.classes.filter.FilterSetting');

class IsbndbNlm30CitationSchemaFilter extends Nlm30CitationSchemaFilter {
	/*
	 * Constructor
	 * @param $filterGroup FilterGroup
	 */
	function __construct($filterGroup) {
		// Instantiate the settings of this filter
		$apiKeySetting = new FilterSetting('apiKey',
				'metadata.filters.isbndb.settings.apiKey.displayName',
				'metadata.filters.isbndb.settings.apiKey.validationMessage');
		$this->addSetting($apiKeySetting);

		parent::__construct($filterGroup, array(NLM30_PUBLICATION_TYPE_BOOK));
	}

	//
	// Getters and Setters
	//
	/**
	 * Get the apiKey
	 * @return string
	 */
	function getApiKey() {
		return $this->getData('apiKey');
	}

	//
	// Protected helper methods
	//
	/**
	 * Checks whether the given string is an ISBN.
	 * @param $isbn
	 * @return boolean
	 */
	function isValidIsbn($isbn) {
		return is_string($isbn) && is_numeric($isbn) && PKPString::strlen($isbn) == 13;
	}
}
?>
