<?php

/**
 * ResolverPlugin.inc.php
 *
 * Copyright (c) 2003-2005 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package plugins
 *
 * Simple resolver gateway plugin
 *
 * $Id$
 */

import('classes.plugins.GatewayPlugin');

class ResolverPlugin extends GatewayPlugin {
	/**
	 * Called as a plugin is registered to the registry
	 * @param @category String Name of category plugin was registered to
	 * @return boolean True iff plugin initialized successfully; if false,
	 * 	the plugin will not be registered.
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
		return 'ResolverPlugin';
	}

	function getDisplayName() {
		return Locale::translate('plugins.gateways.resolver.displayName');
	}

	function getDescription() {
		return Locale::translate('plugins.gateways.resolver.description');
	}

	/**
	 * Handle fetch requests for this plugin.
	 */
	function fetch($args) {
		if (!$this->getEnabled()) {
			return false;
		}

		switch (array_shift($args)) {
			case 'vnp': // Volume, number, page
				$skipYear = true;
			case 'vnyp': // Volume, number, year, page
				// This can only be used from within a journal context
				$journal =& Request::getJournal();
				if (!$journal) break;

				$volume = (int) array_shift($args);
				$number = (int) array_shift($args);
				if (!isset($skipYear) || !$skipYear) $year = (int) array_shift($args);
				else $year = null;
				$page = (int) array_shift($args);

				$issueDao =& DAORegistry::getDAO('IssueDAO');
				$issues =& $issueDao->getPublishedIssuesByNumber($journal->getJournalId(), $volume, $number, $year);

				// Ensure only one issue matched, and fetch it.
				$issue =& $issues->next();
				if (!$issue || $issues->next()) break;
				unset($issues);

				$publishedArticleDao =& DAORegistry::getDAO('PublishedArticleDAO');
				$articles =& $publishedArticleDao->getPublishedArticles($issue->getIssueId());
				foreach ($articles as $article) {
					// Look for the correct page in the list of articles.
					$matches = null;
					if (String::regexp_match_get('/^[Pp][Pp]?[.]?[ ]?(\d+)$/', $article->getPages(), $matches)) {
						$matchedPage = $matches[1];
						if ($page == $matchedPage) Request::redirect(null, 'article', 'view', $article->getBestArticleId());
					}
					if (String::regexp_match_get('/^[Pp][Pp]?[.]?[ ]?(\d+)[ ]?-[ ]?([Pp][Pp]?[.]?[ ]?)?(\d+)$/', $article->getPages(), $matches)) {
						$matchedPageFrom = $matches[1];
						$matchedPageTo = $matches[3];
						if ($page >= $matchedPageFrom && ($page < $matchedPageTo || ($page == $matchedPageTo && $matchedPageFrom = $matchedPageTo))) Request::redirect(null, 'article', 'view', $article->getBestArticleId());
					}
					unset($article);
				}
		}

		// Failure.
		header("HTTP/1.0 500 Internal Server Error");
		$templateMgr =& TemplateManager::getManager();
		$templateMgr->assign('message', 'plugins.gateways.resolver.errors.errorMessage');
		$templateMgr->display('common/message.tpl');
		exit;
	}
}

?>
