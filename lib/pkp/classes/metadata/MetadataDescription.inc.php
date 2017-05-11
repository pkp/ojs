<?php

/**
 * @file classes/metadata/MetadataDescription.inc.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2000-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class MetadataDescription
 * @ingroup metadata
 * @see MetadataProperty
 * @see MetadataRecord
 * @see MetadataSchema
 *
 * @brief Class modeling a description (DCMI abstract model) or subject-
 *  predicate-object graph (RDF). This class and its children provide
 *  meta-data (DCMI abstract model: statements of property-value pairs,
 *  RDF: assertions of predicate-object pairs) about a given PKP application
 *  entity instance (DCMI abstract model: described resource, RDF: subject).
 *
 *  This class has primarily been designed to describe journals, journal
 *  issues, articles, conferences, conference proceedings (conference papers),
 *  monographs (books), monograph components (book chapters) or citations.
 *
 *  It is, however, flexible enough to be extended to describe any
 *  application entity in the future. Descriptions can be retrieved from
 *  any application object that implements the MetadataProvider interface.
 *
 *  Special attention has been paid to the compatibility of the class
 *  implementation with the implementation of several meta-data standards
 *  that we consider especially relevant to our use cases.
 *
 *  We distinguish two main use cases for meta-data: discovery and delivery
 *  of described resources. We have chosen the element-citation tag from the
 *  NLM standard <http://dtd.nlm.nih.gov/publishing/tag-library/3.0/n-8xa0.html>
 *  as our primary representation of delivery meta-data and dcterms
 *  <http://dublincore.org/documents/dcmi-terms/> as our primary
 *  representation of discovery meta-data.
 *
 *  Our specific use of meta-data has important implications and determines
 *  our design goals:
 *  * Neither NLM-citation nor dcterms have been designed with an object
 *    oriented encoding in mind. NLM-citation is usually XML encoded
 *    while typical dcterms encodings are HTML meta-tags, RDF or XML.
 *  * We believe that trying to implement a super-set of meta-data
 *    standards ("least common denominator" or super-schema approach)
 *    is fundamentally flawed as meta-data standards are always
 *    developed with specific use-cases in mind that require potentially
 *    incompatible data properties or encodings.
 *  * Although we think that NLM-citation and dcterms are sensible default
 *    meta-data schemes our design should remain flexible enough for
 *    users to implement and use other schemes as an internal meta-data
 *    standard.
 *  * We have to make sure that we can easily extract/inject meta-data
 *    from/to PKP application objects.
 *  * We have to avoid code duplication to keep maintenance cost under
 *    control.
 *  * We have to minimize the "impedance mismatch" between our own
 *    object oriented encoding and fully standard compliant external
 *    encodings (i.e. XML, RDF, HTML meta-tags, ...) to allow for easy
 *    conversion between encodings.
 *  * We have to make sure that we can switch between internal and
 *    external encodings without any data loss.
 *  * We have to make sure that crosswalks to and from other important
 *    meta-data standards (e.g. OpenURL variants, MODS, MARC) can be
 *    performed in a well-defined and easy way while minimizing data
 *    loss.
 *  * We have to make sure that we can support qualified fields (e.g.
 *    qualified DC).
 *  * We have to make sure that we can support RDF triples.
 *
 *  We took the following design decisions to achieve these goals:
 *  * We only implement properties that are justified by strong real-world
 *    use-cases. We recognize that the limiting factor is not the data that
 *    we could represent but the data we actually have. This is not determined
 *    by the chosen standard but by the PKP application objects we want to
 *    represent. Additional meta-data properties/predicates can be added as
 *    required.
 *  * We do adapt data structures as long as we can make sure that a
 *    fully standard compliant encoding can always be re-constructed. This
 *    is especially true for NLM-citation which is designed with
 *    XML in mind and therefore uses hierarchical constructs that are
 *    difficult to represent in an OO class model.
 *    This means that our meta-data framework only supports (nested) key/
 *    value-based schemas which can however be converted to hierarchical
 *    representations.
 *  * We borrow class and property names from the DCMI abstract model as
 *    the terms used there provide better readability for developers less
 *    acquainted with formal model theory. We'll, however, make sure that
 *    data can easily be RDF encoded within our data model.
 *  * Data validation must ensure that meta-data always complies with a
 *    specific meta-data standard. As we are speaking about an object
 *    oriented encoding that is not defined in the original standard, we
 *    define compliance as "roundtripability". This means we must be able
 *    to convert our object oriented data encoding to a fully standard
 *    compliant encoding and back without any data loss.
 */


