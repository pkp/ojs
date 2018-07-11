<?php

/**
 * @file controllers/grid/toc/TocGridHandler.inc.php
 *
 * Copyright (c) 2014-2018 Simon Fraser University
 * Copyright (c) 2000-2018 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class TocGridHandler
 * @ingroup controllers_grid_toc
 *
 * @brief Handle TOC (table of contents) grid requests.
 */

import('lib.pkp.classes.controllers.grid.CategoryGridHandler');
import('controllers.grid.toc.TocGridCategoryRow');
import('controllers.grid.toc.TocGridRow');

class TocGridHandler extends CategoryGridHandler {
	var $publishedArticlesBySectionId;

	/**
	 * Constructor
	 */
	function __construct() {
		parent::__construct();
		$this->addRoleAssignment(
			array(ROLE_ID_MANAGER),
			array('fetchGrid', 'fetchCategory', 'fetchRow', 'saveSequence', 'removeArticle', 'setAccessStatus')
		);
		$this->publishedArticlesBySectionId = array();
	}


	//
	// Implement template methods from PKPHandler.
	//
	/**
	 * @copydoc PKPHandler::authorize()
	 */
	function authorize($request, &$args, $roleAssignments) {
		import('lib.pkp.classes.security.authorization.ContextAccessPolicy');
		$this->addPolicy(new ContextAccessPolicy($request, $roleAssignments));

		import('classes.security.authorization.OjsIssueRequiredPolicy');
		$this->addPolicy(new OjsIssueRequiredPolicy($request, $args));

		return parent::authorize($request, $args, $roleAssignments);
	}

	/**
	 * @copydoc PKPHandler::initialize()
	 */
	function initialize($request) {
		parent::initialize($request);

		AppLocale::requireComponents(LOCALE_COMPONENT_APP_EDITOR, LOCALE_COMPONENT_PKP_SUBMISSION, LOCALE_COMPONENT_APP_SUBMISSION);

		//
		// Grid columns.
		//
		import('controllers.grid.toc.TocGridCellProvider');
		$tocGridCellProvider = new TocGridCellProvider();

		// Article title
		$this->addColumn(
			new GridColumn(
				'title',
				'article.title',
				null,
				null,
				$tocGridCellProvider
			)
		);

		$issue = $this->getAuthorizedContextObject(ASSOC_TYPE_ISSUE);
		if ($request->getJournal()->getSetting('publishingMode') == PUBLISHING_MODE_SUBSCRIPTION && $issue->getAccessStatus() == ISSUE_ACCESS_SUBSCRIPTION) {
			// Article access status
			$this->addColumn(
				new GridColumn(
					'access',
					'reader.openAccess',
					null,
					'controllers/grid/common/cell/selectStatusCell.tpl',
					$tocGridCellProvider,
					array('width' => 20, 'alignment' => COLUMN_ALIGNMENT_CENTER)
				)
			);
		}
	}

	/**
	 * @copydoc GridHandler::initFeatures()
	 */
	function initFeatures($request, $args) {
		return array(new OrderCategoryGridItemsFeature(ORDER_CATEGORY_GRID_CATEGORIES_AND_ROWS));
	}

	/**
	 * @copydoc CategoryGridHandler::getCategoryRowIdParameterName()
	 */
	function getCategoryRowIdParameterName() {
		return 'sectionId';
	}

	/**
	 * @copydoc GridDataProvider::getRequestArgs()
	 */
	function getRequestArgs() {
		$issue = $this->getAuthorizedContextObject(ASSOC_TYPE_ISSUE);
		return array_merge(
			parent::getRequestArgs(),
			array('issueId' => $issue->getId())
		);
	}

	/**
	 * Get the row handler - override the default row handler
	 * @return TocGridRow
	 */
	protected function getRowInstance() {
		$issue = $this->getAuthorizedContextObject(ASSOC_TYPE_ISSUE);
		return new TocGridRow($issue->getId());
	}

	/**
	 * @copydoc CategoryGridHandler::getCategoryRowInstance()
	 */
	protected function getCategoryRowInstance() {
		return new TocGridCategoryRow();
	}

	/**
	 * @copydoc CategoryGridHandler::loadCategoryData()
	 */
	function loadCategoryData($request, $section) {
		return $this->publishedArticlesBySectionId[$section->getId()];
	}

	/**
	 * @copydoc GridHandler::loadData()
	 */
	protected function loadData($request, $filter) {
		$issue = $this->getAuthorizedContextObject(ASSOC_TYPE_ISSUE);

		$publishedArticleDao = DAORegistry::getDAO('PublishedArticleDAO');
		$sectionDao = DAORegistry::getDAO('SectionDAO');
		$publishedArticlesInSections = $publishedArticleDao->getPublishedArticlesInSections($issue->getId());
		$sections = array();
		foreach ($publishedArticlesInSections as $sectionId => $articles) {
			if (!isset($sections[$sectionId])) {
				$sections[$sectionId] = $sectionDao->getById($sectionId);
			}
			foreach($articles['articles'] as $article) {
				$this->publishedArticlesBySectionId[$sectionId][$article->getId()] = $article;
			}
		}
		return $sections;
	}

