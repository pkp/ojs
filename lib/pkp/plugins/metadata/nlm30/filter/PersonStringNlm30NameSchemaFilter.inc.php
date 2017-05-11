<?php

/**
 * @file plugins/metadata/nlm30/filter/PersonStringNlm30NameSchemaFilter.inc.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2000-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class PersonStringNlm30NameSchemaFilter
 * @ingroup plugins_metadata_nlm30_filter
 * @see Nlm30NameSchema
 *
 * @brief Filter that converts from a string
 *  to an (array of) NLM name description(s).
 */

import('lib.pkp.plugins.metadata.nlm30.filter.Nlm30PersonStringFilter');

class PersonStringNlm30NameSchemaFilter extends Nlm30PersonStringFilter {
	/** @var integer */
	var $_assocType;

	/** @var boolean */
	var $_filterTitle;

	/** @var boolean */
	var $_filterDegrees;

	/**
	 * Constructor
	 */
	function __construct($assocType, $filterMode = PERSON_STRING_FILTER_SINGLE, $filterTitle = false, $filterDegrees = false) {
		$this->setDisplayName('String to NLM Name Schema conversion');

		assert(in_array($assocType, array(ASSOC_TYPE_AUTHOR, ASSOC_TYPE_EDITOR)));
		$this->_assocType = $assocType;
		$this->_filterTitle = $filterTitle;
		$this->_filterDegrees = $filterDegrees;

		$inputType = 'primitive::string';
		$outputType = 'metadata::lib.pkp.plugins.metadata.nlm30.schema.Nlm30NameSchema(*)';
		if ($filterMode == PERSON_STRING_FILTER_MULTIPLE) $outputType .= '[]';

		parent::__construct($inputType, $outputType, $filterMode);
	}

	//
	// Setters and Getters
	//
	/**
	 * Get the association type
	 * @return integer
	 */
	function &getAssocType() {
		return $this->_assocType;
	}

	/**
	 * Do we parse for a title?
	 * @return boolean
	 */
	function getFilterTitle() {
		return $this->_filterTitle;
	}

	/**
	 * Set whether we parse for a title
	 * @param $filterTitle boolean
	 */
	function setFilterTitle($filterTitle) {
		$this->_filterTitle = (boolean)$filterTitle;
	}

	/**
	 * Do we parse for degrees?
	 * @return boolean
	 */
	function getFilterDegrees() {
		return $this->_filterDegrees;
	}

	/**
	 * Set whether we parse for degrees
	 * @param $filterDegrees boolean
	 */
	function setFilterDegrees($filterDegrees) {
		$this->_filterDegrees = (boolean)$filterDegrees;
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
		$filteredOutput =& $this->removeEtAlEntries($output);
		if ($filteredOutput === false) return false;

		return parent::supports($input, $filteredOutput);
	}

	/**
	 * Transform a person string to an (array of) NLM name description(s).
	 * @see Filter::process()
	 * @param $input string
	 * @return mixed Either a MetadataDescription or an array of MetadataDescriptions
	 *  plus optionally a single 'et-al' string.
	 */
	function &process(&$input) {
		switch ($this->getFilterMode()) {
			case PERSON_STRING_FILTER_MULTIPLE:
				return $this->_parsePersonsString($input, $this->_filterTitle, $this->_filterDegrees);

			case PERSON_STRING_FILTER_SINGLE:
				return $this->_parsePersonString($input, $this->_filterTitle, $this->_filterDegrees);

			default:
				assert(false);
		}
	}


