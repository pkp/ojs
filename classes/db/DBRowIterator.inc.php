<?php

/**
 * @file classes/db/DBRowIterator.inc.php
 *
 * Copyright (c) 2003-2008 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class DBRowIterator
 * @ingroup db
 *
 * @brief Wrapper around ADORecordSet providing "factory" features for generating 
 * objects from DAOs.
 */

// $Id$


import('core.ItemIterator');

class DBRowIterator extends ItemIterator {
	/** The ADORecordSet to be wrapped around */
	var $records;

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
	function DBRowIterator(&$records) {
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
			$this->records = &$records;
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
			$row = &$this->records->getRowAssoc(false);
			if (!$this->records->MoveNext()) $this->_cleanup();
			return $row;
		} else {
			$this->_cleanup();
			$nullVar = null;
			return $nullVar;
		}
	}

	/** Return the next row, with key.
	 * @return array ($key, $value)
	 */
	function &nextWithKey() {
		// We don't have keys with rows. (Row numbers might become
		// valuable at some point.)
		return array(null, $this->next());
	}

	function atFirstPage() {
		return $this->isFirst;
	}

	function atLastPage() {
		return $this->isLast;
	}

	function getPage() {
		return $this->page;
	}

	function getCount() {
		return $this->count;
	}

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
			$this->_cleanup();
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
	 * PRIVATE function used internally to clean up the record set.
	 * This is called aggressively because it can free resources.
	 */
	function _cleanup() {
		$this->records->close();
		unset($this->records);
		$this->records = null;
	}

	function &toArray() {
		$returner = array();
		while (!$this->eof()) {
			$returner[] = $this->next();
		}
		return $returner;
	}
}

?>
