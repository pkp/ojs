<?php

/**
 * @file classes/xslt/XMLTypeDescription.inc.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2000-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class XMLTypeDescription
 * @ingroup xslt
 *
 * @brief Class that describes an XML input/output type.
 *
 *  Type descriptors follow the syntax:
 *   xml::validation-schema(http://url.to.the/file.{xsd|dtd|rng})
 *
 *  Example:
 *   xml::schema(http://www.crossref.org/schema/queryResultSchema/crossref_query_output2.0.xsd)
 *
 *  XML input/output can be either represented as a string or as a DOMDocument object.
 *
 *  NB: XML validation currently requires PHP5
 */

import('lib.pkp.classes.filter.TypeDescription');
import('lib.pkp.classes.filter.TypeDescriptionFactory');

define('XML_TYPE_DESCRIPTION_VALIDATE_NONE', '*');
define('XML_TYPE_DESCRIPTION_VALIDATE_SCHEMA', 'schema');
define('XML_TYPE_DESCRIPTION_VALIDATE_DTD', 'dtd');
define('XML_TYPE_DESCRIPTION_VALIDATE_RELAX_NG', 'relax-ng');

class XMLTypeDescription extends TypeDescription {
	/** @var string a validation strategy, see the XML_TYPE_DESCRIPTION_VALIDATE_* constants */
	var $_validationStrategy = XML_TYPE_DESCRIPTION_VALIDATE_SCHEMA;

	/** @var string a validation document as string or filename pointer (xsd or rng only) */
	var $_validationSource;


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
		return TYPE_DESCRIPTION_NAMESPACE_XML;
	}

	/**
	 * Set the validation strategy
	 * @param $validationStrategy string XML_TYPE_DESCRIPTION_VALIDATE_...
	 */
	function setValidationStrategy($validationStrategy) {
		$this->_validationStrategy = $validationStrategy;
	}

	//
	// Implement abstract template methods from TypeDescription
	//
	/**
	 * @copydoc TypeDescription::parseTypeName()
	 */
	function parseTypeName($typeName) {
		// We expect a validation strategy and an optional validation argument
		$typeNameParts = explode('(', $typeName);
		switch (count($typeNameParts)) {
			case 1:
				// No argument present (only dtd or no validation)
				$validationStrategy = $typeName;
				if ($validationStrategy != XML_TYPE_DESCRIPTION_VALIDATE_NONE
						&& $validationStrategy != XML_TYPE_DESCRIPTION_VALIDATE_DTD) return false;
				$validationSource = null;
				break;

			case 2:
				// We have an argument (only available for schema and relax-ng)
				$validationStrategy = $typeNameParts[0];
				if ($validationStrategy != XML_TYPE_DESCRIPTION_VALIDATE_SCHEMA
						&& $validationStrategy != XML_TYPE_DESCRIPTION_VALIDATE_RELAX_NG) return false;
				$validationSource = trim($typeNameParts[1], ')');
				break;

			default:
				return false;
		}

		$this->_validationStrategy = $validationStrategy;
		$this->_validationSource = $validationSource;

		return true;
	}

	/**
	 * @copydoc TypeDescription::checkType()
	 */
	function checkType(&$object) {
		// We only accept DOMDocument objects and source strings.
		if (!is_a($object, 'DOMDocument') && !is_string($object)) return false;

		// No validation...
		if ($this->_validationStrategy == XML_TYPE_DESCRIPTION_VALIDATE_NONE) return true;

		// Validation - requires DOMDocument
		if (is_string($object)) {
			$xmlDom = new DOMDocument();
			$xmlDom->loadXML($object);
		} else {
			$xmlDom =& $object;
		}

		switch($this->_validationStrategy) {
			// We have to suppress validation errors, otherwise the script
			// will stop when validation errors occur.
			case XML_TYPE_DESCRIPTION_VALIDATE_DTD:
				if (!$xmlDom->validate()) return false;
				break;

			case XML_TYPE_DESCRIPTION_VALIDATE_SCHEMA:
				if (!$xmlDom->schemaValidate($this->_validationSource)) return false;
				break;

			case XML_TYPE_DESCRIPTION_VALIDATE_RELAX_NG:
				if (!$xmlDom->relaxNGValidate($this->_validationSource)) return false;
				break;

			default:
				assert(false);
		}

		return true;
	}
}
?>
