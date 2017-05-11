<?php

/**
 * @file controllers/grid/settings/reviewForms/ReviewFormGridHandler.inc.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2000-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class ReviewFormGridHandler
 * @ingroup controllers_grid_settings_reviewForms
 *
 * @brief Handle review form grid requests.
 */

import('lib.pkp.classes.controllers.grid.GridHandler');

import('lib.pkp.controllers.grid.settings.reviewForms.ReviewFormGridRow');

import('lib.pkp.controllers.grid.settings.reviewForms.form.ReviewFormForm');
import('lib.pkp.controllers.grid.settings.reviewForms.form.ReviewFormElements');

import('lib.pkp.controllers.grid.settings.reviewForms.form.PreviewReviewForm');

class ReviewFormGridHandler extends GridHandler {
	/**
	 * Constructor
	 */
	function __construct() {
		parent::__construct();
		$this->addRoleAssignment(
			array(ROLE_ID_MANAGER),
			array('fetchGrid', 'fetchRow', 'createReviewForm', 'editReviewForm', 'updateReviewForm',
				'reviewFormBasics', 'reviewFormElements', 'copyReviewForm',
				'reviewFormPreview', 'activateReviewForm', 'deactivateReviewForm', 'deleteReviewForm',
				'saveSequence')
		);
	}


	//
	// Implement template methods from PKPHandler.
	//
	/**
	 * @copydoc GridHandler::initialize()
	 */
	function initialize($request, $args = null) {
		parent::initialize($request, $args);

		// Load user-related translations.
		AppLocale::requireComponents(
			LOCALE_COMPONENT_APP_ADMIN,
			LOCALE_COMPONENT_APP_MANAGER,
			LOCALE_COMPONENT_PKP_USER,
			LOCALE_COMPONENT_PKP_MANAGER
		);

		// Basic grid configuration.
		$this->setTitle('manager.reviewForms');

		// Grid actions.
		$router = $request->getRouter();

		import('lib.pkp.classes.linkAction.request.AjaxModal');
		$this->addAction(
			new LinkAction(
				'createReviewForm',
				new AjaxModal(
					$router->url($request, null, null, 'createReviewForm', null, null),
					__('manager.reviewForms.create'),
					'modal_add_item',
					true
					),
				__('manager.reviewForms.create'),
				'add_item')
		);

		//
		// Grid columns.
		//
		import('lib.pkp.controllers.grid.settings.reviewForms.ReviewFormGridCellProvider');
		$reviewFormGridCellProvider = new ReviewFormGridCellProvider();

		// Review form name.
		$this->addColumn(
			new GridColumn(
				'name',
				'manager.reviewForms.title',
				null,
				null,
				$reviewFormGridCellProvider
			)
		);

		// Review Form 'in review'
		$this->addColumn(
			new GridColumn(
				'inReview',
				'manager.reviewForms.inReview',
				null,
				null,
				$reviewFormGridCellProvider
			)
		);

		// Review Form 'completed'.
		$this->addColumn(
			new GridColumn(
				'completed',
				'manager.reviewForms.completed',
				null,
				null,
				$reviewFormGridCellProvider
			)
		);

		// Review form 'activate/deactivate'
		// if ($element->getActive()) {
		$this->addColumn(
			new GridColumn(
				'active',
				'common.active',
				null,
				'controllers/grid/common/cell/selectStatusCell.tpl',
				$reviewFormGridCellProvider
			)
		);
	}

	/**
	 * @see PKPHandler::authorize()
	 */
	function authorize($request, &$args, $roleAssignments) {
		import('lib.pkp.classes.security.authorization.PolicySet');
		$rolePolicy = new PolicySet(COMBINING_PERMIT_OVERRIDES);

		import('lib.pkp.classes.security.authorization.RoleBasedHandlerOperationPolicy');
		foreach($roleAssignments as $role => $operations) {
			$rolePolicy->addPolicy(new RoleBasedHandlerOperationPolicy($request, $role, $operations));
		}
		$this->addPolicy($rolePolicy);

		return parent::authorize($request, $args, $roleAssignments);
	}

