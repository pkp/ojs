<?php

/**
 * @file plugins/generic/referral/ReferralPlugin.inc.php
 *
 * Copyright (c) 2014 Simon Fraser University Library
 * Copyright (c) 2003-2014 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class ReferralPlugin
 * @ingroup plugins_generic_referral
 *
 * @brief Referral plugin to track and maintain potential references to published articles
 */

import('lib.pkp.classes.plugins.GenericPlugin');

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
			if ($this->getEnabled()) {
				HookRegistry::register ('TemplateManager::display', array($this, 'handleTemplateDisplay'));
				HookRegistry::register ('LoadHandler', array($this, 'handleLoadHandler'));
				$this->import('Referral');
				$this->import('ReferralDAO');
				$referralDao = new ReferralDAO();
				DAORegistry::registerDAO('ReferralDAO', $referralDao);
			}
			return true;
		}
		return false;
	}

	/**
	 * Display verbs for the management interface.
	 */
	function getManagementVerbs() {
		$verbs = parent::getManagementVerbs();
		if ($this->getEnabled()) {
			$verbs[] = array('settings', __('plugins.generic.googleAnalytics.manager.settings'));
		}
		return $verbs;
	}

 	/**
	 * @see Plugin::manage()
	 */
	function manage($verb, $args, &$message, &$messageParams, &$pluginModalContent = null) {
		if (!parent::manage($verb, $args, $message, $messageParams)) return false;
		$request =& $this->getRequest();

		switch ($verb) {
			case 'settings':
				$templateMgr = TemplateManager::getManager();
				$templateMgr->register_function('plugin_url', array($this, 'smartyPluginUrl'));
				$journal = $request->getJournal();

				$this->import('ReferralPluginSettingsForm');
				$form = new ReferralPluginSettingsForm($this, $journal->getId());
				if ($request->getUserVar('save')) {
					$form->readInputData();
					if ($form->validate()) {
						$form->execute();
						$request->redirect(null, 'manager', 'plugin');
						return false;
					} else {
						$form->display();
					}
				} else {
					$form->initData();
					$form->display();
				}
				return true;
			default:
				// Unknown management verb
				assert(false);
		}
	}

	/**
	 * Intercept the load handler hook to present the user-facing
	 * referrals list if necessary.
	 */
	function handleLoadHandler($hookName, $args) {
		$page =& $args[0];
		$op =& $args[1];
		$sourceFile =& $args[2];

		if ($page === 'referral' && in_array($op, array('editReferral', 'updateReferral', 'deleteReferral', 'bulkAction'))) {
			$this->import('ReferralHandler');
			Registry::set('plugin', $this);
			define('HANDLER_CLASS', 'ReferralHandler');
			return true;
		}

		return false;
	}

	/**
	 * Intercept the author index page to add referral content
	 */
	function handleAuthorTemplateInclude($hookName, $args) {
		$templateMgr =& $args[0];
		$params =& $args[1];
		$request =& $this->getRequest();

		if (!isset($params['smarty_include_tpl_file'])) return false;
		switch ($params['smarty_include_tpl_file']) {
			case 'common/footer.tpl':
				$referralDao = DAORegistry::getDAO('ReferralDAO');
				$user = $request->getUser();
				$rangeInfo =& Handler::getRangeInfo($request, 'referrals');
				$referralFilter = (int) $request->getUserVar('referralFilter');
				if ($referralFilter == 0) $referralFilter = null;

				// Fetch article titles
				$journal = $request->getJournal();
				$referrals = $referralDao->getByUserId($user->getId(), $journal->getId(), $referralFilter, $rangeInfo);
				$articleDao = DAORegistry::getDAO('ArticleDAO');
				$articleTitles = $referralsArray = array();
				while ($referral = $referrals->next()) {
					$article = $articleDao->getById($referral->getArticleId());
					if (!$article) continue;
					$articleTitles[$article->getId()] = $article->getLocalizedTitle();
					$referralsArray[] = $referral;
				}
				// Turn the array back into an interator for display
				import('lib.pkp.classes.core.VirtualArrayIterator');
				$referrals = new VirtualArrayIterator($referralsArray, $referrals->getCount(), $referrals->getPage(), $rangeInfo->getCount());

				$templateMgr->assign('articleTitles', $articleTitles);
				$templateMgr->assign('referrals', $referrals);
				$templateMgr->assign('referralFilter', $referralFilter);
				$templateMgr->display($this->getTemplatePath() . 'authorReferrals.tpl', 'text/html', 'ReferralPlugin::addAuthorReferralContent');
				break;
		}
		return false;
	}

	/**
	 * Intercept the article comments template to add referral content
	 */
	function handleReaderTemplateInclude($hookName, $args) {
		$templateMgr =& $args[0];
		$params =& $args[1];
		if (!isset($params['smarty_include_tpl_file'])) return false;
		switch ($params['smarty_include_tpl_file']) {
			case 'article/comments.tpl':
				$referralDao = DAORegistry::getDAO('ReferralDAO');
				$article = $templateMgr->get_template_vars('article');
				$referrals =& $referralDao->getPublishedReferralsForArticle($article->getId());

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

		assert(false); // Template names no longer apply (e.g. interstitial)

		switch ($template) {
			case 'article/article.tpl':
				HookRegistry::register ('TemplateManager::include', array($this, 'handleReaderTemplateInclude'));
			case 'article/interstitial.tpl':
			case 'article/pdfInterstitial.tpl':
				$this->logArticleRequest($templateMgr);
				break;
			case 'author/index.tpl':
				// Slightly convoluted: register a hook to
				// display the administration options at the
				// end of the normal content
				HookRegistry::register ('TemplateManager::include', array($this, 'handleAuthorTemplateInclude'));
				break;
		}
		return false;
	}

	/**
	 * Intercept requests for article display to collect and record
	 * incoming referrals.
	 */
	function logArticleRequest(&$templateMgr) {
		$article = $templateMgr->get_template_vars('article');
		if (!$article) return false;
		$articleId = $article->getId();

		$referrer = isset($_SERVER['HTTP_REFERER'])?$_SERVER['HTTP_REFERER']:null;

		// Check if referrer is empty or is the local journal
		$request =& $this->getRequest();
		if (empty($referrer) || strpos($referrer, $request->getIndexUrl()) !== false) return false;

		$referralDao = DAORegistry::getDAO('ReferralDAO');
		if ($referralDao->referralExistsByUrl($articleId, $referrer)) {
			// It exists -- increment the count
			$referralDao->incrementReferralCount($article->getId(), $referrer);
		} else {
			// It's a new referral. Log it unless it's excluded.
			$journal = $templateMgr->get_template_vars('currentJournal');
			$exclusions = $this->getSetting($journal->getId(), 'exclusions');
			foreach (array_map('trim', explode("\n", "$exclusions")) as $exclusion) {
				if (empty($exclusion)) continue;
				if (preg_match($exclusion, $referrer)) return false;
			}
			$referral = new Referral();
			$referral->setArticleId($article->getId());
			$referral->setLinkCount(1);
			$referral->setUrl($referrer);
			$referral->setStatus(REFERRAL_STATUS_NEW);
			$referral->setDateAdded(Core::getCurrentDate());
			$referralDao->replaceReferral($referral);
		}
		return false;
	}

	/**
	 * Get the name of the settings file to be installed on new journal
	 * creation.
	 * @return string
	 */
	function getContextSpecificPluginSettingsFile() {
		return $this->getPluginPath() . '/settings.xml';
	}

	/**
	 * Get the display name of this plugin
	 * @return string
	 */
	function getDisplayName() {
		return __('plugins.generic.referral.name');
	}

	/**
	 * Get the description of this plugin
	 * @return string
	 */
	function getDescription() {
		return __('plugins.generic.referral.description');
	}

	/**
	 * Get the filename of the ADODB schema for this plugin.
	 */
	function getInstallSchemaFile() {
		return $this->getPluginPath() . '/' . 'schema.xml';
	}
}

?>
