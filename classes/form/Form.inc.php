<?php

/**
 * Form.inc.php
 *
 * Copyright (c) 2003-2004 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package core
 *
 * Class defining basic operations for handling HTML forms.
 *
 * $Id$
 */

import('form.FormError');
import('form.validation.FormValidator');

class Form {

	/** The template file containing the HTML form */
	var $_template;
	
	/** Associative array containing form data */
	var $_data;
	
	/** Validation checks for this form */
	var $_checks;
	
	/** Errors occurring in form validation */
	var $_errors;
	
	/**
	 * Constructor.
	 * @param $template string the path to the form template file
	 */
	function Form($template) {
		$this->_template = $template;
		$this->_data = array();
		$this->_checks = array();
		$this->_errors = array();
	}
	
	/**
	 * Display the form.
	 */
	function display() {
		$templateMgr = &TemplateManager::getManager();
		$templateMgr->register_block('formLabel', array(&$this, 'smartyFormLabel'));
		
		$templateMgr->assign($this->_data);
		$templateMgr->assign('isError', !$this->isValid());
		$templateMgr->assign('errors', $this->getErrorsArray());
		
		$templateMgr->display($this->_template);
	}
	
	/**
	 * Get the value of a form field.
	 * @param $key string
	 * @return mixed
	 */
	function getData($key) {
		return isset($this->_data[$key]) ? $this->_data[$key] : null;
	}
	
	/**
	 * Set the value of a form field.
	 * @param $key
	 * @param $value
	 */
	function setData($key, $value) {
		$this->_data[$key] = $value;
	}
	
	/**
	 * Initialize form data for a new form.
	 */
	function initData() {
	}
	
	/**
	 * Assign form data to user-submitted data.
	 */
	function readInputData() {
	}
	
	/**
	 * Validate form data.
	 */
	function validate() {
		foreach ($this->_checks as $check) {
			if (!$check->isValid()) {
				$this->addError($check->getField(), $check->getMessage());
			}
		}
		return $this->isValid();
	}
	
	/**
	 * Execute the form's action.
	 * (Note that it is assumed that the form has already been validated.)
	 */
	function execute() {
	}
	
	/**
	 * Adds specified user variables to input data. 
	 * @param $vars array the names of the variables to read
	 */
	function readUserVars($vars) {
		foreach ($vars as $k) {
			$this->setData($k, Request::getUserVar($k));
		}
	}
	
	/**
	 * Add a validation check to the form.
	 * @param $formValidator FormValidator
	 */
	function addCheck($formValidator) {
		$this->_checks[] = &$formValidator;
	}
	
	/**
	 * Add an error to the form.
	 * Errors are typically assigned as the form is validated.
	 * @param $field string the name of the field where the error occurred
	 * @param $message string the error message (i18n key)
	 */
	function addError($field, $message) {
		$this->_errors[] = &new FormError($field, $message);
	}
	
	/**
	 * Check if form passes all validation checks.
	 * @return boolean
	 */
	function isValid() {
		return empty($this->_errors);
	}
	
	/**
	 * Return set of errors that occurred in form validation.
	 * @return array erroneous fields and associated error messages
	 */
	function getErrorsArray() {
		$errorsArray = array();
		foreach ($this->_errors as $error) {
			if (!isset($errorsArray[$error->getField()])) {
				$errorsArray[$error->getField()] = $error->getMessage();
			}
		}
		return $errorsArray;
	}
	
	/**
	 * Custom Smarty block for handling highlighting of fields with error input.
	 * @param $params array associative array, must contain "name" parameter for name of field
	 * @param $content string the label for the form field
	 * @param $smarty Smarty
	 */
	function smartyFormLabel($params, $content, &$smarty) {
		static $errorsArray;
		if (!isset($errorsArray)) {
			$errorsArray = $this->getErrorsArray();
		}
		
		if (isset($content) && !empty($content)) {
			if (!empty($params) && isset($params['name']) && !empty($params['name']) && isset($errorsArray[$params['name']])) {
				echo '<span class="formLabelError">', $content, '</span>';
				
			} else {
				echo $content;
			}
		}
	}
}

?>
