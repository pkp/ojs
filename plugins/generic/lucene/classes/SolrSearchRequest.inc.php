<?php

/**
 * @file plugins/generic/lucene/classes/SolrSearchRequest.inc.php
 *
 * Copyright (c) 2014-2018 Simon Fraser University
 * Copyright (c) 2003-2018 John Willinsky
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
	 * @var array An array of unique IDs to exclude.
	 */
	var $_excludedIds = array();

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
	 * @var boolean Whether to enable spell checking.
	 */
	var $_spellcheck = false;

	/**
	 * @var boolean Whether to enable highlighting.
	 */
	var $_highlighting = false;

	/**
	 * @var boolean Enabled facet categories (none by default).
	 */
	var $_facetCategories = array();

	/**
	 * @var array A field->value->boost factor assignment.
	 */
	var $_boostFactors = array();

	/**
	 * @var array Fields with multiplicative boost values.
	 */
	var $_boostFields = array();

	/**
	 * Constructor
	 *
	 * @param $searchHandler string The search handler URL. We assume the embedded server
	 *  as a default.
	 */
	function __construct(){
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
	 * Set a list of unique IDs to exclude from the search result.
	 * @param $excludedIds array
	 */
	function setExcludedIds($excludedIds) {
		$this->_excludedIds = $excludedIds;
	}

	/**
	 * Get the list of excluded unique IDs.
	 * @return array
	 */
	function getExcludedIds() {
		return $this->_excludedIds;
	}

	/**
	 * Set the search phrase for a field.
	 * @param $field string
	 * @param $searchPhrase string
	 */
	function addQueryFieldPhrase($field, $searchPhrase) {
		// Ignore empty search phrases.
		if (empty($searchPhrase)) return;
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

	/**
	 * Is spellchecking enabled?
	 * @return boolean
	 */
	function getSpellcheck() {
		return $this->_spellcheck;
	}

	/**
	 * Set whether spellchecking should be enabled.
	 * @param $spellcheck boolean
	 */
	function setSpellcheck($spellcheck) {
		$this->_spellcheck = $spellcheck;
	}

	/**
	 * Is highlighting enabled?
	 * @return boolean
	 */
	function getHighlighting() {
		return $this->_highlighting;
	}

	/**
	 * Set whether highlighting should be enabled.
	 * @param $highlighting boolean
	 */
	function setHighlighting($highlighting) {
		$this->_highlighting = $highlighting;
	}

	/**
	 * For which categories should faceting
	 * be enabled?
	 * @return array
	 */
	function getFacetCategories() {
		return $this->_facetCategories;
	}

	/**
	 * Set the categories for which faceting
	 * should be enabled.
	 * @param $facetCategories boolean
	 */
	function setFacetCategories($facetCategories) {
		$this->_facetCategories = $facetCategories;
	}

	/**
	 * Get boost factors.
	 * @return array A field -> value -> boost factor assignment
	 */
	function getBoostFactors() {
		return $this->_boostFactors;
	}

	/**
	 * Set boost factors.
	 * @param $boostQuery array A field -> value -> boost factor assignment
	 */
	function setBoostFactors($boostFactors) {
		$this->_boostFactors = $boostFactors;
	}

	/**
	 * Set the boost factor for a field/value combination.
	 * @param $field string
	 * @param $value string
	 * @param $boostFactor float
	 */
	function addBoostFactor($field, $value, $boostFactor) {
		// Ignore empty values.
		if (empty($value)) return;

		// Ignore neutral boost factors.
		$boostFactor = (float)$boostFactor;
		if ($boostFactor == 1.0) return;

		// Save the boost factor.
		if (!isset($this->_boostFactors[$field])) {
			$this->_boostFactors[$field] = array();
		}
		$this->_boostFactors[$field][$value] = $boostFactor;
	}

	/**
	 * Get boost fields.
	 * @return array A list of fields containing boost factors to be multiplied
	 *   with the internal ranking score.
	 */
	function getBoostFields() {
		return $this->_boostFields;
	}

	/**
	 * Set boost fields.
	 * @param $boostFields array A list of fields containing boost factors to
	 *   be multiplied with the internal ranking score.
	 */
	function setBoostFields($boostFields) {
		$this->_boostFields = $boostFields;
	}

	/**
	 * A field containing boost factors to be multiplied.
	 * with the internal ranking score.
	 * @param $field string
	 */
	function addBoostField($field) {
		$this->_boostFields[] = $field;
	}


	//
	// Public methods
	//
	/**
	 * Configure the search request from a keywords
	 * array as required by SubmissionSearch::retrieveResults()
	 *
	 * @param $keywords array See SubmissionSearch::retrieveResults()
	 */
	function addQueryFromKeywords($keywords) {
		// Get a mapping of OJS search fields bitmaps to index fields.
		$articleSearch = new ArticleSearch();
		$indexFieldMap = $articleSearch->getIndexFieldMap();

		// The keywords list is indexed with a search field bitmap.
		foreach($keywords as $searchFieldBitmap => $searchPhrase) {
			// Translate the search field from OJS to solr nomenclature.
			if (empty($searchFieldBitmap)) {
				// An empty search field means "all fields".
				$solrFields = array_values($indexFieldMap);
			} else {
				$solrFields = array();
				foreach($indexFieldMap as $ojsField => $solrField) {
					// The search field bitmap may stand for
					// several actual index fields (e.g. the index terms
					// field).
					if ($searchFieldBitmap & $ojsField) {
						$solrFields[] = $solrField;
					}
				}
			}
			$solrFieldString = implode('|', $solrFields);
			$this->addQueryFieldPhrase($solrFieldString, $searchPhrase);
		}
	}
}


