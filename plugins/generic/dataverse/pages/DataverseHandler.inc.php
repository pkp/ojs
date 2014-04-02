<?php

/**
 * @file plugins/generic/dataverse/pages/DataverseHandler.inc.php
 *
 * Copyright (c) 2013-2014 Simon Fraser University Library
 * Copyright (c) 2003-2014 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class DataverseHandler
 * @ingroup plugins_generic_dataverse
 *
 * @brief Handle Dataverse requests
 */

import('classes.handler.Handler');

class DataverseHandler extends Handler {

	/**
	 * Index handler: redirect to journal page.
	 */
  function index($args, &$request) {
    $request->redirect(null, 'index');
	}
  
  /**
   * Display data availability policy
   * @param array $args
   * @param Request $request
   */
  function dataAvailabilityPolicy($args, &$request) {
    $journal =& $request->getJournal();
    $dataversePlugin =& PluginRegistry::getPlugin('generic', DATAVERSE_PLUGIN_NAME);
    $templateMgr =& TemplateManager::getManager();
		$templateMgr->assign('pageHierarchy', array(array(Request::url(null, 'about'),'about.aboutTheJournal')));
    $templateMgr->assign('dataAvailabilityPolicy', $dataversePlugin->getSetting($journal->getId(), 'dataAvailability'));
		$templateMgr->display($dataversePlugin->getTemplatePath() .'/dataAvailabilityPolicy.tpl');
  }

  /**
   * Display terms of use of Dataverse configured for journal
   * @param array $args
   * @param Request $request
   */
  function termsOfUse($args, &$request) {
    $journal =& $request->getJournal();
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
    
	/**
	 * Set up common template variables.
	 * @param $subclass boolean set to true if caller is below this handler in the hierarchy
	 */
	function setupTemplate($subclass = false) {
		$templateMgr =& TemplateManager::getManager();
		if ($subclass) {
			$templateMgr->append(
				'pageHierarchy',
				array(
					Request::url(null, 'dataverse'), 
					AppLocale::Translate('plugins.generic.dataverse.displayName'),
					true
				)
			);
		}
	}
}

?>
