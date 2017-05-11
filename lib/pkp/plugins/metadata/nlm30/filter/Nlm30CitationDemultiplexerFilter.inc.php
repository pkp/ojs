<?php
/**
 * @defgroup plugins_metadata_nlm30_filter NLM 3.0 Metadata Filter
 */

/**
 * @file plugins/metadata/nlm30/filter/Nlm30CitationDemultiplexerFilter.inc.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2000-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class Nlm30CitationDemultiplexerFilter
 * @ingroup plugins_metadata_nlm30_filter
 *
 * @brief Filter that takes a list of NLM citation descriptions and joins
 *  them into a single "best" citation.
 */

import('lib.pkp.classes.filter.Filter');

class Nlm30CitationDemultiplexerFilter extends Filter {
	/**
	 * @var MetadataDescription the original unfiltered description required
	 *  for scoring
	 */
	var $_originalDescription;

	/** @var string the original plain text citation required for scoring */
	var $_originalRawCitation;

	/** @var Nlm30CitationSchemaCitationOutputFormatFilter */
	var $_citationOutputFilter;

	/**
	 * Constructor
	 */
	function __construct() {
		$this->setDisplayName('Join several NLM Citation descriptions into a single citation'); // Only for internal debugging.

		parent::__construct('metadata::lib.pkp.plugins.metadata.nlm30.schema.Nlm30CitationSchema(CITATION)[]',
			'class::lib.pkp.classes.citation.Citation');
	}

	//
	// Setters and Getters
	//
	/**
	 * Set the original raw citation
	 * @param $originalRawCitation string
	 */
	function setOriginalRawCitation($originalRawCitation) {
		$this->_originalRawCitation = $originalRawCitation;
	}

	/**
	 * Get the original raw citation
	 * @return string
	 */
	function getOriginalRawCitation() {
		return $this->_originalRawCitation;
	}

	/**
	 * Set the original citation description
	 * @param $originalDescription MetadataDescription
	 */
	function setOriginalDescription(&$originalDescription) {
		$this->_originalDescription =& $originalDescription;
	}

	/**
	 * Get the original citation description
	 * @return MetadataDescription
	 */
	function &getOriginalDescription() {
		return $this->_originalDescription;
	}

	/**
	 * Set the citation output filter
	 * @param $citationOutputFilter Nlm30CitationSchemaCitationOutputFormatFilter
	 */
	function setCitationOutputFilter(&$citationOutputFilter) {
		$this->_citationOutputFilter =& $citationOutputFilter;
	}

	/**
	 * Get the citation output filter
	 * @return Nlm30CitationSchemaCitationOutputFormatFilter
	 */
	function &getCitationOutputFilter() {
		return $this->_citationOutputFilter;
	}


	//
	// Implementing abstract template methods from Filter
	//
	/**
	 * @see Filter::process()
	 * @param $input array incoming MetadataDescriptions
	 * @return Citation
	 */
	function &process(&$input) {
		// Initialize the array that will contain citations by confidence score.
		// This is a two-dimensional array that with the score as key and
		// the scored citations as values.
		$scoredCitations = array();

		// Add the original citation to the citation options to be
		// scored. This is to make sure that we don't downgrade
		// our results.
		$citationOptions = $input;
		$citationOptions[] =& $this->getOriginalDescription();

		// Iterate over the incoming NLM citation descriptions
		foreach ($citationOptions as $citationOption) {
			// Calculate the score for this filtered citation
			$confidenceScore = $this->_filterConfidenceScore($citationOption);

			// Save the filtered result hashed by its confidence score.
			// We save them as a sub-array in case several citations
			// receive the same confidence score.
			if (!isset($scoredCitations[$confidenceScore])) {
				$scoredCitations[$confidenceScore] = array();
			}
			$scoredCitations[$confidenceScore][] =& $citationOption;
			unset ($citationOption);
		}

		// Get a single set of "best" values for the citation description
		// and set them in a new citation object. Don't accept results that
		// don't stem from citations that got at least 50% of the original
		// text right.
		$citation =& $this->_guessValues($scoredCitations, 50);
		return $citation;
	}

	//
	// Private helper methods
	//
	/**
	 * Derive a confidence score calculated as the similarity of the
	 * original raw citation and the citation text generated from the
	 * citation description.
	 * @param $metadataDescription MetadataDescription
	 * @return integer filter confidence score
	 */
	function _filterConfidenceScore(&$metadataDescription) {
		// Retrieve the original plain text citation.
		$originalCitation = $this->getOriginalRawCitation();

		// Generate the formatted citation output from the description.
		$citationOutputFilter =& $this->getCitationOutputFilter();
		$generatedCitation = $citationOutputFilter->execute($metadataDescription);

		// Strip formatting and the Google Scholar tag so that we get a plain
		// text string that is comparable with the raw citation.
		$generatedCitation = trim(str_replace(GOOGLE_SCHOLAR_TAG, '', strip_tags($generatedCitation)));

		// Compare the original to the generated citation.
		$citationDiff = PKPString::diff($originalCitation, $generatedCitation);

		// Calculate similarity as the number of deleted characters in relation to the
		// number of characters in the original citation. This intentionally excludes
		// additions as these can represent useful data like a DOI or an external link.
		$deletedCharacters = 0;
		foreach($citationDiff as $diffPart) {
			// Identify deletions.
			if (key($diffPart) == -1) {
				$deletedCharacters += PKPString::strlen(current($diffPart));
			}
		}
		$originalCharacters = PKPString::strlen($originalCitation);
		$partOfCommonCharacters = ($originalCharacters-$deletedCharacters) / $originalCharacters;

		$filterConfidenceScore = (integer)round(min($partOfCommonCharacters*100, 100));
		return $filterConfidenceScore;
	}

