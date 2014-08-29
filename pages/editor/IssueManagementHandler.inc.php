<?php

/**
 * @file pages/editor/IssueManagementHandler.inc.php
 *
 * Copyright (c) 2013-2014 Simon Fraser University Library
 * Copyright (c) 2003-2014 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class IssueManagementHandler
 * @ingroup pages_editor
 *
 * @brief Handle requests for issue management in publishing.
 */

import('pages.editor.EditorHandler');

class IssueManagementHandler extends EditorHandler {
	/** issue associated with the request **/
	var $issue;

	/**
	 * Constructor
	 */
	function IssueManagementHandler() {
		parent::EditorHandler();
	}

	/**
	 * Displays the listings of future (unpublished) issues
	 * @param $args array
	 * @param $request PKPRequest
	 */
	function futureIssues($args, $request) {
		$this->validate(null, true);
		$this->setupTemplate(EDITOR_SECTION_ISSUES);

		$journal =& $request->getJournal();
		$issueDao =& DAORegistry::getDAO('IssueDAO');
		$rangeInfo = $this->getRangeInfo('issues');
		$templateMgr =& TemplateManager::getManager();
		$templateMgr->assign_by_ref('issues', $issueDao->getUnpublishedIssues($journal->getId(), $rangeInfo));
		$templateMgr->assign('helpTopicId', 'publishing.index');
		$templateMgr->display('editor/issues/futureIssues.tpl');
	}

	/**
	 * Displays the listings of back (published) issues
	 * @param $args array
	 * @param $request PKPRequest
	 */
	function backIssues($args, $request) {
		$this->validate();
		$this->setupTemplate(EDITOR_SECTION_ISSUES);

		$journal =& $request->getJournal();
		$issueDao =& DAORegistry::getDAO('IssueDAO');

		$rangeInfo = $this->getRangeInfo('issues');

		$templateMgr =& TemplateManager::getManager();

		$templateMgr->addJavaScript('lib/pkp/js/lib/jquery/plugins/jquery.tablednd.js');
		$templateMgr->addJavaScript('lib/pkp/js/functions/tablednd.js');

		$templateMgr->assign_by_ref('issues', $issueDao->getPublishedIssues($journal->getId(), $rangeInfo));

		$allIssuesIterator = $issueDao->getPublishedIssues($journal->getId());
		$issueMap = array();
		while ($issue =& $allIssuesIterator->next()) {
			$issueMap[$issue->getId()] = $issue->getIssueIdentification();
			unset($issue);
		}
		$templateMgr->assign('allIssues', $issueMap);
		$templateMgr->assign('rangeInfo', $rangeInfo);

		$currentIssue =& $issueDao->getCurrentIssue($journal->getId());
		$currentIssueId = $currentIssue?$currentIssue->getId():null;
		$templateMgr->assign('currentIssueId', $currentIssueId);

		$templateMgr->assign('helpTopicId', 'publishing.index');
		$templateMgr->assign('usesCustomOrdering', $issueDao->customIssueOrderingExists($journal->getId()));
		$templateMgr->display('editor/issues/backIssues.tpl');
	}

	/**
	 * Removes an issue
	 * @param $args array
	 * @param $request PKPRequest
	 */
	function removeIssue($args, $request) {
		$issueId = (int) array_shift($args);
		$this->validate($issueId);
		$issue =& $this->issue;
		$isBackIssue = $issue->getPublished() > 0 ? true: false;

		$journal =& $request->getJournal();

		// remove all published articles and return original articles to editing queue
		$articleDao =& DAORegistry::getDAO('ArticleDAO');
		$publishedArticleDao =& DAORegistry::getDAO('PublishedArticleDAO');
		$publishedArticles = $publishedArticleDao->getPublishedArticles($issueId);
		if (isset($publishedArticles) && !empty($publishedArticles)) {
			// Insert article tombstone if the issue is published
			import('classes.article.ArticleTombstoneManager');
			$articleTombstoneManager = new ArticleTombstoneManager();
			foreach ($publishedArticles as $article) {
				if ($isBackIssue) {
					$articleTombstoneManager->insertArticleTombstone($article, $journal);
				}
				$articleDao->changeArticleStatus($article->getId(),STATUS_QUEUED);
				$publishedArticleDao->deletePublishedArticleById($article->getPublishedArticleId());
			}
		}

		$issueDao =& DAORegistry::getDAO('IssueDAO');
		$issueDao->deleteIssue($issue);
		if ($issue->getCurrent()) {
			$issues = $issueDao->getPublishedIssues($journal->getId());
			if (!$issues->eof()) {
				$issue =& $issues->next();
				$issue->setCurrent(1);
				$issueDao->updateIssue($issue);
			}
		}

		if ($isBackIssue) {
			$request->redirect(null, null, 'backIssues');
		} else {
			$request->redirect(null, null, 'futureIssues');
		}
	}

