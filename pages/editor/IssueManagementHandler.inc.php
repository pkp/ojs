<?php

/**
 * @file pages/editor/IssueManagementHandler.inc.php
 *
 * Copyright (c) 2003-2013 John Willinsky
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
	 * Displays the listings of back (published) issues
	 * @param $args array
	 * @param $request PKPRequest
	 */
	function issues($args, $request) {
		$this->validate($request);
		$this->setupTemplate($request);

		$templateMgr = TemplateManager::getManager($request);
		$templateMgr->display('editor/issues/issues.tpl');
	}

	/**
	 * Remove cover page from issue
	 * @param $args array
	 * @param $request PKPRequest
	 */
	function removeIssueCoverPage($args, $request) {
		$issueId = (int) array_shift($args);
		$this->validate($request, $issueId, true);

		$formLocale = array_shift($args);
		if (!AppLocale::isLocaleValid($formLocale)) {
			$request->redirect(null, null, 'issueData', $issueId);
		}

		$journal = $request->getJournal();
		$issue = $this->issue;

		import('classes.file.PublicFileManager');
		$publicFileManager = new PublicFileManager();
		$publicFileManager->removeJournalFile($journal->getId(),$issue->getFileName($formLocale));
		$issue->setFileName('', $formLocale);
		$issue->setOriginalFileName('', $formLocale);
		$issue->setWidth('', $formLocale);
		$issue->setHeight('', $formLocale);

		$issueDao = DAORegistry::getDAO('IssueDAO');
		$issueDao->updateObject($issue);

		$request->redirect(null, null, 'issueData', $issueId);
	}

	/**
	 * Remove style file from issue
	 * @param $args array
	 * @param $request PKPRequest
	 */
	function removeStyleFile($args, $request) {
		$issueId = (int) array_shift($args);
		$this->validate($request, $issueId, true);
		$issue =& $this->issue;

		import('classes.file.PublicFileManager');
		$journal = $request->getJournal();
		$publicFileManager = new PublicFileManager();
		$publicFileManager->removeJournalFile($journal->getId(),$issue->getStyleFileName());
		$issue->setStyleFileName('');
		$issue->setOriginalStyleFileName('');

		$issueDao = DAORegistry::getDAO('IssueDAO');
		$issueDao->updateObject($issue);

		$request->redirect(null, null, 'issueData', $issueId);
	}

	/**
	 * Create a new issue galley with the uploaded file.
	 * @param $args array
	 * @param $request PKPRequest
	 */
	function uploadIssueGalley($args, $request) {
		$issueId = (int) array_shift($args);
		$this->validate($request, $issueId, true);

		import('classes.issue.form.IssueGalleyForm');
		$galleyForm = new IssueGalleyForm($issueId);

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

		$this->validate($request, $issueId, true);
		$this->setupTemplate($request);

		import('classes.issue.form.IssueGalleyForm');
		$submitForm = new IssueGalleyForm($issueId, $galleyId);

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

		$this->validate($request, $issueId, true);
		$this->setupTemplate($request);

		import('classes.issue.form.IssueGalleyForm');
		$submitForm = new IssueGalleyForm($issueId, $galleyId);

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

		$this->validate($request, $issueId, true);

		$galleyDao = DAORegistry::getDAO('IssueGalleyDAO');
		$galley = $galleyDao->getById($galleyId, $issueId);

		if (isset($galley)) {
			$galley->setSequence($galley->getSequence() + ($direction == 'u' ? -1.5 : 1.5));
			$galleyDao->updateObject($galley);
			$galleyDao->resequence($issueId);
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

		$this->validate($request, $issueId, true);

		$galleyDao = DAORegistry::getDAO('IssueGalleyDAO');
		$galley = $galleyDao->getById($galleyId, $issueId);

		if (isset($galley)) {
			import('classes.file.IssueFileManager');
			$issueFileManager = new IssueFileManager($issueId);

			if ($galley->getFileId()) {
				$issueFileManager->deleteFile($galley->getFileId());
			}
			$galleyDao->deleteObject($galley);
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

		$this->validate($request, $issueId, true);
		$this->setupTemplate($request);

		$templateMgr = TemplateManager::getManager($request);
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

		$this->validate($request, $issueId, true);
		$this->setupTemplate($request);

		$templateMgr = TemplateManager::getManager($request);
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

		$this->validate($request, $issueId, true);

		$galleyDao = DAORegistry::getDAO('IssueGalleyDAO');
		$galley = $galleyDao->getById($galleyId, $issueId);

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

		$this->validate($request, $issueId, true);

		if ($fileId) {
			import('classes.file.IssueFileManager');
			$issueFileManager = new IssueFileManager($issueId);
			return $issueFileManager->downloadFile($fileId);
		}
		$request->redirect(null, null, 'issueGalleys', $issueId);
	}


	/**
	 * Change the sequence of an issue.
	 * @param $args array
	 * @param $request PKPRequest
	 */
	function setCurrentIssue($args, $request) {
		$issueId = (int) $request->getUserVar('issueId');
		$journal = $request->getJournal();
		$issueDao = DAORegistry::getDAO('IssueDAO');
		if ($issueId) {
			$this->validate($request, $issueId);
			$issue = $this->issue;
			$issue->setCurrent(1);
			$issueDao->updateCurrent($journal->getId(), $issue);
		} else {
			$this->validate($request);
			$issueDao->updateCurrent($journal->getId());
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
		$this->validate($request, $issueId);
		$prevId = (int) $request->getUserVar('prevId');
		$nextId = (int) $request->getUserVar('nextId');
		$issue = $this->issue;
		$journal = $request->getJournal();
		$journalId = $journal->getId();

		$issueDao = DAORegistry::getDAO('IssueDAO');

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
		$this->validate($request);

		$journal = $request->getJournal();

		$issueDao = DAORegistry::getDAO('IssueDAO');
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
		$this->validate($request, $issueId, true);
		$issue = $this->issue;
		$journal = $request->getJournal();

		$sectionDao = DAORegistry::getDAO('SectionDAO');
		$section = $sectionDao->getById($request->getUserVar('sectionId'), $journal->getId());

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
		$this->validate($request, $issueId, true);
		$issue =& $this->issue;

		$sectionDao = DAORegistry::getDAO('SectionDAO');
		$sectionDao->deleteCustomSectionOrdering($issueId);

		$request->redirect(null, null, 'issueToc', $issue->getId());
	}

	/**
	 * Change the sequence of the articles.
	 * @param $args array
	 * @param $request PKPRequest
	 */
	function moveArticleToc($args, $request) {
		$this->validate($request, null, true);
		$pubId = (int) $request->getUserVar('id');

		$journal = $request->getJournal();

		$publishedArticleDao = DAORegistry::getDAO('PublishedArticleDAO');
		$issueDao = DAORegistry::getDAO('IssueDAO');

		$publishedArticle =& $publishedArticleDao->getPublishedArticleById($pubId);

		if (!$publishedArticle) $request->redirect(null, null, 'index');

		$articleId = $publishedArticle->getId();
		$articleDao = DAORegistry::getDAO('ArticleDAO');
		$article = $articleDao->getById($articleId, $journal->getId());

		$issue = $issueDao->getById($publishedArticle->getIssueId());

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
	 * Allows editors to write emails to users associated with the journal.
	 * @param $args array
	 * @param $request PKPRequest
	 */
	function notifyUsers($args, $request) {
		$this->validate($request, (int) $request->getUserVar('issue'));
		$issue =& $this->issue;
		$this->setupTemplate($request);

		$userDao = DAORegistry::getDAO('UserDAO');
		$issueDao = DAORegistry::getDAO('IssueDAO');
		$roleDao = DAORegistry::getDAO('RoleDAO');
		$authorDao = DAORegistry::getDAO('AuthorDAO');
		$individualSubscriptionDao = DAORegistry::getDAO('IndividualSubscriptionDAO');
		$institutionalSubscriptionDao = DAORegistry::getDAO('InstitutionalSubscriptionDAO');

		$journal = $request->getJournal();
		$user = $request->getUser();
		$templateMgr = TemplateManager::getManager($request);

		import('lib.pkp.classes.mail.MassMail');
		$email = new MassMail('PUBLISH_NOTIFY');

		if ($request->getUserVar('send') && !$email->hasErrors()) {
			if($request->getUserVar('ccSelf')) {
				$email->addRecipient($user->getEmail(), $user->getFullName());
			}

			switch ($request->getUserVar('whichUsers')) {
				case 'allIndividualSubscribers':
					$recipients = $individualSubscriptionDao->getSubscribedUsers($journal->getId());
					break;
				case 'allInstitutionalSubscribers':
					$recipients = $institutionalSubscriptionDao->getSubscribedUsers($journal->getId());
					break;
				case 'allAuthors':
					$recipients = $authorDao->getAuthorsAlphabetizedByJournal($journal->getId(), null, null, true);
					break;
				case 'allUsers':
					$recipients = $roleDao->getUsersByJournalId($journal->getId());
					break;
				case 'allReaders':
				default:
					$recipients = $roleDao->getUsersByRoleId(
						ROLE_ID_READER,
						$journal->getId()
					);
					break;
			}

			import('lib.pkp.classes.validation.ValidatorEmail');
			while ($recipient = $recipients->next()) {
				if (preg_match(ValidatorEmail::getRegexp(), $recipient->getEmail())) {
					$email->addRecipient($recipient->getEmail(), $recipient->getFullName());
				} else {
					error_log("Invalid email address: " . $recipient->getEmail());
				}
			}

			if ($request->getUserVar('includeToc')=='1' && isset($issue)) {
				$issue = $issueDao->getById($request->getUserVar('issue'));

				$publishedArticleDao = DAORegistry::getDAO('PublishedArticleDAO');
				$publishedArticles =& $publishedArticleDao->getPublishedArticlesInSections($issue->getId());

				$templateMgr->assign_by_ref('journal', $journal);
				$templateMgr->assign_by_ref('issue', $issue);
				$templateMgr->assign('body', $email->getBody());
				$templateMgr->assign_by_ref('publishedArticles', $publishedArticles);

				$email->setBody($templateMgr->fetch('editor/notifyUsersEmail.tpl'));

				// Stamp the "users notified" date.
				$issue->setDateNotified(Core::getCurrentDate());
				$issueDao->updateObject($issue);
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

			$issuesIterator = $issueDao->getIssues($journal->getId());

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
				)
			);
		}
	}

	/**
	 * Validate that user is an editor in the selected journal and if the issue id is valid
	 * Redirects to issue create issue page if not properly authenticated.
	 * NOTE: As of OJS 2.2, Layout Editors are allowed if specified in args.
	 * @param $request PKPRequest
	 * @param $issueId int optional
	 * @param $allowLayoutEditor boolean optional
	 */
	function validate($request, $issueId = null, $allowLayoutEditor = false) {
		$issue = null;
		$journal = $request->getJournal();

		if (!isset($journal)) Validation::redirectLogin();

		if (!empty($issueId)) {
			$issueDao = DAORegistry::getDAO('IssueDAO');
			$issue = $issueDao->getById($issueId, $journal->getId());

			if (!$issue) {
				$request->redirect(null, null, 'createIssue');
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
}

?>
