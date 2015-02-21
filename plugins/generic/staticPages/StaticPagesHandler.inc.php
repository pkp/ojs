<?php

/**
 * @file plugins/generic/staticPages/StaticPagesHandler.inc.php
 *
 * Copyright (c) 2013-2015 Simon Fraser University Library
 * Copyright (c) 2003-2015 John Willinsky
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
			AppLocale::requireComponents(LOCALE_COMPONENT_PKP_COMMON, LOCALE_COMPONENT_APPLICATION_COMMON, LOCALE_COMPONENT_PKP_USER);
			$journal =& Request::getJournal();
			$journalId = $journal?$journal->getId():0;
			$path = $args[0];

			$staticPagesPlugin =& PluginRegistry::getPlugin('generic', STATIC_PAGES_PLUGIN_NAME);
			$templateMgr =& TemplateManager::getManager();

			$staticPagesDao =& DAORegistry::getDAO('StaticPagesDAO');
			$staticPage = $staticPagesDao->getStaticPageByPath($journalId, $path);

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
