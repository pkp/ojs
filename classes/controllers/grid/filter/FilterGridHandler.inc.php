<?php

/**
 * @file classes/controllers/grid/filter/FilterGridHandler.inc.php
 *
 * Copyright (c) 2013-2015 Simon Fraser University Library
 * Copyright (c) 2000-2015 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class FilterGridHandler
 * @ingroup classes_controllers_grid_filter
 *
 * @brief Handle OJS specific parts of filter grid requests.
 */

import('lib.pkp.classes.controllers.grid.filter.PKPFilterGridHandler');

// import validation classes
import('classes.handler.validation.HandlerValidatorJournal');
import('lib.pkp.classes.handler.validation.HandlerValidatorRoles');

class FilterGridHandler extends PKPFilterGridHandler {
	/**
	 * Constructor
	 */
	function FilterGridHandler() {
		parent::PKPFilterGridHandler();
		$this->addRoleAssignment(
				array(ROLE_ID_SITE_ADMIN, ROLE_ID_JOURNAL_MANAGER),
				array('fetchGrid', 'addFilter', 'editFilter', 'updateFilter', 'deleteFilter'));
	}


	//
	// Implement template methods from PKPHandler
	//
	/**
	 * @see PKPHandler::authorize()
	 */
	function authorize(&$request, &$args, $roleAssignments) {
		// Make sure the user can change the journal setup.
		import('classes.security.authorization.OjsJournalAccessPolicy');
		$this->addPolicy(new OjsJournalAccessPolicy($request, $roleAssignments));
		return parent::authorize($request, $args, $roleAssignments);
	}
}
