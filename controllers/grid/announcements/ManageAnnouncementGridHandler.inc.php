<?php

/**
 * @file controllers/grid/announcements/ManageAnnouncementGridHandler.inc.php
 *
 * Copyright (c) 2003-2012 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class ManageAnnouncementGridHandler
 * @ingroup controllers_grid_announcements
 *
 * @brief Handle announcements management grid requests.
 */

import('lib.pkp.classes.controllers.grid.announcements.PKPManageAnnouncementGridHandler');

import('controllers.grid.announcements.form.AnnouncementForm');

class ManageAnnouncementGridHandler extends PKPManageAnnouncementGridHandler {
	/**
	 * Constructor
	 */
	function ManageAnnouncementGridHandler() {
		parent::PKPManageAnnouncementGridHandler();
		$this->addRoleAssignment(
			ROLE_ID_MANAGER,
			array(
				'fetchGrid', 'fetchRow', 'moreInformation',
				'addAnnouncement', 'editAnnouncement',
				'updateAnnouncement', 'deleteAnnouncement'
			)
		);
	}


	//
	// Overridden template methods
	//
	/**
	 * @see GridHandler::authorize()
	 */
	function authorize(&$request, &$args, $roleAssignments) {
		import('lib.pkp.classes.security.authorization.PkpContextAccessPolicy');
		$this->addPolicy(new PkpContextAccessPolicy($request, $roleAssignments));
		return parent::authorize($request, $args, $roleAssignments, false);
	}
}

?>
