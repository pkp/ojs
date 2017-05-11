<?php

/**
 * @file controllers/grid/announcements/AnnouncementTypeGridHandler.inc.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2003-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class AnnouncementTypeGridHandler
 * @ingroup controllers_grid_announcements
 *
 * @brief Handle announcement type grid requests.
 */

import('lib.pkp.classes.controllers.grid.GridHandler');
import('lib.pkp.controllers.grid.announcements.form.AnnouncementTypeForm');

class AnnouncementTypeGridHandler extends GridHandler {
	/**
	 * Constructor
	 */
	function __construct() {
		parent::__construct();
		$this->addRoleAssignment(
			ROLE_ID_MANAGER,
			array(
				'fetchGrid', 'fetchRow',
				'addAnnouncementType', 'editAnnouncementType',
				'updateAnnouncementType',
				'deleteAnnouncementType'
			)
		);
	}

	//
	// Overridden template methods
	//
	/**
	 * @copydoc GridHandler::authorize()
	 */
	function authorize($request, &$args, $roleAssignments) {
		import('lib.pkp.classes.security.authorization.ContextAccessPolicy');
		$this->addPolicy(new ContextAccessPolicy($request, $roleAssignments));
		$context = $request->getContext();

		$announcementTypeId = $request->getUserVar('announcementTypeId');
		if ($announcementTypeId) {
			// Ensure announcement type is valid and for this context
			$announcementTypeDao = DAORegistry::getDAO('AnnouncementTypeDAO'); /* @var $announcementTypeDao AnnouncementTypeDAO */
			$announcementType = $announcementTypeDao->getById($announcementTypeId);
			if (!$announcementType || $announcementType->getAssocType() != $context->getAssocType() || $announcementType->getAssocId() != $context->getId()) {
				return false;
			}
		}
		return parent::authorize($request, $args, $roleAssignments);
	}

	/**
	 * @copydoc GridHandler::initialize()
	 */
	function initialize($request, $args = null) {
		parent::initialize($request, $args);

		// Basic grid configuration
		$this->setTitle('manager.announcementTypes');

		// Set the no items row text
		$this->setEmptyRowText('manager.announcementTypes.noneCreated');

		$context = $request->getContext();

		// Columns
		import('lib.pkp.controllers.grid.announcements.AnnouncementTypeGridCellProvider');
		$announcementTypeCellProvider = new AnnouncementTypeGridCellProvider();
		$this->addColumn(
			new GridColumn('name',
				'common.name',
				null,
				null,
				$announcementTypeCellProvider,
				array('width' => 60)
			)
		);

		// Load language components
		AppLocale::requireComponents(LOCALE_COMPONENT_PKP_MANAGER);

		// Add grid action.
		$router = $request->getRouter();

		import('lib.pkp.classes.linkAction.request.AjaxModal');
		$this->addAction(
			new LinkAction(
				'addAnnouncementType',
				new AjaxModal(
					$router->url($request, null, null, 'addAnnouncementType', null, null),
					__('grid.action.addAnnouncementType'),
					'modal_add_item',
					true
				),
				__('grid.action.addAnnouncementType'),
				'add_item'
			)
		);
	}

	/**
	 * @copydoc GridHandler::loadData()
	 */
	protected function loadData($request, $filter) {
		$context = $request->getContext();
		$announcementTypeDao = DAORegistry::getDAO('AnnouncementTypeDAO');
		return $announcementTypeDao->getByAssoc($context->getAssocType(), $context->getId());
	}

	/**
	 * @copydoc GridHandler::getRowInstance()
	 */
	protected function getRowInstance() {
		import('lib.pkp.controllers.grid.announcements.AnnouncementTypeGridRow');
		return new AnnouncementTypeGridRow();
	}

	//
	// Public grid actions.
	//
	/**
	 * Display form to add announcement type.
	 * @param $args array
	 * @param $request PKPRequest
	 * @return string
	 */
	function addAnnouncementType($args, $request) {
		return $this->editAnnouncementType($args, $request);
	}

	/**
	 * Display form to edit an announcement type.
	 * @param $args array
	 * @param $request PKPRequest
	 * @return JSONMessage JSON object
	 */
	function editAnnouncementType($args, $request) {
		$announcementTypeId = (int)$request->getUserVar('announcementTypeId');
		$context = $request->getContext();
		$contextId = $context->getId();

		$announcementTypeForm = new AnnouncementTypeForm($contextId, $announcementTypeId);
		$announcementTypeForm->initData($args, $request);

		return new JSONMessage(true, $announcementTypeForm->fetch($request));
	}

	/**
	 * Save an edited/inserted announcement type.
	 * @param $args array
	 * @param $request PKPRequest
	 * @return JSONMessage JSON object
	 */
	function updateAnnouncementType($args, $request) {

		// Identify the announcement type id.
		$announcementTypeId = $request->getUserVar('announcementTypeId');
		$context = $request->getContext();
		$contextId = $context->getId();

		// Form handling.
		$announcementTypeForm = new AnnouncementTypeForm($contextId, $announcementTypeId);
		$announcementTypeForm->readInputData();

		if ($announcementTypeForm->validate()) {
			$announcementTypeForm->execute($request);

			if ($announcementTypeId) {
				// Successful edit of an existing announcement type.
				$notificationLocaleKey = 'notification.editedAnnouncementType';
			} else {
				// Successful added a new announcement type.
				$notificationLocaleKey = 'notification.addedAnnouncementType';
			}

			// Record the notification to user.
			$notificationManager = new NotificationManager();
			$user = $request->getUser();
			$notificationManager->createTrivialNotification($user->getId(), NOTIFICATION_TYPE_SUCCESS, array('contents' => __($notificationLocaleKey)));

			// Prepare the grid row data.
			return DAO::getDataChangedEvent($announcementTypeId);
		} else {
			return new JSONMessage(false);
		}
	}

	/**
	 * Delete an announcement type.
	 * @param $args array
	 * @param $request PKPRequest
	 * @return JSONMessage JSON object
	 */
	function deleteAnnouncementType($args, $request) {
		$announcementTypeId = (int) $request->getUserVar('announcementTypeId');
		$context = $request->getContext();

		$announcementTypeDao = DAORegistry::getDAO('AnnouncementTypeDAO');
		$announcementType = $announcementTypeDao->getById($announcementTypeId, $context->getAssocType(), $context->getId());
		if ($announcementType && $request->checkCSRF()) {
			$announcementTypeDao->deleteObject($announcementType);

			// Create notification.
			$notificationManager = new NotificationManager();
			$user = $request->getUser();
			$notificationManager->createTrivialNotification($user->getId(), NOTIFICATION_TYPE_SUCCESS, array('contents' => __('notification.removedAnnouncementType')));

			return DAO::getDataChangedEvent($announcementTypeId);
		}

		return new JSONMessage(false);
	}
}

?>
