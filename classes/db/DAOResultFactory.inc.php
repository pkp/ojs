<?php

/**
 * DAOResultFactory.inc.php
 *
 * Copyright (c) 2003-2005 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package db
 *
 * Wrapper around ADORecordSet providing "factory" features for generating 
 * objects from DAOs.
 *
 * $Id$
 */

import('core.ItemIterator');

class DAOResultFactory extends ItemIterator {
	/** The DAO used to create objects */
	var $dao;

	/** The name of the DAO's factory function (to be called with an associative array of values) */
	var $functionName;

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
	 * Initialize the DAOResultFactory
	 * @param $records object ADO record set
	 * @param $dao object DAO class for factory
	 * @param $functionName The function to call on $dao to create an object
	 */
	function DAOResultFactory(&$records, &$dao, $functionName) {
		$this->functionName = $functionName;
		$this->dao = &$dao;

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
			$functionName = &$this->functionName;
			$dao = &$this->dao;
			$row = &$this->records->getRowAssoc(false);
			$result = &$dao->$functionName($row);
			if (!$this->records->MoveNext()) $this->_cleanup();
			return $result;
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
