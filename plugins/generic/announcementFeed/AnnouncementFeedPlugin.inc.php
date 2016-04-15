<?php

/**
 * @file plugins/generic/announcementFeed/AnnouncementFeedPlugin.inc.php
 *
 * Copyright (c) 2014-2016 Simon Fraser University Library
 * Copyright (c) 2003-2016 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class AnnouncementFeedPlugin
 * @ingroup plugins_generic_announcementFeed
 *
 * @brief Annoucement Feed plugin class
 */

import('lib.pkp.classes.plugins.GenericPlugin');

class AnnouncementFeedPlugin extends GenericPlugin {
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
	 * Get the display name of this plugin
	 * @return string
	 */
	function getDisplayName() {
		return __('plugins.generic.announcementfeed.displayName');
	}

	/**
	 * Get the description of this plugin
	 * @return string
	 */
	function getDescription() {
		return __('plugins.generic.announcementfeed.description');
	}

	/**
	 * Register as a block and gateway plugin, even though this is a generic plugin.
	 * This will allow the plugin to behave as a block and gateway plugin
	 * @param $hookName string
	 * @param $args array
	 */
	function callbackLoadCategory($hookName, $args) {
		$category =& $args[0];
		$plugins =& $args[1];
		switch ($category) {
			case 'blocks':
				$this->import('AnnouncementFeedBlockPlugin');
				$blockPlugin = new AnnouncementFeedBlockPlugin($this->getName());
				$plugins[$blockPlugin->getSeq()][$blockPlugin->getPluginPath()] =& $blockPlugin;
				break;
			case 'gateways':
				$this->import('AnnouncementFeedGatewayPlugin');
				$gatewayPlugin = new AnnouncementFeedGatewayPlugin($this->getName());
				$plugins[$gatewayPlugin->getSeq()][$gatewayPlugin->getPluginPath()] =& $gatewayPlugin;
				break;
		}
		return false;
	}

	/**
	 * Add links to the feeds.
	 * @param $hookName string
	 * @param $args array
	 * @return boolean Hook processing status
	 */
	function callbackAddLinks($hookName, $args) {
		$request =& $this->getRequest();
		if ($this->getEnabled() && is_a($request->getRouter(), 'PKPPageRouter')) {
			$templateManager = $args[0];
			$currentJournal = $templateManager->get_template_vars('currentJournal');
			$announcementsEnabled = $currentJournal ? $currentJournal->getSetting('enableAnnouncements') : false;
			$displayPage = $currentJournal ? $this->getSetting($currentJournal->getId(), 'displayPage') : null;
			$requestedPage = $request->getRequestedPage();

			if ( $announcementsEnabled && (($displayPage == 'all') || ($displayPage == 'homepage' && (empty($requestedPage) || $requestedPage == 'index' || $requestedPage == 'announcement')) || ($displayPage == $requestedPage)) ) {

				// if we have a journal selected, append feed meta-links into the header
				$additionalHeadData = $templateManager->get_template_vars('additionalHeadData');

				$feedUrl1 = '<link rel="alternate" type="application/atom+xml" href="' . $request->url(null, 'gateway', 'plugin', array('AnnouncementFeedGatewayPlugin', 'atom')) . '" />';
				$feedUrl2 = '<link rel="alternate" type="application/rdf+xml" href="'. $request->url(null, 'gateway', 'plugin', array('AnnouncementFeedGatewayPlugin', 'rss')) . '" />';
				$feedUrl3 = '<link rel="alternate" type="application/rss+xml" href="'. $request->url(null, 'gateway', 'plugin', array('AnnouncementFeedGatewayPlugin', 'rss2')) . '" />';

				$templateManager->assign('additionalHeadData', $additionalHeadData."\n\t".$feedUrl1."\n\t".$feedUrl2."\n\t".$feedUrl3);
			}
		}

		return false;
	}

	/**
	 * @copydoc Plugin::getActions()
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
	 * @copydoc Plugin::manage()
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
