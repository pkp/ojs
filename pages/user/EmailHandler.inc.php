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

		$site = &Request::getSite();
		$journal = &Request::getJournal();
		$user = &Request::getUser();

		$email = &new MailTemplate();
		$email->setFrom($user->getEmail(), $user->getFullName());
		
		if (Request::getUserVar('send')) {
			$email->send();
			Request::redirect(Request::getUserVar('redirectUrl'));
		} else {
			$email->displayEditForm(Request::getPageUrl() . '/' . Request::getRequestedPage() . '/email', array('redirectUrl' => Request::getUserVar('redirectUrl')));
		}
	}
}

?>