import('lib.pkp.classes.core.DataObject');

define('METADATA_DESCRIPTION_REPLACE_ALL', 0x01);
define('METADATA_DESCRIPTION_REPLACE_PROPERTIES', 0x02);
define('METADATA_DESCRIPTION_REPLACE_NOTHING', 0x03);

define('METADATA_DESCRIPTION_UNKNOWN_LOCALE', 'unknown');

class MetadataDescription extends DataObject {
	/** @var string fully qualified class name of the meta-data schema this description complies to */
	var $_metadataSchemaName;

	/** @var MetadataSchema the schema this description complies to */
	var $_metadataSchema;

	/** @var int association type (the type of the described resource) */
	var $_assocType;

	/** @var int association id (the identifier of the described resource) */
	var $_assocId;

	/**
	 * @var string an (optional) display name that describes the contents
	 *  of this meta-data description to the end user.
	 */
	var $_displayName;

	/**
	 * @var integer sequence id used when saving several descriptions
	 *  of the same subject.
	 */
	var $_seq;

	/**
	 * Constructor
	 */
	function __construct($metadataSchemaName, $assocType) {
		assert(is_string($metadataSchemaName) && is_integer($assocType));
		parent::__construct();
		$this->_metadataSchemaName = $metadataSchemaName;
		$this->_assocType = $assocType;
	}

	//
	// Get/set methods
	//
	/**
	 * Get the fully qualified class name of
	 * the supported meta-data schema.
	 */
	function getMetadataSchemaName() {
		return $this->_metadataSchemaName;
	}

	/**
	 * Get the metadata schema
	 * @return MetadataSchema
	 */
	function &getMetadataSchema() {
		// Lazy-load the meta-data schema if this has
		// not been done before.
		if (is_null($this->_metadataSchema)) {
			$this->_metadataSchema =& instantiate($this->getMetadataSchemaName(), 'MetadataSchema');
			assert(is_object($this->_metadataSchema));
		}
		return $this->_metadataSchema;
	}

	/**
	 * Get the association type (described resource type)
	 * @return int
	 */
	function getAssocType() {
		return $this->_assocType;
	}

	/**
	 * Get the association id (described resource identifier)
	 * @return int
	 */
	function getAssocId() {
		return $this->_assocId;
	}

	/**
	 * Set the association id (described resource identifier)
	 * @param $assocId int
	 */
	function setAssocId($assocId) {
		$this->_assocId = $assocId;
	}

	/**
	 * Construct a meta-data application entity id
	 * (described resource id / subject id) for
	 * this meta-data description object.
	 * @return string
	 */
	function getAssoc() {
		$assocType = $this->getAssocType();
		$assocId = $this->getAssocId();
		assert(isset($assocType) && isset($assocId));
		return $assocType.':'.$assocId;
	}

	/**
	 * Set the (optional) display name
	 * @param $displayName string
	 */
	function setDisplayName($displayName) {
		$this->_displayName = $displayName;
	}

	/**
	 * Get the (optional) display name
	 * @return string
	 */
	function getDisplayName() {
		return $this->_displayName;
	}

	/**
	 * Set the sequence id
	 * @param $seq integer
	 */
	function setSequence($seq) {
		$this->_seq = $seq;
	}

	/**
	 * Get the sequence id
	 * @return integer
	 */
	function getSequence() {
		return $this->_seq;
	}

	/**
	 * Add a meta-data statement. Statements can only be added
	 * for properties that are part of the meta-data schema. This
	 * method will also check the validity of the value for the
	 * given property before adding the statement.
	 * @param $propertyName string The name of the property
	 * @param $value mixed The value to be assigned to the property
	 * @param $locale string
	 * @param $replace boolean whether to replace an existing statement
	 * @return boolean true if a valid statement was added, otherwise false
	 */
	function addStatement($propertyName, $value, $locale = null, $replace = false) {
		// Check the property
		$property =& $this->getProperty($propertyName);
		if (is_null($property)) return false;
		assert(is_a($property, 'MetadataProperty'));

		// Check that the property is allowed for the described resource
		if (!in_array($this->_assocType, $property->getAssocTypes())) return false;

		// Handle translation
		$translated = $property->getTranslated();
		if (isset($locale) && !$translated) return false;
		if (!isset($locale) && $translated) {
			// Retrieve the current locale
			$locale = AppLocale::getLocale();
		}

		// Check that the value is compliant with the property specification
		if ($property->isValid($value, $locale) === false) return false;

		// Handle cardinality
		$existingValue =& $this->getStatement($propertyName, $locale);
		switch ($property->getCardinality()) {
			case METADATA_PROPERTY_CARDINALITY_ONE:
				if (isset($existingValue) && !$replace) return false;
				$newValue = $value;
				break;

			case METADATA_PROPERTY_CARDINALITY_MANY:
				if (isset($existingValue) && !$replace) {
					assert(is_array($existingValue));
					$newValue = $existingValue;
					array_push($newValue, $value);
				} else {
					$newValue = array($value);
				}
				break;

			default:
				assert(false);
		}

		// Add the value
		$this->setData($propertyName, $newValue, $locale);
		return true;
	}

