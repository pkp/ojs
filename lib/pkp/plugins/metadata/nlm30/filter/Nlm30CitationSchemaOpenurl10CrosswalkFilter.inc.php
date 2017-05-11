<?php

/**
 * @file plugins/metadata/nlm30/filter/Nlm30CitationSchemaOpenurl10CrosswalkFilter.inc.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2000-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class Nlm30CitationSchemaOpenurl10CrosswalkFilter
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

class Nlm30CitationSchemaOpenurl10CrosswalkFilter extends Nlm30Openurl10CrosswalkFilter {
	/**
	 * Constructor
	 */
	function __construct() {
		$this->setDisplayName('Crosswalk from NLM Citation to Open URL');
		parent::__construct('lib.pkp.plugins.metadata.nlm30.schema.Nlm30CitationSchema',
				'lib.pkp.plugins.metadata.openurl10.schema.Openurl10BaseSchema');
	}


	//
	// Implement template methods from Filter
	//
	/**
	 * Map NLM properties to OpenURL properties.
	 * NB: OpenURL has no i18n so we use the default
	 * locale when mapping.
	 * @see Filter::process()
	 * @param $input MetadataDescription
	 * @return MetadataDescription
	 */
	function &process(&$input) {
		$nullVar = null;
		// Identify the genre of the target record and
		// instantiate the target description.
		$publicationType = $input->getStatement('[@publication-type]');
		switch($publicationType) {
			case NLM30_PUBLICATION_TYPE_JOURNAL:
			case NLM30_PUBLICATION_TYPE_CONFPROC:
				$outputSchemaName = 'lib.pkp.plugins.metadata.openurl10.schema.Openurl10JournalSchema';
				break;

			case NLM30_PUBLICATION_TYPE_BOOK:
				$outputSchemaName = 'lib.pkp.plugins.metadata.openurl10.schema.Openurl10BookSchema';
				break;

			case NLM30_PUBLICATION_TYPE_THESIS:
				$outputSchemaName = 'lib.pkp.plugins.metadata.openurl10.schema.Openurl10DissertationSchema';
				break;

			default:
				// Unsupported type
				return $nullVar;
		}

		// Create the target description
		$output = new MetadataDescription($outputSchemaName, $input->getAssocType());

		// Transform authors
		import('lib.pkp.plugins.metadata.nlm30.filter.Nlm30NameSchemaPersonStringFilter');
		$personStringFilter = new Nlm30NameSchemaPersonStringFilter();
		$authors =& $input->getStatement('person-group[@person-group-type="author"]');
		if (is_array($authors) && count($authors)) {
			$aulast = ($authors[0]->hasStatement('prefix') ? $authors[0]->getStatement('prefix').' ' : '');
			$aulast .= $authors[0]->getStatement('surname');
			if (!empty($aulast)) {
				$success = $output->addStatement('aulast', $aulast);
				assert((boolean) $success);
			}

			$givenNames = $authors[0]->getStatement('given-names');
			if(is_array($givenNames) && count($givenNames)) {
				$aufirst = implode(' ', $givenNames);
				if (!empty($aufirst)) {
					$success = $output->addStatement('aufirst', $aufirst);
					assert((boolean) $success);
				}

				$initials = array();
				foreach($givenNames as $givenName) {
					$initials[] = substr($givenName, 0, 1);
				}

				$auinit1 = array_shift($initials);
				if (!empty($auinit1)) {
					$success = $output->addStatement('auinit1', $auinit1);
					assert((boolean) $success);
				}

				$auinitm = implode('', $initials);
				if (!empty($auinitm)) {
					$success = $output->addStatement('auinitm', $auinitm);
					assert((boolean) $success);
				}

				$auinit = $auinit1.$auinitm;
				if (!empty($auinit)) {
					$success = $output->addStatement('auinit', $auinit);
					assert((boolean) $success);
				}
			}

			$ausuffix = $authors[0]->getStatement('suffix');
			if (!empty($ausuffix)) {
				$success = $output->addStatement('ausuffix', $ausuffix);
				assert((boolean) $success);
			}

			foreach ($authors as $author) {
				if ($author == PERSON_STRING_FILTER_ETAL) {
					$au = $author;
				} else {
					$au = $personStringFilter->execute($author);
				}
				$success = $output->addStatement('au', $au);
				assert((boolean) $success);
				unset($au);
			}
		}

		// Genre: Guesswork
		if (is_a($output->getMetadataSchema(), 'Openurl10JournalBookBaseSchema')) {
			switch($publicationType) {
				case NLM30_PUBLICATION_TYPE_JOURNAL:
					$genre = ($input->hasProperty('article-title') ? OPENURL10_GENRE_ARTICLE : OPENURL10_GENRE_JOURNAL);
					break;

				case NLM30_PUBLICATION_TYPE_CONFPROC:
					$genre = ($input->hasProperty('article-title') ? OPENURL10_GENRE_PROCEEDING : OPENURL10_GENRE_CONFERENCE);
					break;

				case NLM30_PUBLICATION_TYPE_BOOK:
					$genre = ($input->hasProperty('article-title') ? OPENURL10_GENRE_BOOKITEM : OPENURL10_GENRE_BOOK);
					break;
			}
			assert(!empty($genre));
			$success = $output->addStatement('genre', $genre);
			assert((boolean) $success);
		}

		// Map remaining properties (NLM => OpenURL)
		$propertyMap =& $this->nlmOpenurl10Mapping($publicationType, $output->getMetadataSchema());

		// Transfer mapped properties with default locale
		foreach ($propertyMap as $nlm30Property => $openurl10Property) {
			if ($input->hasStatement($nlm30Property)) {
				$success = $output->addStatement($openurl10Property, $input->getStatement($nlm30Property));
				assert((boolean) $success);
			}
		}

		return $output;
	}
}
?>
