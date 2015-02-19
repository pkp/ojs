<?php

/**
 * @file plugins/generic/timedView/TimedViewReportPlugin.inc.php
 *
 * Copyright (c) 2014-2015 Simon Fraser University Library
 * Copyright (c) 2003-2015 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 * 
 * @class TimedViewReportPlugin
 * @ingroup plugins_reports_timedView
 *
 * @brief Timed View report plugin
 */


import('lib.pkp.classes.plugins.GenericPlugin');

class TimedViewPlugin extends GenericPlugin {
	/**
	 * Get the display name of this plugin
	 * @return string
	 */
	function getDisplayName() {
		return __('plugins.generic.timedView.displayName');
	}

	/**
	 * Get the description of this plugin
	 * @return string
	 */
	function getDescription() {
		return __('plugins.generic.timedView.description');
	}

	/**
	 * Get the filename of the ADODB schema for this plugin.
	 */
	function getInstallSchemaFile() {
		return $this->getPluginPath() . '/' . 'schema.xml';
	}

	/**
	 * Hide this plugin from the generic plugin management interface
	 */
	function getHideManagement() {
		return true;
	}

	/**
	 * Called as a plugin is registered to the registry
	 * @param $category String Name of category plugin was registered to
	 * @return boolean True if plugin initialized successfully; if false,
	 * 	the plugin will not be registered.
	 */
	function register($category, $path) {
		$success = parent::register($category, $path);

		if($success) {
			if($this->getEnabled()) {
				$this->import('TimedViewReportDAO');
				$timedViewReportDao = new TimedViewReportDAO();
				DAORegistry::registerDAO('TimedViewReportDAO', $timedViewReportDao);

				$this->import('TimedViewReportForm');

				$this->addLocaleData();
				HookRegistry::register('TemplateManager::display', array($this, 'logRequest'));
				HookRegistry::register('PluginRegistry::loadCategory', array($this, 'callbackLoadCategory'));
			}
		}
		return $success;
	}

	/**
	 * Register as a report plugin, even though this is a generic plugin.
	 * This will allow the plugin to behave as a report plugin.
	 * @param $hookName string
	 * @param $args array
	 */
	function callbackLoadCategory($hookName, $args) {
		$category =& $args[0];
		$plugins =& $args[1];
		switch ($category) {
			case 'reports':
				$this->import('TimedViewReportPlugin');
				$reportPlugin = new TimedViewReportPlugin($this->getName());
				$plugins[$reportPlugin->getSeq()][$reportPlugin->getPluginPath()] =& $reportPlugin;
				break;
		}
		return false;
	}

	/**
	 * Log the request.
	 * @param $hookName string
	 * @param $args array
	 * @return boolean
	 */
	function logRequest($hookName, $args) {
		$templateManager = $args[0];
		$template =& $args[1];

		// Only page requests will be handled
		$request = Registry::get('request');
		if (!is_a($request->getRouter(), 'PKPPageRouter')) return false;

		$site = $request->getSite();
		$journal = $request->getJournal();

		if (!$this->getEnabled() || !$journal || $request->isBot()) return false;

		switch ($template) {
			case 'article/article.tpl':
				$article = $templateManager->get_template_vars('article');
				$galley = $templateManager->get_template_vars('galley');

				// If no galley exists, this is an abstract view
				if (!$galley) {
					$this->incrementAbstractViewCount($article, $request);
				} else {
					$this->incrementGalleyViewCount($article, $galley, $request);
				}
				break;
		}

		return false;
	}

	/**
	 * Add a view record for the requested abstract.
	 * @param $article Article
	 * @param $request PKPRequest
	 */
	function incrementAbstractViewCount($article, $request) {
		$ip = $request->getRemoteAddr();
		$userAgent = $request->getUserAgent();

		$timedViewReportDao = DAORegistry::getDAO('TimedViewReportDAO');
		$timedViewReportDao->incrementViewCount($article->getJournalId(), $article->getId(), null, $ip, $userAgent);

		// Also increment view count in the regular location
		$publishedArticleDao = DAORegistry::getDAO('PublishedArticleDAO');
		$publishedArticleDao->incrementViewsByArticleId($article->getId());
	}

	/**
	 * Add a view record for the requested galley.
	 * @param $hookName string
	 * @param $args array
	 * @param $request PKPRequest
	 */
	function incrementGalleyViewCount($article, $galley, $request) {
		$ip = $request->getRemoteAddr();
		$userAgent = $request->getUserAgent();

		$timedViewReportDao = DAORegistry::getDAO('TimedViewReportDAO');
		$timedViewReportDao->incrementViewCount($article->getJournalId(), $article->getId(), $galley->getId(), $ip, $userAgent);

		// Also increment view count in the regular location
		$galleyDao = DAORegistry::getDAO('ArticleGalleyDAO');
		$galleyDao->incrementViews($galley->getId());
	}
}

?>
