 <?php

/**
 * @file plugins/generic/browse/pages/BrowseHandler.inc.php
 *
 * Copyright (c) 2013-2015 Simon Fraser University Library
 * Copyright (c) 2003-2015 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class BrowseHandler
 * @ingroup plugins_generic_browse
 *
 * @brief Handle requests for additional browse functions.
 */

import('classes.handler.Handler');
import('classes.core.VirtualArrayIterator');

class BrowseHandler extends Handler {

	/**
	 * Show list of journal sections.
	 */
	function sections($args = array(), &$request) {
		$this->setupTemplate($request, true);

		$router =& $request->getRouter();
		$journal =& $router->getContext($request);

		$browsePlugin =& PluginRegistry::getPlugin('generic', BROWSE_PLUGIN_NAME);
		$enableBrowseBySections = $browsePlugin->getSetting($journal->getId(), 'enableBrowseBySections');
		if ($enableBrowseBySections) {
			if (isset($args[0]) && $args[0] == 'view') {
				$sectionId = Request::getUserVar('sectionId');
				$sectionDao = &DAORegistry::getDAO('SectionDAO');
				$section =& $sectionDao->getSection($sectionId);
				$publishedArticleDao = &DAORegistry::getDAO('PublishedArticleDAO');
				$publishedArticleIds = $publishedArticleDao->getPublishedArticleIdsBySection($sectionId);

				$rangeInfo = Handler::getRangeInfo('search');
				$totalResults = count($publishedArticleIds);
				$publishedArticleIds = array_slice($publishedArticleIds, $rangeInfo->getCount() * ($rangeInfo->getPage()-1), $rangeInfo->getCount());
				$results = new VirtualArrayIterator(ArticleSearch::formatResults($publishedArticleIds), $totalResults, $rangeInfo->getPage(), $rangeInfo->getCount());

				$templateMgr = &TemplateManager::getManager();
				$templateMgr->assign_by_ref('results', $results);
				$templateMgr->assign('title', $section->getLocalizedTitle());
				$templateMgr->assign('sectionId', $sectionId);
				$templateMgr->assign('enableBrowseBySections', $enableBrowseBySections);
				$templateMgr->display($browsePlugin->getTemplatePath() . 'searchDetails.tpl');
			} else {
				$excludedSections = $browsePlugin->getSetting($journal->getId(), 'excludedSections');
				$sectionDao = &DAORegistry::getDAO('SectionDAO');
				$sectionsIterator =& $sectionDao->getJournalSections($journal->getId());
				$sections = array();
				while (($section =& $sectionsIterator->next())) {
					if (!in_array($section->getId(), $excludedSections)) {
						$sections[$section->getLocalizedTitle()] = $section->getId();
					}
				}
				ksort($sections);

				$rangeInfo = Handler::getRangeInfo('search');
				$totalResults = count($sections);
				$sections = array_slice($sections, $rangeInfo->getCount() * ($rangeInfo->getPage()-1), $rangeInfo->getCount());
				$results = new VirtualArrayIterator($sections, $totalResults, $rangeInfo->getPage(), $rangeInfo->getCount());

				$templateMgr =& TemplateManager::getManager();
				$templateMgr->assign_by_ref('results', $results);
				$templateMgr->assign('enableBrowseBySections', $enableBrowseBySections);
				$templateMgr->display($browsePlugin->getTemplatePath() . 'searchIndex.tpl');
			}
		} else {
			$request->redirect(null, 'index');
		}
	}

