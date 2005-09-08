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
	
	/** Array of field names where an error occurred and the associated error message */
	var $errorsArray;
	
	/** Array of field names where an error occurred */
	var $errorFields;
	
	/**
	 * Constructor.
	 * @param $template string the path to the form template file
	 */
	function Form($template) {
		$this->_template = $template;
		$this->_data = array();
		$this->_checks = array();
		$this->_errors = array();
		$this->errorsArray = array();
		$this->errorFields = array();
	}
	
	/**
	 * Display the form.
	 */
	function display() {
		$templateMgr = &TemplateManager::getManager();
		$templateMgr->register_function('fieldLabel', array(&$this, 'smartyFieldLabel'));
		
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
		if (!isset($this->errorsArray)) {
			$this->getErrorsArray();
		}
		
		foreach ($this->_checks as $check) {
			if (!isset($this->errorsArray[$check->getField()]) && !$check->isValid()) {
				$this->addError($check->getField(), $check->getMessage());
				$this->errorFields[$check->getField()] = 1;
				
				if (method_exists($check, 'getErrorFields')) {
					$errorFields = call_user_func(array(&$check, 'getErrorFields'));
					for ($i=0, $count=count($errorFields); $i < $count; $i++) {
						$this->errorFields[$errorFields[$i]] = 1;
					}
				}
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
	 * Add an error field for highlighting on form
	 * @param $field string the name of the field where the error occurred
	 */
	function addErrorField($field) {
		$this->errorFields[$field] = 1;
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
	 * If multiple errors occurred processing a single field, only the first error is included.
	 * @return array erroneous fields and associated error messages
	 */
	function getErrorsArray() {
		$this->errorsArray = array();
		foreach ($this->_errors as $error) {
			if (!isset($this->errorsArray[$error->getField()])) {
				$this->errorsArray[$error->getField()] = $error->getMessage();
			}
		}
		return $this->errorsArray;
	}
	
	/**
	 * Custom Smarty function for labelling/highlighting of form fields.
	 * @param $params array can contain 'name' (field name/ID), 'required' (required field), 'key' (localization key), 'label' (non-localized label string), 'suppressId' (boolean)
	 * @param $smarty Smarty
	 */
	function smartyFieldLabel($params, &$smarty) {
		if (isset($params) && !empty($params)) {
			if (isset($params['key'])) {
				$params['label'] = Locale::translate($params['key']);
			}
			
			if (isset($this->errorFields[$params['name']])) {
				$class = ' class="error"';
			} else {
				$class = '';	
			}
			echo '<label' . (isset($params['suppressId']) ? '' : ' for="' . $params['name'] . '"'), $class, '>', $params['label'], (isset($params['required']) && !empty($params['required']) ? ' *' : ''), '</label>';
		}
	}
}

?>
