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

use APP\core\Application;
use APP\core\Services;
use APP\decision\types\Accept;
use APP\decision\types\SkipExternalReview;
use APP\file\PublicFileManager;
use APP\submission\Submission;
use APP\template\TemplateManager;
use PKP\db\DAORegistry;
use PKP\decision\types\BackToCopyediting;
use PKP\decision\types\BackToReview;
use PKP\decision\types\BackToSubmissionFromCopyediting;
use PKP\decision\types\Decline;
use PKP\decision\types\InitialDecline;
use PKP\decision\types\RecommendAccept;
use PKP\decision\types\RecommendDecline;
use PKP\decision\types\RecommendRevisions;
use PKP\decision\types\RequestRevisions;
use PKP\decision\types\RevertDecline;
use PKP\decision\types\RevertInitialDecline;
use PKP\decision\types\SendExternalReview;
use PKP\decision\types\SendToProduction;
use PKP\notification\PKPNotification;
use PKP\plugins\HookRegistry;
use PKP\security\Role;

class WorkflowHandler extends PKPWorkflowHandler
{
    /**
     * Constructor
     */
    public function __construct()
    {
        parent::__construct();

        $this->addRoleAssignment(
            [Role::ROLE_ID_SUB_EDITOR, Role::ROLE_ID_MANAGER, Role::ROLE_ID_SITE_ADMIN, Role::ROLE_ID_ASSISTANT],
            [
                'access', 'index', 'submission',
                'editorDecisionActions', // Submission & review
                'externalReview', // review
                'editorial',
                'production',
                'submissionHeader',
                'submissionProgressBar',
            ]
        );
    }

