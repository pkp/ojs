<?php

/**
 * @defgroup metadata Metadata
 * Implements the metadata framework, which allows for the flexible description
 * of objects in many schemas, and conversion of metadata from one schema to
 * another.
 */

/**
 * @file classes/metadata/MetadataSchema.inc.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2000-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class MetadataSchema
 * @ingroup metadata
 * @see MetadataProperty
 * @see MetadataRecord
 *
 * @brief Class that represents a meta-data schema (e.g. NLM element-citation,
 *  OpenURL, dc(terms), MODS) or a subset of it.
 *
 *  We only implement such subsets of meta-data schemas that contain elements which
 *  can be mapped to PKP application objects. Meta-data schemas are not meant to
 *  represent any meta-data in the given schema just PKP application meta-data. The
 *  constructor argument uniquely identifies the application objects this meta-data
 *  schema can be mapped to. There should never be two MetadataSchemas with the same
 *  namespace that map to the same application object type. This also means that we
 *  implement composite elements if and only if the composite complies with our
 *  internal class composition schema and not only because the schema allows a composite
 *  in a certain position. See MetadataDescription and MetadataProperty for further
 *  information about composite meta-data properties.
 *
 *  Example: We implement a composite to represent authors that correspond to the
 *  PKPAuthor class. We do not implement composites for title meta-data
 *  even if the chosen schema allows this (e.g. abbreviated title, alternative title)
 *  as this data is implemented as fields of the Submission object. This doesn't mean
 *  that such data cannot be mapped to composites in external bindings, e.g. in an
 *  XML binding of the meta-data schema. We can always map a flat list of key/value
 *  pairs to a hierarchical representation in an external binding.
 *
 *  This coupling allows us to flexibly configure meta-data entry for application
 *  objects. We can identify appropriate meta-data fields for application objects
 *  when displaying or entering object meta-data in application forms. Thereby users
 *  can dynamically add meta-data fields for input/output if they require these for
 *  their local meta-data partners (e.g. libraries, repositories, harvesters, indexers).
 *
 *  We assume that all properties defined within a schema can potentially be assigned
 *  to the objects represented by the given association types. Users should, however,
 *  be able to enable / disable properties on a per-assoc-type basis so that only a
 *  sub-set of properties will actually be available in the user interface as well as
 *  exported or imported for these objects.
 *
 *  New schemas can be dynamically added to the mix at any time if they provide fields
 *  not provided by already existing schemas.
 *
 *  NB: We currently provide meta-data schemas as classes for better performance
 *  and code readability. It might, however, be necessary to maintain meta-data
 *  schemas in the database for higher flexibility and easier run-time configuration/
 *  installation of new schemas.
 */


import('lib.pkp.classes.metadata.MetadataProperty');

class MetadataSchema {
	/** @var array */
	var $_assocTypes;

	/** @var string */
	var $_name;

	/** @var string */
	var $_namespace;

	/** @var string */
	var $_classname;

	/**
	 * @var array meta-data properties (predicates)
	 *  supported for this meta-data schema.
	 */
	var $_properties = array();

	/**
	 * Constructor
	 * @param $name string the meta-data schema name
	 * @param $namespace string a globally unique namespace for
	 *  the schema. Property names must be unique within this
	 *  namespace.
	 * @param $classname the fully qualified class name of
	 *  this schema
	 * @param $assocTypes array|integer the association types of
	 *  PKP application objects that can be described using
	 *  this schema. A single association type can be given as
	 *  a scalar.
	 */
	function __construct($name, $namespace, $classname, $assocTypes) {
		assert(is_string($name) && is_string($namespace) && is_string($classname));
		assert(is_array($assocTypes) || is_integer($assocTypes));

		// Set name and namespace.
		$this->_name = $name;
		$this->_namespace = $namespace;
		$this->_classname = $classname;

		// Normalize and set the association types.
		if (!is_array($assocTypes)) {
			$assocTypes = array($assocTypes);
		}
		$this->_assocTypes = $assocTypes;
	}


