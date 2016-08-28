<?php

/**
 * @file plugins/importexport/doaj/DOAJExportPlugin.inc.php
 *
 * Copyright (c) 2014-2016 Simon Fraser University Library
 * Copyright (c) 2003-2016 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class DOAJExportPlugin
 * @ingroup plugins_importexport_doaj
 *
 * @brief DOAJ export plugin
 */

import('classes.plugins.PubObjectsExportPlugin');

define('DOAJ_XSD_URL', 'http://www.doaj.org/schemas/doajArticles.xsd');

class DOAJExportPlugin extends PubObjectsExportPlugin {
	/**
	 * Constructor
	 */
	function DOAJExportPlugin() {
		parent::PubObjectsExportPlugin();
	}

	/**
	 * @copydoc Plugin::getName()
	 */
	function getName() {
		return 'DOAJExportPlugin';
	}

	/**
	 * @copydoc Plugin::getDisplayName()
	 */
	function getDisplayName() {
		return __('plugins.importexport.doaj.displayName');
	}

	/**
	 * @copydoc Plugin::getDescription()
	 */
	function getDescription() {
		return __('plugins.importexport.doaj.description');
	}

	/**
	 * @copydoc ImportExportPlugin::display()
	 */
	function display($args, $request) {
		parent::display($args, $request);
		switch (array_shift($args)) {
			case 'index':
			case '':
				$templateMgr = TemplateManager::getManager($request);
				$templateMgr->display($this->getTemplatePath() . 'index.tpl');
				break;
		}
	}

	function getStatusNames() {
		return array(
			EXPORT_STATUS_ANY => __('plugins.importexport.common.status.any'),
			EXPORT_STATUS_NOT_DEPOSITED => __('plugins.importexport.common.status.notDeposited'),
			EXPORT_STATUS_MARKEDREGISTERED => __('plugins.importexport.common.status.markedRegistered'),
		);
	}

	/**
	 * Get the plugin ID used as plugin settings prefix.
	 * @return string
	 */
	function getPluginSettingsPrefix() {
		return 'doaj';
	}

	/**
	 * @copydoc PubObjectsExportPlugin::getSubmissionFilter()
	 */
	function getSubmissionFilter() {
		return 'article=>doaj-xml';
	}

	/**
	 * @copydoc PubObjectsExportPlugin::getExportDeploymentClassName()
	 */
	function getExportDeploymentClassName() {
		return 'DOAJExportDeployment';
	}

}

?>
