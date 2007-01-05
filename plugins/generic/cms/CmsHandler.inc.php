<?php

/**
 * cmsimpleHandler.inc.php
 *
 * Copyright (c) 2003-2006 John Willinsky
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


class CmsHandler extends Handler {
	function index( $args ) {
	}	

	function view( $args ) {
		if ( count($args) > 0 ) {
		 	$journal = &Request::getJournal();
			$journalId = $journal->getJournalId();
			$cmsPlugin = &PluginRegistry::getPlugin('generic', 'CmsPlugin');

			$allContent = $cmsPlugin->getSetting($journalId, 'content');
		
			$cmsPlugin->import('ContentManager');	
			$contentManager =& new ContentManager();
	
			$content = array();
			$headings = array();
			$current = $args[0];
	
			// get the content
			$contentManager->parseContents( &$headings, &$content, $current );

			// silly way to do this, but we have to find all the proper titles 
			// and they are not stored anywhere conveniently
			$title = $current;
			$cur_array = explode(":", $current);
			if ( count($cur_array) > 1 ) 
				$cur_array[1] = $cur_array[0].':'.$cur_array[1];
			if ( count($cur_array) > 2 ) 
				$cur_array[2] = $cur_array[0].':'.$cur_array[1].':'.$cur_array[2];
			array_pop($cur_array);
			

			$breadcrumbs = array();
			foreach ( $headings as $heading ) {
				if ( count($cur_array) > 0 && $cur_array[0] == $heading[1] )
					$breadcrumbs[] = array(
										'./'.$heading[1], 
										$heading[2], 
										$heading[2] );
					
				elseif ( count($cur_array) > 1 && $cur_array[1] == $heading[1] )
					$breadcrumbs[] = array(
										'./'.$heading[1], 
										$heading[2], 
										$heading[2] );
					
				elseif ( count($cur_array) > 2 && $cur_array[2] == $heading[1] )
					$breadcrumbs[] = array(
										'./'.$heading[1], 
										$heading[2], 
										$heading[2] );
							
				if ( $heading[1] == $current ) { 
					$title = $heading[2];
					break;
				}
			}
			
			
			// and assign the template vars needed
			$templateMgr = &TemplateManager::getManager();
			$templateMgr->assign('title', $title);
			$templateMgr->assign('content', $content[$args[0]] );		
			$templateMgr->assign('headings', $headings);
			$templateMgr->assign('cmsPluginToc', $headings);
			$templateMgr->assign('pageHierarchy', $breadcrumbs);			
			$templateMgr->display($cmsPlugin->getTemplatePath().'content.tpl');
		}
	}
}

?>
