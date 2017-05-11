<?php

/**
 * @file plugins/metadata/nlm30/filter/Nlm30NameSchemaPersonStringFilter.inc.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2000-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class Nlm30NameSchemaPersonStringFilter
 * @ingroup plugins_metadata_nlm30_filter
 * @see Nlm30NameSchema
 *
 * @brief Filter that converts from NLM name to
 *  a string.
 */

import('lib.pkp.plugins.metadata.nlm30.filter.Nlm30PersonStringFilter');

class Nlm30NameSchemaPersonStringFilter extends Nlm30PersonStringFilter {
	/** @var string */
	var $_template;

	/** @var string */
	var $_delimiter;

	/**
	 * Constructor
	 * @param $filterMode integer
	 * @param $template string default: DRIVER guidelines 2.0 name template
	 *  Possible template variables are %surname%, %suffix%, %prefix%, %initials%, %firstname%
	 */
	function __construct($filterMode = PERSON_STRING_FILTER_SINGLE, $template = '%surname%%suffix%,%initials% (%firstname%)%prefix%', $delimiter = '; ') {
		$this->setDisplayName('NLM Name Schema to string conversion');

		assert(!empty($template) && is_string($template));
		$this->_template = $template;
		assert(is_string($delimiter));
		$this->_delimiter = $delimiter;

		$inputType = 'metadata::lib.pkp.plugins.metadata.nlm30.schema.Nlm30NameSchema(*)';
		$outputType = 'primitive::string';
		if ($filterMode == PERSON_STRING_FILTER_MULTIPLE) $inputType .= '[]';

		parent::__construct($inputType, $outputType, $filterMode);
	}

	//
	// Getters and Setters
	//
	/**
	 * Get the output template
	 * @return string
	 */
	function getTemplate() {
		return $this->_template;
	}

	/**
	 * Set the output template
	 * @param $template string
	 */
	function setTemplate($template) {
		$this->_template = $template;
	}

	/**
	 * Get the author delimiter (for multiple mode)
	 * @return string
	 */
	function getDelimiter() {
		return $this->_delimiter;
	}

	/**
	 * Set the author delimiter (for multiple mode)
	 * @param $delimiter string
	 */
	function setDelimiter($delimiter) {
		$this->_delimiter = $delimiter;
	}


	//
	// Implement template methods from Filter
	//
	/**
	 * @copydoc Filter::supports()
	 */
	function supports(&$input, &$output) {
		// We intercept the supports() method so that
		// we can remove et-al entries which are valid but
		// do not conform to the canonical type definition.
		$filteredInput =& $this->removeEtAlEntries($input);
		if ($filteredInput === false) return false;

		return parent::supports($filteredInput, $output);
	}

	/**
	 * @see Filter::process()
	 * @param $input mixed a(n array of) MetadataDescription(s)
	 * @return string
	 */
	function &process(&$input) {
		switch ($this->getFilterMode()) {
			case PERSON_STRING_FILTER_MULTIPLE:
				$personDescription = $this->_flattenPersonDescriptions($input);
				break;

			case PERSON_STRING_FILTER_SINGLE:
				$personDescription = $this->_flattenPersonDescription($input);
				break;

			default:
				assert(false);
		}

		return $personDescription;
	}

	//
	// Private helper methods
	//
	/**
	 * Transform an NLM name description array to a person string.
	 * NB: We use ; as name separator.
	 * @param $personDescriptions array an array of MetadataDescriptions
	 * @return string
	 */
	function _flattenPersonDescriptions(&$personDescriptions) {
		assert(is_array($personDescriptions));
		$personDescriptionStrings = array_map(array($this, '_flattenPersonDescription'), $personDescriptions);
		$personString = implode($this->getDelimiter(), $personDescriptionStrings);
		return $personString;
	}

	/**
	 * Transform a single NLM name description to a person string.
	 * NB: We use the style: surname suffix, initials (first-name) prefix
	 * which is relatively easy to parse back.
	 * @param $personDescription MetadataDescription|'et-al'
	 * @return string
	 */
	function _flattenPersonDescription(&$personDescription) {
		// Handle et-al
		if (is_string($personDescription) && $personDescription == PERSON_STRING_FILTER_ETAL) return 'et al';

		$nameVars['%surname%'] = (string)$personDescription->getStatement('surname');

		$givenNames = $personDescription->getStatement('given-names');
		$nameVars['%firstname%'] = $nameVars['%initials%'] = '';
		if(is_array($givenNames) && count($givenNames)) {
			if (PKPString::strlen($givenNames[0]) > 1) {
				$nameVars['%firstname%'] = array_shift($givenNames);
			}
			foreach($givenNames as $givenName) {
				$nameVars['%initials%'] .= PKPString::substr($givenName, 0, 1).'.';
			}
		}
		if (!empty($nameVars['%initials%'])) $nameVars['%initials%'] = ' '.$nameVars['%initials%'];

		$nameVars['%prefix%'] = (string)$personDescription->getStatement('prefix');
		if (!empty($nameVars['%prefix%'])) $nameVars['%prefix%'] = ' '.$nameVars['%prefix%'];
		$nameVars['%suffix%'] = (string)$personDescription->getStatement('suffix');
		if (!empty($nameVars['%suffix%'])) $nameVars['%suffix%'] = ' '.$nameVars['%suffix%'];

		// Fill placeholders in person template.
		$personString = str_replace(array_keys($nameVars), array_values($nameVars), $this->getTemplate());

		// Remove empty brackets and trailing/leading whitespace
		$personString = trim(str_replace('()', '', $personString));

		return $personString;
	}
}
?>
