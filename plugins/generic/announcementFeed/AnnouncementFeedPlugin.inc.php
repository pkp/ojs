<?php

/**
 * @file plugins/generic/announcementFeed/AnnouncementFeedPlugin.inc.php
 *
 * Copyright (c) 2014-2018 Simon Fraser University
 * Copyright (c) 2003-2018 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class AnnouncementFeedPlugin
 * @ingroup plugins_generic_announcementFeed
 *
 * @brief Annoucement Feed plugin class
 */

import('lib.pkp.classes.plugins.GenericPlugin');

class AnnouncementFeedPlugin extends GenericPlugin {
	/**
	 * @copydoc Plugin::register()
	 */
	public function register($category, $path, $mainContextId = null) {
		if (!parent::register($category, $path, $mainContextId)) return false;
		if ($this->getEnabled($mainContextId)) {
			HookRegistry::register('TemplateManager::display',array($this, 'callbackAddLinks'));
			$this->import('AnnouncementFeedBlockPlugin');
			$blockPlugin = new AnnouncementFeedBlockPlugin($this);
			PluginRegistry::register('blocks', $blockPlugin, $this->getPluginPath());

			$this->import('AnnouncementFeedGatewayPlugin');
			$gatewayPlugin = new AnnouncementFeedGatewayPlugin($this);
			PluginRegistry::register('gateways', $gatewayPlugin, $this->getPluginPath());
		}
		return true;
	}

	/**
	 * Get the display name of this plugin
	 * @return string
	 */
	public function getDisplayName() {
		return __('plugins.generic.announcementfeed.displayName');
	}

	/**
	 * Get the description of this plugin
	 * @return string
	 */
	public function getDescription() {
		return __('plugins.generic.announcementfeed.description');
	}

	/**
	 * Add links to the feeds.
	 * @param $hookName string
	 * @param $args array
	 * @return boolean Hook processing status
	 */
	public function callbackAddLinks($hookName, $args) {
		$request = Application::getRequest();
		if ($this->getEnabled() && is_a($request->getRouter(), 'PKPPageRouter')) {
			$templateManager = $args[0];
			$currentJournal = $templateManager->getTemplateVars('currentJournal');
			$announcementsEnabled = $currentJournal ? $currentJournal->getSetting('enableAnnouncements') : false;

			if (!$announcementsEnabled) {
				return false;
			}

			$displayPage = $currentJournal ? $this->getSetting($currentJournal->getId(), 'displayPage') : null;

			// Define when the <link> elements should appear
			$contexts = 'frontend';
			if ($displayPage == 'homepage') {
				$contexts = array('frontend-index', 'frontend-announcement');
			} elseif ($displayPage == 'announcement') {
				$contexts = 'frontend-' . $displayPage;
			}

			$templateManager->addHeader(
				'announcementsAtom+xml',
				'<link rel="alternate" type="application/atom+xml" href="' . $request->url(null, 'gateway', 'plugin', array('AnnouncementFeedGatewayPlugin', 'atom')) . '">',
				array(
					'contexts' => $contexts,
				)
			);
			$templateManager->addHeader(
				'announcementsRdf+xml',
				'<link rel="alternate" type="application/rdf+xml" href="'. $request->url(null, 'gateway', 'plugin', array('AnnouncementFeedGatewayPlugin', 'rss')) . '">',
				array(
					'contexts' => $contexts,
				)
			);
			$templateManager->addHeader(
				'announcementsRss+xml',
				'<link rel="alternate" type="application/rss+xml" href="'. $request->url(null, 'gateway', 'plugin', array('AnnouncementFeedGatewayPlugin', 'rss2')) . '">',
				array(
					'contexts' => $contexts,
				)
			);
		}

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
				$context = $request->getContext();

				AppLocale::requireComponents(LOCALE_COMPONENT_APP_COMMON,  LOCALE_COMPONENT_PKP_MANAGER);
				$templateMgr = TemplateManager::getManager($request);
				$templateMgr->registerPlugin('function', 'plugin_url', array($this, 'smartyPluginUrl'));

				$this->import('AnnouncementFeedSettingsForm');
				$form = new AnnouncementFeedSettingsForm($this, $context->getId());

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