	/**
	 * Show list of journal sections identify types.
	 */
	function identifyTypes($args = array(), &$request) {
		$this->setupTemplate($request, true);

		$router =& $request->getRouter();
		$journal =& $router->getContext($request);

		$browsePlugin =& PluginRegistry::getPlugin('generic', BROWSE_PLUGIN_NAME);
		$enableBrowseByIdentifyTypes = $browsePlugin->getSetting($journal->getId(), 'enableBrowseByIdentifyTypes');
		if ($enableBrowseByIdentifyTypes) {
			if (isset($args[0]) && $args[0] == 'view') {
				$identifyType = Request::getUserVar('identifyType');
				$sectionDao = &DAORegistry::getDAO('SectionDAO');
				$sectionsIterator =& $sectionDao->getJournalSections($journal->getId());
				$sections = array();
				while (($section =& $sectionsIterator->next())) {
					if ($section->getLocalizedIdentifyType() == $identifyType) {
						$sections[] =& $section;
					}
				}
				$publishedArticleDao = &DAORegistry::getDAO('PublishedArticleDAO');
				$publishedArticleIds = array();
				foreach ($sections as $section) {
					$publishedArticleIdsBySection = $publishedArticleDao->getPublishedArticleIdsBySection($section->getId());
					$publishedArticleIds = array_merge($publishedArticleIds, $publishedArticleIdsBySection);
				}

				$rangeInfo = Handler::getRangeInfo('search');
				$totalResults = count($publishedArticleIds);
				$publishedArticleIds = array_slice($publishedArticleIds, $rangeInfo->getCount() * ($rangeInfo->getPage()-1), $rangeInfo->getCount());
				$results = new VirtualArrayIterator(ArticleSearch::formatResults($publishedArticleIds), $totalResults, $rangeInfo->getPage(), $rangeInfo->getCount());

				$templateMgr = &TemplateManager::getManager();
				$templateMgr->assign_by_ref('results', $results);
				$templateMgr->assign('title', $identifyType);
				$templateMgr->assign('enableBrowseByIdentifyTypes', $enableBrowseByIdentifyTypes);
				$templateMgr->display($browsePlugin->getTemplatePath() . 'searchDetails.tpl');
			} else {
				$excludedIdentifyTypes = $browsePlugin->getSetting($journal->getId(), 'excludedIdentifyTypes');
				$sectionDao = &DAORegistry::getDAO('SectionDAO');
				$sectionsIterator =& $sectionDao->getJournalSections($journal->getId());
				$sectionidentifyTypes = array();
				while (($section =& $sectionsIterator->next())) {
					if ($section->getLocalizedIdentifyType() && !in_array($section->getId(), $excludedIdentifyTypes) && !in_array($section->getLocalizedIdentifyType(), $sectionidentifyTypes)) {
						$sectionidentifyTypes[] =& $section->getLocalizedIdentifyType();
					}
				}
				sort($sectionidentifyTypes);

				$rangeInfo = Handler::getRangeInfo('search');
				$totalResults = count($sectionidentifyTypes);
				$sectionidentifyTypes = array_slice($sectionidentifyTypes, $rangeInfo->getCount() * ($rangeInfo->getPage()-1), $rangeInfo->getCount());
				$results = new VirtualArrayIterator($sectionidentifyTypes, $totalResults, $rangeInfo->getPage(), $rangeInfo->getCount());

				$templateMgr =& TemplateManager::getManager();
				$templateMgr->assign_by_ref('results', $results);
				$templateMgr->assign('enableBrowseByIdentifyTypes', $enableBrowseByIdentifyTypes);
				$templateMgr->display($browsePlugin->getTemplatePath() . 'searchIndex.tpl');
			}
		} else {
			$request->redirect(null, 'index');
		}
	}

	/**
	 * Ensure that we have a journal and the plugin is enabled.
	 */
	function authorize(&$request, &$args, $roleAssignments) {
		$router =& $request->getRouter();
		$journal =& $router->getContext($request);
		if (!isset($journal)) return false;
		$browsePlugin =& PluginRegistry::getPlugin('generic', BROWSE_PLUGIN_NAME);
		if (!isset($browsePlugin)) return false;
		if (!$browsePlugin->getEnabled()) return false;
		return parent::authorize($request, $args, $roleAssignments);
	}

	/**
	 * Setup common template variables.
	 * @param $subclass boolean set to true if caller is below this handler in the hierarchy
	 */
	function setupTemplate(&$request, $subclass = false, $op = 'index') {
		$templateMgr =& TemplateManager::getManager();
		$templateMgr->assign('helpTopicId', 'user.searchAndBrowse');

		$opMap = array(
			'index' => 'navigation.search',
			'categories' => 'navigation.categories'
		);

		$templateMgr->assign('pageHierarchy',
			$subclass ? array(array($request->url(null, 'search', $op), $opMap[$op]))
				: array()
		);

		$router =& $request->getRouter();
		$journal =& $router->getContext($request);
		if (!$journal || !$journal->getSetting('restrictSiteAccess')) {
			$templateMgr->setCacheability(CACHEABILITY_PUBLIC);
		}
	}
}

?>
