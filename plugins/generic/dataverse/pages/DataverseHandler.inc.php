<?php

/**
 * @file plugins/generic/dataverse/pages/DataverseHandler.inc.php
 *
 * Copyright (c) 2013-2015 Simon Fraser University Library
 * Copyright (c) 2003-2015 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class DataverseHandler
 * @ingroup plugins_generic_dataverse
 *
 * @brief Handle Dataverse page requests.
 */

import('classes.handler.Handler');

class DataverseHandler extends Handler {

	/**
	 * Index handler: redirect to journal page.
	 * @param $args array
	 * @param $request Request
	 */
	function index($args, &$request) {
		$request->redirect(null, 'index');
	}
	
	/**
	 * Display data availability policy.
	 * @param array $args
	 * @param Request $request
	 */
	function dataAvailabilityPolicy($args, &$request) {
		$router =& $request->getRouter();
		$journal =& $router->getContext($request);
		$dataversePlugin =& PluginRegistry::getPlugin('generic', DATAVERSE_PLUGIN_NAME);
		$templateMgr =& TemplateManager::getManager();
		$templateMgr->assign('pageHierarchy', array(array($router->url($request, null, 'about'), 'about.aboutTheJournal')));
		$templateMgr->assign('dataAvailabilityPolicy', $dataversePlugin->getSetting($journal->getId(), 'dataAvailability'));
		$templateMgr->display($dataversePlugin->getTemplatePath() .'/dataAvailabilityPolicy.tpl');
	}

	/**
	 * Display terms of use for Dataverse configured for journal.
	 * @param array $args
	 * @param Request $request
	 */
	function termsOfUse($args, &$request) {
		$router =& $request->getRouter();
		$journal =& $router->getContext($request);
		$dataversePlugin =& PluginRegistry::getPlugin('generic', DATAVERSE_PLUGIN_NAME);
		$templateMgr =& TemplateManager::getManager();
		
		if ($dataversePlugin->getSetting($journal->getId(), 'fetchTermsOfUse')) {
			// Try fetching terms of use from DV. If not available, use DV terms cached on last fetch
			$termsOfUse = $dataversePlugin->getTermsOfUse();
			$templateMgr->assign('termsOfUse', $termsOfUse ? $termsOfUse : $this->getSetting($journal->getId(), 'dvTermsOfUse'));
		}
		else {
			// Get terms of use configured by JM
			$templateMgr->assign('termsOfUse', $dataversePlugin->getSetting($journal->getId(), 'termsOfUse'));
		}
		$templateMgr->display($dataversePlugin->getTemplatePath() .'/termsOfUse.tpl');
	}
}

?>
