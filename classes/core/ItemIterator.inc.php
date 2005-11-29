<?php

/**
 * ItemIterator.inc.php
 *
 * Copyright (c) 2003-2005 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package db
 *
 * Generic iterator class; needs to be overloaded by subclasses
 * providing specific implementations.
 *
 * $Id$
 */

class ItemIterator {
	/**
	 * Return the next item in the iterator.
	 * @return object
	 */
	function &next() {
		$nullVar = null;
		return $nullVar;
	}

	/**
	 * Return the next item with key.
	 * @return array ($key, $value);
	function &nextWithKey() {
		return array(null, null);
	}

	function atFirstPage() {
		return true;
	}

	function atLastPage() {
		return true;
	}

	function getPage() {
		return 1;
	}

	function getCount() {
		return 0;
	}

	function getPageCount() {
		return 0;
	}

	/**
	 * Return a boolean indicating whether or not we've reached the end of results
	 * @return boolean
	 */
	function eof() {
		return true;
	}

	/**
	 * Return a boolean indicating whether or not this iterator was empty from the beginning
	 * @return boolean
	 */
	function wasEmpty() {
		return true;
	}

	function &toArray() {
		return array();
	}
}

?>
