<?php

/**
 * @file Form.inc.php
 *
 * Copyright (c) 2003-2007 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package core
 * @class Form
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
		$templateMgr->setCacheability(CACHEABILITY_NO_STORE);
		$templateMgr->register_function('fieldLabel', array(&$this, 'smartyFieldLabel'));
		$templateMgr->register_function('form_language_chooser', array(&$this, 'smartyFormLanguageChooser'));
		
		$templateMgr->assign($this->_data);
		$templateMgr->assign('isError', !$this->isValid());
		$templateMgr->assign('errors', $this->getErrorsArray());

		$templateMgr->assign('formLocales', Locale::getSupportedLocales());

		// Determine the current locale to display fields with
		$formLocale = Request::getUserVar('formLocale');
		if (empty($formLocale) || !in_array($formLocale, array_keys(Locale::getAllLocales()))) {
			$formLocale = Locale::getLocale();
		}
		$templateMgr->assign('formLocale', $formLocale);
		
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

		if (is_string($value)) $value = Core::cleanVar($value);

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
				if (method_exists($check, 'getErrorFields') && method_exists($check, 'isArray') && call_user_func(array(&$check, 'isArray'))) {
					$errorFields = call_user_func(array(&$check, 'getErrorFields'));
					for ($i=0, $count=count($errorFields); $i < $count; $i++) {
						$this->addError($errorFields[$i], $check->getMessage());
						$this->errorFields[$errorFields[$i]] = 1;
					}
				} else {
					$this->addError($check->getField(), $check->getMessage());
					$this->errorFields[$check->getField()] = 1;
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
	 * Get the list of field names that need to support multiple locales
	 * @return array
	 */
	function getLocaleFieldNames() {
		return array();
	}

	/**
	 * Determine whether or not the current request results from a resubmit
	 * of locale data resulting from a form language change.
	 * @return boolean
	 */
	function isLocaleResubmit() {
		$formLocale = Request::getUserVar('formLocale');
		return (!empty($formLocale));
	}

	/**
	 * Get the current form locale.
	 * @return string
	 */
	function getFormLocale() {
		$formLocale = Request::getUserVar('formLocale');
		if (empty($formLocale)) $formLocale = Locale::getLocale();
		return $formLocale;
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
			echo '<label' . (isset($params['suppressId']) ? '' : ' for="' . $params['name'] . '"'), $class, '>', $params['label'], (isset($params['required']) && !empty($params['required']) ? '*' : ''), '</label>';
		}
	}

	function _decomposeArray($name, $value, $stack) {
		if (is_array($value)) {
			foreach ($value as $key => $subValue) {
				$newStack = $stack;
				$newStack[] = $key;
				$this->_decomposeArray($name, $subValue, $newStack);
			}
		} else {
			$name = htmlentities($name, ENT_COMPAT, LOCALE_ENCODING);
			$value = htmlentities($value, ENT_COMPAT, LOCALE_ENCODING);
			echo '<input type="hidden" name="' . $name;
			while (($item = array_shift($stack)) !== null) {
				$item = htmlentities($item, ENT_COMPAT, LOCALE_ENCODING);
				echo '[' . $item . ']';
			}
			echo '" value="' . $value . "\" />\n";
		}
	}
	/**
	 * Add hidden form parameters for the localized fields for this form
	 * and display the language chooser field
	 * @param $params array
	 * @param $smarty object
	 */
	function smartyFormLanguageChooser($params, &$smarty) {
		// Echo back all non-current language field values so that they
		// are not lost.
		$formLocale = $smarty->get_template_vars('formLocale');
		foreach ($this->getLocaleFieldNames() as $field) {
			$values = $this->getData($field);
			if (!is_array($values)) continue;
			foreach ($values as $locale => $value) {
				if ($locale != $formLocale) $this->_decomposeArray($field, $value, array($locale));
				/*$locale = htmlentities($locale, ENT_COMPAT, LOCALE_ENCODING);
				if (is_array($value)) {
					foreach ($value as $subName => $subValue) {print_r($value);
						$subValue = htmlentities($subValue, ENT_COMPAT, LOCALE_ENCODING);
						$subName = htmlentities($subName, ENT_COMPAT, LOCALE_ENCODING);
						if (empty($subValue)) continue;
						echo '<input type="hidden" name="' . htmlentities($field, ENT_COMPAT, LOCALE_ENCODING) . "[$locale][$subName]\" value=\"$subValue\" />\n";
					}
				} else {
					$value = htmlentities($value, ENT_COMPAT, LOCALE_ENCODING);
					if (empty($value)) continue;
					echo '<input type="hidden" name="' . htmlentities($field, ENT_COMPAT, LOCALE_ENCODING) . "[$locale]\" value=\"$value\" />\n";
				} */
			}
		}

		// Display the language selector widget.
		$formLocale = $smarty->get_template_vars('formLocale');
		echo '<div id="languageSelector"><select size="1" name="formLocale" onchange="changeFormAction(\'' . htmlentities($params['form'], ENT_COMPAT, LOCALE_ENCODING) . '\', \'' . htmlentities($params['url'], ENT_QUOTES, LOCALE_ENCODING) . '\')" class="selectMenu">';
		foreach (Locale::getSupportedLocales() as $locale => $name) {
			
			echo '<option ' . ($locale == $formLocale?'selected="selected" ':'') . 'value="' . htmlentities($locale, ENT_COMPAT, LOCALE_ENCODING) . '">' . htmlentities($name, ENT_COMPAT, LOCALE_ENCODING) . '</option>';
		}
		echo '</select></div>';
	}
}

?>
