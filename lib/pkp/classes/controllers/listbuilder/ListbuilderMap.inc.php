<?php

/**
 * @file classes/controllers/listbuilder/ListbuilderMap.inc.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2000-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class ListbuilderMap
 * @ingroup controllers_listbuilder
 *
 * @brief Utility class representing a simple name / value association
 */

class ListbuilderMap {
	/** @var mixed */
	var $key;

	/** @var string */
	var $value;

	/**
	 * Constructor
	 */
	function __construct($key, $value) {
		$this->key = $key;
		$this->value = $value;
	}

	/**
	 * Get the key for this map
	 * @return mixed
	 */
	function getKey() {
		return $this->key;
	}

	/**
	 * Get the value for this map
	 * @return string
	 */
	function getValue() {
		return $this->value;
	}
}

?>
