<?php

/**
 * @file classes/citation/TemplateBasedReferencesListFilter.inc.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2000-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class TemplateBasedReferencesListFilter
 * @ingroup classes_citation
 *
 * @brief Abstract base class for filters that create a references
 *  list for a submission.
 */


import('lib.pkp.classes.filter.TemplateBasedFilter');

class TemplateBasedReferencesListFilter extends TemplateBasedFilter {
	/**
	 * Constructor
	 * @param $filterGroup FilterGroup
	 */
	function __construct($filterGroup) {
		// Add the persistable filter settings.
		import('lib.pkp.classes.filter.FilterSetting');
		$this->addSetting(new FilterSetting('citationOutputFilterName', null, null));
		$this->addSetting(new FilterSetting('metadataSchemaName', null, null));

		parent::__construct($filterGroup);
	}


	//
	// Getters and Setters
	//
	/**
	 * Get the metadata schema being used to extract
	 * data from the citations.
	 * @return MetadataSchema
	 */
	function &getMetadataSchema() {
		$metadataSchemaName = $this->getData('metadataSchemaName');
		assert(!is_null($metadataSchemaName));
		$metadataSchema =& instantiate($metadataSchemaName, 'MetadataSchema');
		return $metadataSchema;
	}

	/**
	 * Retrieve the citation output filter that will be
	 * used to transform citations.
	 * @return TemplateBasedFilter
	 */
	function getCitationOutputFilterInstance() {
		$citationOutputFilterName = $this->getData('citationOutputFilterName');
		assert(!is_null($citationOutputFilterName));
		list($inputTypeDescription, $outputTypeDescription) = $this->getCitationOutputFilterTypeDescriptions();
		$filterGroup = PersistableFilter::tempGroup($inputTypeDescription, $outputTypeDescription);
		return instantiate($citationOutputFilterName, 'TemplateBasedFilter', null, null, $filterGroup);
	}


	//
	// Abstract template methods to be implemented by sub-classes.
	//
	/**
	 * Return an input and output type description that
	 * describes the transformation implemented by the citation
	 * output filter.
	 * @return array
	 */
	function getCitationOutputFilterTypeDescriptions() {
		assert(false);
	}


	//
	// Implement template methods from TemplateBasedFilter
	//
	/**
	 * @see TemplateBasedFilter::addTemplateVars()
	 * @param $templateMgr TemplateManager
	 * @param $submission Submission
	 * @param $request Request
	 * @param $locale AppLocale
	 */
	function addTemplateVars($templateMgr, &$submission, $request, &$locale) {
		// Retrieve approved citations for this assoc object.
		$citationDao = DAORegistry::getDAO('CitationDAO');
		$citationResults = $citationDao->getObjectsByAssocId(ASSOC_TYPE_SUBMISSION, $submission->getId(), CITATION_APPROVED);
		$citations = $citationResults->toAssociativeArray('seq');

		// Create citation output for these citations.
		$metadataSchema = $this->getMetadataSchema();
		assert(is_a($metadataSchema, 'MetadataSchema'));
		$citationOutputFilter = $this->getCitationOutputFilterInstance();
		$citationsOutput = array();
		foreach($citations as $seq => $citation) {
			$citationMetadata = $citation->extractMetadata($metadataSchema);
			$citationsOutput[$seq] = $citationOutputFilter->execute($citationMetadata);
		}

		// Add citation mark-up and submission to template.
		$templateMgr->assign('citationsOutput', $citationsOutput);
		$templateMgr->assign('submission', $submission);
	}
}
?>
