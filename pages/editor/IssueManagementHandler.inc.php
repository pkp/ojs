<?php

/**
 * IssueManagementHandler.inc.php
 *
 * Copyright (c) 2003-2004 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package pages.editor
 *
 * Handle requests for issue management in publishing.
 *
 * $Id$
 */

class IssueManagementHandler extends EditorHandler {

	/**
	 * Displays the listings of future (unpublished) issues
	 */
	function futureIssues() {
		IssueManagementHandler::validate();
		IssueManagementHandler::setupTemplate(EDITOR_SECTION_ISSUES);

		$journal = &Request::getJournal();
		$issueDao = &DAORegistry::getDAO('IssueDAO');
		$rangeInfo = Handler::getRangeInfo('issues');
		$templateMgr = &TemplateManager::getManager();
		$templateMgr->assign_by_ref('issues', $issueDao->getUnpublishedIssues($journal->getJournalId(), false, $rangeInfo));
		$templateMgr->assign('helpTopicId', 'publishing.index');
		$templateMgr->display('editor/issues/futureIssues.tpl');
	}

	/**
	 * Displays the listings of back (published) issues
	 */
	function backIssues() {
		IssueManagementHandler::validate();
		IssueManagementHandler::setupTemplate(EDITOR_SECTION_ISSUES);

		$journal = &Request::getJournal();
		$issueDao = &DAORegistry::getDAO('IssueDAO');

		$rangeInfo = Handler::getRangeInfo('issues');

		$templateMgr = &TemplateManager::getManager();
		$templateMgr->assign_by_ref('issues', $issueDao->getPublishedIssues($journal->getJournalId(), false, $rangeInfo));
		$templateMgr->assign('helpTopicId', 'publishing.index');
		$templateMgr->display('editor/issues/backIssues.tpl');
	}

	/**
	 * Removes an issue
	 */
	function removeIssue($args) {
		$issueId = isset($args[0]) ? (int) $args[0] : 0;
		$issue = IssueManagementHandler::validate($issueId);

		// remove all published articles and return original articles to editing queue
		$articleDao = &DAORegistry::getDAO('ArticleDAO');
		$publishedArticleDao = &DAORegistry::getDAO('PublishedArticleDAO');
		$publishedArticles = $publishedArticleDao->getPublishedArticles($issueId);
		if (isset($publishedArticles) && !empty($publishedArticles)) {
			foreach ($publishedArticles as $article) {
				$articleDao->changeArticleStatus($article->getArticleId(),STATUS_QUEUED);
				$publishedArticleDao->deletePublishedArticleById($article->getPubId());
			}
		}

		// finally remove the issue and cover page if available
		if ($issue->getFileName()) {
			import('file.PublicFileManager');
			$journal = &Request::getJournal();
			$publicFileManager = &new PublicFileManager();
			$publicFileManager->removeJournalFile($journal->getJournalId(),$issue->getFileName());
		}
		
		$issueDao = &DAORegistry::getDAO('IssueDAO');
		$issueDao->deleteIssue($issue);
		if ($issue->getCurrent()) {
			$journal = &Request::getJournal();
			$issues = $issueDao->getPublishedIssues($journal->getJournalId());
			if (!$issues->eof()) {
				$issue = &$issues->next();
				$issue->setCurrent(1);
				$issueDao->updateIssue($issue);
			}
		}

		Request::redirect(null, null, 'backIssues');
	}

	/**
	 * Displays the create issue form
	 */
	function createIssue($articles = array()) {
		IssueManagementHandler::validate();
		IssueManagementHandler::setupTemplate(EDITOR_SECTION_ISSUES);

		import('issue.form.IssueForm');

		$templateMgr = &TemplateManager::getManager();
		import('issue.IssueAction');
		$templateMgr->assign('issueOptions', IssueAction::getIssueOptions());
		$templateMgr->assign('helpTopicId', 'publishing.createIssue');
		$templateMgr->assign('articles', join(':', $articles));

		$issueForm = &new IssueForm('editor/issues/createIssue.tpl');
		$issueForm->initData();
		$issueForm->display();
	}

