<?php

/**
 * @file plugins/generic/usageEvent/UsageEventPlugin.inc.php
 *
 * Copyright (c) 2014-2019 Simon Fraser University
 * Copyright (c) 2003-2019 John Willinsky
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
			'PreprintHandler::download',
			'HtmlArticleGalleyPlugin::articleDownload',
			'HtmlArticleGalleyPlugin::articleDownloadFinished'
		));
	}

	/**
	 * @copydoc PKPUsageEventPlugin::getDownloadFinishedEventHooks()
	 */
	protected function getDownloadFinishedEventHooks() {
		return array_merge(parent::getDownloadFinishedEventHooks(), array(
			'HtmlArticleGalleyPlugin::articleDownloadFinished'
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
				// Press index page and preprint abstract.
				case 'TemplateManager::display':
					$page = $router->getRequestedPage($request);
					$op = $router->getRequestedOp($request);
					$args = $router->getRequestedArgs($request);

					$wantedPages = array('preprint');
					$wantedOps = array('index', 'view');

					if (!in_array($page, $wantedPages) || !in_array($op, $wantedOps)) break;

					// View requests with 1 argument might relate to server
					// or preprint. With more than 1 is related with other objects
					// that we are not interested in or that are counted using a
					// different hook.
					if ($op == 'view' && count($args) > 1) break;

					$journal = $templateMgr->getTemplateVars('currentContext');
					$submission = $templateMgr->getTemplateVars('preprint');

					// No published objects, no usage event.
					if (!$journal && !$submission) break;

					if ($journal) {
						$pubObject = $journal;
						$assocType = ASSOC_TYPE_JOURNAL;
						$canonicalUrlOp = '';
					}

					if ($submission) {
						$pubObject = $submission;
						$assocType = ASSOC_TYPE_SUBMISSION;
						$canonicalUrlParams = array($pubObject->getId());
						$idParams = array('m' . $pubObject->getId());
					}

					$downloadSuccess = true;
					$canonicalUrlOp = $op;
					break;

					// Preprint file.
				case 'PreprintHandler::download':
				case 'HtmlArticleGalleyPlugin::articleDownload':
					$assocType = ASSOC_TYPE_SUBMISSION_FILE;
					$preprint = $hookArgs[0];
					$galley = $hookArgs[1];
					$fileId = $hookArgs[2];
					// if file is not a gallay file (e.g. CSS or images), there is no usage event.
					if ($galley->getFileId() != $fileId) return false;
					$canonicalUrlOp = 'download';
					$canonicalUrlParams = array($preprint->getId(), $galley->getId(), $fileId);
					$idParams = array('a' . $preprint->getId(), 'g' . $galley->getId(), 'f' . $fileId);
					$downloadSuccess = false;
					$submissionFileDao = DAORegistry::getDAO('SubmissionFileDAO');
					$pubObject = $submissionFileDao->getLatestRevision($fileId);
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

