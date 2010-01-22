<?php

/**
 * @file IssueManagementHandler.inc.php
 *
 * Copyright (c) 2003-2010 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class IssueManagementHandler
 * @ingroup pages_editor
 *
 * @brief Handle requests for issue management in publishing.
 */

// $Id$


class IssueManagementHandler extends EditorHandler {

	/**
	 * Displays the listings of future (unpublished) issues
	 */
	function futureIssues() {
		IssueManagementHandler::validate(null, true);
		IssueManagementHandler::setupTemplate(EDITOR_SECTION_ISSUES);

		$journal = &Request::getJournal();
		$issueDao = &DAORegistry::getDAO('IssueDAO');
		$rangeInfo = Handler::getRangeInfo('issues');
		$templateMgr = &TemplateManager::getManager();
		$templateMgr->assign_by_ref('issues', $issueDao->getUnpublishedIssues($journal->getJournalId(), $rangeInfo));
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
		$templateMgr->assign_by_ref('issues', $issueDao->getPublishedIssues($journal->getJournalId(), $rangeInfo));

		$allIssuesIterator = $issueDao->getPublishedIssues($journal->getJournalId());
		$issueMap = array();
		while ($issue =& $allIssuesIterator->next()) {
			$issueMap[$issue->getIssueId()] = $issue->getIssueIdentification();
			unset($issue);
		}
		$templateMgr->assign('allIssues', $issueMap);

		$currentIssue =& $issueDao->getCurrentIssue($journal->getJournalId());
		$currentIssueId = $currentIssue?$currentIssue->getIssueId():null;
		$templateMgr->assign('currentIssueId', $currentIssueId);

		$templateMgr->assign('helpTopicId', 'publishing.index');
		$templateMgr->assign('usesCustomOrdering', $issueDao->customIssueOrderingExists($journal->getJournalId()));
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
	function createIssue() {
		IssueManagementHandler::validate();
		IssueManagementHandler::setupTemplate(EDITOR_SECTION_ISSUES);

		import('issue.form.IssueForm');

		$templateMgr = &TemplateManager::getManager();
		import('issue.IssueAction');
		$templateMgr->assign('issueOptions', IssueAction::getIssueOptions());
		$templateMgr->assign('helpTopicId', 'publishing.createIssue');

		$issueForm = &new IssueForm('editor/issues/createIssue.tpl');

		if ($issueForm->isLocaleResubmit()) {
			$issueForm->readInputData();
		} else {
			$issueForm->initData();
		}
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
			$issueForm->execute();
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
		$issue = IssueManagementHandler::validate($issueId, true);
		IssueManagementHandler::setupTemplate(EDITOR_SECTION_ISSUES);

		$templateMgr = &TemplateManager::getManager();
		import('issue.IssueAction');
		$templateMgr->assign('issueOptions', IssueAction::getIssueOptions());

		import('issue.form.IssueForm');
		$issueForm = &new IssueForm('editor/issues/issueData.tpl');

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
	 */
	function editIssue($args) {
		$issueId = isset($args[0]) ? (int) $args[0] : 0;
		$issue = IssueManagementHandler::validate($issueId, true);
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
		$formLocale = $args[1];
		$issue = IssueManagementHandler::validate($issueId, true);

		import('file.PublicFileManager');
		$journal = &Request::getJournal();
		$publicFileManager = &new PublicFileManager();
		$publicFileManager->removeJournalFile($journal->getJournalId(),$issue->getFileName($formLocale));
		$issue->setFileName('', $formLocale);
		$issue->setOriginalFileName('', $formLocale);
		$issue->setWidth('', $formLocale);
		$issue->setHeight('', $formLocale);

		$issueDao = &DAORegistry::getDAO('IssueDAO');
		$issueDao->updateIssue($issue);

		Request::redirect(null, null, 'issueData', $issueId);
	}

	/**
	 * Remove style file from issue
	 */
	function removeStyleFile($args) {
		$issueId = isset($args[0]) ? (int)$args[0] : 0;
		$issue = IssueManagementHandler::validate($issueId, true);

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
		$issue = IssueManagementHandler::validate($issueId, true);
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
		$templateMgr->assign('customSectionOrderingExists', $customSectionOrderingExists = $sectionDao->customSectionOrderingExists($issueId));

		$templateMgr->assign('issueId', $issueId);
		$templateMgr->assign_by_ref('issue', $issue);
		$templateMgr->assign('unpublished', !$issue->getPublished());
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
		IssueManagementHandler::validate($issueId, true);

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
				$publishedArticleDao->resequencePublishedArticles($article->getSectionId(), $issueId);
			}
			$articleDao->updateArticle($article);
		}

		Request::redirect(null, null, 'issueToc', $issueId);
	}

	/**
	 * Change the sequence of an issue.
	 */
	function setCurrentIssue($args) {
		$issueId = Request::getUserVar('issueId');
		$journal = &Request::getJournal();
		$issueDao = &DAORegistry::getDAO('IssueDAO');
		if ($issueId) {
			$issue = IssueManagementHandler::validate($issueId);
			$issue->setCurrent(1);
			$issueDao->updateCurrentIssue($journal->getJournalId(), $issue);
		} else {
			IssueManagementHandler::validate();
			$issueDao->updateCurrentIssue($journal->getJournalId());
		}
		Request::redirect(null, null, 'backIssues');
	}

