<?php

/**
 * @file classes/db/DBRowIterator.inc.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2000-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class DBRowIterator
 * @ingroup db
 *
 * @brief Wrapper around ADORecordSet providing "factory" features for generating
 * objects from DAOs.
 */


import('lib.pkp.classes.core.ItemIterator');

class DBRowIterator extends ItemIterator {
	/** The ADORecordSet to be wrapped around */
	var $records;

	/**
	 * @var array an array of primary key field names that uniquely
	 *   identify a result row in the records array.
	 */
	var $idFields;

	/** True iff the resultset was always empty */
	var $wasEmpty;

	var $isFirst;
	var $isLast;
	var $page;
	var $count;
	var $pageCount;

	/**
	 * Constructor.
	 * Initialize the DBRowIterator
	 * @param $records object ADO record set
	 * @param $dao object DAO class for factory
	 * @param $functionName The function to call on $dao to create an object
	 */
	function __construct(&$records, $idFields = array()) {
		parent::__construct();
		$this->idFields = $idFields;

		if (!$records || $records->EOF) {
			if ($records) $records->Close();
			$this->records = null;
			$this->wasEmpty = true;
			$this->page = 1;
			$this->isFirst = true;
			$this->isLast = true;
			$this->count = 0;
			$this->pageCount = 1;
		}
		else {
			$this->records =& $records;
			$this->wasEmpty = false;
			$this->page = $records->AbsolutePage();
			$this->isFirst = $records->atFirstPage();
			$this->isLast = $records->atLastPage();
			$this->count = $records->MaxRecordCount();
			$this->pageCount = $records->LastPageNo();
		}
	}

	/**
	 * Return the object representing the next row.
	 * @return object
	 */
	function &next() {
		if ($this->records == null) return $this->records;
		if (!$this->records->EOF) {
			$row = $this->records->getRowAssoc(false);
			if (!$this->records->MoveNext()) $this->close();
			return $row;
		} else {
			$this->close();
			$nullVar = null;
			return $nullVar;
		}
	}

	/**
	 * Return the next row, with key.
	 * @return array ($key, $value)
	 */
	function &nextWithKey() {
		$result =& $this->next();
		if (empty($this->idFields)) {
			$key = null;
		} else {
			assert(is_array($result) && is_array($this->idFields));
			$key = '';
			foreach($this->idFields as $idField) {
				assert(isset($result[$idField]));
				if (!empty($key)) $key .= '-';
				$key .= (string)$result[$idField];
			}
		}
		$returner = array($key, &$result);
		return $returner;
	}

	/**
	 * Determine whether this iterator represents the first page of a set.
	 * @return boolean
	 */
	function atFirstPage() {
		return $this->isFirst;
	}

	/**
	 * Determine whether this iterator represents the last page of a set.
	 * @return boolean
	 */
	function atLastPage() {
		return $this->isLast;
	}

	/**
	 * Get the page number of a set that this iterator represents.
	 * @return int
	 */
	function getPage() {
		return $this->page;
	}

	/**
	 * Get the total number of items in the set.
	 * @return int
	 */
	function getCount() {
		return $this->count;
	}

	/**
	 * Get the total number of pages in the set.
	 * @return int
	 */
	function getPageCount() {
		return $this->pageCount;
	}

	/**
	 * Return a boolean indicating whether or not we've reached the end of results
	 * @return boolean
	 */
	function eof() {
		if ($this->records == null) return true;
		if ($this->records->EOF) {
			$this->close();
			return true;
		}
		return false;
	}

	/**
	 * Return a boolean indicating whether or not this resultset was empty from the beginning
	 * @return boolean
	 */
	function wasEmpty() {
		return $this->wasEmpty;
	}

	/**
	 * Clean up the record set.
	 * This is called aggressively because it can free resources.
	 */
	function close() {
		$this->records->close();
		unset($this->records);
		$this->records = null;
	}

	/**
	 * Convert this iterator to an array.
	 * @return array
	 */
	function &toArray() {
		$returner = array();
		while (!$this->eof()) {
			$returner[] = $this->next();
		}
		return $returner;
	}
}

?>
