<?php

/**
 * @file classes/filter/TemplateBasedFilter.inc.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2000-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class TemplateBasedFilter
 * @ingroup classes_filter
 *
 * @brief Abstract base class for all filters that transform
 *  their input via smarty templates.
 */

import('lib.pkp.classes.filter.PersistableFilter');

class TemplateBasedFilter extends PersistableFilter {
	/**
	 * Constructor
	 * @param $filterGroup FilterGroup
	 */
	function __construct($filterGroup) {
		parent::__construct($filterGroup);
	}


	//
	// Abstract template methods
	//
	/**
	 * Return the base path of the filter so that we
	 * can find the filter templates.
	 *
	 * @return string
	 */
	function getBasePath() {
		// Must be implemented by sub-classes.
		assert(false);
	}

	/**
	 * Return the template name to be used by this filter.
	 * @return string
	 */
	function getTemplateName() {
		// Must be implemented by sub-classes.
		assert(false);
	}

	/**
	 * Sub-classes must implement this method to add
	 * template variables to the template.
	 * @param $templateMgr TemplateManager
	 * @param $input mixed the filter input
	 * @param $request Request
	 * @param $locale AppLocale
	 */
	function addTemplateVars($templateMgr, &$input, $request, &$locale) {
		// Must be implemented by sub-classes.
		assert(false);
	}


	//
	// Implement template methods from Filter
	//
	/**
	 * @see Filter::process()
	 */
	function &process(&$input) {
		// Initialize view
		$locale = AppLocale::getLocale();
		$application = PKPApplication::getApplication();
		$request = $application->getRequest();
		$templateMgr = TemplateManager::getManager($request);

		// Add the filter's directory as additional template dir so that
		// templates can include sub-templates in the same folder.
		array_unshift($templateMgr->template_dir, $this->getBasePath());

		// Give sub-filters a chance to add their variables
		// to the template.
		$this->addTemplateVars($templateMgr, $input, $request, $locale);

		// Use a base path hash as compile id to make sure that we don't
		// get namespace problems if several filters use the same
		// template names.
		$previousCompileId = $templateMgr->compile_id;
		$templateMgr->compile_id = md5($this->getBasePath());

		// Let the template engine render the citation.
		$output = $templateMgr->fetch($this->getTemplateName());

		// Remove the additional template dir
		array_shift($templateMgr->template_dir);

		// Restore the compile id.
		$templateMgr->compile_id = $previousCompileId;

		return $output;
	}
}
?>
