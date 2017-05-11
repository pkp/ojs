<?php

/**
 * @file classes/citation/PlainTextReferencesListFilter.inc.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2000-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class PlainTextReferencesListFilter
 * @ingroup classes_citation
 *
 * @brief Class that converts a submission to a plain text references list
 *  based on the configured ordering type and citation output filter.
 */


import('lib.pkp.classes.citation.TemplateBasedReferencesListFilter');
import('lib.pkp.classes.citation.PlainTextReferencesList');

class PlainTextReferencesListFilter extends TemplateBasedReferencesListFilter {
	/**
	 * Constructor
	 * @param $filterGroup FilterGroup
	 */
	function __construct($filterGroup) {
		// Add the persistable filter settings.
		import('lib.pkp.classes.filter.SetFilterSetting');
		$this->addSetting(new SetFilterSetting('ordering', null, null,
				array(REFERENCES_LIST_ORDERING_ALPHABETICAL, REFERENCES_LIST_ORDERING_NUMERICAL)));

		parent::__construct($filterGroup);
	}


	//
	// Implement template methods from TemplateBasedReferencesListFilter
	//
	/**
	 * @copydoc TemplateBasedReferencesListFilter::getCitationOutputFilterTypeDescriptions()
	 */
	function getCitationOutputFilterTypeDescriptions() {
		return array(
				'metadata::lib.pkp.plugins.metadata.nlm30.schema.Nlm30CitationSchema(CITATION)',
				'primitive::string');
	}


	//
	// Implement template methods from PersistableFilter
	//
	/**
	 * @copydoc PersistableFilter::getClassName()
	 */
	function getClassName() {
		return 'lib.pkp.classes.citation.PlainTextReferencesListFilter';
	}


	//
	// Implement template methods from Filter
	//
	/**
	 * @copydoc Filter::process()
	 */
	function &process(&$input) {
		$output =& parent::process($input);
		$referencesList = new PlainTextReferencesList($output, $this->getData('ordering'));
		return $referencesList;
	}


	//
	// Implement template methods from TemplateBasedFilter
	//
	/**
	 * @copydoc TemplateBasedFilter::addTemplateVars()
	 */
	function addTemplateVars($templateMgr, $submission, $request, &$locale) {
		parent::addTemplateVars($templateMgr, $submission, $request, $locale);

		// Add the ordering type to the template.
		$templateMgr->assign('ordering', $this->getData('ordering'));
	}

	/**
	 * @copydoc TemplateBasedFilter::getTemplateName()
	 */
	function getTemplateName() {
		return 'references-list.tpl';
	}

	/**
	 * @copydoc TemplateBasedFilter::getBasePath()
	 */
	function getBasePath() {
		return dirname(__FILE__);
	}
}
?>
