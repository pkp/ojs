<?php

/**
 * HelpHandler.inc.php
 *
 * Copyright (c) 2003-2004 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package pages.help
 *
 * Handle requests for viewing help pages. 
 *
 * $Id$
 */

define('HELP_DEFAULT_TOPIC', 'index/topic/000000');
define('HELP_DEFAULT_TOC', 'index/toc/000000');

import('help.HelpToc');
import('help.HelpTocDAO');
import('help.HelpTopic');
import('help.HelpTopicDAO');
import('help.HelpTopicSection');

class HelpHandler extends Handler {

	/**
	 * Display help table of contents.
	 */
	function index() {
		parent::validate();
		HelpHandler::view(HELP_DEFAULT_TOPIC);
	}
	
	/**
	 * Display the selected help topic.
	 * @param $args array first parameter is the ID of the topic to display
	 */
	function view($args) {
		parent::validate();
		
		$topicId = implode("/",$args);
		
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
						
		$templateMgr = &TemplateManager::getManager();
		$templateMgr->assign('currentTopicId', $topic->getId());
		$templateMgr->assign('topic', $topic);
		$templateMgr->assign('toc', $toc);
		$templateMgr->display('help/view.tpl');
	}
	
	/**
	 * Display search results for a topic search by keyword.
	 */
	function search() {
		parent::validate();
		$topicDao = &DAORegistry::getDAO('HelpTopicDAO');
		
		$keyword = trim(String::regexp_replace('/[^\w\s\.\-]/', '', strip_tags(Request::getUserVar('keyword'))));
		
		if (empty($keyword)) {
			$topics = array();
		} else {
			$topics = $topicDao->getTopicsByKeyword($keyword);
		}
						
		$templateMgr = &TemplateManager::getManager();
		$templateMgr->assign('helpSearchKeyword', $keyword);
		$templateMgr->assign('topics', $topics);
		$templateMgr->display('help/searchResults.tpl');
	}
	
}

?>
