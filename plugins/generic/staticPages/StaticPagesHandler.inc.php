<?php

/**
 * @file plugins/generic/staticPages/StaticPagesHandler.inc.php
 *
 * Copyright (c) 2014 Simon Fraser University Library
 * Copyright (c) 2003-2014 John Willinsky
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
	function index($args, $request) {
		$request->redirect(null, null, 'view', $request->getRequestedOp());
	}

	function view($args, $request) {
		if (count($args) > 0 ) {
			AppLocale::requireComponents(LOCALE_COMPONENT_PKP_COMMON, LOCALE_COMPONENT_APP_COMMON, LOCALE_COMPONENT_PKP_USER);
			$journal = $request->getJournal();
			$journalId = $journal?$journal->getId():0;
			$path = $args[0];

			$staticPagesPlugin = PluginRegistry::getPlugin('generic', STATIC_PAGES_PLUGIN_NAME);
			$templateMgr = TemplateManager::getManager($request);

			$staticPagesDao = DAORegistry::getDAO('StaticPagesDAO');
			$staticPage = $staticPagesDao->getStaticPageByPath($journalId, $path);

			if ( !$staticPage ) {
				$request->redirect(null, 'index');
			}

			// and assign the template vars needed
			$templateMgr->assign('title', $staticPage->getStaticPageTitle());
			$templateMgr->assign('content',  $staticPage->getStaticPageContent());
			$templateMgr->display($staticPagesPlugin->getTemplatePath().'content.tpl');
		}
	}
}

?>