	/**
	 * Saves the new issue form
	 */
	function saveIssue() {
		IssueManagementHandler::validate();
		IssueManagementHandler::setupTemplate(EDITOR_SECTION_ISSUES);

		import('issue.form.IssueForm');
		$issueForm = &new IssueForm('editor/issues/createIssue.tpl');
		$issueForm->readInputData();

		if ($issueForm->validate()) {
			$issueId = $issueForm->execute();
			$articles = $issueForm->getData('articles');

			if (isset($articles) && !empty($articles)) {
				$journal = &Request::getJournal();
				$articles = explode(':', $articles);
				$articleDao = &DAORegistry::getDAO('ArticleDAO');
				$publishedArticleDao = &DAORegistry::getDAO('PublishedArticleDAO');

				foreach ($articles as $articleId) {
					$article = $articleDao->getArticle($articleId);
					
					if (isset($article) && $journal->getJournalId() == $article->getJournalId()) {
						$article->setStatus(STATUS_PUBLISHED);
						$article->stampStatusModified();
						$articleDao->updateArticle($article);
	
						$publishedArticle = &new PublishedArticle();
						$publishedArticle->setArticleId($article->getArticleId());
						$publishedArticle->setIssueId($issueId);
						$publishedArticle->setDatePublished(Core::getCurrentDate());
						$publishedArticle->setSeq(0);
						$publishedArticle->setViews(0);
						$publishedArticle->setAccessStatus(0);
	
						$publishedArticleDao->insertPublishedArticle($publishedArticle);
						$publishedArticleDao->resequencePublishedArticles($article->getSectionId(),$issueId);
					}
				}
			}

			EditorHandler::backIssues();
		} else {
			$templateMgr = &TemplateManager::getManager();
			import('issue.IssueAction');
			$templateMgr->assign('issueOptions', IssueAction::getIssueOptions());
			$templateMgr->assign('helpTopicId', 'publishing.createIssue');
			$issueForm->display();
		}
	}

	/**
	 * Displays the issue data page
	 */
	function issueData($args) {
		$issueId = isset($args[0]) ? $args[0] : 0;
		$issue = IssueManagementHandler::validate($issueId);
		IssueManagementHandler::setupTemplate(EDITOR_SECTION_ISSUES);

		$templateMgr = &TemplateManager::getManager();
		import('issue.IssueAction');
		$templateMgr->assign('issueOptions', IssueAction::getIssueOptions());

		import('issue.form.IssueForm');
		$issueForm = &new IssueForm('editor/issues/issueData.tpl');
		$issueId = $issueForm->initData($issueId);
		$templateMgr->assign('issueId', $issueId);

		$templateMgr->assign_by_ref('issue', $issue);
		$templateMgr->assign('unpublished',!$issue->getPublished());
		$templateMgr->assign('helpTopicId', 'publishing.index');
		$issueForm->display();
	}

	/**
	 * Edit the current issue form
	 */
	function editIssue($args) {
		$issueId = isset($args[0]) ? (int) $args[0] : 0;
		$issue = IssueManagementHandler::validate($issueId);
		IssueManagementHandler::setupTemplate(EDITOR_SECTION_ISSUES);

		$templateMgr = &TemplateManager::getManager();
		$templateMgr->assign('issueId', $issueId);

		$journal = &Request::getJournal();
		$journalId = $journal->getJournalId();

		import('issue.IssueAction');
		$templateMgr->assign('issueOptions', IssueAction::getIssueOptions());

		import('issue.form.IssueForm');
		$issueForm = &new IssueForm('editor/issues/issueData.tpl');
		$issueForm->readInputData();

		if ($issueForm->validate($issueId)) {
			$issueForm->execute($issueId);
			$issueForm->initData($issueId);
		}

		$templateMgr->assign_by_ref('issue', $issue);
		$templateMgr->assign('unpublished',!$issue->getPublished());

		$issueForm->display();
	}

	/**
	 * Remove cover page from issue
	 */
	function removeCoverPage($args) {
		$issueId = isset($args[0]) ? (int)$args[0] : 0;
		$issue = IssueManagementHandler::validate($issueId);

		import('file.PublicFileManager');
		$journal = &Request::getJournal();
		$publicFileManager = &new PublicFileManager();
		$publicFileManager->removeJournalFile($journal->getJournalId(),$issue->getFileName());
		$issue->setFileName('');
		$issue->setOriginalFileName('');
		
		$issueDao = &DAORegistry::getDAO('IssueDAO');
		$issueDao->updateIssue($issue);

		Request::redirect(null, null, 'issueData', $issueId);
	}

