<?php

/**
 * @file PLNGatewayPlugin.inc.php
 *
 * Copyright (c) 2013-2020 Simon Fraser University
 * Copyright (c) 2003-2020 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file LICENSE.
 *
 * @class PLNGatewayPlugin
 * @brief Gateway component of web PLN plugin
 */

import('lib.pkp.classes.plugins.GatewayPlugin');
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
	 * @param $parentPluginName string
	 */
	public function __construct($parentPluginName) {
		parent::__construct();
		$this->parentPluginName = $parentPluginName;
	}

	/**
	 * Hide this plugin from the management interface (it's subsidiary)
	 * @return boolean
	 */
	public function getHideManagement() {
		return true;
	}

	/**
	 * @copydoc Plugin::getName
	 */
	public function getName() {
		return 'PLNGatewayPlugin';
	}

	/**
	 * @copydoc Plugin::getDisplayName
	 */
	public function getDisplayName() {
		return __('plugins.generic.plngateway.displayName');
	}

	/**
	 * @copydoc Plugin::getDescription
	 */
	public function getDescription() {
		return __('plugins.generic.plngateway.description');
	}

	/**
	 * Get the PLN plugin
	 * @return object
	 */
	public function getPLNPlugin() {
		return PluginRegistry::getPlugin('generic', $this->parentPluginName);
	}

	/**
	 * Override the builtin to get the correct plugin path.
	 * @return string
	 */
	public function getPluginPath() {
		$plugin = $this->getPLNPlugin();
		return $plugin->getPluginPath();
	}

	/**
	 * Override the builtin to get the correct template path.
	 * @return string
	 */
	public function getTemplatePath($inCore = false) {
		$plugin = $this->getPLNPlugin();
		return $plugin->getTemplatePath($inCore);
	}

	/**
	 * Get whether or not this plugin is enabled. (Should always return true, as the
	 * parent plugin will take care of loading this one when needed)
	 * @return boolean
	 */
	public function getEnabled() {
		$plugin = $this->getPLNPlugin();
		return $plugin->getEnabled(); // Should always be true anyway if this is loaded
	}

	/**
	 * @copydoc GatewayPlugin::fetch
	 */
	public function fetch($args, $request) {
		$plugin = $this->getPLNPlugin();
		$templateMgr = TemplateManager::getManager($request);
		$journal = $request->getJournal();

		$pluginVersionFile = $this->getPluginPath() . DIRECTORY_SEPARATOR . 'version.xml';
		$pluginVersion = VersionCheck::parseVersionXml($pluginVersionFile);
		$templateMgr->assign('pluginVersion', $pluginVersion);

		$terms = array();
		$termsAccepted = $plugin->termsAgreed($journal->getId());
		if ($termsAccepted) {
			$templateMgr->assign('termsAccepted', 'yes');
			$terms = unserialize($plugin->getSetting($journal->getId(), 'terms_of_use'));
			$termsAgreement = unserialize($plugin->getSetting($journal->getId(), 'terms_of_use_agreement'));
		} else {
			$templateMgr->assign('termsAccepted', 'no');
		}

		$application = PKPApplication::getApplication();
		$products = $application->getEnabledProducts('plugins.generic');
		$prerequisites = array(
			'phpVersion' => PHP_VERSION,
			'zipInstalled' => class_exists('ZipArchive') ? 'yes' : 'no',
			'tarInstalled' => class_exists('Archive_Tar') ? 'yes' : 'no',
			'acron' => isset($products['acron']) ? 'yes' : 'no',
			//'tasks' => Config::getVar('general', 'scheduled_tasks', false) ? 'yes' : 'no',
		);
		$templateMgr->assign('prerequisites', $prerequisites);

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

		$versionDao = DAORegistry::getDAO('VersionDAO');
		$ojsVersion = $versionDao->getCurrentVersion();
		$templateMgr->assign('ojsVersion', $ojsVersion->getVersionString());

		$submissionDao = DAORegistry::getDAO('SubmissionDAO');
		$publications = array();
		$submissions = $submissionDao->getByContextId($journal->getId());
		while ($submission = $submissions->next()) {
			$publication = $submission->getCurrentPublication();
			if (!$publication || $publication->getData('status') != STATUS_PUBLISHED) continue;
			$publications[] = $publication;
			if (count($publications) == PLN_PLUGIN_PING_ARTICLE_COUNT) break;
		}
		$templateMgr->assign('publications', $publications);
		$templateMgr->assign('pln_network', $plugin->getSetting($journal->getId(), 'pln_network'));

		header('Content-Type: text/xml; charset=' . Config::getVar('i18n', 'client_charset'));
		$templateMgr->display($plugin->getTemplateResource('ping.tpl'));

		return true;
	}
}
