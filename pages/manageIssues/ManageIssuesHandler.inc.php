<?php

/**
 * @file pages/manageIssues/ManageIssuesHandler.inc.php
 *
 * Copyright (c) 2014-2019 Simon Fraser University
 * Copyright (c) 2003-2019 John Willinsky
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
	function __construct() {
		parent::__construct();
		$this->addRoleAssignment(
			array(ROLE_ID_SUB_EDITOR, ROLE_ID_MANAGER),
			array(
				'index', 'issuesTabs'
			)
		);
	}

	/**
	 * @copydoc PKPHandler::authorize()
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
	 * @return string Response contents.
	 */
	function index($args, $request) {
		$this->setupTemplate($request);
		AppLocale::requireComponents(LOCALE_COMPONENT_APP_EDITOR, LOCALE_COMPONENT_APP_MANAGER);

		$templateMgr = TemplateManager::getManager($request);
		return $templateMgr->display('manageIssues/issues.tpl');
	}

	/**
	 * Returns the issues tabs in the form of a JSON Message.
	 * @param $args array
	 * @param $request PKPRequest
	 * @return JSONMessage JSON object
	 */
	function issuesTabs($args, $request) {
		$this->setupTemplate($request);
		AppLocale::requireComponents(LOCALE_COMPONENT_APP_EDITOR);

		$templateMgr = TemplateManager::getManager($request);

		return $templateMgr->fetchJson('manageIssues/issuesTabs.tpl');
	}
}


