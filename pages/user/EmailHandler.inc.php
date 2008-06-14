<?php

/**
 * @file EmailHandler.inc.php
 *
 * Copyright (c) 2003-2008 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package pages.user
 * @class EmailHandler
 *
 * Handle requests for user emails.
 *
 * $Id$
 */

class EmailHandler extends UserHandler {
	function email($args) {
		parent::validate();

		parent::setupTemplate(true);

		$templateMgr = &TemplateManager::getManager();

		$userDao = &DAORegistry::getDAO('UserDAO');

		$journal = &Request::getJournal();
		$user = &Request::getUser();

		// See if this is the Editor or Manager and an email template has been chosen
		$template = Request::getUserVar('template');
		if (	!$journal || empty($template) || (
			!Validation::isJournalManager($journal->getJournalId()) &&
			!Validation::isEditor($journal->getJournalId()) &&
			!Validation::isSectionEditor($journal->getJournalId())
		)) {
			$template = null;
		}

		// Determine whether or not this account is subject to
		// email sending restrictions.
		$canSendUnlimitedEmails = Validation::isSiteAdmin();
		$unlimitedEmailRoles = array(
			ROLE_ID_JOURNAL_MANAGER,
			ROLE_ID_EDITOR,
			ROLE_ID_SECTION_EDITOR
		);
		$roleDao =& DAORegistry::getDAO('RoleDAO');
		if ($journal) {
			$roles =& $roleDao->getRolesByUserId($user->getUserId(), $journal->getJournalId());
			foreach ($roles as $role) {
				if (in_array($role->getRoleId(), $unlimitedEmailRoles)) $canSendUnlimitedEmails = true;
			}
		}

		// Check when this user last sent an email, and if it's too
		// recent, make them wait.
		if (!$canSendUnlimitedEmails) {
			$dateLastEmail = $user->getDateLastEmail();
			if ($dateLastEmail && strtotime($dateLastEmail) + ((int) Config::getVar('email', 'time_between_emails')) > strtotime(Core::getCurrentDate())) {
				$templateMgr->assign('pageTitle', 'email.compose');
				$templateMgr->assign('message', 'email.compose.tooSoon');
				$templateMgr->assign('backLink', 'javascript:history.back()');
				$templateMgr->assign('backLinkLabel', 'email.compose');
				return $templateMgr->display('common/message.tpl');
			}
		}

		$email = null;
		if ($articleId = Request::getUserVar('articleId')) {
			// This message is in reference to an article.
			// Determine whether the current user has access
			// to the article in some form, and if so, use an
			// ArticleMailTemplate.
			$articleDao =& DAORegistry::getDAO('ArticleDAO');

			$article =& $articleDao->getArticle($articleId);
			$hasAccess = false;

			// First, conditions where access is OK.
			// 1. User is submitter
			if ($article && $article->getUserId() == $user->getUserId()) $hasAccess = true;
			// 2. User is section editor of article or full editor
			$editAssignmentDao =& DAORegistry::getDAO('EditAssignmentDAO');
			$editAssignments =& $editAssignmentDao->getEditAssignmentsByArticleId($articleId);
			while ($editAssignment =& $editAssignments->next()) {
				if ($editAssignment->getEditorId() === $user->getUserId()) $hasAccess = true;
			}
			if (Validation::isEditor($journal->getJournalId())) $hasAccess = true;

			// 3. User is reviewer
			$reviewAssignmentDao =& DAORegistry::getDAO('ReviewAssignmentDAO');
			foreach ($reviewAssignmentDao->getReviewAssignmentsByArticleId($articleId) as $reviewAssignment) {
				if ($reviewAssignment->getReviewerId() === $user->getUserId()) $hasAccess = true;
			}
			// 4. User is copyeditor
			$copyAssignmentDao =& DAORegistry::getDAO('CopyAssignmentDAO');
			$copyAssignment =& $copyAssignmentDao->getCopyAssignmentByArticleId($articleId);
			if ($copyAssignment && $copyAssignment->getCopyeditorId() === $user->getUserId()) $hasAccess = true;
			// 5. User is layout editor
			$layoutAssignmentDao =& DAORegistry::getDAO('LayoutAssignmentDAO');
			$layoutAssignment =& $layoutAssignmentDao->getLayoutAssignmentByArticleId($articleId);
			if ($layoutAssignment && $layoutAssignment->getEditorId() === $user->getUserId()) $hasAccess = true;
			// 6. User is proofreader
			$proofAssignmentDao =& DAORegistry::getDAO('ProofAssignmentDAO');
			$proofAssignment =& $proofAssignmentDao->getProofAssignmentByArticleId($articleId);
			if ($proofAssignment && $proofAssignment->getProofreaderId() === $user->getUserId()) $hasAccess = true;

			// Last, "deal-breakers" -- access is not allowed.
			if (!$article || ($article && $article->getJournalId() !== $journal->getJournalId())) $hasAccess = false;

			if ($hasAccess) {
				import('mail.ArticleMailTemplate');
				$email =& new ArticleMailTemplate($articleDao->getArticle($articleId, $template));
			}
		}

		if ($email === null) {
			import('mail.MailTemplate');
			$email = &new MailTemplate($template);
		}

		if (Request::getUserVar('send') && !$email->hasErrors()) {
			$recipients = $email->getRecipients();
			$ccs = $email->getCcs();
			$bccs = $email->getBccs();

			// Make sure there aren't too many recipients (to
			// prevent use as a spam relay)
			$recipientCount = 0;
			if (is_array($recipients)) $recipientCount += count($recipients);
			if (is_array($ccs)) $recipientCount += count($ccs);
			if (is_array($bccs)) $recipientCount += count($bccs);

			if (!$canSendUnlimitedEmails && $recipientCount > ((int) Config::getVar('email', 'max_recipients'))) {
				$templateMgr->assign('pageTitle', 'email.compose');
				$templateMgr->assign('message', 'email.compose.tooManyRecipients');
				$templateMgr->assign('backLink', 'javascript:history.back()');
				$templateMgr->assign('backLinkLabel', 'email.compose');
				return $templateMgr->display('common/message.tpl');
			}
			$email->send();
			$redirectUrl = Request::getUserVar('redirectUrl');
			if (empty($redirectUrl)) $redirectUrl = Request::url(null, 'user');
			$user->setDateLastEmail(Core::getCurrentDate());
			$userDao->updateUser($user);
			Request::redirectUrl($redirectUrl);
		} else {
			if (!Request::getUserVar('continued')) {
				// Check for special cases.

				// 1. If the parameter authorsArticleId is set, preload
				// the template with all the authors of the specified
				// article ID as recipients and use the article title
				// as a subject.
				if (Request::getUserVar('authorsArticleId')) {
					$articleDao = &DAORegistry::getDAO('ArticleDAO');
					$article = $articleDao->getArticle(Request::getUserVar('authorsArticleId'));
					if (isset($article) && $article != null) {
						foreach ($article->getAuthors() as $author) {
							$email->addRecipient($author->getEmail(), $author->getFullName());
						}
						$email->setSubject($email->getSubject() . strip_tags($article->getArticleTitle()));
					}
				}
			}
			$email->displayEditForm(Request::url(null, null, 'email'), array('redirectUrl' => Request::getUserVar('redirectUrl'), 'articleId' => $articleId), null, array('disableSkipButton' => true, 'articleId' => $articleId));
		}
	}
}

?>
