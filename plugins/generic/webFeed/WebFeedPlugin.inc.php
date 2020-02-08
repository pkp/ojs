<?php

/**
 * @file plugins/generic/webFeed/WebFeedPlugin.inc.php
 *
 * Copyright (c) 2014-2020 Simon Fraser University
 * Copyright (c) 2003-2020 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
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
	public function getDisplayName() {
		return __('plugins.generic.webfeed.displayName');
	}

	/**
	 * Get the description of this plugin
	 * @return string
	 */
	public function getDescription() {
		return __('plugins.generic.webfeed.description');
	}

	/**
	 * @copydoc Plugin::register()
	 */
	public function register($category, $path, $mainContextId = null) {
		if (!parent::register($category, $path, $mainContextId)) return false;
		if ($this->getEnabled($mainContextId)) {
			HookRegistry::register('TemplateManager::display',array($this, 'callbackAddLinks'));
			$this->import('WebFeedBlockPlugin');
			PluginRegistry::register('blocks', new WebFeedBlockPlugin($this), $this->getPluginPath());

			$this->import('WebFeedGatewayPlugin');
			PluginRegistry::register('gateways', new WebFeedGatewayPlugin($this), $this->getPluginPath());
		}
		return true;
	}

	/**
	 * Get the name of the settings file to be installed on new context
	 * creation.
	 * @return string
	 */
	public function getContextSpecificPluginSettingsFile() {
		return $this->getPluginPath() . '/settings.xml';
	}

	/**
	 * Add feed links to page <head> on select/all pages.
	 */
	public function callbackAddLinks($hookName, $args) {
		// Only page requests will be handled
		$request = Application::get()->getRequest();
		if (!is_a($request->getRouter(), 'PKPPageRouter')) return false;

		$templateManager =& $args[0];
		$currentJournal = $templateManager->getTemplateVars('currentJournal');
		if (is_null($currentJournal)) {
			return;
		}
		$issueDao = DAORegistry::getDAO('IssueDAO'); /* @var $issueDao IssueDAO */
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
	 * @copydoc Plugin::getActions()
	 */
	public function getActions($request, $verb) {
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
	 * @copydoc Plugin::manage()
	 */
	public function manage($args, $request) {
		switch ($request->getUserVar('verb')) {
			case 'settings':
				AppLocale::requireComponents(LOCALE_COMPONENT_APP_COMMON,  LOCALE_COMPONENT_PKP_MANAGER);
				$this->import('WebFeedSettingsForm');
				$form = new WebFeedSettingsForm($this, $request->getContext()->getId());

				if ($request->getUserVar('save')) {
					$form->readInputData();
					if ($form->validate()) {
						$form->execute();
						$notificationManager = new NotificationManager();
						$notificationManager->createTrivialNotification($request->getUser()->getId());
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
