<?php

/**
 * @file plugins/generic/browse/BrowsePlugin.inc.php
 *
 * Copyright (c) 2013-2015 Simon Fraser University Library
 * Copyright (c) 2003-2015 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class BrowsePlugin
 * @ingroup plugins_generic_browse
 *
 * @brief Browse by additional objects plugin class.
 */


import('lib.pkp.classes.plugins.GenericPlugin');

class BrowsePlugin extends GenericPlugin {

	/**
	 * Called as a plugin is registered to the registry
	 * @param $category String Name of category plugin was registered to
	 * @return boolean True if plugin initialized successfully; if false,
	 * 	the plugin will not be registered.
	 */
	function register($category, $path) {
		if (parent::register($category, $path)) {
			if ($this->getEnabled()) {
				// Add new navigation items in the navigation block plugin
				HookRegistry::register('Plugins::Blocks::Navigation::BrowseBy',array(&$this, 'addNavigationItem'));
				// Handler for browse plugin pages
				HookRegistry::register('LoadHandler', array($this, 'setupBrowseHandler'));
			}
			return true;
		}
		return false;
	}

	/**
	 * Get the display name of this plugin.
	 * @return string
	 */
	function getDisplayName() {
		return __('plugins.generic.browse.displayName');
	}

	/**
	 * Get the description of this plugin.
	 * @return string
	 */
	function getDescription() {
		return __('plugins.generic.browse.description');
	}

	/**
	 * Get the template path for this plugin.
	 */
	function getTemplatePath() {
		return parent::getTemplatePath() . 'templates/';
	}

	/**
	 * Get the handler path for this plugin.
	 */
	function getHandlerPath() {
		return $this->getPluginPath() . '/pages/';
	}

	/**
	 * Add additional navigation items.
	 */
	function addNavigationItem($hookName, $params) {
		$smarty =& $params[1];
		$output =& $params[2];

		$journal =& $smarty->get_template_vars('currentJournal');

		$templateMgr =& TemplateManager::getManager();
		if ($this->getSetting($journal->getId(), 'enableBrowseBySections')) {
			$output .= '<li id="linkBrowseBySections"><a href="' . $templateMgr->smartyUrl(array('page' => 'browseSearch', 'op'=>'sections'), $smarty) . '">' . $templateMgr->smartyTranslate(array('key'=>'plugins.generic.browse.search.sections'), $smarty) . '</a></li>';
		}
		if ($this->getSetting($journal->getId(), 'enableBrowseByIdentifyTypes')) {
			$output .= '<li id="linkBrowseByIdentifyTypes"><a href="' . $templateMgr->smartyUrl(array('page' => 'browseSearch', 'op'=>'identifyTypes'), $smarty).'">' . $templateMgr->smartyTranslate(array('key'=>'plugins.generic.browse.search.identifyTypes'), $smarty) . '</a></li>';
		}
		return false;
	}

	/**
	 * Enable editor pixel tags management.
	 */
	function setupBrowseHandler($hookName, $params) {
		$page =& $params[0];

		if ($page == 'browseSearch') {
			$op =& $params[1];

			if ($op) {
				$editorPages = array(
					'sections',
					'identifyTypes'
				);

				if (in_array($op, $editorPages)) {
					define('HANDLER_CLASS', 'BrowseHandler');
					define('BROWSE_PLUGIN_NAME', $this->getName());
					AppLocale::requireComponents(LOCALE_COMPONENT_APPLICATION_COMMON);
					$handlerFile =& $params[2];
					$handlerFile = $this->getHandlerPath() . 'BrowseHandler.inc.php';
				}
			}
		}
	}

	/**
	 * Set the breadcrumbs, given the plugin's tree of items to append.
	 * @param $subclass boolean
	 */
	function setBreadcrumbs($isSubclass = false) {
		$templateMgr =& TemplateManager::getManager();
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
		if ($isSubclass) $pageCrumbs[] = array(
			Request::url(null, 'manager', 'plugins'),
			'manager.plugins'
		);

		$templateMgr->assign('pageHierarchy', $pageCrumbs);
	}

	/**
	 * Display verbs for the management interface.
	 */
	function getManagementVerbs() {
		$verbs = array();
		if ($this->getEnabled()) {
			$verbs[] = array('settings', __('plugins.generic.browse.manager.settings'));
		}
		return parent::getManagementVerbs($verbs);
	}

	/**
	 * Execute a management verb on this plugin
	 * @param $verb string
	 * @param $args array
	 * @param $message string Location for the plugin to put a result msg
	 * @param $messageParams array
	 * @return boolean
	 */
	function manage($verb, $args, &$message, &$messageParams) {
		if (!parent::manage($verb, $args, $message, $messageParams)) return false;

		switch ($verb) {
			case 'settings':
				$templateMgr =& TemplateManager::getManager();
				$templateMgr->register_function('plugin_url', array(&$this, 'smartyPluginUrl'));
				$journal =& Request::getJournal();

				$this->import('classes.form.BrowseSettingsForm');
				$form = new BrowseSettingsForm($this, $journal->getId());

				if (Request::getUserVar('save')) {
					$form->readInputData();
					if ($form->validate()) {
						$form->execute();
						Request::redirect(null, 'manager', 'plugins');
						return false;
					} else {
						$this->setBreadCrumbs(true);
						$form->display();
					}
				} else {
					$this->setBreadCrumbs(true);
					$form->initData();
					$form->display();
				}
				return true;
			default:
				// Unknown management verb
				assert(false);
				return false;
		}
	}
}

?>
