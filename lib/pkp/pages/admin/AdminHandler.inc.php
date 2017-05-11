<?php

/**
 * @file pages/admin/AdminHandler.inc.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2003-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class AdminHandler
 * @ingroup pages_admin
 *
 * @brief Handle requests for site administration functions.
 */

import('classes.handler.Handler');

class AdminHandler extends Handler {
	/**
	 * Constructor
	 */
	function __construct() {
		parent::__construct();

		$this->addRoleAssignment(
			array(ROLE_ID_SITE_ADMIN),
			array('index', 'settings')
		);
	}

	/**
	 * @copydoc PKPHandler::authorize()
	 */
	function authorize($request, &$args, $roleAssignments) {
		import('lib.pkp.classes.security.authorization.PKPSiteAccessPolicy');
		$this->addPolicy(new PKPSiteAccessPolicy($request, null, $roleAssignments));
		$returner = parent::authorize($request, $args, $roleAssignments);

		// Make sure user is in a context. Otherwise, redirect.
		$context = $request->getContext();
		$router = $request->getRouter();
		$requestedOp = $router->getRequestedOp($request);

		if ($requestedOp == 'settings') {
			$contextDao = Application::getContextDAO();
			$contextFactory = $contextDao->getAll();
			if ($contextFactory->getCount() == 1) {
				// Don't let users access site settings in a single context installation.
				// In that case, those settings are available under management or are not
				// relevant (like site appearance).
				return false;
			}
		}

		return $returner;
	}

	/**
	 * Display site admin index page.
	 * @param $args array
	 * @param $request PKPRequest
	 */
	function index($args, $request) {
		$this->setupTemplate($request);
		$templateMgr = TemplateManager::getManager($request);
		$workingContexts = $this->getWorkingContexts($request);
		$templateMgr->assign('multipleContexts', $workingContexts->getCount() > 1);
		$templateMgr->display('admin/index.tpl');
	}

	/**
	 * Display the administration settings page.
	 * @param $args array
	 * @param $request PKPRequest
	 */
	function settings($args, $request) {
		$this->setupTemplate($request);
		$templateMgr = TemplateManager::getManager($request);
		$templateMgr->display('admin/settings.tpl');
	}

	/**
	 * Initialize the handler.
	 * @param $request PKPRequest
	 * @param $args array
	 */
	function initialize($request, $args = null) {
		AppLocale::requireComponents(LOCALE_COMPONENT_PKP_ADMIN, LOCALE_COMPONENT_APP_MANAGER, LOCALE_COMPONENT_APP_ADMIN, LOCALE_COMPONENT_APP_COMMON);
		return parent::initialize($request, $args);
	}
}

?>