	//
	// Getters and Setters
	//
	/**
	 * Get the name of the schema
	 * @return string
	 */
	function getName() {
		return $this->_name;
	}

	/**
	 * Get the internal namespace qualifier of the schema
	 * @return string
	 */
	function getNamespace() {
		return $this->_namespace;
	}

	/**
	 * Get the fully qualified class name of this schema.
	 * @return string
	 */
	function getClassName() {
		return $this->_classname;
	}

	/**
	 * Get the association types for PKP application objects
	 * that can be described with this schema.
	 * @return array
	 */
	function getAssocTypes() {
		return $this->_assocTypes;
	}

	/**
	 * Get the properties of the meta-data schema.
	 * @return array an array of MetadataProperties
	 */
	function &getProperties() {
		return $this->_properties;
	}

	/**
	 * Get a property. Returns null if the property
	 * doesn't exist.
	 * @return MetadataProperty
	 */
	function &getProperty($propertyName) {
		assert(is_string($propertyName));
		if ($this->hasProperty($propertyName)) {
			$property =& $this->_properties[$propertyName];
		} else {
			$property = null;
		}
		return $property;
	}

	/**
	 * Returns the property id with prefixed name space
	 * for use in an external context (e.g. Forms, Templates).
	 * @param $propertyName string
	 * @return string
	 */
	function getNamespacedPropertyId($propertyName) {
		$property =& $this->getProperty($propertyName);
		assert(is_a($property, 'MetadataProperty'));
		return $this->getNamespace().ucfirst($property->getId());
	}

	/**
	 * (Re-)set all properties of this meta-data schema.
	 * @param $properties array an array of MetadataProperties
	 */
	function setProperties(&$properties) {
		// Remove the existing properties
		$this->_properties = array();

		// Insert the new properties
		foreach($properties as $property) {
			$this->addProperty($property);
		}
	}

	/**
	 * Add a property to this meta-data schema.
	 * @param $name string the unique name of the property within a meta-data schema (can be a property URI)
	 * @param $allowedTypes mixed must be a scalar or an array with the supported types, default: METADATA_PROPERTY_TYPE_STRING
	 * @param $translated boolean whether the property may have various language versions, default: false
	 * @param $cardinality integer must be on of the supported cardinalities, default: METADATA_PROPERTY_CARDINALITY_ONE
	 * @param $displayName string
	 * @param $validationMessage string A string that can be displayed in case a user tries to set an invalid value for this property.
	 * @param $mandatory boolean Is this a mandatory property within the schema?
	 */
	function addProperty($name, $allowedTypes = METADATA_PROPERTY_TYPE_STRING,
			$translated = false, $cardinality = METADATA_PROPERTY_CARDINALITY_ONE, $displayName = null, $validationMessage = null, $mandatory = false) {
		// Make sure that this property has not been added before
		assert(!is_null($name) && !isset($this->_properties[$name]));

		// Instantiate the property.
		$property = new MetadataProperty($name, $this->_assocTypes, $allowedTypes, $translated, $cardinality, $displayName, $validationMessage, $mandatory);

		// Add the property
		$this->_properties[$name] =& $property;
	}

	/**
	 * Get the property names defined for this meta-data schema
	 * @return array an array of string values representing valid property names
	 */
	function getPropertyNames() {
		return array_keys($this->_properties);
	}

	/**
	 * Get the names of properties with a given data type.
	 * @param $propertyType mixed a valid property type description
	 * @return array an array of string values representing valid property names
	 */
	function getPropertyNamesByType($propertyType) {
		assert(in_array($propertyType, MetadataProperty::getSupportedTypes()));

		$propertyNames = array();
		foreach($this->_properties as $property) {
			$allowedPropertyTypes = $property->getAllowedTypes();
			if (isset($allowedPropertyTypes[$propertyType])) {
				$propertyNames[] = $property->getName();
			}
		}

		return $propertyNames;
	}

	/**
	 * Checks whether a property exists in the meta-data schema
	 * @param $propertyName string
	 * @return boolean
	 */
	function hasProperty($propertyName) {
		return isset($this->_properties[$propertyName]);
	}
}
?>
