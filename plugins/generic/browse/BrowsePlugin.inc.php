<?php

/**
 * @file plugins/generic/browse/BrowsePlugin.inc.php
 *
 * Copyright (c) 2014-2018 Simon Fraser University
 * Copyright (c) 2003-2018 John Willinsky
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
	 * @copydoc Plugin::register()
	 */
	function register($category, $path, $mainContextId = null) {
		if (parent::register($category, $path, $mainContextId)) {
			if ($this->getEnabled($mainContextId)) {
				// Add new navigation items in the navigation block plugin
				HookRegistry::register('Plugins::Blocks::Navigation::BrowseBy',array($this, 'addNavigationItem'));
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
	 * Get the handler path for this plugin.
	 */
	function getHandlerPath() {
		return $this->getPluginPath() . '/pages/';
	}

	/**
	 * Add additional navigation items.
	 */
	function addNavigationItem($hookName, $params) {
		$smarty = $params[1];
		$output =& $params[2];

		$journal = $smarty->getTemplateVars('currentJournal');

		$templateMgr = TemplateManager::getManager(Application::getRequest());
		if ($this->getSetting($journal->getId(), 'enableBrowseBySections')) {
			$output .= '<li><a href="' . $templateMgr->smartyUrl(array('page' => 'browseSearch', 'op'=>'sections'), $smarty) . '">' . $templateMgr->smartyTranslate(array('key'=>'plugins.generic.browse.search.sections'), $smarty) . '</a></li>';
		}
		if ($this->getSetting($journal->getId(), 'enableBrowseByIdentifyTypes')) {
			$output .= '<li><a href="' . $templateMgr->smartyUrl(array('page' => 'browseSearch', 'op'=>'identifyTypes'), $smarty).'">' . $templateMgr->smartyTranslate(array('key'=>'plugins.generic.browse.search.identifyTypes'), $smarty) . '</a></li>';
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
					AppLocale::requireComponents(LOCALE_COMPONENT_APP_COMMON);
					$handlerFile =& $params[2];
					$handlerFile = $this->getHandlerPath() . 'BrowseHandler.inc.php';
				}
			}
		}
	}

 	/**
	 * @see Plugin::manage()
	 */
	function manage($args, $request) {
		if (!parent::manage($args, $request)) return false;

		switch (array_shift($args)) {
			case 'settings':
				$templateMgr = TemplateManager::getManager($request);
				$templateMgr->register_function('plugin_url', array($this, 'smartyPluginUrl'));
				$journal = $request->getJournal();

				$this->import('classes.form.BrowseSettingsForm');
				$form = new BrowseSettingsForm($this, $journal->getId());

				if ($request->getUserVar('save')) {
					$form->readInputData();
					if ($form->validate()) {
						$form->execute();
						$request->redirect(null, 'manager', 'plugins');
						return false;
					} else {
						$form->display();
					}
				} else {
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
