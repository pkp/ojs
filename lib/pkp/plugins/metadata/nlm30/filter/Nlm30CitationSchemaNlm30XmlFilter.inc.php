<?php

/**
 * @file plugins/metadata/nlm30/filter/Nlm30CitationSchemaNlm30XmlFilter.inc.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2000-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class Nlm30CitationSchemaNlm30XmlFilter
 * @ingroup plugins_metadata_nlm30_filter
 *
 * @brief Filter that transforms NLM citation metadata descriptions into
 *  NLM 3.0 XML citation output.
 */


import('lib.pkp.plugins.metadata.nlm30.filter.Nlm30CitationSchemaCitationOutputFormatFilter');

class Nlm30CitationSchemaNlm30XmlFilter extends Nlm30CitationSchemaCitationOutputFormatFilter {
	/**
	 * Constructor
	 * @param $filterGroup FilterGroup
	 */
	function __construct($filterGroup) {
		$this->setDisplayName('NLM 3.0 XML Citation Output');

		parent::__construct($filterGroup);
	}


	//
	// Implement template methods from PersistableFilter
	//
	/**
	 * @copydoc PersistableFilter::getClassName()
	 */
	function getClassName() {
		return 'lib.pkp.plugins.metadata.nlm30.filter.Nlm30CitationSchemaNlm30XmlFilter';
	}


	//
	// Implement abstract template methods from TemplateBasedFilter
	//
	/**
	 * @copydoc TemplateBasedFilter::addTemplateVars()
	 */
	function addTemplateVars($templateMgr, &$input, $request, &$locale) {
		// Assign the full meta-data description.
		$templateMgr->assign('metadataDescription', $input);

		parent::addTemplateVars($templateMgr, $input, $request, $locale);
	}

	/**
	 * @copydoc TemplateBasedFilter::getBasePath()
	 */
	function getBasePath() {
		return dirname(__FILE__);
	}
}
?>
