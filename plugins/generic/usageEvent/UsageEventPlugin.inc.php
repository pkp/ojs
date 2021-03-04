<?php

/**
 * @file plugins/generic/usageEvent/UsageEventPlugin.inc.php
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2003-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
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
			'IssueHandler::download',
			'HtmlArticleGalleyPlugin::articleDownload',
			'HtmlArticleGalleyPlugin::articleDownloadFinished',
			'LensGalleyPlugin::articleDownloadFinished'
		));
	}

	/**
	 * @copydoc PKPUsageEventPlugin::getDownloadFinishedEventHooks()
	 */
	protected function getDownloadFinishedEventHooks() {
		return array_merge(parent::getDownloadFinishedEventHooks(), array(
			'HtmlArticleGalleyPlugin::articleDownloadFinished',
			'LensGalleyPlugin::articleDownloadFinished'
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
					$args = $router->getRequestedArgs($request);

					$wantedPages = array('issue', 'article');
					$wantedOps = array('index', 'view');

					if (!in_array($page, $wantedPages) || !in_array($op, $wantedOps)) break;

					// View requests with 1 argument might relate to journal
					// or article. With more than 1 is related either with a
					// version of the submissin abstract page or
					// with other objects that we are not interested in or
					// that are counted using a different hook.
					// If the operation is 'view' and the arguments count > 1
					// the arguments must be: $submissionId/version/$publicationId.
					if ($op == 'view' && count($args) > 1) {
						if ($args[1] !== 'version') break;
						else if (count($args) != 3) break;
						$publicationId = (int) $args[2];
					}

					$journal = $templateMgr->getTemplateVars('currentContext');
					$issue = $templateMgr->getTemplateVars('issue');
					$submission = $templateMgr->getTemplateVars('article');

					// No published objects, no usage event.
					if (!$journal && !$issue && !$submission) break;

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

					if ($submission) {
						$pubObject = $submission;
						$assocType = ASSOC_TYPE_SUBMISSION;
						$canonicalUrlParams = array($pubObject->getId());
						$idParams = array('m' . $pubObject->getId());
						if (isset($publicationId)) {
							// no need to check if the publication exists (for the submisison),
							// 404 would be returned and the usage event would not be there
							$canonicalUrlParams = array($pubObject->getId(), 'version', $publicationId);
						}
					}

					$downloadSuccess = true;
					$canonicalUrlOp = $op;
					break;

					// Issue galley.
				case 'IssueHandler::download':
					$assocType = ASSOC_TYPE_ISSUE_GALLEY;
					$issue = $hookArgs[0];
					$galley = $hookArgs[1];
					$canonicalUrlOp = 'download';
					$canonicalUrlParams = array($issue->getId(), $galley->getId());
					$idParams = array('i' . $issue->getId(), 'f' . $galley->getId());
					$downloadSuccess = false;
					$pubObject = $galley;
					break;

					// Article file.
				case 'ArticleHandler::download':
				case 'HtmlArticleGalleyPlugin::articleDownload':
				case 'LensGalleyPlugin::articleDownloadFinished':
					$assocType = ASSOC_TYPE_SUBMISSION_FILE;
					$article = $hookArgs[0];
					$galley = $hookArgs[1];
					$submissionFileId = $hookArgs[2];
					// if file is not a gallay file (e.g. CSS or images), there is no usage event.
					if ($galley->getData('submissionFileId') != $submissionFileId) return false;
					$canonicalUrlOp = 'download';
					$canonicalUrlParams = array($article->getId(), $galley->getId(), $submissionFileId);
					$idParams = array('a' . $article->getId(), 'g' . $galley->getId(), 'f' . $submissionFileId);
					$downloadSuccess = false;
					$pubObject = Services::get('submissionFile')->get($submissionFileId);
					break;
				default:
					// Why are we called from an unknown hook?
					assert(false);
			}
		}

		return array($pubObject, $downloadSuccess, $assocType, $idParams, $canonicalUrlPage, $canonicalUrlOp, $canonicalUrlParams);
	}

	/**
	 * @see PKPUsageEventPlugin::getHtmlPageAssocTypes()
	 */
	protected function getHtmlPageAssocTypes() {
		return array(
			ASSOC_TYPE_JOURNAL,
			ASSOC_TYPE_ISSUE,
			ASSOC_TYPE_SUBMISSION,
		);
	}

	/**
	 * @see PKPUsageEventPlugin::isPubIdObjectType()
	 */
	protected function isPubIdObjectType($pubObject) {
		return is_a($pubObject, 'Submission');
	}

}


