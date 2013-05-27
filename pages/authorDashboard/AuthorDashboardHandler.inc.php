<?php

/**
 * @file pages/authorDashboard/AuthorDashboardHandler.inc.php
 *
 * Copyright (c) 2003-2013 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class AuthorDashboardHandler
 * @ingroup pages_authorDashboard
 *
 * @brief Handle requests for the author dashboard.
 */

// Import base class
import('lib.pkp.pages.authorDashboard.PKPAuthorDashboardHandler');

class AuthorDashboardHandler extends PKPAuthorDashboardHandler {

	/**
	 * Constructor
	 */
	function AuthorDashboardHandler() {
		parent::PKPAuthorDashboardHandler();
	}


	//
	// Implement template methods from PKPHandler
	//
	/**
	 * @see PKPHandler::authorize()
	 */
	function authorize($request, &$args, $roleAssignments) {
		import('classes.security.authorization.OjsAuthorDashboardAccessPolicy');
		$this->addPolicy(new OjsAuthorDashboardAccessPolicy($request, $args, $roleAssignments), true);

		return parent::authorize($request, $args, $roleAssignments);
	}
}

?>
