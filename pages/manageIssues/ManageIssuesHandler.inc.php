<?php

/**
 * @file pages/manageIssues/ManageIssuesHandler.inc.php
 *
 * Copyright (c) 2003-2013 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class IssueManagementHandler
 * @ingroup pages_editor
 *
 * @brief Handle requests for issue management in publishing.
 */

import('classes.handler.Handler');

class ManageIssuesHandler extends Handler {
	/** issue associated with the request **/
	var $issue;

	/**
	 * Constructor
	 */
	function ManageIssuesHandler() {
		$this->addRoleAssignment(
			array(ROLE_ID_SUB_EDITOR, ROLE_ID_MANAGER),
			array(
				'index',
			)
		);
	}

	/**
	 * @see PKPHandler::authorize()
	 * @param $request PKPRequest
	 * @param $args array
	 * @param $roleAssignments array
	 */
	function authorize($request, &$args, $roleAssignments) {
		import('lib.pkp.classes.security.authorization.PKPSiteAccessPolicy');
		$this->addPolicy(new PKPSiteAccessPolicy($request, null, $roleAssignments));
		return parent::authorize($request, $args, $roleAssignments);
	}

	/**
	 * Displays the issue listings in a tabbed interface.
	 * @param $args array
	 * @param $request PKPRequest
	 */
	function index($args, $request) {
		$this->setupTemplate($request);
		AppLocale::requireComponents(LOCALE_COMPONENT_APP_EDITOR);

		$templateMgr = TemplateManager::getManager($request);
		$templateMgr->display('manageIssues/issues.tpl');
	}
}

?>