	/**
	 * Remove statement. If the property has cardinality 'many'
	 * then all statements for the property will be removed at once.
	 * If the property is translated and the locale is null then
	 * the statements for all locales will be removed.
	 * @param $propertyName string
	 * @param $locale string
	 * @return boolean true if the statement was found and removed, otherwise false
	 */
	function removeStatement($propertyName, $locale = null) {
		// Remove the statement if it exists
		if (isset($propertyName) && $this->hasData($propertyName, $locale)) {
			$this->setData($propertyName, null, $locale);
			return true;
		}

		return false;
	}

	/**
	 * Get all statements
	 * @return array statements
	 */
	function &getStatements() {
		// Do not retrieve the data by-ref
		// otherwise the following unset()
		// will change internal state.
		$allData = $this->getAllData();

		// Unset data variables that are not statements
		unset($allData['id']);
		return $allData;
	}

	/**
	 * Get a specific statement
	 * @param $propertyName string
	 * @param $locale string
	 * @return mixed a scalar property value or an array of property values
	 *  if the cardinality of the property is 'many'.
	 */
	function &getStatement($propertyName, $locale = null) {
		// Check the property
		$property =& $this->getProperty($propertyName);
		assert(isset($property) && is_a($property, 'MetadataProperty'));

		// Handle translation
		$translated = $property->getTranslated();
		if (!$translated) assert(is_null($locale));
		if ($translated && !isset($locale)) {
			// Retrieve the current locale
			$locale = AppLocale::getLocale();
		}

		// Retrieve the value
		return $this->getData($propertyName, $locale);
	}

	/**
	 * Returns all translations of a translated property
	 * @param $propertyName string
	 * @return array all translations of a given property; if the
	 *  property has cardinality "many" then this returns a two-dimensional
	 *  array whereby the first key represents the locale and the second
	 *  the translated values.
	 */
	function &getStatementTranslations($propertyName) {
		assert($this->isTranslatedProperty($propertyName));
		return $this->getData($propertyName);
	}

	/**
	 * Add several statements at once. If one of the statements
	 * is invalid then the meta-data description will remain in its
	 * initial state.
	 * * Properties with a cardinality of 'many' must be passed in as
	 *   sub-arrays.
	 * * Translated properties with a cardinality of 'one' must be
	 *   passed in as sub-arrays with the locale as a key.
	 * * Translated properties with a cardinality of 'many' must be
	 *   passed in as sub-sub-arrays with the locale as the first key.
	 * @param $statements array statements
	 * @param $replace integer one of the allowed replace levels.
	 * @return boolean true if all statements could be added, false otherwise
	 */
	function setStatements(&$statements, $replace = METADATA_DESCRIPTION_REPLACE_PROPERTIES) {
		assert(in_array($replace, $this->_allowedReplaceLevels()));

		// Make a backup copy of all existing statements.
		$statementsBackup = $this->getAllData();

		if ($replace == METADATA_DESCRIPTION_REPLACE_ALL) {
			// Delete existing statements
			$emptyArray = array();
			$this->setAllData($emptyArray);
		}

		// Add statements one by one to detect invalid values.
		foreach($statements as $propertyName => $content) {
			assert(!empty($content));

			// Transform scalars or translated fields to arrays so that
			// we can handle properties with different cardinalities in
			// the same way.
			if (is_scalar($content) || is_string(key($content))) {
				$values = array(&$content);
			} else {
				$values =& $content;
			}

			if ($replace == METADATA_DESCRIPTION_REPLACE_PROPERTIES) {
				$replaceProperty = true;
			} else {
				$replaceProperty = false;
			}

			$valueIndex = 0;
			foreach($values as $value) {
				$firstValue = ($valueIndex == 0) ? true : false;
				// Is this a translated property?
				if (is_array($value)) {
					foreach($value as $locale => $translation) {
						// Handle cardinality many and one in the same way
						if (is_scalar($translation)) {
							$translationValues = array(&$translation);
						} else {
							$translationValues =& $translation;
						}
						$translationIndex = 0;
						foreach($translationValues as $translationValue) {
							$firstTranslation = ($translationIndex == 0) ? true : false;
							// Add a statement (replace existing statement if any)
							if (!($this->addStatement($propertyName, $translationValue, $locale, $firstTranslation && $replaceProperty))) {
								$this->setAllData($statementsBackup);
								return false;
							}
							unset($translationValue);
							$translationIndex++;
						}
						unset($translationValues);
					}
					unset($translation);
				} else {
					// Add a statement (replace existing statement if any)
					if (!($this->addStatement($propertyName, $value, null, $firstValue && $replaceProperty))) {
						$this->setAllData($statementsBackup);
						return false;
					}
				}
				unset($value);
				$valueIndex++;
			}
			unset($values);
		}
		return true;
	}

