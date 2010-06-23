<?php

/**
 * @file classes/controllers/grid/filter/FilterGridHandler.inc.php
 *
 * Copyright (c) 2000-2010 John Willinsky
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
	}


	//
	// Overridden methods from PKPHandler
	//
	/**
	 * OJS-specific authorization and validation checks
	 *
	 * Checks whether the user is the assigned manager for
	 * the filter grid's context (=journal).
	 *
	 * @see PKPHandler::validate()
	 */
	function validate($requiredContexts, &$request) {
		// Retrieve the request context
		$router =& $request->getRouter();
		$journal =& $router->getContext($request);

		// 1) We need a journal
		$this->addCheck(new HandlerValidatorJournal($this, false, 'No journal in context!'));

		// 2) Only journal managers or site administrators may access
		$this->addCheck(new HandlerValidatorRoles($this, true, null, null, array(ROLE_ID_SITE_ADMIN, ROLE_ID_JOURNAL_MANAGER)));

		// Execute application-independent checks
		if (!parent::validate($requiredContexts, $request, $journal)) return false;

		return true;
	}
}
