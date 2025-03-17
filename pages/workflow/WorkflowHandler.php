<?php

/**
 * @file pages/workflow/WorkflowHandler.php
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2003-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class WorkflowHandler
 *
 * @ingroup pages_reviewer
 *
 * @brief Handle requests for the submission workflow.
 */

namespace APP\pages\workflow;

use APP\components\forms\publication\PublishForm;
use APP\core\Application;
use APP\facades\Repo;
use APP\file\PublicFileManager;
use APP\publication\Publication;
use APP\template\TemplateManager;
use PKP\components\forms\publication\TitleAbstractForm;
use PKP\context\Context;
use PKP\notification\Notification;
use PKP\pages\workflow\PKPWorkflowHandler;
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
                'externalReview', // review
                'editorial',
                'production',
            ]
        );
    }

    /**
     * Setup variables for the template
     *
     * @param \APP\core\Request $request
     */
    public function setupIndex($request)
    {
        parent::setupIndex($request);

        $templateMgr = TemplateManager::getManager($request);
        $submission = $this->getAuthorizedContextObject(Application::ASSOC_TYPE_SUBMISSION);

        $submissionContext = $request->getContext();
        if ($submission->getData('contextId') !== $submissionContext->getId()) {
            $submissionContext = app()->get('context')->get($submission->getData('contextId'));
        }

        $latestPublication = $submission->getLatestPublication();

        $submissionLocale = $submission->getData('locale');
        $locales = collect($submissionContext->getSupportedSubmissionMetadataLocaleNames() + $submission->getPublicationLanguageNames())
            ->map(fn (string $name, string $locale) => ['key' => $locale, 'label' => $name])
            ->sortBy('key')
            ->values()
            ->toArray();

        $latestPublicationApiUrl = $request->getDispatcher()->url($request, Application::ROUTE_API, $submissionContext->getPath(), 'submissions/' . $submission->getId() . '/publications/' . $latestPublication->getId());
        $temporaryFileApiUrl = $request->getDispatcher()->url($request, Application::ROUTE_API, $submissionContext->getPath(), 'temporaryFiles');
        $issueApiUrl = $request->getDispatcher()->url($request, Application::ROUTE_API, $submissionContext->getData('urlPath'), 'issues/__issueId__');

        $publicFileManager = new PublicFileManager();
        $baseUrl = $request->getBaseUrl() . '/' . $publicFileManager->getContextFilesPath($submissionContext->getId());

        $issueEntryForm = new \APP\components\forms\publication\IssueEntryForm($latestPublicationApiUrl, $locales, $latestPublication, $submissionContext, $baseUrl, $temporaryFileApiUrl);

        $sectionWordLimits = [];
        $sections = Repo::section()->getCollector()->filterByContextIds([$submissionContext->getId()])->getMany();
        foreach ($sections as $section) {
            $sectionWordLimits[$section->getId()] = (int) $section->getAbstractWordCount() ?? 0;
        }

        $templateMgr->setConstants([
            'FORM_ASSIGN_TO_ISSUE' => \APP\components\forms\publication\AssignToIssueForm::FORM_ASSIGN_TO_ISSUE,
            'FORM_ISSUE_ENTRY' => $issueEntryForm::FORM_ISSUE_ENTRY,
            'FORM_PUBLISH' => PublishForm::FORM_PUBLISH,
        ]);

        $components = $templateMgr->getState('components');
        $components[$issueEntryForm::FORM_ISSUE_ENTRY] = $this->getLocalizedForm($issueEntryForm, $submissionLocale, $locales);
        $templateMgr->registerClass($issueEntryForm::class, $issueEntryForm::class); // FORM_ISSUE_ENTRY

        $canEditPublication = Repo::submission()->canEditPublication($submission->getId(), $request->getUser()->getId());

        $jatsPanel = $this->getJatsPanel(
            $submission,
            $submissionContext,
            $canEditPublication,
            $latestPublication
        );

        $components[$jatsPanel->id] = $jatsPanel->getConfig();

        // Add payments form if enabled
        $paymentManager = Application::get()->getPaymentManager($submissionContext);
        $templateMgr->assign([
            'submissionPaymentsEnabled' => $paymentManager->publicationEnabled(),
        ]);
        if ($paymentManager->publicationEnabled()) {
            $submissionPaymentsForm = new \APP\components\forms\publication\SubmissionPaymentsForm(
                $request->getDispatcher()->url($request, Application::ROUTE_API, $submissionContext->getPath(), '_submissions/' . $submission->getId() . '/payment'),
                $submission,
                $request->getContext()
            );
            $components[$submissionPaymentsForm::FORM_SUBMISSION_PAYMENTS] = $submissionPaymentsForm->getConfig();
            $templateMgr->setConstants([
                'FORM_SUBMISSION_PAYMENTS' => $submissionPaymentsForm::FORM_SUBMISSION_PAYMENTS,
            ]);
        }

        // Add the word limit to the existing title/abstract form
        if (!empty($components[TitleAbstractForm::FORM_TITLE_ABSTRACT]) &&
                array_key_exists($submission->getLatestPublication()->getData('sectionId'), $sectionWordLimits)) {
            $limit = (int) $sectionWordLimits[$submission->getLatestPublication()->getData('sectionId')];
            foreach ($components[TitleAbstractForm::FORM_TITLE_ABSTRACT]['fields'] as $key => $field) {
                if ($field['name'] === 'abstract') {
                    $components[TitleAbstractForm::FORM_TITLE_ABSTRACT]['fields'][$key]['wordLimit'] = $limit;
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
        $publicationFormIds[] = $issueEntryForm::FORM_ISSUE_ENTRY;

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
     * @return ?int
     */
    protected function getEditorAssignmentNotificationTypeByStageId($stageId)
    {
        switch ($stageId) {
            case WORKFLOW_STAGE_ID_SUBMISSION:
                return Notification::NOTIFICATION_TYPE_EDITOR_ASSIGNMENT_SUBMISSION;
            case WORKFLOW_STAGE_ID_EXTERNAL_REVIEW:
                return Notification::NOTIFICATION_TYPE_EDITOR_ASSIGNMENT_EXTERNAL_REVIEW;
            case WORKFLOW_STAGE_ID_EDITING:
                return Notification::NOTIFICATION_TYPE_EDITOR_ASSIGNMENT_EDITING;
            case WORKFLOW_STAGE_ID_PRODUCTION:
                return Notification::NOTIFICATION_TYPE_EDITOR_ASSIGNMENT_PRODUCTION;
        }
        return null;
    }

    protected function getTitleAbstractForm(string $latestPublicationApiUrl, array $locales, Publication $latestPublication, Context $context): TitleAbstractForm
    {
        $section = Repo::section()->get($latestPublication->getData('sectionId'), $context->getId());
        return new TitleAbstractForm(
            $latestPublicationApiUrl,
            $locales,
            $latestPublication,
            (int) $section->getData('wordCount'),
            !$section->getData('abstractsNotRequired')
        );
    }
}
