<?php

/**
 * @file ReviewFormHandler.inc.php
 *
 * Copyright (c) 2003-2008 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package pages.manager
 * @class ReviewFormHandler
 *
 * Handle requests for review form management functions.
 *
*/

class ReviewFormHandler extends ManagerHandler {

	/**
	 * Display a list of review forms within the current journal.
	 */
	function reviewForms() {
		parent::validate();
		ReviewFormHandler::setupTemplate();

		$journal =& Request::getJournal();
		$rangeInfo =& Handler::getRangeInfo('reviewForms');
		$reviewFormDao =& DAORegistry::getDAO('ReviewFormDAO');
		$reviewForms =& $reviewFormDao->getJournalReviewForms($journal->getJournalId(), $rangeInfo);
		$reviewAssignmentDao =& DAORegistry::getDAO('ReviewAssignmentDAO');

		$templateMgr =& TemplateManager::getManager();
		$templateMgr->assign_by_ref('reviewForms', $reviewForms);
		$templateMgr->assign('helpTopicId','journal.managementPages.reviewForms');
		$templateMgr->display('manager/reviewForms/reviewForms.tpl');
	}

	/**
	 * Display form to create a new review form.
	 */
	function createReviewForm() {
		ReviewFormHandler::editReviewForm();
	}

	/**
	 * Display form to create/edit a review form.
	 * @param $args array optional, if set the first parameter is the ID of the review form to edit
	 */
	function editReviewForm($args = array()) {
		parent::validate();

		$reviewFormId = isset($args[0]) ? (int)$args[0] : null;

		$journal =& Request::getJournal();
		$reviewFormDao =& DAORegistry::getDAO('ReviewFormDAO');
		$reviewForm =& $reviewFormDao->getReviewForm($reviewFormId, $journal->getJournalId());

		if ($reviewFormId != null && (!isset($reviewForm) || $reviewForm->getCompleteCount() != 0 || $reviewForm->getIncompleteCount() != 0)) {
			Request::redirect(null, null, 'reviewForms');
		} else {
			ReviewFormHandler::setupTemplate(true, $reviewForm);
			$templateMgr =& TemplateManager::getManager();

			if ($reviewFormId == null) {
				$templateMgr->assign('pageTitle', 'manager.reviewForms.create');
			} else {
				$templateMgr->assign('pageTitle', 'manager.reviewForms.edit');
			}

			import('manager.form.ReviewFormForm');
			$reviewFormForm =& new ReviewFormForm($reviewFormId);

			if ($reviewFormForm->isLocaleResubmit()) {
				$reviewFormForm->readInputData();
			} else {
				$reviewFormForm->initData();
			}
			$reviewFormForm->display();
		}
	}

	/**
	 * Save changes to a review form.
	 */
	function updateReviewForm() {
		parent::validate();

		$reviewFormId = Request::getUserVar('reviewFormId') === null? null : (int) Request::getUserVar('reviewFormId');

		$journal =& Request::getJournal();
		$reviewFormDao =& DAORegistry::getDAO('ReviewFormDAO');
		$reviewForm =& $reviewFormDao->getReviewForm($reviewFormId, $journal->getJournalId());

		if ($reviewFormId != null && (!isset($reviewForm) || $reviewForm->getCompleteCount() != 0 || $reviewForm->getIncompleteCount() != 0)) {
			Request::redirect(null, null, 'reviewForms');
		}

		import('manager.form.ReviewFormForm');
		$reviewFormForm =& new ReviewFormForm($reviewFormId);
		$reviewFormForm->readInputData();

		if ($reviewFormForm->validate()) {
			$reviewFormForm->execute();
			Request::redirect(null, null, 'reviewForms');
		} else {
			ReviewFormHandler::setupTemplate(true, $reviewForm);
			$templateMgr =& TemplateManager::getManager();

			if ($reviewFormId == null) {
				$templateMgr->assign('pageTitle', 'manager.reviewForms.create');
			} else {
				$templateMgr->assign('pageTitle', 'manager.reviewForms.edit');
			}

			$reviewFormForm->display();
		}
	}