	/**
	 * Convenience method that returns the properties of
	 * the underlying meta-data schema.
	 * @return array an array of MetadataProperties
	 */
	function &getProperties() {
		$metadataSchema =& $this->getMetadataSchema();
		return $metadataSchema->getProperties();
	}

	/**
	 * Convenience method that returns a property from
	 * the underlying meta-data schema.
	 * @param $propertyName string
	 * @return MetadataProperty
	 */
	function &getProperty($propertyName) {
		$metadataSchema =& $this->getMetadataSchema();
		return $metadataSchema->getProperty($propertyName);
	}

	/**
	 * Convenience method that returns a property id
	 * the underlying meta-data schema.
	 * @param $propertyName string
	 * @return string
	 */
	function getNamespacedPropertyId($propertyName) {
		$metadataSchema =& $this->getMetadataSchema();
		return $metadataSchema->getNamespacedPropertyId($propertyName);
	}

	/**
	 * Convenience method that returns the valid
	 * property names of the underlying meta-data schema.
	 * @return array an array of string values representing valid property names
	 */
	function getPropertyNames() {
		$metadataSchema =& $this->getMetadataSchema();
		return $metadataSchema->getPropertyNames();
	}

	/**
	 * Convenience method that returns the names of properties with a
	 * given data type of the underlying meta-data schema.
	 * @param $propertyType string
	 * @return array an array of string values representing valid property names
	 */
	function getPropertyNamesByType($propertyType) {
		$metadataSchema =& $this->getMetadataSchema();
		return $metadataSchema->getPropertyNamesByType($propertyType);
	}

	/**
	 * Returns an array of property names for
	 * which statements exist.
	 * @return array an array of string values representing valid property names
	 */
	function getSetPropertyNames() {
		return array_keys($this->getStatements());
	}

	/**
	 * Convenience method that checks the existence
	 * of a property in the underlying meta-data schema.
	 * @param $propertyName string
	 * @return boolean
	 */
	function hasProperty($propertyName) {
		$metadataSchema =& $this->getMetadataSchema();
		return $metadataSchema->hasProperty($propertyName);
	}

	/**
	 * Check the existence of a statement for the given property.
	 * @param $propertyName string
	 * @return boolean
	 */
	function hasStatement($propertyName) {
		$statements =& $this->getStatements();
		return (isset($statements[$propertyName]));
	}

	/**
	 * Convenience method that checks whether a given property
	 * is translated.
	 * @param $propertyName string
	 * @return boolean
	 */
	function isTranslatedProperty($propertyName) {
		$property = $this->getProperty($propertyName);
		assert(is_a($property, 'MetadataProperty'));
		return $property->getTranslated();
	}


	//
	// Private helper methods
	//
	/**
	 * The allowed replace levels for the
	 * setStatements() method.
	 */
	static function _allowedReplaceLevels() {
		static $allowedReplaceLevels = array(
			METADATA_DESCRIPTION_REPLACE_ALL,
			METADATA_DESCRIPTION_REPLACE_PROPERTIES,
			METADATA_DESCRIPTION_REPLACE_NOTHING
		);
		return $allowedReplaceLevels;
	}
}

?>
