<?php

/**
 * @file pages/PLNHandler.inc.php
 *
 * Copyright (c) 2013-2020 Simon Fraser University
 * Copyright (c) 2003-2020 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file LICENSE.
 *
 * @class PLNHandler
 * @brief Handle PLN requests
 */

import('classes.handler.Handler');
class PLNHandler extends Handler {
	/**
	 * Index handler: redirect to journal page.
	 * @param $args array
	 * @param $request PKPRequest
	 */
	public function index($args, $request) {
		$request->redirect(null, 'index');
	}

	/**
	 * @copydoc PKPHandler::authorize()
	 */
	public function authorize($request, &$args, $roleAssignments) {
		import('lib.pkp.classes.security.authorization.ContextRequiredPolicy');
		$this->addPolicy(new ContextRequiredPolicy($request));

		return parent::authorize($request, $args, $roleAssignments);
	}

	/**
	 * Provide an endpoint for the PLN staging server to retrieve a deposit
	 * @param array $args
	 * @param Request $request
	 *
	 * @return bool
	 */
	public function deposits($args, $request) {
		$journal = $request->getJournal();
		$depositDao = DAORegistry::getDAO('DepositDAO');
		$fileManager = new FileManager();
		$dispatcher = $request->getDispatcher();

		$depositUuid = (!isset($args[0]) || empty($args[0])) ? null : $args[0];

		// sanitize the input
		if (!preg_match('/^[[:xdigit:]]{8}-[[:xdigit:]]{4}-[[:xdigit:]]{4}-[[:xdigit:]]{4}-[[:xdigit:]]{12}$/',$depositUuid)) {
			error_log(__('plugins.generic.pln.error.handler.uuid.invalid'));
			$dispatcher->handle404();
			return false;
		}

		$deposit = $depositDao->getByUUID($journal->getId(), $depositUuid);

		if (!$deposit) {
			error_log(__('plugins.generic.pln.error.handler.uuid.notfound'));
			$dispatcher->handle404();
			return false;
		}

		$depositPackage = new DepositPackage($deposit, null);
		$depositBag = $depositPackage->getPackageFilePath();

		if (!$fileManager->fileExists($depositBag)) {
			error_log('plugins.generic.pln.error.handler.file.notfound');
			$dispatcher->handle404();
			return false;
		}

		return $fileManager->downloadByPath($depositBag, PKPString::mime_content_type($depositBag), true);
	}

	/**
	 * Display status of deposit(s)
	 * @param array $args
	 * @param Request $request
	 */
	public function status($args, $request) {
		$router = $request->getRouter();
		$plnPlugin = PluginRegistry::getPlugin('generic', PLN_PLUGIN_NAME);
		$templateMgr = TemplateManager::getManager();
		$templateMgr->assign('pageHierarchy', array(array($router->url($request, null, 'about'), 'about.aboutTheJournal')));
		$templateMgr->display($plnPlugin->getTemplatePath() . DIRECTORY_SEPARATOR . 'status.tpl');
	}

	//
	// Protected helper methods
	//
	/**
	 * Get the Usage Stats plugin object
	 * @return PLNPlugin
	 */
	protected function _getPlugin() {
		return PluginRegistry::getPlugin('generic', PLN_PLUGIN_NAME);
	}
}