	/**
	 * Preview a review form.
	 * @param $args array first parameter is the ID of the review form to preview
	 */
	function previewReviewForm($args) {
		parent::validate();

		$reviewFormId = isset($args[0]) ? (int)$args[0] : null;

		$journal =& Request::getJournal();
		$reviewFormDao =& DAORegistry::getDAO('ReviewFormDAO');
		$reviewForm =& $reviewFormDao->getReviewForm($reviewFormId, $journal->getJournalId());
		$reviewFormElementDao =& DAORegistry::getDAO('ReviewFormElementDAO');
		$reviewFormElements =& $reviewFormElementDao->getReviewFormElements($reviewFormId);

		if (!isset($reviewForm)) {
			Request::redirect(null, null, 'reviewForms');
		}

		if ($reviewForm->getCompleteCount() != 0 || $reviewForm->getIncompleteCount() != 0) {
			ReviewFormHandler::setupTemplate(true);
		} else {
			ReviewFormHandler::setupTemplate(true, $reviewForm);
		}

		$templateMgr =& TemplateManager::getManager();

		$templateMgr->assign('pageTitle', 'manager.reviewForms.preview');
		$templateMgr->assign_by_ref('reviewForm', $reviewForm);
		$templateMgr->assign('reviewFormElements', $reviewFormElements);
		$templateMgr->register_function('form_language_chooser', array(&$this, 'smartyFormLanguageChooser'));
		$templateMgr->assign('helpTopicId','journal.managementPages.reviewForms');
		$templateMgr->display('manager/reviewForms/previewReviewForm.tpl');
	}

	/**
	 * Delete a review form.
	 * @param $args array first parameter is the ID of the review form to delete
	 */
	function deleteReviewForm($args) {
		parent::validate();

		$reviewFormId = isset($args[0]) ? (int)$args[0] : null;

		$journal =& Request::getJournal();
		$reviewFormDao =& DAORegistry::getDAO('ReviewFormDAO');
		$reviewForm =& $reviewFormDao->getReviewForm($reviewFormId, $journal->getJournalId());

		if (isset($reviewForm) && $reviewForm->getCompleteCount() == 0 && $reviewForm->getIncompleteCount() == 0) {
			$reviewAssignmentDao =& DAORegistry::getDAO('ReviewAssignmentDAO');
			$reviewAssignments =& $reviewAssignmentDao->getReviewAssignmentsByReviewFormId($reviewFormId);

			foreach ($reviewAssignments as $reviewAssignment) {
				$reviewAssignment->setReviewFormId('');
				$reviewAssignmentDao->updateReviewAssignment($reviewAssignment);
			}

			$reviewFormDao->deleteReviewFormById($reviewFormId, $journal->getJournalId());
		}

		Request::redirect(null, null, 'reviewForms');
	}

	/**
	 * Activate a published review form.
	 * @param $args array first parameter is the ID of the review form to activate
	 */
	function activateReviewForm($args) {
		parent::validate();

		$reviewFormId = isset($args[0]) ? (int)$args[0] : null;

		$journal =& Request::getJournal();
		$reviewFormDao =& DAORegistry::getDAO('ReviewFormDAO');
		$reviewForm =& $reviewFormDao->getReviewForm($reviewFormId, $journal->getJournalId());

		if (isset($reviewForm) && !$reviewForm->getActive()) {
			$reviewForm->setActive(1);
			$reviewFormDao->updateReviewForm($reviewForm);
		}

		Request::redirect(null, null, 'reviewForms');
	}

	/**
	 * Deactivate a published review form.
	 * @param $args array first parameter is the ID of the review form to deactivate
	 */
	function deactivateReviewForm($args) {
		parent::validate();

		$reviewFormId = isset($args[0]) ? (int)$args[0] : null;

		$journal =& Request::getJournal();
		$reviewFormDao =& DAORegistry::getDAO('ReviewFormDAO');
		$reviewForm =& $reviewFormDao->getReviewForm($reviewFormId, $journal->getJournalId());

		if (isset($reviewForm) && $reviewForm->getActive()) {
			$reviewForm->setActive(0);
			$reviewFormDao->updateReviewForm($reviewForm);
		}

		Request::redirect(null, null, 'reviewForms');
	}

