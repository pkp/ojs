<?php

/**
 * DataObject.inc.php
 *
 * Copyright (c) 2003-2004 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package core
 *
 * Abstract class for data objects. 
 * Any class with an associated DAO should extend this class.
 *
 * $Id$
 */

class DataObject {

	/** Array of object data */
	var $_data;
	
	/** Fields modified since object creation */
	/* var $_modified_data; */
	
	/**
	 * Constructor.
	 */
	function DataObject() {
		$this->_data = array();
		/*
		$this->_modified_data = array();
		*/
	}
	
	/**
	 * Get the value of a data variable.
	 * @param $key string
	 * @return mixed
	 */
	function getData($key) {
		if (isset($this->_data[$key])) {
			return $this->_data[$key];
		}
		return null;
	}
	
	/**
	 * Set the value of a new or existing data variable.
	 * @param $key string
	 * @param $value mixed
	 */
	function setData($key, $value) {
		$this->_data[$key] = $value;
		/*
		if (!in_array($key, $this->_modified_data))
		{
			$this->_modified_data[] = $key;
		}
		*/
	}
	
	/* Unused. Might be useful to keep track of modified fields for more efficient update operations?
	function resetModifiedData() {
		$this->_modified_data = array();
	}
	
	function isModifiedData($key) {
		return in_array($key, $this->_modified_data);
	}
	*/
	
}

?>