	/**
	 * Remove style file from issue
	 */
	function removeStyleFile($args) {
		$issueId = isset($args[0]) ? (int)$args[0] : 0;
		$issue = IssueManagementHandler::validate($issueId);

		import('file.PublicFileManager');
		$journal = &Request::getJournal();
		$publicFileManager = &new PublicFileManager();
		$publicFileManager->removeJournalFile($journal->getJournalId(),$issue->getStyleFileName());
		$issue->setStyleFileName('');
		$issue->setOriginalStyleFileName('');
		
		$issueDao = &DAORegistry::getDAO('IssueDAO');
		$issueDao->updateIssue($issue);

		Request::redirect(null, null, 'issueData', $issueId);
	}

	/**
	 * Display the table of contents
	 */
	function issueToc($args) {
		$issueId = isset($args[0]) ? $args[0] : 0;
		$issue = IssueManagementHandler::validate($issueId);
		IssueManagementHandler::setupTemplate(EDITOR_SECTION_ISSUES);

		$templateMgr = &TemplateManager::getManager();

		$journal = &Request::getJournal();
		$journalId = $journal->getJournalId();

		$journalSettingsDao = &DAORegistry::getDAO('JournalSettingsDAO');
		$sectionDao =& DAORegistry::getDAO('SectionDAO');

		$enablePublicArticleId = $journalSettingsDao->getSetting($journalId,'enablePublicArticleId');
		$templateMgr->assign('enablePublicArticleId', $enablePublicArticleId);
		$enableSubscriptions = $journalSettingsDao->getSetting($journalId,'enableSubscriptions');
		$templateMgr->assign('enableSubscriptions', $enableSubscriptions);
		$enablePageNumber = $journalSettingsDao->getSetting($journalId, 'enablePageNumber');
		$templateMgr->assign('enablePageNumber', $enablePageNumber);
		$templateMgr->assign('customSectionOrderingExists', $sectionDao->customSectionOrderingExists($issueId));
		
		$templateMgr->assign('issueId', $issueId);
		$templateMgr->assign_by_ref('issue', $issue);
		$templateMgr->assign('unpublished',!$issue->getPublished());
		$templateMgr->assign('issueAccess',$issue->getAccessStatus());

		// get issue sections and articles
		$publishedArticleDao = &DAORegistry::getDAO('PublishedArticleDAO');
		$publishedArticles = $publishedArticleDao->getPublishedArticles($issueId);

		$layoutAssignmentDao =& DAORegistry::getDAO('LayoutAssignmentDAO');
		$proofedArticleIds = $layoutAssignmentDao->getProofedArticlesByIssueId($issueId);
		$templateMgr->assign('proofedArticleIds', $proofedArticleIds);

		$currSection = 0;
		$counter = 0;
		$sections = array();
		$sectionDao =& DAORegistry::getDAO('SectionDAO');
		foreach ($publishedArticles as $article) {
			$sectionId = $article->getSectionId();
			if ($currSection != $sectionId) {
				$lastSectionId = $currSection;
				if ($lastSectionId !== 0) $sections[$lastSectionId][5] = $sectionDao->getCustomSectionOrder($issueId, $sectionId); // Store next custom order
				$currSection = $sectionId;
				$counter++;
				$sections[$sectionId] = array(
					$sectionId,
					$article->getSectionTitle(),
					array($article),
					$counter,
					$sectionDao->getCustomSectionOrder($issueId, $lastSectionId), // Last section custom ordering
					null // Later populated with next section ordering
				);
			} else {
				$sections[$article->getSectionId()][2][] = $article;
			}
		}
		$templateMgr->assign_by_ref('sections', $sections);

		$accessOptions[ISSUE_DEFAULT] = Locale::Translate('editor.issues.default');
		$accessOptions[OPEN_ACCESS] = Locale::Translate('editor.issues.open');
		$templateMgr->assign('accessOptions',$accessOptions);

		import('issue.IssueAction');
		$templateMgr->assign('issueOptions', IssueAction::getIssueOptions());
		$templateMgr->assign('helpTopicId', 'publishing.tableOfContents');
		$templateMgr->display('editor/issues/issueToc.tpl');
	}

