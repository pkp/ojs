<?php

/**
 * @file StaticPagesHandler.inc.php
 *
 * Copyright (c) 2003-2011 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package plugins.generic.staticPages
 * @class StaticPagesHandler
 *
 * Find the content and display the appropriate page
 *
 */

import('classes.handler.Handler');

class StaticPagesHandler extends Handler {
	function index( $args ) {
		Request::redirect(null, null, 'view', Request::getRequestedOp());
	}

	function view ($args) {
		if (count($args) > 0 ) {
			Locale::requireComponents(array(LOCALE_COMPONENT_PKP_COMMON, LOCALE_COMPONENT_APPLICATION_COMMON, LOCALE_COMPONENT_PKP_USER));
			$journal =& Request::getJournal();
			$journalId = $journal->getId();
			$path = $args[0];

			$staticPagesPlugin =& PluginRegistry::getPlugin('generic', STATIC_PAGES_PLUGIN_NAME);
			$templateMgr =& TemplateManager::getManager();

			$staticPagesDAO =& DAORegistry::getDAO('StaticPagesDAO');
			$staticPage = $staticPagesDAO->getStaticPageByPath($journalId, $path);

			if ( !$staticPage ) {
				Request::redirect(null, 'index');
			}

			// and assign the template vars needed
			$templateMgr->assign('title', $staticPage->getStaticPageTitle());
			$templateMgr->assign('content',  $staticPage->getStaticPageContent());
			$templateMgr->display($staticPagesPlugin->getTemplatePath().'content.tpl');
		}
	}
}

?>
