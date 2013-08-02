<?php

/**
 * @file plugins/citationFormats/abnt/AbntCitationPlugin.inc.php
 *
 * Copyright (c) 2003-2013 John Willinsky
 * With contributions from by Lepidus Tecnologia
 *
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class AbntCitationPlugin
 * @ingroup plugins_citationFormats_abnt
 *
 * @brief ABNT citation format plugin
 */

import('classes.plugins.CitationPlugin');

class AbntCitationPlugin extends CitationPlugin {
	/**
	 * @copydoc Plugin::register()
	 */
	function register($category, $path) {
		$success = parent::register($category, $path);
		$this->addLocaleData();
		return $success;
	}

	/**
	 * Get the name of this plugin. The name must be unique within
	 * its category.
	 * @return String name of plugin
	 */
	function getName() {
		return 'AbntCitationPlugin';
	}

	/**
	 * @copydoc Plugin::getDisplayName()
	 */
	function getDisplayName() {
		return __('plugins.citationFormats.abnt.displayName');
	}

	/**
	 * @copydoc CitationFormatPlugin::getCitationFormatName()
	 */
	function getCitationFormatName() {
		return __('plugins.citationFormats.abnt.citationFormatName');
	}

	/**
	 * @copydoc Plugin::getDescription()
	 */
	function getDescription() {
		return __('plugins.citationFormats.abnt.description');
	}

	/**
	 * Get the localized location for citations in this journal
	 * @param $journal Journal
	 * @return string
	 */
	function getLocalizedLocation($journal) {
		$settings = $this->getSetting($journal->getId(), 'location');
		if ($settings === null) {
			return null;
		}
		$location = $settings[AppLocale::getLocale()];
		if (empty($location)) {
			$location = $settings[AppLocale::getPrimaryLocale()];
		}
		return $location;
	}

	/**
	 * Display verbs for the management interface.
	 * @return array
	 */
	function getManagementVerbs() {
		return array(
			array(
				'settings',
				__('plugins.citationFormats.abnt.manager.settings')
			)
		);
	}

	/**
	 * Display an HTML-formatted citation. We register String::strtoupper modifier
	 * in order to convert author names to uppercase.
	 * @param $article Article
	 * @param $issue Issue
	 * @param $journal Journal
	 */
	function displayCitation(&$article, &$issue, &$journal) {
		$templateMgr = TemplateManager::getManager($this->getRequest());
		$templateMgr->register_modifier('mb_upper', array('String', 'strtoupper'));
		return parent::displayCitation($article, $issue, $journal);
	}

 	/**
	 * @copydoc PKPPlugin::manage()
	 */
	function manage($verb, $args, &$message, &$messageParams, &$pluginModalContent = null) {
		$request = $this->getRequest();
		switch ($verb) {
			case 'settings':
				$templateMgr = TemplateManager::getManager($request);
				$templateMgr->register_function('plugin_url', array($this, 'smartyPluginUrl'));
				$journal = $request->getJournal();

				$this->import('AbntSettingsForm');
				$form = new AbntSettingsForm($this, $journal->getId());
				if ($request->getUserVar('save')) {
					$form->readInputData();
					if ($form->validate()) {
						$form->execute();
						$request->redirect(null, 'manager', 'plugin');
						return false;
					} else {
						$form->display();
					}
				} else {
					if ($form->isLocaleResubmit()) {
						$form->readInputData();
					} else {
						$form->initData();
					}
					$form->display();
				}
				return true;
			default:
				// Unknown management verb, delegate to parent
				return parent::manage($verb, $args, $message);
		}
	}

	/**
	 * Extend the {url ...} smarty to support this plugin.
	 */
	function smartyPluginUrl($params, &$smarty) {
		$path = array($this->getCategory(), $this->getName());
		if (is_array($params['path'])) {
			$params['path'] = array_merge($path, $params['path']);
		} elseif (!empty($params['path'])) {
			$params['path'] = array_merge($path, array($params['path']));
		} else {
			$params['path'] = $path;
		}

		return $smarty->smartyUrl($params, $smarty);
	}
}

?>
