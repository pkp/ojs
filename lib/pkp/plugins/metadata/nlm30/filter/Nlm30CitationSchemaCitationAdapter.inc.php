<?php

/**
 * @file plugins/metadata/nlm30/filter/Nlm30CitationSchemaCitationAdapter.inc.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2000-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class Nlm30CitationSchemaCitationAdapter
 * @ingroup plugins_metadata_nlm30_filter
 * @see Citation
 * @see Nlm30CitationSchema
 *
 * @brief Class that injects/extracts NLM citation schema compliant
 *  meta-data into/from a Citation object.
 */

import('lib.pkp.classes.metadata.MetadataDataObjectAdapter');
import('lib.pkp.plugins.metadata.nlm30.schema.Nlm30NameSchema');

class Nlm30CitationSchemaCitationAdapter extends MetadataDataObjectAdapter {
	/**
	 * Constructor
	 * @param $filterGroup FilterGroup
	 */
	function __construct($filterGroup) {
		parent::__construct($filterGroup);
	}

	//
	// Implement template methods from PersistableFilter
	//
	/**
	 * @copydoc PersistableFilter::getClassName()
	 */
	function getClassName() {
		return 'lib.pkp.plugins.metadata.nlm30.filter.Nlm30CitationSchemaCitationAdapter';
	}


	//
	// Implement template methods from MetadataDataObjectAdapter
	//
	/**
	 * @copydoc MetadataDataObjectAdapter::injectMetadataIntoDataObject()
	 * @param $metadataDescription MetadataDescription
	 * @param $dataObject Citation
	 * @return DataObject
	 */
	function &injectMetadataIntoDataObject(&$metadataDescription, &$dataObject) {
		assert(is_a($dataObject, 'Citation'));

		// Add new meta-data statements to the citation. Add the schema
		// name space to each property name so that it becomes unique
		// across schemas.
		$metadataSchemaNamespace = $this->getMetadataNamespace();

		$nullVar = null;
		foreach($metadataDescription->getPropertyNames() as $propertyName) {
			$dataObjectKey = $metadataSchemaNamespace.':'.$propertyName;
			if ($metadataDescription->hasStatement($propertyName)) {
				// Directly retrieve the internal data so that we don't
				// have to care about cardinality and translation.
				$value =& $metadataDescription->getData($propertyName);
				if (in_array($propertyName, array('person-group[@person-group-type="author"]', 'person-group[@person-group-type="editor"]'))) {
					assert(is_array($value));

					// Dereference the value to make sure that we don't destroy
					// the original MetadataDescription.
					$tmpValue = $value;
					unset($value);
					$value =& $tmpValue;

					// Convert MetadataDescription objects to simple key/value arrays.
					foreach($value as $key => $name) {
						if(is_a($name, 'MetadataDescription')) {
							// A name can either be a full name description...
							$value[$key] =& $name->getAllData();
						} else {
							// ...or an 'et-al' string.
							assert($name == PERSON_STRING_FILTER_ETAL);
							// No need to change the value encoding.
						}
					}
				}
				$dataObject->setData($dataObjectKey, $value);
				unset($value);
			}
		}

		return $dataObject;
	}

	/**
	 * @copydoc MetadataDataObjectAdapter::extractMetadataFromDataObject()
	 * @param $dataObject Citation
	 * @return MetadataDescription
	 */
	function extractMetadataFromDataObject(&$dataObject) {
		$metadataDescription = $this->instantiateMetadataDescription();

		// Establish the association between the meta-data description
		// and the citation object.
		$metadataDescription->setAssocId($dataObject->getId());

		// Identify the length of the name space prefix
		$namespacePrefixLength = strlen($this->getMetadataNamespace())+1;

		// Get all meta-data field names
		$fieldNames = array_merge($this->getDataObjectMetadataFieldNames(false),
				$this->getDataObjectMetadataFieldNames(true));

		// Retrieve the statements from the data object
		$statements = array();
		foreach($fieldNames as $fieldName) {
			if ($dataObject->hasData($fieldName)) {
				// Remove the name space prefix
				$propertyName = substr($fieldName, $namespacePrefixLength);
				if (in_array($propertyName, array('person-group[@person-group-type="author"]', 'person-group[@person-group-type="editor"]'))) {
					// Retrieve the names array (must not be by-ref
					// to protect the original citation object!)
					$names = $dataObject->getData($fieldName);

					// Convert key/value arrays to MetadataDescription objects.
					foreach($names as $key => $name) {
						if (is_array($name)) {
							// Construct a meta-data description from
							// this name array.
							switch($propertyName) {
								case 'person-group[@person-group-type="author"]':
									$assocType = ASSOC_TYPE_AUTHOR;
									break;

								case 'person-group[@person-group-type="editor"]':
									$assocType = ASSOC_TYPE_EDITOR;
									break;
							}
							$nameDescription = new MetadataDescription('lib.pkp.plugins.metadata.nlm30.schema.Nlm30NameSchema', $assocType);
							$nameDescription->setStatements($name);
							$names[$key] =& $nameDescription;
							unset($nameDescription);
						} else {
							// The only non-structured data allowed here
							// is the et-al string.
							import('lib.pkp.plugins.metadata.nlm30.filter.Nlm30PersonStringFilter');
							assert($name == PERSON_STRING_FILTER_ETAL);
						}
					}
					$statements[$propertyName] =& $names;
					unset($names);
				} else {
					$statements[$propertyName] =& $dataObject->getData($fieldName);
				}
			}
		}

		// Set the statements in the meta-data description
		$success = $metadataDescription->setStatements($statements);
		assert((boolean) $success);

		return $metadataDescription;
	}
}
?>