	/**
	 * Take an array of citation parse/lookup results and derive a citation
	 * with one "best" set of values.
	 *
	 * We determine the best values within the citations that have a score above
	 * the given threshold. Citations with a score below the threshold will be
	 * ignored.
	 *
	 * For these citations we count the frequency of values per meta-data property.
	 * The most frequent value will be chosen as "best" value.
	 *
	 * If two values have the same frequency then decide based on the score. If
	 * this is still ambivalent then return the first of the remaining values.
	 *
	 * @param $scoredCitations
	 * @param $scoreThreshold integer a number between 0 (=no threshold) and 100
	 * @return Citation one citation with the "best" values set
	 */
	function &_guessValues(&$scoredCitations, $scoreThreshold) {
		assert($scoreThreshold >= 0 && $scoreThreshold <= 100);

		// Create the target citation description.
		$targetDescription = new MetadataDescription('lib.pkp.plugins.metadata.nlm30.schema.Nlm30CitationSchema', ASSOC_TYPE_CITATION);

		// Step 1: List all values and max scores that have been identified for a given element
		//         but only include values from results above a given scoring threshold

		// Initialize variables for the first step.
		$valuesByPropertyName = array();
		$maxScoresByPropertyNameAndValue = array();

		// Sort the scored citations by score with the highest score first.
		krsort($scoredCitations);
		foreach ($scoredCitations as $currentScore => $citationsForCurrentScore) {
			// Check whether the current score is below the threshold, if so
			// stop the loop. We've sorted our citations by score so the remaining
			// citations all have scores below the threshold and we can forget
			// about them.
			if ($currentScore < $scoreThreshold) {
				break;
			}

			foreach($citationsForCurrentScore as $citationForCurrentScore) {
				$statements = $citationForCurrentScore->getStatements();

				// Add the property values and scores of this citation
				// to the overall property lists
				foreach($statements as $propertyName => $value) {
					// Initialize sub-arrays if necessary
					if (!isset($valuesByPropertyName[$propertyName])) {
						$valuesByPropertyName[$propertyName] = array();
					}
					if (!isset($maxScoresByPropertyNameAndValue[$propertyName])) {
						$maxScoresByPropertyNameAndValue[$propertyName] = array();
					}

					// Add the value for the given property, as we want to count
					// value frequencies later, we explicitly allow duplicates.
					$serializedValue = serialize($value);
					$valuesByPropertyName[$propertyName][] = $serializedValue;

					// As we have ordered our citations descending by score, the
					// first score found for a value is also the maximum score.
					if (!isset($maxScoresByPropertyNameAndValue[$propertyName][$serializedValue])) {
						$maxScoresByPropertyNameAndValue[$propertyName][$serializedValue] = $currentScore;
					}
				}
			}
		}

		// Step 2: Find out the values that occur most frequently for each element
		//         and order these by score.

		foreach($valuesByPropertyName as $propertyName => $values) {
			// Count the occurrences of each value within the given element
			$valueFrequencies = array_count_values($values);

			// Order the most frequent values to the beginning of the array
			arsort($valueFrequencies);

			// Get the most frequent values (may be several if there are more than one
			// with the same frequency).
			$scoresOfMostFrequentValues = array();
			$previousValueFrequency = 0;
			foreach($valueFrequencies as $value => $valueFrequency) {
				// Only extract the most frequent values, jump out of the
				// loop when less frequent values start.
				if ($previousValueFrequency > $valueFrequency) break;
				$previousValueFrequency = $valueFrequency;

				$scoresOfMostFrequentValues[$value] =
						$maxScoresByPropertyNameAndValue[$propertyName][$value];
			}

			// Now we can order the most frequent values by score, starting
			// with the highest score.
			arsort($scoresOfMostFrequentValues);

			// Now get the first key which represents the value with the
			// highest frequency and the highest score.
			reset($scoresOfMostFrequentValues);
			$bestValue = unserialize(key($scoresOfMostFrequentValues));

			// Set the found "best" element value in the result citation.
			$statements = array($propertyName => $bestValue);
			$success = $targetDescription->setStatements($statements);
			assert((boolean) $success);
		}

		// Instantiate the target citation
		$targetCitation = new Citation();
		$targetCitation->injectMetadata($targetDescription);
		return $targetCitation;
	}
}
?>
