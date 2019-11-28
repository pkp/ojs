<?php

/**
 * @file pages/workflow/WorkflowHandler.inc.php
 *
 * Copyright (c) 2014-2019 Simon Fraser University
 * Copyright (c) 2003-2019 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class WorkflowHandler
 * @ingroup pages_reviewer
 *
 * @brief Handle requests for the submssion workflow.
 */

import('lib.pkp.pages.workflow.PKPWorkflowHandler');

// Access decision actions constants.
import('classes.workflow.EditorDecisionActionsManager');

class WorkflowHandler extends PKPWorkflowHandler {
	/**
	 * Constructor
	 */
	function __construct() {
		parent::__construct();

		$this->addRoleAssignment(
			array(ROLE_ID_SUB_EDITOR, ROLE_ID_MANAGER, ROLE_ID_ASSISTANT),
			array(
				'access', 'index', 'submission',
				'editorDecisionActions', // Submission & review
				'externalReview', // review
				'editorial',
				'production',
				'submissionHeader',
				'submissionProgressBar',
			)
		);
	}

	/**
	 * Setup variables for the template
	 * @param $request Request
	 */
	function setupIndex($request) {
		parent::setupIndex($request);

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
		$temporaryFileApiUrl = $request->getDispatcher()->url($request, ROUTE_API, $submissionContext->getPath(), 'temporaryFiles');
		$issueApiUrl = $request->getDispatcher()->url($request, ROUTE_API, $submissionContext->getData('urlPath'), 'issues/__issueId__');

		import('classes.file.PublicFileManager');
		$publicFileManager = new PublicFileManager();
		$baseUrl = $request->getBaseUrl() . '/' . $publicFileManager->getContextFilesPath($submissionContext->getId());

		$journalEntryForm = new APP\components\forms\publication\JournalEntryForm($latestPublicationApiUrl, $locales, $latestPublication, $submissionContext, $baseUrl, $temporaryFileApiUrl);

		$sectionWordLimits = [];
		$sectionIterator = DAORegistry::getDAO('SectionDAO')->getByContextId($submissionContext->getId());
		while ($section = $sectionIterator->next()) {
			$sectionWordLimits[$section->getId()] = (int) $section->getAbstractWordCount() ?? 0;
		}

		import('classes.components.forms.publication.JournalEntryForm'); // Constant import
		$templateMgr->setConstants([
			'FORM_JOURNAL_ENTRY',
		]);

		$workflowData = $templateMgr->getTemplateVars('workflowData');

		// Add the word limit to the existing title/abstract form
		if (!empty($workflowData['components'][FORM_TITLE_ABSTRACT]) &&
				array_key_exists($submission->getLatestPublication()->getData('sectionId'), $sectionWordLimits)) {
			$limit = (int) $sectionWordLimits[$submission->getLatestPublication()->getData('sectionId')];
			foreach ($workflowData['components'][FORM_TITLE_ABSTRACT]['fields'] as $key => $field) {
				if ($field['name'] === 'abstract') {
					$workflowData['components'][FORM_TITLE_ABSTRACT]['fields'][$key]['wordLimit'] = $limit;
					break;
				}
			}
		}

		$workflowData['components'][FORM_JOURNAL_ENTRY] = $journalEntryForm->getConfig();
		$workflowData['publicationFormIds'][] = FORM_JOURNAL_ENTRY;
		$workflowData['issueApiUrl'] = $issueApiUrl;
		$workflowData['sectionWordLimits'] = $sectionWordLimits;
		$workflowData['i18n']['schedulePublication'] = __('editor.submission.schedulePublication');

		$templateMgr->assign('workflowData', $workflowData);
	}


	//
	// Protected helper methods
	//
	/**
	 * Return the editor assignment notification type based on stage id.
	 * @param $stageId int
	 * @return int
	 */
	protected function getEditorAssignmentNotificationTypeByStageId($stageId) {
		switch ($stageId) {
			case WORKFLOW_STAGE_ID_SUBMISSION:
				return NOTIFICATION_TYPE_EDITOR_ASSIGNMENT_SUBMISSION;
			case WORKFLOW_STAGE_ID_EXTERNAL_REVIEW:
				return NOTIFICATION_TYPE_EDITOR_ASSIGNMENT_EXTERNAL_REVIEW;
			case WORKFLOW_STAGE_ID_EDITING:
				return NOTIFICATION_TYPE_EDITOR_ASSIGNMENT_EDITING;
			case WORKFLOW_STAGE_ID_PRODUCTION:
				return NOTIFICATION_TYPE_EDITOR_ASSIGNMENT_PRODUCTION;
		}
		return null;
	}

	/**
	 * @copydoc PKPWorkflowHandler::_getRepresentationsGridUrl()
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


