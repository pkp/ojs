<?php

/**
 * @file plugins/generic/webFeed/WebFeedGatewayPlugin.inc.php
 *
 * Copyright (c) 2014-2019 Simon Fraser University
 * Copyright (c) 2003-2019 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class WebFeedGatewayPlugin
 * @ingroup plugins_generic_webFeed
 *
 * @brief Gateway component of web feed plugin
 *
 */

import('lib.pkp.classes.plugins.GatewayPlugin');

class WebFeedGatewayPlugin extends GatewayPlugin {
	/** @var WebFeedPlugin Parent plugin */
	protected $_parentPlugin;

	/**
	 * @param $parentPlugin WebFeedPlugin
	 */
	public function __construct($parentPlugin) {
		parent::__construct();
		$this->_parentPlugin = $parentPlugin;
	}

	/**
	 * Hide this plugin from the management interface (it's subsidiary)
	 */
	public function getHideManagement() {
		return true;
	}

	/**
	 * Get the name of this plugin. The name must be unique within
	 * its category.
	 * @return String name of plugin
	 */
	public function getName() {
		return 'WebFeedGatewayPlugin';
	}

	/**
	 * @copydoc Plugin::getDisplayName()
	 */
	public function getDisplayName() {
		return __('plugins.generic.webfeed.displayName');
	}

	/**
	 * @copydoc Plugin::getDescription()
	 */
	public function getDescription() {
		return __('plugins.generic.webfeed.description');
	}

	/**
	 * Override the builtin to get the correct plugin path.
	 * @return string
	 */
	public function getPluginPath() {
		return $this->_parentPlugin->getPluginPath();
	}

	/**
	 * Get whether or not this plugin is enabled. (Should always return true, as the
	 * parent plugin will take care of loading this one when needed)
	 * @param $contextId int Context ID (optional)
	 * @return boolean
	 */
	public function getEnabled($contextId = null) {
		return $this->_parentPlugin->getEnabled($contextId);
	}

	/**
	 * Handle fetch requests for this plugin.
	 * @param $args array Arguments.
	 * @param $request PKPRequest Request object.
	 */
	public function fetch($args, $request) {
		// Make sure we're within a Journal context
		$request = Application::get()->getRequest();
		$journal = $request->getJournal();
		if (!$journal) return false;

		// Make sure there's a current issue for this journal
		$issueDao = DAORegistry::getDAO('IssueDAO');
		$issue = $issueDao->getCurrent($journal->getId(), true);
		if (!$issue) return false;

		if (!$this->_parentPlugin->getEnabled($journal->getId())) return false;

		// Make sure the feed type is specified and valid
		$type = array_shift($args);
		$typeMap = array(
			'rss' => 'rss.tpl',
			'rss2' => 'rss2.tpl',
			'atom' => 'atom.tpl'
		);
		$mimeTypeMap = array(
			'rss' => 'application/rdf+xml',
			'rss2' => 'application/rss+xml',
			'atom' => 'application/atom+xml'
		);
		if (!isset($typeMap[$type])) return false;

		// Get limit setting from web feeds plugin
		$displayItems = $this->_parentPlugin->getSetting($journal->getId(), 'displayItems');
		$recentItems = (int) $this->_parentPlugin->getSetting($journal->getId(), 'recentItems');

		$publishedArticleDao = DAORegistry::getDAO('PublishedArticleDAO');
		if ($displayItems == 'recent' && $recentItems > 0) {
			import('lib.pkp.classes.db.DBResultRange');
			$rangeInfo = new DBResultRange($recentItems, 1);
			$publishedArticleObjects = $publishedArticleDao->getPublishedArticlesByJournalId($journal->getId(), $rangeInfo, true);
			$publishedArticles = array();
			while ($publishedArticle = $publishedArticleObjects->next()) {
				$publishedArticles[]['articles'][] = $publishedArticle;
			}
		} else {
			$publishedArticles = $publishedArticleDao->getPublishedArticlesInSections($issue->getId(), true);
		}

		$versionDao = DAORegistry::getDAO('VersionDAO');
		$version = $versionDao->getCurrentVersion();

		$templateMgr = TemplateManager::getManager($request);
		$templateMgr->assign(array(
			'ojsVersion' => $version->getVersionString(),
			'publishedArticles' => $publishedArticles,
			'journal' => $journal,
			'issue' => $issue,
			'showToc' => true,
		));

		AppLocale::requireComponents(LOCALE_COMPONENT_PKP_SUBMISSION); // submission.copyrightStatement

		$templateMgr->display($this->_parentPlugin->getTemplateResource($typeMap[$type]), $mimeTypeMap[$type]);

		return true;
	}
}
