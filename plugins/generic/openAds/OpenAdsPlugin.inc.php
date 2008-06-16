<?php

/**
 * @file OpenAdsPlugin.inc.php
 *
 * Copyright (c) 2003-2008 Siavash Miri and Alec Smecher
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package plugins.generic.openAds
 * @class OpenAdsPlugin
 *
 * Integrate OpenAds ad manager with OJS.
 *
 * $Id: CounterPlugin.inc.php,v 1.0 2006/10/20 12:28pm
 */

define ('AD_ORIENTATION_LEFT',		1);
define ('AD_ORIENTATION_RIGHT',		2);
define ('AD_ORIENTATION_CENTRE',	3);

import('classes.plugins.GenericPlugin');

class OpenAdsPlugin extends GenericPlugin {
	/** @var $templateName string Used to track the name of current template */
	var $templateName;

	/**
	 * Called as a plugin is registered to the registry
	 * @param @category String Name of category plugin was registered to
	 * @return boolean True iff plugin initialized successfully; if false,
	 * 	the plugin will not be registered.
	 */
	function register($category, $path) {
		if (parent::register($category, $path)) {
			$this->addLocaleData();

			if ($this->getEnabled()) {
				HookRegistry::register('TemplateManager::display', array(&$this, 'mainCallback'));
				HookRegistry::register('PluginRegistry::loadCategory', array(&$this, 'callbackLoadCategory'));
			}

			return true;
		}
		return false;
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
				$this->import('OpenAdsBlockPlugin');
				$openAdsBlockPlugin =& new OpenAdsBlockPlugin();
				$plugins[$category][$openAdsBlockPlugin->getSeq()] =& $openAdsBlockPlugin;
				break;
		}
		return false;
	}

	/**
	 * Get the name of this plugin. The name must be unique within
	 * its category.
	 * @return String name of plugin
	 */
	function getName() {
		return 'OpenAdsPlugin';
	}

	function getDisplayName() {
		$this->addLocaleData();
		return Locale::translate('plugins.generic.openads');
	}

	function getDescription() {
		$this->addLocaleData();
		return Locale::translate($this->isConfigured()?'plugins.generic.openads.description':'plugins.generic.openads.descriptionUnconfigured');
	}

	function mainCallback($hookName, $args) {
		$smarty =& $args[0];
		$template =& $args[1];
		if ($template == 'rt/rt.tpl') {
			$smarty->register_outputfilter(array(&$this, 'rtOutputFilter'));
		} else {
			$smarty->register_outputfilter(array(&$this, 'mainOutputFilter'));
		}
		$this->templateName = $template;
		return false;
	}

	function mainOutputFilter($output, &$smarty) {
		$journal =& Request::getJournal();

		// Get the ad settings.
		$headerAdHtml = $contentAdHtml = '';
		if ($journal) {
			$journalId = $journal->getJournalId();
			$this->import('OpenAdsConnection');
			$openAdsConnection =& new OpenAdsConnection($this, $this->getInstallationPath());
			$headerAdHtml = $openAdsConnection->getAdHtml($this->getSetting($journalId, 'headerAdId'));
			$headerAdOrientation = $this->getSetting($journal->getJournalId(), 'headerAdOrientation');
			$contentAdHtml = $openAdsConnection->getAdHtml($this->getSetting($journalId, 'contentAdId'));
		}

		// Look for the first <h1> tag and insert the header ad.
		if (!empty($headerAdHtml)) {
			if (($index = strpos($output, '<h1>')) !== false) {
				$smarty->unregister_outputfilter('mainOutputFilter');
				$newOutput = substr($output, 0, $index);
				switch ($headerAdOrientation) {
					case AD_ORIENTATION_CENTRE:
						$newOutput .= '<center>';
						$newOutput .= $headerAdHtml;
						$newOutput .= '</center>';
						break;
					case AD_ORIENTATION_RIGHT:
						$newOutput .= '<span style="float: right">';
						$newOutput .= $headerAdHtml;
						$newOutput .= '</span>';
						break;
					case AD_ORIENTATION_LEFT:
					default:
						$newOutput .= $headerAdHtml;
						break;
				}
				$newOutput .= substr($output, $index);
				$output =& $newOutput;
			} else if (($index = strpos($output, '<h2>')) !== false) {
				$smarty->unregister_outputfilter('mainOutputFilter');
				$newOutput = substr($output, 0, $index);
				$newOutput .= $headerAdHtml;
				$newOutput .= substr($output, $index);
				$output =& $newOutput;
			}
		}

		if (in_array($this->templateName, array('article/article.tpl'))) {
			$output = str_replace ('{$adContent}', $contentAdHtml, $output);
		} else {
			$output = str_replace ('{$adContent}', '', $output);
		}

		return $output;

	}

	/**
	 * Output filter to modify the RT sidebar
	 */
	function rtOutputFilter($output, &$smarty) {
		$journal =& Request::getJournal();
		if (!$journal) return $output;

		//Get the ad settings.
		$this->import('OpenAdsConnection');
		$openAdsConnection =& new OpenAdsConnection($this, $this->getInstallationPath());
		$sidebarAdHtml = $openAdsConnection->getAdHtml($this->getSetting($journal->getJournalId(), 'sidebarAdId'));

		$index = strrpos($output, '<h5>' . Locale::translate('rt.readingTools') . '</h5>');
		if ($index !== false && !empty($sidebarAdHtml)) {
			$newOutput = substr($output,0,$index);
			$newOutput .= $sidebarAdHtml;
			$newOutput .= substr($output, $index);
			$output =& $newOutput;
		}
		$smarty->unregister_outputfilter('sidebarOutputFilter');
		return $output;
	}

	/**
	 * Display verbs for the management interface.
	 */
	function getManagementVerbs() {
		$verbs = array();
		if ($this->getEnabled()) {
			$verbs[] = array(
				'disable',
				Locale::translate('manager.plugins.disable')
			);
			if ($this->isConfigured()) $verbs[] = array(
				'settings',
				Locale::translate('plugins.generic.openads.manager.settings')
			);
		} else {
			if ($this->isConfigured()) $verbs[] = array(
				'enable',
				Locale::translate('manager.plugins.enable')
			);
		}
		return $verbs;
	}

	/**
	 * Determine whether or not this plugin is enabled.
	 */
	function getEnabled() {
		$journal =& Request::getJournal();
		if (!$journal) return false;
		return $this->getSetting($journal->getJournalId(), 'enabled');
	}

	/**
	 * Set the enabled/disabled state of this plugin
	 */
	function setEnabled($enabled) {
		$journal =& Request::getJournal();
		if ($journal) {
			$this->updateSetting($journal->getJournalId(), 'enabled', $enabled ? true : false);
			return true;
		}
		return false;
	}

	/**
	 * Perform management functions
	 */
	function manage($verb, $args) {
		$templateMgr = &TemplateManager::getManager();
		$journal = &Request::getJournal();
		$returner = false;

		switch ($verb) {
			case 'enable': $this->setEnabled(true); break;
			case 'disable': $this->setEnabled(false); break;
			case 'settings':
				$this->import('OpenAdsSettingsForm');
				$this->import('OpenAdsConnection');
				$openAdsConnection =& new OpenAdsConnection($this, $this->getInstallationPath());
				$openAdsConnection->loadConfig();
				$form =& new OpenAdsSettingsForm($this, $openAdsConnection, $journal->getJournalId());
				if (array_shift($args) == 'save') {
					$form->readInputData();
					$form->execute();
					Request::redirect(null, 'manager', 'plugins');
				} else {
					$form->initData();
					$form->display();
					$returner = true;
				}
		}
		return $returner;
	}

	/**
	 * Get the configured state of this plugin
	 * @return boolean
	 */
	function isConfigured() {
		$this->import('OpenAdsConnection');
		$config =& new OpenAdsConnection($this, $this->getInstallationPath());
		return $config->isConfigured();
	}

	/**
	 * Get the installation path to OpenAds
	 * @return string
	 */
	function getInstallationPath() {
		return Config::getVar('open_ads', 'install_path');
	}
}

?>
