<?php

/**
 * @file plugins/generic/webFeed/WebFeedPlugin.inc.php
 *
 * Copyright (c) 2013-2015 Simon Fraser University Library
 * Copyright (c) 2003-2015 John Willinsky
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
				HookRegistry::register('TemplateManager::display',array(&$this, 'callbackAddLinks'));
				HookRegistry::register('PluginRegistry::loadCategory', array(&$this, 'callbackLoadCategory'));
				HookRegistry::register('LoadHandler', array(&$this, 'callbackHandleShortURL') );
			}
			return true;
		}
		return false;
	}

	/**
	 * Get the name of the settings file to be installed on new journal
	 * creation.
	 * @return string
	 */
	function getContextSpecificPluginSettingsFile() {
		return $this->getPluginPath() . '/settings.xml';
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
				$plugins[$blockPlugin->getSeq()][$blockPlugin->getPluginPath()] =& $blockPlugin;
				break;
			case 'gateways':
				$this->import('WebFeedGatewayPlugin');
				$gatewayPlugin = new WebFeedGatewayPlugin($this->getName());
				$plugins[$gatewayPlugin->getSeq()][$gatewayPlugin->getPluginPath()] =& $gatewayPlugin;
				break;
		}
		return false;
	}

	/**
	 * Add feed links to page <head> on select/all pages.
	 */
	function callbackAddLinks($hookName, $args) {
		if ($this->getEnabled()) {
			// Only page requests will be handled
			$request =& Registry::get('request');
			if (!is_a($request->getRouter(), 'PKPPageRouter')) return false;

			$templateManager =& $args[0];

			$currentJournal =& $templateManager->get_template_vars('currentJournal');
			$requestedPage = Request::getRequestedPage();
			$journalTitle = '';
			if ($currentJournal) {
				$issueDao =& DAORegistry::getDAO('IssueDAO');
				$currentIssue =& $issueDao->getCurrentIssue($currentJournal->getId(), true);
				$displayPage = $this->getSetting($currentJournal->getId(), 'displayPage');
				$journalTitle = $this->sanitize($currentJournal->getLocalizedTitle());
			}

			if ( ($currentIssue) && (($displayPage == 'all') || ($displayPage == 'homepage' && (empty($requestedPage) || $requestedPage == 'index' || $requestedPage == 'issue')) || ($displayPage == 'issue' && $displayPage == $requestedPage)) ) {
				$additionalHeadData = $templateManager->get_template_vars('additionalHeadData');

				$feedUrl1 = '<link rel="alternate" type="application/atom+xml" title="' . $journalTitle . ' (atom+xml)" href="'.$currentJournal->getUrl().'/gateway/plugin/WebFeedGatewayPlugin/atom" />';
				$feedUrl2 = '<link rel="alternate" type="application/rdf+xml" title="' . $journalTitle . ' (rdf+xml)" href="'.$currentJournal->getUrl().'/gateway/plugin/WebFeedGatewayPlugin/rss" />';
				$feedUrl3 = '<link rel="alternate" type="application/rss+xml" title="' . $journalTitle . ' (rss+xml)" href="'.$currentJournal->getUrl().'/gateway/plugin/WebFeedGatewayPlugin/rss2" />';

				$templateManager->assign('additionalHeadData', $additionalHeadData."\n\t".$feedUrl1."\n\t".$feedUrl2."\n\t".$feedUrl3);
			}
		}

		return false;
	}

	/**
	 * Handle requests for feed via short URLs (e.g., journalPath/feed/atom).
	 * This is for backwards compatibility with older versions of this plugin.
	 */
	function callbackHandleShortURL($hookName, $args) {
		if ($this->getEnabled()) {
			$page =& $args[0];
			$op =& $args[1];

			if ($page == 'feed') {
				switch ($op) {
					case 'atom':
						Request::redirect(null, 'gateway', 'plugin', array('WebFeedGatewayPlugin', 'atom'));
						break;
					case 'rss':
						Request::redirect(null, 'gateway', 'plugin', array('WebFeedGatewayPlugin', 'rss'));
						break;
					case 'rss2':
						Request::redirect(null, 'gateway', 'plugin', array('WebFeedGatewayPlugin', 'rss2'));
						break;
					default:
						Request::redirect(null, 'index');
				}
			}
		}
		return false;
	}

	/**
	 * Display verbs for the management interface.
	 */
	function getManagementVerbs() {
		$verbs = array();
		if ($this->getEnabled()) {
			$verbs[] = array('settings', __('plugins.generic.webfeed.settings'));
		}
		return parent::getManagementVerbs($verbs);
	}

	/**
	 * Execute a management verb on this plugin
	 * @param $verb string
	 * @param $args array
	 * @param $message string Result status message
	 * @param $messageParams array Parameters for the message key
	 * @return boolean
	 */
	function manage($verb, $args, &$message, &$messageParams) {
		if (!parent::manage($verb, $args, $message, $messageParams)) return false;

		switch ($verb) {
			case 'settings':
				$journal =& Request::getJournal();

				AppLocale::requireComponents(LOCALE_COMPONENT_APPLICATION_COMMON,  LOCALE_COMPONENT_PKP_MANAGER);
				$templateMgr =& TemplateManager::getManager();
				$templateMgr->register_function('plugin_url', array(&$this, 'smartyPluginUrl'));

				$this->import('SettingsForm');
				$form = new SettingsForm($this, $journal->getId());

				if (Request::getUserVar('save')) {
					$form->readInputData();
					if ($form->validate()) {
						$form->execute();
						Request::redirect(null, null, 'plugins');
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

	/**
	 * Clean up the Journal title.
	 * @param $string
	 * @return $string
	 */
	function sanitize($string) {
		return htmlspecialchars(strip_tags($string));
	}
}

?>
