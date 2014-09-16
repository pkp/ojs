<?php

/**
 * @file plugins/generic/pln/pages/PLNHandler.inc.php
 *
 * Copyright (c) 2013-2014 Simon Fraser University Library
 * Copyright (c) 2003-2014 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class PLNHandler
 * @ingroup plugins_generic_pln
 *
 * @brief Handle PLN requests
 */

import('classes.handler.Handler');

class PLNHandler extends Handler {

	/**
	 * Index handler: redirect to journal page.
	 * @param $args array
	 * @param $request PKPRequest
	 */
	function index($args, &$request) {
		$request->redirect(null, 'index');
	}

	/**
	 * Provide an endpoint for the PLN staging server to retrieve a deposit
	 * @param array $args
	 * @param Request $request
	 */
	function deposits($args, &$request) {
		$journal =& $request->getJournal();
		$plnPlugin =& PluginRegistry::getPlugin('generic', PLN_PLUGIN_NAME);
		$depositDao =& DAORegistry::getDAO('DepositDAO');
		$fileManager = new FileManager();
		
		$depositUuid = (!isset($args[0]) || empty($args[0])) ? null : $args[0];

		// sanitize the input
		if (!preg_match('/^[[:xdigit:]]{8}-[[:xdigit:]]{4}-[[:xdigit:]]{4}-[[:xdigit:]]{4}-[[:xdigit:]]{12}$/',$depositUuid)) return FALSE;
		
		$deposit =& $depositDao->getDepositByUUID($journal->getId(),$depositUuid);
		
		if (!$deposit) return FALSE;
		
		$depositPackage = new DepositPackage($deposit);
		$depositBag = $depositPackage->getPackageFilePath();
		
		if (!$fileManager->fileExists($depositBag)) return FALSE;
				
		//TODO: Additional check here for journal UUID in HTTP header from staging server
		
		return $fileManager->downloadFile($depositBag, mime_content_type($depositBag), TRUE);
		
	}

	/**
	 * Display status of deposit(s)
	 * @param array $args
	 * @param Request $request
	 */
	function status($args=array(), &$request) {
		$journal =& $request->getJournal();
		$plnPlugin =& PluginRegistry::getPlugin('generic', PLN_PLUGIN_NAME);
		$templateMgr =& TemplateManager::getManager();
		$templateMgr->assign('pageHierarchy', array(array($router->url($request, null, 'about'), 'about.aboutTheJournal')));
		$templateMgr->display($plnPlugin->getTemplatePath() . DIRECTORY_SEPARATOR . 'status.tpl');
	}
}

?>