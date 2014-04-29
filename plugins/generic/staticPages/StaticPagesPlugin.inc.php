<?php

/**
 * @file plugins/generic/staticPages/StaticPagesPlugin.inc.php
 *
 * Copyright (c) 2013-2014 Simon Fraser University Library
 * Copyright (c) 2003-2014 John Willinsky
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
		$application =& PKPApplication::getApplication();
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
				if (checkPhpVersion('5.0.0')) { // WARNING: see http://pkp.sfu.ca/wiki/index.php/Information_for_Developers#Use_of_.24this_in_the_constructor
					$staticPagesDao = new StaticPagesDAO($this->getName());
				} else {
					$staticPagesDao =& new StaticPagesDAO($this->getName());
				}
				$returner =& DAORegistry::registerDAO('StaticPagesDAO', $staticPagesDao);

				HookRegistry::register('LoadHandler', array(&$this, 'callbackHandleContent'));
			}
			return true;
		}
		return false;
	}

	/**
	 * Declare the handler function to process the actual page PATH
	 */
	function callbackHandleContent($hookName, $args) {
		$templateMgr =& TemplateManager::getManager();

		$page =& $args[0];
		$op =& $args[1];

		if ( $page == 'pages' ) {
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
		$verbs = array();
		if ($this->getEnabled()) {
			if ($this->isTinyMCEInstalled()) {
				$verbs[] = array('settings', __('plugins.generic.staticPages.editAddContent'));
			}
		}
		return parent::getManagementVerbs($verbs);
	}

	/**
	 * Perform management functions
	 */
	function manage($verb, $args, &$message, &$messageParams) {
		if (!parent::manage($verb, $args, $message, $messageParams)) return false;

		$templateMgr =& TemplateManager::getManager();
		$templateMgr->register_function('plugin_url', array(&$this, 'smartyPluginUrl'));
		$templateMgr->assign('pagesPath', Request::url(null, 'pages', 'view', 'REPLACEME'));

		$pageCrumbs = array(
			array(
				Request::url(null, 'user'),
				'navigation.user'
			),
			array(
				Request::url(null, 'manager'),
				'user.role.manager'
			)
		);

		switch ($verb) {
			case 'settings':
				$journal =& Request::getJournal();

				$this->import('StaticPagesSettingsForm');
				$form = new StaticPagesSettingsForm($this, $journal->getId());

				$templateMgr->assign('pageHierarchy', $pageCrumbs);
				$form->initData();
				$form->display();
				return true;
			case 'edit':
			case 'add':
				$journal =& Request::getJournal();

				$this->import('StaticPagesEditForm');

				$staticPageId = isset($args[0])?(int)$args[0]:null;
				$form = new StaticPagesEditForm($this, $journal->getId(), $staticPageId);

				if ($form->isLocaleResubmit()) {
					$form->readInputData();
					$form->addTinyMCE();
				} else {
					$form->initData();
				}

				$pageCrumbs[] = array(
					Request::url(null, 'manager', 'plugin', array('generic', $this->getName(), 'settings')),
					$this->getDisplayName(),
					true
				);
				$templateMgr->assign('pageHierarchy', $pageCrumbs);
				$form->display();
				return true;
			case 'save':
				$journal =& Request::getJournal();

				$this->import('StaticPagesEditForm');

				$staticPageId = isset($args[0])?(int)$args[0]:null;
				$form = new StaticPagesEditForm($this, $journal->getId(), $staticPageId);

				if (Request::getUserVar('edit')) {
					$form->readInputData();
					if ($form->validate()) {
						$form->save();
						$templateMgr->assign(array(
							'currentUrl' => Request::url(null, null, null, array($this->getCategory(), $this->getName(), 'settings')),
							'pageTitle' => 'plugins.generic.staticPages.displayName',
							'pageHierarchy' => $pageCrumbs,
							'message' => 'plugins.generic.staticPages.pageSaved',
							'backLink' => Request::url(null, null, null, array($this->getCategory(), $this->getName(), 'settings')),
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
				Request::redirect(null, null, 'manager', 'plugins');
				return false;
			case 'delete':
				$journal =& Request::getJournal();
				$staticPageId = isset($args[0])?(int) $args[0]:null;
				$staticPagesDao =& DAORegistry::getDAO('StaticPagesDAO');
				$staticPagesDao->deleteStaticPageById($staticPageId);

				$templateMgr->assign(array(
					'currentUrl' => Request::url(null, null, null, array($this->getCategory(), $this->getName(), 'settings')),
					'pageTitle' => 'plugins.generic.staticPages.displayName',
					'message' => 'plugins.generic.staticPages.pageDeleted',
					'backLink' => Request::url(null, null, null, array($this->getCategory(), $this->getName(), 'settings')),
					'backLinkLabel' => 'common.continue'
				));

				$templateMgr->assign('pageHierarchy', $pageCrumbs);
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