	/**
	 * Displays the create issue form
	 * @param $args array
	 * @param $request PKPRequest
	 */
	function createIssue($args, $request) {
		$this->validate();
		$this->setupTemplate(EDITOR_SECTION_ISSUES);

		import('classes.issue.form.IssueForm');

		$templateMgr =& TemplateManager::getManager();
		import('classes.issue.IssueAction');
		$templateMgr->assign('issueOptions', IssueAction::getIssueOptions());
		$templateMgr->assign('helpTopicId', 'publishing.createIssue');

		if (checkPhpVersion('5.0.0')) { // WARNING: This form needs $this in constructor
			$issueForm = new IssueForm('editor/issues/createIssue.tpl');
		} else {
			$issueForm =& new IssueForm('editor/issues/createIssue.tpl');
		}

		if ($issueForm->isLocaleResubmit()) {
			$issueForm->readInputData();
		} else {
			$issueForm->initData();
		}
		$issueForm->display();
	}

	/**
	 * Saves the new issue form
	 * @param $args array
	 * @param $request PKPRequest
	 */
	function saveIssue($args, $request) {
		$this->validate();
		$this->setupTemplate(EDITOR_SECTION_ISSUES);

		import('classes.issue.form.IssueForm');
		if (checkPhpVersion('5.0.0')) { // WARNING: This form needs $this in constructor
			$issueForm = new IssueForm('editor/issues/createIssue.tpl');
		} else {
			$issueForm =& new IssueForm('editor/issues/createIssue.tpl');
		}

		$issueForm->readInputData();

		if ($issueForm->validate()) {
			$issueForm->execute();
			$this->futureIssues($args, $request);
		} else {
			$templateMgr =& TemplateManager::getManager();
			import('classes.issue.IssueAction');
			$templateMgr->assign('issueOptions', IssueAction::getIssueOptions());
			$templateMgr->assign('helpTopicId', 'publishing.createIssue');
			$issueForm->display();
		}
	}

	/**
	 * Displays the issue data page
	 * @param $args array
	 * @param $request PKPRequest
	 */
	function issueData($args, $request) {
		$issueId = (int) array_shift($args);
		$this->validate($issueId, true);
		$issue =& $this->issue;
		$this->setupTemplate(EDITOR_SECTION_ISSUES);

		$templateMgr =& TemplateManager::getManager();
		import('classes.issue.IssueAction');
		$templateMgr->assign('issueOptions', IssueAction::getIssueOptions());

		import('classes.issue.form.IssueForm');

		if (checkPhpVersion('5.0.0')) { // WARNING: This form needs $this in constructor
			$issueForm = new IssueForm('editor/issues/issueData.tpl');
		} else {
			$issueForm =& new IssueForm('editor/issues/issueData.tpl');
		}

		if ($issueForm->isLocaleResubmit()) {
			$issueForm->readInputData();
		} else {
			$issueId = $issueForm->initData($issueId);
		}
		$templateMgr->assign('issueId', $issueId);

		$templateMgr->assign_by_ref('issue', $issue);
		$templateMgr->assign('unpublished',!$issue->getPublished());
		$templateMgr->assign('helpTopicId', 'publishing.index');
		$issueForm->display();
	}

	/**
	 * Edit the current issue form
	 * @param $args array
	 * @param $request PKPRequest
	 */
	function editIssue($args, $request) {
		$issueId = (int) array_shift($args);
		$this->validate($issueId, true);
		$issue =& $this->issue;
		$this->setupTemplate(EDITOR_SECTION_ISSUES);

		$templateMgr =& TemplateManager::getManager();
		$templateMgr->assign('issueId', $issueId);

		$journal =& $request->getJournal();
		$journalId = $journal->getId();

		import('classes.issue.IssueAction');
		$templateMgr->assign('issueOptions', IssueAction::getIssueOptions());

		import('classes.issue.form.IssueForm');
		if (checkPhpVersion('5.0.0')) { // WARNING: This form needs $this in constructor
			$issueForm = new IssueForm('editor/issues/issueData.tpl');
		} else {
			$issueForm =& new IssueForm('editor/issues/issueData.tpl');
		}
		$issueForm->readInputData();

		if ($issueForm->validate($issue)) {
			$issueForm->execute($issueId);
			$issueForm->initData($issueId);
		}

		$templateMgr->assign_by_ref('issue', $issue);
		$templateMgr->assign('unpublished',!$issue->getPublished());

		$issueForm->display();
	}

	/**
	 * Remove cover page from issue
	 * @param $args array
	 * @param $request PKPRequest
	 */
	function removeIssueCoverPage($args, $request) {
		$issueId = (int) array_shift($args);
		$this->validate($issueId, true);

		$formLocale = array_shift($args);
		if (!AppLocale::isLocaleValid($formLocale)) {
			$request->redirect(null, null, 'issueData', $issueId);
		}

		$journal =& $request->getJournal();
		$issue =& $this->issue;

		import('classes.file.PublicFileManager');
		$publicFileManager = new PublicFileManager();
		$publicFileManager->removeJournalFile($journal->getId(),$issue->getFileName($formLocale));
		$issue->setFileName('', $formLocale);
		$issue->setOriginalFileName('', $formLocale);
		$issue->setWidth('', $formLocale);
		$issue->setHeight('', $formLocale);

		$issueDao =& DAORegistry::getDAO('IssueDAO');
		$issueDao->updateIssue($issue);

		$request->redirect(null, null, 'issueData', $issueId);
	}

