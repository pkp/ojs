<?php

/**
 * @file FeedHandler.inc.php
 *
 * Copyright (c) 2003-2007 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package plugins.generic.webFeed
 * @class FeedHandler
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
		FeedHandler::__displayFeed("/templates/atom.tpl", 'application/atom+xml');
    }

    /**
    * Display current issue page as RSS 2.0
    */
    function rss2() {        
		FeedHandler::__displayFeed("/templates/rss2.tpl", 'application/rss+xml');
    }
    
    /**
    * Display current issue page as RSS 1.0 (RDF/XML).
    */
    function rss() {        
		FeedHandler::__displayFeed("/templates/rss.tpl", 'application/rdf+xml');
    }
    
    /**
     * Display given feed/template
     */
     function __displayFeed($template, $mimeType) {
        $journal = &Request::getJournal();
        $issueDao = &DAORegistry::getDAO('IssueDAO');
        $issue = &$issueDao->getCurrentIssue($journal->getJournalId());

		// only display the feed if the journal has published an issue (doesn't make sense otherwise)
        if ($issue != null) {
           	//Get the associated Web Feed Plugin
			$webFeedPlugin = &PluginRegistry::getPlugin('generic', 'WebFeedPlugin');
            $publishedArticleDao = &DAORegistry::getDAO('PublishedArticleDAO');

			// Get limit setting from web feeds plugin
			$displayItems = $webFeedPlugin->getSetting($journal->getJournalId(), 'displayItems');
			$recentItems = (int) $webFeedPlugin->getSetting($journal->getJournalId(), 'recentItems');

			if ($displayItems == 'recent' && $recentItems > 0) {
				import('db.DBResultRange');
				$rangeInfo =& new DBResultRange($recentItems, 1);
				$publishedArticleObjects = &$publishedArticleDao->getPublishedArticlesByJournalId($journal->getJournalId(), $rangeInfo);

				while ($publishedArticle =& $publishedArticleObjects->next()) {
					$publishedArticles[]['articles'][] = &$publishedArticle;
				}
			} else {
	            $publishedArticles = &$publishedArticleDao->getPublishedArticlesInSections($issue->getIssueId());
			}

	        $templateMgr = &TemplateManager::getManager();

            $templateMgr->assign_by_ref('publishedArticles', $publishedArticles);
            $templateMgr->assign_by_ref('journal', $journal);
            $templateMgr->assign_by_ref('issue', $issue);
            $templateMgr->assign('showToc', true);

            $templateMgr->display($webFeedPlugin->getTemplatePath() . $template, $mimeType);
        }
	}
    
}

?>
