<?php

/**
 * @file plugins/generic/referral/ReferralPlugin.inc.php
 *
 * Copyright (c) 2003-2009 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class ReferralPlugin
 * @ingroup plugins_generic_referral
 *
 * @brief Referral plugin to track and maintain potential references to published articles
 */

// $Id$


import('classes.plugins.GenericPlugin');

class ReferralPlugin extends GenericPlugin {
	/**
	 * Register the plugin, if enabled; note that this plugin
	 * runs under both Journal and Site contexts.
	 * @param $category string
	 * @param $path string
	 * @return boolean
	 */
	function register($category, $path) {
		if (parent::register($category, $path)) {
			$this->addLocaleData();
			if ($this->getEnabled()) {
				HookRegistry::register ('TemplateManager::display', array(&$this, 'handleTemplateDisplay'));
				HookRegistry::register ('LoadHandler', array(&$this, 'handleLoadHandler'));
				$this->import('Referral');
				$this->import('ReferralDAO');
				$referralDao = new ReferralDAO();
				DAORegistry::registerDAO('ReferralDAO', $referralDao);
			}
			return true;
		}
		return false;
	}

	function handleLoadHandler($hookName, $args) {
		$page =& $args[0];
		$op =& $args[1];
		$sourceFile =& $args[2];

		if ($page === 'referral') {
			$this->import('ReferralHandler');
			Registry::set('plugin', $this);
			define('HANDLER_CLASS', 'ReferralHandler');
			return true;
		}

		return false;
	}

	function handleAuthorTemplateInclude($hookName, $args) {
		$templateMgr =& $args[0];
		$params =& $args[1];
		if (!isset($params['smarty_include_tpl_file'])) return false;
		switch ($params['smarty_include_tpl_file']) {
			case 'common/footer.tpl':
				$referralDao =& DAORegistry::getDAO('ReferralDAO');
				$user =& Request::getUser();
				$rangeInfo =& Handler::getRangeInfo('referrals');
				$referralFilter = (int) Request::getUserVar('referralFilter');
				if ($referralFilter == 0) $referralFilter = null;

				$referrals =& $referralDao->getReferralsByUserId($user->getId(), $referralFilter, $rangeInfo);
			
				$templateMgr->assign('referrals', $referrals);
				$templateMgr->assign('referralFilter', $referralFilter);
				$templateMgr->display($this->getTemplatePath() . 'authorReferrals.tpl', 'text/html', 'ReferralPlugin::addAuthorReferralContent');
				break;
		}
		return false;
	}

	function handleReaderTemplateInclude($hookName, $args) {
		$templateMgr =& $args[0];
		$params =& $args[1];
		if (!isset($params['smarty_include_tpl_file'])) return false;
		switch ($params['smarty_include_tpl_file']) {
			case 'article/comments.tpl':
				$referralDao =& DAORegistry::getDAO('ReferralDAO');
				$article = $templateMgr->get_template_vars('article');
				$referrals =& $referralDao->getPublishedReferralsForArticle($article->getArticleId());
			
				$templateMgr->assign('referrals', $referrals);
				$templateMgr->display($this->getTemplatePath() . 'readerReferrals.tpl', 'text/html', 'ReferralPlugin::addReaderReferralContent');
				break;
		}
		return false;
	}

	/**
	 * Hook callback: Handle requests.
	 */
	function handleTemplateDisplay($hookName, $args) {
		$templateMgr =& $args[0];
		$template =& $args[1];

		switch ($template) {
			case 'article/article.tpl':
				HookRegistry::register ('TemplateManager::include', array(&$this, 'handleReaderTemplateInclude'));
			case 'article/interstitial.tpl':
			case 'article/pdfInterstitial.tpl':
			case 'article/view.tpl':
				$this->logArticleRequest($templateMgr);
				break;
			case 'author/index.tpl':
				// Slightly convoluted: register a hook to
				// display the administration options at the
				// end of the normal content
				HookRegistry::register ('TemplateManager::include', array(&$this, 'handleAuthorTemplateInclude'));
				break;
		}
		return false;
	}

	function logArticleRequest(&$templateMgr) {
		$article = $templateMgr->get_template_vars('article');
		if (!$article) return false;
		$articleId = $article->getArticleId();

		$referrer = isset($_SERVER['HTTP_REFERER'])?$_SERVER['HTTP_REFERER']:null;

		// Check if referrer is empty or is the local journal
		if (empty($referrer) || strpos($referrer, Request::getIndexUrl()) !== false) return false;

		$referralDao =& DAORegistry::getDAO('ReferralDAO');
		if ($referralDao->referralExistsByUrl($articleId, $referrer)) {
			// It exists -- increment the count
			$referralDao->incrementReferralCount($article->getArticleId(), $referrer);
		} else {
			// It's a new referral -- log it.
			$referral = new Referral();
			$referral->setArticleId($article->getArticleId());
			$referral->setLinkCount(1);
			$referral->setUrl($referrer);
			$referral->setStatus(REFERRAL_STATUS_NEW);
			$referral->setDateAdded(Core::getCurrentDate());
			$referralDao->insertReferral($referral);
		}
	}

	/**
	 * Get the name of the settings file to be installed on new journal
	 * creation.
	 * @return string
	 */
	function getNewJournalPluginSettingsFile() {
		return $this->getPluginPath() . '/settings.xml';
	}

	/**
	 * Get the symbolic name of this plugin
	 * @return string
	 */
	function getName() {
		return 'ReferralPlugin';
	}

	/**
	 * Get the display name of this plugin
	 * @return string
	 */
	function getDisplayName() {
		return Locale::translate('plugins.generic.referral.name');
	}

	/**
	 * Get the description of this plugin
	 * @return string
	 */
	function getDescription() {
		return Locale::translate('plugins.generic.referral.description');
	}

	/**
	 * Get the filename of the ADODB schema for this plugin.
	 */
	function getInstallSchemaFile() {
		return $this->getPluginPath() . '/' . 'schema.xml';
	}

	/**
	 * Check whether or not this plugin is enabled
	 * @return boolean
	 */
	function getEnabled() {
		$journal =& Request::getJournal();
		$journalId = $journal?$journal->getJournalId():0;
		return $this->getSetting($journalId, 'enabled');
	}

	/**
	 * Get a list of available management verbs for this plugin
	 * @return array
	 */
	function getManagementVerbs() {
		$verbs = array();
		$verbs[] = array(
			($this->getEnabled()?'disable':'enable'),
			Locale::translate($this->getEnabled()?'manager.plugins.disable':'manager.plugins.enable')
		);
		return $verbs;
	}

 	/*
 	 * Execute a management verb on this plugin
 	 * @param $verb string
 	 * @param $args array
	 * @param $message string Location for the plugin to put a result msg
 	 * @return boolean
 	 */
	function manage($verb, $args, &$message) {
		$journal =& Request::getJournal();
		$journalId = $journal?$journal->getJournalId():0;
		switch ($verb) {
			case 'enable':
				$this->updateSetting($journalId, 'enabled', true);
				$message = Locale::translate('plugins.generic.referral.enabled');
				break;
			case 'disable':
				$this->updateSetting($journalId, 'enabled', false);
				$message = Locale::translate('plugins.generic.referral.disabled');
				break;
		}
		
		return false;
	}
}

?>
