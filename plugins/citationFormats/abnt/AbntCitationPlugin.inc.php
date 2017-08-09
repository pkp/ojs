<?php

/**
 * @file plugins/citationFormats/abnt/AbntCitationPlugin.inc.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2003-2017 John Willinsky
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
	 * @copydoc CitationPlugin::getCitationFormatName()
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
						$router->url($request, null, null, 'manage', null, array('verb' => 'settings', 'plugin' => $this->getName(), 'category' => 'citationFormats')),
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
	 * Display an HTML-formatted citation. We register PKPString::strtoupper modifier
	 * in order to convert author names to uppercase.
	 * @param $article Article
	 * @param $issue Issue
	 * @param $journal Journal
	 */
	function fetchCitation($article, $issue, $journal) {
		$templateMgr = TemplateManager::getManager($this->getRequest());
		$templateMgr->register_modifier('mb_upper', array('PKPString', 'strtoupper'));
		$templateMgr->register_modifier('abnt_date_format', array($this, 'abntDateFormat'));
		$templateMgr->register_modifier('abnt_date_format_with_day', array($this, 'abntDateFormatWithDay'));
		return parent::fetchCitation($article, $issue, $journal);
	}

 	/**
	 * @see Plugin::manage()
	 */
	function manage($args, $request) {
		switch ($request->getUserVar('verb')) {
			case 'settings':
				AppLocale::requireComponents(LOCALE_COMPONENT_APP_COMMON,  LOCALE_COMPONENT_PKP_MANAGER);
				$this->import('AbntSettingsForm');
				$form = new AbntSettingsForm($this, $request->getContext()->getId());

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

	/**
	 * Extend the {url ...} smarty to support this plugin.
	 * @param $params array
	 * @param $smarty Smarty
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

	/**
	 * @function abntDateFormat Format date taking in consideration ABNT month abbreviations
	 * @param $string string
	 * @return string
	 */
	function abntDateFormat($string) {
		if (is_numeric($string)) {
			// it is a numeric string, we handle it as timestamp
			$timestamp = (int)$string;
		} else {
			$timestamp = strtotime($string);
		}
		$format = "%B %Y";
		if (PKPString::strlen(strftime("%B", $timestamp)) > 4) {
			$format = "%b. %Y";
		}

		return PKPString::strtolower(strftime($format, $timestamp));
	}

	/**
	 * @function abntDateFormatWithDay Format date taking in consideration ABNT month abbreviations
	 * @param $string string
	 * @return string
	 */
	function abntDateFormatWithDay($string) {
		if (is_numeric($string)) {
			// it is a numeric string, we handle it as timestamp
			$timestamp = (int)$string;
		} else {
			$timestamp = strtotime($string);
		}
		$format = "%d %B %Y";
		if (PKPString::strlen(strftime("%B", $timestamp)) > 4) {
			$format = "%d %b. %Y";
		}

		return PKPString::strtolower(strftime($format, $timestamp));
	}
}

?>
