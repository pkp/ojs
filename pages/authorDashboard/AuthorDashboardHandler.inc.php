<?php

/**
 * @file pages/authorDashboard/AuthorDashboardHandler.inc.php
 *
 * Copyright (c) 2014-2019 Simon Fraser University
 * Copyright (c) 2003-2019 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class AuthorDashboardHandler
 * @ingroup pages_authorDashboard
 *
 * @brief Handle requests for the author dashboard.
 */

// Import base class
import('lib.pkp.pages.authorDashboard.PKPAuthorDashboardHandler');

class AuthorDashboardHandler extends PKPAuthorDashboardHandler {

	/**
	 * Setup variables for the template
	 * @param $request Request
	 */
	function setupTemplate($request) {
		parent::setupTemplate($request);
		$templateMgr = TemplateManager::getManager($request);		

		$submission = $this->getAuthorizedContextObject(ASSOC_TYPE_SUBMISSION);

		$submissionContext = $request->getContext();
		if ($submission->getContextId() !== $submissionContext->getId()) {
			$submissionContext = Services::get('context')->get($submission->getContextId());
		}

		$supportedFormLocales = $submissionContext->getSupportedFormLocales();
		$localeNames = AppLocale::getAllLocales();
		$locales = array_map(function($localeKey) use ($localeNames) {
			return ['key' => $localeKey, 'label' => $localeNames[$localeKey]];
		}, $supportedFormLocales);		

		$latestPublication = $submission->getLatestPublication();
		$latestPublicationApiUrl = $request->getDispatcher()->url($request, ROUTE_API, $submissionContext->getPath(), 'submissions/' . $submission->getId() . '/publications/' . $latestPublication->getId());

		$titleAbstractForm = new PKP\components\forms\publication\PKPTitleAbstractForm($latestPublicationApiUrl, $locales, $latestPublication);

		$templateMgr->setConstants([
			'FORM_TITLE_ABSTRACT',
		]);

		$workflowData = $templateMgr->getTemplateVars('workflowData');
		$workflowData['components'][FORM_TITLE_ABSTRACT] = $titleAbstractForm->getConfig();
		$templateMgr->assign('workflowData', $workflowData);
	}

	/**
	 * @copydoc PKPAuthorDashboardHandler::_getRepresentationsGridUrl()
	 */
	protected function _getRepresentationsGridUrl($request, $submission) {
		return $request->getDispatcher()->url(
			$request,
			ROUTE_COMPONENT,
			null,
			'grid.articleGalleys.ArticleGalleyGridHandler',
			'fetchGrid',
			null,
			[
				'submissionId' => $submission->getId(),
				'publicationId' => '__publicationId__',
			]
		);
	}
}


