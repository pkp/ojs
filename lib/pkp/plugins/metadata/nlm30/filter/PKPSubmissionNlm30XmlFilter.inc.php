<?php

/**
 * @file plugins/metadata/nlm30/filter/PKPSubmissionNlm30XmlFilter.inc.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2000-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class PKPSubmissionNlm30XmlFilter
 * @ingroup plugins_metadata_nlm30_filter
 *
 * @brief Class that converts a submission to an NLM Journal Publishing
 * Tag Set 3.0 XML document.
 *
 * FIXME: This class currently only generates partial (citation) NLM XML output.
 * Full NLM journal publishing tag set support still has to be added, see #5648
 * and the L8X development roadmap.
 */


import('lib.pkp.classes.citation.TemplateBasedReferencesListFilter');

class PKPSubmissionNlm30XmlFilter extends TemplateBasedReferencesListFilter {
	/**
	 * Constructor
	 * @param $filterGroup FilterGroup
	 */
	function __construct($filterGroup) {
		$this->setDisplayName('NLM Journal Publishing V3.0 ref-list');

		parent::__construct($filterGroup);

		// Set the output filter.
		$this->setData('citationOutputFilterName', 'lib.pkp.plugins.metadata.nlm30.filter.Nlm30CitationSchemaNlm30XmlFilter');
		// Set the metadata schema.
		$this->setData('metadataSchemaName', 'lib.pkp.plugins.metadata.nlm30.schema.Nlm30CitationSchema');
	}


	//
	// Implement template methods from TemplateBasedReferencesListFilter
	//
	/**
	 * @copydoc TemplateBasedReferencesListFilter::getCitationOutputFilterTypeDescriptions()
	 */
	function getCitationOutputFilterTypeDescriptions() {
		// FIXME: Add NLM citation-element + name validation (requires partial NLM DTD, XSD or RelaxNG), see #5648.
		return array(
				'metadata::lib.pkp.plugins.metadata.nlm30.schema.Nlm30CitationSchema(CITATION)',
				'xml::*');
	}


	//
	// Implement template methods from PersistableFilter
	//
	/**
	 * @copydoc PersistableFilter::getClassName()
	 */
	function getClassName() {
		return 'lib.pkp.plugins.metadata.nlm30.filter.PKPSubmissionNlm30XmlFilter';
	}


	//
	// Implement template methods from TemplateBasedFilter
	//
	/**
	 * @copydoc TemplateBasedFilter::getTemplateName()
	 */
	function getTemplateName() {
		return 'nlm30-ref-list.tpl';
	}

	/**
	 * @copydoc TemplateBasedFilter::getBasePath()
	 */
	function getBasePath() {
		return dirname(__FILE__);
	}
}
?>
