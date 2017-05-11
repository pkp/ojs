<?php

/**
 * @defgroup citation Citation
 * Implements the Citation Assistant, which is used to facilitate
 * the parsing and approval of citations.
 */

/**
 * @file classes/citation/Citation.inc.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2000-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class Citation
 * @ingroup citation
 * @see MetadataDescription
 *
 * @brief Class representing a citation (bibliographic reference)
 */


define('CITATION_RAW', 0x01);
define('CITATION_CHECKED', 0x02);
define('CITATION_PARSED', 0x03);
define('CITATION_LOOKED_UP', 0x04);
define('CITATION_APPROVED', 0x05);

import('lib.pkp.classes.core.DataObject');
import('lib.pkp.plugins.metadata.nlm30.schema.Nlm30CitationSchema');
import('lib.pkp.plugins.metadata.nlm30.filter.Nlm30CitationSchemaCitationAdapter');

class Citation extends DataObject {
	/** @var int citation state (raw, edited, parsed, looked-up) */
	var $_citationState = CITATION_RAW;

	/** @var array an array of MetadataDescriptions */
	var $_sourceDescriptions = array();

	/** @var integer the max sequence number that has been attributed so far */
	var $_maxSourceDescriptionSeq = 0;

	/**
	 * @var array errors that occurred while
	 *  checking or filtering the citation.
	 */
	var $_errors = array();


	/**
	 * Constructor.
	 * @param $rawCitation string an unparsed citation string
	 */
	function __construct($rawCitation = null) {
		// Switch on meta-data adapter support.
		$this->setHasLoadableAdapters(true);

		parent::__construct();

		$this->setRawCitation($rawCitation); // this will set state to CITATION_RAW
	}

	//
	// Getters and Setters
	//
	/**
	 * Set meta-data descriptions discovered for this
	 * citation from external sources.
	 *
	 * @param $sourceDescriptions array MetadataDescriptions
	 */
	function setSourceDescriptions(&$sourceDescriptions) {
		$this->_sourceDescriptions =& $sourceDescriptions;
	}

	/**
	 * Add a meta-data description discovered for this
	 * citation from an external source.
	 *
	 * @param $sourceDescription MetadataDescription
	 * @return integer the source description's sequence
	 *  number.
	 */
	function addSourceDescription($sourceDescription) {
		assert(is_a($sourceDescription, 'MetadataDescription'));

		// Identify an appropriate sequence number.
		$seq = $sourceDescription->getSequence();
		if (is_numeric($seq) && $seq > 0) {
			// This description has a pre-set sequence number
			if ($seq > $this->_maxSourceDescriptionSeq) $this->_maxSourceDescriptionSeq = $seq;
		} else {
			// We'll create a sequence number for the description
			$this->_maxSourceDescriptionSeq++;
			$seq = $this->_maxSourceDescriptionSeq;
			$sourceDescription->setSequence($seq);
		}

		// We add descriptions by display name as they are
		// purely informational. This avoids getting duplicates
		// when we update a description.
		$this->_sourceDescriptions[$sourceDescription->getDisplayName()] = $sourceDescription;
		return $seq;
	}

	/**
	 * Get all meta-data descriptions discovered for this
	 * citation from external sources.
	 *
	 * @return array MetadataDescriptions
	 */
	function &getSourceDescriptions() {
		return $this->_sourceDescriptions;
	}

	/**
	 * Get the citationState
	 * @return integer
	 */
	function getCitationState() {
		return $this->_citationState;
	}

	/**
	 * Set the citationState
	 * @param $citationState integer
	 */
	function setCitationState($citationState) {
		assert(in_array($citationState, Citation::_getSupportedCitationStates()));
		$this->_citationState = $citationState;
	}

	/**
	 * Get the association type
	 * @return integer
	 */
	function getAssocType() {
		return $this->getData('assocType');
	}

	/**
	 * Set the association type
	 * @param $assocType integer
	 */
	function setAssocType($assocType) {
		$this->setData('assocType', $assocType);
	}

	/**
	 * Get the association id
	 * @return integer
	 */
	function getAssocId() {
		return $this->getData('assocId');
	}

	/**
	 * Set the association id
	 * @param $assocId integer
	 */
	function setAssocId($assocId) {
		$this->setData('assocId', $assocId);
	}

	/**
	 * Add a checking error
	 * @param $errorMessage string
	 */
	function addError($errorMessage) {
		$this->_errors[] = $errorMessage;
	}

	/**
	 * Get all checking errors
	 * @return array
	 */
	function getErrors() {
		return $this->_errors;
	}


	/**
	 * Get the rawCitation
	 * @return string
	 */
	function getRawCitation() {
		return $this->getData('rawCitation');
	}

	/**
	 * Set the rawCitation
	 * @param $rawCitation string
	 */
	function setRawCitation($rawCitation) {
		$rawCitation = $this->_cleanCitationString($rawCitation);

		$this->setData('rawCitation', $rawCitation);
	}

	/**
	 * Get the sequence number
	 * @return integer
	 */
	function getSequence() {
		return $this->getData('seq');
	}

	/**
	 * Set the sequence number
	 * @param $seq integer
	 */
	function setSequence($seq) {
		$this->setData('seq', $seq);
	}

	/**
	 * Returns all properties of this citation. The returned
	 * array contains the name spaces as key and the property
	 * list as values.
	 * @return array
	 */
	function &getNamespacedMetadataProperties() {
		$metadataSchemas =& $this->getSupportedMetadataSchemas();
		$metadataProperties = array();
		foreach($metadataSchemas as $metadataSchema) {
			$metadataProperties[$metadataSchema->getNamespace()] = $metadataSchema->getProperties();
		}
		return $metadataProperties;
	}


	//
	// Private methods
	//
	/**
	 * Return supported citation states
	 * @return array supported citation states
	 */
	static function _getSupportedCitationStates() {
		static $_supportedCitationStates = array(
			CITATION_RAW,
			CITATION_CHECKED,
			CITATION_PARSED,
			CITATION_LOOKED_UP,
			CITATION_APPROVED
		);
		return $_supportedCitationStates;
	}

	/**
	 * Take a citation string and clean/normalize it
	 * @param $citationString string
	 * @return string
	 */
	function _cleanCitationString($citationString) {
		// 1) If the string contains non-UTF8 characters, convert it to UTF-8
		if (Config::getVar('i18n', 'charset_normalization') && !PKPString::utf8_compliant($citationString)) {
			$citationString = PKPString::utf8_normalize($citationString);
		}
		// 2) Strip slashes and whitespace
		$citationString = trim(stripslashes($citationString));

		// 3) Normalize whitespace
		$citationString = PKPString::regexp_replace('/[\s]+/', ' ', $citationString);

		return $citationString;
	}
}
?>
