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

class IssueManagementHandler extends Handler {

	/**
	 * Displays the listings of back issues
	 */
	function backIssues() {
		IssueManagementHandler::validate();
		IssueManagementHandler::setupTemplate(false);

		$journal = &Request::getJournal();
		$journalId = $journal->getJournalId();

		$templateMgr = &TemplateManager::getManager();

		$issueDao = &DAORegistry::getDAO('IssueDAO');
		$issues = $issueDao->getSelectedIssues($journalId,1,true);
		$templateMgr->assign('issues',$issues);

		$publishedArticleDao = &DAORegistry::getDAO('PublishedArticleDAO');
		$issueAuthors = $publishedArticleDao->getPublishedArticleAuthors();
		$templateMgr->assign('issueAuthors',$issueAuthors);

		$selectOptions[0] = Locale::translate('common.applyAction');
		$selectOptions[1] = Locale::translate('common.deleteSelection');
		$templateMgr->assign('selectOptions',$selectOptions);

		$templateMgr->display('editor/issues/backIssues.tpl');
	}

	/**
	 *	Update back issues
	 */
	function updateBackIssues($args) {
		IssueManagementHandler::validate();

		$actionId = isset($args[0]) ? (int) $args[0] : 0;

		$select = Request::getUserVar('select');

		switch($actionId) {
			case '1':
				foreach($select as $issueId) {
					IssueManagementHandler::removeIssue(array($issueId));
				}
				break;
		}
	}

	/**
	 * Removes an issue
	 */
	function removeIssue($args) {
		IssueManagementHandler::validate();

		$issueId = isset($args[0]) ? (int) $args[0] : 0;
		
		// remove all published articles and return original articles to scheduling queue
		$articleDao = &DAORegistry::getDAO('ArticleDAO');
		$publishedArticleDao = &DAORegistry::getDAO('PublishedArticleDAO');
		$publishedArticles = $publishedArticleDao->getPublishedArticles($issueId);
		if (isset($publishedArticles) && !empty($publishedArticles)) {
			foreach ($publishedArticles as $article) {
				$articleDao->changeArticleStatus($article->getArticleId(),SCHEDULED);
				$publishedArticleDao->deletePublishedArticleById($article->getPubId());
			}
		}

		// remove all related issue files
		import('file.FrontMatterManager');
		$frontMatterManager = new FrontMatterManager($issueId);
		if ($issueId) {
			$frontMatterManager->rmtree($frontMatterManager->getIssueDirectory());
		}

		// finally remove the issue
		$issueDao = &DAORegistry::getDAO('IssueDAO');
		$issueDao->deleteIssueById($issueId);

		Request::redirect(sprintf('%s/issueManagement/issueToc', Request::getRequestedPage()));
	}

	/**
	 * Displays the issue form
	 */
	function createIssue($articles = null) {
		IssueManagementHandler::validate();
		IssueManagementHandler::setupTemplate(false);

		Session::setSessionVar('articles',$articles);

		import('issue.form.IssueForm');
		
		$issueForm = &new IssueForm('editor/issues/createIssue.tpl');
		$issueForm->display();
	}

