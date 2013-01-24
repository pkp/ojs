<?php

/**
 * @file controllers/grid/announcements/AnnouncementTypeGridHandler.inc.php
 *
 * Copyright (c) 2003-2012 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class AnnouncementTypeGridHandler
 * @ingroup controllers_grid_announcements
 *
 * @brief Handle announcement type grid requests.
 */

import('lib.pkp.classes.controllers.grid.announcements.PKPAnnouncementTypeGridHandler');

import('controllers.grid.announcements.form.AnnouncementTypeForm');

class AnnouncementTypeGridHandler extends PKPAnnouncementTypeGridHandler {
	/**
	 * Constructor
	 */
	function AnnouncementTypeGridHandler() {
		parent::PKPAnnouncementTypeGridHandler();
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
	 * @see GridHandler::authorize()
	 */
	function authorize($request, &$args, $roleAssignments) {
		import('lib.pkp.classes.security.authorization.PkpContextAccessPolicy');
		$this->addPolicy(new PkpContextAccessPolicy($request, $roleAssignments));
		return parent::authorize($request, $args, $roleAssignments);
	}
}

?>