	/**
	 * Copy a published review form.
	 */
	function copyReviewForm($args) {
		parent::validate();

		$reviewFormId = isset($args[0]) ? (int)$args[0] : null;

		$journal =& Request::getJournal();
		$reviewFormDao =& DAORegistry::getDAO('ReviewFormDAO');
		$reviewForm =& $reviewFormDao->getReviewForm($reviewFormId, $journal->getJournalId());

		if (isset($reviewForm)) {
			$reviewForm->setActive(0);
			$reviewForm->setSequence(REALLY_BIG_NUMBER);
			$newReviewFormId = $reviewFormDao->insertReviewForm($reviewForm);
			$reviewFormDao->resequenceReviewForms($journal->getJournalId());

			$reviewFormElementDao =& DAORegistry::getDAO('ReviewFormElementDAO');
			$reviewFormElements =& $reviewFormElementDao->getReviewFormElements($reviewFormId);
			foreach ($reviewFormElements as $reviewFormElement) {
				$reviewFormElement->setReviewFormId($newReviewFormId);
				$reviewFormElement->setSequence(REALLY_BIG_NUMBER);
				$reviewFormElementDao->insertReviewFormElement($reviewFormElement);
				$reviewFormElementDao->resequenceReviewFormElements($newReviewFormId);
			}

		}

		Request::redirect(null, null, 'reviewForms');
	}

	/**
	 * Change the sequence of a review form.
	 */
	function moveReviewForm() {
		parent::validate();

		$journal =& Request::getJournal();
		$reviewFormDao =& DAORegistry::getDAO('ReviewFormDAO');
		$reviewForm =& $reviewFormDao->getReviewForm(Request::getUserVar('reviewFormId'), $journal->getJournalId());

		if (isset($reviewForm)) {
			$reviewForm->setSequence($reviewForm->getSequence() + (Request::getUserVar('d') == 'u' ? -1.5 : 1.5));
			$reviewFormDao->updateReviewForm($reviewForm);
			$reviewFormDao->resequenceReviewForms($journal->getJournalId());
		}

		Request::redirect(null, null, 'reviewForms');
	}

	/**
	 * Display a list of the review form elements within a review form.
	 */
	function reviewFormElements($args) {
		parent::validate();

		$reviewFormId = isset($args[0]) ? $args[0] : null;

		$journal =& Request::getJournal();
		$reviewFormDao =& DAORegistry::getDAO('ReviewFormDAO');
		$reviewForm =& $reviewFormDao->getReviewForm($reviewFormId, $journal->getJournalId());

		if (!isset($reviewForm) || $reviewForm->getCompleteCount() != 0 || $reviewForm->getIncompleteCount() != 0) {
			Request::redirect(null, null, 'reviewForms');
		}

		$rangeInfo =& Handler::getRangeInfo('reviewFormElements');
		$reviewFormElementDao =& DAORegistry::getDAO('ReviewFormElementDAO');
		$reviewFormElements =& $reviewFormElementDao->getReviewFormElementsByReviewForm($reviewFormId, $rangeInfo);

		$unusedReviewFormTitles =& $reviewFormDao->getJournalReviewFormTitles($journal->getJournalId(), 0);

		ReviewFormHandler::setupTemplate(true, $reviewForm);
		$templateMgr =& TemplateManager::getManager();

		$templateMgr->assign_by_ref('unusedReviewFormTitles', $unusedReviewFormTitles);
		$templateMgr->assign_by_ref('reviewFormElements', $reviewFormElements);
		$templateMgr->assign('reviewFormId', $reviewFormId);
		import('reviewForm.ReviewFormElement');
		$templateMgr->assign_by_ref('reviewFormElementTypeOptions', ReviewFormElement::getReviewFormElementTypeOptions());
		$templateMgr->assign('helpTopicId','journal.managementPages.reviewForms');
		$templateMgr->display('manager/reviewForms/reviewFormElements.tpl');
	}

	/**
	 * Display form to create a new review form element.
	 */
	function createReviewFormElement($args) {
		ReviewFormHandler::editReviewFormElement($args);
	}