	/**
	 * Saves the current issue form
	 */
	function saveIssue() {
		IssueManagementHandler::validate();
		IssueManagementHandler::setupTemplate(true);

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
					$article->setStatus(PUBLISHED);
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
			$issueForm->display();
		}

	}

	/**
	 * Displays the issue management page
	 */
	function issueManagement($args) {

		$journal = &Request::getJournal();
		$journalId = $journal->getJournalId();

		$templateMgr = &TemplateManager::getManager();

		$subsection = isset($args[0]) ? $args[0] : 'issueToc';
		$templateMgr->assign('subsection',$subsection);
		$issueId = isset($args[1]) ? $args[1] : 0;

		if (isset($args[1])) {
			$issueId = $args[1];
			IssueManagementHandler::validate($issueId);
		} else {
			$issueId = 0;
		}

		$issueOptions = IssueManagementHandler::getIssueOptions($journalId,3);
		$templateMgr->assign('issueOptions', $issueOptions);

		switch ($subsection) {
			case 'issueData':
				IssueManagementHandler::issueData($issueId);
				break;
			default:
				$subsection = 'issueToc';
				$templateMgr->assign('subsection',$subsection);
				IssueManagementHandler::issueToc($issueId);
				$templateMgr->display('editor/issueManagement.tpl');
		}
	}

	/**
	 * Display the table of contents
	 * @param issueId int
	 */
	function issueToc($issueId) {
		IssueManagementHandler::validate();

		$templateMgr = &TemplateManager::getManager();

		$journal = &Request::getJournal();
		$journalId = $journal->getJournalId();

		$journalSettingsDao = &DAORegistry::getDAO('JournalSettingsDAO');
		$enablePublicArticleId = $journalSettingsDao->getSetting($journalId,'enablePublicArticleId');
		$templateMgr->assign('enablePublicArticleId', $enablePublicArticleId);
		$enableSubscriptions = $journalSettingsDao->getSetting($journalId,'enableSubscriptions');
		$templateMgr->assign('enableSubscriptions', $enableSubscriptions);

		$issueDao = &DAORegistry::getDAO('IssueDAO');
		if ($issueId) {
			$issue = $issueDao->getIssueById($issueId);
		} else {
			$issues = $issueDao->getSelectedIssues($journalId,0,false);
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
		$accessOptions[OPEN_ACCESS] = Locale::Translate('editor.issues.openAccess');
		$templateMgr->assign('accessOptions',$accessOptions);

		$selectOptions[0] = Locale::translate('common.applyAction');
		$selectOptions[1] = Locale::translate('common.removeSelection');
		$templateMgr->assign('selectOptions',$selectOptions);

		IssueManagementHandler::setupTemplate(false, $issueId);
	}

	/**
	 * remove all selected articles from issue and back into scheduling queue
	 */
	function updateIssueToc($args) {
		IssueManagementHandler::validate();

		$issueId = isset($args[0]) ? (int) $args[0] : 0;

		$publishedArticles = Request::getUserVar('publishedArticles');
		$removedPublishedArticles = array();

		$removedArticles = Request::getUserVar('remove');
		$accessStatus = Request::getUserVar('accessStatus');

		$articleDao = &DAORegistry::getDAO('ArticleDAO');
		$publishedArticleDao = &DAORegistry::getDAO('PublishedArticleDAO');

		while (list($articleId, $publicArticleId) = each($publishedArticles)) {
			$article = $articleDao->getArticle($articleId);
			if (!isset($removedArticles[$articleId])) {
				if (!$publicArticleId || !$articleDao->publicArticleIdExists($publicArticleId, $articleId)) {
					$article->setPublicArticleId($publicArticleId);
				}
			} else {
				$pubId = $removedArticles[$articleId];
				$article->setStatus(SCHEDULED);
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

		Request::redirect(sprintf('%s/issueManagement/issueToc/%d', Request::getRequestedPage(), $issueId));
	}

	/**
	 * Change the sequence of a section.
	 */
	function moveSectionToc($args) {
		IssueManagementHandler::validate();

		$issueId = isset($args[0]) ? (int) $args[0] : 0;
		
		$journal = &Request::getJournal();
		
		$sectionDao = &DAORegistry::getDAO('SectionDAO');
		$section = &$sectionDao->getSection(Request::getUserVar('sectionId'), $journal->getJournalId());
		
		if ($section != null) {
			$section->setSequence($section->getSequence() + (Request::getUserVar('d') == 'u' ? -1.5 : 1.5));
			$sectionDao->updateSection($section);
			$sectionDao->resequenceSections($journal->getJournalId());
		}

		Request::redirect(sprintf('%s/issueManagement/issueToc/%d', Request::getRequestedPage(), $issueId));
	}

	/**
	 * Change the sequence of the articles.
	 */
	function moveArticleToc($args) {
		IssueManagementHandler::validate();

		$issueId = isset($args[0]) ? (int) $args[0] : 0;
		
		$journal = &Request::getJournal();
		
		$publishedArticleDao = &DAORegistry::getDAO('PublishedArticleDAO');
		$publishedArticle = &$publishedArticleDao->getPublishedArticleById(Request::getUserVar('pubId'));
		
		if ($publishedArticle != null) {
			$publishedArticle->setSeq($publishedArticle->getSeq() + (Request::getUserVar('d') == 'u' ? -1.5 : 1.5));
			$publishedArticleDao->updatePublishedArticle($publishedArticle);
			$publishedArticleDao->resequencePublishedArticles(Request::getUserVar('sectionId'),$issueId);
		}

		Request::redirect(sprintf('%s/issueManagement/issueToc/%d', Request::getRequestedPage(), $issueId));
	}

	/**
	 * publish issue
	 */
	function publishIssue($args) {
		IssueManagementHandler::validate();

		$journal = &Request::getJournal();
		$journalId = $journal->getJournalId();

		$issueId = isset($args[0]) ? (int) $args[0] : 0;

		$issueDao = &DAORegistry::getDAO('IssueDAO');
		$issue = $issueDao->getIssueById($issueId);
		$issue->setCurrent(1);
		$issue->setPublished(1);
		$issue->setDatePublished(Core::getCurrentDate());

		$issueDao->updateCurrentIssue($journalId,$issue);

		Request::redirect(sprintf('%s/issueManagement/issueToc/%d', Request::getRequestedPage(), $issueId));
	}

	/**
	 * Displays the issue data page
	 */
	function issueData($issueId) {
		IssueManagementHandler::validate();

		$templateMgr = &TemplateManager::getManager();

		import('issue.form.IssueForm');
		
		$issueForm = &new IssueForm('editor/issueManagement.tpl');
		$issueId = $issueForm->initData($issueId);
		$templateMgr->assign('issueId', $issueId);

		IssueManagementHandler::setupTemplate(false, $issueId);
		$issueForm->display();
	}

	/**
	 * Edit the current issue form
	 */
	function editIssue($args) {
		IssueManagementHandler::validate();

		$templateMgr = &TemplateManager::getManager();

		// retrieve specified issue id otherwise set to default
		$issueId = isset($args[0]) ? (int) $args[0] : 0;
		$templateMgr->assign('issueId', $issueId);

		$templateMgr->assign('subsection','issueData');

		$journal = &Request::getJournal();
		$journalId = $journal->getJournalId();

		$issueOptions = IssueManagementHandler::getIssueOptions($journalId,3);
		$templateMgr->assign('issueOptions', $issueOptions);

		import('issue.form.IssueForm');
		$issueForm = &new IssueForm('editor/issueManagement.tpl');
		$issueForm->readInputData();

		if ($issueForm->validate($issueId)) {
			$issueForm->execute($issueId);
			$issueForm->initData($issueId);
		}

		IssueManagementHandler::setupTemplate(false, $issueId);
		$issueForm->display();
	}

	/**
	 * delete front matter
	 */
	function removeCoverPage($args) {
		IssueManagementHandler::validate();

		$issueId = isset($args[0]) ? (int)$args[0] : 0;

		$issueDao = &DAORegistry::getDAO('IssueDAO');
		$issue = $issueDao->getIssueById($issueId);

		if (isset($issue)) {
			import('file.FrontMatterManager');
			$frontMatterManager = new FrontMatterManager($issueId);
			$frontMatterManager->deleteFile($issue->getFileName());
			$issue->setFileName('');
			$issue->setOriginalFileName('');
			$issueDao->updateIssue($issue);
		}

		Request::redirect(sprintf('%s/issueManagement/issueData/%d', Request::getRequestedPage(), $issueId));
	}

	/**
	 * builds the issue options pulldown with only unpublished issues
	 */
	function getIssueOptions($journalId, $listStart = 0) {

		$vol = Locale::Translate('editor.issues.vol');
		$no = Locale::Translate('editor.issues.no');

		$issueDao = &DAORegistry::getDAO('IssueDAO');
		$issues = $issueDao->getIssues($journalId);

		$publishedOptions = array();
		$currentOptions = array();
		$unpublishedOptions = array();
		$issueOptions = array();

		foreach ($issues as $issue) {
			$label = "$vol " . $issue->getVolume() . ", $no " . $issue->getNumber() . ' (' . $issue->getYear() . ')';
			switch($issue->getPublished()) {
				case '0':
					$unpublishedOptions[$issue->getIssueId()] = $label;
					break;
				case '1':
					if (!$issue->getCurrent()) {
						$publishedOptions[$issue->getIssueId()] = $label;
					} else {
						$currentOptions[$issue->getIssueId()] = $label;
					}
					break;
			}
		}

		switch($listStart) {
			case '0':
				if (!empty($publishedOptions)) $issueOptions[Locale::translate('editor.issues.backIssues')] = $publishedOptions;
			case '1':
				if (!empty($currentOptions)) $issueOptions[Locale::translate('editor.issues.liveIssue')] = $currentOptions;
			case '2':
				if (!empty($unpublishedOptions)) $issueOptions[Locale::translate('editor.issues.unpublishedIssues')] = $unpublishedOptions;
				break;
			case '3':
				if (!empty($unpublishedOptions)) return $unpublishedOptions;
				break;
			case '4':
				if (!empty($unpublishedOptions) || !empty($currentOptions)) return $currentOptions + $unpublishedOptions;
				break;
		}
		return $issueOptions;
	}

	/**
	 * downloads a file
	 */
	function download($args) {
		IssueManagementHandler::validate();

		$issueId = isset($args[0]) ? (int) $args[0] : 0;
		$fileName = isset($args[1]) ? $args[1] : 0;

		import('file.FrontMatterManager');
		$frontMatterManager = new FrontMatterManager($issueId);
		$frontMatterManager->download($fileName);
	}

	/**
	 * Validate that user is an editor in the selected journal.
	 * Redirects to user index page if not properly authenticated.
	 */
	function validate($issueId = 0) {
		parent::validate();
		$journal = &Request::getJournal();
		if (!isset($journal) || !Validation::isEditor($journal->getJournalId())) {
			Request::redirect('user');
		}
		if ($issueId) {
			$issueDao = &DAORegistry::getDAO('IssueDAO');
			if (!$issueDao->issueIdExists($issueId)) {
				Request::redirect(sprintf('%s/createIssue', Request::getRequestedPage()));
			}
		}

	}

	/**
	 * Setup common template variables.
	 * @param $subclass boolean set to true if caller is below this handler in the hierarchy
	 */
	function setupTemplate($subclass = false, $issueId = 0) {
		$templateMgr = &TemplateManager::getManager();

		if ($issueId) {
			$issueDao = &DAORegistry::getDAO('IssueDAO');
			$issue = $issueDao->getIssueById($issueId);

			$vol = Locale::Translate('editor.issues.vol');
			$no = Locale::Translate('editor.issues.no');
			$pageTitle = "$vol " . $issue->getVolume() . ", $no " . $issue->getNumber() . ' (' . $issue->getYear() . ')';

			$currentUrl = sprintf('%s/editor/issueManagement/issueToc/%d', Request::getPageUrl(), $issueId);
			$templateMgr->assign('pageTitleTranslated', $pageTitle);
			$templateMgr->assign('currentUrl', $currentUrl);
		}

		$templateMgr->assign('pageHierarchy',
			$subclass ? array(array('user', 'navigation.user'), array('editor', 'editor.journalEditor'), array('editor/issueManagement', 'editor.issueManagement'))
				: array(array('user', 'navigation.user'), array('editor', 'editor.journalEditor'))
		);
		$templateMgr->assign('pagePath', '/user/editor/');

		$templateMgr->assign('sidebarTemplate', 'editor/navsidebar.tpl');
		$journal = &Request::getJournal();
		$editorSubmissionDao = &DAORegistry::getDAO('EditorSubmissionDAO');
		$submissionsCount = &$editorSubmissionDao->getEditorSubmissionsCount($journal->getJournalId());
		$templateMgr->assign('submissionsCount', $submissionsCount);

	}
	
}

?>
