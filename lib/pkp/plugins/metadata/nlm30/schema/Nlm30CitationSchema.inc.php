<?php
/**
 * @defgroup plugins_metadata_nlm30_schema NLM 3.0 Schema
 */

/**
 * @file plugins/metadata/nlm30/schema/Nlm30CitationSchema.inc.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2000-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class Nlm30CitationSchema
 * @ingroup plugins_metadata_nlm30_schema
 * @see MetadataSchema
 *
 * @brief @verbatim Class that provides meta-data properties compliant with
 *  the NLM element-citation tag from the NLM Journal Publishing Tag Set
 *  Version 3.0. We only use the "references class" of elements allowed
 *  in the element-citation tag. We do not support all sub-elements
 *  but only those we have use-cases for. We map elements and attributes
 *  from the original XML standard to 'element[@attribute="..."]' property
 *  names.
 *
 *  For details see <http://dtd.nlm.nih.gov/publishing/>,
 *  <http://dtd.nlm.nih.gov/publishing/tag-library/3.0/n-8xa0.html>,
 *  <http://dtd.nlm.nih.gov/publishing/tag-library/3.0/n-5332.html> and
 *  <http://dtd.nlm.nih.gov/publishing/tag-library/3.0/n-fmz0.html>.
 * @endverbatim
 */

import('lib.pkp.classes.metadata.MetadataSchema');

// Define the well-known elements of the NLM publication type vocabulary.
define('NLM30_PUBLICATION_TYPE_JOURNAL', 'journal');
define('NLM30_PUBLICATION_TYPE_CONFPROC', 'conf-proc');
define('NLM30_PUBLICATION_TYPE_BOOK', 'book');
define('NLM30_PUBLICATION_TYPE_THESIS', 'thesis');

class Nlm30CitationSchema extends MetadataSchema {
	/**
	 * Constructor
	 */
	function __construct() {
		// Configure the meta-data schema.
		parent::__construct(
			'nlm-3.0-element-citation',
			'nlm30',
			'lib.pkp.plugins.metadata.nlm30.schema.Nlm30CitationSchema',
			ASSOC_TYPE_CITATION
		);

		$this->addProperty('person-group[@person-group-type="author"]', array(array(METADATA_PROPERTY_TYPE_COMPOSITE => ASSOC_TYPE_AUTHOR), METADATA_PROPERTY_TYPE_STRING), false, METADATA_PROPERTY_CARDINALITY_MANY, 'metadata.property.displayName.author', 'metadata.property.validationMessage.author');
		$this->addProperty('person-group[@person-group-type="editor"]', array(array(METADATA_PROPERTY_TYPE_COMPOSITE => ASSOC_TYPE_EDITOR), METADATA_PROPERTY_TYPE_STRING), false, METADATA_PROPERTY_CARDINALITY_MANY, 'metadata.property.displayName.editor', 'metadata.property.validationMessage.editor');
		$this->addProperty('article-title', METADATA_PROPERTY_TYPE_STRING, true);
		$this->addProperty('source', METADATA_PROPERTY_TYPE_STRING, true);
		$this->addProperty('date', METADATA_PROPERTY_TYPE_DATE);
		$this->addProperty('date-in-citation[@content-type="access-date"]', METADATA_PROPERTY_TYPE_DATE, false, METADATA_PROPERTY_CARDINALITY_ONE, 'metadata.property.displayName.access-date', 'metadata.property.validationMessage.access-date');
		$this->addProperty('issue');
		$this->addProperty('volume');
		$this->addProperty('season');
		$this->addProperty('chapter-title', METADATA_PROPERTY_TYPE_STRING, true);
		$this->addProperty('edition');
		$this->addProperty('series');
		$this->addProperty('supplement');
		$this->addProperty('conf-date', METADATA_PROPERTY_TYPE_DATE);
		$this->addProperty('conf-loc');
		$this->addProperty('conf-name');
		$this->addProperty('conf-sponsor');
		$this->addProperty('institution');
		$this->addProperty('fpage', METADATA_PROPERTY_TYPE_INTEGER);
		$this->addProperty('lpage', METADATA_PROPERTY_TYPE_INTEGER);
		$this->addProperty('size', METADATA_PROPERTY_TYPE_INTEGER);
		$this->addProperty('publisher-loc');
		$this->addProperty('publisher-name');
		$this->addProperty('isbn');
		$this->addProperty('issn[@pub-type="ppub"]', METADATA_PROPERTY_TYPE_STRING, false, METADATA_PROPERTY_CARDINALITY_ONE, 'metadata.property.displayName.issn', 'metadata.property.validationMessage.issn');
		$this->addProperty('issn[@pub-type="epub"]', METADATA_PROPERTY_TYPE_STRING, false, METADATA_PROPERTY_CARDINALITY_ONE, 'metadata.property.displayName.eissn', 'metadata.property.validationMessage.eissn');
		$this->addProperty('pub-id[@pub-id-type="doi"]', METADATA_PROPERTY_TYPE_STRING, false, METADATA_PROPERTY_CARDINALITY_ONE, 'metadata.property.displayName.doi', 'metadata.property.validationMessage.doi');
		$this->addProperty('pub-id[@pub-id-type="publisher-id"]', METADATA_PROPERTY_TYPE_STRING, false, METADATA_PROPERTY_CARDINALITY_ONE, 'metadata.property.displayName.publisher-id', 'metadata.property.validationMessage.publisher-id');
		$this->addProperty('pub-id[@pub-id-type="coden"]', METADATA_PROPERTY_TYPE_STRING, false, METADATA_PROPERTY_CARDINALITY_ONE, 'metadata.property.displayName.coden', 'metadata.property.validationMessage.coden');
		$this->addProperty('pub-id[@pub-id-type="sici"]', METADATA_PROPERTY_TYPE_STRING, false, METADATA_PROPERTY_CARDINALITY_ONE, 'metadata.property.displayName.sici', 'metadata.property.validationMessage.sici');
		$this->addProperty('pub-id[@pub-id-type="pmid"]', METADATA_PROPERTY_TYPE_STRING, false, METADATA_PROPERTY_CARDINALITY_ONE, 'metadata.property.displayName.pmid', 'metadata.property.validationMessage.pmid');
		$this->addProperty('uri', METADATA_PROPERTY_TYPE_URI);
		$this->addProperty('comment');
		$this->addProperty('annotation');
		$this->addProperty('[@publication-type]', array(METADATA_PROPERTY_TYPE_VOCABULARY => 'nlm30-publication-types'), false, METADATA_PROPERTY_CARDINALITY_ONE, 'metadata.property.displayName.publication-type', 'metadata.property.validationMessage.publication-type');

		// NB: NLM citation does not have very good thesis support. We might
		// encode the degree in the publication type and the advisor as 'contrib'
		// with role 'advisor' in the future.
	}
}
?>
