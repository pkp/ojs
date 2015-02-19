<?php

/**
 * @file plugins/generic/sword/SwordImportExportPlugin.inc.php
 *
 * Copyright (c) 2013-2015 Simon Fraser University Library
 * Copyright (c) 2003-2015 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class SwordPlugin
 * @ingroup plugins_importexport_sword
 *
 * @brief Sword deposit plugin
 */



import('classes.plugins.ImportExportPlugin');

class SwordImportExportPlugin extends ImportExportPlugin {
	/** @var $parentPluginName string Name of parent plugin */
	var $parentPluginName;

	/**
	 * Constructor
	 */
	function SwordImportExportPlugin($parentPluginName) {
		parent::ImportExportPlugin();
		$this->parentPluginName = $parentPluginName;
	}

	/**
	 * Called as a plugin is registered to the registry
	 * @param $category String Name of category plugin was registered to
	 * @return boolean True iff plugin initialized successfully; if false,
	 * 	the plugin will not be registered.
	 */
	function register($category, $path) {
		import('classes.sword.OJSSwordDeposit');
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
		return 'SwordImportExportPlugin';
	}

	function getDisplayName() {
		return __('plugins.importexport.sword.displayName');
	}

	function getDescription() {
		return __('plugins.importexport.sword.description');
	}

	/**
	 * Get the sword plugin
	 * @return object
	 */
	function &getSwordPlugin() {
		$plugin =& PluginRegistry::getPlugin('generic', $this->parentPluginName);
		return $plugin;
	}

	/**
	 * Override the builtin to get the correct plugin path.
	 * @return string
	 */
	function getPluginPath() {
		$plugin =& $this->getSwordPlugin();
		return $plugin->getPluginPath();
	}

	function deposit($url, $username, $password, $articleId, $depositEditorial, $depositGalleys) {
		$publishedArticleDao =& DAORegistry::getDAO('PublishedArticleDAO');
		$publishedArticle =& $publishedArticleDao->getPublishedArticleByArticleId($articleId);
		$journal =& Request::getJournal();

		$deposit = new OJSSwordDeposit($publishedArticle);
		$deposit->setMetadata();
		if ($depositGalleys) $deposit->addGalleys();
		if ($depositEditorial) $deposit->addEditorial();
		$deposit->createPackage();
		$response = $deposit->deposit($url, $username, $password);
		$deposit->cleanup();
		return $response->sac_id;
	}

	function display(&$args, $request) {
		$templateMgr =& TemplateManager::getManager();
		parent::display($args, $request);
		$this->setBreadcrumbs();
		$journal =& Request::getJournal();
		$plugin =& $this->getSwordPlugin();

		$swordUrl = Request::getUserVar('swordUrl');

		$depositPointKey = Request::getUserVar('depositPoint');
		$depositPoints = $plugin->getSetting($journal->getId(), 'depositPoints');
		$username = Request::getUserVar('swordUsername');
		$password = Request::getUserVar('swordPassword');

		if (isset($depositPoints[$depositPointKey])) {
			$selectedDepositPoint = $depositPoints[$depositPointKey];
			if ($selectedDepositPoint['username'] != '') $username = $selectedDepositPoint['username'];
			if ($selectedDepositPoint['password'] != '') $password = $selectedDepositPoint['password'];
		}

		$swordDepositPoint = Request::getUserVar('swordDepositPoint');
		$depositEditorial = Request::getUserVar('depositEditorial');
		$depositGalleys = Request::getUserVar('depositGalleys');

		switch (array_shift($args)) {
			case 'deposit':
				$depositIds = array();
				try {
					foreach (Request::getUserVar('articleId') as $articleId) {
						$depositIds[] = $this->deposit(
							$swordDepositPoint,
							$username,
							$password,
							$articleId,
							$depositEditorial,
							$depositGalleys
						);
					}
				} catch (Exception $e) {
					// Deposit failed
					$templateMgr->assign(array(
						'pageTitle' => 'plugins.importexport.sword.depositFailed',
						'messageTranslated' => $e->getMessage(),
						'backLink' => Request::url(
							null, null, null,
							array('plugin', $this->getName()),
							array(
								'swordUrl' => $swordUrl,
								'swordUsername' => $username,
								'swordDepositPoint' => $swordDepositPoint,
								'depositEditorial' => $depositEditorial,
								'depositGalleys' => $depositGalleys,
							)
						),
						'backLinkLabel' => 'common.back'
					));
					return $templateMgr->display('common/message.tpl');
				}
				// Deposit was successful
				$templateMgr->assign(array(
					'pageTitle' => 'plugins.importexport.sword.depositSuccessful',
					'message' => 'plugins.importexport.sword.depositSuccessfulDescription',
					'backLink' => Request::url(
						null, null, null,
						array('plugin', $this->getName()),
						array(
							'swordUrl' => $swordUrl,
							'swordUsername' => $username,
							'swordDepositPoint' => $swordDepositPoint,
							'depositEditorial' => $depositEditorial,
							'depositGalleys' => $depositGalleys
						)
					),
					'backLinkLabel' => 'common.continue'
				));
				return $templateMgr->display('common/message.tpl');
				break;
			default:
				$journal =& Request::getJournal();
				$publishedArticleDao =& DAORegistry::getDAO('PublishedArticleDAO');
				$rangeInfo = Handler::getRangeInfo('articles');
				$articleIds = $publishedArticleDao->getPublishedArticleIdsAlphabetizedByJournal($journal->getId(), false);
				$totalArticles = count($articleIds);
				if ($rangeInfo->isValid()) $articleIds = array_slice($articleIds, $rangeInfo->getCount() * ($rangeInfo->getPage()-1), $rangeInfo->getCount());
				import('lib.pkp.classes.core.VirtualArrayIterator');
				$iterator = new VirtualArrayIterator(ArticleSearch::formatResults($articleIds), $totalArticles, $rangeInfo->getPage(), $rangeInfo->getCount());
				foreach (array('swordUrl', 'swordUsername', 'swordPassword', 'depositEditorial', 'depositGalleys', 'swordDepositPoint') as $var) {
					$templateMgr->assign($var, Request::getUserVar($var));
				}
				$templateMgr->assign('depositPoints', $depositPoints);
				if (!empty($swordUrl)) {
					$client = new SWORDAPPClient();
					$doc = $client->servicedocument($swordUrl, $username, $password, '');
					$depositPoints = array();
					if (is_array($doc->sac_workspaces)) foreach ($doc->sac_workspaces as $workspace) {
						if (is_array($workspace->sac_collections)) foreach ($workspace->sac_collections as $collection) {
							$depositPoints["$collection->sac_href"] = "$collection->sac_colltitle";
						}
					}
					$templateMgr->assign_by_ref('swordDepositPoints', $depositPoints);
				}
				$templateMgr->assign_by_ref('articles', $iterator);
				$templateMgr->display($this->getTemplatePath() . 'articles.tpl');
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
