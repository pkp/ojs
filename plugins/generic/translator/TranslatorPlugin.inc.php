<?php

/**
 * @file plugins/generic/translator/TranslatorPlugin.inc.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2003-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class TranslatorPlugin
 * @ingroup plugins_generic_translator
 *
 * @brief This plugin helps with translation maintenance.
 */

import('lib.pkp.classes.plugins.GenericPlugin');

class TranslatorPlugin extends GenericPlugin {
	/**
	 * Register the plugin
	 * @param $category string Plugin category
	 * @param $path string Plugin path
	 * @return boolean True on success
	 */
	function register($category, $path) {
		if (parent::register($category, $path)) {
			if ($this->getEnabled()) {
				// Allow the Translate tab to appear on website settings
				HookRegistry::register('Templates::Management::Settings::website', array($this, 'callbackShowWebsiteSettingsTabs'));

				// Register the components this plugin implements to
				// permit administration of static pages.
				HookRegistry::register('LoadComponentHandler', array($this, 'setupComponentHandlers'));

				// Bring in the TranslatorAction helper class.
				$this->import('TranslatorAction');
			}
			return true;
		}
		return false;
	}

	/**
	 * Extend the website settings tabs to include translation
	 * @param $hookName string The name of the invoked hook
	 * @param $args array Hook parameters
	 * @return boolean Hook handling status
	 */
	function callbackShowWebsiteSettingsTabs($hookName, $args) {
		$output =& $args[2];
		$request = Registry::get('request');
		$dispatcher = $request->getDispatcher();

		// Add a new tab for static pages
		$output .= '<li><a name="translate" href="' . $dispatcher->url($request, ROUTE_COMPONENT, null, 'plugins.generic.translator.controllers.grid.LocaleGridHandler', 'index') . '">' . __('plugins.generic.translator.translate') . '</a></li>';

		// Permit other plugins to continue interacting with this hook
		return false;
	}

	/**
	 * Permit requests to the static pages grid handler
	 * @param $hookName string The name of the hook being invoked
	 * @param $args array The parameters to the invoked hook
	 */
	function setupComponentHandlers($hookName, $params) {
		$component =& $params[0];
		switch ($component) {
			case 'plugins.generic.translator.controllers.grid.LocaleGridHandler':
			case 'plugins.generic.translator.controllers.grid.LocaleFileGridHandler':
			case 'plugins.generic.translator.controllers.grid.MiscTranslationFileGridHandler':
			case 'plugins.generic.translator.controllers.grid.EmailGridHandler':
			case 'plugins.generic.translator.controllers.listbuilder.LocaleFileListbuilderHandler':
				// Allow the static page grid handler to get the plugin object
				import($component);
				$className = array_pop(explode('.', $component));
				$className::setPlugin($this);
				return true;
		}
		return false;
	}

	/**
	 * @copydoc Plugin::getDisplayName()
	 */
	function getDisplayName() {
		return __('plugins.generic.translator.name');
	}

	/**
	 * @copydoc Plugin::getDescription()
	 */
	function getDescription() {
		return __('plugins.generic.translator.description');
	}

	/**
	 * @copydoc Plugin::isSitePlugin()
	 */
	function isSitePlugin() {
		return true;
	}

	/**
	 * @copydoc Plugin::getActions()
	 */
	function getActions($request, $actionArgs) {
		$dispatcher = $request->getDispatcher();
		import('lib.pkp.classes.linkAction.request.RedirectAction');
		return array_merge(
			$this->getEnabled() && Validation::isSiteAdmin()?array(
				new LinkAction(
					'translate',
					new RedirectAction($dispatcher->url(
						$request, ROUTE_PAGE,
						null, 'management', 'settings', 'website',
						array('uid' => uniqid()), // Force reload
						'translate' // Anchor for tab
					)),
					__('plugins.generic.translator.translate'),
					null
				)
			):array(),
			parent::getActions($request, $actionArgs)
		);
	}

	/**
	 * Get the JavaScript URL for this plugin.
	 */
	function getJavaScriptURL($request) {
		return $request->getBaseUrl() . '/' . $this->getPluginPath() . '/js';
	}

	/**
	 * @copydoc PKPPlugin::getTemplatePath
	 */
	function getTemplatePath($inCore = false) {
		return parent::getTemplatePath($inCore) . 'templates/';
	}

	/**
	 * Get the registry path for this plugin.
	 * @return string Registry path
	 */
	function getRegistryPath() {
		return parent::getPluginPath() . '/registry';
	}
}

?>
