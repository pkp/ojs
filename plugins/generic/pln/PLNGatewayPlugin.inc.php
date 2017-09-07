<?php

/**
 * @file plugins/generic/pln/PLNGatewayPlugin.inc.php
 *
 * Copyright (c) 2013-2017 Simon Fraser University
 * Copyright (c) 2003-2016 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class PLNGatewayPlugin
 * @ingroup plugins_generic_pln
 *
 * @brief Gateway component of web feed plugin
 *
 */

import('classes.plugins.GatewayPlugin');
import('lib.pkp.classes.site.VersionCheck');
import('lib.pkp.classes.db.DBResultRange');
import('lib.pkp.classes.core.ArrayItemIterator');

define('PLN_PLUGIN_PING_ARTICLE_COUNT', 12);

// Archive/Tar.php may not be installed, so supress possible error.
@include_once('Archive/Tar.php');

class PLNGatewayPlugin extends GatewayPlugin {
	/** @var $parentPluginName string Name of parent plugin */
	var $parentPluginName;

	/**
	 * Constructor.
	 *
	 * @param $parentPluginName string
	 */
	function PLNGatewayPlugin($parentPluginName) {
		parent::GatewayPlugin();
		$this->parentPluginName = $parentPluginName;
	}

	/**
	 * Hide this plugin from the management interface (it's subsidiary)
	 */
	function getHideManagement() {
		return true;
	}

	/**
         * @copydoc Plugin::getName
         */
	function getName() {
		return 'PLNGatewayPlugin';
	}

        /**
         * @copydoc Plugin::getDisplayName
         */
	function getDisplayName() {
		return __('plugins.generic.plngateway.displayName');
	}

        /**
         * @copydoc Plugin::getDescription
         */
	function getDescription() {
		return __('plugins.generic.plngateway.description');
	}

	/**
	 * Get the PLN plugin
	 * @return object
	 */
	function &getPLNPlugin() {
		$plugin =& PluginRegistry::getPlugin('generic', $this->parentPluginName);
		return $plugin;
	}

	/**
	 * Override the builtin to get the correct plugin path.
	 */
	function getPluginPath() {
		$plugin =& $this->getPLNPlugin();
		return $plugin->getPluginPath();
	}

	/**
	 * Override the builtin to get the correct template path.
	 * @return string
	 */
	function getTemplatePath() {
		$plugin =& $this->getPLNPlugin();
		return $plugin->getTemplatePath();
	}

	/**
	 * Get whether or not this plugin is enabled. (Should always return true, as the
	 * parent plugin will take care of loading this one when needed)
	 * @return boolean
	 */
	function getEnabled() {
		$plugin =& $this->getPLNPlugin();
		return $plugin->getEnabled(); // Should always be true anyway if this is loaded
	}

	/**
	 * Get the management verbs for this plugin (override to none so that the parent
	 * plugin can handle this)
	 * @return array
	 */
	function getManagementVerbs($verbs = array()) {
                return $verbs;
	}
        
	/**
	 * Handle fetch requests for this plugin.
	 */
	function fetch() {
		$plugin =& $this->getPLNPlugin();
		$templateMgr =& TemplateManager::getManager();

		$journal =& Request::getJournal();
		$templateMgr->assign_by_ref('journal', $journal);

		$pluginVersionFile = $this->getPluginPath() . DIRECTORY_SEPARATOR . 'version.xml';
		$pluginVersion =& VersionCheck::parseVersionXml($pluginVersionFile);
		$templateMgr->assign_by_ref('pluginVersion', $pluginVersion);

		$terms = array();
		$termsAccepted = $plugin->termsAgreed($journal->getId());
		if ($termsAccepted) {
			$templateMgr->assign('termsAccepted', 'yes');
			$terms = unserialize($plugin->getSetting($journal->getId(), 'terms_of_use'));
			$termsAgreement = unserialize($plugin->getSetting($journal->getId(), 'terms_of_use_agreement'));
		} else {
			$templateMgr->assign('termsAccepted', 'no');
		}
		
		$application =& PKPApplication::getApplication();
		$products =& $application->getEnabledProducts('plugins.generic');
		$curlVersion = 'not installed';
		if(function_exists('curl_version')) {
			$versionArray = curl_version();
			$curlVersion = $versionArray['version'];
		}		
		$prerequisites = array(
			'phpVersion' => PHP_VERSION,
			'curlVersion' => $curlVersion,
			'zipInstalled' => class_exists('ZipArchive') ? 'yes' : 'no',
			'tarInstalled' => class_exists('Archive_Tar') ? 'yes' : 'no',
			'acron' => isset($products['acron']) ? 'yes' : 'no',
			'tasks' => Config::getVar('scheduled_tasks', false) ? 'yes' : 'no',
		);
		$templateMgr->assign_by_ref('prerequisites', $prerequisites);

		$termKeys = array_keys($terms);
		$termsDisplay = array();
		foreach ($termKeys as $key) {
			$termsDisplay[] = array(
				'key' => $key,
				'term' => $terms[$key]['term'],
				'updated' => $terms[$key]['updated'],
				'accepted' => $termsAgreement[$key]
			);
		}
		$templateMgr->assign('termsDisplay', new ArrayItemIterator($termsDisplay));

		$versionDao =& DAORegistry::getDAO('VersionDAO');
		$ojsVersion =& $versionDao->getCurrentVersion();
		$templateMgr->assign('ojsVersion', $ojsVersion->getVersionString());

		$publishedArticlesDAO =& DAORegistry::getDAO('PublishedArticleDAO');
		$range = new DBResultRange(PLN_PLUGIN_PING_ARTICLE_COUNT);
		$publishedArticles =& $publishedArticlesDAO->getPublishedArticlesByJournalId($journal->getId(), $range, true);
		$templateMgr->assign_by_ref('articles', $publishedArticles);
		$templateMgr->assign_by_ref('pln_network', $plugin->getSetting($journal->getId(), 'pln_network'));

		$templateMgr->display($this->getTemplatePath() . DIRECTORY_SEPARATOR . 'ping.tpl', 'text/xml');

		return true;
	}

}