	/**
	 * Display form to create/edit a review form element.
	 * @param $args ($reviewFormId, $reviewFormElementId)
	 */
	function editReviewFormElement($args) {
		parent::validate();

		$reviewFormId = isset($args[0]) ? (int)$args[0] : null;
		$reviewFormElementId = isset($args[1]) ? (int) $args[1] : null;

		$journal =& Request::getJournal();
		$reviewFormDao =& DAORegistry::getDAO('ReviewFormDAO');
		$reviewForm =& $reviewFormDao->getReviewForm($reviewFormId, $journal->getJournalId());
		$reviewFormElementDao =& DAORegistry::getDAO('ReviewFormElementDAO');

		if (!isset($reviewForm) || $reviewForm->getCompleteCount() != 0 || $reviewForm->getIncompleteCount() != 0 || ($reviewFormElementId != null && !$reviewFormElementDao->reviewFormElementExists($reviewFormElementId, $reviewFormId))) {
			Request::redirect(null, null, 'reviewFormElements', array($reviewFormId));
		}

		ReviewFormHandler::setupTemplate(true, $reviewForm);
		$templateMgr =& TemplateManager::getManager();

		if ($reviewFormElementId == null) {
			$templateMgr->assign('pageTitle', 'manager.reviewFormElements.create');
		} else {
			$templateMgr->assign('pageTitle', 'manager.reviewFormElements.edit');
		}

		import('manager.form.ReviewFormElementForm');
		$reviewFormElementForm =& new ReviewFormElementForm($reviewFormId, $reviewFormElementId);
		if ($reviewFormElementForm->isLocaleResubmit()) {
			$reviewFormElementForm->readInputData();
		} else {
			$reviewFormElementForm->initData();
		}

		$reviewFormElementForm->display();
	}

	/**
	 * Save changes to a review form element.
	 */
	function updateReviewFormElement() {
		parent::validate();

		$reviewFormId = Request::getUserVar('reviewFormId') === null? null : (int) Request::getUserVar('reviewFormId');
		$reviewFormElementId = Request::getUserVar('reviewFormElementId') === null? null : (int) Request::getUserVar('reviewFormElementId');

		$journal =& Request::getJournal();
		$reviewFormDao =& DAORegistry::getDAO('ReviewFormDAO');
		$reviewFormElementDao =& DAORegistry::getDAO('ReviewFormElementDAO');

		if (!$reviewFormDao->unusedReviewFormExists($reviewFormId, $journal->getJournalId()) || ($reviewFormElementId != null && !$reviewFormElementDao->reviewFormElementExists($reviewFormElementId, $reviewFormId))) {
			Request::redirect(null, null, 'reviewFormElements', array($reviewFormId));
		}

		import('manager.form.ReviewFormElementForm');
		$reviewFormElementForm =& new ReviewFormElementForm($reviewFormId, $reviewFormElementId);
		$reviewFormElementForm->readInputData();
		$formLocale = $reviewFormElementForm->getFormLocale();

		// Reorder response items
		$response = $reviewFormElementForm->getData('possibleResponses');
		if (isset($response[$formLocale]) && is_array($response[$formLocale])) {
			usort($response[$formLocale], create_function('$a,$b','return $a[\'order\'] == $b[\'order\'] ? 0 : ($a[\'order\'] < $b[\'order\'] ? -1 : 1);'));
		}
		$reviewFormElementForm->setData('possibleResponses', $response);

		if (Request::getUserVar('addResponse')) {
			// Add a response item
			$editData = true;
			$response = $reviewFormElementForm->getData('possibleResponses');
			if (!isset($response[$formLocale]) || !is_array($response[$formLocale])) {
				$response[$formLocale] = array();
				$lastOrder = 0;
			} else {
				$lastOrder = $response[$formLocale][count($response[$formLocale])-1]['order'];
			}
			array_push($response[$formLocale], array('order' => $lastOrder+1));
			$reviewFormElementForm->setData('possibleResponses', $response);

		} else if (($delResponse = Request::getUserVar('delResponse')) && count($delResponse) == 1) {
			// Delete a response item
			$editData = true;
			list($delResponse) = array_keys($delResponse);
			$delResponse = (int) $delResponse;
			$response = $reviewFormElementForm->getData('possibleResponses');
			if (!isset($response[$formLocale])) $response[$formLocale] = array();
			array_splice($response[$formLocale], $delResponse, 1);
			$reviewFormElementForm->setData('possibleResponses', $response);
		}

		if (!isset($editData) && $reviewFormElementForm->validate()) {
			$reviewFormElementForm->execute();
			Request::redirect(null, null, 'reviewFormElements', array($reviewFormId));
		} else {
			$journal =& Request::getJournal();
			$reviewFormDao =& DAORegistry::getDAO('ReviewFormDAO');
			$reviewForm =& $reviewFormDao->getReviewForm($reviewFormId, $journal->getJournalId());

			ReviewFormHandler::setupTemplate(true, $reviewForm);
			$templateMgr =& TemplateManager::getManager();
			if ($reviewFormElementId == null) {
				$templateMgr->assign('pageTitle', 'manager.reviewFormElements.create');
			} else {
				$templateMgr->assign('pageTitle', 'manager.reviewFormElements.edit');
			}

			$reviewFormElementForm->display();
		}
	}

