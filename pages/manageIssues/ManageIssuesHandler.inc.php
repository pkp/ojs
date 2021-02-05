<?php

/**
 * @file pages/manageIssues/ManageIssuesHandler.inc.php
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2003-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
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

	/** @copydoc PKPHandler::_isBackendPage */
	var $_isBackendPage = true;

	/**
	 * Constructor
	 */
	function __construct() {
		parent::__construct();
		$this->addRoleAssignment(
			array(ROLE_ID_MANAGER),
			array(
				'index',
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
		$templateMgr->assign([
			'pageTitle' => __('editor.navigation.issues')
		]);
		return $templateMgr->display('manageIssues/issues.tpl');
	}
}


