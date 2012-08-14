<?php

/**
 * @file plugins/generic/lucene/classes/SolrSearchRequest.inc.php
 *
 * Copyright (c) 2003-2012 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class SolrSearchRequest
 * @ingroup plugins_generic_lucene_classes
 *
 * @brief A value object containing all parameters of a solr search query.
 */

class SolrSearchRequest {

	/**
	 * @var Journal The journal to be queried. All journals of
	 *  an OJS instance will be queried if no journal is given.
	 */
	var $_journal = null;

	/**
	 * @var array A field->search phrase assignment defining fieldwise
	 *  search phrases.
	 */
	var $_query = array();

	/**
	 * @var integer For paginated queries: The page to be returned.
	 */
	var $_page = 1;

	/**
	 * @var integer For paginated queries: The items per page.
	 */
	var $_itemsPerPage = 25;

	/**
	 * @var string Timestamp representing the first publication date to be
	 *  included in the result set. Null means: No limitation.
	 */
	var $_fromDate = null;

	/**
	 * @var string Timestamp representing the last publication date to be
	 *  included in the result set. Null means: No limitation.
	 */
	var $_toDate = null;

	/**
	 * @var string Result set ordering. Can be any index field or the pseudo-
	 *  field "score" for ordering by relevance.
	 */
	var $_orderBy = 'score';

	/**
	 * @var boolean Result set ordering direction. Can be 'true' for ascending
	 * or 'false' for descending order.
	 */
	var $_orderDir = false;

	/**
	 * Constructor
	 *
	 * @param $searchHandler string The search handler URL. We assume the embedded server
	 *  as a default.
	 */
	function SolrSearchRequest(){
		// The constructor does nothing
	}


	//
	// Getters and Setters
	//
	/**
	 * Get the journal to be queried.
	 * @return Journal
	 */
	function &getJournal() {
		return $this->_journal;
	}

	/**
	 * Set the journal to be queried
	 * @param $journal Journal
	 */
	function setJournal(&$journal) {
		$this->_journal =& $journal;
	}

	/**
	 * Get fieldwise search phrases.
	 * @return array A field -> search phrase assignment
	 */
	function getQuery() {
		return $this->_query;
	}

	/**
	 * Set fieldwise search phrases.
	 * @param $query array A field -> search phrase assignment
	 */
	function setQuery($query) {
		$this->_query = $query;
	}

	/**
	 * Set the search phrase for a field.
	 * @param $fieldwiseQuery array
	 */
	function addQueryFieldPhrase($field, $searchPhrase) {
		$this->_query[$field] = $searchPhrase;
	}

	/**
	 * Get the page.
	 * @return integer
	 */
	function getPage() {
		return $this->_page;
	}

	/**
	 * Set the page
	 * @param $page integer
	 */
	function setPage($page) {
		$page = (int) $page;
		if ($page < 0) $page = 0;
		$this->_page = $page;
	}

	/**
	 * Get the items per page.
	 * @return integer
	 */
	function getItemsPerPage() {
		return $this->_itemsPerPage;
	}

	/**
	 * Set the items per page
	 * @param $itemsPerPage integer
	 */
	function setItemsPerPage($itemsPerPage) {
		$this->_itemsPerPage = $itemsPerPage;
	}

	/**
	 * Get the first publication date
	 * @return string
	 */
	function getFromDate() {
		return $this->_fromDate;
	}

	/**
	 * Set the first publication date
	 * @param $fromDate string
	 */
	function setFromDate($fromDate) {
		$this->_fromDate = $fromDate;
	}

	/**
	 * Get the last publication date
	 * @return string
	 */
	function getToDate() {
		return $this->_toDate;
	}

	/**
	 * Set the last publication date
	 * @param $toDate string
	 */
	function setToDate($toDate) {
		$this->_toDate = $toDate;
	}

	/**
	 * Get the result ordering criteria
	 * @return string
	 */
	function getOrderBy() {
		return $this->_orderBy;
	}

	/**
	 * Set the result ordering criteria
	 * @param $orderBy string
	 */
	function setOrderBy($orderBy) {
		$this->_orderBy = $orderBy;
	}

	/**
	 * Get the result ordering direction
	 * @return boolean
	 */
	function getOrderDir() {
		return $this->_orderDir;
	}

	/**
	 * Set the result ordering direction
	 * @param $orderDir boolean
	 */
	function setOrderDir($orderDir) {
		$this->_orderDir = $orderDir;
	}
}

?>