	/**
	 * Remove style file from issue
	 * @param $args array
	 * @param $request PKPRequest
	 */
	function removeStyleFile($args, $request) {
		$issueId = (int) array_shift($args);
		$this->validate($issueId, true);
		$issue =& $this->issue;

		import('classes.file.PublicFileManager');
		$journal =& $request->getJournal();
		$publicFileManager = new PublicFileManager();
		$publicFileManager->removeJournalFile($journal->getId(),$issue->getStyleFileName());
		$issue->setStyleFileName('');
		$issue->setOriginalStyleFileName('');

		$issueDao =& DAORegistry::getDAO('IssueDAO');
		$issueDao->updateIssue($issue);

		$request->redirect(null, null, 'issueData', $issueId);
	}

	/**
	 * Displays the issue galleys page.
	 * @param $args array
	 * @param $request PKPRequest
	 */
	function issueGalleys($args, $request) {
		$issueId = (int) array_shift($args);
		$this->validate($issueId, true);
		$issue =& $this->issue;
		$this->setupTemplate(EDITOR_SECTION_ISSUES);

		$templateMgr =& TemplateManager::getManager();
		import('classes.issue.IssueAction');
		$templateMgr->assign('issueOptions', IssueAction::getIssueOptions());

		$templateMgr->assign('issueId', $issueId);
		$templateMgr->assign('unpublished',!$issue->getPublished());
		$templateMgr->assign('helpTopicId', 'publishing.index');
		$templateMgr->assign_by_ref('issue', $issue);

		$issueGalleyDao =& DAORegistry::getDAO('IssueGalleyDAO');
		$templateMgr->assign_by_ref('issueGalleys', $issueGalleyDao->getGalleysByIssue($issue->getId()));

		$templateMgr->display('editor/issues/issueGalleys.tpl');
	}

	/**
	 * Create a new issue galley with the uploaded file.
	 * @param $args array
	 * @param $request PKPRequest
	 */
	function uploadIssueGalley($args, $request) {
		$issueId = (int) array_shift($args);
		$this->validate($issueId, true);

		import('classes.issue.form.IssueGalleyForm');
		if (checkPhpVersion('5.0.0')) { // WARNING: This form needs $this in constructor
			$galleyForm = new IssueGalleyForm($issueId);
		} else {
			$galleyForm =& new IssueGalleyForm($issueId);
		}

		$galleyId = $galleyForm->execute();
		$request->redirect(null, null, 'editIssueGalley', array($issueId, $galleyId));
	}

	/**
	 * Edit an issue galley.
	 * @param $args array ($issueId, $galleyId)
	 * @param $request PKPRequest
	 */
	function editIssueGalley($args, $request) {
		$issueId = (int) array_shift($args);
		$galleyId = (int) array_shift($args);

		$this->validate($issueId, true);
		$this->setupTemplate(EDITOR_SECTION_ISSUES);

		import('classes.issue.form.IssueGalleyForm');
		if (checkPhpVersion('5.0.0')) { // WARNING: This form needs $this in constructor
			$submitForm = new IssueGalleyForm($issueId, $galleyId);
		} else {
			$submitForm =& new IssueGalleyForm($issueId, $galleyId);
		}

		if ($submitForm->isLocaleResubmit()) {
			$submitForm->readInputData();
		} else {
			$submitForm->initData();
		}
		$submitForm->display();
	}

	/**
	 * Save changes to an issue galley.
	 * @param $args array ($issueId, $galleyId)
	 * @param $request PKPRequest
	 */
	function saveIssueGalley($args, $request) {
		$issueId = (int) array_shift($args);
		$galleyId = (int) array_shift($args);

		$this->validate($issueId, true);
		$this->setupTemplate(EDITOR_SECTION_ISSUES);

		import('classes.issue.form.IssueGalleyForm');
		if (checkPhpVersion('5.0.0')) { // WARNING: This form needs $this in constructor
			$submitForm = new IssueGalleyForm($issueId, $galleyId);
		} else {
			$submitForm =& new IssueGalleyForm($issueId, $galleyId);
		}

		$submitForm->readInputData();
		if ($submitForm->validate()) {
			$submitForm->execute();
			$request->redirect(null, null, 'issueGalleys', $issueId);
		} else {
			$submitForm->display();
		}
	}

	/**
	 * Change the sequence order of an issue galley.
	 * @param $args array
	 * @param $request PKPRequest
	 */
	function orderIssueGalley($args, $request) {
		$issueId = (int) $request->getUserVar('issueId');
		$galleyId = (int) $request->getUserVar('galleyId');
		$direction = $request->getUserVar('d');

		$this->validate($issueId, true);

		$galleyDao =& DAORegistry::getDAO('IssueGalleyDAO');
		$galley =& $galleyDao->getGalley($galleyId, $issueId);

		if (isset($galley)) {
			$galley->setSequence($galley->getSequence() + ($direction == 'u' ? -1.5 : 1.5));
			$galleyDao->updateGalley($galley);
			$galleyDao->resequenceGalleys($issueId);
		}
		$request->redirect(null, null, 'issueGalleys', $issueId);
	}