	/**
	 * Updates issue table of contents with selected changes and article removals.
	 */
	function updateIssueToc($args) {
		$issueId = isset($args[0]) ? $args[0] : 0;
		IssueManagementHandler::validate($issueId);

		$journal = &Request::getJournal();

		$removedPublishedArticles = array();

		$publishedArticles = Request::getUserVar('publishedArticles');
		$removedArticles = Request::getUserVar('remove');
		$accessStatus = Request::getUserVar('accessStatus');
		$pages = Request::getUserVar('pages');

		$articleDao = &DAORegistry::getDAO('ArticleDAO');
		$publishedArticleDao = &DAORegistry::getDAO('PublishedArticleDAO');

		$articles = $publishedArticleDao->getPublishedArticles($issueId);

		foreach($articles as $article) {
			$articleId = $article->getArticleId();
			$pubId = $article->getPubId();
			if (!isset($removedArticles[$articleId])) {
				if (isset($pages[$articleId])) {
					$article->setPages($pages[$articleId]);
				}
				if (isset($publishedArticles[$articleId])) {
					$publicArticleId = $publishedArticles[$articleId];
					if (!$publicArticleId || !$publishedArticleDao->publicArticleIdExists($publicArticleId, $articleId, $journal->getJournalId())) {
						$publishedArticleDao->updatePublishedArticleField($pubId, 'public_article_id', $publicArticleId);
					}
				}
				if (isset($accessStatus[$pubId])) {
					$publishedArticleDao->updatePublishedArticleField($pubId, 'access_status', $accessStatus[$pubId]);
				}
			} else {
				$article->setStatus(STATUS_QUEUED);
				$article->stampStatusModified();
				$publishedArticleDao->deletePublishedArticleById($pubId);
			}
			$articleDao->updateArticle($article);
		}

		Request::redirect(null, null, 'issueToc', $issueId);
	}

	/**
	 * Change the sequence of a section.
	 */
	function moveSectionToc($args) {
		$issueId = isset($args[0]) ? $args[0] : 0;
		$issue = IssueManagementHandler::validate($issueId);
		$journal = &Request::getJournal();

		$sectionDao = &DAORegistry::getDAO('SectionDAO');
		$section = &$sectionDao->getSection(Request::getUserVar('sectionId'), $journal->getJournalId());

		if ($section != null) {
			// If issue-specific section ordering isn't yet in place, bring it in.
			if (!$sectionDao->customSectionOrderingExists($issueId)) {
				$sectionDao->setDefaultCustomSectionOrders($issueId);
			}

			$sectionDao->moveCustomSectionOrder($issueId, $section->getSectionId(), Request::getUserVar('newPos'), Request::getUserVar('d') == 'u');
		}

		Request::redirect(null, null, 'issueToc', $issueId);
	}

	/**
	 * Reset section ordering to section defaults.
	 */
	function resetSectionOrder($args) {
		$issueId = isset($args[0]) ? $args[0] : 0;
		$issue = IssueManagementHandler::validate($issueId);

		$sectionDao =& DAORegistry::getDAO('SectionDAO');
		$sectionDao->deleteCustomSectionOrdering($issueId);

		Request::redirect(null, null, 'issueToc', $issue->getIssueId());
	}

	/**
	 * Change the sequence of the articles.
	 */
	function moveArticleToc($args) {
		$issueId = isset($args[0]) ? $args[0] : 0;
		$issue = IssueManagementHandler::validate($issueId);

		$journal = &Request::getJournal();

		$publishedArticleDao = &DAORegistry::getDAO('PublishedArticleDAO');
		$publishedArticle = &$publishedArticleDao->getPublishedArticleById(Request::getUserVar('pubId'));

		if ($publishedArticle != null && $publishedArticle->getIssueId() == $issue->getIssueId() && $issue->getJournalId() == $journal->getJournalId()) {
			$publishedArticle->setSeq($publishedArticle->getSeq() + (Request::getUserVar('d') == 'u' ? -1.5 : 1.5));
			$publishedArticleDao->updatePublishedArticle($publishedArticle);
			$publishedArticleDao->resequencePublishedArticles(Request::getUserVar('sectionId'),$issueId);
		}

		Request::redirect(null, null, 'issueToc', $issueId);
	}

	/**
	 * publish issue
	 */
	function publishIssue($args) {
		$issueId = isset($args[0]) ? (int) $args[0] : 0;
		$issue = IssueManagementHandler::validate($issueId);

		$journal = &Request::getJournal();
		$journalId = $journal->getJournalId();

		$issue->setCurrent(1);
		$issue->setPublished(1);
		$issue->setDatePublished(Core::getCurrentDate());

		// If subscriptions with delayed open access are enabled then
		// update open access date according to open access delay policy
		if ($journal->getSetting('enableSubscriptions') && $journal->getSetting('enableDelayedOpenAccess')) {

			$delayDuration = $journal->getSetting('delayedOpenAccessDuration');
			$delayYears = (int)floor($delayDuration/12);
			$delayMonths = (int)fmod($delayDuration,12);

			$curYear = date('Y');
			$curMonth = date('n');
			$curDay = date('j');

			$delayOpenAccessYear = $curYear + $delayYears + (int)floor(($curMonth+$delayMonths)/12);
 			$delayOpenAccessMonth = (int)fmod($curMonth+$delayMonths,12);

			$issue->setAccessStatus(SUBSCRIPTION);
			$issue->setOpenAccessDate(date('Y-m-d H:i:s',mktime(0,0,0,$delayOpenAccessMonth,$curDay,$delayOpenAccessYear)));
		}

		$issueDao = &DAORegistry::getDAO('IssueDAO');
		$issueDao->updateCurrentIssue($journalId,$issue);

		Request::redirect(null, null, 'issueToc', $issue->getIssueId());
	}

