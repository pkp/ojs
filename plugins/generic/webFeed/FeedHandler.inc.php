<?php

/**
 * FeedHandler.inc.php
 *
 * Copyright (c) 2003-2006 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package plugins
 *
 * Handle requests for Atom/RSS feeds when a feed URL is requested
 *
 * $Id$
 */
 
import('core.Handler');
import('classes.plugins.PluginRegistry');

class FeedHandler extends Handler {
	
   function index() {
	   FeedHandler::atom();	 
   }
   
    /**
    * Display current issue page as Atom.
    */
    function atom() {              
        $journal = &Request::getJournal();
        
        $issueDao = &DAORegistry::getDAO('IssueDAO');
        $issue = &$issueDao->getCurrentIssue($journal->getJournalId());
        
        $templateMgr = &TemplateManager::getManager();

		$versionDao =& DAORegistry::getDAO('VersionDAO');
		$version =& $versionDao->getCurrentVersion();
        $templateMgr->assign('ojsVersion', $version->getVersionString());

        $publisher = $journal->getSetting('publisher');
        $institution = $publisher['institution'];
        
        if ($issue != null) {
            $publishedArticleDao = &DAORegistry::getDAO('PublishedArticleDAO');
            $publishedArticles = &$publishedArticleDao->getPublishedArticlesInSections($issue->getIssueId());
            $templateMgr->assign_by_ref('publishedArticles', $publishedArticles);
            $templateMgr->assign_by_ref('journal', $journal);
            $templateMgr->assign_by_ref('issue', $issue);
            $templateMgr->assign('publisher', $institution);
            $templateMgr->assign('showToc', true);
            
            //Get the associated Plugin so we can then grab its TemplatePath
			$webFeedPlugin = &PluginRegistry::getPlugin('generic', 'FeedPlugin');
			
            $templateMgr->display($webFeedPlugin->getTemplatePath() . "/templates/atom.tpl", 'text/xml');
        }
        
    }

    /**
    * Display current issue page as RSS 2.0
    */    
    function rss2() {        
        $journal = &Request::getJournal();

        $issueDao = &DAORegistry::getDAO('IssueDAO');
        $issue = &$issueDao->getCurrentIssue($journal->getJournalId());
        
        $templateMgr = &TemplateManager::getManager();

		$versionDao =& DAORegistry::getDAO('VersionDAO');
		$version =& $versionDao->getCurrentVersion();
        $templateMgr->assign('ojsVersion', $version->getVersionString());

        if ($issue != null) {
            $publishedArticleDao = &DAORegistry::getDAO('PublishedArticleDAO');
            $publishedArticles = &$publishedArticleDao->getPublishedArticlesInSections($issue->getIssueId());
            $templateMgr->assign_by_ref('publishedArticles', $publishedArticles);
            $templateMgr->assign_by_ref('journal', $journal);
            $templateMgr->assign_by_ref('issue', $issue);
            $templateMgr->assign('showToc', true);
            
           	//Get the associated Plugin so we can then grab its TemplatePath
			$webFeedPlugin = &PluginRegistry::getPlugin('generic', 'FeedPlugin');
            $templateMgr->display($webFeedPlugin->getTemplatePath() . "/templates/rss2.tpl", 'text/xml');
        }
    }
    
    /**
    * Display current issue page as RSS 1.0 (RDF/XML).
    */    
    function rss() {        
        $journal = &Request::getJournal();

        $issueDao = &DAORegistry::getDAO('IssueDAO');
        $issue = &$issueDao->getCurrentIssue($journal->getJournalId());
        
        $templateMgr = &TemplateManager::getManager();
        
        if ($issue != null) {
            $publishedArticleDao = &DAORegistry::getDAO('PublishedArticleDAO');
            $publishedArticles = &$publishedArticleDao->getPublishedArticlesInSections($issue->getIssueId());
            $templateMgr->assign_by_ref('publishedArticles', $publishedArticles);
            $templateMgr->assign_by_ref('journal', $journal);
            $templateMgr->assign_by_ref('issue', $issue);
            $templateMgr->assign('showToc', true);
            
           	//Get the associated Plugin so we can then grab its TemplatePath
			$webFeedPlugin = &PluginRegistry::getPlugin('generic', 'FeedPlugin');
            $templateMgr->display($webFeedPlugin->getTemplatePath() . "/templates/rss.tpl", 'text/xml');
        }
    }
    
}

?>
