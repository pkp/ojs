<?php

/**
 * Iterator.inc.php
 *
 * Copyright (c) 2003-2005 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package db
 *
 * Generic Iterator class; needs to be overloaded by subclasses
 * providing specific implementations.
 *
 * $Id$
 */

class Iterator {
	/**
	 * Return the next item in the iterator.
	 * @return object
	 */
	function &next() {
		return null;
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