	/**
	 * Allows editors to write emails to users associated with the journal.
	 */
	function notifyUsers($args) {
		$issue = IssueManagementHandler::validate(Request::getUserVar('issue'));
		IssueManagementHandler::setupTemplate(EDITOR_SECTION_ISSUES);

		$userDao = &DAORegistry::getDAO('UserDAO');
		$issueDao = &DAORegistry::getDAO('IssueDAO');
		$notificationStatusDao = &DAORegistry::getDAO('NotificationStatusDAO');
		$roleDao = &DAORegistry::getDAO('RoleDAO');

		$journal = &Request::getJournal();
		$user = &Request::getUser();

		import('mail.MailTemplate');
		$email = &new MailTemplate('PUBLISH_NOTIFY');

		if (Request::getUserVar('send') && !$email->hasErrors()) {
			$email->addRecipient($user->getEmail(), $user->getFullName());

			if (Request::getUserVar('whichUsers') == 'allUsers') {
				$recipients = $roleDao->getUsersByJournalId($journal->getJournalId());
			} else {
				$recipients = $notificationStatusDao->getNotifiableUsersByJournalId($journal->getJournalId());
			}
			while (!$recipients->eof()) {
				$recipient = &$recipients->next();
				$email->addBcc($recipient->getEmail(), $recipient->getFullName());
				unset($recipient);
			}

			if (Request::getUserVar('includeToc')=='1' && isset($issue)) {
				$issue = $issueDao->getIssueById(Request::getUserVar('issue'));

				$publishedArticleDao = &DAORegistry::getDAO('PublishedArticleDAO');
				$publishedArticles = &$publishedArticleDao->getPublishedArticlesInSections($issue->getIssueId());

				$templateMgr = &TemplateManager::getManager();
				$templateMgr->assign_by_ref('journal', $journal);
				$templateMgr->assign_by_ref('issue', $issue);
				$templateMgr->assign('body', $email->getBody());
				$templateMgr->assign_by_ref('publishedArticles', $publishedArticles);

				$email->setBody($templateMgr->fetch('editor/notifyUsersEmail.tpl'));

				// Stamp the "users notified" date.
				$issue->setDateNotified(Core::getCurrentDate());
				$issueDao->updateIssue($issue);
			}

			$email->send();

			Request::redirect(null, Request::getRequestedPage());
		} else {
			if (!Request::getUserVar('continued')) {
				$email->assignParams(array(
					'editorialContactSignature' => $user->getContactSignature()
				));
			}
			$notifiableCount = $notificationStatusDao->getNotifiableUsersCount($journal->getJournalId());
			$allUsersCount = $roleDao->getJournalUsersCount($journal->getJournalId());

			$issuesIterator = &$issueDao->getIssues($journal->getJournalId());

			$email->displayEditForm(
				Request::url(null, null, 'notifyUsers'),
				array(),
				'editor/notifyUsers.tpl',
				array(
					'issues' => $issuesIterator,
					'notifiableCount' => $notifiableCount,
					'allUsersCount' => $allUsersCount
				)
			);
		}
	}

	/**
	 * Validate that user is an editor in the selected journal and if the issue id is valid
	 * Redirects to issue create issue page if not properly authenticated.
	 */
	function validate($issueId = null) {
		parent::validate();
		
		if (isset($issueId)) {
			$journal = &Request::getJournal();
			$issueDao = &DAORegistry::getDAO('IssueDAO');
			$issue = $issueDao->getIssueById($issueId, $journal->getJournalId());
			
			if (!isset($issue)) {
				Request::redirect(null, null, 'createIssue');
			}
			
			return $issue;
		}
		
		return null;
	}

	/**
	 * Setup common template variables.
	 * @param $level int set to one of EDITOR_SECTION_? defined in EditorHandler.
	 */
	function setupTemplate($level) {
		EditorHandler::setupTemplate($level);
	}

}
