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

}

?>
