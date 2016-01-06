<?php

/**
 * @file plugins/generic/usageEvent/UsageEventPlugin.inc.php
 *
 * Copyright (c) 2014-2015 Simon Fraser University Library
 * Copyright (c) 2003-2015 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class UsageEventPlugin
 * @ingroup plugins_generic_usageEvent
 *
 * @brief Implement application specifics for generating usage events.
 */

import('lib.pkp.plugins.generic.usageEvent.PKPUsageEventPlugin');

class UsageEventPlugin extends PKPUsageEventPlugin {


	//
	// Implement methods from PKPUsageEventPlugin.
	//
	/**
	 * @copydoc PKPUsageEventPlugin::getEventHooks()
	 */
	function getEventHooks() {
		return array_merge(parent::getEventHooks(), array(
			'ArticleHandler::download',
			'IssueHandler::download'
		));
	}

	/**
	 * @copydoc PKPUsageEventPlugin::getUSageEventData()
	 */
	protected function getUsageEventData($hookName, $hookArgs, $request, $router, $templateMgr, $context) {
		list($pubObject, $downloadSuccess, $assocType, $idParams, $canonicalUrlPage, $canonicalUrlOp, $canonicalUrlParams) =
			parent::getUsageEventData($hookName, $hookArgs, $request, $router, $templateMgr, $context);

		if (!$pubObject) {
			switch ($hookName) {
				// Press index page, issue content page and article abstract.
				case 'TemplateManager::display':
					$page = $router->getRequestedPage($request);
					$op = $router->getRequestedOp($request);

					$wantedPages = array('issue', 'article');
					$wantedOps = array('index', 'view');

					if (!in_array($page, $wantedPages) || !in_array($op, $wantedOps)) break;

					$journal = $templateMgr->get_template_vars('currentContext');
					$issue = $templateMgr->get_template_vars('issue');
					$publishedArticle = $templateMgr->get_template_vars('publishedArticle');

					// No published objects, no usage event.
					if (!$journal && !$issue && !$publishedArticle) break;

					if ($journal) {
						$pubObject = $journal;
						$assocType = ASSOC_TYPE_JOURNAL;
						$canonicalUrlOp = '';
					}

					if ($issue) {
						$pubObject = $issue;
						$assocType = ASSOC_TYPE_ISSUE;
						$canonicalUrlParams = array($issue->getId());
						$idParams = array('s' . $issue->getId());
					}

					if ($publishedArticle) {
						$pubObject = $publishedArticle;
						$assocType = ASSOC_TYPE_ARTICLE;
						$canonicalUrlParams = array($pubObject->getId());
						$idParams = array('m' . $pubObject->getId());
					}

					$downloadSuccess = true;
					$canonicalUrlOp = $op;
					break;

					// Issue galley.
				case 'IssueHandler::download':
					$assocType = ASSOC_TYPE_ISSUE_GALLEY;
					$galley = $hookArgs[2];
					$issue = $hookArgs[1];
					$canonicalUrlOp = 'download';
					$canonicalUrlParams = array($issue->getId(), $galley->getId());
					$idParams = array('i' . $issue->getId(), 'f' . $galley->getId());
					$downloadSuccess = false;
					$pubObject = $galley;
					break;

					// Article file.
				case 'ArticleHandler::download':
					$assocType = ASSOC_TYPE_SUBMISSION_FILE;
					$file = $hookArgs[2];
					$article = $hookArgs[1];
					$canonicalUrlOp = 'download';
					$canonicalUrlParams = array($article->getId(), $file->getAssocId(), $file->getFileId());
					$idParams = array('a' . $article->getId(), 'f' . $file->getId());
					$downloadSuccess = false;
					$pubObject = $file;
					break;
				default:
					// Why are we called from an unknown hook?
					assert(false);
			}
		}

	}

	/**
	 * @see PKPUsageEventPlugin::getHtmlPageAssocTypes()
	 */
	protected function getHtmlPageAssocTypes() {
		return array(
			ASSOC_TYPE_JOURNAL,
			ASSOC_TYPE_ISSUE,
			ASSOC_TYPE_ARTICLE
		);
	}

	/**
	 * @see PKPUsageEventPlugin::isPubIdObjectType()
	 */
	protected function isPubIdObjectType($pubObject) {
		return is_a($pubObject, 'PublishedArticle');
	}

}

?>
