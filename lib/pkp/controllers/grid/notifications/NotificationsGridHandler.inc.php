<?php

/**
 * @file controllers/grid/notifications/NotificationsGridHandler.inc.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2003-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class NotificationsGridHandler
 * @ingroup controllers_grid_notifications
 *
 * @brief Handle the display of notifications for a given user
 */

// Import UI base classes.
import('lib.pkp.classes.controllers.grid.GridHandler');

// Other classes associated with this grid
import('lib.pkp.controllers.grid.notifications.NotificationsGridCellProvider');

class NotificationsGridHandler extends GridHandler {
	/** @var $_selectedNotificationIds array Set of selected IDs */
	var $_selectedNotificationIds;

	/**
	 * Constructor
	 */
	function __construct() {
		parent::__construct();
	}


	/**
	 * @copydoc GridHandler::initialize()
	 */
	function initialize($request, $args = null) {
		parent::initialize($request);
		AppLocale::requireComponents(LOCALE_COMPONENT_APP_SUBMISSION, LOCALE_COMPONENT_PKP_SUBMISSION);

		$this->_selectedNotificationIds = (array) $request->getUserVar('selectedNotificationIds');

		$cellProvider = new NotificationsGridCellProvider();
		$this->addColumn(
			new GridColumn(
				'task',
				$this->getNotificationsColumnTitle(),
				null,
				null,
				$cellProvider,
				array('anyhtml' => true,
						'alignment' => COLUMN_ALIGNMENT_LEFT)
			)
		);

		// Set the no items row text
		$this->setEmptyRowText('grid.noItems');

		import('lib.pkp.classes.linkAction.request.NullAction');
		$this->addAction(
			new LinkAction(
				'markNew',
				new NullAction(),
				__('grid.action.markNew'),
				'edit' // FIXME: Icon
			),
			GRID_ACTION_POSITION_BELOW
		);
		$this->addAction(
			new LinkAction(
				'markRead',
				new NullAction(),
				__('grid.action.markRead'),
				'edit' // FIXME: Icon
			),
			GRID_ACTION_POSITION_BELOW
		);

		$router = $request->getRouter();
		$this->addAction(
			new LinkAction(
				'deleteNotifications',
				new NullAction(),
				__('grid.action.delete'),
				'delete'
			),
			GRID_ACTION_POSITION_BELOW
		);
	}


	//
	// Overridden methods from GridHandler
	//
	/**
	 * @see GridHandler::getJSHandler()
	 */
	public function getJSHandler() {
		return '$.pkp.controllers.grid.notifications.NotificationsGridHandler';
	}

	/**
	 * @see GridHandler::setUrls()
	 */
	function setUrls($request, $extraUrls = array()) {
		$router = $request->getRouter();
		parent::setUrls(
			$request,
			array_merge(
				$extraUrls,
				array(
					'markNewUrl' => $router->url($request, null, null, 'markNew', null, $this->getRequestArgs()),
					'markReadUrl' => $router->url($request, null, null, 'markRead', null, $this->getRequestArgs()),
					'deleteUrl' => $router->url($request, null, null, 'deleteNotifications', null, $this->getRequestArgs()),
				)
			)
		);
	}

	/**
	 * Get the list of "publish data changed" events.
	 * Used to update the site context switcher upon create/delete.
	 * @return array
	 */
	function getPublishChangeEvents() {
		return array('updateUnreadNotificationsCount');
	}

	/**
	 * @copydoc GridHandler::initFeatures()
	 */
	function initFeatures($request, $args) {
		import('lib.pkp.classes.controllers.grid.feature.selectableItems.SelectableItemsFeature');
		import('lib.pkp.classes.controllers.grid.feature.PagingFeature');
		return array(new SelectableItemsFeature(), new PagingFeature());
	}

	/**
	 * @copydoc GridHandler::getSelectName()
	 */
	function getSelectName() {
		return 'selectedNotifications';
	}

	/**
	 * @copydoc GridHandler::isDataElementSelected()
	 */
	function isDataElementSelected($gridDataElement) {
		return in_array($gridDataElement->getId(), $this->_selectedNotificationIds);
	}


	//
	// Protected methods.
	//
	/**
	 * Get the notifications column title.
	 * @return string Locale key.
	 */
	protected function getNotificationsColumnTitle() {
		return 'common.tasks';
	}


	//
	// Public methods
	//
	/**
	 * Mark notifications unread
	 * @param $args array
	 * @param $request PKPRequest
	 * @return JSONMessage JSON object
	 */
	function markNew($args, $request) {
		$notificationDao = DAORegistry::getDAO('NotificationDAO');
		$user = $request->getUser();

		$selectedElements = (array) $request->getUserVar('selectedElements');
		foreach ($selectedElements as $notificationId) {
			if ($notification = $notificationDao->getById($notificationId, $user->getId())) {
				$notificationDao->setDateRead($notificationId, null);
			}
		}
		return DAO::getDataChangedEvent(null, null, $selectedElements);
	}

	/**
	 * Mark notifications unread
	 * @param $args array
	 * @param $request PKPRequest
	 * @return JSONMessage JSON object
	 */
	function markRead($args, $request) {
		$notificationDao = DAORegistry::getDAO('NotificationDAO');
		$user = $request->getUser();

		$selectedElements = (array) $request->getUserVar('selectedElements');
		foreach ($selectedElements as $notificationId) {
			if ($notification = $notificationDao->getById($notificationId, $user->getId())) {
				$notificationDao->setDateRead($notificationId, Core::getCurrentDate());
			}
		}
		if ($request->getUserVar('redirect')) {
			// In this case, the user has clicked on a notification
			// and wants to view it. Mark it read first and redirect
			$notificationMgr = new NotificationManager();
			return $request->redirectUrlJson($notificationMgr->getNotificationUrl($request, $notification));
		} else {
			// The notification has been marked read explicitly.
			// Update its status in the grid.
			return DAO::getDataChangedEvent(null, null, $selectedElements);
		}
	}

	/**
	 * Delete notifications
	 * @param $args array
	 * @param $request PKPRequest
	 * @return JSONMessage JSON object
	 */
	function deleteNotifications($args, $request) {
		$notificationDao = DAORegistry::getDAO('NotificationDAO');
		$user = $request->getUser();

		$selectedElements = (array) $request->getUserVar('selectedElements');
		foreach ($selectedElements as $notificationId) {
			if ($notification = $notificationDao->getById($notificationId, $user->getId())) {
				$notificationDao->deleteObject($notification);
			}
		}
		return DAO::getDataChangedEvent();
	}

	/**
	 * Get unread notifications count
	 * @param $args array
	 * @param $request PKPRequest
	 * @return JSONMessage JSON object
	 */
	function getUnreadNotificationsCount($args, $request) {
		$notificationDao = DAORegistry::getDAO('NotificationDAO');
		$user = $request->getUser();
		return new JSONMessage(true, $notificationDao->getNotificationCount(false, $user->getId(), null, NOTIFICATION_LEVEL_TASK));
	}
}

?>
