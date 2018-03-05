<?php

/**
 * @file plugins/generic/usageStats/UsageStatsPlugin.inc.php
 *
 * Copyright (c) 2013-2018 Simon Fraser University
 * Copyright (c) 2003-2018 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class UsageStatsPlugin
 * @ingroup plugins_generic_usageStats
 *
 * @brief Provide usage statistics to data objects.
 */


import('lib.pkp.plugins.generic.usageStats.PKPUsageStatsPlugin');

class UsageStatsPlugin extends PKPUsageStatsPlugin {

	/**
	 * @copydoc PKPUsageEventPlugin::getDownloadFinishedEventHooks()
	 */
	protected function getDownloadFinishedEventHooks() {
		return array_merge(parent::getDownloadFinishedEventHooks(), array(
			'HtmlArticleGalleyPlugin::articleDownloadFinished'
		));
	}

	/**
	 * Register assets and output hooks to display statistics on the reader
	 * frontend.
	 *
	 * @return null
	 */
	function displayReaderStatistics() {

		// Add chart to article view page
		HookRegistry::register('Templates::Article::Main', array($this, 'displayReaderArticleGraph'));
	}

	/**
	 * Add chart to article view page
	 *
	 * Hooked to `Templates::Article::Main`
	 * @param $hookName string
	 * @param $params array
	 *   [1] $smarty object
	 *   [2] $output string HTML output to return
	 */
	function displayReaderArticleGraph($hookName, $params) {
		$smarty =& $params[1];
		$output =& $params[2];

		$context = $smarty->get_template_vars('currentContext');
		$pluginSettingsDao = DAORegistry::getDAO('PluginSettingsDAO');
		$contextDisplaySettingExists = $pluginSettingsDao->settingExists($context->getId(), $this->getName(), 'displayStatistics');
		$contextDisplaySetting = $this->getSetting($context->getId(), 'displayStatistics');
		$siteDisplaySetting = $this->getSetting(CONTEXT_ID_NONE, 'displayStatistics');
		if (($contextDisplaySettingExists && $contextDisplaySetting) ||
			(!$contextDisplaySettingExists && $siteDisplaySetting)) {

			$pubObject =& $smarty->get_template_vars('article');
			assert(is_a($pubObject, 'PublishedArticle'));
			$pubObjectId = $pubObject->getID();
			$pubObjectType = 'PublishedArticle';

			$output .= $this->getTemplate(
				array(
					'pubObjectType' => $pubObjectType,
					'pubObjectId'   => $pubObjectId,
				),
				'outputFrontend.tpl',
				$smarty
			);

			$this->addJavascriptData($this->getAllDownloadsStats($pubObjectId), $pubObjectType, $pubObjectId, 'frontend-article-view');
			$this->loadJavascript('frontend-article-view' );
		}
		return false;
	}
}

?>
