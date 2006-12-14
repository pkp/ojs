<?php

/**
 * PhpAdsNewPlugin.inc.php
 *
 * Copyright (c) 2003-2006 Siavash Miri and Alec Smecher
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package plugins
 *
 * Integrate PHPAdsNew ad manager with OJS.
 *
 * $Id: CounterPlugin.inc.php,v 1.0 2006/10/20 12:28pm
 */

import('classes.plugins.GenericPlugin');

class PhpAdsNewPlugin extends GenericPlugin {
	/** @var $templateName string Used to track the name of current template */
	var $templateName;

	/**
	 * Called as a plugin is registered to the registry
	 * @param @category String Name of category plugin was registered to
	 * @return boolean True iff plugin initialized successfully; if false,
	 * 	the plugin will not be registered.
	 */
	function register($category, $path) {
		if (!Config::getVar('general', 'installed')) return false;
		if (parent::register($category, $path)) {
			$this->addLocaleData();

			if ($this->getEnabled()) {
				HookRegistry::register('TemplateManager::display', array(&$this, 'mainCallback'));
				HookRegistry::register('Templates::Common::Header::sidebar', array(&$this, 'sidebarCallback'));
			}

			return true;
		}
		return false;
	}

	/**
	 * Get the name of this plugin. The name must be unique within
	 * its category.
	 * @return String name of plugin
	 */
	function getName() {
		return 'PhpAdsNewPlugin';
	}

	function getDisplayName() {
		$this->addLocaleData();
		return Locale::translate('plugins.generic.phpadsnew');
	}

	function getDescription() {
		$this->addLocaleData();
		return Locale::translate($this->isConfigured()?'plugins.generic.phpadsnew.description':'plugins.generic.phpadsnew.descriptionUnconfigured');
	}

	function mainCallback($hookName, $args) {
		$smarty =& $args[0];
		$template =& $args[1];
		if ($template == 'rt/rt.tpl') {
			$smarty->register_outputfilter(array(&$this, 'sidebarOutputFilter'));
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
			$headerAdHtml = $this->getSetting($journal->getJournalId(), 'headerAdHtml');
			$contentAdHtml = $this->getSetting($journal->getJournalId(), 'contentAdHtml');
		}

		// Look for the first <h1> tag and insert the header ad.
		if (!empty($headerAdHtml)) {
			if (($index = strpos($output, '<h1>')) !== false) {
				$newOutput = substr($output, 0, $index);
				$newOutput .= $headerAdHtml;
				$newOutput .= substr($output, $index);
				$output =& $newOutput;
			} else if (($index = strpos($output, '<h2>')) !== false) {
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
	
	function sidebarCallback($hookName, $args) {
		$smarty =& $args[0];
		$template =& $args[1];
		$smarty->register_outputfilter(array(&$this, 'sidebarOutputFilter'));
		return false;
	}

	function sidebarOutputFilter($output, &$smarty) {
		$journal =& Request::getJournal();
		if (!$journal) return $output;

		// Get the ad settings.
		$sidebarAdHtml = $this->getSetting($journal->getJournalId(), 'sidebarAdHtml');

		// Look for the last </div> tag and insert the sidebar ad.
		$index = strrpos($output, '</div>');
		if ($index !== false && !empty($sidebarAdHtml)) {
			$newOutput = substr($output, 0, $index);
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
				Locale::translate('plugins.generic.phpadsnew.manager.settings')
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
				$this->import('PhpAdsNewSettingsForm');
				$this->import('PhpAdsNewConnection');
				$phpAdsNewConnection =& new PhpAdsNewConnection($this, $this->getInstallationPath());
				$phpAdsNewConnection->loadConfig();
				$form =& new PhpAdsNewSettingsForm($this, $phpAdsNewConnection, $journal->getJournalId());
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

	function isConfigured() {
		$this->import('PhpAdsNewConnection');
		$config =& new PhpAdsNewConnection($this, $this->getInstallationPath());
		return $config->isConfigured();
	}

	function getInstallationPath() {
		return Config::getVar('phpAdsNew', 'installPath');
	}
}

?>
