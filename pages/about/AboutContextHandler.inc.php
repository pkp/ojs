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

		import('lib.pkp.classes.security.authorization.ContextRequiredPolicy');
		$this->addPolicy(new ContextRequiredPolicy($request));

		return parent::authorize($request, $args, $roleAssignments);
	}

	/**
	 * Display about page.
	 * @param $args array
	 * @param $request PKPRequest
	 */
	function index($args, $request) {
		$settingsDao = DAORegistry::getDAO('JournalSettingsDAO');
		$context = $request->getContext();

		$this->setupTemplate($request);
		$templateMgr = TemplateManager::getManager($request);
		$contextSettings = $settingsDao->getSettings($context->getId());
		$templateMgr->assign('contextSettings', $contextSettings);

		// Contact details
		$contactName = $contextSettings['contactName'];
		$contactTitle = $context->getLocalizedSetting('contactTitle');
		$contactAffiliation = $context->getLocalizedSetting('contactAffiliation');
		$contactMailingAddress = $context->getLocalizedSetting('contactMailingAddress');
		$contactPhone = $contextSettings['contactPhone'];
		$contactEmail = $contextSettings['contactEmail'];
		$supportName = $contextSettings['supportName'];
		$supportPhone = $contextSettings['supportPhone'];
		$supportEmail = $contextSettings['supportEmail'];

		// Whether or not contact details should be displayed
		if ($contactName || $contactTitle || $contactAffiliation || $contactMailingAddress ||
			$contactPhone || $contactEmail ) {
			$templateMgr->assign('showContact', true);
		}

		// Whether or not to show contact details for a support tech
		if ($supportName || $supportPhone || $supportEmail ) {
			$templateMgr->assign('showSupportContact', true);
		}

		$templateMgr->assign('mailingAddress', $contextSettings['mailingAddress']);
		$templateMgr->assign('contactName', $contactName);
		$templateMgr->assign('contactTitle', $contactTitle);
		$templateMgr->assign('contactAffiliation', $contactAffiliation);
		$templateMgr->assign('contactMailingAddress', $contactMailingAddress);
		$templateMgr->assign('contactPhone', $contactPhone);
		$templateMgr->assign('contactEmail', $contactEmail);
		$templateMgr->assign('supportName', $supportName);
		$templateMgr->assign('supportPhone', $supportPhone);
		$templateMgr->assign('supportEmail', $supportEmail);

		// Sponsorship details
		$templateMgr->assign(array(
			'contributorNote' => $context->getLocalizedSetting('contributorNote'),
			'contributors' => $context->getSetting('contributors'),
			'sponsorNote' => $context->getLocalizedSetting('sponsorNote'),
			'sponsors' => $context->getSetting('sponsors'),
		));

		$templateMgr->display('frontend/pages/about.tpl');
	}

	/**
	 * Display editorialTeam page.
	 * @param $args array
	 * @param $request PKPRequest
	 */
	function editorialTeam($args, $request) {
		$this->setupTemplate($request);
		$templateMgr = TemplateManager::getManager($request);
		$templateMgr->display('frontend/pages/editorialTeam.tpl');
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
		$templateMgr->display('frontend/pages/submissions.tpl');
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
