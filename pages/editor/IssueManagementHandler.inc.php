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

		$templateMgr = &TemplateManager::getManager();
		$templateMgr->assign('issues', $issueDao->getUnpublishedIssues($journal->getJournalId()));
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

		$templateMgr = &TemplateManager::getManager();
		$templateMgr->assign('issues', $issueDao->getPublishedIssues($journal->getJournalId()));
		$templateMgr->assign('helpTopicId', 'publishing.index');
		$templateMgr->display('editor/issues/backIssues.tpl');
	}

	/**
	 * Removes an issue
	 */
	function removeIssue($args) {

		$issueId = isset($args[0]) ? (int) $args[0] : 0;
		IssueManagementHandler::validate($issueId);
		
		// remove all published articles and return original articles to scheduling queue
		$articleDao = &DAORegistry::getDAO('ArticleDAO');
		$publishedArticleDao = &DAORegistry::getDAO('PublishedArticleDAO');
		$publishedArticles = $publishedArticleDao->getPublishedArticles($issueId);
		if (isset($publishedArticles) && !empty($publishedArticles)) {
			foreach ($publishedArticles as $article) {
				$articleDao->changeArticleStatus($article->getArticleId(),STATUS_SCHEDULED);
				$publishedArticleDao->deletePublishedArticleById($article->getPubId());
			}
		}

		// finally remove the issue and cover page if available
		$issueDao = &DAORegistry::getDAO('IssueDAO');
		$issue = $issueDao->getIssueById($issueId);
		if (isset($issue)) {
			if ($issue->getFileName()) {
				$journal = &Request::getJournal();
				$publicFileManager = new PublicFileManager();
				$publicFileManager->removeJournalFile($journal->getJournalId(),$issue->getFileName());
			}

			$issueDao->deleteIssueById($issueId);
			if ($issue->getCurrent()) {
				$journal = &Request::getJournal();
				$issues = $issueDao->getPublishedIssues($journal->getJournalId());
				if (!empty($issues)) {
					$issue = $issues[0];
					$issue->setCurrent(1);
					$issueDao->updateIssue($issue);
				}
			}
		}
	}

	/**
	 * Displays the create issue form
	 */
	function createIssue($articles = null) {
		IssueManagementHandler::validate();
		IssueManagementHandler::setupTemplate(EDITOR_SECTION_ISSUES);

		Session::setSessionVar('articles',$articles);

		import('issue.form.IssueForm');
		
		$templateMgr = &TemplateManager::getManager();
		$templateMgr->assign('issueOptions', IssueManagementHandler::getIssueOptions());
		$templateMgr->assign('helpTopicId', 'publishing.createIssue');
		
		$issueForm = &new IssueForm('editor/issues/createIssue.tpl');
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

			$articles = Session::getSessionVar('articles');
			
			if ($articles != null) {
				$articleDao = &DAORegistry::getDAO('ArticleDAO');
				$publishedArticleDao = &DAORegistry::getDAO('PublishedArticleDAO');

				foreach ($articles as $article) {
					$article->setStatus(STATUS_PUBLISHED);
					$article->stampStatusModified();
					$articleDao->updateArticle($article);

					$publishedArticle = &new PublishedArticle();
					$publishedArticle->setArticleId($article->getArticleId());
					$publishedArticle->setIssueId($issueId);
					$publishedArticle->setSectionId($article->getSectionId());
					$publishedArticle->setDatePublished(Core::getCurrentDate());
					$publishedArticle->setSeq(0);
					$publishedArticle->setViews(0);
					$publishedArticle->setAccessStatus(0);
					
					$publishedArticleDao->insertPublishedArticle($publishedArticle);
					$publishedArticleDao->resequencePublishedArticles($article->getSectionId(),$issueId);
				}
			}

			Session::unsetSessionVar('articles');
			EditorHandler::schedulingQueue();
		} else {
			Session::unsetSessionVar('articles');
			$templateMgr = &TemplateManager::getManager();
			$templateMgr->assign('issueOptions', IssueManagementHandler::getIssueOptions());
			$templateMgr->assign('helpTopicId', 'publishing.createIssue');
			$issueForm->display();
		}
	}

	/**
	 * Displays the issue data page
	 */
	function issueData($args) {

		$issueId = isset($args[0]) ? $args[0] : 0;
		IssueManagementHandler::validate($issueId);
		IssueManagementHandler::setupTemplate(EDITOR_SECTION_ISSUES);
		
		$templateMgr = &TemplateManager::getManager();
		$templateMgr->assign('issueOptions', IssueManagementHandler::getIssueOptions());

		import('issue.form.IssueForm');
		$issueForm = &new IssueForm('editor/issues/issueData.tpl');
		$issueId = $issueForm->initData($issueId);
		$templateMgr->assign('issueId', $issueId);
	
		$issueDao = &DAORegistry::getDAO('IssueDAO');
		$issue = $issueDao->getIssueById($issueId);
		$templateMgr->assign('issue', $issue);
		$templateMgr->assign('unpublished',!$issue->getPublished());
		$templateMgr->assign('helpTopicId', 'publishing.index');
		$issueForm->display();
	}

	/**
	 * Edit the current issue form
	 */
	function editIssue($args) {

		$issueId = isset($args[0]) ? (int) $args[0] : 0;
		IssueManagementHandler::validate($issueId);
		IssueManagementHandler::setupTemplate(EDITOR_SECTION_ISSUES);
		
		$templateMgr = &TemplateManager::getManager();
		$templateMgr->assign('issueId', $issueId);

		$journal = &Request::getJournal();
		$journalId = $journal->getJournalId();

		$templateMgr->assign('issueOptions', IssueManagementHandler::getIssueOptions());

		import('issue.form.IssueForm');
		$issueForm = &new IssueForm('editor/issues/issueData.tpl');
		$issueForm->readInputData();

		if ($issueForm->validate($issueId)) {
			$issueForm->execute($issueId);
			$issueForm->initData($issueId);
		}

		$issueDao = &DAORegistry::getDAO('IssueDAO');
		$issue = $issueDao->getIssueById($issueId);
		$templateMgr->assign('issue', $issue);
		$templateMgr->assign('unpublished',!$issue->getPublished());
		
		$issueForm->display();
	}

	/**
	 * Remove cover page from issue
	 */
	function removeCoverPage($args) {

		$issueId = isset($args[0]) ? (int)$args[0] : 0;
		IssueManagementHandler::validate($issueId);
		
		$issueDao = &DAORegistry::getDAO('IssueDAO');
		$issue = $issueDao->getIssueById($issueId);

		if (isset($issue)) {
			$journal = &Request::getJournal();
			$publicFileManager = new PublicFileManager();
			$publicFileManager->removeJournalFile($journal->getJournalId(),$issue->getFileName());
			$issue->setFileName('');
			$issue->setOriginalFileName('');
			$issueDao->updateIssue($issue);
		}

		Request::redirect(sprintf('%s/issueData/%d', Request::getRequestedPage(), $issueId));
	}		

	/**
	 * Display the table of contents
	 */
	function issueToc($args) {
				
		$issueId = isset($args[0]) ? $args[0] : 0;
		IssueManagementHandler::validate($issueId);
		IssueManagementHandler::setupTemplate(EDITOR_SECTION_ISSUES);
		
		$templateMgr = &TemplateManager::getManager();

		$journal = &Request::getJournal();
		$journalId = $journal->getJournalId();

		$journalSettingsDao = &DAORegistry::getDAO('JournalSettingsDAO');
		$enablePublicArticleId = $journalSettingsDao->getSetting($journalId,'enablePublicArticleId');
		$templateMgr->assign('enablePublicArticleId', $enablePublicArticleId);
		$enableSubscriptions = $journalSettingsDao->getSetting($journalId,'enableSubscriptions');
		$templateMgr->assign('enableSubscriptions', $enableSubscriptions);
		$enablePageNumber = $journalSettingsDao->getSetting($journalId, 'enablePageNumber');
		$templateMgr->assign('enablePageNumber', $enablePageNumber);

		$issueDao = &DAORegistry::getDAO('IssueDAO');
		if ($issueId) {
			$issue = $issueDao->getIssueById($issueId);
		} else {
			$issues = $issueDao->getUnpublishedIssues($journalId);
			if (!empty($issues)) {
				$issue = $issues[0];
				$issueId = $issue->getIssueId();
			}
		}

		if ($issueId) {
			$templateMgr->assign('issueId', $issueId);
			$templateMgr->assign('issue', $issue);
			$templateMgr->assign('unpublished',!$issue->getPublished());
			$templateMgr->assign('issueAccess',$issue->getAccessStatus());

			// get issue sections and articles
			$publishedArticleDao = &DAORegistry::getDAO('PublishedArticleDAO');		
			$publishedArticles = $publishedArticleDao->getPublishedArticles($issueId);

			$currSection = 0;
			$counter = 0;
			$sections = array();
			foreach ($publishedArticles as $article) {
				$sectionId = $article->getSectionId();
				if ($currSection != $sectionId) {
					$currSection = $sectionId;
					$counter++;
					$sections[$sectionId] = array($sectionId, $article->getSectionTitle(), array($article), $counter);
				} else {
					$sections[$article->getSectionId()][2][] = $article;
				}
			}
			$templateMgr->assign('sections', $sections);
						
		} else {
			$templateMgr->assign('noIssue', true);
		}

		$accessOptions[ISSUE_DEFAULT] = Locale::Translate('editor.issues.default');
		$accessOptions[OPEN_ACCESS] = Locale::Translate('editor.issues.open');
		$templateMgr->assign('accessOptions',$accessOptions);

		$templateMgr->assign('issueOptions', IssueManagementHandler::getIssueOptions());
		$templateMgr->assign('helpTopicId', 'publishing.tableOfContents');
		$templateMgr->display('editor/issues/issueToc.tpl');	
	}

	/**
	 * Updates issue table of contents with selected changes and article removals.
	 */
	function updateIssueToc($args) {

		$issueId = isset($args[0]) ? $args[0] : 0;
		IssueManagementHandler::validate($issueId);
	
		$publishedArticles = Request::getUserVar('publishedArticles');
		$removedPublishedArticles = array();

		$removedArticles = Request::getUserVar('remove');
		$accessStatus = Request::getUserVar('accessStatus');
		$pages = Request::getUserVar('pages');
		
		$articleDao = &DAORegistry::getDAO('ArticleDAO');
		$publishedArticleDao = &DAORegistry::getDAO('PublishedArticleDAO');
		
		if (!is_array($publishedArticles)) { $publishedArticles = array(); }
		if (!is_array($removedArticles)) { $removedArticles = array(); }
		if (!is_array($accessStatus)) { $accessStatus = array(); }
		if (!is_array($pages)) { $pages = array(); }

		while (list($articleId, $pageNum) = each($pages)) {
			$article = $articleDao->getArticle($articleId);
			if (!isset($removedArticles[$articleId])) {
				$publicArticleId = $publishedArticles[$articleId];
				if (!$publicArticleId || !$articleDao->publicArticleIdExists($publicArticleId, $articleId)) {
					$article->setPublicArticleId($publicArticleId);
				}
				$article->setPages($pageNum);
			} else {
				$pubId = $removedArticles[$articleId];
				$article->setStatus(STATUS_SCHEDULED);
				$article->stampStatusModified();
				$removedPublishedArticles[$pubId] = $pubId;
				$publishedArticleDao->deletePublishedArticleById($pubId);			
			}
			$articleDao->updateArticle($article);		
		}

		while (list($pubId,$access) = each($accessStatus)) {
			if (!isset($removedPublishedArticles[$pubId])) {
				$publishedArticleDao->updatePublishedArticleField($pubId, 'access_status', $access);
			}
		}

		Request::redirect(sprintf('%s/issueToc/%d', Request::getRequestedPage(), $issueId));
	}		

	/**
	 * Change the sequence of a section.
	 */
	function moveSectionToc($args) {

		$issueId = isset($args[0]) ? $args[0] : 0;
		IssueManagementHandler::validate($issueId);
		
		$journal = &Request::getJournal();
		
		$sectionDao = &DAORegistry::getDAO('SectionDAO');
		$section = &$sectionDao->getSection(Request::getUserVar('sectionId'), $journal->getJournalId());
		
		if ($section != null) {
			$section->setSequence($section->getSequence() + (Request::getUserVar('d') == 'u' ? -1.5 : 1.5));
			$sectionDao->updateSection($section);
			$sectionDao->resequenceSections($journal->getJournalId());
		}

		Request::redirect(sprintf('%s/issueToc/%d', Request::getRequestedPage(), $issueId));
	}

	/**
	 * Change the sequence of the articles.
	 */
	function moveArticleToc($args) {

		$issueId = isset($args[0]) ? $args[0] : 0;
		IssueManagementHandler::validate($issueId);
		
		$journal = &Request::getJournal();
		
		$publishedArticleDao = &DAORegistry::getDAO('PublishedArticleDAO');
		$publishedArticle = &$publishedArticleDao->getPublishedArticleById(Request::getUserVar('pubId'));
		
		if ($publishedArticle != null) {
			$publishedArticle->setSeq($publishedArticle->getSeq() + (Request::getUserVar('d') == 'u' ? -1.5 : 1.5));
			$publishedArticleDao->updatePublishedArticle($publishedArticle);
			$publishedArticleDao->resequencePublishedArticles(Request::getUserVar('sectionId'),$issueId);
		}

		Request::redirect(sprintf('%s/issueToc/%d', Request::getRequestedPage(), $issueId));
	}
		
	/**
	 * publish issue
	 */
	function publishIssue($args) {

		$issueId = isset($args[0]) ? (int) $args[0] : 0;
		IssueManagementHandler::validate($issueId);

		$journal = &Request::getJournal();
		$journalId = $journal->getJournalId();

		$issueDao = &DAORegistry::getDAO('IssueDAO');
		$issue = $issueDao->getIssueById($issueId);
		$issue->setCurrent(1);
		$issue->setPublished(1);
		$issue->setDatePublished(Core::getCurrentDate());

		$issueDao->updateCurrentIssue($journalId,$issue);

		Request::redirect(sprintf('%s/issueToc', Request::getRequestedPage()));
	}		

	/**
	 * Allows editors to write emails to users associated with the journal.
	 */
	function notifyUsers($args) {
		IssueManagementHandler::validate();
		IssueManagementHandler::setupTemplate(EDITOR_SECTION_ISSUES);

		$userDao = &DAORegistry::getDAO('UserDAO');
		$issueDao = &DAORegistry::getDAO('IssueDAO');

		$journal = &Request::getJournal();
		$user = &Request::getUser();

		$email = &new MailTemplate('PUBLISH_NOTIFY');

		if (Request::getUserVar('send') && !$email->hasErrors()) {
			$email->addRecipient($user->getEmail(), $user->getFullName());

			$roleDao = &DAORegistry::getDAO('RoleDAO');
			if (Request::getUserVar('whichUsers') == 'allUsers') {
				$recipients = $roleDao->getUsersByJournalId($journal->getJournalId());
			} else {
				$notificationStatusDao = &DAORegistry::getDAO('NotificationStatusDAO');
				$recipients = $notificationStatusDao->getNotifiableUsersByJournalId($journal->getJournalId());
			}
			foreach ($recipients as $recipient) {
				$email->addBcc($recipient->getEmail(), $recipient->getFullName());
			}

			if (Request::getUserVar('includeToc')=='1') {
				$issue = $issueDao->getIssueById(Request::getUserVar('issue'));

				$publishedArticleDao = &DAORegistry::getDAO('PublishedArticleDAO');
				$publishedArticles = &$publishedArticleDao->getPublishedArticlesInSections($issue->getIssueId());

				$mimeBoundary = '==boundary_' . md5(microtime());
				$templateMgr = &TemplateManager::getManager();
				$templateMgr->assign('issue', $issue);
				$templateMgr->assign('body', $email->getBody());
				$templateMgr->assign('mimeBoundary', $mimeBoundary);
                                $templateMgr->assign('publishedArticles', $publishedArticles);

				$email->addHeader('MIME-Version', '1.0');
				$email->setContentType('multipart/mixed; boundary="'.$mimeBoundary.'"');
				$email->setBody($templateMgr->fetch('editor/notifyUsersEmail.tpl'));
			}

			$email->send();

			// Stamp the "users notified" date.
			$issue->setDateNotified(Core::getCurrentDate());
			$issueDao->updateIssue($issue);

			Request::redirect(Request::getRequestedPage());
		} else {
			if (!Request::getUserVar('continued')) {
				$email->assignParams(array(
					'editorialContactSignature' => $user->getContactSignature($journal)
				));
			}
			$issues = &$issueDao->getIssues($journal->getJournalId());
			$email->displayEditForm(Request::getPageUrl() . '/' . Request::getRequestedPage() . '/notifyUsers', array(), 'editor/notifyUsers.tpl', array('issues' => $issues));
		}
	}

	/**
	 * builds the issue options pulldown for published and unpublished issues
	 * @param $current bool retrieve current or not
	 * @param $published bool retrieve published or non-published issues
	 */
	function getIssueOptions() {

		$issueOptions = array();

		$journal = &Request::getJournal();
		$journalId = $journal->getJournalId();

		$issueDao = &DAORegistry::getDAO('IssueDAO');
		
		$issueOptions['-100'] =  '------    ' . Locale::translate('editor.issues.futureIssues') . '    ------';
		$issues = $issueDao->getUnpublishedIssues($journalId);
		foreach ($issues as $issue) {
			$issueOptions[$issue->getIssueId()] = $issue->getIssueIdentification();
		}
		$issueOptions['-101'] = '------    ' . Locale::translate('editor.issues.currentIssue') . '    ------';
		$issues = $issueDao->getPublishedIssues($journalId, true);
		if (isset($issues[0]) && $issues[0]->getCurrent()) {
			$issueOptions[$issues[0]->getIssueId()] = $issues[0]->getIssueIdentification();
			array_shift($issues);
		}
		$issueOptions['-102'] = '------    ' . Locale::translate('editor.issues.backIssues') . '    ------';
		foreach ($issues as $issue) {
			$issueOptions[$issue->getIssueId()] = $issue->getIssueIdentification();
		}
		
		return $issueOptions;
	}	
	
	/**
	 * Validate that user is an editor in the selected journal and if the issue id is valid
	 * Redirects to issue create issue page if not properly authenticated.
	 */
	function validate($issueId = 0) {
		parent::validate();
		if ($issueId) {
			$issueDao = &DAORegistry::getDAO('IssueDAO');
			if (!$issueDao->issueIdExists($issueId)) {
				Request::redirect(sprintf('%s/createIssue', Request::getRequestedPage()));
			}
		}

	}

	/**
	 * Setup common template variables.
	 * @param $level int set to one of EDITOR_SECTION_? defined in EditorHandler.
	 */
	function setupTemplate($level) {
		EditorHandler::setupTemplate($level);
	}

}
