<?php

/**
 * @file pages/user/EmailHandler.inc.php
 *
 * Copyright (c) 2013-2015 Simon Fraser University Library
 * Copyright (c) 2003-2015 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class EmailHandler
 * @ingroup pages_user
 *
 * @brief Handle requests for user emails.
 */

import('pages.user.UserHandler');

class EmailHandler extends UserHandler {
	/**
	 * Constructor
	 **/
	function EmailHandler() {
		parent::UserHandler();
	}

	/**
	 * Display a "send email" template or send an email.
	 * @param $args array
	 * @param $request PKPRequest
	 */
	function email($args, &$request) {
		$this->validate();

		$this->setupTemplate($request, true);

		$templateMgr =& TemplateManager::getManager();

		$signoffDao =& DAORegistry::getDAO('SignoffDAO');
		$userDao =& DAORegistry::getDAO('UserDAO');

		$journal =& $request->getJournal();
		$user =& $request->getUser();

		// See if this is the Editor or Manager and an email template has been chosen
		$template = $request->getUserVar('template');
		if (	!$journal || empty($template) || (
			!Validation::isJournalManager($journal->getId()) &&
			!Validation::isEditor($journal->getId()) &&
			!Validation::isSectionEditor($journal->getId())
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
			$roles =& $roleDao->getRolesByUserId($user->getId(), $journal->getId());
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
		if ($articleId = $request->getUserVar('articleId')) {
			// This message is in reference to an article.
			// Determine whether the current user has access
			// to the article in some form, and if so, use an
			// ArticleMailTemplate.
			$articleDao =& DAORegistry::getDAO('ArticleDAO');

			$article =& $articleDao->getArticle($articleId);
			$hasAccess = false;

			// First, conditions where access is OK.
			// 1. User is submitter
			if ($article && $article->getUserId() == $user->getId()) $hasAccess = true;
			// 2. User is section editor of article or full editor
			$editAssignmentDao =& DAORegistry::getDAO('EditAssignmentDAO');
			$editAssignments =& $editAssignmentDao->getEditAssignmentsByArticleId($articleId);
			while ($editAssignment =& $editAssignments->next()) {
				if ($editAssignment->getEditorId() === $user->getId()) $hasAccess = true;
			}
			if (Validation::isEditor($journal->getId())) $hasAccess = true;

			// 3. User is reviewer
			$reviewAssignmentDao =& DAORegistry::getDAO('ReviewAssignmentDAO');
			foreach ($reviewAssignmentDao->getBySubmissionId($articleId) as $reviewAssignment) {
				if ($reviewAssignment->getReviewerId() === $user->getId()) $hasAccess = true;
			}
			// 4. User is copyeditor
			$copyedSignoff =& $signoffDao->getBySymbolic('SIGNOFF_COPYEDITING_INITIAL', ASSOC_TYPE_ARTICLE, $articleId);
			if ($copyedSignoff && $copyedSignoff->getUserId() === $user->getId()) $hasAccess = true;
			// 5. User is layout editor
			$layoutSignoff =& $signoffDao->getBySymbolic('SIGNOFF_LAYOUT', ASSOC_TYPE_ARTICLE, $articleId);
			if ($layoutSignoff && $layoutSignoff->getUserId() === $user->getId()) $hasAccess = true;
			// 6. User is proofreader
			$proofSignoff =& $signoffDao->getBySymbolic('SIGNOFF_PROOFREADING_PROOFREADER', ASSOC_TYPE_ARTICLE, $articleId);
			if ($proofSignoff && $proofSignoff->getUserId() === $user->getId()) $hasAccess = true;

			// Last, "deal-breakers" -- access is not allowed.
			if (!$article || ($article && $article->getJournalId() !== $journal->getId())) $hasAccess = false;

			if ($hasAccess) {
				import('classes.mail.ArticleMailTemplate');
				$email = new ArticleMailTemplate($articleDao->getArticle($articleId, $template));
			}
		}

		if ($email === null) {
			import('classes.mail.MailTemplate');
			$email = new MailTemplate($template);
		}

		if ($request->getUserVar('send') && !$email->hasErrors()) {
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
			if (is_a($email, 'ArticleMailTemplate')) {
				// Make sure the email gets logged if needed
				$email->send($request);
			} else {
				$email->send();
			}
			$redirectUrl = $request->getUserVar('redirectUrl');
			if (empty($redirectUrl)) $redirectUrl = $request->url(null, 'user');
			$user->setDateLastEmail(Core::getCurrentDate());
			$userDao->updateObject($user);
			$request->redirectUrl($redirectUrl);
		} else {
			$email->displayEditForm($request->url(null, null, 'email'), array('redirectUrl' => $request->getUserVar('redirectUrl'), 'articleId' => $articleId), null, array('disableSkipButton' => true, 'articleId' => $articleId));
		}
	}
}

?>
