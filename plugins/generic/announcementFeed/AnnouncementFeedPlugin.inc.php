<?php

/**
 * @file plugins/generic/announcementFeed/AnnouncementFeedPlugin.inc.php
 *
 * Copyright (c) 2003-2013 John Willinsky
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

	function callbackAddLinks($hookName, $args) {
		$request =& $this->getRequest();
		if ($this->getEnabled() && is_a($request->getRouter(), 'PKPPageRouter')) {
			$templateManager =& $args[0];
			$currentJournal =& $templateManager->get_template_vars('currentJournal');
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
	 * Display verbs for the management interface.
	 */
	function getManagementVerbs() {
		$verbs = parent::getManagementVerbs();
		if ($this->getEnabled()) {
			$verbs[] = array('settings', __('plugins.generic.announcementfeed.settings'));
		}
		return $verbs;
	}

 	/**
	 * @see Plugin::manage()
	 */
	function manage($verb, $args, &$message, &$messageParams, &$pluginModalContent = null) {
		if (!parent::manage($verb, $args, $message, $messageParams)) return false;
		$request =& $this->getRequest();
		switch ($verb) {
			case 'settings':
				$journal = $request->getJournal();

				$templateMgr = TemplateManager::getManager($request);
				$templateMgr->register_function('plugin_url', array($this, 'smartyPluginUrl'));

				$this->import('SettingsForm');
				$form = new SettingsForm($this, $journal->getId());

				if ($request->getUserVar('save')) {
					$form->readInputData();
					if ($form->validate()) {
						$form->execute();
						return false;
					} else {
						$form->display($request);
					}
				} else {
					$form->initData();
					$form->display($request);
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
