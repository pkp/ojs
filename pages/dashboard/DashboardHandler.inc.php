<?php
/**
 * @file pages/dashboard/DashboardHandler.inc.php
 *
 * Copyright (c) 2003-2013 John Willinsky
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
	function DashboardHandler() {
		parent::Handler();

		$this->addRoleAssignment(array(ROLE_ID_SITE_ADMIN, ROLE_ID_MANAGER, ROLE_ID_GUEST_EDITOR, ROLE_ID_AUTHOR, ROLE_ID_REVIEWER, ROLE_ID_ASSISTANT),
				array('index', 'tasks', 'submissions', 'archives'));
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
	 * Display about index page.
	 * @param $request PKPRequest
	 * @param $args array
	 */
	function index($args, $request) {
		$templateMgr = TemplateManager::getManager($request);
		$this->setupTemplate($request);
		$templateMgr->display('dashboard/index.tpl');
	}

	/**
	 * View tasks tab
	 * @param $args array
	 * @param $request PKPRequest
	 */
	function tasks($args, $request) {
		$templateMgr = TemplateManager::getManager($request);
		$this->setupTemplate($request);

		// Get all the journals in the system, to determine which 'new submission' entry point we display
		$journalDao = DAORegistry::getDAO('JournalDAO'); /* @var $journalDao JournalDAO */
		$journals = $journalDao->getAll();

		// Check each journal to see if user has access to it.
		$user = $request->getUser();
		$roleDao = DAORegistry::getDAO('RoleDAO');
		$allContextsUserRoles = $roleDao->getByUserIdGroupedByContext($user->getId());
		$userRolesThatCanSubmit = array(ROLE_ID_AUTHOR, ROLE_ID_ASSISTANT, ROLE_ID_MANAGER, ROLE_ID_SECTION_EDITOR);
		$accessibleJournals = array();
		while ($journal = $journals->next()) {
			if (array_key_exists($journal->getId(), $allContextsUserRoles)) {
				$journalContextUserRoles = array_keys($allContextsUserRoles[$journal->getId()]);
				if (array_intersect($userRolesThatCanSubmit, $journalContextUserRoles)) {
					$accessibleJournals[] = $journal;
				}
			}
		}

		// Assign journals to template.
		$journalCount = count($accessibleJournals);
		$templateMgr->assign('journalCount', $journalCount);
		if ($journalCount == 1) {
			$templateMgr->assign('journal', $accessibleJournals[0]);
		} elseif ($journalCount > 1) {
			$journals = array();
			foreach ($accessibleJournals as $journal) {
				// FIXME when ready for OMP-style submission URLs, change the URL below.
				$url = $request->url($journal->getPath(), 'submission');
				$journals[$url] = $journal->getLocalizedName();
			}
			$templateMgr->assign('journals', $journals);
		}

		return $templateMgr->fetchJson('dashboard/tasks.tpl');
	}

	/**
	 * View submissions tab
	 * @param $args array
	 * @param $request PKPRequest
	 */
	function submissions($args, $request) {
		$templateMgr = TemplateManager::getManager($request);
		$this->setupTemplate($request);

		return $templateMgr->fetchJson('dashboard/submissions.tpl');
	}

	/**
	 * View archives tab
	 * @param $args array
	 * @param $request PKPRequest
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
		AppLocale::requireComponents(LOCALE_COMPONENT_APP_MANAGER, LOCALE_COMPONENT_PKP_MANAGER, LOCALE_COMPONENT_APP_SUBMISSION);
		parent::setupTemplate($request);
	}
}

?>