	/**
	 * Change the sequence of an issue.
	 */
	function moveIssue($args) {
		$issueId = isset($args[0]) ? $args[0] : 0;
		$issue = IssueManagementHandler::validate($issueId);
		$journal = &Request::getJournal();

		$issueDao = &DAORegistry::getDAO('IssueDAO');

		// If custom issue ordering isn't yet in place, bring it in.
		if (!$issueDao->customIssueOrderingExists($journal->getJournalId())) {
			$issueDao->setDefaultCustomIssueOrders($journal->getJournalId());
		}

		$issueDao->moveCustomIssueOrder($journal->getJournalId(), $issue->getIssueId(), Request::getUserVar('newPos'), Request::getUserVar('d') == 'u');

		Request::redirect(null, null, 'backIssues');
	}

	/**
	 * Reset issue ordering to defaults.
	 */
	function resetIssueOrder($args) {
		IssueManagementHandler::validate();

		$journal =& Request::getJournal();

		$issueDao =& DAORegistry::getDAO('IssueDAO');
		$issueDao->deleteCustomIssueOrdering($journal->getJournalId());

		Request::redirect(null, null, 'backIssues');
	}

	/**
	 * Change the sequence of a section.
	 */
	function moveSectionToc($args) {
		$issueId = isset($args[0]) ? $args[0] : 0;
		$issue = IssueManagementHandler::validate($issueId, true);
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
		$issue = IssueManagementHandler::validate($issueId, true);

		$sectionDao =& DAORegistry::getDAO('SectionDAO');
		$sectionDao->deleteCustomSectionOrdering($issueId);

		Request::redirect(null, null, 'issueToc', $issue->getIssueId());
	}

	/**
	 * Change the sequence of the articles.
	 */
	function moveArticleToc($args) {
		$issueId = isset($args[0]) ? $args[0] : 0;
		$issue = IssueManagementHandler::validate($issueId, true);

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

		if (!$issue->getPublished()) {
			// Set the status of any attendant queued articles to STATUS_PUBLISHED.
			$publishedArticleDao =& DAORegistry::getDAO('PublishedArticleDAO');
			$articleDao =& DAORegistry::getDAO('ArticleDAO');
			$publishedArticles =& $publishedArticleDao->getPublishedArticles($issueId, null, true);
			foreach ($publishedArticles as $publishedArticle) {
				$article =& $articleDao->getArticle($publishedArticle->getArticleId());
				if ($article && $article->getStatus() == STATUS_QUEUED) {
					$article->setStatus(STATUS_PUBLISHED);
					$article->stampStatusModified();
					$articleDao->updateArticle($article);
				}
				unset($article);
			}
		}

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
		$templateMgr = &TemplateManager::getManager();

		import('mail.MassMail');
		$email = &new MassMail('PUBLISH_NOTIFY');

		if (Request::getUserVar('send') && !$email->hasErrors()) {
			$email->addRecipient($user->getEmail(), $user->getFullName());

			if (Request::getUserVar('whichUsers') == 'allUsers') {
				$recipients = $roleDao->getUsersByJournalId($journal->getJournalId());
			} else {
				$recipients = $notificationStatusDao->getNotifiableUsersByJournalId($journal->getJournalId());
			}
			while (!$recipients->eof()) {
				$recipient = &$recipients->next();
				$email->addRecipient($recipient->getEmail(), $recipient->getFullName());
				unset($recipient);
			}

			if (Request::getUserVar('includeToc')=='1' && isset($issue)) {
				$issue = $issueDao->getIssueById(Request::getUserVar('issue'));

				$publishedArticleDao = &DAORegistry::getDAO('PublishedArticleDAO');
				$publishedArticles = &$publishedArticleDao->getPublishedArticlesInSections($issue->getIssueId());

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
			echo '<script type="text/javascript">window.location = "' . Request::url(null, 'editor') . '";</script>';
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
	 * NOTE: As of OJS 2.2, Layout Editors are allowed if specified in args.
	 */
	function validate($issueId = null, $allowLayoutEditor = false) {
		$issue = null;
		$journal =& Request::getJournal();

		if (!isset($journal)) Validation::redirectLogin();

		if (isset($issueId)) {
			$issueDao = &DAORegistry::getDAO('IssueDAO');
			$issue = $issueDao->getIssueById($issueId, $journal->getJournalId());

			if (!$issue) {
				Request::redirect(null, null, 'createIssue');
			}
		}

		if (!Validation::isEditor($journal->getJournalId())) {
			if (isset($journal) && $allowLayoutEditor && Validation::isLayoutEditor($journal->getJournalId())) {
				// We're a Layout Editor. If specified, make sure that the issue is not published.
				if ($issue && !$issue->getPublished()) {
					Validation::redirectLogin();
				}
			} else {
				Validation::redirectLogin();
			}
		}

		return $issue;
	}

	/**
	 * Setup common template variables.
	 * @param $level int set to one of EDITOR_SECTION_? defined in EditorHandler.
	 */
	function setupTemplate($level) {
		$templateMgr =& TemplateManager::getManager();
		$templateMgr->assign('isLayoutEditor', Request::getRequestedPage() == 'layoutEditor');
		EditorHandler::setupTemplate($level);
	}
}
