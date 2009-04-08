<?php

/**
 * @file ReferralHandler.inc.php
 *
 * Copyright (c) 2003-2009 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class ReferralHandler
 * @ingroup plugins_generic_referral
 *
 * @brief This handles requests for the referral plugin.
 */

// $Id$


import('core.PKPHandler');

class ReferralHandler extends PKPHandler {
	function setupTemplate() {
		parent::setupTemplate();
		$templateMgr =& TemplateManager::getManager();
		$pageHierarchy = array(array(Request::url(null, 'referral', 'index'), 'plugins.generic.referral.referrals'));
		$templateMgr->assign('pageHierarchy', $pageHierarchy);
	}

	function editReferral($args) {
		$referralId = (int) array_shift($args);
		if ($referralId === 0) $referralId = null;

		list($plugin, $referral, $article) = ReferralHandler::validate($referralId);
		ReferralHandler::setupTemplate();

		$plugin->import('ReferralForm');
		$templateMgr =& TemplateManager::getManager();

		if ($referralId == null) {
			$templateMgr->assign('referralTitle', 'plugins.generic.referral.createReferral');
		} else {
			$templateMgr->assign('referralTitle', 'plugins.generic.referral.editReferral');	
		}

		// FIXME: Need construction by reference or validation always fails on PHP 4.x
		$referralForm =& new ReferralForm($plugin, $article, $referralId);
		if ($referralForm->isLocaleResubmit()) {
			$referralForm->readInputData();
		} else {
			$referralForm->initData();
		}
		$referralForm->display();
	}

	/**
	 * Save changes to an announcement type.
	 */
	function updateReferral() {
		$referralId = (int) Request::getUserVar('referralId');
		if ($referralId === 0) $referralId = null;

		list($plugin, $referral, $article) = ReferralHandler::validate($referralId);
		// If it's an insert, ensure that it's allowed for this article
		if (!isset($referral)) {
			$publishedArticleDao =& DAORegistry::getDAO('PublishedArticleDAO');
			$journal =& Request::getJournal();
			$article =& $publishedArticleDao->getPublishedArticleByArticleId((int) Request::getUserVar('articleId'));
			if (!$article || ($article->getUserId() != $user->getUserId() && !Validation::isSectionEditor($journal->getJournalId()) && !Validation::isEditor($journal->getJournalId()))) {
				Request::redirect(null, 'author');
			}
		}
		ReferralHandler::setupTemplate();

		$plugin->import('ReferralForm');

		// FIXME: Need construction by reference or validation always fails on PHP 4.x
		$referralForm =& new ReferralForm($plugin, $article, $referralId);
		$referralForm->readInputData();

		if ($referralForm->validate()) {
			$referralForm->execute();
			Request::redirect(null, 'author');
		} else {
			$templateMgr =& TemplateManager::getManager();

			if ($referralId == null) {
				$templateMgr->assign('referralTitle', 'plugins.generic.referral.createReferral');
			} else {
				$templateMgr->assign('referralTitle', 'plugins.generic.referral.editReferral');	
			}

			$referralForm->display();
		}
	}	

	function deleteReferral($args) {
		$referralId = (int) array_shift($args);
		list($plugin, $referral) = ReferralHandler::validate($referralId);

		$referralDao =& DAORegistry::getDAO('ReferralDAO');
		$referralDao->deleteReferral($referral);

		Request::redirect(null, 'author');
	}

	function validate($referralId = null) {
		if ($referralId) {
			$referralDao =& DAORegistry::getDAO('ReferralDAO');
			$publishedArticleDao =& DAORegistry::getDAO('PublishedArticleDAO');
			$referral =& $referralDao->getReferral($referralId);
			if (!$referral) Request::redirect(null, 'index');

			$user =& Request::getUser();
			$journal =& Request::getJournal();
			$article =& $publishedArticleDao->getPublishedArticleByArticleId($referral->getArticleId());
			if (!$article || !$journal) Request::redirect(null, 'index');
			if ($article->getJournalId() != $journal->getJournalId()) Request::redirect(null, 'index');
			// The article's submitter, journal SE, and journal Editors are allowed.
			if ($article->getUserId() != $user->getUserId() && !Validation::isSectionEditor($journal->getJournalId()) && !Validation::isEditor($journal->getJournalId())) Request::redirect(null, 'index');
		} else {
			$referral = $article = null;
		}
		$plugin =& Registry::get('plugin');
		return array(&$plugin, &$referral, &$article);
	}
}

?>
