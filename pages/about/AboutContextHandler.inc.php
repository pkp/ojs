<?php

/**
 * @file pages/about/AboutContextHandler.inc.php
 *
 * Copyright (c) 2014-2015 Simon Fraser University Library
 * Copyright (c) 2003-2015 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class AboutContextHandler
 * @ingroup pages_about
 *
 * @brief Handle requests for context-level about functions.
 */

import('classes.handler.Handler');

class AboutContextHandler extends Handler {
	/**
	 * Constructor
	 */
	function AboutContextHandler() {
		parent::Handler();
		AppLocale::requireComponents(LOCALE_COMPONENT_APP_COMMON);
	}

	/**
	 * @copydoc PKPHandler::authorize()
	 */
	function authorize($request, &$args, $roleAssignments) {
		$context = $request->getContext();
		if (!$context || !$context->getSetting('restrictSiteAccess')) {
			$templateMgr = TemplateManager::getManager($request);
			$templateMgr->setCacheability(CACHEABILITY_PUBLIC);
		}

		import('lib.pkp.classes.security.authorization.UserRolesRequiredPolicy');
		$this->addPolicy(new UserRolesRequiredPolicy($request));

		import('lib.pkp.classes.security.authorization.ContextRequiredPolicy');
		$this->addPolicy(new ContextRequiredPolicy($request));

		return parent::authorize($request, $args, $roleAssignments);
	}

	/**
	 * Display contact page.
	 * @param $args array
	 * @param $request PKPRequest
	 */
	function contact($args, $request) {
		$settingsDao = DAORegistry::getDAO('JournalSettingsDAO');
		$context = $request->getContext();

		$this->setupTemplate($request);
		$templateMgr = TemplateManager::getManager($request);
		$templateMgr->assign('contextSettings', $settingsDao->getSettings($context->getId()));
		$templateMgr->display('about/contact.tpl');
	}

	/**
	 * Display description page.
	 * @param $args array
	 * @param $request PKPRequest
	 */
	function description($args, $request) {
		$this->setupTemplate($request);
		$templateMgr = TemplateManager::getManager($request);
		$templateMgr->display('about/description.tpl');
	}

	/**
	 * Display sponsorship page.
	 * @param $args array
	 * @param $request PKPRequest
	 */
	function sponsorship($args, $request) {
		$this->setupTemplate($request);

		$context = $request->getContext();
		$templateMgr = TemplateManager::getManager($request);
		$templateMgr->assign(array(
			'contributorNote' => $context->getLocalizedSetting('contributorNote'),
			'contributors' => $context->getSetting('contributors'),
			'sponsorNote' => $context->getLocalizedSetting('sponsorNote'),
			'sponsors' => $context->getSetting('sponsors'),
		));
		$templateMgr->display('about/sponsorship.tpl');
	}

	/**
	 * Display editorialTeam page.
	 * @param $args array
	 * @param $request PKPRequest
	 */
	function editorialTeam($args, $request) {
		$this->setupTemplate($request);
		$templateMgr = TemplateManager::getManager($request);
		$templateMgr->display('about/editorialTeam.tpl');
	}

	/**
	 * Display editorialPolicies page.
	 * @param $args array
	 * @param $request PKPRequest
	 */
	function editorialPolicies($args, $request) {
		$this->setupTemplate($request);
		$templateMgr = TemplateManager::getManager($request);
		$templateMgr->display('about/editorialPolicies.tpl');
	}

	/**
	 * Display submissions page.
	 * @param $args array
	 * @param $request PKPRequest
	 */
	function submissions($args, $request) {
		$this->setupTemplate($request);

		$context = $request->getContext();
		$templateMgr = TemplateManager::getManager($request);
		$submissionChecklist = $context->getLocalizedSetting('submissionChecklist');
		if (!empty($submissionChecklist)) {
			ksort($submissionChecklist);
			reset($submissionChecklist);
		}
		$templateMgr->assign('submissionChecklist', $submissionChecklist);
		$templateMgr->display('about/submissions.tpl');
	}

	/**
	 * @copydoc PKPHandler::setupTemplate()
	 */
	function setupTemplate($request) {
		parent::setupTemplate($request);
		$templateMgr = TemplateManager::getManager($request);
		$templateMgr->assign('userRoles', $this->getAuthorizedContextObject(ASSOC_TYPE_USER_ROLES));
	}
}

?>
