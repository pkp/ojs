<?php
/**
 * @file classes/filter/TypeDescription.inc.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2000-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class TypeDescription
 * @ingroup filter
 *
 * @brief Abstract base class for filter input/output type descriptions.
 *
 * Filter type specifications need to fulfill the following requirements:
 * 1) They must uniquely and reliably identify the input/output types
 *    supported by a filter.
 * 2) They must be flexible enough to deal with type polymorphism (e.g. a
 *    generic XSL filter may accept several XML input formats while a
 *    specialized crosswalk filter may only accept a very specific XML
 *    encoding as input) or inheritance (e.g. when checking the class
 *    of an object).
 * 3) Type definitions must be consistent across all filters (even those
 *    contributed by community plugins).
 * 4) Type descriptions must be flexible enough so that new types can
 *    always be introduced, even by the community.
 * 5) Type descriptions should have a unique string representation that
 *    is easy to read by humans and can be persisted. The string
 *    must contain all information necessary to instantiate the type
 *    description object.
 *
 * String representations of type descriptions consist of two parts:
 * - the first part identifies the type description class
 * - the second part contains parameters that a specific type description
 *   class needs to actually check a type
 * The two parts are separated by a double colon.
 *
 * Example: "primitive::string"
 *
 * @see TypeDescriptionFactory for more details about the type description
 * string representation.
 *
 * @see FilterDAO for more details about how type descriptions are used
 * to choose compatible filters for a given input/output type.
 */

define('TYPE_DESCRIPTION_CARDINALITY_SCALAR', '-1');
define('TYPE_DESCRIPTION_CARDINALITY_UNKNOWN', '0');

class TypeDescription {
	/** @var string the unparsed type name */
	var $_typeName;

	/** @var integer the cardinality of the type */
	var $_cardinality;

	/**
	 * Constructor
	 *
	 * @param $typeName string A plain text type name to be parsed
	 *  by this type description class.
	 *
	 *  Type names can be any string. This base class provides a basic
	 *  implementation for type cardinality (array-types) which can
	 *  be re-used by all subclasses.
	 *
	 *  We currently do not support heterogeneous or multi-dimensional arrays
	 *  because we don't have corresponding use cases. We may, however, expand
	 *  our syntax later to accommodate that.
	 *
	 *  If you do not know the exact count of an array then you can leave the
	 *  parentheses empty ([]).
	 */
	function __construct($typeName) {
		$this->_typeName = $typeName;
		if (!$this->_parseTypeNameInternally($typeName)) {
			// Invalid type
			fatalError('Trying to instantiate a "'.$this->getNamespace().'" type description with an invalid type name "'.$typeName.'".');
		}
	}


	//
	// Setters and Getters
	//
	/**
	 * Get the type description's namespace string
	 * @return string
	 */
	function getNamespace() {
		// Must be implemented by subclasses.
		assert(false);
	}

	/**
	 * Get the unparsed type name
	 * @return string
	 */
	function getTypeName() {
		return $this->_typeName;
	}

	/**
	 * Get the full unparsed type description
	 * @return string
	 */
	function getTypeDescription() {
		return $this->getNamespace().'::'.$this->getTypeName();
	}


	//
	// Public methods
	//
	/**
	 * Checks whether the given object complies
	 * with the type description.
	 * @param $object mixed
	 * @return boolean
	 */
	function isCompatible($object) {
		// Null is never compatible
		if (is_null($object)) return false;

		// Check cardinality
		if ($this->_cardinality == TYPE_DESCRIPTION_CARDINALITY_SCALAR) {
			// Should be a scalar
			if (!is_scalar($object) && !is_object($object)) return false;

			// Delegate to subclass for type checking
			if (!$this->checkType($object)) return false;
		} else {
			// Should be an array
			if (!is_array($object)) return false;

			if ($this->_cardinality !=  TYPE_DESCRIPTION_CARDINALITY_UNKNOWN) {
				// We know an exact cardinality - so check it
				if (count($object) != $this->_cardinality) return false;
			}

			// We currently only support homogeneous one-dimensional arrays.
			foreach($object as $scalar) {
				// Should be a scalar
				if (!is_scalar($scalar) && !is_object($scalar)) return false;

				// Delegate to subclass for type checking
				if (!$this->checkType($scalar)) return false;
			}
		}

		// All checks passed so the object complies to the type spec.
		return true;
	}


	//
	// Abstract template methods
	//
	/**
	 * Parse a type name
	 *
	 * @param $typeName string
	 * @return boolean true if success, otherwise false
	 */
	function parseTypeName($typeName) {
		// Must be implemented by subclasses
		assert(false);
	}

	/**
	 * Validates an object against the internal type description.
	 *
	 * @param $object mixed
	 * @return boolean
	 */
	function checkType(&$object) {
		// Must be implemented by subclasses
		assert(false);
	}


	//
	// Private helper methods
	//
	/**
	 * Takes a type name and parses the cardinality part of it
	 * then delegate to the subclass to do the type-specific
	 * parsing.
	 *
	 * @param $typeName string
	 */
	function _parseTypeNameInternally($typeName) {
		// Identify cardinality
		$typeNameParts = explode('[', $typeName);
		switch(count($typeNameParts)) {
			case 1:
				// This is not an array
				$this->_cardinality = TYPE_DESCRIPTION_CARDINALITY_SCALAR;
				break;

			case 2:
				// This is an array, identify its cardinality
				$typeName = $typeNameParts[0];
				$cardinality = trim($typeNameParts[1], ']');
				if($cardinality === '') {
					// A variable length array
					$this->_cardinality = TYPE_DESCRIPTION_CARDINALITY_UNKNOWN;
				} elseif (is_numeric($cardinality)) {
					// A fixed-length array
					$cardinality = (int)$cardinality;
					assert($cardinality > 0);
					$this->_cardinality = $cardinality;
				} else {
					// Invalid type description
					return false;
				}
				break;

			default:
				// Invalid type description
				return false;
		}

		// Delegate to the subclass to parse the actual type name.
		return $this->parseTypeName($typeName);
	}
}
?>
