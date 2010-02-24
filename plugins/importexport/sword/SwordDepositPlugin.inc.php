<?php

/**
 * @file SwordPlugin.inc.php
 *
 * Copyright (c) 2003-2010 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class SwordPlugin
 * @ingroup plugins_importexport_sword
 *
 * @brief Sword deposit plugin
 */

// $Id: SwordImportExportPlugin.inc.php,v 1.60 2010/01/27 21:35:04 asmecher Exp $


import('classes.plugins.ImportExportPlugin');

class SwordDepositPlugin extends ImportExportPlugin {
	/**
	 * Called as a plugin is registered to the registry
	 * @param $category String Name of category plugin was registered to
	 * @return boolean True iff plugin initialized successfully; if false,
	 * 	the plugin will not be registered.
	 */
	function register($category, $path) {
		$success = parent::register($category, $path);
		$this->addLocaleData();
		return $success;
	}

	/**
	 * Get the name of this plugin. The name must be unique within
	 * its category.
	 * @return String name of plugin
	 */
	function getName() {
		return 'SwordDepositPlugin';
	}

	function getDisplayName() {
		return Locale::translate('plugins.importexport.sword.displayName');
	}

	function getDescription() {
		return Locale::translate('plugins.importexport.sword.description');
	}

	function deposit($url, $username, $password, $articleId) {
		$publishedArticleDao =& DAORegistry::getDAO('PublishedArticleDAO');
		$publishedArticle =& $publishedArticleDao->getPublishedArticleByArticleId($articleId);
		$journal =& Request::getJournal();

		import('sword.OJSSwordDeposit');
		$deposit = new OJSSwordDeposit($publishedArticle);
		$deposit->setMetadata();
		$deposit->addGalleys();
		$deposit->createPackage();
		$response = $deposit->deposit($url, $username, $password);
		$deposit->cleanup();
		return $response->sac_id;
	}

	function display(&$args) {
		$templateMgr =& TemplateManager::getManager();
		parent::display($args);
		$this->setBreadcrumbs();

		switch (array_shift($args)) {
			case 'deposit':
				$depositUrl = Request::getUserVar('swordUrl');
				$username = Request::getUserVar('swordUsername');
				$password = Request::getUserVar('swordPassword');
				$depositIds = array();
				try {
					foreach (Request::getUserVar('articleId') as $articleId) {
						$depositIds[] = $this->deposit($depositUrl, $username, $password, $articleId);
					}
				} catch (Exception $e) {
					$templateMgr->assign(array(
						'pageTitle' => 'plugins.importexport.sword.depositFailed',
						'messageTranslated' => $e->getMessage(),
						'backLink' => Request::url(null, null, null, array('plugin', $this->getName()), array('swordUrl' => $depositUrl, 'swordUsername' => $swordUsername)),
						'backLinkLabel' => 'common.back'
					));
					return $templateMgr->display('common/message.tpl');
				}
				$templateMgr->assign(array(
					'pageTitle' => 'plugins.importexport.sword.depositSuccessful',
					'message' => 'plugins.importexport.sword.depositSuccessfulDescription',
					'backLink' => Request::url(null, null, null, array('plugin', $this->getName()), array('swordUrl' => $depositUrl, 'swordUsername' => $swordUsername)),
					'backLinkLabel' => 'common.continue'
				));
				$templateMgr->display('common/message.tpl');
				break;
			default:
				$journal =& Request::getJournal();
				$publishedArticleDao =& DAORegistry::getDAO('PublishedArticleDAO');
				$rangeInfo = Handler::getRangeInfo('articles');
				$articleIds = $publishedArticleDao->getPublishedArticleIdsAlphabetizedByJournal($journal->getId(), false);
				$totalArticles = count($articleIds);
				if ($rangeInfo->isValid()) $articleIds = array_slice($articleIds, $rangeInfo->getCount() * ($rangeInfo->getPage()-1), $rangeInfo->getCount());
				import('core.VirtualArrayIterator');
				$iterator = new VirtualArrayIterator(ArticleSearch::formatResults($articleIds), $totalArticles, $rangeInfo->getPage(), $rangeInfo->getCount());
				foreach (array('swordUrl', 'swordUsername', 'swordPassword') as $var) {
					$templateMgr->assign($var, Request::getUserVar($var));
				}
				$templateMgr->assign_by_ref('articles', $iterator);
				$templateMgr->display($this->getTemplatePath() . 'index.tpl');
				break;
		}
	}

	/**
	 * Execute import/export tasks using the command-line interface.
	 * @param $args Parameters to the plugin
	 */ 
	function executeCLI($scriptName, &$args) {
		die('executeCLI unimplemented');
	}
}

?>
