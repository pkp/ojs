<?php

/**
 * @file controllers/tab/workflow/WorkflowTabHandler.php
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2003-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class WorkflowTabHandler
 *
 * @ingroup controllers_tab_workflow
 *
 * @brief Handle AJAX operations for workflow tabs.
 */

namespace APP\controllers\tab\workflow;

use APP\core\Application;
use APP\notification\Notification;
use APP\template\TemplateManager;
use PKP\controllers\tab\workflow\PKPWorkflowTabHandler;
use PKP\core\PKPApplication;
use PKP\decision\DecisionType;
use PKP\decision\types\NewExternalReviewRound;
use PKP\linkAction\LinkAction;
use PKP\linkAction\request\AjaxModal;
use PKP\notification\PKPNotification;

class WorkflowTabHandler extends PKPWorkflowTabHandler
{
    /**
     * @copydoc PKPWorkflowTabHandler::fetchTab
     */
    public function fetchTab($args, $request)
    {
        $this->setupTemplate($request);
        $templateMgr = TemplateManager::getManager($request);
        $stageId = $this->getAuthorizedContextObject(Application::ASSOC_TYPE_WORKFLOW_STAGE);
        $submission = $this->getAuthorizedContextObject(Application::ASSOC_TYPE_SUBMISSION);
        switch ($stageId) {
            case WORKFLOW_STAGE_ID_PRODUCTION:
                $dispatcher = $request->getDispatcher();
                $schedulePublicationLinkAction = new LinkAction(
                    'schedulePublication',
                    new AjaxModal(
                        $dispatcher->url(
                            $request,
                            PKPApplication::ROUTE_COMPONENT,
                            null,
                            'tab.issueEntry.IssueEntryTabHandler',
                            'publicationMetadata',
                            null,
                            ['submissionId' => $submission->getId(), 'stageId' => $stageId]
                        ),
                        __('submission.publication')
                    ),
                    __('editor.submission.schedulePublication')
                );
                $templateMgr->assign('schedulePublicationLinkAction', $schedulePublicationLinkAction);
                break;
        }
        return parent::fetchTab($args, $request);
    }

    /**
     * Get all production notification options to be used in the production stage tab.
     *
     * @param int $submissionId
     *
     * @return array
     */
    protected function getProductionNotificationOptions($submissionId)
    {
        return [
            Notification::NOTIFICATION_LEVEL_NORMAL => [
                PKPNotification::NOTIFICATION_TYPE_VISIT_CATALOG => [Application::ASSOC_TYPE_SUBMISSION, $submissionId],
                PKPNotification::NOTIFICATION_TYPE_ASSIGN_PRODUCTIONUSER => [Application::ASSOC_TYPE_SUBMISSION, $submissionId],
                PKPNotification::NOTIFICATION_TYPE_AWAITING_REPRESENTATIONS => [Application::ASSOC_TYPE_SUBMISSION, $submissionId],
            ],
            Notification::NOTIFICATION_LEVEL_TRIVIAL => []
        ];
    }

    protected function getNewReviewRoundDecisionType(int $stageId): DecisionType
    {
        // OJS only supports the external review stage
        return new NewExternalReviewRound();
    }
}