    /**
     * Setup variables for the template
     *
     * @param Request $request
     */
    public function setupIndex($request)
    {
        parent::setupIndex($request);

        $templateMgr = TemplateManager::getManager($request);
        $submission = $this->getAuthorizedContextObject(Application::ASSOC_TYPE_SUBMISSION);

        $submissionContext = $request->getContext();
        if ($submission->getContextId() !== $submissionContext->getId()) {
            $submissionContext = Services::get('context')->get($submission->getContextId());
        }

        $locales = $submissionContext->getSupportedSubmissionLocaleNames();
        $locales = array_map(fn (string $locale, string $name) => ['key' => $locale, 'label' => $name], array_keys($locales), $locales);

        $latestPublication = $submission->getLatestPublication();

        $latestPublicationApiUrl = $request->getDispatcher()->url($request, Application::ROUTE_API, $submissionContext->getPath(), 'submissions/' . $submission->getId() . '/publications/' . $latestPublication->getId());
        $temporaryFileApiUrl = $request->getDispatcher()->url($request, Application::ROUTE_API, $submissionContext->getPath(), 'temporaryFiles');
        $issueApiUrl = $request->getDispatcher()->url($request, Application::ROUTE_API, $submissionContext->getData('urlPath'), 'issues/__issueId__');

        $publicFileManager = new PublicFileManager();
        $baseUrl = $request->getBaseUrl() . '/' . $publicFileManager->getContextFilesPath($submissionContext->getId());

        $issueEntryForm = new APP\components\forms\publication\IssueEntryForm($latestPublicationApiUrl, $locales, $latestPublication, $submissionContext, $baseUrl, $temporaryFileApiUrl);

        $sectionWordLimits = [];
        $sectionDao = DAORegistry::getDAO('SectionDAO'); /** @var SectionDAO $sectionDao */
        $sectionIterator = $sectionDao->getByContextId($submissionContext->getId());
        while ($section = $sectionIterator->next()) {
            $sectionWordLimits[$section->getId()] = (int) $section->getAbstractWordCount() ?? 0;
        }

        import('classes.components.forms.publication.AssignToIssueForm');
        import('classes.components.forms.publication.PublishForm');
        $templateMgr->setConstants([
            'FORM_ASSIGN_TO_ISSUE' => FORM_ASSIGN_TO_ISSUE,
            'FORM_ISSUE_ENTRY' => FORM_ISSUE_ENTRY,
            'FORM_PUBLISH' => FORM_PUBLISH,
        ]);

        $components = $templateMgr->getState('components');
        $components[FORM_ISSUE_ENTRY] = $issueEntryForm->getConfig();

        // Add payments form if enabled
        $paymentManager = Application::getPaymentManager($submissionContext);
        $templateMgr->assign([
            'submissionPaymentsEnabled' => $paymentManager->publicationEnabled(),
        ]);
        if ($paymentManager->publicationEnabled()) {
            $submissionPaymentsForm = new APP\components\forms\publication\SubmissionPaymentsForm(
                $request->getDispatcher()->url($request, Application::ROUTE_API, $submissionContext->getPath(), '_submissions/' . $submission->getId() . '/payment'),
                $submission,
                $request->getContext()
            );
            $components[FORM_SUBMISSION_PAYMENTS] = $submissionPaymentsForm->getConfig();
            $templateMgr->setConstants([
                'FORM_SUBMISSION_PAYMENTS' => FORM_SUBMISSION_PAYMENTS,
            ]);
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
            Application::ROUTE_COMPONENT,
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
     *
     * @param int $stageId
     *
     * @return int
     */
    protected function getEditorAssignmentNotificationTypeByStageId($stageId)
    {
        switch ($stageId) {
            case WORKFLOW_STAGE_ID_SUBMISSION:
                return PKPNotification::NOTIFICATION_TYPE_EDITOR_ASSIGNMENT_SUBMISSION;
            case WORKFLOW_STAGE_ID_EXTERNAL_REVIEW:
                return PKPNotification::NOTIFICATION_TYPE_EDITOR_ASSIGNMENT_EXTERNAL_REVIEW;
            case WORKFLOW_STAGE_ID_EDITING:
                return PKPNotification::NOTIFICATION_TYPE_EDITOR_ASSIGNMENT_EDITING;
            case WORKFLOW_STAGE_ID_PRODUCTION:
                return PKPNotification::NOTIFICATION_TYPE_EDITOR_ASSIGNMENT_PRODUCTION;
        }
        return null;
    }

    /**
     * @copydoc PKPWorkflowHandler::_getRepresentationsGridUrl()
     */
    protected function _getRepresentationsGridUrl($request, $submission)
    {
        return $request->getDispatcher()->url(
            $request,
            Application::ROUTE_COMPONENT,
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

    protected function getStageDecisionTypes(int $stageId): array
    {
        $submission = $this->getAuthorizedContextObject(Application::ASSOC_TYPE_SUBMISSION);
        switch ($stageId) {
            case WORKFLOW_STAGE_ID_SUBMISSION:
                $decisionTypes = [
                    new SendExternalReview(),
                    new SkipExternalReview(),
                ];
                if ($submission->getData('status') === Submission::STATUS_DECLINED) {
                    $decisionTypes[] = new RevertInitialDecline();
                } elseif ($submission->getData('status') === Submission::STATUS_QUEUED) {
                    $decisionTypes[] = new InitialDecline();
                }
                break;
            case WORKFLOW_STAGE_ID_EXTERNAL_REVIEW:
                $decisionTypes = [
                    new RequestRevisions(),
                    new Accept(),
                ];
                if ($submission->getData('status') === Submission::STATUS_DECLINED) {
                    $decisionTypes[] = new RevertDecline();
                } elseif ($submission->getData('status') === Submission::STATUS_QUEUED) {
                    $decisionTypes[] = new Decline();
                }
                break;
            case WORKFLOW_STAGE_ID_EDITING:
                /** @var ReviewRoundDAO $reviewRoundDao */
                $reviewRoundDao = DAORegistry::getDAO('ReviewRoundDAO');
                $hasReviewRound = $reviewRoundDao->submissionHasReviewRound($submission->getId(), WORKFLOW_STAGE_ID_EXTERNAL_REVIEW);
                $decisionTypes = [
                    new SendToProduction(),
                    $hasReviewRound
                        ? new BackToReview()
                        : new BackToSubmissionFromCopyediting()
                ];
                break;
            case WORKFLOW_STAGE_ID_PRODUCTION:
                $decisionTypes = [
                    new BackToCopyediting(),
                ];
                break;
        }

        HookRegistry::call('Workflow::Decisions', [&$decisionTypes, $stageId]);

        return $decisionTypes;
    }

    protected function getStageRecommendationTypes(int $stageId): array
    {
        switch ($stageId) {
            case WORKFLOW_STAGE_ID_EXTERNAL_REVIEW:
                $decisionTypes = [
                    new RecommendRevisions(),
                    new RecommendAccept(),
                    new RecommendDecline(),
                ];
                break;
            default:
                $decisionTypes = [];
        }


        HookRegistry::call('Workflow::Recommendations', [$decisionTypes, $stageId]);

        return $decisionTypes;
    }

    /** @copydoc parent::getPrimaryDecisionTypes() */
    protected function getPrimaryDecisionTypes(): array
    {
        return [
            SendExternalReview::class,
            Accept::class,
            SendToProduction::class,
        ];
    }

    /** @copydoc parent::getWarnableDecisionTypes() */
    protected function getWarnableDecisionTypes(): array
    {
        return [
            InitialDecline::class,
            Decline::class,
        ];
    }
}
