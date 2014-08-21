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
	 */
	function index($args = array(), &$request) {
		
		$request->redirect(null, 'index');
	}
  
	/**
	* Provide an endpoint for the PLN staging server to retrieve a deposit
	* @param array $args
	* @param Request $request
	*/
	function deposits($args=array(), &$request) {
		
		$journal =& $request->getJournal();
		$pkp_plugin =& PluginRegistry::getPlugin('generic', PLN_PLUGIN_NAME);
		$deposit_dao =& DAORegistry::getDAO('DepositDAO');
		$file_manager = new FileManager();
		
		$deposit_uuid = !isset($args) || empty($args) ? null : $args[0];

		if (!$deposit_uuid) return FALSE;
		
		$deposit =& $deposit_dao->getDepositByUUID($journal->getId(),$deposit_uuid);
		
		if (!$deposit) return FALSE;
		
		$deposit_bag = $deposit->getPackageFilePath();
		
		if (!file_exists($deposit_bag)) return FALSE;
				
		//TODO: Additional check here for journal UUID in HTTP header from staging server
		
		return $file_manager->downloadFile($deposit_bag, mime_content_type($deposit_bag), TRUE);
		
	}
    
    /**
	 * Display status of deposit(s)
	 * @param array $args
	 * @param Request $request
	 */
	function status($args=array(), &$request) {
		
		$journal =& $request->getJournal();
		$pkp_plugin =& PluginRegistry::getPlugin('generic', PLN_PLUGIN_NAME);
		$templateMgr =& TemplateManager::getManager();
		$templateMgr->assign('pageHierarchy', array(array($router->url($request, null, 'about'), 'about.aboutTheJournal')));
		$templateMgr->display($plugin->getTemplatePath() . DIRECTORY_SEPARATOR . 'status.tpl');
	}
    
}

?>