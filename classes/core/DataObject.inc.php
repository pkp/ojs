<?php

/**
 * @file DataObject.inc.php
 *
 * Copyright (c) 2003-2007 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package core
 * @class DataObject
 *
 * Abstract class for data objects. 
 * Any class with an associated DAO should extend this class.
 *
 * $Id$
 */

class DataObject {
	/** Array of object data */
	var $_data;
	
	/**
	 * Constructor.
	 */
	function DataObject($callHooks = true) {
		$this->_data = array();
	}

	function &getLocalizedData($key) {
		$localePrecedence = Locale::getLocalePrecedence();
		foreach ($localePrecedence as $locale) {
			$value =& $this->getData($key, $locale);
			if (!empty($value)) return $value;
			unset($value);
		}

		// Fallback: Get the first available piece of data.
		$data =& $this->getData($key, null);
		if (!empty($data)) return $data[array_shift(array_keys($data))];

		// No data available; return null.
		unset($data);
		$data = null;
		return $data;
	}

	/**
	 * Get the value of a data variable.
	 * @param $key string
	 * @param $locale string optional
	 * @return mixed
	 */
	function &getData($key, $locale = null) {
		if ($locale !== null) {
			if (isset($this->_data[$key][$locale])) {
				return $this->_data[$key][$locale];
			}
		} else {
			if (isset($this->_data[$key])) {
				return $this->_data[$key];
			}
		}
		$nullVar = null;
		return $nullVar;
	}
	
	/**
	 * Set the value of a new or existing data variable.
	 * @param $key string
	 * @param $locale optional
	 * @param $value mixed
	 */
	function setData($key, $value, $locale = null) {
		if ($locale !== null) {
			$this->_data[$key][$locale] = $value;
		} else {
			$this->_data[$key] = $value;
		}
	}
}

?>
