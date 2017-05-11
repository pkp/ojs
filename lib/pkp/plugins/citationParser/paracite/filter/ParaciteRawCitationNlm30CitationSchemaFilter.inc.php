<?php
/**
 * @defgroup plugins_citationParser_paracite_filter ParaCite Citation Filter
 */

/**
 * @file plugins/citationParser/paracite/filter/ParaciteRawCitationNlm30CitationSchemaFilter.inc.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2000-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class ParaciteRawCitationNlm30CitationSchemaFilter
 * @ingroup plugins_citationParser_paracite_filter
 *
 * @brief Paracite parsing filter implementation.
 *
 *  The paracite parsing filter has one parameter: the citation module
 *  to be used. This can be one of "Standard", "Citebase" or "Jiao".
 *
 *  If you want to use various modules at the same time then you have
 *  to instantiate this parser filter several times with different
 *  configuration and chain all instances.
 *
 *  NB: This filter requires perl and CPAN's Biblio::Citation::Parser
 *  and Text::Unidecode packages to be installed on the server. It also
 *  requires the PHP shell_exec() function to be available which is often
 *  disabled in shared hosting environments.
 */


import('lib.pkp.plugins.metadata.nlm30.filter.Nlm30CitationSchemaFilter');
import('lib.pkp.plugins.metadata.nlm30.filter.Openurl10Nlm30CitationSchemaCrosswalkFilter');
import('lib.pkp.classes.filter.SetFilterSetting');

define('CITATION_PARSER_PARACITE_STANDARD', 'Standard');
define('CITATION_PARSER_PARACITE_CITEBASE', 'Citebase');
define('CITATION_PARSER_PARACITE_JIAO', 'Jiao');

class ParaciteRawCitationNlm30CitationSchemaFilter extends Nlm30CitationSchemaFilter {
	/*
	 * Constructor
	 * @param $filterGroup FilterGroup
	 */
	function __construct($filterGroup) {
		$this->setDisplayName('ParaCite');

		// Instantiate the settings of this filter
		$citationModuleSetting = new SetFilterSetting('citationModule',
				'metadata.filters.paracite.settings.citationModule.displayName',
				'metadata.filters.paracite.settings.citationModule.validationMessage',
				ParaciteRawCitationNlm30CitationSchemaFilter::getSupportedCitationModules());
		$this->addSetting($citationModuleSetting);

		parent::__construct($filterGroup);
	}

	//
	// Getters and Setters
	//
	/**
	 * get the citation module
	 * @return string
	 */
	function getCitationModule() {
		return $this->getData('citationModule');
	}


	//
	// Implement template methods from PersistableFilter
	//
	/**
	 * @copydoc PersistableFilter::getClassName()
	 */
	function getClassName() {
		return 'lib.pkp.plugins.citationParser.paracite.filter.ParaciteRawCitationNlm30CitationSchemaFilter';
	}


