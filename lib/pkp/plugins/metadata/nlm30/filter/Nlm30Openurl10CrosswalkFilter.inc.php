<?php

/**
 * @file plugins/metadata/nlm30/filter/Nlm30Openurl10CrosswalkFilter.inc.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2000-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class Nlm30Openurl10CrosswalkFilter
 * @ingroup plugins_metadata_nlm30_filter
 * @see Nlm30CitationSchema
 * @see Openurl10BookSchema
 * @see Openurl10JournalSchema
 * @see Openurl10DissertationSchema
 *
 * @brief Filter that converts from NLM citation to
 *  OpenURL schemas.
 */

import('lib.pkp.classes.metadata.CrosswalkFilter');
import('lib.pkp.plugins.metadata.nlm30.schema.Nlm30CitationSchema');
import('lib.pkp.plugins.metadata.openurl10.schema.Openurl10JournalSchema');
import('lib.pkp.plugins.metadata.openurl10.schema.Openurl10BookSchema');
import('lib.pkp.plugins.metadata.openurl10.schema.Openurl10DissertationSchema');

class Nlm30Openurl10CrosswalkFilter extends CrosswalkFilter {
	/**
	 * Constructor
	 * @param $fromSchema string fully qualified class name of supported input meta-data schema
	 * @param $toSchema string fully qualified class name of supported output meta-data schema
	 */
	function __construct($fromSchema, $toSchema) {
		parent::__construct($fromSchema, $toSchema);
	}

	//
	// Protected helper methods
	//
	/**
	 * Create a mapping of NLM properties to OpenURL
	 * properties that do not need special processing.
	 * @param $publicationType The NLM publication type
	 * @param $openurl10Schema MetadataSchema
	 * @return array
	 */
	function &nlmOpenurl10Mapping($publicationType, &$openurl10Schema) {
		$propertyMap = array();

		// Map titles and date
		switch($publicationType) {
			case NLM30_PUBLICATION_TYPE_JOURNAL:
				$propertyMap['source'] = 'jtitle';
				$propertyMap['article-title'] = 'atitle';
				break;

			case NLM30_PUBLICATION_TYPE_CONFPROC:
				$propertyMap['conf-name'] = 'jtitle';
				$propertyMap['article-title'] = 'atitle';
				if ($input->hasStatement('conf-date')) {
					$propertyMap['conf-date'] = 'date';
				}
				break;

			case NLM30_PUBLICATION_TYPE_BOOK:
				$propertyMap['source'] = 'btitle';
				$propertyMap['chapter-title'] = 'atitle';
				break;

			case NLM30_PUBLICATION_TYPE_THESIS:
				$propertyMap['article-title'] = 'title';
				break;
		}

		// Map the date (if it's not already mapped).
		if (!isset($propertyMap['conf-date'])) {
			$propertyMap['date'] = 'date';
		}

		// ISBN is common to all OpenURL schemas and
		// can be mapped one-to-one.
		$propertyMap['isbn'] = 'isbn';

		// Properties common to OpenURL book and journal
		if (is_a($openurl10Schema, 'Openurl10JournalBookBaseSchema')) {
			// Some properties can be mapped one-to-one
			$propertyMap += array(
				'issn[@pub-type="ppub"]' => 'issn',
				'fpage' => 'spage',
				'lpage' => 'epage'
			);

			// FIXME: Map 'aucorp' for OpenURL journal/book when we
			// have 'collab' statements in NLM citation.
		}

		// OpenURL journal properties
		// The properties 'chron' and 'quarter' remain unmatched.
		if (is_a($openurl10Schema, 'Openurl10JournalSchema')) {
			$propertyMap += array(
				'season' => 'ssn',
				'volume' => 'volume',
				'supplement' => 'part',
				'issue' => 'issue',
				'issn[@pub-type="epub"]' => 'eissn',
				'pub-id[@pub-id-type="publisher-id"]' => 'artnum',
				'pub-id[@pub-id-type="coden"]' => 'coden',
				'pub-id[@pub-id-type="sici"]' => 'sici'
			);
		}

		// OpenURL book properties
		// The 'bici' property remains unmatched.
		if (is_a($openurl10Schema, 'Openurl10BookSchema')) {
			$propertyMap += array(
				'publisher-loc' => 'place',
				'publisher-name' => 'pub',
				'edition' => 'edition',
				'size' => 'tpages',
				'series' => 'series'
			);
		}

		// OpenURL dissertation properties
		// The properties 'cc', 'advisor' and 'degree' remain unmatched
		// as NLM does not have good dissertation support.
		if (is_a($openurl10Schema, 'Openurl10DisertationSchema')) {
			$propertyMap += array(
				'size' => 'tpages',
				'publisher-loc' => 'co',
				'institution' => 'inst'
			);
		}

		return $propertyMap;
	}
}
?>
