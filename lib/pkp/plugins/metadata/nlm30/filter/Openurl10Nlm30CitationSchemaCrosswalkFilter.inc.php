<?php

/**
 * @file plugins/metadata/nlm30/filter/Openurl10Nlm30CitationSchemaCrosswalkFilter.inc.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2000-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class Openurl10Nlm30CitationSchemaCrosswalkFilter
 * @ingroup plugins_metadata_nlm30_filter
 * @see Nlm30CitationSchema
 * @see Openurl10BookSchema
 * @see Openurl10JournalSchema
 * @see Openurl10DissertationSchema
 *
 * @brief Filter that converts from NLM citation to
 *  OpenURL schemas.
 */

import('lib.pkp.plugins.metadata.nlm30.filter.Nlm30Openurl10CrosswalkFilter');

class Openurl10Nlm30CitationSchemaCrosswalkFilter extends Nlm30Openurl10CrosswalkFilter {
	/**
	 * Constructor
	 */
	function __construct() {
		$this->setDisplayName('Crosswalk from Open URL to NLM Citation');
		parent::__construct('lib.pkp.plugins.metadata.openurl10.schema.Openurl10BaseSchema',
				'lib.pkp.plugins.metadata.nlm30.schema.Nlm30CitationSchema');
	}


	//
	// Implement template methods from Filter
	//
	/**
	 * Map OpenURL properties to NLM properties.
	 * NB: OpenURL has no i18n so we use the default
	 * locale when mapping.
	 * @see Filter::process()
	 * @param $input MetadataDescription
	 * @return MetadataDescription
	 */
	function &process(&$input) {
		$nullVar = null;

		// Instantiate the target description.
		$output = new MetadataDescription('lib.pkp.plugins.metadata.nlm30.schema.Nlm30CitationSchema', $input->getAssocType());

		// Parse au statements into name descriptions
		import('lib.pkp.plugins.metadata.nlm30.filter.PersonStringNlm30NameSchemaFilter');
		$personStringFilter = new PersonStringNlm30NameSchemaFilter(ASSOC_TYPE_AUTHOR);
		$authors =& $input->getStatement('au');
		if (is_array($authors) && count($authors)) {
			// TODO: We might improve results here by constructing the
			// first author from aufirst, aulast fields.
			foreach ($authors as $author) {
				$authorDescription =& $personStringFilter->execute($author);
				$success = $output->addStatement('person-group[@person-group-type="author"]', $authorDescription);
				assert((boolean) $success);
				unset($authorDescription);
			}
		}

		// Publication type
		$publicationType = null;
		if ($input->hasStatement('genre')) {
			$genre = $input->getStatement('genre');
			$genreMap = $this->_getOpenurl10GenreTranslationMapping();
			$publicationType = (isset($genreMap[$genre]) ? $genreMap[$genre] : $genre);
			$success = $output->addStatement('[@publication-type]', $publicationType);
			assert((boolean) $success);
		}

		// Get NLM => OpenURL property mapping.
		$propertyMap =& $this->nlmOpenurl10Mapping($publicationType, $input->getMetadataSchema());

		// Transfer mapped properties with default locale
		foreach ($propertyMap as $nlm30Property => $openurl10Property) {
			if ($input->hasStatement($openurl10Property)) {
				$success = $output->addStatement($nlm30Property, $input->getStatement($openurl10Property));
				assert((boolean) $success);
			}
		}

		return $output;
	}

	//
	// Private helper methods
	//
	/**
	 * Return a mapping of OpenURL genres to NLM publication
	 * types.
	 * @return array
	 */
	static function _getOpenurl10GenreTranslationMapping() {
		static $openurl10GenreTranslationMapping = array(
			OPENURL10_GENRE_ARTICLE => NLM30_PUBLICATION_TYPE_JOURNAL,
			OPENURL10_GENRE_ISSUE => NLM30_PUBLICATION_TYPE_JOURNAL,
			OPENURL10_GENRE_CONFERENCE => NLM30_PUBLICATION_TYPE_CONFPROC,
			OPENURL10_GENRE_PROCEEDING => NLM30_PUBLICATION_TYPE_CONFPROC,
			OPENURL10_GENRE_PREPRINT => NLM30_PUBLICATION_TYPE_JOURNAL,
			OPENURL10_GENRE_BOOKITEM => NLM30_PUBLICATION_TYPE_BOOK,
			OPENURL10_GENRE_BOOK => NLM30_PUBLICATION_TYPE_BOOK,
			OPENURL10_GENRE_REPORT => NLM30_PUBLICATION_TYPE_BOOK,
			OPENURL10_GENRE_DOCUMENT => NLM30_PUBLICATION_TYPE_BOOK,
			OPENURL10_PSEUDOGENRE_DISSERTATION => NLM30_PUBLICATION_TYPE_THESIS
		);

		return $openurl10GenreTranslationMapping;
	}
}

?>
