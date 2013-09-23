<?php

/**
 * @file plugins/generic/staticPages/StaticPagesPlugin.inc.php
 *
 * Copyright (c) 2003-2013 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package plugins.generic.staticPages
 * @class StaticPagesPlugin
 *
 * StaticPagesPlugin class
 *
 */

import('lib.pkp.classes.plugins.GenericPlugin');

class StaticPagesPlugin extends GenericPlugin {
	function getDisplayName() {
		return __('plugins.generic.staticPages.displayName');
	}

	function getDescription() {
		$description = __('plugins.generic.staticPages.description');
		if ( !$this->isTinyMCEInstalled() )
			$description .= "<br />".__('plugins.generic.staticPages.requirement.tinymce');
		return $description;
	}

	function isTinyMCEInstalled() {
		// If the thesis plugin isn't enabled, don't do anything.
		$application = PKPApplication::getApplication();
		$products =& $application->getEnabledProducts('plugins.generic');
		return (isset($products['tinymce']));
	}

	/**
	 * Register the plugin, attaching to hooks as necessary.
	 * @param $category string
	 * @param $path string
	 * @return boolean
	 */
	function register($category, $path) {
		if (parent::register($category, $path)) {
			if ($this->getEnabled()) {
				$this->import('StaticPagesDAO');
				$staticPagesDao = new StaticPagesDAO($this->getName());
				DAORegistry::registerDAO('StaticPagesDAO', $staticPagesDao);

				HookRegistry::register('LoadHandler', array($this, 'callbackHandleContent'));
			}
			return true;
		}
		return false;
	}

	/**
	 * Declare the handler function to process the actual page PATH
	 */
	function callbackHandleContent($hookName, $args) {
		$request = $this->getRequest();
		$templateMgr = TemplateManager::getManager($request);

		$page = $args[0];
		$op = $args[1];

		if ($page == 'pages' && in_array($op, array('index', 'view'))) {
			define('STATIC_PAGES_PLUGIN_NAME', $this->getName()); // Kludge
			define('HANDLER_CLASS', 'StaticPagesHandler');
			$this->import('StaticPagesHandler');
			return true;
		}
		return false;
	}

	/**
	 * Display verbs for the management interface.
	 */
	function getManagementVerbs() {
		$verbs = parent::getManagementVerbs();
		if ($this->getEnabled()) {
			if ($this->isTinyMCEInstalled()) {
				$verbs[] = array('settings', __('plugins.generic.staticPages.editAddContent'));
			}
		}
		return $verbs;
	}

 	/**
	 * @see Plugin::manage()
	 */
	function manage($verb, $args, &$message, &$messageParams, &$pluginModalContent = null) {
		if (!parent::manage($verb, $args, $message, $messageParams)) return false;
		$request = $this->getRequest();

		$templateMgr = TemplateManager::getManager($request);
		$templateMgr->register_function('plugin_url', array($this, 'smartyPluginUrl'));
		$templateMgr->assign('pagesPath', $request->url(null, 'pages', 'view', 'REPLACEME'));

		switch ($verb) {
			case 'settings':
				$journal = $request->getJournal();

				$this->import('StaticPagesSettingsForm');
				$form = new StaticPagesSettingsForm($this, $journal->getId());

				$form->initData($request);
				$form->display();
				return true;
			case 'edit':
			case 'add':
				$journal = $request->getJournal();

				$this->import('StaticPagesEditForm');

				$staticPageId = isset($args[0])?(int)$args[0]:null;
				$form = new StaticPagesEditForm($this, $journal->getId(), $staticPageId);

				if ($form->isLocaleResubmit()) {
					$form->readInputData();
					$form->addTinyMCE();
				} else {
					$form->initData();
				}

				$form->display();
				return true;
			case 'save':
				$journal = $request->getJournal();

				$this->import('StaticPagesEditForm');

				$staticPageId = isset($args[0])?(int)$args[0]:null;
				$form = new StaticPagesEditForm($this, $journal->getId(), $staticPageId);

				if ($request->getUserVar('edit')) {
					$form->readInputData();
					if ($form->validate()) {
						$form->save();
						$templateMgr->assign(array(
							'currentUrl' => $request->url(null, null, null, array($this->getCategory(), $this->getName(), 'settings')),
							'pageTitle' => 'plugins.generic.staticPages.displayName',
							'message' => 'plugins.generic.staticPages.pageSaved',
							'backLink' => $request->url(null, null, null, array($this->getCategory(), $this->getName(), 'settings')),
							'backLinkLabel' => 'common.continue'
						));
						$templateMgr->display('common/message.tpl');
						exit;
					} else {
						$form->addTinyMCE();
						$form->display();
						exit;
					}
				}
				$request->redirect(null, null, 'manager', 'plugins');
				return false;
			case 'delete':
				$journal = $request->getJournal();
				$staticPageId = isset($args[0])?(int) $args[0]:null;
				$staticPagesDao = DAORegistry::getDAO('StaticPagesDAO');
				$staticPagesDao->deleteStaticPageById($staticPageId);

				$templateMgr->assign(array(
					'currentUrl' => $request->url(null, null, null, array($this->getCategory(), $this->getName(), 'settings')),
					'pageTitle' => 'plugins.generic.staticPages.displayName',
					'message' => 'plugins.generic.staticPages.pageDeleted',
					'backLink' => $request->url(null, null, null, array($this->getCategory(), $this->getName(), 'settings')),
					'backLinkLabel' => 'common.continue'
				));

				$templateMgr->display('common/message.tpl');
				return true;
			default:
				// Unknown management verb
				assert(false);
				return false;
		}
	}

	/**
	 * Get the filename of the ADODB schema for this plugin.
	 */
	function getInstallSchemaFile() {
		return $this->getPluginPath() . '/' . 'schema.xml';
	}
}

?>
