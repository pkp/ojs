<?php

/**
 * @file plugins/citationLookup/isbndb/filter/IsbndbNlm30CitationSchemaIsbnFilter.inc.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2000-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class IsbndbNlm30CitationSchemaIsbnFilter
 * @ingroup plugins_citationLookup_isbndb_filter
 *
 * @brief Filter that uses the ISBNdb web
 *  service to identify an ISBN for a given citation.
 */


import('lib.pkp.plugins.citationLookup.isbndb.filter.IsbndbNlm30CitationSchemaFilter');

class IsbndbNlm30CitationSchemaIsbnFilter extends IsbndbNlm30CitationSchemaFilter {
	/*
	 * Constructor
	 * @param $filterGroup FilterGroup
	 */
	function __construct($filterGroup) {
		$this->setDisplayName('ISBNdb (from NLM)');

		parent::__construct($filterGroup);
	}

	//
	// Implement template methods from PersistableFilter
	//
	/**
	 * @copydoc PersistableFilter::getClassName()
	 */
	function getClassName() {
		return 'lib.pkp.plugins.citationLookup.isbndb.filter.IsbndbNlm30CitationSchemaIsbnFilter';
	}


	//
	// Implement template methods from Filter
	//
	/**
	 * @copydoc Filter::supports()
	 * @param $input mixed
	 * @param $output mixed
	 * @return boolean
	 */
	function supports(&$input, &$output) {
		if (!(is_null($output) || $this->isValidIsbn($output))) return false;
		return parent::supports($input, $output, false, true);
	}

	/**
	 * @copydoc Filter::process()
	 * @param $citationDescription MetadataDescription
	 * @return string an ISBN or null
	 */
	function &process(&$citationDescription) {
		$nullVar = null;

		// Get the search strings
		$searchTemplates =& $this->_getSearchTemplates();
		$searchStrings = $this->constructSearchStrings($searchTemplates, $citationDescription);

		// Run the searches, in order, until we have a result
		$searchParams = array(
			'access_key' => $this->getApiKey(),
			'index1' => 'combined'
		);
		foreach ($searchStrings as $searchString) {
			$searchParams['value1'] = $searchString;
			if (is_null($resultDOM =& $this->callWebService(ISBNDB_WEBSERVICE_URL, $searchParams))) return $nullVar;

			// Did we get a search hit?
			$numResults = '';
			$bookList = $resultDOM->getElementsByTagName('BookList');
			if (is_a($bookList, 'DOMNodeList')) {
				$bookListFirstItem =& $bookList->item(0);
				if (is_a($bookListFirstItem, 'DOMNode')) {
					$numResults = $bookListFirstItem->getAttribute('total_results');
				}
			}
			if (!empty($numResults)) break;
		}

		// Retrieve the first search hit
		$bookDataNodes = $resultDOM->getElementsByTagName('BookData');
		$bookDataFirstNode = null;
		if (is_a($bookDataNodes, 'DOMNodeList')) {
			$bookDataFirstNode =& $bookDataNodes->item(0);
		}

		// If no book data present, then abort (this includes no search result at all)
		if (is_null($bookDataFirstNode)) return $nullVar;

		$isbn = $bookDataFirstNode->getAttribute('isbn13');

		// If we have no ISBN then abort
		if (empty($isbn)) return $nullVar;

		return $isbn;
	}

	//
	// Private methods
	//
	/**
	 * Return an array of search templates.
	 * @return array
	 */
	function &_getSearchTemplates() {
		$searchTemplates = array(
			'%au% %title% %date%',
			'%aulast% %title% %date%',
			'%au% %title% c%date%',
			'%aulast% %title% c%date%',
			'%au% %title%',
			'%aulast% %title%',
			'%title% %date%',
			'%title% c%date%',
			'%au% %date%',
			'%aulast% %date%',
			'%au% c%date%',
			'%aulast% c%date%'
		);
		return $searchTemplates;
	}
}
?>
