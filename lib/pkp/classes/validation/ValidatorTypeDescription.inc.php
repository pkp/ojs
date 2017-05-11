<?php

/**
 * @file classes/validation/ValidatorTypeDescription.inc.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2000-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class ValidatorTypeDescription
 * @ingroup filter
 *
 * @brief Class that describes a string input/output type that passes
 *  additional validation (via standard validator classes).
 */

import('lib.pkp.classes.filter.PrimitiveTypeDescription');

class ValidatorTypeDescription extends PrimitiveTypeDescription {
	/** @var string the validator class name */
	var $_validatorClassName;

	/** @var array arguments to be passed to the validator constructor */
	var $_validatorArgs;

	/**
	 * Constructor
	 *
	 * @param $typeName string Allowed primitive types are
	 *  'integer', 'string', 'float' and 'boolean'.
	 */
	function __construct($typeName) {
		parent::__construct($typeName);
	}


	//
	// Setters and Getters
	//
	/**
	 * @see TypeDescription::getNamespace()
	 */
	function getNamespace() {
		return TYPE_DESCRIPTION_NAMESPACE_VALIDATOR;
	}


	//
	// Implement abstract template methods from TypeDescription
	//
	/**
	 * @see TypeDescription::parseTypeName()
	 */
	function parseTypeName($typeName) {
		// Standard validators are based on string input.
		parent::parseTypeName('string');

		// Split the type name into validator name and arguments.
		$typeNameParts = explode('(', $typeName, 2);
		switch (count($typeNameParts)) {
			case 1:
				// no argument
				$this->_validatorArgs = '';
				break;

			case 2:
				// parse arguments (no UTF8-treatment necessary)
				if (substr($typeNameParts[1], -1) != ')') return false;
				// FIXME: Escape for PHP code inclusion?
				$this->_validatorArgs = substr($typeNameParts[1], 0, -1);
				break;
		}

		// Validator name must start with a lower case letter
		// and may contain only alphanumeric letters.
		if (!PKPString::regexp_match('/^[a-z][a-zA-Z0-9]+$/', $typeNameParts[0])) return false;

		// Translate the validator name into a validator class name.
		$this->_validatorClassName = 'Validator'.PKPString::ucfirst($typeNameParts[0]);

		return true;
	}

	/**
	 * @see TypeDescription::checkType()
	 */
	function checkType(&$object) {
		// Check primitive type.
		if (!parent::checkType($object)) return false;

		// Instantiate and call validator
		import('lib.pkp.classes.validation.'.$this->_validatorClassName);
		assert(class_exists($this->_validatorClassName));
		$validatorConstructorCode = 'return new '.$this->_validatorClassName.'('.$this->_validatorArgs.');';
		$validator = eval($validatorConstructorCode);
		assert(is_a($validator, 'Validator'));

		// Validate the object
		if (!$validator->isValid($object)) return false;

		return true;
	}
}
?>