	//
	// Implement template methods from Filter
	//
	/**
	 * @copydoc Filter::process()
	 * @param $input string
	 * @return MetadataDescription
	 */
	function &process(&$input) {
		$citationString =& $input;
		$nullVar = null;

		// Check the availability of perl
		$perlCommand = Config::getVar('cli', 'perl');
		if (empty($perlCommand) || !file_exists($perlCommand)) return $nullVar;

		// Convert to ASCII - Paracite doesn't handle UTF-8 well
		$citationString = PKPString::utf8_to_ascii($citationString);

		// Call the paracite parser
		$wrapperScript = dirname(__FILE__).DIRECTORY_SEPARATOR.'paracite.pl';
		$paraciteCommand = $perlCommand.' '.escapeshellarg($wrapperScript).' '.
			$this->getCitationModule().' '.escapeshellarg($citationString);
		$xmlResult = shell_exec($paraciteCommand);
		if (empty($xmlResult)) return $nullVar;

		if ( Config::getVar('i18n', 'charset_normalization') == 'On' && !PKPString::utf8_compliant($xmlResult) ) {
			$xmlResult = PKPString::utf8_normalize($xmlResult);
		}

		// Create a temporary DOM document
		$resultDOM = new DOMDocument();
		$resultDOM->recover = true;
		$resultDOM->loadXML($xmlResult);

		// Extract the parser results as an array
		$xmlHelper = new XMLHelper();
		$metadata = $xmlHelper->xmlToArray($resultDOM->documentElement);

		// We have to merge subtitle and title as neither OpenURL
		// nor NLM can handle subtitles.
		if (isset($metadata['subtitle'])) {
			$metadata['title'] .= '. '.$metadata['subtitle'];
			unset($metadata['subtitle']);
		}

		// Break up the authors field
		if (isset($metadata['authors'])) {
			$metadata['authors'] = PKPString::trimPunctuation($metadata['authors']);
			$metadata['authors'] = PKPString::iterativeExplode(array(':', ';'), $metadata['authors']);
		}

		// Convert pages to integers
		foreach(array('spage', 'epage') as $pageProperty) {
			if (isset($metadata[$pageProperty])) $metadata[$pageProperty] = (integer)$metadata[$pageProperty];
		}

		// Convert titles to title case
		foreach(array('title', 'chapter', 'publication') as $titleProperty) {
			if (isset($metadata[$titleProperty])) $metadata[$titleProperty] = PKPString::titleCase($metadata[$titleProperty]);
		}

		// Map ParaCite results to OpenURL - null means
		// throw the value away.
		$metadataMapping = array(
			'genre' => 'genre',
			'_class' => null,
			'any' => null,
			'authors' => 'au',
			'aufirst' => 'aufirst',
			'aufull' => null,
			'auinit' => 'auinit',
			'aulast' => 'aulast',
			'atitle' => 'atitle',
			'cappublication' => null,
			'captitle' => null,
			'date' => 'date',
			'epage' => 'epage',
			'featureID' => null,
			'id' => null,
			'issue' => 'issue',
			'jnl_epos' => null,
			'jnl_spos' => null,
			'match' => null,
			'marked' => null,
			'num_of_fig' => null,
			'pages' => 'pages',
			'publisher' => 'pub',
			'publoc' => 'place',
			'ref' => null,
			'rest_text' => null,
			'spage' => 'spage',
			'targetURL' => 'url',
			'text' => null,
			'ucpublication' => null,
			'uctitle' => null,
			'volume' => 'volume',
			'year' => 'date'
		);

		// Ignore 'year' if 'date' is set
		if (isset($metadata['date'])) {
			$metadataMapping['year'] = null;
		}

		// Set default genre
		if (empty($metadata['genre'])) $metadata['genre'] = OPENURL10_GENRE_ARTICLE;

		// Handle title, chapter and publication depending on
		// the (inferred) genre. Also instantiate the target schema.
		switch($metadata['genre']) {
			case OPENURL10_GENRE_BOOK:
			case OPENURL10_GENRE_BOOKITEM:
			case OPENURL10_GENRE_REPORT:
			case OPENURL10_GENRE_DOCUMENT:
				$metadataMapping += array(
					'publication' => 'btitle',
					'chapter' => 'atitle'
				);
				if (isset($metadata['title'])) {
					if (!isset($metadata['publication'])) {
						$metadata['publication'] = $metadata['title'];
					} elseif (!isset($metadata['chapter'])) {
						$metadata['chapter'] = $metadata['title'];
					}
					unset($metadata['title']);
				}
				$openurl10SchemaName = 'lib.pkp.plugins.metadata.openurl10.schema.Openurl10BookSchema';
				$openurl10SchemaClass = 'Openurl10BookSchema';
				break;

			case OPENURL10_GENRE_ARTICLE:
			case OPENURL10_GENRE_JOURNAL:
			case OPENURL10_GENRE_ISSUE:
			case OPENURL10_GENRE_CONFERENCE:
			case OPENURL10_GENRE_PROCEEDING:
			case OPENURL10_GENRE_PREPRINT:
			default:
				$metadataMapping += array('publication' => 'jtitle');
				if (isset($metadata['title'])) {
					if (!isset($metadata['publication'])) {
						$metadata['publication'] = $metadata['title'];
					} elseif (!isset($metadata['atitle'])) {
						$metadata['atitle'] = $metadata['title'];
					}
					unset($metadata['title']);
				}
				$openurl10SchemaName = 'lib.pkp.plugins.metadata.openurl10.schema.Openurl10JournalSchema';
				$openurl10SchemaClass = 'Openurl10JournalSchema';
				break;
		}

		// Instantiate an OpenURL description
		$openurl10Description = new MetadataDescription($openurl10SchemaName, ASSOC_TYPE_CITATION);
		$openurl10Schema = new $openurl10SchemaClass();

		// Map the ParaCite result to OpenURL
		foreach ($metadata as $paraciteElementName => $paraciteValue) {
			if (!empty($paraciteValue)) {
				// Trim punctuation
				if (is_string($paraciteValue)) $paraciteValue = PKPString::trimPunctuation($paraciteValue);

				// Transfer the value to the OpenURL result array
				assert(array_key_exists($paraciteElementName, $metadataMapping));
				$openurl10PropertyName = $metadataMapping[$paraciteElementName];
				if (!is_null($openurl10PropertyName) && $openurl10Schema->hasProperty($openurl10PropertyName)) {
					if (is_array($paraciteValue)) {
						foreach($paraciteValue as $singleValue) {
							$success = $openurl10Description->addStatement($openurl10PropertyName, $singleValue);
							assert((boolean) $success);
						}
					} else {
						$success = $openurl10Description->addStatement($openurl10PropertyName, $paraciteValue);
						assert((boolean) $success);
					}
				}
			}
		}

		// Crosswalk to NLM
		$crosswalkFilter = new Openurl10Nlm30CitationSchemaCrosswalkFilter();
		$nlm30Description =& $crosswalkFilter->execute($openurl10Description);
		assert(is_a($nlm30Description, 'MetadataDescription'));

		// Add 'rest_text' as NLM comment (if given)
		if (isset($metadata['rest_text'])) {
			$nlm30Description->addStatement('comment', PKPString::trimPunctuation($metadata['rest_text']));
		}

		// Set display name and sequence id in the meta-data description
		// to the corresponding values from the filter. This is important
		// so that we later know which result came from which filter.
		$nlm30Description->setDisplayName($this->getDisplayName());
		$nlm30Description->setSequence($this->getSequence());

		return $nlm30Description;
	}


	//
	// Private helper methods
	//
	/**
	 * Return supported paracite citation parser modules
	 * @return array supported citation modules
	 */
	static function getSupportedCitationModules() {
		static $_supportedCitationModules = array(
			CITATION_PARSER_PARACITE_STANDARD,
			CITATION_PARSER_PARACITE_CITEBASE,
			CITATION_PARSER_PARACITE_JIAO
		);

		return $_supportedCitationModules;
	}
}

?>