	/**
	 * Delete a review form element.
	 * @param $args array ($reviewFormId, $reviewFormElementId)
	 */
	function deleteReviewFormElement($args) {
		parent::validate();

		$reviewFormId = isset($args[0]) ? (int)$args[0] : null;
		$reviewFormElementId = isset($args[1]) ? (int) $args[1] : null;

		$journal =& Request::getJournal();
		$reviewFormDao =& DAORegistry::getDAO('ReviewFormDAO');

		if ($reviewFormDao->unusedReviewFormExists($reviewFormId, $journal->getJournalId())) {
			$reviewFormElementDao =& DAORegistry::getDAO('ReviewFormElementDAO');
			$reviewFormElementDao->deleteReviewFormElementById($reviewFormElementId);
		}
		Request::redirect(null, null, 'reviewFormElements', array($reviewFormId));
	}

	/**
	 * Change the sequence of a review form element.
	 */
	function moveReviewFormElement() {
		parent::validate();

		$journal =& Request::getJournal();
		$reviewFormDao =& DAORegistry::getDAO('ReviewFormDAO');
		$reviewFormElementDao =& DAORegistry::getDAO('ReviewFormElementDAO');
		$reviewFormElement =& $reviewFormElementDao->getReviewFormElement(Request::getUserVar('reviewFormElementId'));

		if (isset($reviewFormElement) && $reviewFormDao->unusedReviewFormExists($reviewFormElement->getReviewFormId(), $journal->getJournalId())) {
			$reviewFormElement->setSequence($reviewFormElement->getSequence() + (Request::getUserVar('d') == 'u' ? -1.5 : 1.5));
			$reviewFormElementDao->updateReviewFormElement($reviewFormElement);
			$reviewFormElementDao->resequenceReviewFormElements($reviewFormElement->getReviewFormId());
		}

		Request::redirect(null, null, 'reviewFormElements', array($reviewFormElement->getReviewFormId()));
	}

	/**
	 * Copy review form elemnts to another review form.
	 */
	function copyReviewFormElement() {
		parent::validate();

		$copy = Request::getUserVar('copy');
		$targetReviewFormId = Request::getUserVar('targetReviewForm');

		$journal =& Request::getJournal();
		$reviewFormDao =& DAORegistry::getDAO('ReviewFormDAO');

		if ($reviewFormDao->unusedReviewFormExists($targetReviewFormId, $journal->getJournalId())) {
			$reviewFormElementDao =& DAORegistry::getDAO('ReviewFormElementDAO');
			foreach ($copy as $reviewFormElementId) {
				$reviewFormElement =& $reviewFormElementDao->getReviewFormElement($reviewFormElementId);
				if (isset($reviewFormElement) && $reviewFormDao->unusedReviewFormExists($reviewFormElement->getReviewFormId(), $journal->getJournalId())) {
					$reviewFormElement->setReviewFormId($targetReviewFormId);
					$reviewFormElement->setSequence(REALLY_BIG_NUMBER);
					$reviewFormElementDao->insertReviewFormElement($reviewFormElement);
					$reviewFormElementDao->resequenceReviewFormElements($targetReviewFormId);
				}
				unset($reviewFormElement);
			}
		}

		Request::redirect(null, null, 'reviewFormElements', array($targetReviewFormId));
	}

	function setupTemplate($subclass = false, $reviewForm = null) {
		parent::setupTemplate(true);
		if ($subclass) {
			$templateMgr =& TemplateManager::getManager();
			$templateMgr->append('pageHierarchy', array(Request::url(null, 'manager', 'reviewForms'), 'manager.reviewForms'));
		}
		if ($reviewForm) {
			$templateMgr->append('pageHierarchy', array(Request::url(null, 'manager', 'editReviewForm', $reviewForm->getReviewFormId()), $reviewForm->getReviewFormTitle(), true));
		}
	}
}

?>
