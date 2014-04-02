<?php

/**
 * @file pages/manager/ReviewFormHandler.inc.php
 *
 * Copyright (c) 2013-2014 Simon Fraser University Library
 * Copyright (c) 2003-2014 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class ReviewFormHandler
 * @ingroup pages_manager
 *
 * @brief Handle requests for review form management functions.
 *
*/

import('pages.manager.ManagerHandler');

class ReviewFormHandler extends ManagerHandler {
	/**
	 * Constructor
	 */
	function ReviewFormHandler() {
		parent::ManagerHandler();
	}

	/**
	 * Display a list of review forms within the current journal.
	 * @param $args array
	 * @param $request PKPRequest
	 */
	function reviewForms($args, &$request) {
		$this->validate();
		$this->setupTemplate();

		$journal =& $request->getJournal();
		$rangeInfo =& $this->getRangeInfo('reviewForms');
		$reviewFormDao =& DAORegistry::getDAO('ReviewFormDAO');
		$reviewForms =& $reviewFormDao->getByAssocId(ASSOC_TYPE_JOURNAL, $journal->getId(), $rangeInfo);
		$reviewAssignmentDao =& DAORegistry::getDAO('ReviewAssignmentDAO');

		$templateMgr =& TemplateManager::getManager();
		$templateMgr->addJavaScript('lib/pkp/js/lib/jquery/plugins/jquery.tablednd.js');
		$templateMgr->addJavaScript('lib/pkp/js/functions/tablednd.js');
		$templateMgr->assign_by_ref('reviewForms', $reviewForms);
		$templateMgr->assign('completeCounts', $reviewFormDao->getUseCounts(ASSOC_TYPE_JOURNAL, $journal->getId(), true));
		$templateMgr->assign('incompleteCounts', $reviewFormDao->getUseCounts(ASSOC_TYPE_JOURNAL, $journal->getId(), false));
		$templateMgr->assign('helpTopicId','journal.managementPages.reviewForms');
		$templateMgr->display('manager/reviewForms/reviewForms.tpl');
	}

	/**
	 * Display form to create a new review form.
	 */
	function createReviewForm() {
		$this->editReviewForm();
	}

