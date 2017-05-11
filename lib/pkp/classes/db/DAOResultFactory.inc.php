<?php

/**
 * @file classes/db/DAOResultFactory.inc.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2000-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class DAOResultFactory
 * @ingroup db
 *
 * @brief Wrapper around ADORecordSet providing "factory" features for generating
 * objects from DAOs.
 */


import('lib.pkp.classes.core.ItemIterator');

class DAOResultFactory extends ItemIterator {
	/** @var DAO The DAO used to create objects */
	var $dao;

	/** @var string The name of the DAO's factory function (to be called with an associative array of values) */
	var $functionName;

	/**
	 * @var array an array of primary key field names that uniquely
	 *   identify a result row in the record set.
	 */
	var $idFields;

	/** @var ADORecordSet The ADORecordSet to be wrapped around */
	var $records;

	/** @var boolean True iff the resultset was always empty */
	var $wasEmpty;

	var $isFirst;
	var $isLast;
	var $page;
	var $count;
	var $pageCount;

	/**
	 * Constructor.
	 * Initialize the DAOResultFactory
	 * @param $records object ADO record set
	 * @param $dao object DAO class for factory
	 * @param $functionName The function to call on $dao to create an object
	 * @param $idFields array an array of primary key field names that uniquely
	 *  identify a result row in the record set.
	 *  Should be data object _data array key, not database column name
	 */
	function __construct(&$records, &$dao, $functionName, $idFields = array()) {
		parent::__construct();
		$this->functionName = $functionName;
		$this->dao =& $dao;
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
		} else {
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
	 * Advances the internal cursor to a specific row.
	 * @param int $to
	 * @return boolean
	 */
	function move($to) {
		if ($this->records == null) return false;
		if ($this->records->Move($to))
			return true;
		else
			return false;
	}

	/**
	 * Return the object representing the next row.
	 * @return object
	 */
	function &next() {
		if ($this->records == null) return $this->records;
		if (!$this->records->EOF) {
			$functionName = $this->functionName;
			$dao =& $this->dao;
			$row = $this->records->getRowAssoc(false);
			$result = $dao->$functionName($row);
			if (!$this->records->MoveNext()) $this->close();
			return $result;
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
	function nextWithKey($idField = null) {
		$result =& $this->next();
		if($idField) {
			assert(is_a($result, 'DataObject'));
			$key = $result->getData($idField);
		} elseif (empty($this->idFields)) {
			$key = null;
		} else {
			assert(is_a($result, 'DataObject') && is_array($this->idFields));
			$key = '';
			foreach($this->idFields as $idField) {
				assert(!is_null($result->getData($idField)));
				if (!empty($key)) $key .= '-';
				$key .= (string)$result->getData($idField);
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
		if ($this->records) {
			$this->records->close();
			unset($this->records);
			$this->records = null;
		}
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

	/**
	 * Convert this iterator to an associative array by database ID.
	 * @return array
	 */
	function &toAssociativeArray($idField = 'id') {
		$returner = array();
		while (!$this->eof()) {
			$result =& $this->next();
			$returner[$result->getData($idField)] =& $result;
			unset($result);
		}
		return $returner;
	}

	/**
	 * Determine whether or not this iterator is in the range of pages for the set it represents
	 * @return boolean
	 */
	function isInBounds() {
		return ($this->pageCount >= $this->page);
	}

	/**
	 * Get the RangeInfo representing the last page in the set.
	 * @return object
	 */
	function &getLastPageRangeInfo() {
		import('lib.pkp.classes.db.DBResultRange');
		$returner = new DBResultRange($this->count, $this->pageCount);
		return $returner;
	}
}

?>
