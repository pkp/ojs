<?php
/**
 * @defgroup plugins_metadata_mods34_filter MODS 3.4 Filter Plugin
 */

/**
 * @file plugins/metadata/mods34/filter/Mods34DescriptionXmlFilter.inc.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2000-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class Mods34DescriptionXmlFilter
 * @ingroup plugins_metadata_mods34_filter
 *
 * @brief Class that converts a meta-data description to a MODS 3.4 XML document.
 */


import('lib.pkp.classes.filter.PersistableFilter');
import('lib.pkp.classes.xml.XMLCustomWriter');

class Mods34DescriptionXmlFilter extends PersistableFilter {
	/**
	 * Constructor
	 * @param $filterGroup FilterGroup
	 */
	function __construct($filterGroup) {
		$this->setDisplayName('MODS 3.4');
		parent::__construct($filterGroup);
	}


	//
	// Implement template methods from PersistableFilter
	//
	/**
	 * @copydoc PersistableFilter::getClassName()
	 */
	function getClassName() {
		return 'lib.pkp.plugins.metadata.mods34.filter.Mods34DescriptionXmlFilter';
	}


	//
	// Implement template methods from Filter
	//
	/**
	 * @copydoc Filter::process()
	 * @param $input MetadataDescription
	 */
	function &process(&$input) {
		// Start the XML document.
		$doc =& XMLCustomWriter::createDocument();

		// Create the root element.
		$root =& XMLCustomWriter::createElement($doc, 'mods');

		// Add the XML namespace and schema.
		XMLCustomWriter::setAttribute($root, 'version', '3.4');
		XMLCustomWriter::setAttribute($root, 'xmlns', 'http://www.loc.gov/mods/v3');
		XMLCustomWriter::setAttribute($root, 'xmlns:xsi', 'http://www.w3.org/2001/XMLSchema-instance');
		XMLCustomWriter::setAttribute($root, 'xsi:schemaLocation', 'http://www.loc.gov/mods/v3 http://www.loc.gov/standards/mods/v3/mods-3-4.xsd');

		// Prepare the MODS document hierarchy from the MODS MetadataDescription instance.
		$documentHierarchy =& $this->_buildDocumentHierarchy($doc, $root, $input);

		// Recursively join the document hierarchy into a single document.
		$root =& $this->_joinNodes($documentHierarchy);
		XMLCustomWriter::appendChild($doc, $root);

		// Retrieve the XML from the DOM.
		$output = XMLCustomWriter::getXml($doc);
		return $output;
	}

	//
	// Private helper methods
	//
	/**
	 * Process a MODS composite property into an XML node.
	 * @param $doc XMLNode|DOMDocument the MODS document node.
	 * @param $elementName string the name of the root element of the composite
	 * @param $metadataDescription MetadataDescription
	 * @return XMLNode|DOMDocument
	 */
	function &_processCompositeProperty(&$doc, $elementName, &$metadataDescription) {
		// Create the root element.
		$root =& XMLCustomWriter::createElement($doc, $elementName);

		// Prepare the MODS hierarchy from the MODS name MetadataDescription instance.
		$documentHierarchy =& $this->_buildDocumentHierarchy($doc, $root, $metadataDescription);

		// Recursively join the name document hierarchy into a single element.
		$root =& $this->_joinNodes($documentHierarchy);
		return $root;
	}

