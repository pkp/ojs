<?php

/**
 * @file plugins/generic/webFeed/WebFeedPlugin.inc.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2003-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class WebFeedPlugin
 * @ingroup plugins_block_webFeed
 *
 * @brief Web Feeds plugin class
 */

import('lib.pkp.classes.plugins.GenericPlugin');

class WebFeedPlugin extends GenericPlugin {
	/**
	 * Get the display name of this plugin
	 * @return string
	 */
	function getDisplayName() {
		return __('plugins.generic.webfeed.displayName');
	}

	/**
	 * Get the description of this plugin
	 * @return string
	 */
	function getDescription() {
		return __('plugins.generic.webfeed.description');
	}

	function register($category, $path) {
		if (parent::register($category, $path)) {
			if ($this->getEnabled()) {
				HookRegistry::register('TemplateManager::display',array($this, 'callbackAddLinks'));
				HookRegistry::register('PluginRegistry::loadCategory', array($this, 'callbackLoadCategory'));
			}
			return true;
		}
		return false;
	}

	/**
	 * Get the name of the settings file to be installed on new context
	 * creation.
	 * @return string
	 */
	function getContextSpecificPluginSettingsFile() {
		return $this->getPluginPath() . '/settings.xml';
	}

	/**
	 * @copydoc PKPPlugin::getTemplatePath
	 */
	function getTemplatePath($inCore = false) {
		return parent::getTemplatePath($inCore) . 'templates/';
	}

	/**
	 * Register as a block plugin, even though this is a generic plugin.
	 * This will allow the plugin to behave as a block plugin, i.e. to
	 * have layout tasks performed on it.
	 * @param $hookName string
	 * @param $args array
	 */
	function callbackLoadCategory($hookName, $args) {
		$category =& $args[0];
		$plugins =& $args[1];
		switch ($category) {
			case 'blocks':
				$this->import('WebFeedBlockPlugin');
				$blockPlugin = new WebFeedBlockPlugin($this->getName());
				$plugins[$blockPlugin->getSeq()][$blockPlugin->getPluginPath()] = $blockPlugin;
				break;
			case 'gateways':
				$this->import('WebFeedGatewayPlugin');
				$gatewayPlugin = new WebFeedGatewayPlugin($this->getName());
				$plugins[$gatewayPlugin->getSeq()][$gatewayPlugin->getPluginPath()] = $gatewayPlugin;
				break;
		}
		return false;
	}

	/**
	 * Add feed links to page <head> on select/all pages.
	 */
	function callbackAddLinks($hookName, $args) {
		// Only page requests will be handled
		$request = $this->getRequest();
		if (!is_a($request->getRouter(), 'PKPPageRouter')) return false;

		$templateManager =& $args[0];
		$currentJournal = $templateManager->get_template_vars('currentJournal');
		if (is_null($currentJournal)) {
			return;
		}
		$issueDao = DAORegistry::getDAO('IssueDAO');
		$currentIssue = $issueDao->getCurrent($currentJournal->getId(), true);

		if (!$currentIssue) {
			return;
		}

		$displayPage = $this->getSetting($currentJournal->getId(), 'displayPage');

		// Define when the <link> elements should appear
		$contexts = 'frontend';
		if ($displayPage == 'homepage') {
			$contexts = array('frontend-index', 'frontend-issue');
		} elseif ($displayPage == 'issue') {
			$contexts = 'frontend-issue';
		}

		$templateManager->addHeader(
			'webFeedAtom+xml',
			'<link rel="alternate" type="application/atom+xml" href="' . $request->url(null, 'gateway', 'plugin', array('WebFeedGatewayPlugin', 'atom')) . '">',
			array(
				'contexts' => $contexts,
			)
		);
		$templateManager->addHeader(
			'webFeedRdf+xml',
			'<link rel="alternate" type="application/rdf+xml" href="'. $request->url(null, 'gateway', 'plugin', array('WebFeedGatewayPlugin', 'rss')) . '">',
			array(
				'contexts' => $contexts,
			)
		);
		$templateManager->addHeader(
			'webFeedRss+xml',
			'<link rel="alternate" type="application/rss+xml" href="'. $request->url(null, 'gateway', 'plugin', array('WebFeedGatewayPlugin', 'rss2')) . '">',
			array(
				'contexts' => $contexts,
			)
		);

		return false;
	}

	/**
	 * @see Plugin::getActions()
	 */
	function getActions($request, $verb) {
		$router = $request->getRouter();
		import('lib.pkp.classes.linkAction.request.AjaxModal');
		return array_merge(
			$this->getEnabled()?array(
				new LinkAction(
					'settings',
					new AjaxModal(
						$router->url($request, null, null, 'manage', null, array('verb' => 'settings', 'plugin' => $this->getName(), 'category' => 'generic')),
						$this->getDisplayName()
					),
					__('manager.plugins.settings'),
					null
				),
			):array(),
			parent::getActions($request, $verb)
		);
	}

 	/**
	 * @see Plugin::manage()
	 */
	function manage($args, $request) {
		switch ($request->getUserVar('verb')) {
			case 'settings':
				$context = $request->getContext();

				AppLocale::requireComponents(LOCALE_COMPONENT_APP_COMMON,  LOCALE_COMPONENT_PKP_MANAGER);
				$templateMgr = TemplateManager::getManager($request);
				$templateMgr->register_function('plugin_url', array($this, 'smartyPluginUrl'));

				$this->import('SettingsForm');
				$form = new SettingsForm($this, $context->getId());

				if ($request->getUserVar('save')) {
					$form->readInputData();
					if ($form->validate()) {
						$form->execute();
						return new JSONMessage(true);
					}
				} else {
					$form->initData();
				}
				return new JSONMessage(true, $form->fetch($request));
		}
		return parent::manage($args, $request);
	}
}

?>
