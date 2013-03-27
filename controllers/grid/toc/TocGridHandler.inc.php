<?php

/**
 * @file controllers/grid/admin/systemInfo/TocGridHandler.inc.php
 *
 * Copyright (c) 2000-2013 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class TocGridHandler
 * @ingroup controllers_grid_toc
 *
 * @brief Handle TOC (table of contents) grid requests.
 */

import('lib.pkp.classes.controllers.grid.CategoryGridHandler');
import('controllers.grid.toc.TocGridCategoryRow');

class TocGridHandler extends CategoryGridHandler {
	var $publishedArticlesBySectionId;

	/**
	 * Constructor
	 */
	function TocGridHandler() {
		parent::CategoryGridHandler();
		$this->addRoleAssignment(
			array(ROLE_ID_EDITOR, ROLE_ID_MANAGER),
			array('fetchGrid', 'fetchCategory', 'fetchRow')
		);
		$this->publishedArticlesBySectionId = array();
	}


	//
	// Implement template methods from PKPHandler.
	//
	/**
	 * @see PKPHandler::authorize()
	 */
	function authorize(&$request, &$args, $roleAssignments) {
		import('lib.pkp.classes.security.authorization.PkpContextAccessPolicy');
		$this->addPolicy(new PkpContextAccessPolicy($request, $roleAssignments));

		import('classes.security.authorization.OjsIssueRequiredPolicy');
		$this->addPolicy(new OjsIssueRequiredPolicy($request, $args));

		return parent::authorize($request, $args, $roleAssignments);
	}

	/**
	 * @see PKPHandler::initialize()
	 */
	function initialize(&$request) {
		parent::initialize($request);

		// Basic grid configuration.
		$this->setTitle('issue.toc');

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
				'controllers/grid/gridCell.tpl',
				$tocGridCellProvider
			)
		);
	}

	/**
	 * @see GridHandler::initFeatures()
	 */
	function initFeatures($request, $args) {
		import('lib.pkp.classes.controllers.grid.feature.OrderCategoryGridItemsFeature');
		return array(new OrderCategoryGridItemsFeature(ORDER_CATEGORY_GRID_CATEGORIES_AND_ROWS));
	}

	/**
	 * @see CategoryGridHandler::getCategoryRowIdParameterName()
	 */
	function getCategoryRowIdParameterName() {
		return 'sectionId';
	}

	/**
	 * @see GridDataProvider::getRequestArgs()
	 */
	function getRequestArgs() {
		$issue = $this->getAuthorizedContextObject(ASSOC_TYPE_ISSUE);
		return array_merge(
			parent::getRequestArgs(),
			array('issueId' => $issue->getId())
		);
	}

	/**
	 * @see CategoryGridHandler::getCategoryRowInstance()
	 */
	function getCategoryRowInstance() {
		return new TocGridCategoryRow();
	}

	/**
	 * @see CategoryGridHandler::getCategoryData()
	 */
	function getCategoryData($section) {
		return $this->publishedArticlesBySectionId[$section->getId()];
	}

	/**
	 * @see GridHandler::loadData
	 */
	function loadData(&$request, $filter) {
		$issue = $this->getAuthorizedContextObject(ASSOC_TYPE_ISSUE);

		$publishedArticleDao = DAORegistry::getDAO('PublishedArticleDAO');
		$sectionDao = DAORegistry::getDAO('SectionDAO');
		$publishedArticles = $publishedArticleDao->getPublishedArticles($issue->getId());

		$sections = array();
		foreach ($publishedArticles as $article) {
			$sectionId = $article->getSectionId();
			if (!isset($sections[$sectionId])) {
				$sections[$sectionId] = $sectionDao->getById($sectionId);
			}
			$this->publishedArticlesBySectionId[$sectionId][] = $article;
		}
		return $sections;
	}

	/**
	 * @see GridHandler::getDataElementSequence()
	 */
	function getDataElementSequence($article) {
		return $article->getSequence();
	}

	/**
	 * @see GridHandler::setDataElementSequence()
	 */
	function setDataElementSequence($request, $articleId, $publishedArticle, $newSequence) {
		$publishedArticleDao = DAORegistry::getDAO('PublishedArticleDAO');
		$publishedArticle->setSequence($newSequence);
		$publishedArticleDao->updateObject($publishedArticle);
	}
}

?>