	/**
	 * Create a hierarchical array that represents the MODS DOM
	 * from the meta-data description.
	 *
	 * @param $doc XMLNode|DOMDocument the MODS document node.
	 * @param $root XMLNode|DOMDocument the root node of the
	 *  MODS document.
	 * @param $mods34Description MetadataDescription
	 * @return array a hierarchical array of XMLNode|DOMDocument objects
	 *  representing the MODS document.
	 */
	function &_buildDocumentHierarchy(&$doc, &$root, &$mods34Description) {
		// Get the MODS schema.
		$mods34Schema =& $mods34Description->getMetadataSchema();
		if (is_a($mods34Schema, 'Mods34Schema')) {
			// Identify the cataloging language.
			assert($mods34Description->hasStatement('recordInfo/languageOfCataloging/languageTerm[@authority="iso639-2b"]'));
			$catalogingLanguage = $mods34Description->getStatement('recordInfo/languageOfCataloging/languageTerm[@authority="iso639-2b"]');
		} else {
			// This must be a MODS name schema.
			assert(is_a($mods34Schema, 'Mods34NameSchema'));
			$catalogingLanguage = 'undefined';
		}

		// Initialize the document hierarchy with the root node.
		$documentHierarchy = array(
			'@branch' => &$root
		);

		// Find the translations required for top-level elements.
		// We need this array later because we'll have to repeat non-translated
		// values for every translated top-level element.
		$properties = $mods34Description->getProperties();
		$translations = array();
		foreach ($properties as $propertyName => $property) { /* @var $property MetadataProperty */
			if ($mods34Description->hasStatement($propertyName)) {
				$nodes = explode('/', $propertyName);
				$topLevelNode = array_shift($nodes);
				if (!isset($translations[$topLevelNode])) $translations[$topLevelNode] = array();
				if ($property->getTranslated()) {
					foreach ($mods34Description->getStatementTranslations($propertyName) as $locale => $value) {
						$isoLanguage = AppLocale::get3LetterIsoFromLocale($locale);
						if (!in_array($isoLanguage, $translations[$topLevelNode])) {
							$translations[$topLevelNode][] = $isoLanguage;
						}
					}
				} else {
					if (!in_array($catalogingLanguage, $translations[$topLevelNode])) {
						$translations[$topLevelNode][] = $catalogingLanguage;
					}
				}
			}
		}

		// Build the document hierarchy.
		foreach ($properties as $propertyName => $property) { /* @var $property MetadataProperty */
			if ($mods34Description->hasStatement($propertyName)) {
				// Get relevant property attributes.
				$translated = $property->getTranslated();
				$cardinality = $property->getCardinality();

				// Get the XML element hierarchy.
				$nodes = explode('/', $propertyName);
				$hierarchyDepth = count($nodes) - 1;

				// Normalize property values to an array of translated strings.
				if ($translated) {
					// Only the main MODS schema can contain translated values.
					assert(is_a($mods34Schema, 'Mods34Schema'));

					// Retrieve the translated values of the statement.
					$localizedValues =& $mods34Description->getStatementTranslations($propertyName);

					// Translate the PKP locale into ISO639-2b 3-letter codes.
					$translatedValues = array();
					foreach($localizedValues as $locale => $translatedValue) {
						$isoLanguage = AppLocale::get3LetterIsoFromLocale($locale);
						assert(!is_null($isoLanguage));
						$translatedValues[$isoLanguage] = $translatedValue;
					}
				} else {
					// Untranslated statements will be repeated for all languages
					// present in the top-level element.
					$untranslatedValue =& $mods34Description->getStatement($propertyName);
					$translatedValues = array();
					assert(isset($translations[$nodes[0]]));
					foreach($translations[$nodes[0]] as $isoLanguage) {
						$translatedValues[$isoLanguage] = $untranslatedValue;
					}
				}

				// Normalize all values to arrays so that we can
				// handle them uniformly.
				$translatedValueArrays = array();
				foreach($translatedValues as $isoLanguage => $translatedValue) {
					if ($cardinality == METADATA_PROPERTY_CARDINALITY_ONE) {
						assert(is_scalar($translatedValue));
						$translatedValueArrays[$isoLanguage] = array(&$translatedValue);
					} else {
						assert(is_array($translatedValue));
						$translatedValueArrays[$isoLanguage] =& $translatedValue;
					}
					unset($translatedValue);
				}

				// Add the translated values one by one to the element hierarchy.
				foreach($translatedValueArrays as $isoLanguage => $translatedValueArray) {
					foreach($translatedValueArray as $translatedValue) {
						// Add a language attribute to the top-level element if
						// it differs from the cataloging language.
						$translatedNodes = $nodes;
						if ($isoLanguage != $catalogingLanguage) {
							assert(strpos($translatedNodes[0], '[') === false);
							$translatedNodes[0] .= '[@lang="'.$isoLanguage.'"]';
						}

						// Create the node hierarchy for the statement.
						$currentNodeList =& $documentHierarchy;
						foreach($translatedNodes as $nodeDepth => $nodeName) {
							// Are we at a leaf node?
							if($nodeDepth == $hierarchyDepth) {
								// Is this a top-level attribute?
								if (substr($nodeName, 0, 1) == '[') {
									assert($nodeDepth == 0);
									assert($translated == false);
									assert($cardinality == METADATA_PROPERTY_CARDINALITY_ONE);
									assert(!is_object($translatedValue));
									$attributeName = trim($nodeName, '[@"]');
									XMLCustomWriter::setAttribute($root, $attributeName, (string)$translatedValue);
									continue;
								}

								// This is a sub-element.
								if (isset($currentNodeList[$nodeName])) {
									// Only properties with cardinality "many" can
									// have more than one leaf node.
									assert($cardinality == METADATA_PROPERTY_CARDINALITY_MANY);

									// Check that the leaf list is actually there.
									assert(isset($currentNodeList[$nodeName]['@leaves']));

									// We should never find any branch in a leaves node.
									assert(!isset($currentNodeList[$nodeName]['@branch']));
								} else {
									// Create the leaf list in the hierarchy.
									$currentNodeList[$nodeName]['@leaves'] = array();
								}

								if (is_a($translatedValue, 'MetadataDescription')) {
									// Recursively process composite properties.
									$leafNode =& $this->_processCompositeProperty($doc, $propertyName, $translatedValue);
								} else {
									// Cast scalar values to string types for XML binding.
									$translatedValue = (string)$translatedValue;

									// Create the leaf element.
									$leafNode =& $this->_createNode($doc, $nodeName, $translatedValue);
								}

								// Add the leaf element to the leaves list.
								$currentNodeList[$nodeName]['@leaves'][] =& $leafNode;
								unset($leafNode);
							} else {
								// This is a branch node.

								// Has the branch already been created? If not: create it.
								if (isset($currentNodeList[$nodeName])) {
									// Check that the branch node is actually there.
									assert(isset($currentNodeList[$nodeName]['@branch']));

									// We should never find any leaves in a branch node.
									assert(!isset($currentNodeList[$nodeName]['@leaves']));
								} else {
									// Create the branch node.
									$branchNode =& $this->_createNode($doc, $nodeName);

									// Add the branch node list and add the new node as it's root element.
									$currentNodeList[$nodeName] = array(
										'@branch' => &$branchNode
									);
									unset($branchNode);
								}
							}

							// Set the node list pointer to the sub-element
							$currentNodeList =& $currentNodeList[$nodeName];
						}
					}
				}
			}
		}

		return $documentHierarchy;
	}