	//
	// Implement methods from GridHandler.
	//
	/**
	 * @see GridHandler::getRowInstance()
	 * @return UserGridRow
	 */
	protected function getRowInstance() {
		return new ReviewFormGridRow();
	}

	/**
	 * @see GridHandler::loadData()
	 * @param $request PKPRequest
	 * @return array Grid data.
	 */
	protected function loadData($request) {
		// Get all review forms.
		$reviewFormDao = DAORegistry::getDAO('ReviewFormDAO');
		$context = $request->getContext();
		$reviewForms = $reviewFormDao->getByAssocId(Application::getContextAssocType(), $context->getId());

		return $reviewForms->toAssociativeArray();
	}

	/**
	 * @copydoc GridHandler::setDataElementSequence()
	 */
	function setDataElementSequence($request, $rowId, $gridDataElement, $newSequence) {
		$reviewFormDao = DAORegistry::getDAO('ReviewFormDAO'); /* @var $reviewFormDao ReviewFormDAO */
		$gridDataElement->setSequence($newSequence);
		$reviewFormDao->updateObject($gridDataElement);
	}

	/**
	 * @see lib/pkp/classes/controllers/grid/GridHandler::getDataElementSequence()
	 */
	function getDataElementSequence($reviewForm) {
		return $reviewForm->getSequence();
	}

	/**
	 * @see GridHandler::addFeatures()
	 */
	function initFeatures($request, $args) {
		import('lib.pkp.classes.controllers.grid.feature.OrderGridItemsFeature');
		return array(new OrderGridItemsFeature());
	}


	//
	// Public grid actions.
	//
	/**
	 * Preview a review form.
	 * @param $args array
	 * @param $request PKPRequest
	 * @return JSONMessage JSON object
	 */
	function reviewFormPreview($args, $request) {
		// Identify the review form ID.
		$reviewFormId = (int) $request->getUserVar('reviewFormId');

		// Identify the context id.
		$context = $request->getContext();

		// Get review form object
		$reviewFormDao = DAORegistry::getDAO('ReviewFormDAO');
		$reviewForm = $reviewFormDao->getById($reviewFormId, Application::getContextAssocType(), $context->getId());

		$previewReviewForm = new PreviewReviewForm($reviewFormId);
		$previewReviewForm->initData($request);
		return new JSONMessage(true, $previewReviewForm->fetch($args, $request));
	}

	/**
	 * Add a new review form.
	 * @param $args array
	 * @param $request PKPRequest
	 * @return JSONMessage JSON object
	 */
	function createReviewForm($args, $request) {
		// Form handling.
		$reviewFormForm = new ReviewFormForm(null);
		$reviewFormForm->initData($request);
		return new JSONMessage(true, $reviewFormForm->fetch($args, $request));
	}

	/**
	 * Edit an existing review form.
	 * @param $args array
	 * @param $request PKPRequest
	 * @return JSONMessage JSON object
	 */
	function editReviewForm($args, $request) {
		$reviewFormDao = DAORegistry::getDAO('ReviewFormDAO');
		$context = $request->getContext();
		$reviewForm = $reviewFormDao->getById(
			$request->getUserVar('rowId'),
			Application::getContextAssocType(), $context->getId()
		);

		// Display 'editReviewForm' tabs
		$templateMgr = TemplateManager::getManager($request);
		$templateMgr->assign(array(
			'preview' => $request->getUserVar('preview'),
			'reviewFormId' => $reviewForm->getId(),
			'canEdit' => $reviewForm->getIncompleteCount() == 0 && $reviewForm->getCompleteCount() == 0,
		));
		return new JSONMessage(true, $templateMgr->fetch('controllers/grid/settings/reviewForms/editReviewForm.tpl'));
	}

