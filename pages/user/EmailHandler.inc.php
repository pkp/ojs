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

		if (isset($args[0]) && $args[0] == 'send') {
			echo "FIXME not implemented yet.<br/>\n";
		} else {
			$templateMgr->assign('user', Request::getUser());
			$templateMgr->assign('profileLocalesEnabled', $site->getProfileLocalesEnabled());
			$templateMgr->assign('localeNames', Locale::getAllLocales());
			$templateMgr->display('user/email.tpl');
		}
	}
}

?>