	//
	// Private helper methods
	//
	/**
	 * Converts a string with multiple persons
	 * to an array of NLM name descriptions.
	 *
	 * @param $personsString string
	 * @param $title boolean true to parse for title
	 * @param $degrees boolean true to parse for degrees
	 * @return array an array of NLM name descriptions or null
	 *  if the string could not be converted plus optionally a
	 *  single 'et-al' string.
	 */
	function &_parsePersonsString($personsString, $title, $degrees) {
		// Check for 'et al'.
		$personsStringBeforeEtal = PKPString::strlen($personsString);
		$personsString = PKPString::regexp_replace('/et ?al$/', '', $personsString);
		$etAl = ($personsStringBeforeEtal == PKPString::strlen($personsString) ? false : true);

		// Remove punctuation.
		$personsString = trim($personsString, ':;, ');

		// Cut the authors string into pieces.
		$personStrings = PKPString::iterativeExplode(array(':', ';'), $personsString);

		// If we did not have success with simple patterns then try more complex
		// patterns to tokenize multiple-person strings.
		if (count($personStrings) == 1) {
			// The first pattern must match the whole string, the second is used
			// to extract names.
			$complexPersonsPatterns = array(
				// APA author style unfortunately uses commas to separate surnames
				// as well as pre-names which makes it much more difficult to split
				// correctly. We allow for non-APA whitespacing and punctuation.
				array(
					'/^((([^ \t\n\r\f\v,.&]{2,}\s*)+,\s*([A-Z]\.\s*)+),\s*)+(\&|\.\s\.\s\.)\s*([^ \t\n\r\f\v,.&]{2,}\s*,\s*([A-Z]\.\s*)+)$/i',
					'/(?:[^ \t\n\r\f\v,.&]{2,}\s*)+,\s*(?:[A-Z]\.\s*)+/i'
				),
				// Try to cut by comma if the pieces contain more
				// than one word to avoid splitting between last name and
				// first name. This captures APA editor and Vancouver
				// author styles for example.
				array(
					'/^((([^ \t\n\r\f\v,&]+\s+)+[^ \t\n\r\f\v,&]+\s*)[,&]\s*)+(([^ \t\n\r\f\v,&]+\s+)+[^ \t\n\r\f\v,&]+)/i',
					'/(?:(?:[^ \t\n\r\f\v,&.]+|[^ \t\n\r\f\v,&]{2,})\s+)+(?:[^ \t\n\r\f\v,&.]+|[^ \t\n\r\f\v,&]{2,})/i'
				)
			);
			$matched = false;
			foreach($complexPersonsPatterns as $complexPersonsPattern) {
				// Break at the first pattern that matches.
				if ($matched = PKPString::regexp_match($complexPersonsPattern[0], $personsString)) {
					// Retrieve names.
					$success = PKPString::regexp_match_all($complexPersonsPattern[1], $personsString, $personStrings);
					assert((boolean) $success && count($personStrings) == 1);
					$personStrings = $personStrings[0];
					break;
				}
			}

			if (!$matched) {
				// If nothing matches then try to parse as a single person.
				$personStrings = array($personsString);
			}
		}

		// Parse persons.
		$persons = array();
		foreach ($personStrings as $personString) {
			$persons[] =& $this->_parsePersonString($personString, $title, $degrees);
		}

		// Add et-al string.
		if ($etAl) {
			$persons[] = PERSON_STRING_FILTER_ETAL;
		}

		return $persons;
	}