	/**
	 * Edit an existing review form's basics (title, description)
	 * @param $args array
	 * @param $request PKPRequest
	 * @return JSONMessage JSON object
	 */
	function reviewFormBasics($args, $request) {
		// Identify the review form Id
		$reviewFormId = (int) $request->getUserVar('reviewFormId');

		// Form handling
		$reviewFormForm = new ReviewFormForm($reviewFormId);
		$reviewFormForm->initData($request);
		return new JSONMessage(true, $reviewFormForm->fetch($args, $request));
	}


	/**
	 * Display a list of the review form elements within a review form.
	 * @param $args array
	 * @param $request PKPRequest
	 * @return JSONMessage JSON object
	 */
	function reviewFormElements($args, $request) {
		$templateMgr = TemplateManager::getManager($request);
		$dispatcher = $request->getDispatcher();
		return $templateMgr->fetchAjax(
			'reviewFormElementsGridContainer',
			$dispatcher->url(
				$request, ROUTE_COMPONENT, null,
				'grid.settings.reviewForms.ReviewFormElementsGridHandler', 'fetchGrid', null,
				array('reviewFormId' => (int) $request->getUserVar('reviewFormId'))
			)
		);
	}

	/**
	 * Update an existing review form.
	 * @param $args array
	 * @param $request PKPRequest
	 * @return JSONMessage JSON message
	 */
	function updateReviewForm($args, $request) {
		// Identify the review form Id.
		$reviewFormId = (int) $request->getUserVar('reviewFormId');

		// Identify the context id.
		$context = $request->getContext();

		// Get review form object
		$reviewFormDao = DAORegistry::getDAO('ReviewFormDAO');
		$reviewForm = $reviewFormDao->getById($reviewFormId, Application::getContextAssocType(), $context->getId());

		// Form handling.
		$reviewFormForm = new ReviewFormForm(!isset($reviewFormId) || empty($reviewFormId) ? null : $reviewFormId);
		$reviewFormForm->readInputData();

		if ($reviewFormForm->validate()) {
			$reviewFormForm->execute($request);

			// Create the notification.
			$notificationMgr = new NotificationManager();
			$user = $request->getUser();
			$notificationMgr->createTrivialNotification($user->getId());

			return DAO::getDataChangedEvent($reviewFormId);

		}

		return new JSONMessage(false);
	}

	/**
	 * Copy a review form.
	 * @param $args array
	 * @param $request PKPRequest
	 * @return JSONMessage JSON object
	 */
	function copyReviewForm($args, $request) {
		// Identify the current review form
		$reviewFormId = (int) $request->getUserVar('rowId');

		// Identify the context id.
		$context = $request->getContext();

		// Get review form object
		$reviewFormDao = DAORegistry::getDAO('ReviewFormDAO');
		$reviewForm = $reviewFormDao->getById($reviewFormId, Application::getContextAssocType(), $context->getId());

		if ($request->checkCSRF() && isset($reviewForm)) {
			$reviewForm->setActive(0);
			$reviewForm->setSequence(REALLY_BIG_NUMBER);
			$newReviewFormId = $reviewFormDao->insertObject($reviewForm);
			$reviewFormDao->resequenceReviewForms(Application::getContextAssocType(), $context->getId());

			$reviewFormElementDao = DAORegistry::getDAO('ReviewFormElementDAO');
			$reviewFormElements = $reviewFormElementDao->getByReviewFormId($reviewFormId);
			while ($reviewFormElement = $reviewFormElements->next()) {
				$reviewFormElement->setReviewFormId($newReviewFormId);
				$reviewFormElement->setSequence(REALLY_BIG_NUMBER);
				$reviewFormElementDao->insertObject($reviewFormElement);
				$reviewFormElementDao->resequenceReviewFormElements($newReviewFormId);
			}

			// Create the notification.
			$notificationMgr = new NotificationManager();
			$user = $request->getUser();
			$notificationMgr->createTrivialNotification($user->getId());

			return DAO::getDataChangedEvent($newReviewFormId);
		}

		return new JSONMessage(false);
	}