	/**
	 * Delete an issue galley.
	 * @param $args array ($issueId, $galleyId)
	 * @param $request PKPRequest
	 */
	function deleteIssueGalley($args, $request) {
		$issueId = (int) array_shift($args);
		$galleyId = (int) array_shift($args);

		$this->validate($issueId, true);

		$galleyDao =& DAORegistry::getDAO('IssueGalleyDAO');
		$galley =& $galleyDao->getGalley($galleyId, $issueId);

		if (isset($galley)) {
			import('classes.file.IssueFileManager');
			$issueFileManager = new IssueFileManager($issueId);

			if ($galley->getFileId()) {
				$issueFileManager->deleteFile($galley->getFileId());
			}
			$galleyDao->deleteGalley($galley);
		}
		$request->redirect(null, null, 'issueGalleys', $issueId);
	}

	/**
	 * Preview an issue galley.
	 * @param $args array ($issueId, $galleyId)
	 * @param $request PKPRequest
	 */
	function proofIssueGalley($args, $request) {
		$issueId = (int) array_shift($args);
		$galleyId = (int) array_shift($args);

		$this->validate($issueId, true);
		$this->setupTemplate(EDITOR_SECTION_ISSUES);

		$templateMgr =& TemplateManager::getManager();
		$templateMgr->assign('issueId', $issueId);
		$templateMgr->assign('galleyId', $galleyId);
		$templateMgr->display('editor/issues/proofIssueGalley.tpl');
	}

	/**
	 * Proof issue galley (shows frame header).
	 * @param $args array ($issueId, $galleyId)
	 * @param $request PKPRequest
	 */
	function proofIssueGalleyTop($args, $request) {
		$issueId = (int) array_shift($args);
		$galleyId = (int) array_shift($args);

		$this->validate($issueId, true);
		$this->setupTemplate(EDITOR_SECTION_ISSUES);

		$templateMgr =& TemplateManager::getManager();
		$templateMgr->assign('issueId', $issueId);
		$templateMgr->assign('galleyId', $galleyId);
		$templateMgr->display('editor/issues/proofIssueGalleyTop.tpl');
	}

	/**
	 * Preview an issue galley (outputs file contents).
	 * @param $args array ($issueId, $galleyId)
	 * @param $request PKPRequest
	 */
	function proofIssueGalleyFile($args, $request) {
		$issueId = (int) array_shift($args);
		$galleyId = (int) array_shift($args);

		$this->validate($issueId, true);

		$galleyDao =& DAORegistry::getDAO('IssueGalleyDAO');
		$galley =& $galleyDao->getGalley($galleyId, $issueId);

		if (isset($galley)) {
			if ($galley->getFileId()) {
				import('classes.file.IssueFileManager');
				$issueFileManager = new IssueFileManager($issueId);
				return $issueFileManager->downloadFile($galley->getFileId());
			}
		}
		$request->redirect(null, null, 'issueGalleys', $issueId);
	}

	/**
	 * Download an issue file.
	 * @param $args array ($issueId, $fileId)
	 * @param $request PKPRequest
	 */
	function downloadIssueFile($args, $request) {
		$issueId = (int) array_shift($args);
		$fileId = (int) array_shift($args);

		$this->validate($issueId, true);

		if ($fileId) {
			import('classes.file.IssueFileManager');
			$issueFileManager = new IssueFileManager($issueId);
			return $issueFileManager->downloadFile($fileId);
		}
		$request->redirect(null, null, 'issueGalleys', $issueId);
	}

