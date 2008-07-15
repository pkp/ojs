<?php

/**
 * @file HelpHandler.inc.php
 *
 * Copyright (c) 2003-2008 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class HelpHandler
 * @ingroup pages_help
 *
 * @brief Handle requests for viewing help pages. 
 */

// $Id$


define('HELP_DEFAULT_TOPIC', 'index/topic/000000');
define('HELP_DEFAULT_TOC', 'index/toc/000000');

import('help.HelpToc');
import('help.HelpTocDAO');
import('help.HelpTopic');
import('help.HelpTopicDAO');
import('help.HelpTopicSection');
import('core.Handler');

class HelpHandler extends Handler {

	/**
	 * Display help table of contents.
	 */
	function index() {
		HelpHandler::view(array('index', 'topic', '000000'));
	}

	function toc() {
		parent::validate();
		HelpHandler::setupTemplate();

		$templateMgr = &TemplateManager::getManager();
		$help =& Help::getHelp();
		$templateMgr->assign_by_ref('helpToc', $help->getTableOfContents());
		$templateMgr->display('help/helpToc.tpl');
	}

	/**
	 * Display the selected help topic.
	 * @param $args array first parameter is the ID of the topic to display
	 */
	function view($args) {
		parent::validate();
		HelpHandler::setupTemplate();

		$topicId = implode("/",$args);
		$keyword = trim(String::regexp_replace('/[^\w\s\.\-]/', '', strip_tags(Request::getUserVar('keyword'))));
		$result = (int) Request::getUserVar('result');

		$topicDao = &DAORegistry::getDAO('HelpTopicDAO');
		$topic = $topicDao->getTopic($topicId);

		if ($topic === false) {
			// Invalid topic, use default instead
			$topicId = HELP_DEFAULT_TOPIC;
			$topic = $topicDao->getTopic($topicId);
		}

		$tocDao = &DAORegistry::getDAO('HelpTocDAO');
		$toc = $tocDao->getToc($topic->getTocId());

		if ($toc === false) {
			// Invalid toc, use default instead
			$toc = $tocDao->getToc(HELP_DEFAULT_TOC);
		}

		if ($topic->getSubTocId() != null) {
			$subToc = $tocDao->getToc($topic->getSubTocId());
		} else {
			$subToc =  null;
		}

		$relatedTopics = $topic->getRelatedTopics();

		$topics = $toc->getTopics();

		$templateMgr = &TemplateManager::getManager();
		$templateMgr->assign('currentTopicId', $topic->getId());
		$templateMgr->assign_by_ref('topic', $topic);
		$templateMgr->assign('toc', $toc);
		$templateMgr->assign('subToc', $subToc);
		$templateMgr->assign('relatedTopics', $relatedTopics);
		$templateMgr->assign('locale', Locale::getLocale());
		$templateMgr->assign('breadcrumbs', $toc->getBreadcrumbs());
		if (!empty($keyword)) {
			$templateMgr->assign('helpSearchKeyword', $keyword);
		}
		if (!empty($result)) {
			$templateMgr->assign('helpSearchResult', $result);
		}
		$templateMgr->display('help/view.tpl');
	}

	/**
	 * Display search results for a topic search by keyword.
	 */
	function search() {
		parent::validate();
		HelpHandler::setupTemplate();

		$searchResults = array();

		$keyword = trim(String::regexp_replace('/[^\w\s\.\-]/', '', strip_tags(Request::getUserVar('keyword'))));

		if (!empty($keyword)) {
			$topicDao = &DAORegistry::getDAO('HelpTopicDAO');
			$topics = $topicDao->getTopicsByKeyword($keyword);

			$tocDao = &DAORegistry::getDAO('HelpTocDAO');
			foreach ($topics as $topic) {
				$searchResults[] = array('topic' => $topic, 'toc' => $tocDao->getToc($topic->getTocId()));		
			}
		}

		$templateMgr = &TemplateManager::getManager();
		$templateMgr->assign('showSearch', true);
		$templateMgr->assign('pageTitle', Locale::translate('help.searchResults'));
		$templateMgr->assign('helpSearchKeyword', $keyword);
		$templateMgr->assign('searchResults', $searchResults);
		$templateMgr->display('help/searchResults.tpl');
	}

	/**
	 * Initialize the template
	 */
	function setupTemplate() {
		$templateMgr =& TemplateManager::getManager();
		$templateMgr->setCacheability(CACHEABILITY_PUBLIC);
	}
}

?>
