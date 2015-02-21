<?php

/**
 * @file classes/manager/form/ReviewFormForm.inc.php
 *
 * Copyright (c) 2013-2015 Simon Fraser University Library
 * Copyright (c) 2003-2015 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class ReviewFormForm
 * @ingroup manager_form
 * @see ReviewForm
 *
 * @brief Form for creating and modifying review forms.
 *
 */

import('lib.pkp.classes.form.Form');

class ReviewFormForm extends Form {

	/** @var $reviewFormId int The ID of the review form being edited */
	var $reviewFormId;

	/**
	 * Constructor.
	 * @param $reviewFormId int
	 */
	function ReviewFormForm($reviewFormId = null) {
		parent::Form('manager/reviewForms/reviewFormForm.tpl');

		$this->reviewFormId = $reviewFormId;

		// Validation checks for this form
		$this->addCheck(new FormValidatorLocale($this, 'title', 'required', 'manager.reviewForms.form.titleRequired'));
		$this->addCheck(new FormValidatorPost($this));

	}

	/**
	 * Get the names of fields for which localized data is allowed.
	 * @return array
	 */
	function getLocaleFieldNames() {
		$reviewFormDao =& DAORegistry::getDAO('ReviewFormDAO');
		return $reviewFormDao->getLocaleFieldNames();
	}

	/**
	 * Display the form.
	 */
	function display() {
		$templateMgr =& TemplateManager::getManager();
		$templateMgr->assign('reviewFormId', $this->reviewFormId);
		$templateMgr->assign('helpTopicId','journal.managementPages.reviewForms');
		parent::display();
	}

	/**
	 * Initialize form data from current review form.
	 */
	function initData() {
		if ($this->reviewFormId != null) {
			$journal =& Request::getJournal();
			$reviewFormDao =& DAORegistry::getDAO('ReviewFormDAO');
			$reviewForm =& $reviewFormDao->getReviewForm($this->reviewFormId, ASSOC_TYPE_JOURNAL, $journal->getId());

			if ($reviewForm == null) {
				$this->reviewFormId = null;
			} else {
				$this->_data = array(
					'title' => $reviewForm->getTitle(null), // Localized
					'description' => $reviewForm->getDescription(null) // Localized
				);
			}
		}
	}

	/**
	 * Assign form data to user-submitted data.
	 */
	function readInputData() {
		$this->readUserVars(array('title', 'description'));
	}

	/**
	 * Save review form.
	 */
	function execute() {
		$journal =& Request::getJournal();
		$journalId = $journal->getId();

		$reviewFormDao =& DAORegistry::getDAO('ReviewFormDAO');

		if ($this->reviewFormId != null) {
			$reviewForm =& $reviewFormDao->getReviewForm($this->reviewFormId, ASSOC_TYPE_JOURNAL, $journalId);
		}

		if (!isset($reviewForm)) {
			$reviewForm = $reviewFormDao->newDataObject();
			$reviewForm->setAssocType(ASSOC_TYPE_JOURNAL);
			$reviewForm->setAssocId($journalId);
			$reviewForm->setActive(0);
			$reviewForm->setSequence(REALLY_BIG_NUMBER);
		}

		$reviewForm->setTitle($this->getData('title'), null); // Localized
		$reviewForm->setDescription($this->getData('description'), null); // Localized

		if ($reviewForm->getId() != null) {
			$reviewFormDao->updateObject($reviewForm);
			$reviewFormId = $reviewForm->getId();
		} else {
			$reviewFormId = $reviewFormDao->insertObject($reviewForm);
			$reviewFormDao->resequenceReviewForms(ASSOC_TYPE_JOURNAL, $journalId);
		}
	}
}

?>