	/**
	 * Display form to create/edit a review form.
	 * @param $args array optional, if set the first parameter is the ID of the review form to edit
	 */
	function editReviewForm($args = array()) {
		$this->validate();

		$reviewFormId = isset($args[0]) ? (int)$args[0] : null;

		$journal =& Request::getJournal();
		$reviewFormDao =& DAORegistry::getDAO('ReviewFormDAO');
		$reviewForm =& $reviewFormDao->getReviewForm($reviewFormId, ASSOC_TYPE_JOURNAL, $journal->getId());
		$completeCounts = $reviewFormDao->getUseCounts(ASSOC_TYPE_JOURNAL, $journal->getId(), true);
		$incompleteCounts = $reviewFormDao->getUseCounts(ASSOC_TYPE_JOURNAL, $journal->getId(), false);

		if ($reviewFormId != null && (!isset($reviewForm) || $completeCounts[$reviewFormId] != 0 || $incompleteCounts[$reviewFormId] != 0)) {
			Request::redirect(null, null, 'reviewForms');
		} else {
			$this->setupTemplate(true, $reviewForm);
			$templateMgr =& TemplateManager::getManager();

			if ($reviewFormId == null) {
				$templateMgr->assign('pageTitle', 'manager.reviewForms.create');
			} else {
				$templateMgr->assign('pageTitle', 'manager.reviewForms.edit');
			}

			import('classes.manager.form.ReviewFormForm');
			$reviewFormForm = new ReviewFormForm($reviewFormId);

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
		$this->validate();

		$reviewFormId = Request::getUserVar('reviewFormId') === null? null : (int) Request::getUserVar('reviewFormId');

		$journal =& Request::getJournal();
		$reviewFormDao =& DAORegistry::getDAO('ReviewFormDAO');
		$reviewForm =& $reviewFormDao->getReviewForm($reviewFormId, ASSOC_TYPE_JOURNAL, $journal->getId());
		$completeCounts = $reviewFormDao->getUseCounts(ASSOC_TYPE_JOURNAL, $journal->getId(), true);
		$incompleteCounts = $reviewFormDao->getUseCounts(ASSOC_TYPE_JOURNAL, $journal->getId(), false);
		if ($reviewFormId != null && (!isset($reviewForm) || $completeCounts[$reviewFormId] != 0 || $incompleteCounts[$reviewFormId] != 0)) {
			Request::redirect(null, null, 'reviewForms');
		}
		$this->setupTemplate(true, $reviewForm);

		import('classes.manager.form.ReviewFormForm');
		$reviewFormForm = new ReviewFormForm($reviewFormId);
		$reviewFormForm->readInputData();

		if ($reviewFormForm->validate()) {
			$reviewFormForm->execute();
			Request::redirect(null, null, 'reviewForms');
		} else {
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
		$this->validate();

		$reviewFormId = isset($args[0]) ? (int)$args[0] : null;

		$journal =& Request::getJournal();
		$reviewFormDao =& DAORegistry::getDAO('ReviewFormDAO');
		$reviewForm =& $reviewFormDao->getReviewForm($reviewFormId, ASSOC_TYPE_JOURNAL, $journal->getId());
		$reviewFormElementDao =& DAORegistry::getDAO('ReviewFormElementDAO');
		$reviewFormElements =& $reviewFormElementDao->getReviewFormElements($reviewFormId);

		if (!isset($reviewForm)) {
			Request::redirect(null, null, 'reviewForms');
		}

		$completeCounts = $reviewFormDao->getUseCounts(ASSOC_TYPE_JOURNAL, $journal->getId(), true);
		$incompleteCounts = $reviewFormDao->getUseCounts(ASSOC_TYPE_JOURNAL, $journal->getId(), false);
		if ($completeCounts[$reviewFormId] != 0 || $incompleteCounts[$reviewFormId] != 0) {
			$this->setupTemplate(true);
		} else {
			$this->setupTemplate(true, $reviewForm);
		}

		$templateMgr =& TemplateManager::getManager();

		$templateMgr->assign('pageTitle', 'manager.reviewForms.preview');
		$templateMgr->assign_by_ref('reviewForm', $reviewForm);
		$templateMgr->assign('reviewFormElements', $reviewFormElements);
		$templateMgr->assign('completeCounts', $completeCounts);
		$templateMgr->assign('incompleteCounts', $incompleteCounts);
		$templateMgr->register_function('form_language_chooser', array('ReviewFormHandler', 'smartyFormLanguageChooser'));
		$templateMgr->assign('helpTopicId','journal.managementPages.reviewForms');
		$templateMgr->display('manager/reviewForms/previewReviewForm.tpl');
	}

	/**
	 * Delete a review form.
	 * @param $args array first parameter is the ID of the review form to delete
	 */
	function deleteReviewForm($args) {
		$this->validate();

		$reviewFormId = isset($args[0]) ? (int)$args[0] : null;

		$journal =& Request::getJournal();
		$reviewFormDao =& DAORegistry::getDAO('ReviewFormDAO');
		$reviewForm =& $reviewFormDao->getReviewForm($reviewFormId, ASSOC_TYPE_JOURNAL, $journal->getId());

		$completeCounts = $reviewFormDao->getUseCounts(ASSOC_TYPE_JOURNAL, $journal->getId(), true);
		$incompleteCounts = $reviewFormDao->getUseCounts(ASSOC_TYPE_JOURNAL, $journal->getId(), false);
		if (isset($reviewForm) && $completeCounts[$reviewFormId] == 0 && $incompleteCounts[$reviewFormId] == 0) {
			$reviewAssignmentDao =& DAORegistry::getDAO('ReviewAssignmentDAO');
			$reviewAssignments =& $reviewAssignmentDao->getByReviewFormId($reviewFormId);

			foreach ($reviewAssignments as $reviewAssignment) {
				$reviewAssignment->setReviewFormId('');
				$reviewAssignmentDao->updateReviewAssignment($reviewAssignment);
			}

			$reviewFormDao->deleteById($reviewFormId, $journal->getId());
		}

		Request::redirect(null, null, 'reviewForms');
	}

	/**
	 * Activate a published review form.
	 * @param $args array first parameter is the ID of the review form to activate
	 */
	function activateReviewForm($args) {
		$this->validate();

		$reviewFormId = isset($args[0]) ? (int)$args[0] : null;

		$journal =& Request::getJournal();
		$reviewFormDao =& DAORegistry::getDAO('ReviewFormDAO');
		$reviewForm =& $reviewFormDao->getReviewForm($reviewFormId, ASSOC_TYPE_JOURNAL, $journal->getId());

		if (isset($reviewForm) && !$reviewForm->getActive()) {
			$reviewForm->setActive(1);
			$reviewFormDao->updateObject($reviewForm);
		}

		Request::redirect(null, null, 'reviewForms');
	}

	/**
	 * Deactivate a published review form.
	 * @param $args array first parameter is the ID of the review form to deactivate
	 */
	function deactivateReviewForm($args) {
		$this->validate();

		$reviewFormId = isset($args[0]) ? (int)$args[0] : null;

		$journal =& Request::getJournal();
		$reviewFormDao =& DAORegistry::getDAO('ReviewFormDAO');
		$reviewForm =& $reviewFormDao->getReviewForm($reviewFormId, ASSOC_TYPE_JOURNAL, $journal->getId());

		if (isset($reviewForm) && $reviewForm->getActive()) {
			$reviewForm->setActive(0);
			$reviewFormDao->updateObject($reviewForm);
		}

		Request::redirect(null, null, 'reviewForms');
	}

	/**
	 * Copy a published review form.
	 */
	function copyReviewForm($args) {
		$this->validate();

		$reviewFormId = isset($args[0]) ? (int)$args[0] : null;

		$journal =& Request::getJournal();
		$reviewFormDao =& DAORegistry::getDAO('ReviewFormDAO');
		$reviewForm =& $reviewFormDao->getReviewForm($reviewFormId, ASSOC_TYPE_JOURNAL, $journal->getId());

		if (isset($reviewForm)) {
			$reviewForm->setActive(0);
			$reviewForm->setSequence(REALLY_BIG_NUMBER);
			$newReviewFormId = $reviewFormDao->insertObject($reviewForm);
			$reviewFormDao->resequenceReviewForms(ASSOC_TYPE_JOURNAL, $journal->getId());

			$reviewFormElementDao =& DAORegistry::getDAO('ReviewFormElementDAO');
			$reviewFormElements =& $reviewFormElementDao->getReviewFormElements($reviewFormId);
			foreach ($reviewFormElements as $reviewFormElement) {
				$reviewFormElement->setReviewFormId($newReviewFormId);
				$reviewFormElement->setSequence(REALLY_BIG_NUMBER);
				$reviewFormElementDao->insertObject($reviewFormElement);
				$reviewFormElementDao->resequenceReviewFormElements($newReviewFormId);
			}

		}

		Request::redirect(null, null, 'reviewForms');
	}

	/**
	 * Change the sequence of a review form.
	 */
	function moveReviewForm() {
		$this->validate();

		$journal =& Request::getJournal();
		$reviewFormDao =& DAORegistry::getDAO('ReviewFormDAO');
		$reviewForm =& $reviewFormDao->getReviewForm(Request::getUserVar('id'), ASSOC_TYPE_JOURNAL, $journal->getId());

		if (isset($reviewForm)) {
			$direction = Request::getUserVar('d');

			if ($direction != null) {
				// moving with up or down arrow
				$reviewForm->setSequence($reviewForm->getSequence() + ($direction == 'u' ? -1.5 : 1.5));

			} else {
				// Dragging and dropping
				$prevId = Request::getUserVar('prevId');
				if ($prevId == null)
					$prevSeq = 0;
				else {
					$prevJournal = $reviewFormDao->getReviewForm($prevId);
					$prevSeq = $prevJournal->getSequence();
				}

				$reviewForm->setSequence($prevSeq + .5);
			}

			$reviewFormDao->updateObject($reviewForm);
			$reviewFormDao->resequenceReviewForms(ASSOC_TYPE_JOURNAL, $journal->getId());
		}

		// Moving up or down with the arrows requires a page reload.
		if ($direction != null) {
			Request::redirect(null, null, 'reviewForms');
		}
	}

	/**
	 * Display a list of the review form elements within a review form.
	 */
	function reviewFormElements($args) {
		$this->validate();

		$reviewFormId = isset($args[0]) ? $args[0] : null;

		$journal =& Request::getJournal();
		$reviewFormDao =& DAORegistry::getDAO('ReviewFormDAO');
		$reviewForm =& $reviewFormDao->getReviewForm($reviewFormId, ASSOC_TYPE_JOURNAL, $journal->getId());
		$completeCounts = $reviewFormDao->getUseCounts(ASSOC_TYPE_JOURNAL, $journal->getId(), true);
		$incompleteCounts = $reviewFormDao->getUseCounts(ASSOC_TYPE_JOURNAL, $journal->getId(), false);

		if (!isset($reviewForm) || $completeCounts[$reviewFormId] != 0 || $incompleteCounts[$reviewFormId] != 0) {
			Request::redirect(null, null, 'reviewForms');
		}

		$rangeInfo =& $this->getRangeInfo('reviewFormElements');
		$reviewFormElementDao =& DAORegistry::getDAO('ReviewFormElementDAO');
		$reviewFormElements =& $reviewFormElementDao->getReviewFormElementsByReviewForm($reviewFormId, $rangeInfo);

		$unusedReviewFormTitles =& $reviewFormDao->getTitlesByAssocId(ASSOC_TYPE_JOURNAL, $journal->getId(), 0);

		$this->setupTemplate(true, $reviewForm);
		$templateMgr =& TemplateManager::getManager();

		$templateMgr->addJavaScript('lib/pkp/js/lib/jquery/plugins/jquery.tablednd.js');
		$templateMgr->addJavaScript('lib/pkp/js/functions/tablednd.js');

		$templateMgr->assign_by_ref('unusedReviewFormTitles', $unusedReviewFormTitles);
		$templateMgr->assign_by_ref('reviewFormElements', $reviewFormElements);
		$templateMgr->assign('reviewFormId', $reviewFormId);
		import('lib.pkp.classes.reviewForm.ReviewFormElement');
		$templateMgr->assign_by_ref('reviewFormElementTypeOptions', ReviewFormElement::getReviewFormElementTypeOptions());
		$templateMgr->assign('helpTopicId','journal.managementPages.reviewForms');
		$templateMgr->display('manager/reviewForms/reviewFormElements.tpl');
	}

	/**
	 * Display form to create a new review form element.
	 */
	function createReviewFormElement($args) {
		$this->editReviewFormElement($args);
	}

	/**
	 * Display form to create/edit a review form element.
	 * @param $args ($reviewFormId, $reviewFormElementId)
	 */
	function editReviewFormElement($args) {
		$this->validate();

		$reviewFormId = isset($args[0]) ? (int)$args[0] : null;
		$reviewFormElementId = isset($args[1]) ? (int) $args[1] : null;

		$journal =& Request::getJournal();
		$reviewFormDao =& DAORegistry::getDAO('ReviewFormDAO');
		$reviewForm =& $reviewFormDao->getReviewForm($reviewFormId, ASSOC_TYPE_JOURNAL, $journal->getId());
		$completeCounts = $reviewFormDao->getUseCounts(ASSOC_TYPE_JOURNAL, $journal->getId(), true);
		$incompleteCounts = $reviewFormDao->getUseCounts(ASSOC_TYPE_JOURNAL, $journal->getId(), false);
		$reviewFormElementDao =& DAORegistry::getDAO('ReviewFormElementDAO');

		if (!isset($reviewForm) || $completeCounts[$reviewFormId] != 0 || $incompleteCounts[$reviewFormId] != 0 || ($reviewFormElementId != null && !$reviewFormElementDao->reviewFormElementExists($reviewFormElementId, $reviewFormId))) {
			Request::redirect(null, null, 'reviewFormElements', array($reviewFormId));
		}

		$this->setupTemplate(true, $reviewForm);
		$templateMgr =& TemplateManager::getManager();

		if ($reviewFormElementId == null) {
			$templateMgr->assign('pageTitle', 'manager.reviewFormElements.create');
		} else {
			$templateMgr->assign('pageTitle', 'manager.reviewFormElements.edit');
		}

		import('classes.manager.form.ReviewFormElementForm');
		$reviewFormElementForm = new ReviewFormElementForm($reviewFormId, $reviewFormElementId);
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
		$this->validate();

		$reviewFormId = Request::getUserVar('reviewFormId') === null? null : (int) Request::getUserVar('reviewFormId');
		$reviewFormElementId = Request::getUserVar('reviewFormElementId') === null? null : (int) Request::getUserVar('reviewFormElementId');

		$journal =& Request::getJournal();
		$reviewFormDao =& DAORegistry::getDAO('ReviewFormDAO');
		$reviewFormElementDao =& DAORegistry::getDAO('ReviewFormElementDAO');

		$reviewForm =& $reviewFormDao->getReviewForm($reviewFormId, ASSOC_TYPE_JOURNAL, $journal->getId());
		$this->setupTemplate(true, $reviewForm);

		if (!$reviewFormDao->unusedReviewFormExists($reviewFormId, ASSOC_TYPE_JOURNAL, $journal->getId()) || ($reviewFormElementId != null && !$reviewFormElementDao->reviewFormElementExists($reviewFormElementId, $reviewFormId))) {
			Request::redirect(null, null, 'reviewFormElements', array($reviewFormId));
		}

		import('classes.manager.form.ReviewFormElementForm');
		$reviewFormElementForm = new ReviewFormElementForm($reviewFormId, $reviewFormElementId);
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
		$this->validate();

		$reviewFormId = isset($args[0]) ? (int)$args[0] : null;
		$reviewFormElementId = isset($args[1]) ? (int) $args[1] : null;

		$journal =& Request::getJournal();
		$reviewFormDao =& DAORegistry::getDAO('ReviewFormDAO');

		if ($reviewFormDao->unusedReviewFormExists($reviewFormId, ASSOC_TYPE_JOURNAL, $journal->getId())) {
			$reviewFormElementDao =& DAORegistry::getDAO('ReviewFormElementDAO');
			$reviewFormElementDao->deleteById($reviewFormElementId);
		}
		Request::redirect(null, null, 'reviewFormElements', array($reviewFormId));
	}

	/**
	 * Change the sequence of a review form element.
	 */
	function moveReviewFormElement($args, $request) {
		$this->validate();

		$journal =& $request->getJournal();
		$reviewFormDao =& DAORegistry::getDAO('ReviewFormDAO');
		$reviewFormElementDao =& DAORegistry::getDAO('ReviewFormElementDAO');
		$reviewFormElement =& $reviewFormElementDao->getReviewFormElement($request->getUserVar('id'));

		if (!isset($reviewFormElement) || !$reviewFormDao->unusedReviewFormExists($reviewFormElement->getReviewFormId(), ASSOC_TYPE_JOURNAL, $journal->getId())) {
			$request->redirect(null, null, 'reviewForms');
		}

		$direction = $request->getUserVar('d');

		if ($direction != null) {
			// moving with up or down arrow
			$reviewFormElement->setSequence($reviewFormElement->getSequence() + ($direction == 'u' ? -1.5 : 1.5));

		} else {
			// drag and drop
			$prevId = $request->getUserVar('prevId');
			if ($prevId == null)
				$prevSeq = 0;
			else {
				$prevReviewFormElement = $reviewFormElementDao->getReviewFormElement($prevId);
				$prevSeq = $prevReviewFormElement->getSequence();
			}

			$reviewFormElement->setSequence($prevSeq + .5);
		}

		$reviewFormElementDao->updateObject($reviewFormElement);
		$reviewFormElementDao->resequenceReviewFormElements($reviewFormElement->getReviewFormId());

		// Moving up or down with the arrows requires a page reload.
		// In the case of a drag and drop move, the display has been
		// updated on the client side, so no reload is necessary.
		if ($direction != null) {
			$request->redirect(null, null, 'reviewFormElements', array($reviewFormElement->getReviewFormId()));
		}
	}

	/**
	 * Copy review form elemnts to another review form.
	 */
	function copyReviewFormElement() {
		$this->validate();

		$copy = Request::getUserVar('copy');
		$targetReviewFormId = Request::getUserVar('targetReviewForm');

		$journal =& Request::getJournal();
		$reviewFormDao =& DAORegistry::getDAO('ReviewFormDAO');

		if (is_array($copy) && $reviewFormDao->unusedReviewFormExists($targetReviewFormId, ASSOC_TYPE_JOURNAL, $journal->getId())) {
			$reviewFormElementDao =& DAORegistry::getDAO('ReviewFormElementDAO');
			foreach ($copy as $reviewFormElementId) {
				$reviewFormElement =& $reviewFormElementDao->getReviewFormElement($reviewFormElementId);
				if (isset($reviewFormElement) && $reviewFormDao->unusedReviewFormExists($reviewFormElement->getReviewFormId(), ASSOC_TYPE_JOURNAL, $journal->getId())) {
					$reviewFormElement->setReviewFormId($targetReviewFormId);
					$reviewFormElement->setSequence(REALLY_BIG_NUMBER);
					$reviewFormElementDao->insertObject($reviewFormElement);
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
			$templateMgr->append('pageHierarchy', array(Request::url(null, 'manager', 'editReviewForm', $reviewForm->getId()), $reviewForm->getLocalizedTitle(), true));
		}
	}
}

?>
