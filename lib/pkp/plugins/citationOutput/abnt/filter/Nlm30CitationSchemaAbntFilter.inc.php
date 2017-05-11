<?php
/**
 * @defgroup plugins_citationOutput_abnt_filter ABNT Citation Format Filter
 */

/**
 * @file plugins/citationOutput/abnt/filter/Nlm30CitationSchemaAbntFilter.inc.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2000-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class Nlm30CitationSchemaAbntFilter
 * @ingroup plugins_citationOutput_abnt_filter
 *
 * @brief Filter that transforms NLM citation metadata descriptions into
 *  ABNT citation output.
 */


import('lib.pkp.plugins.metadata.nlm30.filter.Nlm30CitationSchemaCitationOutputFormatFilter');

class Nlm30CitationSchemaAbntFilter extends Nlm30CitationSchemaCitationOutputFormatFilter {
	/**
	 * Constructor
	 * @param $filterGroup FilterGroup
	 */
	function __construct($filterGroup) {
		$this->setDisplayName('ABNT Citation Output');

		// FIXME: Implement conference proceedings support for ABNT.
		$this->setSupportedPublicationTypes(array(
			NLM30_PUBLICATION_TYPE_BOOK, NLM30_PUBLICATION_TYPE_JOURNAL
		));

		parent::__construct($filterGroup);
	}


	//
	// Implement template methods from PersistableFilter
	//
	/**
	 * @copydoc PersistableFilter::getClassName()
	 */
	function getClassName() {
		return 'lib.pkp.plugins.citationOutput.abnt.filter.Nlm30CitationSchemaAbntFilter';
	}


	//
	// Implement abstract template methods from TemplateBasedFilter
	//
	/**
	 * @copydoc TemplateBasedFilter::getBasePath()
	 */
	function getBasePath() {
		return dirname(__FILE__);
	}
}
?>
