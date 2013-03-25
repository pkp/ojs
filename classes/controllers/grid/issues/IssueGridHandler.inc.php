<?php

/**
 * @file controllers/grid/issues/IssueGridHandler.inc.php
 *
 * Copyright (c) 2000-2013 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class IssueGridHandler
 * @ingroup controllers_grid_issues
 *
 * @brief Handle issues grid requests.
 */

import('lib.pkp.classes.controllers.grid.GridHandler');
import('controllers.grid.issues.IssueGridRow');

class IssueGridHandler extends GridHandler {
	/**
	 * Constructor
	 */
	function IssueGridHandler() {
		parent::GridHandler();
		$this->addRoleAssignment(
				array(ROLE_ID_EDITOR),
				array('fetchGrid', 'fetchRow', 'deleteIssue'));
	}


	//
	// Implement template methods from PKPHandler
	//
	/**
	 * @see PKPHandler::initialize()
	 */
	function initialize(&$request, $args) {
		parent::initialize($request, $args);

		AppLocale::requireComponents(LOCALE_COMPONENT_APP_EDITOR);

		//
		// Grid columns.
		//
		import('controllers.grid.issues.IssueGridCellProvider');
		$issueGridCellProvider = new IssueGridCellProvider();

		// Issue identification
		$this->addColumn(
			new GridColumn(
				'identification',
				'issue.issue',
				null,
				'controllers/grid/gridCell.tpl',
				$issueGridCellProvider
			)
		);

		// Published state
		$this->addColumn(
			new GridColumn(
				'published',
				'editor.issues.published',
				null,
				'controllers/grid/gridCell.tpl',
				$issueGridCellProvider
			)
		);

		// Number of articles
		$this->addColumn(
			new GridColumn(
				'numArticles',
				'editor.issues.numArticles',
				null,
				'controllers/grid/gridCell.tpl',
				$issueGridCellProvider
			)
		);
	}

	/**
	 * Get the row handler - override the default row handler
	 * @return IssueGridRow
	 */
	function &getRowInstance() {
		$row = new IssueGridRow();
		return $row;
	}

	//
	// Public operations
	//
	/**
	 * Removes an issue
	 * @param $args array
	 * @param $request PKPRequest
	 */
	function deleteIssue($args, $request) {
		$issueId = (int) $request->getUserVar('issueId');
		$journal = $request->getJournal();
		$issueDao = DAORegistry::getDAO('IssueDAO');
		$issue = $issueDao->getById($issueId, $journal->getId());
		if (!$issue) fatalError('Invalid issue ID!');

		$isBackIssue = $issue->getPublished() > 0 ? true: false;

		// remove all published articles and return original articles to editing queue
		$articleDao = DAORegistry::getDAO('ArticleDAO');
		$publishedArticleDao = DAORegistry::getDAO('PublishedArticleDAO');
		$publishedArticles = $publishedArticleDao->getPublishedArticles($issueId);
		if (isset($publishedArticles) && !empty($publishedArticles)) {
			// Insert article tombstone if the issue is published
			import('classes.article.ArticleTombstoneManager');
			$articleTombstoneManager = new ArticleTombstoneManager();
			foreach ($publishedArticles as $article) {
				if ($isBackIssue) {
					$articleTombstoneManager->insertArticleTombstone($article, $journal);
				}
				$articleDao->changeStatus($article->getId(), STATUS_QUEUED);
				$publishedArticleDao->deletePublishedArticleById($article->getPublishedArticleId());
			}
		}

		$issueDao->deleteObject($issue);
		if ($issue->getCurrent()) {
			$issues = $issueDao->getPublishedIssues($journal->getId());
			if (!$issues->eof()) {
				$issue = $issues->next();
				$issue->setCurrent(1);
				$issueDao->updateObject($issue);
			}
		}

		return DAO::getDataChangedEvent($issueId);
	}
}

?>