	/**
	 * Create a new XML node.
	 * @param $doc XMLNode|DOMImplementation
	 * @param $nodePath string an XPath-like string that describes the
	 *  node to be created.
	 * @param $value string the value to be added as a text node (if any)
	 * @return XMLNode|DOMDocument
	 */
	function &_createNode($doc, $nodePath, $value = null) {
		// Separate the element name from the attributes.
		$elementPlusAttributes = explode('[', $nodePath);
		$element = $elementPlusAttributes[0];
		assert(!empty($element));

		// Create the element.
		$newNode =& XMLCustomWriter::createElement($doc, $element);

		// Add attributes.
		if (count($elementPlusAttributes) == 2) {
			// Separate the attribute key/value pairs.
			$unparsedAttributes = explode('@', rtrim(ltrim($elementPlusAttributes[1], '@'), ']'));
			foreach($unparsedAttributes as $unparsedAttribute) {
				// Split attribute expressions into key and value.
				list($attributeName, $attributeValue) = explode('=', rtrim($unparsedAttribute, ' '));
				$attributeValue = trim($attributeValue, '"');
				XMLCustomWriter::setAttribute($newNode, $attributeName, $attributeValue);
			}
		}

		// Insert a text node if we got a value for it.
		if (!is_null($value)) {
			$textNode =& XMLCustomWriter::createTextNode($doc, $value);
			XMLCustomWriter::appendChild($newNode, $textNode);
		}

		return $newNode;
	}

	/**
	 * Recursively join the document hierarchy into a single document.
	 * @param $documentHierarchy
	 * @return array an array of joined nodes
	 */
	function &_joinNodes(&$documentHierarchy) {
		// Get the root node of the hierarchy.
		$root = $documentHierarchy['@branch'];
		unset($documentHierarchy['@branch']);

		// Add the sub-hierarchies to the root element.
		foreach($documentHierarchy as $subHierarchy) {
			// Is this a leaf node?
			if (isset($subHierarchy['@leaves'])) {
				// Make sure that there's no rubbish in this node.
				assert(count($subHierarchy) == 1);

				foreach($subHierarchy['@leaves'] as $leafNode) {
					XMLCustomWriter::appendChild($root, $leafNode);
				}
			} else {
				// This is a branch node.
				$subNode =& $this->_joinNodes($subHierarchy);
				XMLCustomWriter::appendChild($root, $subNode);
			}
		}

		// Return the node list.
		return $root;
	}
}
?>