	/**
	 * Display the table of contents
	 * @param $request PKPRequest
	 */
	function issueToc($args, $request) {
		$issueId = (int) array_shift($args);
		$this->validate($issueId, true);
		$issue =& $this->issue;
		$this->setupTemplate(EDITOR_SECTION_ISSUES);

		$templateMgr =& TemplateManager::getManager();

		$journal =& $request->getJournal();
		$journalId = $journal->getId();

		$journalSettingsDao =& DAORegistry::getDAO('JournalSettingsDAO');
		$sectionDao =& DAORegistry::getDAO('SectionDAO');

		$enablePublicArticleId = $journalSettingsDao->getSetting($journalId,'enablePublicArticleId');
		$templateMgr->assign('enablePublicArticleId', $enablePublicArticleId);
		$enablePageNumber = $journalSettingsDao->getSetting($journalId, 'enablePageNumber');
		$templateMgr->assign('enablePageNumber', $enablePageNumber);
		$templateMgr->assign('customSectionOrderingExists', $customSectionOrderingExists = $sectionDao->customSectionOrderingExists($issueId));

		$templateMgr->assign('issueId', $issueId);
		$templateMgr->assign_by_ref('issue', $issue);
		$templateMgr->assign('unpublished', !$issue->getPublished());
		$templateMgr->assign('issueAccess', $issue->getAccessStatus());

		// get issue sections and articles
		$publishedArticleDao =& DAORegistry::getDAO('PublishedArticleDAO');
		$publishedArticles = $publishedArticleDao->getPublishedArticles($issueId);

		$layoutEditorSubmissionDao =& DAORegistry::getDAO('LayoutEditorSubmissionDAO');
		$proofedArticleIds = $layoutEditorSubmissionDao->getProofedArticlesByIssueId($issueId);
		$templateMgr->assign('proofedArticleIds', $proofedArticleIds);

		$currSection = 0;
		$counter = 0;
		$sections = array();
		$sectionCount = 0;
		$sectionDao =& DAORegistry::getDAO('SectionDAO');
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

		import('classes.issue.IssueAction');
		$templateMgr->assign('issueOptions', IssueAction::getIssueOptions());
		$templateMgr->assign('helpTopicId', 'publishing.tableOfContents');

		$templateMgr->addJavaScript('lib/pkp/js/lib/jquery/plugins/jquery.tablednd.js');
		$templateMgr->addJavaScript('lib/pkp/js/functions/tablednd.js');

		$templateMgr->display('editor/issues/issueToc.tpl');
	}

