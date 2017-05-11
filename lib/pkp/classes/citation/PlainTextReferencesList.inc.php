<?php

/**
 * @file classes/citation/PlainTextReferencesList.inc.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2000-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class PlainTextReferencesList
 * @ingroup citation
 *
 * @brief Class representing an ordered list of plain text citation output.
 */


define('REFERENCES_LIST_ORDERING_NUMERICAL', 0x01);
define('REFERENCES_LIST_ORDERING_ALPHABETICAL', 0x02);

class PlainTextReferencesList {
	/** @var integer one of the REFERENCES_LIST_ORDERING_* constants */
	var $_ordering;

	/** @var string the actual list */
	var $_listContent;

	/**
	 * Constructor.
	 * @param $listContent string
	 * @param $ordering integer one of the REFERENCES_LIST_ORDERING_* constants
	 */
	function __construct($listContent, $ordering) {
		$this->_listContent = $listContent;
		$this->_ordering = $ordering;
	}


	//
	// Getters and Setters
	//
	/**
	 * Set the list content
	 * @param $listContent string
	 */
	function setListContent(&$listContent) {
		$this->_listContent =& $listContent;
	}

	/**
	 * Get the list content
	 * @return string
	 */
	function &getListContent() {
		return $this->_listContent;
	}

	/**
	 * Set the ordering
	 * @param $ordering integer
	 */
	function setOrdering($ordering) {
		$this->_ordering = $ordering;
	}

	/**
	 * Get the ordering
	 * @return integer
	 */
	function getOrdering() {
		return $this->_ordering;
	}

}
?>
