<?php

/**
 * @file controllers/grid/notifications/NotificationsGridHandler.inc.php
 *
 * Copyright (c) 2003-2013 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class NotificationsGridHandler
 * @ingroup controllers_grid_notifications
 *
 * @brief Handle the display of notifications for a given user
 */

// Import UI base classes.
import('lib.pkp.classes.controllers.grid.notifications.PKPNotificationsGridHandler');

class NotificationsGridHandler extends PKPNotificationsGridHandler {
	/**
	 * Constructor
	 */
	function NotificationsGridHandler() {
		parent::PKPNotificationsGridHandler();

		$this->addRoleAssignment(
			array(ROLE_ID_MANAGER, ROLE_ID_EDITOR, ROLE_ID_ASSISTANT, ROLE_ID_AUTHOR, ROLE_ID_REVIEWER, ROLE_ID_SUB_EDITOR),
			array('fetchGrid')
		);
	}

	//
	// Protected methods
	//
	/**
	 * Get the implementation of the cell provider for this grid.
	 */
	function _getCellProvider() {
		import('controllers.grid.notifications.NotificationsGridCellProvider');
		return new NotificationsGridCellProvider();
	}
}

?>