	/**
	 * Updates issue table of contents with selected changes and article removals.
	 * @param $args array
	 * @param $request PKPRequest
	 */
	function updateIssueToc($args, $request) {
		$issueId = (int) array_shift($args);
		$this->validate($issueId, true);

		$journal =& $request->getJournal();

		$removedPublishedArticles = array();

		$publishedArticles = $request->getUserVar('publishedArticles');
		$removedArticles = $request->getUserVar('remove');
		$accessStatus = $request->getUserVar('accessStatus');
		$pages = $request->getUserVar('pages');

		$articleDao =& DAORegistry::getDAO('ArticleDAO');
		$publishedArticleDao =& DAORegistry::getDAO('PublishedArticleDAO');
		$sectionDao =& DAORegistry::getDAO('SectionDAO');

		$articles = $publishedArticleDao->getPublishedArticles($issueId);

		// insert article tombstone, if an article is removed from a published issue
		import('classes.article.ArticleTombstoneManager');
		$articleTombstoneManager = new ArticleTombstoneManager();
		$issueDao =& DAORegistry::getDAO('IssueDAO');
		$issue =& $issueDao->getIssueById($issueId, $journal->getId());
		foreach($articles as $article) {
			$articleId = $article->getId();
			$pubId = $article->getPublishedArticleId();
			if (!isset($removedArticles[$articleId])) {
				if (isset($pages[$articleId])) {
					$article->setPages($pages[$articleId]);
				}
				if (isset($publishedArticles[$articleId])) {
					$journalDao =& DAORegistry::getDAO('JournalDAO'); /* @var $journalDao JournalDAO */
					$publicArticleId = $publishedArticles[$articleId];
					if ($publicArticleId && $journalDao->anyPubIdExists($journal->getId(), 'publisher-id', $publicArticleId, ASSOC_TYPE_ARTICLE, $articleId)) {
						// We are not in a form so we cannot send form errors.
						// Let's at least send a notification to give some feedback
						// to the user.
						import('classes.notification.NotificationManager');
						$notificationManager = new NotificationManager();
						AppLocale::requireComponents(array(LOCALE_COMPONENT_OJS_EDITOR));
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
			$articleDao->updateArticle($article);
		}

		$request->redirect(null, null, 'issueToc', $issueId);
	}

	/**
	 * Change the sequence of an issue.
	 * @param $args array
	 * @param $request PKPRequest
	 */
	function setCurrentIssue($args, $request) {
		$issueId = $request->getUserVar('issueId');
		$journal =& $request->getJournal();
		$issueDao =& DAORegistry::getDAO('IssueDAO');
		if ($issueId) {
			$this->validate($issueId);
			$issue =& $this->issue;
			$issue->setCurrent(1);
			$issueDao->updateCurrentIssue($journal->getId(), $issue);
		} else {
			$this->validate();
			$issueDao->updateCurrentIssue($journal->getId());
		}
		$request->redirect(null, null, 'backIssues');
	}

	/**
	 * Change the sequence of an issue.
	 * @param $args array
	 * @param $request PKPRequest
	 */
	function moveIssue($args, $request) {
		$issueId = (int) $request->getUserVar('id');
		$this->validate($issueId);
		$prevId = (int) $request->getUserVar('prevId');
		$nextId = (int) $request->getUserVar('nextId');
		$issue =& $this->issue;
		$journal =& $request->getJournal();
		$journalId = $journal->getId();

		$issueDao =& DAORegistry::getDAO('IssueDAO');

		// If custom issue ordering isn't yet in place, bring it in.
		if (!$issueDao->customIssueOrderingExists($journalId)) {
			$issueDao->setDefaultCustomIssueOrders($journalId);
		}

		$direction = $request->getUserVar('d');
		if ($direction) {
			// Moved using up or down arrow
			$newPos = $issueDao->getCustomIssueOrder($journalId, $issueId) + ($direction == 'u' ? -1.5 : +1.5);
		} else {
			// Drag and Drop
			if ($nextId)
				// we are dropping before the next row
				$newPos = $issueDao->getCustomIssueOrder($journalId, $nextId) - 0.5;
			else
				// we are dropping after the previous row
				$newPos = $issueDao->getCustomIssueOrder($journalId, $prevId) + 0.5;
		}
		$issueDao->moveCustomIssueOrder($journal->getId(), $issueId, $newPos);

		if ($direction) {
			// Only redirect the nonajax call
			$request->redirect(null, null, 'backIssues', null, array("issuesPage" => $request->getUserVar('issuesPage')));
		}
	}

	/**
	 * Reset issue ordering to defaults.
	 * @param $args array
	 * @param $request PKPRequest
	 */
	function resetIssueOrder($args, $request) {
		$this->validate();

		$journal =& $request->getJournal();

		$issueDao =& DAORegistry::getDAO('IssueDAO');
		$issueDao->deleteCustomIssueOrdering($journal->getId());

		$request->redirect(null, null, 'backIssues');
	}

	/**
	 * Change the sequence of a section.
	 * @param $args array
	 * @param $request PKPRequest
	 */
	function moveSectionToc($args, $request) {
		$issueId = (int) array_shift($args);
		$this->validate($issueId, true);
		$issue =& $this->issue;
		$journal =& $request->getJournal();

		$sectionDao =& DAORegistry::getDAO('SectionDAO');
		$section =& $sectionDao->getSection($request->getUserVar('sectionId'), $journal->getId());

		if ($section != null) {
			// If issue-specific section ordering isn't yet in place, bring it in.
			if (!$sectionDao->customSectionOrderingExists($issueId)) {
				$sectionDao->setDefaultCustomSectionOrders($issueId);
			}

			$sectionDao->moveCustomSectionOrder($issueId, $section->getId(), $request->getUserVar('newPos'), $request->getUserVar('d') == 'u');
		}

		$request->redirect(null, null, 'issueToc', $issueId);
	}

	/**
	 * Reset section ordering to section defaults.
	 * @param $args array
	 * @param $request PKPRequest
	 */
	function resetSectionOrder($args, $request) {
		$issueId = (int) array_shift($args);
		$this->validate($issueId, true);
		$issue =& $this->issue;

		$sectionDao =& DAORegistry::getDAO('SectionDAO');
		$sectionDao->deleteCustomSectionOrdering($issueId);

		$request->redirect(null, null, 'issueToc', $issue->getId());
	}

	/**
	 * Change the sequence of the articles.
	 * @param $args array
	 * @param $request PKPRequest
	 */
	function moveArticleToc($args, $request) {
		$this->validate(null, true);
		$pubId = (int) $request->getUserVar('id');

		$journal =& $request->getJournal();

		$publishedArticleDao =& DAORegistry::getDAO('PublishedArticleDAO');
		$issueDao =& DAORegistry::getDAO('IssueDAO');

		$publishedArticle =& $publishedArticleDao->getPublishedArticleById($pubId);

		if (!$publishedArticle) $request->redirect(null, null, 'index');

		$articleId = $publishedArticle->getId();
		$articleDao =& DAORegistry::getDAO('ArticleDAO');
		$article =& $articleDao->getArticle($articleId, $journal->getId());

		$issue =& $issueDao->getIssueById($publishedArticle->getIssueId());

		if (!$article || !$issue || $publishedArticle->getIssueId() != $issue->getId() || $issue->getJournalId() != $journal->getId()) $request->redirect(null, null, 'index');

		if ($d = $request->getUserVar('d')) {
			// Moving by up/down arrows
			$publishedArticle->setSeq(
				$publishedArticle->getSeq() + ($d == 'u' ? -1.5 : 1.5)
			);
		} else {
			// Moving by drag 'n' drop
			$prevId = (int) $request->getUserVar('prevId');
			if (!$prevId) {
				$nextId = (int) $request->getUserVar('nextId');
				$nextArticle = $publishedArticleDao->getPublishedArticleById($nextId);
				$publishedArticle->setSeq($nextArticle->getSeq() - .5);
			} else {
				$prevArticle = $publishedArticleDao->getPublishedArticleById($prevId);
				$publishedArticle->setSeq($prevArticle->getSeq() + .5);
			}
		}
		$publishedArticleDao->updatePublishedArticle($publishedArticle);
		$publishedArticleDao->resequencePublishedArticles($article->getSectionId(), $issue->getId());

		// Only redirect if we're not doing drag and drop
		if ($d) {
			$request->redirect(null, null, 'issueToc', $publishedArticle->getIssueId());
		}
	}

	/**
	 * Publish issue
	 * @param $args array
	 * @param $request Request
	 */
	function publishIssue($args, $request) {
		$issueId = (int) array_shift($args);
		$this->validate($issueId);
		$issue =& $this->issue;

		$journal =& $request->getJournal();
		$journalId = $journal->getId();

		$articleSearchIndex = null;
		if (!$issue->getPublished()) {
			// Set the status of any attendant queued articles to STATUS_PUBLISHED.
			$publishedArticleDao =& DAORegistry::getDAO('PublishedArticleDAO');
			$articleDao =& DAORegistry::getDAO('ArticleDAO');
			$publishedArticles =& $publishedArticleDao->getPublishedArticles($issueId);
			foreach ($publishedArticles as $publishedArticle) {
				$article =& $articleDao->getArticle($publishedArticle->getId());
				if ($article && $article->getStatus() == STATUS_QUEUED) {
					$article->setStatus(STATUS_PUBLISHED);
					$article->stampStatusModified();
					$articleDao->updateArticle($article);
					
					// Call initialize permissions again to check if copyright year needs to be initialized.
					$article->initializePermissions();
					$articleDao->updateLocaleFields($article);
				
					if (!$articleSearchIndex) {
						import('classes.search.ArticleSearchIndex');
						$articleSearchIndex = new ArticleSearchIndex();
					}
					$articleSearchIndex->articleMetadataChanged($publishedArticle);
				}
				// delete article tombstone
				$tombstoneDao =& DAORegistry::getDAO('DataObjectTombstoneDAO');
				$tombstoneDao->deleteByDataObjectId($article->getId());
				unset($article);
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

		$issueDao =& DAORegistry::getDAO('IssueDAO');
		$issueDao->updateCurrentIssue($journalId,$issue);

		if ($articleSearchIndex) $articleSearchIndex->articleChangesFinished();

		// Send a notification to associated users
		import('classes.notification.NotificationManager');
		$notificationManager = new NotificationManager();
		$roleDao =& DAORegistry::getDAO('RoleDAO');
		$notificationUsers = array();
		$allUsers = $roleDao->getUsersByJournalId($journalId);
		while (!$allUsers->eof()) {
			$user =& $allUsers->next();
			$notificationUsers[] = array('id' => $user->getId());
			unset($user);
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

		$request->redirect(null, null, 'issueToc', $issue->getId());
	}

	/**
	 * Unpublish a previously-published issue
	 * @param $args array
	 * @param $request PKPRequest
	 */
	function unpublishIssue($args, $request) {
		$issueId = (int) array_shift($args);
		$this->validate($issueId);
		$issue =& $this->issue;

		$journal =& $request->getJournal();

		$issue->setCurrent(0);
		$issue->setPublished(0);
		$issue->setDatePublished(null);

		$issueDao =& DAORegistry::getDAO('IssueDAO');
		$issueDao->updateIssue($issue);

		// insert article tombstones for all articles
		import('classes.article.ArticleTombstoneManager');
		$articleTombstoneManager = new ArticleTombstoneManager();
		$publishedArticleDao =& DAORegistry::getDAO('PublishedArticleDAO');
		$publishedArticles =& $publishedArticleDao->getPublishedArticles($issueId);
		foreach ($publishedArticles as $article) {
			$articleTombstoneManager->insertArticleTombstone($article, $journal);
		}
		$request->redirect(null, null, 'futureIssues');
	}

	/**
	 * Allows editors to write emails to users associated with the journal.
	 * @param $args array
	 * @param $request PKPRequest
	 */
	function notifyUsers($args, $request) {
		$this->validate((int) $request->getUserVar('issue'));
		$issue =& $this->issue;
		$this->setupTemplate(EDITOR_SECTION_ISSUES);

		$issueDao =& DAORegistry::getDAO('IssueDAO');
		$roleDao =& DAORegistry::getDAO('RoleDAO');
		$authorDao =& DAORegistry::getDAO('AuthorDAO');
		$individualSubscriptionDao =& DAORegistry::getDAO('IndividualSubscriptionDAO');
		$institutionalSubscriptionDao =& DAORegistry::getDAO('InstitutionalSubscriptionDAO');
		$notificationMailListDao =& DAORegistry::getDAO('NotificationMailListDAO'); /* @var $notificationMailListDao NotificationMailListDAO */

		$journal =& $request->getJournal();
		$user =& $request->getUser();
		$templateMgr =& TemplateManager::getManager();

		import('lib.pkp.classes.mail.MassMail');
		$email = new MassMail('PUBLISH_NOTIFY');

		if ($request->getUserVar('send') && !$email->hasErrors()) {
			if($request->getUserVar('ccSelf')) {
				$email->addRecipient($user->getEmail(), $user->getFullName());
			}

			switch ($request->getUserVar('whichUsers')) {
				case 'allIndividualSubscribers':
					$recipients =& $individualSubscriptionDao->getSubscribedUsers($journal->getId());
					break;
				case 'allInstitutionalSubscribers':
					$recipients =& $institutionalSubscriptionDao->getSubscribedUsers($journal->getId());
					break;
				case 'allAuthors':
					$recipients =& $authorDao->getAuthorsAlphabetizedByJournal($journal->getId(), null, null, true);
					break;
				case 'allUsers':
					$recipients =& $roleDao->getUsersByJournalId($journal->getId());
					break;
				case 'allReaders':
					$recipients =& $roleDao->getUsersByRoleId(
						ROLE_ID_READER,
						$journal->getId()
					);
					break;
				default:
					$recipients = null;
			}

			import('lib.pkp.classes.validation.ValidatorEmail');
			while ($recipients && !$recipients->eof()) {
				$recipient =& $recipients->next();
				if (preg_match(ValidatorEmail::getRegexp(), $recipient->getEmail())) {
					$email->addRecipient($recipient->getEmail(), $recipient->getFullName());
				} else {
					error_log("Invalid email address: " . $recipient->getEmail());
				}
				unset($recipient);
			}

			if($request->getUserVar('sendToMailList')) {
				$mailList = $notificationMailListDao->getMailList($journal->getId());
				foreach ($mailList as $mailListRecipient) {
					$email->addRecipient($mailListRecipient);
				}
			}


			if ($request->getUserVar('includeToc')=='1' && isset($issue)) {
				$issue = $issueDao->getIssueById($request->getUserVar('issue'));

				$publishedArticleDao =& DAORegistry::getDAO('PublishedArticleDAO');
				$publishedArticles =& $publishedArticleDao->getPublishedArticlesInSections($issue->getId());

				$templateMgr->assign_by_ref('journal', $journal);
				$templateMgr->assign_by_ref('issue', $issue);
				$templateMgr->assign('body', $email->getBody());
				$templateMgr->assign_by_ref('publishedArticles', $publishedArticles);

				$email->setBody($templateMgr->fetch('editor/notifyUsersEmail.tpl'));

				// Stamp the "users notified" date.
				$issue->setDateNotified(Core::getCurrentDate());
				$issueDao->updateIssue($issue);
			}

			$callback = array(&$email, 'send');
			$templateMgr->setProgressFunction($callback);
			unset($callback);

			$email->setFrequency(10); // 10 emails per callback
			$callback = array(&$templateMgr, 'updateProgressBar');
			$email->setCallback($callback);
			unset($callback);

			$templateMgr->assign('message', 'editor.notifyUsers.inProgress');
			$templateMgr->display('common/progress.tpl');
			echo '<script type="text/javascript">window.location = "' . $request->url(null, 'editor') . '";</script>';
		} else {
			if (!$request->getUserVar('continued')) {
				$email->assignParams(array(
					'editorialContactSignature' => $user->getContactSignature()
				));
			}

			$issuesIterator =& $issueDao->getIssues($journal->getId());

			$allUsersCount = $roleDao->getJournalUsersCount($journal->getId());

			// FIXME: There should be a better way of doing this.
			$authors =& $authorDao->getAuthorsAlphabetizedByJournal($journal->getId(), null, null, true);
			$authorCount = $authors->getCount();


			$email->displayEditForm(
				$request->url(null, null, 'notifyUsers'),
				array(),
				'editor/notifyUsers.tpl',
				array(
					'issues' => $issuesIterator,
					'allUsersCount' => $allUsersCount,
					'allReadersCount' => $roleDao->getJournalUsersCount($journal->getId(), ROLE_ID_READER),
					'allAuthorsCount' => $authorCount,
					'allIndividualSubscribersCount' => $individualSubscriptionDao->getSubscribedUserCount($journal->getId()),
					'allInstitutionalSubscribersCount' => $institutionalSubscriptionDao->getSubscribedUserCount($journal->getId()),
					'allMailListCount' => count($notificationMailListDao->getMailList($journal->getId()))
				)
			);
		}
	}

	/**
	 * Validate that user is an editor in the selected journal and if the issue id is valid
	 * Redirects to issue create issue page if not properly authenticated.
	 * NOTE: As of OJS 2.2, Layout Editors are allowed if specified in args.
	 */
	function validate($issueId = null, $allowLayoutEditor = false) {
		$issue = null;
		$journal =& Request::getJournal();

		if (!isset($journal)) Validation::redirectLogin();

		if (!empty($issueId)) {
			$issueDao =& DAORegistry::getDAO('IssueDAO');
			$issue = $issueDao->getIssueById($issueId, $journal->getId());

			if (!$issue) {
				Request::redirect(null, null, 'createIssue');
			}
		}


		if (!Validation::isEditor($journal->getId())) {
			if (isset($journal) && $allowLayoutEditor && Validation::isLayoutEditor($journal->getId())) {
				// We're a Layout Editor. If specified, make sure that the issue is not published.
				if ($issue && !$issue->getPublished()) {
					Validation::redirectLogin();
				}
			} else {
				Validation::redirectLogin();
			}
		}

		$this->issue =& $issue;
		return true;
	}

	/**
	 * Setup common template variables.
	 * @param $level int set to one of EDITOR_SECTION_? defined in EditorHandler.
	 */
	function setupTemplate($level) {
		parent::setupTemplate($level);

		$templateMgr =& TemplateManager::getManager();
		$templateMgr->assign('isLayoutEditor', Request::getRequestedPage() == 'layoutEditor');
	}
}