	/**
	 * @copydoc GridHandler::getDataElementSequence()
	 */
	function getDataElementSequence($section) {
		$sectionDao = DAORegistry::getDAO('SectionDAO');
		$issue = $this->getAuthorizedContextObject(ASSOC_TYPE_ISSUE);
		$customOrdering = $sectionDao->getCustomSectionOrder($issue->getId(), $section->getId());
		if ($customOrdering === null) { // No custom ordering specified; use default section ordering
			return $section->getSequence();
		} else { // Custom ordering specified.
			return $customOrdering;
		}
	}

	/**
	 * @copydoc GridHandler::setDataElementSequence()
	 */
	function setDataElementSequence($request, $sectionId, $gridDataElement, $newSequence) {
		$sectionDao = DAORegistry::getDAO('SectionDAO');
		$issue = $this->getAuthorizedContextObject(ASSOC_TYPE_ISSUE);
		if (!$sectionDao->customSectionOrderingExists($issue->getId())) {
			$sectionDao->setDefaultCustomSectionOrders($issue->getId());
		}
		$sectionDao->updateCustomSectionOrder($issue->getId(), $sectionId, $newSequence);
	}

	/**
	 * @copydoc CategoryGridHandler::getDataElementInCategorySequence()
	 */
	function getDataElementInCategorySequence($categoryId, &$publishedArticle) {
		return $publishedArticle->getSequence();
	}

	/**
	 * @copydoc GridHandler::setDataElementSequence()
	 */
	function setDataElementInCategorySequence($sectionId, $publishedArticle, $newSequence) {
		$publishedArticleDao = DAORegistry::getDAO('PublishedArticleDAO');
		if ($sectionId != $publishedArticle->getSectionId()) {
			$publishedArticle->setSectionId($sectionId);
		}
		$publishedArticle->setSequence($newSequence);
		$publishedArticleDao->updatePublishedArticle($publishedArticle);
	}

	//
	// Public handler functions
	//
	/**
	 * Remove an article from the issue.
	 * @param $args array
	 * @param $request PKPRequest
	 * @return JSONMessage JSON object
	 */
	function removeArticle($args, $request) {
		$journal = $request->getJournal();
		$articleId = (int) $request->getUserVar('articleId');
		$issue = $this->getAuthorizedContextObject(ASSOC_TYPE_ISSUE);
		$issueDao = DAORegistry::getDAO('IssueDAO');
		$publishedArticleDao = DAORegistry::getDAO('PublishedArticleDAO');
		$article = $publishedArticleDao->getByArticleId($articleId);
		import('classes.article.ArticleTombstoneManager');
		$articleTombstoneManager = new ArticleTombstoneManager();
		if ($article && $article->getIssueId() == $issue->getId() && $request->checkCSRF()) {
			if ($issue->getPublished()) {
				$articleTombstoneManager->insertArticleTombstone($article, $journal);
			}
			$article->setStatus(STATUS_QUEUED);
			$article->stampStatusModified();
			// If the article is the only one in the section, delete the section from custom issue ordering
			$sectionId = $article->getSectionId();
			$publishedArticleArray = $publishedArticleDao->getPublishedArticlesBySectionId($sectionId, $issue->getId());
			if (sizeof($publishedArticleArray) == 1) {
				$sectionDao = DAORegistry::getDAO('SectionDAO');
				$sectionDao->deleteCustomSection($issue->getId(), $sectionId);
			}
			$publishedArticleDao->deletePublishedArticleByArticleId($articleId);
			$publishedArticleDao->resequencePublishedArticles($article->getSectionId(), $issue->getId());
			return DAO::getDataChangedEvent();
		}

		// If we've fallen through, it must be a badly-specified article
		return new JSONMessage(false);
	}

	/**
	 * Set access status on an article.
	 * @param $args array
	 * @param $request PKPRequest
	 * @return JSONMessage JSON object
	 */
	function setAccessStatus($args, $request) {
		$journal = $request->getJournal();
		$articleId = (int) $request->getUserVar('articleId');
		$issue = $this->getAuthorizedContextObject(ASSOC_TYPE_ISSUE);
		$issueDao = DAORegistry::getDAO('IssueDAO');
		$publishedArticleDao = DAORegistry::getDAO('PublishedArticleDAO');
		$article = $publishedArticleDao->getByArticleId($articleId);
		if ($article && $article->getIssueId() == $issue->getId() && $request->checkCSRF()) {
			$article->setAccessStatus($request->getUserVar('status'));
			$article->stampStatusModified();
			$publishedArticleDao->updatePublishedArticle($article);
			return DAO::getDataChangedEvent();
		}

		// If we've fallen through, it must be a badly-specified article
		return new JSONMessage(false);
	}
}


