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
			array(ROLE_ID_EDITOR, ROLE_ID_MANAGER),
			array(
				'fetchGrid', 'fetchRow',
				'addIssue', 'editIssue', 'editIssueData', 'updateIssue',
				'editCover', 'updateCover',
				'issueToc',
				'issueGalleys',
				'deleteIssue', 'publishIssue', 'unpublishIssue',
			)
		);
	}


	//
	// Implement template methods from PKPHandler
	//
	/**
	 * @see PKPHandler::authorize()
	 * @param $request PKPRequest
	 * @param $args array
	 * @param $roleAssignments array
	 */
	function authorize(&$request, &$args, $roleAssignments) {
		import('lib.pkp.classes.security.authorization.PkpContextAccessPolicy');
		$this->addPolicy(new PkpContextAccessPolicy($request, $roleAssignments));

		// If a signoff ID was specified, authorize it.
		if ($request->getUserVar('issueId')) {
			import('classes.security.authorization.OjsIssueRequiredPolicy');
			$this->addPolicy(new OjsIssueRequiredPolicy($request, $args));
		}

		return parent::authorize($request, $args, $roleAssignments);
	}

	/**
	 * @see PKPHandler::initialize()
	 */
	function initialize(&$request, $args) {
		parent::initialize($request, $args);

		AppLocale::requireComponents(LOCALE_COMPONENT_APP_EDITOR);

		// Grid columns.
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
	function getRowInstance() {
		return new IssueGridRow();
	}

	//
	// Public operations
	//
	/**
	 * An action to add a new issue
	 * @param $args array
	 * @param $request PKPRequest
	 */
	function addIssue($args, $request) {
		// Calling editIssueData with an empty ID will add
		// a new issue.
		return $this->editIssueData($args, $request);
	}

	/**
	 * An action to edit a issue
	 * @param $args array
	 * @param $request PKPRequest
	 * @return string Serialized JSON object
	 */
	function editIssue($args, $request) {
		$issueId = isset($args['issueId']) ? $args['issueId'] : null;
		$templateMgr = TemplateManager::getManager($request);
		$templateMgr->assign('issueId', $issueId);
		$json = new JSONMessage(true, $templateMgr->fetch('controllers/grid/issues/issue.tpl'));
		return $json->getString();
	}

	/**
	 * An action to edit a issue's identifying data
	 * @param $args array
	 * @param $request PKPRequest
	 * @return string Serialized JSON object
	 */
	function editIssueData($args, $request) {
		$issueId = isset($args['issueId']) ? $args['issueId'] : null;

		import('controllers.grid.issues.form.IssueForm');
		$issueForm = new IssueForm($issueId);
		$issueForm->initData($request, $issueId);
		$json = new JSONMessage(true, $issueForm->fetch($request));
		return $json->getString();
	}

	/**
	 * Update a issue
	 * @param $args array
	 * @param $request PKPRequest
	 * @return string Serialized JSON object
	 */
	function updateIssue($args, $request) {
		$issue = $this->getAuthorizedContextObject(ASSOC_TYPE_ISSUE);
		$issueId = $issue?$issue->getId():null;

		import('controllers.grid.issues.form.IssueForm');
		$issueForm = new IssueForm($issueId);
		$issueForm->readInputData();

		if ($issueForm->validate($request, $issue)) {
			$issueId = $issueForm->execute($request, $issueId);
			return DAO::getDataChangedEvent($issueId);
		} else {
			$json = new JSONMessage(false);
			return $json->getString();
		}
	}

	/**
	 * An action to edit a issue's cover
	 * @param $args array
	 * @param $request PKPRequest
	 * @return string Serialized JSON object
	 */
	function editCover($args, $request) {
		$issueId = isset($args['issueId']) ? $args['issueId'] : null;

		import('controllers.grid.issues.form.CoverForm');
		$coverForm = new CoverForm($issueId);
		$coverForm->initData($request, $issueId);
		$json = new JSONMessage(true, $coverForm->fetch($request));
		return $json->getString();
	}

	/**
	 * Update an issue cover
	 * @param $args array
	 * @param $request PKPRequest
	 * @return string Serialized JSON object
	 */
	function updateCover($args, $request) {
		$issue = $this->getAuthorizedContextObject(ASSOC_TYPE_ISSUE);
		$issueId = $issue?$issue->getId():null;

		import('controllers.grid.issues.form.CoverForm');
		$coverForm = new CoverForm($issueId);
		$coverForm->readInputData();

		if ($coverForm->validate($request, $issue)) {
			$coverForm->execute($request, $issueId);
			return DAO::getDataChangedEvent($issueId);
		} else {
			$json = new JSONMessage(false);
			return $json->getString();
		}
	}

	/**
	 * Removes an issue
	 * @param $args array
	 * @param $request PKPRequest
	 */
	function deleteIssue($args, $request) {
		$issue = $this->getAuthorizedContextObject(ASSOC_TYPE_ISSUE);
		$issueId = $issue->getId();

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

		$issueDao = DAORegistry::getDAO('IssueDAO');
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

	/**
	 * Display the table of contents
	 * @param $request PKPRequest
	 */
	function issueToc($args, $request) {
		$issue = $this->getAuthorizedContextObject(ASSOC_TYPE_ISSUE);
		$issueId = $issue->getId();

		$templateMgr = TemplateManager::getManager($request);

		$journal = $request->getJournal();
		$templateMgr->assign('enablePublicArticleId', $journal->getSetting('enablePublicArticleId'));
		$templateMgr->assign('enablePageNumber', $journal->getSetting('enablePageNumber'));
		$sectionDao = DAORegistry::getDAO('SectionDAO');
		$templateMgr->assign('customSectionOrderingExists', $customSectionOrderingExists = $sectionDao->customSectionOrderingExists($issueId));

		$templateMgr->assign('issueId', $issueId);
		$templateMgr->assign('issue', $issue);
		$templateMgr->assign('unpublished', !$issue->getPublished());
		$templateMgr->assign('issueAccess', $issue->getAccessStatus());

		// get issue sections and articles
		$publishedArticleDao = DAORegistry::getDAO('PublishedArticleDAO');
		$publishedArticles = $publishedArticleDao->getPublishedArticles($issueId);

		$layoutEditorSubmissionDao = DAORegistry::getDAO('LayoutEditorSubmissionDAO');
		$proofedArticleIds = $layoutEditorSubmissionDao->getProofedArticlesByIssueId($issueId);
		$templateMgr->assign('proofedArticleIds', $proofedArticleIds);

		$currSection = 0;
		$counter = 0;
		$sections = array();
		$sectionCount = 0;
		$sectionDao = DAORegistry::getDAO('SectionDAO');
		foreach ($publishedArticles as $article) {
			$sectionId = $article->getSectionId();
			if ($currSection != $sectionId) {
				$lastSectionId = $currSection;
				$sectionCount++;
				if ($lastSectionId !== 0) $sections[$lastSectionId][5] = $customSectionOrderingExists?$sectionDao->getCustomSectionOrder($issueId, $sectionId):$sectionCount; // Store next custom order
				$currSection = $sectionId;
				$counter++;
				$sections[$sectionId] = array(
					$sectionId,
					$article->getSectionTitle(),
					array($article),
					$counter,
					$customSectionOrderingExists?
						$sectionDao->getCustomSectionOrder($issueId, $lastSectionId): // Last section custom ordering
						($sectionCount-1),
					null // Later populated with next section ordering
				);
			} else {
				$sections[$article->getSectionId()][2][] = $article;
			}
		}
		$templateMgr->assign_by_ref('sections', $sections);

		$templateMgr->assign('accessOptions', array(
			ARTICLE_ACCESS_ISSUE_DEFAULT => AppLocale::Translate('editor.issues.default'),
			ARTICLE_ACCESS_OPEN => AppLocale::Translate('editor.issues.open')
		));

		$json = new JSONMessage(true, $templateMgr->fetch('controllers/grid/issues/issueToc.tpl'));
		return $json->getString();
	}

	/**
	 * Updates issue table of contents with selected changes and article removals.
	 * @param $args array
	 * @param $request PKPRequest
	 */
	function updateIssueToc($args, $request) {
		$issue = $request->getAuthorizedContextObject(ASSOC_TYPE_ISSUE);
		$issueId = $issue->getId();

		$journal = $request->getAuthorizedContextObject(ASSOC_TYPE_JOURNAL);

		$removedPublishedArticles = array();

		$publishedArticles = $request->getUserVar('publishedArticles');
		$removedArticles = $request->getUserVar('remove');
		$accessStatus = $request->getUserVar('accessStatus');
		$pages = $request->getUserVar('pages');

		$articleDao = DAORegistry::getDAO('ArticleDAO');
		$publishedArticleDao = DAORegistry::getDAO('PublishedArticleDAO');
		$sectionDao = DAORegistry::getDAO('SectionDAO');

		$articles = $publishedArticleDao->getPublishedArticles($issueId);

		// insert article tombstone, if an article is removed from a published issue
		import('classes.article.ArticleTombstoneManager');
		$articleTombstoneManager = new ArticleTombstoneManager();
		$issueDao = DAORegistry::getDAO('IssueDAO');
		$issue = $issueDao->getById($issueId, $journal->getId());
		foreach($articles as $article) {
			$articleId = $article->getId();
			$pubId = $article->getPublishedArticleId();
			if (!isset($removedArticles[$articleId])) {
				if (isset($pages[$articleId])) {
					$article->setPages($pages[$articleId]);
				}
				if (isset($publishedArticles[$articleId])) {
					$journalDao = DAORegistry::getDAO('JournalDAO'); /* @var $journalDao JournalDAO */
					$publicArticleId = $publishedArticles[$articleId];
					if ($publicArticleId && $journalDao->anyPubIdExists($journal->getId(), 'publisher-id', $publicArticleId, ASSOC_TYPE_ARTICLE, $articleId)) {
						// We are not in a form so we cannot send form errors.
						// Let's at least send a notification to give some feedback
						// to the user.
						import('classes.notification.NotificationManager');
						$notificationManager = new NotificationManager();
						AppLocale::requireComponents(array(LOCALE_COMPONENT_APP_EDITOR));
						$message = 'editor.publicIdentificationExists';
						$params = array('publicIdentifier' => $publicArticleId);
						$user =& $request->getUser();
						$notificationManager->createTrivialNotification(
							$user->getId(), NOTIFICATION_TYPE_ERROR,
							array('contents' => __($message, $params))
						);
						$publicArticleId = '';
					}
					$article->setStoredPubId('publisher-id', $publicArticleId);
				}
				if (isset($accessStatus[$pubId])) {
					$publishedArticleDao->updatePublishedArticleField($pubId, 'access_status', $accessStatus[$pubId]);
				}
			} else {
				if ($issue->getPublished()) {
					$articleTombstoneManager->insertArticleTombstone($article, $journal);
				}
				$article->setStatus(STATUS_QUEUED);
				$article->stampStatusModified();

				// If the article is the only one in the section, delete the section from custom issue ordering
				$sectionId = $article->getSectionId();
				$publishedArticleArray =& $publishedArticleDao->getPublishedArticlesBySectionId($sectionId, $issueId);
				if (sizeof($publishedArticleArray) == 1) {
					$sectionDao->deleteCustomSection($issueId, $sectionId);
				}

				$publishedArticleDao->deletePublishedArticleById($pubId);
				$publishedArticleDao->resequencePublishedArticles($article->getSectionId(), $issueId);
			}
			$articleDao->updateObject($article);
		}

		$request->redirect(null, null, 'issueToc', $issueId);
	}

	/**
	 * Displays the issue galleys page.
	 * @param $args array
	 * @param $request PKPRequest
	 */
	function issueGalleys($args, $request) {
		$issue = $this->getAuthorizedContextObject(ASSOC_TYPE_ISSUE);
		$issueId = $issue->getId();

		$templateMgr = TemplateManager::getManager($request);
		import('classes.issue.IssueAction');
		$templateMgr->assign('issueId', $issueId);
		$templateMgr->assign('unpublished',!$issue->getPublished());
		$templateMgr->assign_by_ref('issue', $issue);

		$issueGalleyDao = DAORegistry::getDAO('IssueGalleyDAO');
		$templateMgr->assign_by_ref('issueGalleys', $issueGalleyDao->getGalleysByIssue($issue->getId()));

		$json = new JSONMessage(true, $templateMgr->fetch('controllers/grid/issues/issueGalleys.tpl'));
		return $json->getString();
	}

	/**
	 * Publish issue
	 * @param $args array
	 * @param $request Request
	 */
	function publishIssue($args, $request) {
		$issue = $this->getAuthorizedContextObject(ASSOC_TYPE_ISSUE);
		$issueId = $issue->getId();

		$journal = $request->getJournal();
		$journalId = $journal->getId();

		$articleSearchIndex = null;
		if (!$issue->getPublished()) {
			// Set the status of any attendant queued articles to STATUS_PUBLISHED.
			$publishedArticleDao = DAORegistry::getDAO('PublishedArticleDAO');
			$articleDao = DAORegistry::getDAO('ArticleDAO');
			$publishedArticles = $publishedArticleDao->getPublishedArticles($issueId);
			foreach ($publishedArticles as $publishedArticle) {
				$article = $articleDao->getById($publishedArticle->getId());
				if ($article && $article->getStatus() == STATUS_QUEUED) {
					$article->setStatus(STATUS_PUBLISHED);
					$article->stampStatusModified();
					$articleDao->updateObject($article);
					if (!$articleSearchIndex) {
						import('classes.search.ArticleSearchIndex');
						$articleSearchIndex = new ArticleSearchIndex();
					}
					$articleSearchIndex->articleMetadataChanged($publishedArticle);
				}
				// delete article tombstone
				$tombstoneDao = DAORegistry::getDAO('DataObjectTombstoneDAO');
				$tombstoneDao->deleteByDataObjectId($article->getId());
			}
		}

		$issue->setCurrent(1);
		$issue->setPublished(1);
		$issue->setDatePublished(Core::getCurrentDate());

		// If subscriptions with delayed open access are enabled then
		// update open access date according to open access delay policy
		if ($journal->getSetting('publishingMode') == PUBLISHING_MODE_SUBSCRIPTION && $journal->getSetting('enableDelayedOpenAccess')) {

			$delayDuration = $journal->getSetting('delayedOpenAccessDuration');
			$delayYears = (int)floor($delayDuration/12);
			$delayMonths = (int)fmod($delayDuration,12);

			$curYear = date('Y');
			$curMonth = date('n');
			$curDay = date('j');

			$delayOpenAccessYear = $curYear + $delayYears + (int)floor(($curMonth+$delayMonths)/12);
 			$delayOpenAccessMonth = (int)fmod($curMonth+$delayMonths,12);

			$issue->setAccessStatus(ISSUE_ACCESS_SUBSCRIPTION);
			$issue->setOpenAccessDate(date('Y-m-d H:i:s',mktime(0,0,0,$delayOpenAccessMonth,$curDay,$delayOpenAccessYear)));
		}

		$issueDao = DAORegistry::getDAO('IssueDAO');
		$issueDao->updateCurrent($journalId,$issue);

		if ($articleSearchIndex) $articleSearchIndex->articleChangesFinished();

		// Send a notification to associated users
		import('classes.notification.NotificationManager');
		$notificationManager = new NotificationManager();
		$roleDao = DAORegistry::getDAO('RoleDAO');
		$notificationUsers = array();
		$allUsers = $roleDao->getUsersByJournalId($journalId);
		while ($user = $allUsers->next()) {
			$notificationUsers[] = array('id' => $user->getId());
		}
		foreach ($notificationUsers as $userRole) {
			$notificationManager->createNotification(
				$request, $userRole['id'], NOTIFICATION_TYPE_PUBLISHED_ISSUE,
				$journalId
			);
		}
		$notificationManager->sendToMailingList($request,
			$notificationManager->createNotification(
				$request, UNSUBSCRIBED_USER_NOTIFICATION, NOTIFICATION_TYPE_PUBLISHED_ISSUE,
				$journalId
			)
		);

		$dispatcher = $request->getDispatcher();
		// FIXME: Find a better way to reload the containing tabs.
		// Without this, issues don't move between tabs properly.
		return $request->redirectUrlJson($dispatcher->url($request, ROUTE_PAGE, null, 'editor', 'issues'));
	}

	/**
	 * Unpublish a previously-published issue
	 * @param $args array
	 * @param $request PKPRequest
	 */
	function unpublishIssue($args, $request) {
		$issue = $this->getAuthorizedContextObject(ASSOC_TYPE_ISSUE);
		$issueId = $issue->getId();

		$journal = $request->getJournal();

		$issue->setCurrent(0);
		$issue->setPublished(0);
		$issue->setDatePublished(null);

		$issueDao = DAORegistry::getDAO('IssueDAO');
		$issueDao->updateObject($issue);

		// insert article tombstones for all articles
		import('classes.article.ArticleTombstoneManager');
		$articleTombstoneManager = new ArticleTombstoneManager();
		$publishedArticleDao = DAORegistry::getDAO('PublishedArticleDAO');
		$publishedArticles = $publishedArticleDao->getPublishedArticles($issueId);
		foreach ($publishedArticles as $article) {
			$articleTombstoneManager->insertArticleTombstone($article, $journal);
		}

		$dispatcher = $request->getDispatcher();
		// FIXME: Find a better way to reload the containing tabs.
		// Without this, issues don't move between tabs properly.
		return $request->redirectUrlJson($dispatcher->url($request, ROUTE_PAGE, null, 'editor', 'issues'));
	}
}

?>
