<?php
/**
 * @file pages/dashboard/DashboardHandler.inc.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2003-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class DashboardHandler
 * @ingroup pages_dashboard
 *
 * @brief Handle requests for user's dashboard.
 */

import('classes.handler.Handler');

class DashboardHandler extends Handler {
	/**
	 * Constructor
	 */
	function __construct() {
		parent::__construct();

		$this->addRoleAssignment(array(ROLE_ID_SITE_ADMIN, ROLE_ID_MANAGER, ROLE_ID_SUB_EDITOR, ROLE_ID_AUTHOR, ROLE_ID_REVIEWER, ROLE_ID_ASSISTANT),
				array('index', 'tasks', 'myQueue', 'active', 'archives'));
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
	 * Display about index page.
	 * @param $request PKPRequest
	 * @param $args array
	 */
	function index($args, $request) {
		if ($request->getContext()) {
			$templateMgr = TemplateManager::getManager($request);
			$this->setupTemplate($request);
			return $templateMgr->display('dashboard/index.tpl');
		}
		$request->redirect(null, 'user');
	}

	/**
	 * View tasks tab
	 * @param $args array
	 * @param $request PKPRequest
	 * @return JSONMessage JSON object
	 */
	function tasks($args, $request) {
		$templateMgr = TemplateManager::getManager($request);
		$this->setupTemplate($request);


		return $templateMgr->fetchJson('dashboard/tasks.tpl');
	}

	/**
	 * View myQueue tab
	 * @param $args array
	 * @param $request PKPRequest
	 * @return JSONMessage JSON object
	 */
	function myQueue($args, $request) {
		$templateMgr = TemplateManager::getManager($request);
		$this->setupTemplate($request);

		// Get all the contexts in the system, to determine which 'new submission' entry point we display
		$contextDao = Application::getContextDAO(); /* @var $contextDao ContextDAO */
		$contexts = $contextDao->getAll();

		// Check each context to see if user has access to it.
		$user = $request->getUser();
		$roleDao = DAORegistry::getDAO('RoleDAO');
		$allContextsUserRoles = $roleDao->getByUserIdGroupedByContext($user->getId());
		$userRolesThatCanSubmit = array(ROLE_ID_AUTHOR, ROLE_ID_ASSISTANT, ROLE_ID_MANAGER, ROLE_ID_SUB_EDITOR);
		$accessibleContexts = array();
		while ($context = $contexts->next()) {
			if (array_key_exists($context->getId(), $allContextsUserRoles)) {
				$contextUserRoles = array_keys($allContextsUserRoles[$context->getId()]);
				if (array_intersect($userRolesThatCanSubmit, $contextUserRoles)) {
					$accessibleContexts[] = $context;
				}
			}
		}

		return $templateMgr->fetchJson('dashboard/myQueue.tpl');
	}

	/**
	 * View active submissions tab
	 * @param $args array
	 * @param $request PKPRequest
	 * @return JSONMessage JSON object
	 */
	function active($args, $request) {
		$templateMgr = TemplateManager::getManager($request);
		$this->setupTemplate($request);
		return $templateMgr->fetchJson('dashboard/active.tpl');
	}

	/**
	 * View archived submissions tab
	 * @param $args array
	 * @param $request PKPRequest
	 * @return JSONMessage JSON object
	 */
	function archives($args, $request) {
		$templateMgr = TemplateManager::getManager($request);
		$this->setupTemplate($request);
		return $templateMgr->fetchJson('dashboard/archives.tpl');
	}

	/**
	 * Setup common template variables.
	 * @param $request PKPRequest
	 */
	function setupTemplate($request = null) {
		AppLocale::requireComponents(LOCALE_COMPONENT_APP_MANAGER, LOCALE_COMPONENT_PKP_MANAGER, LOCALE_COMPONENT_APP_SUBMISSION, LOCALE_COMPONENT_PKP_SUBMISSION);
		parent::setupTemplate($request);
	}
}

?>