	/**
	 * Converts a string with a single person
	 * to an NLM name description.
	 *
	 * TODO: add initials from all given names to initials
	 *       element
	 *
	 * @param $personString string
	 * @param $title boolean true to parse for title
	 * @param $degrees boolean true to parse for degrees
	 * @return MetadataDescription an NLM name description or null
	 *  if the string could not be converted
	 */
	function &_parsePersonString($personString, $title, $degrees) {
		// Expressions to parse person strings, ported from CiteULike person
		// plugin, see http://svn.citeulike.org/svn/plugins/person.tcl
		static $personRegex = array(
			'title' => '(?:His (?:Excellency|Honou?r)\s+|Her (?:Excellency|Honou?r)\s+|The Right Honou?rable\s+|The Honou?rable\s+|Right Honou?rable\s+|The Rt\.? Hon\.?\s+|The Hon\.?\s+|Rt\.? Hon\.?\s+|Mr\.?\s+|Ms\.?\s+|M\/s\.?\s+|Mrs\.?\s+|Miss\.?\s+|Dr\.?\s+|Sir\s+|Dame\s+|Prof\.?\s+|Professor\s+|Doctor\s+|Mister\s+|Mme\.?\s+|Mast(?:\.|er)?\s+|Lord\s+|Lady\s+|Madam(?:e)?\s+|Priv\.-Doz\.\s+)+',
			'degrees' => '(,\s+(?:[A-Z\.]+))+',
			'initials' => '(?:(?:[A-Z]\.){1,3}[A-Z]\.?)|(?:(?:[A-Z]\.\s){1,3}[A-Z]\.?)|(?:[A-Z]{1,4})|(?:(?:[A-Z]\.-?){1,4})|(?:(?:[A-Z]\.-?){1,3}[A-Z]\.?)|(?:(?:[A-Z]-){1,3}[A-Z])|(?:(?:[A-Z]\s){1,3}[A-Z]\.?)|(?:(?:[A-Z]-){1,3}[A-Z]\.?)',
			'prefix' => 'Dell(?:[a|e])?(?:\s|$)|Dalle(?:\s|$)|D[a|e]ll\'(?:\s|$)|Dela(?:\s|$)|Del(?:\s|$)|[Dd]e(?:\s|$)(?:La(?:\s|$)|Los(?:\s|$))?|[Dd]e(?:\s|$)|[Dd][a|i|u](?:\s|$)|L[a|e|o](?:\s|$)|[D|L|O]\'|St\.?(?:\s|$)|San(?:\s|$)|[Dd]en(?:\s|$)|[Vv]on(?:\s|$)(?:[Dd]er(?:\s|$))?|(?:[Ll][ea](?:\s|$))?[Vv]an(?:\s|$)(?:[Dd]e(?:n|r)?(?:\s|$))?',
			'givenName' => '(?:[^ \t\n\r\f\v,.;()]{2,}|[^ \t\n\r\f\v,.;()]{2,}\-[^ \t\n\r\f\v,.;()]{2,})'
		);
		// The expressions for given name, suffix and surname are the same
		$personRegex['surname'] = $personRegex['suffix'] = $personRegex['givenName'];
		$personRegex['double-surname'] = "(?:".$personRegex['surname']."\s)*".$personRegex['surname'];

		// Shortcut for prefixed surname
		$personRegexPrefixedSurname = "(?P<prefix>(?:".$personRegex['prefix'].")?)(?P<surname>".$personRegex['surname'].")";
		$personRegexPrefixedDoubleSurname = "(?P<prefix>(?:".$personRegex['prefix'].")?)(?P<surname>".$personRegex['double-surname'].")";

		// Instantiate the target person description
		$personDescription = new MetadataDescription('lib.pkp.plugins.metadata.nlm30.schema.Nlm30NameSchema', $this->_assocType);

		// Clean the person string
		$personString = trim($personString);

		// 1. Extract title and degree from the person string and use this as suffix
		$suffixString = '';

		$results = array();
		if ($title && PKPString::regexp_match_get('/^('.$personRegex['title'].')/i', $personString, $results)) {
			$suffixString = trim($results[1], ',:; ');
			$personString = PKPString::regexp_replace('/^('.$personRegex['title'].')/i', '', $personString);
		}

		if ($degrees && PKPString::regexp_match_get('/('.$personRegex['degrees'].')$/i', $personString, $results)) {
			$degreesArray = explode(',', trim($results[1], ','));
			foreach($degreesArray as $key => $degree) {
				$degreesArray[$key] = PKPString::trimPunctuation($degree);
			}
			$suffixString .= ' - '.implode('; ', $degreesArray);
			$personString = PKPString::regexp_replace('/('.$personRegex['degrees'].')$/i', '', $personString);
		}

		if (!empty($suffixString)) $personDescription->addStatement('suffix', $suffixString);

		// Space initials when followed by a given name or last name.
		$personString = PKPString::regexp_replace('/([A-Z])\.([A-Z][a-z])/', '\1. \2', $personString);

		// 2. Extract names and initials from the person string

		// The parser expressions are ordered by specificity. The most specific expressions
		// come first. Only if these specific expressions don't work will we turn to less
		// specific ones. This avoids parsing errors. It also explains why we don't use the
		// ?-quantifier for optional elements like initials or middle name where they could
		// be misinterpreted.
		$personExpressions = array(
			// All upper surname
			'/^'.$personRegexPrefixedSurname.'$/i',

			// Several permutations of name elements, ordered by specificity
			'/^(?P<initials>'.$personRegex['initials'].')\s'.$personRegexPrefixedSurname.'$/',
			'/^'.$personRegexPrefixedSurname.',?\s(?P<initials>'.$personRegex['initials'].')$/',
			'/^'.$personRegexPrefixedDoubleSurname.',\s(?P<givenName>'.$personRegex['givenName'].')\s(?P<initials>'.$personRegex['initials'].')$/',
			'/^(?P<givenName>'.$personRegex['givenName'].')\s(?P<initials>'.$personRegex['initials'].')\s'.$personRegexPrefixedSurname.'$/',
			'/^'.$personRegexPrefixedDoubleSurname.',\s(?P<givenName>(?:'.$personRegex['givenName'].'\s)+)(?P<initials>'.$personRegex['initials'].')$/',
			'/^(?P<givenName>(?:'.$personRegex['givenName'].'\s)+)(?P<initials>'.$personRegex['initials'].')\s'.$personRegexPrefixedSurname.'$/',
			'/^'.$personRegexPrefixedDoubleSurname.',(?P<givenName>(?:\s'.$personRegex['givenName'].')+)$/',
			'/^(?P<givenName>(?:'.$personRegex['givenName'].'\s)+)'.$personRegexPrefixedSurname.'$/',

			// DRIVER guidelines 2.0 name syntax
			'/^\s*(?P<surname>'.$personRegex['surname'].')(?P<suffix>(?:\s+'.$personRegex['suffix'].')?)\s*,\s*(?P<initials>(?:'.$personRegex['initials'].')?)\s*\((?P<givenName>(?:\s*'.$personRegex['givenName'].')+)\s*\)\s*(?P<prefix>(?:'.$personRegex['prefix'].')?)$/',

			// ParaCite name syntax
			'/^(?P<givenName>'.$personRegex['givenName'].')\.(?P<surname>'.$personRegex['double-surname'].')$/',

		// Catch-all expression
			'/^(?P<surname>.*)$/'
		);

		$results = array();
		foreach ($personExpressions as $expressionId => $personExpression) {
			if ($nameFound = PKPString::regexp_match_get($personExpression, $personString, $results)) {
				// Given names
				if (!empty($results['givenName'])) {
					// Split given names
					$givenNames = explode(' ', trim($results['givenName']));
					foreach($givenNames as $givenName) {
						$personDescription->addStatement('given-names', $givenName);
						unset($givenName);
					}
				}

				// Initials (will also be saved as given names)
				if (!empty($results['initials'])) {
					$results['initials'] = str_replace(array('.', '-', ' '), array('', '', ''), $results['initials']);
					for ($initialNum = 0; $initialNum < PKPString::strlen($results['initials']); $initialNum++) {
						$initial = $results['initials'][$initialNum];
						$personDescription->addStatement('given-names', $initial);
						unset($initial);
					}
				}

				// Surname
				if (!empty($results['surname'])) {
					// Correct all-upper surname
					if (strtoupper($results['surname']) == $results['surname']) {
						$results['surname'] = ucwords(strtolower($results['surname']));
					}

					$personDescription->addStatement('surname', $results['surname']);
				}

				// Prefix/Suffix
				foreach(array('prefix', 'suffix') as $propertyName) {
					if (!empty($results[$propertyName])) {
						$results[$propertyName] = trim($results[$propertyName]);
						$personDescription->addStatement($propertyName, $results[$propertyName]);
					}
				}

				break;
			}
		}

		return $personDescription;
	}
}
?>
