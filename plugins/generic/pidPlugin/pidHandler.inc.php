<?php

/**
 * @file pidHandler.inc.php
 *
 * Copyright (c) 2003-2008 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class pidHandler
 * @ingroup plugins_generic_counter
 *
 * @brief enables PID functionality.
 */
import('handler.Handler');

class pidHandler extends Handler {

	function requestHsPid($pidAssignorPath, $pidResolverPath, $articleId){
		
		$journalDao = &DAORegistry::getDAO('JournalDAO');
		$articleDao = &DAORegistry::getDAO('ArticleDAO');
		$pidResourceDao = &DAORegistry::getDAO('pidResourceDAO');
		
		$journalId = $articleDao->getArticleJournalId($articleId);
		$journal = $journalDao->getJournal($journalId);

		$articlePid = hsClientCreate(Request::url($journal->getPath(), 'article', 'view', $articleId), $pidAssignorPath);
		$pidResolverPath = $pidResolverPath.'/'.$articlePid;		
				
		$pidResourceDao->setResourcePid($articlePid, $articleId, ASSOC_TYPE_PID_ARTICLE);
		$pidResourceDao->setResourcePurl($articlePid, $pidResolverPath);
		
		return $articlePid;
	}
	
	function getResourcePid($assoc_id, $assoc_type){
		$pidResourceDao = &DAORegistry::getDAO('pidResourceDAO');
		return $pidResourceDao->getResourcePid($assoc_id, $assoc_type);
	}
	
	function getResourcePurl($resourcePid){
		$pidResourceDao = &DAORegistry::getDAO('pidResourceDAO');
		return $pidResourceDao->getResourcePurl($resourcePid);
	}

	function setPidsRetroactively($journalId, $pidAssignorPath, $pidResolverPath){
		$pidResourceDao = &DAORegistry::getDAO('pidResourceDAO');
		$articleIdList = $pidResourceDao->getJournalArticleIDsWithoutPid($journalId, ASSOC_TYPE_PID_ARTICLE);

		foreach($articleIdList as $articleId){
			pidHandler::requestHsPid($pidAssignorPath, $pidResolverPath, $articleId);
		}
		return true;
	}
}
?>