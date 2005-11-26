<?php

/**
 * EmailHandler.inc.php
 *
 * Copyright (c) 2003-2005 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package pages.user
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

		import('mail.MailTemplate');
		$email = &new MailTemplate();
		
		if (Request::getUserVar('send') && !$email->hasErrors()) {
			$email->send();
			Request::redirectUrl(Request::getUserVar('redirectUrl'));
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
			$email->displayEditForm(Request::url(null, null, 'email'), array('redirectUrl' => Request::getUserVar('redirectUrl')));
		}
	}
}

?>
