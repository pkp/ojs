<?php

/**
 * @file classes/plugins/ViewableFilePlugin.inc.php
 *
 * Copyright (c) 2014-2015 Simon Fraser University Library
 * Copyright (c) 2003-2015 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class ViewableFilePlugin
 * @ingroup plugins
 *
 * @brief Abstract class for article galley plugins
 */

import('lib.pkp.classes.plugins.PKPViewableFilePlugin');

abstract class ViewableFilePlugin extends PKPViewableFilePlugin {
	/**
	 * Constructor
	 */
	function ViewableFilePlugin() {
		parent::PKPViewableFilePlugin();
	}

	/**
	 * @copydoc Plugin::register()
	 */
	function register($category, $path) {
		if (!parent::register($category, $path)) return false;
		if ($this->getEnabled()) {
			HookRegistry::register('ArticleHandler::view::galley', array($this, 'articleCallback'));
			HookRegistry::register('IssueHandler::view::galley', array($this, 'issueCallback'));
		}
		return true;
	}

	/**
	 * Determine whether this plugin can handle the specified content.
	 * @param $galley ArticleGalley|IssueGalley
	 * @return boolean True iff the plugin can handle the content
	 */
	function canHandle($galley) {
		return false;
	}

	/**
	 * Display an article galley.
	 * @param $request PKPRequest
	 * @param $issue Issue
	 * @param $article Article
	 * @param $galley ArticleGalley
	 * @return string
	 */
	function displayArticleGalley($request, $issue, $article, $galley) {
		$templateMgr = TemplateManager::getManager($request);
		$templateMgr->assign(array(
			'issue' => $issue,
			'article' => $article,
			'galley' => $galley,
		));
		$templateMgr->display($this->getTemplatePath() . '/articleGalley.tpl');
	}

	/**
	 * Display an issue galley.
	 * @param $request PKPRequest
	 * @param $issue Issue
	 * @param $galley IssueGalley
	 * @return string
	 */
	function displayIssueGalley($request, $issue, $galley) {
		$templateMgr = TemplateManager::getManager($request);
		$templateMgr->assign(array(
			'issue' => $issue,
			'galley' => $galley,
		));
		$templateMgr->display($this->getTemplatePath() . '/issueGalley.tpl');
	}

	/**
	 * Callback that renders the article galley.
	 * @param $hookName string
	 * @param $args array
	 * @return boolean
	 */
	function articleCallback($hookName, $args) {
		$request =& $args[0];
		$issue =& $args[1];
		$galley =& $args[2];
		$article =& $args[3];

		$templateMgr = TemplateManager::getManager($request);
		if ($this->canHandle($galley)) {
			$this->displayArticleGalley($request, $issue, $article, $galley);
			return true;
		}

		return false;
	}

	/**
	 * Callback that renders the issue galley.
	 * @param $hookName string
	 * @param $args array
	 * @return boolean
	 */
	function issueCallback($hookName, $args) {
		$request = $args[0];
		$issue = $args[1];
		$galley = $args[2];

		$templateMgr = TemplateManager::getManager($request);
		$fileId = $galley->getFileId();
		if ($this->canHandle($galley)) {
			$this->displayIssueGalley($request, $issue, $galley);
			return true;
		}

		return false;
	}
}

?>
