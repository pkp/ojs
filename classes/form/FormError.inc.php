<?php

/**
 * @file FormError.inc.php
 *
 * Copyright (c) 2003-2007 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package form
 * @class FormError
 *
 * Class to represent a form validation error.
 *
 * $Id$
 */

class FormError {

	/** The name of the field */
	var $field;
	
	/** The error message */
	var $message;
	
	/**
	 * Constructor.
	 * @param $field string the name of the field
	 * @param $message string the error message (i18n key)
	 */
	function FormError($field, $message) {
		$this->field = $field;
		$this->message = $message;
	}
	
	/**
	 * Get the field associated with the error.
	 * @return string
	 */
	function getField() {
		return $this->field;
	}
	
	/**
	 * Get the error message (i18n key).
	 * @return string
	 */
	function getMessage() {
		return $this->message;
	}
	
}

?>
