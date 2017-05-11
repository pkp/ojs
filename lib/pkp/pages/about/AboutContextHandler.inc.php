<?php

/**
 * @file pages/about/AboutContextHandler.inc.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2003-2017 John Willinsky
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
	function __construct() {
		parent::__construct();
		AppLocale::requireComponents(LOCALE_COMPONENT_APP_COMMON);
	}

	/**
	 * @see PKPHandler::authorize()
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
		$templateMgr = TemplateManager::getManager($request);
		$this->setupTemplate($request);
		$templateMgr->display('frontend/pages/about.tpl');
	}

	/**
	 * Display editorialTeam page.
	 * @param $args array
	 * @param $request PKPRequest
	 */
	function editorialTeam($args, $request) {
		$templateMgr = TemplateManager::getManager($request);
		$this->setupTemplate($request);
		$templateMgr->display('frontend/pages/editorialTeam.tpl');
	}

	/**
	 * Display submissions page.
	 * @param $args array
	 * @param $request PKPRequest
	 */
	function submissions($args, $request) {
		$templateMgr = TemplateManager::getManager($request);
		$this->setupTemplate($request);

		$context = $request->getContext();
		$checklist = $context->getLocalizedSetting('submissionChecklist');
		if (!empty($checklist)) {
			ksort($checklist);
			reset($checklist);
		}

		$templateMgr->assign( 'submissionChecklist', $context->getLocalizedSetting('submissionChecklist') );

		$templateMgr->display('frontend/pages/submissions.tpl');
	}

	/**
	 * Display contact page.
	 * @param $args array
	 * @param $request PKPRequest
	 */
	function contact($args, $request) {
		$templateMgr = TemplateManager::getManager($request);
		$this->setupTemplate($request);
		$context = $request->getContext();
		$templateMgr->assign(array(
			'mailingAddress'     => $context->getSetting('mailingAddress'),
			'contactPhone'       => $context->getSetting('contactPhone'),
			'contactEmail'       => $context->getSetting('contactEmail'),
			'contactName'        => $context->getSetting('contactName'),
			'supportName'        => $context->getSetting('supportName'),
			'supportPhone'       => $context->getSetting('supportPhone'),
			'supportEmail'       => $context->getSetting('supportEmail'),
			'contactTitle'       => $context->getLocalizedSetting('contactTitle'),
			'contactAffiliation' => $context->getLocalizedSetting('contactAffiliation'),
		));
		$templateMgr->display('frontend/pages/contact.tpl');
	}
}

?>
