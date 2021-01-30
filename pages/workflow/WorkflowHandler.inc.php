<?php

/**
 * @file pages/workflow/WorkflowHandler.inc.php
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2003-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
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

		$supportedSubmissionLocales = $submissionContext->getSupportedSubmissionLocales();
		$localeNames = AppLocale::getAllLocales();
		$locales = array_map(function($localeKey) use ($localeNames) {
			return ['key' => $localeKey, 'label' => $localeNames[$localeKey]];
		}, $supportedSubmissionLocales);

		$latestPublication = $submission->getLatestPublication();

		$latestPublicationApiUrl = $request->getDispatcher()->url($request, ROUTE_API, $submissionContext->getPath(), 'submissions/' . $submission->getId() . '/publications/' . $latestPublication->getId());
		$temporaryFileApiUrl = $request->getDispatcher()->url($request, ROUTE_API, $submissionContext->getPath(), 'temporaryFiles');
		$issueApiUrl = $request->getDispatcher()->url($request, ROUTE_API, $submissionContext->getData('urlPath'), 'issues/__issueId__');

		import('classes.file.PublicFileManager');
		$publicFileManager = new PublicFileManager();
		$baseUrl = $request->getBaseUrl() . '/' . $publicFileManager->getContextFilesPath($submissionContext->getId());

		$issueEntryForm = new APP\components\forms\publication\IssueEntryForm($latestPublicationApiUrl, $locales, $latestPublication, $submissionContext, $baseUrl, $temporaryFileApiUrl);

		$sectionWordLimits = [];
		$sectionDao = DAORegistry::getDAO('SectionDAO'); /* @var $sectionDao SectionDAO */
		$sectionIterator = $sectionDao->getByContextId($submissionContext->getId());
		while ($section = $sectionIterator->next()) {
			$sectionWordLimits[$section->getId()] = (int) $section->getAbstractWordCount() ?? 0;
		}

		import('classes.components.forms.publication.AssignToIssueForm');
		import('classes.components.forms.publication.PublishForm');
		$templateMgr->setConstants([
			'FORM_ASSIGN_TO_ISSUE',
			'FORM_ISSUE_ENTRY',
			'FORM_PUBLISH',
		]);

		$components = $templateMgr->getState('components');
		$components[FORM_ISSUE_ENTRY] = $issueEntryForm->getConfig();

		// Add payments form if enabled
		$paymentManager = \Application::getPaymentManager($submissionContext);
		$templateMgr->assign([
			'submissionPaymentsEnabled' => $paymentManager->publicationEnabled(),
		]);
		if ($paymentManager->publicationEnabled()) {
			$submissionPaymentsForm = new APP\components\forms\publication\SubmissionPaymentsForm(
				$request->getDispatcher()->url($request, ROUTE_API, $submissionContext->getPath(), '_submissions/' . $submission->getId() . '/payment'),
				$submission,
				$request->getContext()
			);
			$components[FORM_SUBMISSION_PAYMENTS] = $submissionPaymentsForm->getConfig();
			$templateMgr->setConstants([FORM_SUBMISSION_PAYMENTS]);
		}

		// Add the word limit to the existing title/abstract form
		if (!empty($components[FORM_TITLE_ABSTRACT]) &&
				array_key_exists($submission->getLatestPublication()->getData('sectionId'), $sectionWordLimits)) {
			$limit = (int) $sectionWordLimits[$submission->getLatestPublication()->getData('sectionId')];
			foreach ($components[FORM_TITLE_ABSTRACT]['fields'] as $key => $field) {
				if ($field['name'] === 'abstract') {
					$components[FORM_TITLE_ABSTRACT]['fields'][$key]['wordLimit'] = $limit;
					break;
				}
			}
		}

		$assignToIssueUrl = $request->getDispatcher()->url(
			$request,
			ROUTE_COMPONENT,
			null,
			'modals.publish.AssignToIssueHandler',
			'assign',
			null,
			[
				'submissionId' => $submission->getId(),
				'publicationId' => '__publicationId__',
			]
		);

		$publicationFormIds = $templateMgr->getState('publicationFormIds');
		$publicationFormIds[] = FORM_ISSUE_ENTRY;

		$templateMgr->setState([
			'assignToIssueUrl' => $assignToIssueUrl,
			'components' => $components,
			'publicationFormIds' => $publicationFormIds,
			'issueApiUrl' => $issueApiUrl,
			'sectionWordLimits' => $sectionWordLimits,
			'selectIssueLabel' => __('publication.selectIssue'),
		]);
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


