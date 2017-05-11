<?php

/**
 * @file controllers/grid/settings/reviewForms/form/ReviewFormForm.inc.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2003-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class ReviewFormForm
 * @ingroup controllers_grid_settings_reviewForms_form
 *
 * @brief Form for manager to edit a review form.
 */

import('lib.pkp.classes.form.Form');

class ReviewFormForm extends Form {

	/** The ID of the review form being edited, if any */
	var $reviewFormId;

	/**
	 * Constructor.
	 * @param $reviewFormId omit for a new review form
	 */
	function __construct($reviewFormId = null) {
		parent::__construct('manager/reviewForms/reviewFormForm.tpl');
		$this->reviewFormId = $reviewFormId ? (int) $reviewFormId : null;

		// Validation checks for this form
		$this->addCheck(new FormValidatorLocale($this, 'title', 'required', 'manager.reviewForms.form.titleRequired'));
		$this->addCheck(new FormValidatorPost($this));
		$this->addCheck(new FormValidatorCSRF($this));
	}

	/**
	 * Assign form data to user-submitted data.
	 */
	function readInputData() {
		$this->readUserVars(array('title', 'description'));
	}

	/**
	 * Initialize form data from current settings.
	 * @param $request PKPRequest
	 */
	function initData($request) {
		if ($this->reviewFormId) {
			$context = $request->getContext();
			$reviewFormDao = DAORegistry::getDAO('ReviewFormDAO');
			$reviewForm = $reviewFormDao->getById($this->reviewFormId, Application::getContextAssocType(), $context->getId());

			$this->setData('title', $reviewForm->getTitle(null));
			$this->setData('description', $reviewForm->getDescription(null));
		}
	}

	/**
	 * Display the form.
	 */
	function fetch($args, $request) {
		$json = new JSONMessage();

		$templateMgr = TemplateManager::getManager($request);
		$templateMgr->assign('reviewFormId', $this->reviewFormId);

		return parent::fetch($request);
	}

	/**
	 * Save review form.
	 * @param $request PKPRequest
	 */
	function execute($request) {
		$context = $request->getContext();
		$reviewFormDao = DAORegistry::getDAO('ReviewFormDAO');

		if ($this->reviewFormId) {
			$reviewForm = $reviewFormDao->getById($this->reviewFormId, Application::getContextAssocType(), $context->getId());
		} else {
			$reviewForm = $reviewFormDao->newDataObject();
			$reviewForm->setAssocType(Application::getContextAssocType());
			$reviewForm->setAssocId($context->getId());
			$reviewForm->setActive(0);
			$reviewForm->setSequence(REALLY_BIG_NUMBER);
		}

		$reviewForm->setTitle($this->getData('title'), null); // Localized
		$reviewForm->setDescription($this->getData('description'), null); // Localized

		if ($this->reviewFormId) {
			$reviewFormDao->updateObject($reviewForm);
			$this->reviewFormId = $reviewForm->getId();
		} else {
			$this->reviewFormId = $reviewFormDao->insertObject($reviewForm);
			$reviewFormDao->resequenceReviewForms(Application::getContextAssocType(), $context->getId());
		}
	}

	/**
	 * Get a list of field names for which localized settings are used
	 * @return array
	 */
	function getLocaleFieldNames() {
		$reviewFormDao = DAORegistry::getDAO('ReviewFormDAO');
		return $reviewFormDao->getLocaleFieldNames();
	}
}

?>
