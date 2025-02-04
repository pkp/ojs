<?php

/**
 * @file api/v1/_submissions/BackendSubmissionsController.php
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2003-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class BackendSubmissionsController
 *
 * @ingroup api_v1_backend
 *
 * @brief Handle API requests for backend operations.
 *
 */

namespace APP\API\v1\_submissions;

use APP\core\Application;
use APP\payment\ojs\OJSCompletedPaymentDAO;
use APP\payment\ojs\OJSPaymentManager;
use APP\submission\Collector;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Route;
use PKP\core\PKPRequest;
use PKP\db\DAORegistry;
use PKP\security\authorization\SubmissionAccessPolicy;
use PKP\security\Role;
use PKP\stageAssignment\StageAssignment;

class BackendSubmissionsController extends \PKP\API\v1\_submissions\PKPBackendSubmissionsController
{
    /**
     * @copydoc \PKP\core\PKPBaseController::getGroupRoutes()
     */
    public function getGroupRoutes(): void
    {
        parent::getGroupRoutes();

        Route::put('{submissionId}/payment', $this->payment(...))
            ->name('_submission.payment')
            ->middleware([
                self::roleAuthorizer([
                    Role::ROLE_ID_SUB_EDITOR,
                    Role::ROLE_ID_MANAGER,
                    Role::ROLE_ID_SITE_ADMIN,
                    Role::ROLE_ID_ASSISTANT,
                ]),
            ])
            ->whereNumber('submissionId');
    }

    /**
     * @copydoc \PKP\core\PKPBaseController::authorize()
     */
    public function authorize(PKPRequest $request, array &$args, array $roleAssignments): bool
    {
        $illuminateRequest = $args[0]; /** @var \Illuminate\Http\Request $illuminateRequest */

        if (static::getRouteActionName($illuminateRequest) === 'payment') {
            $this->addPolicy(new SubmissionAccessPolicy($request, $args, $roleAssignments));
        }

        return parent::authorize($request, $args, $roleAssignments);
    }

    /**
     * Change the status of submission payments.
     */
    public function payment(Request $illuminateRequest): JsonResponse
    {
        $request = $this->getRequest();
        $context = $request->getContext();
        $submission = $this->getAuthorizedContextObject(Application::ASSOC_TYPE_SUBMISSION);

        if (!$submission || !$context || $context->getId() != $submission->getData('contextId')) {
            return response()->json([
                'error' => __('api.404.resourceNotFound')
            ], Response::HTTP_NOT_FOUND);
        }

        /** @var \APP\payment\ojs\OJSPaymentManager $paymentManager */
        $paymentManager = Application::get()->getPaymentManager($context);
        $publicationFeeEnabled = $paymentManager->publicationEnabled();
        if (!$publicationFeeEnabled) {
            return response()->json([
                'error' => __('api.404.resourceNotFound')
            ], Response::HTTP_NOT_FOUND);
        }

        $params = $illuminateRequest->input();

        if (empty($params['publicationFeeStatus'])) {
            return response()->json([
                'publicationFeeStatus' => [__('validator.required')],
            ], Response::HTTP_BAD_REQUEST);
        }

        $completedPaymentDao = DAORegistry::getDAO('OJSCompletedPaymentDAO'); /** @var OJSCompletedPaymentDAO $completedPaymentDao */
        $publicationFeePayment = $completedPaymentDao->getByAssoc(null, OJSPaymentManager::PAYMENT_TYPE_PUBLICATION, $submission->getId());

        switch ($params['publicationFeeStatus']) {
            case 'waived':
                // Check if a waiver already exists; if so, don't do anything.
                if ($publicationFeePayment && !$publicationFeePayment->getAmount()) {
                    break;
                }

                // If a fulfillment (nonzero amount) already exists, remove it.
                if ($publicationFeePayment) {
                    $completedPaymentDao->deleteById($publicationFeePayment->getId());
                }

                // Record a waived payment.
                $queuedPayment = $paymentManager->createQueuedPayment(
                    $request,
                    OJSPaymentManager::PAYMENT_TYPE_PUBLICATION,
                    $request->getUser()->getId(),
                    $submission->getId(),
                    0,
                    '' // Zero amount, no currency
                );
                $paymentManager->queuePayment($queuedPayment);
                $paymentManager->fulfillQueuedPayment($request, $queuedPayment, 'ManualPayment');
                break;
            case 'paid':
                // Check if a fulfilled payment already exists; if so, don't do anything.
                if ($publicationFeePayment && $publicationFeePayment->getAmount()) {
                    break;
                }

                // If a waiver (0 amount) already exists, remove it.
                if ($publicationFeePayment) {
                    $completedPaymentDao->deleteById($publicationFeePayment->getId());
                }

                // Record a fulfilled payment.
                // Replaces StageAssignmentDAO::getBySubmissionAndRoleIds
                $submitterAssignment = StageAssignment::withSubmissionIds([$submission->getId()])
                    ->withRoleIds([Role::ROLE_ID_AUTHOR])
                    ->first();

                $queuedPayment = $paymentManager->createQueuedPayment(
                    $request,
                    OJSPaymentManager::PAYMENT_TYPE_PUBLICATION,
                    $submitterAssignment->userId,
                    $submission->getId(),
                    $context->getData('publicationFee'),
                    $context->getData('currency')
                );
                $paymentManager->queuePayment($queuedPayment);
                $paymentManager->fulfillQueuedPayment($request, $queuedPayment, 'Waiver');
                break;
            case 'unpaid':
                if ($publicationFeePayment) {
                    $completedPaymentDao->deleteById($publicationFeePayment->getId());
                }
                break;
            default:
                return response()->json([
                    'publicationFeeStatus' => [__('validator.required')],
                ], Response::HTTP_BAD_REQUEST);
        }

        return response()->json([], Response::HTTP_OK);
    }

    /** @copydoc PKPSubmissionHandler::getSubmissionCollector() */
    protected function getSubmissionCollector(array $queryParams): Collector
    {
        $collector = parent::getSubmissionCollector($queryParams);

        if (isset($queryParams['issueIds'])) {
            $collector->filterByIssueIds(
                array_map(intval(...), paramToArray($queryParams['issueIds']))
            );
        }

        if (isset($queryParams['sectionIds'])) {
            $collector->filterBySectionIds(
                array_map(intval(...), paramToArray($queryParams['sectionIds']))
            );
        }

        return $collector;
    }
}