	/**
	 * Activate a review form.
	 * @param $args array
	 * @param $request PKPRequest
	 * @return JSONMessage JSON object
	 */
	function activateReviewForm($args, $request) {
		// Identify the current review form
		$reviewFormId = (int) $request->getUserVar('reviewFormKey');

		// Identify the context id.
		$context = $request->getContext();

		// Get review form object
		$reviewFormDao = DAORegistry::getDAO('ReviewFormDAO');
		$reviewForm = $reviewFormDao->getById($reviewFormId, Application::getContextAssocType(), $context->getId());

		if ($request->checkCSRF() && isset($reviewForm) && !$reviewForm->getActive()) {
			$reviewForm->setActive(1);
			$reviewFormDao->updateObject($reviewForm);

			// Create the notification.
			$notificationMgr = new NotificationManager();
			$user = $request->getUser();
			$notificationMgr->createTrivialNotification($user->getId());

			return DAO::getDataChangedEvent($reviewFormId);
		}

		return new JSONMessage(false);
	}


	/**
	 * Deactivate a review form.
	 * @param $args array
	 * @param $request PKPRequest
	 * @return JSONMessage JSON object
	 */
	function deactivateReviewForm($args, $request) {

		// Identify the current review form
		$reviewFormId = (int) $request->getUserVar('reviewFormKey');

		// Identify the context id.
		$context = $request->getContext();

		// Get review form object
		$reviewFormDao = DAORegistry::getDAO('ReviewFormDAO');
		$reviewForm = $reviewFormDao->getById($reviewFormId, Application::getContextAssocType(), $context->getId());

		if ($request->checkCSRF() && isset($reviewForm) && $reviewForm->getActive()) {
			$reviewForm->setActive(0);
			$reviewFormDao->updateObject($reviewForm);

			// Create the notification.
			$notificationMgr = new NotificationManager();
			$user = $request->getUser();
			$notificationMgr->createTrivialNotification($user->getId());

			return DAO::getDataChangedEvent($reviewFormId);
		}

		return new JSONMessage(false);
	}

	/**
	 * Delete a review form.
	 * @param $args array
	 * @param $request PKPRequest
	 * @return JSONMessage JSON object
	 */
	function deleteReviewForm($args, $request) {
		// Identify the current review form
		$reviewFormId = (int) $request->getUserVar('rowId');

		// Identify the context id.
		$context = $request->getContext();

		// Get review form object
		$reviewFormDao = DAORegistry::getDAO('ReviewFormDAO');
		$reviewForm = $reviewFormDao->getById($reviewFormId, Application::getContextAssocType(), $context->getId());

		$completeCounts = $reviewFormDao->getUseCounts(Application::getContextAssocType(), $context->getId(), true);
		$incompleteCounts = $reviewFormDao->getUseCounts(Application::getContextAssocType(), $context->getId(), false);

		if ($request->checkCSRF() && isset($reviewForm) && $completeCounts[$reviewFormId] == 0 && $incompleteCounts[$reviewFormId] == 0) {
			$reviewAssignmentDao = DAORegistry::getDAO('ReviewAssignmentDAO');
			$reviewAssignments = $reviewAssignmentDao->getByReviewFormId($reviewFormId);

			foreach ($reviewAssignments as $reviewAssignment) {
				$reviewAssignment->setReviewFormId(null);
				$reviewAssignmentDao->updateObject($reviewAssignment);
			}

			$reviewFormDao->deleteById($reviewFormId, $context->getId());

			// Create the notification.
			$notificationMgr = new NotificationManager();
			$user = $request->getUser();
			$notificationMgr->createTrivialNotification($user->getId());

			return DAO::getDataChangedEvent($reviewFormId);
		}

		return new JSONMessage(false);
	}
}

?>
