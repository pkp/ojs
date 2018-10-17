<?php

/**
 * @file plugins/generic/backup/BackupPlugin.inc.php
 *
 * Copyright (c) 2014-2018 Simon Fraser University
 * Copyright (c) 2000-2018 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class BackupPlugin
 * @ingroup plugins_generic_backup
 *
 * @brief Plugin to allow generation of a backup extract
 */

import('lib.pkp.classes.plugins.GenericPlugin');

class BackupPlugin extends GenericPlugin {
	/**
	 * @copydoc Plugin::register()
	 */
	function register($category, $path, $mainContextId = null) {
		if (parent::register($category, $path, $mainContextId)) {
			$this->addLocaleData();
			if ($this->getEnabled($mainContextId) && Validation::isSiteAdmin()) {
				HookRegistry::register('Templates::Admin::Index::AdminFunctions',array($this, 'addLink'));
				HookRegistry::register ('LoadHandler', array($this, 'handleRequest'));
			}
			return true;
		}
		return false;
	}

	/**
	 * Designate this plugin as a site plugin
	 */
	function isSitePlugin() {
		return true;
	}

	/**
	 * Hook callback function for TemplateManager::display
	 * @param $hookName string
	 * @param $args array
	 * @return boolean
	 */
	function addLink($hookName, $args) {
		$params =& $args[0];
		$smarty =& $args[1];
		$output =& $args[2];
		$request = Application::getRequest();
		$output .= '<li><a href="' . $request->url(null, 'backup') . '">' . __('plugins.generic.backup.link') . '</a></li>';
		return false;
	}

	/**
	 * Hook callback: handle a request for the backup plugin.
	 * @param $hookName string
	 * @param $args array
	 * @return boolean false (hook processing conventions)
	 */
	function handleRequest($hookName, $args) {
		$page =& $args[0];
		$op =& $args[1];
		$sourceFile =& $args[2];
		$request = Application::getRequest();

		if ($page !== 'backup') return false;
		// We've already verified that this is a site admin through
		// conditional hook registration.

		AppLocale::requireComponents(LOCALE_COMPONENT_PKP_ADMIN, LOCALE_COMPONENT_APP_COMMON);
		$returnValue = 0;
		switch ($op) {
			case 'index':
				$templateMgr = TemplateManager::getManager($request);
				$templateMgr->assign('isDumpConfigured', Config::getVar('cli', 'dump')!='');
				$templateMgr->assign('isTarConfigured', Config::getVar('cli', 'tar')!='');
				$templateMgr->display($this->getTemplateResource('index.tpl'));
				exit();
			case 'db':
				$dumpTool = Config::getVar('cli', 'dump');
				header('Content-Description: File Transfer');
				header('Content-Disposition: attachment; filename=db-' . strftime('%Y-%m-%d') . '.sql');
				header('Content-Type: text/plain');
				header('Content-Transfer-Encoding: binary');

				passthru(sprintf(
					$dumpTool,
					escapeshellarg(Config::getVar('database', 'host')),
					escapeshellarg(Config::getVar('database', 'username')),
					escapeshellarg(Config::getVar('database', 'password')),
					escapeshellarg(Config::getVar('database', 'name'))
				), $returnValue);
				if ($returnValue !== 0) $request->redirect(null, null, 'failure');
				exit();
			case 'files':
				$tarTool = Config::getVar('cli', 'tar');
				header('Content-Description: File Transfer');
				header('Content-Disposition: attachment; filename=files-' . strftime('%Y-%m-%d') . '.tar.gz');
				header('Content-Type: text/plain');
				header('Content-Transfer-Encoding: binary');
				passthru($tarTool . ' -c -z ' . escapeshellarg(Config::getVar('files', 'files_dir')), $returnValue);
				if ($returnValue !== 0) $request->redirect(null, null, 'failure');
				exit();
			case 'code':
				$tarTool = Config::getVar('cli', 'tar');
				header('Content-Description: File Transfer');
				header('Content-Disposition: attachment; filename=code-' . strftime('%Y-%m-%d') . '.tar.gz');
				header('Content-Type: text/plain');
				header('Content-Transfer-Encoding: binary');
				passthru($tarTool . ' -c -z ' . escapeshellarg(dirname(dirname(dirname(dirname(__FILE__))))), $returnValue);
				if ($returnValue !== 0) $request->redirect(null, null, 'failure');
				exit();
			case 'failure':
				$templateMgr = TemplateManager::getManager($request);
				$templateMgr->assign('message', 'plugins.generic.backup.failure');
				$templateMgr->assign('backLink', $request->url(null, null, 'backup'));
				$templateMgr->assign('backLinkLabel', 'plugins.generic.backup.link');
				$templateMgr->display('frontend/pages/message.tpl');
				exit();
		}
		return false;
	}

	/**
	 * Get the symbolic name of this plugin
	 * @return string
	 */
	function getName() {
		return 'BackupPlugin';
	}

	/**
	 * Get the display name of this plugin
	 * @return string
	 */
	function getDisplayName() {
		return __('plugins.generic.backup.name');
	}

	/**
	 * Get the description of this plugin
	 * @return string
	 */
	function getDescription() {
		return __('plugins.generic.backup.description');
	}
}


