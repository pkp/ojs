<?php

/**
 * @file pidPlugin.inc.php
 *
 * Copyright (c) 2003-2008 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class pidPlugin
 * @ingroup plugins_generic_pid
 *
 * @brief enables PID functionality.
 */

import('classes.plugins.GenericPlugin');

define('ASSOC_TYPE_PID_JOURNAL', 1);
define('ASSOC_TYPE_PID_ARTICLE', 2);

require_once('hsClientQueries.inc.php');

class pidPlugin extends GenericPlugin {

	var $journal;

	/**
	 * Called as a plugin is registered to the registry
	 * @param $category String Name of category plugin was registered to
	 * @return boolean True if plugin initialized successfully; if false,
	 * 	the plugin will not be registered.
	 */
	function register($category, $path) {
			
		$success = parent::register($category, $path);
		$this->addLocaleData();

		$this->import('pidResourceDAO');
		$pidResourceDao = new pidResourceDao();
		DAORegistry::registerDAO('pidResourceDAO', $pidResourceDao);

		$this->journal =& Request::getJournal();
		$isEnabled = $this->getEnabled();

		if ($success && $isEnabled === true) {
			HookRegistry::register('Template::Author::Submission::Status', array(&$this, 'submissionStatus'));
			HookRegistry::register('Template::sectionEditor::Submission::Status', array(&$this, 'submissionStatus'));

			HookRegistry::register('PublishedArticleDAO::_insertPublishedArticle', array(&$this, 'publishedArticlePidHandler'));

			$this->import('pidHandler');
		}

		return $success;
	}

	/**
	 * Get the name of this plugin. The name must be unique within
	 * its category.
	 * @return String name of plugin
	 */
	function getName() {
		return 'pidPlugin';
	}

	function getDisplayName() {
		$this->addLocaleData();
		return TemplateManager::smartyTranslate(array('key'=>'plugins.generic.pid'), $smarty);
	}

	function getDescription() {
		$this->addLocaleData();
		return TemplateManager::smartyTranslate(array('key'=>'plugins.generic.pid.description'), $smarty);
	}

	function publishedArticlePidHandler($hookName, $args){
		$articleId = $args[1][0];
		$localPid = pidHandler::getResourcePid($articleId, ASSOC_TYPE_PID_ARTICLE);
		if(empty($localPid)){
			$pidAssignorPath = $this->getSetting($this->journal->getJournalId(), 'pidAssignorPath');
			$pidResolverPath = $this->getSetting($this->journal->getJournalId(), 'pidResolverPath');
			$this->articlePid = pidHandler::requestHsPid($pidAssignorPath, $pidResolverPath, $articleId);
		}
		else{
			$this->articlePid = $localPid;
		}
		return false;
	}
	
	function isSitePlugin() {
		return false;
	}

	function submissionStatus($hookname, $args){

		$args = Request::getRequestedArgs();
		
		if(isset($args[0])){
			$templateMgr = &TemplateManager::getManager();

			$articleId = $args[0];
			$articlePid = pidHandler::getResourcePid($articleId, ASSOC_TYPE_PID_ARTICLE);

			if($articlePid){
				$articlePurl = pidHandler::getResourcePurl($articlePid);

				$templateMgr->assign('articlePid', $articlePid);
				$templateMgr->assign('articlePurl', $articlePurl);
			}
			else{
				$templateMgr->assign('articlePid', '-');
				$templateMgr->assign('articlePurl', '-');
			}
			$templateMgr->display($this->getTemplatePath() . 'pidSubmissionStatus.tpl');
		}

		return false;
	}

	function getManagementVerbs() {

		$this->addLocaleData();
		$isEnabled = $this->getSetting($this->journal->getJournalId(), 'enabled');

		$verbs[] = array(
		($isEnabled?'disable':'enable'),
		Locale::translate($isEnabled?'manager.plugins.disable':'manager.plugins.enable')
		);

		if ($isEnabled) $verbs[] = array(
		'settings',
		Locale::translate('plugins.generic.pid.settings')
		);

		return $verbs;
	}

	function manage($verb, $args) {

		$templateMgr =& TemplateManager::getManager();
		$templateMgr->register_function('plugin_url', array(&$this, 'smartyPluginUrl'));

		$journalId = $this->journal->getJournalId();
		$isEnabled = $this->getSetting($journalId, 'enabled');
		
		$this->addLocaleData();
		$returner = true;

		switch ($verb) {
			case 'enable':
				$this->updateSetting($journalId, 'enabled', true);
				$returner = false;
				break;
			case 'disable':
				$this->updateSetting($journalId, 'enabled', false);
				$returner = false;
				break;
			case 'settings':

				if ($isEnabled) {
					$this->import('pidSettingsForm');
					$form = new pidSettingsForm($this, $journalId);

					if (Request::getUserVar('save')) {
						$form->readInputData();
						if ($form->validate()) {
							$form->execute();
							Request::redirect(null, 'manager');
						}
					}
					if (Request::getUserVar('setPidsRetroactively')) {
						$form->readInputData();
						if ($form->validate()) {
							$pidAssignorPath = $this->getSetting($journalId, 'pidAssignorPath');
							$pidResolverPath = $this->getSetting($journalId, 'pidResolverPath');
							pidHandler::setPidsRetroactively($journalId, $pidAssignorPath, $pidResolverPath);
							Request::redirect(null, 'manager');
						}
					}
					$form->initData();
					$this->setBreadCrumbs(true);
					$form->display();

				} else {
					Request::redirect(null, 'manager');
				}
				break;
		}
		return $returner;
	}


	/**
	 * Determine whether or not this plugin is enabled.
	 */
	function getEnabled() {
		$journal = &Request::getJournal();
		if (!$journal) return false;
		return $this->getSetting($journal->getJournalId(), 'enabled');
	}

	/**
	 * Set the page's breadcrumbs, given the plugin's tree of items
	 * to append.
	 * @param $subclass boolean
	 */
	function setBreadcrumbs($isSubclass = false) {
		$templateMgr = &TemplateManager::getManager();
		$pageCrumbs = array(
		array(
		Request::url(null, 'user'),
		'navigation.user'
			),
		array(
		Request::url(null, 'manager'),
		'user.role.manager'
			)
		);
		if ($isSubclass) $pageCrumbs[] = array(
		Request::url(null, 'manager', 'plugins'),
		'manager.plugins'
		);

		$templateMgr->assign('pageHierarchy', $pageCrumbs);
	}
}
?>