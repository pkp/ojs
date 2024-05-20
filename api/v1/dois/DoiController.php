<?php

/**
 * @file api/v1/dois/DoiController.php
 *
 * Copyright (c) 2023 Simon Fraser University
 * Copyright (c) 2023 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class DoiController
 *
 * @ingroup api_v1_dois
 *
 * @brief Handle API requests for DOI operations.
 *
 */

namespace APP\API\v1\dois;

use APP\facades\Repo;
use APP\issue\Issue;
use APP\jobs\doi\DepositIssue;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Route;
use PKP\context\Context;
use PKP\doi\Doi;
use PKP\doi\exceptions\DoiException;

class DoiController extends \PKP\API\v1\dois\PKPDoiController
{
    /**
     * @copydoc \PKP\core\PKPBaseController::getGroupRoutes()
     */
    public function getGroupRoutes(): void
    {
        parent::getGroupRoutes();

        Route::post('issues/assignDois', $this->assignIssueDois(...))
            ->name('doi.issues.assignDois');

        Route::put('issues/export', $this->exportIssues(...))
            ->name('doi.issues.export');

        Route::put('issues/deposit', $this->depositIssues(...))
            ->name('doi.issues.deposit');

        Route::put('issues/markRegistered', $this->markIssuesRegistered(...))
            ->name('doi.issues.markRegistered');

        Route::put('issues/markUnregistered', $this->markIssuesUnregistered(...))
            ->name('doi.issues.markUnregistered');

        Route::put('issues/markStale', $this->markIssuesStale(...))
            ->name('doi.issues.markStale');
    }

    /**
     * Export XML for configured DOI registration agency
     */
    public function exportIssues(Request $illuminateRequest): JsonResponse
    {
        // Retrieve and validate issues
        $requestIds = $illuminateRequest->input()['ids'] ?? [];
        if (!count($requestIds)) {
            return response()->json([
                'error' => __('api.dois.404.noPubObjectIncluded')
            ], Response::HTTP_NOT_FOUND);
        }

        $context = $this->getRequest()->getContext();

        $validIds = Repo::issue()
            ->getCollector()
            ->filterByContextIds([$context->getId()])
            ->filterByPublished(true)
            ->getIds()
            ->toArray();

        $invalidIds = array_diff($requestIds, $validIds);
        if (count($invalidIds)) {
            return response()->json([
                'error' => __('api.dois.400.invalidPubObjectIncluded')
            ], Response::HTTP_BAD_REQUEST);
        }

        /** @var Issue[] $issues */
        $issues = [];
        foreach ($requestIds as $id) {
            $issues[] = Repo::issue()->get($id);
        }

        if (empty($issues[0])) {
            return response()->json([
                'error' => __('api.dois.404.doiNotFound')
            ], Response::HTTP_NOT_FOUND);
        }

        $agency = $context->getConfiguredDoiAgency();
        if ($agency === null) {
            return response()->json([
                'error' => __('api.dois.400.noRegistrationAgencyConfigured')
            ], Response::HTTP_BAD_REQUEST);
        }

        // Invoke IDoiRegistrationAgency::exportIssues
        $responseData = $agency->exportIssues($issues, $context);
        if (!empty($responseData['xmlErrors'])) {
            return response()->json([
                'error' => __('api.dois.400.xmlExportFailed')
            ], Response::HTTP_BAD_REQUEST);
        }

        return response()->json([
            'temporaryFileId' => $responseData['temporaryFileId']
        ], Response::HTTP_OK);
    }

    /**
     * Deposit XML for configured DOI registration agency
     */
    public function depositIssues(Request $illuminateRequest): JsonResponse
    {
        // Retrieve and validate issues
        $requestIds = $illuminateRequest->input()['ids'] ?? [];
        if (!count($requestIds)) {
            return response()->json([
                'error' => __('api.dois.404.noPubObjectIncluded')
            ], Response::HTTP_NOT_FOUND);
        }

        $context = $this->getRequest()->getContext();

        $validIds = Repo::issue()
            ->getCollector()
            ->filterByContextIds([$context->getId()])
            ->filterByPublished(true)
            ->getIds()
            ->toArray();

        $invalidIds = array_diff($requestIds, $validIds);
        if (count($invalidIds)) {
            return response()->json([
                'error' => __('api.dois.400.invalidPubObjectIncluded')
            ], Response::HTTP_BAD_REQUEST);
        }

        $agency = $context->getConfiguredDoiAgency();
        if ($agency === null) {
            return response()->json([
                'error' => __('api.dois.400.noRegistrationAgencyConfigured')
            ], Response::HTTP_BAD_REQUEST);
        }

        $doisToUpdate = [];
        foreach ($requestIds as $issueId) {
            dispatch(new DepositIssue($issueId, $context, $agency));
            array_merge($doisToUpdate, Repo::doi()->getDoisForIssue($issueId));
        }
        Repo::doi()->markSubmitted($doisToUpdate);

        return response()->json([], Response::HTTP_OK);
    }

    /**
     * Mark submission DOIs as registered with a DOI registration agency.
     */
    public function markIssuesRegistered(Request $illuminateRequest): JsonResponse
    {
        // Retrieve issues
        $requestIds = $illuminateRequest->input()['ids'] ?? [];
        if (!count($requestIds)) {
            return response()->json([
                'error' => __('api.dois.404.noPubObjectIncluded')
            ], Response::HTTP_NOT_FOUND);
        }

        $context = $this->getRequest()->getContext();

        $validIds = Repo::issue()
            ->getCollector()
            ->filterByContextIds([$context->getId()])
            ->filterByPublished(true)
            ->getIds()
            ->toArray();

        $invalidIds = array_diff($requestIds, $validIds);
        if (count($invalidIds)) {
            $failedDoiActions = array_map(function (int $id) {
                $issueTitle = Repo::issue()->get($id)?->getIssueIdentification() ?? 'Issue not found';
                return new DoiException(DoiException::ISSUE_NOT_PUBLISHED, $issueTitle, $issueTitle);
            }, $invalidIds);

            return response()->json(['failedDoiActions' => array_map(
                function (DoiException $item) {
                    return $item->getMessage();
                },
                $failedDoiActions
            )], Response::HTTP_BAD_REQUEST);
        }

        foreach ($requestIds as $id) {
            $doiIds = Repo::doi()->getDoisForIssue($id);
            foreach ($doiIds as $doiId) {
                Repo::doi()->markRegistered($doiId);
            }
        }

        return response()->json([], Response::HTTP_OK);
    }

    /**
     * Mark issues DOIs as no longer registered with a DOI registration agency.
     */
    public function markIssuesUnregistered(Request $illuminateRequest): JsonResponse
    {
        // Retrieve issues
        $requestIds = $illuminateRequest->input()['ids'] ?? [];
        if (!count($requestIds)) {
            return response()->json([
                'error' => __('api.dois.404.noPubObjectIncluded')
            ], Response::HTTP_NOT_FOUND);
        }

        $context = $this->getRequest()->getContext();

        $validIds = Repo::issue()
            ->getCollector()
            ->filterByContextIds([$context->getId()])
            ->getIds()
            ->toArray();

        $invalidIds = array_diff($requestIds, $validIds);
        if (count($invalidIds)) {
            $failedDoiActions = array_map(function (int $id) {
                $issueTitle = Repo::issue()->get($id)?->getIssueIdentification() ?? 'Issue not found';
                return new DoiException(DoiException::INCORRECT_ISSUE_CONTEXT, $issueTitle, $issueTitle);
            }, $invalidIds);

            return response()->json(['failedDoiActions' => array_map(
                function (DoiException $item) {
                    return $item->getMessage();
                },
                $failedDoiActions
            )], Response::HTTP_BAD_REQUEST);
        }

        foreach ($requestIds as $id) {
            $doiIds = Repo::doi()->getDoisForIssue($id);
            foreach ($doiIds as $doiId) {
                Repo::doi()->markUnregistered($doiId);
            }
        }

        return response()->json([], Response::HTTP_OK);
    }

    /**
     * Mark submission DOIs as stale, indicating a need to be resubmitted to registration agency with updated metadata.
     */
    public function markIssuesStale(Request $illuminateRequest): JsonResponse
    {
        // Retrieve issues
        $requestIds = $illuminateRequest->input()['ids'] ?? [];
        if (!count($requestIds)) {
            return response()->json([
                'error' => __('api.dois.404.noPubObjectIncluded')
            ], Response::HTTP_NOT_FOUND);
        }

        $context = $this->getRequest()->getContext();

        $validIds = Repo::issue()
            ->getCollector()
            ->filterByContextIds([$context->getId()])
            ->filterByPublished(true)
            // Items can only be considered stale if they have been deposited/queued for deposit in the first place
            ->filterByDoiStatuses([Doi::STATUS_SUBMITTED, Doi::STATUS_REGISTERED])
            ->getIds()
            ->toArray();

        $invalidIds = array_diff($requestIds, $validIds);
        if (count($invalidIds)) {
            $failedDoiActions = array_map(function (int $id) {
                $issueTitle = Repo::issue()->get($id)?->getIssueIdentification() ?? 'Issue not found';
                return new DoiException(DoiException::INCORRECT_STALE_STATUS, $issueTitle, $issueTitle);
            }, $invalidIds);

            return response()->json(['failedDoiActions' => array_map(
                function (DoiException $item) {
                    return $item->getMessage();
                },
                $failedDoiActions
            )], Response::HTTP_BAD_REQUEST);
        }

        foreach ($requestIds as $id) {
            $doiIds = Repo::doi()->getDoisForIssue($id);
            Repo::doi()->markStale($doiIds);
        }

        return response()->json([], Response::HTTP_OK);
    }

    /**
     * Assign DOIs to issue
     */
    public function assignIssueDois(Request $illuminateRequest): JsonResponse
    {
        // Retrieve issues
        $ids = $illuminateRequest->input()['ids'] ?? [];
        if (!count($ids)) {
            return response()->json([
                'error' => __('api.issue.404.issuesNotFound'),
            ], Response::HTTP_NOT_FOUND);
        }

        $context = $this->getRequest()->getContext();
        $doiPrefix = $context->getData(Context::SETTING_DOI_PREFIX);
        if (empty($doiPrefix)) {
            return response()->json([
                'error' => __('api.dois.403.prefixRequired'),
            ], Response::HTTP_FORBIDDEN);
        }

        $failedDoiActions = [];

        // Assign DOIs
        foreach ($ids as $id) {
            $issue = Repo::issue()->get($id);
            if ($issue !== null) {
                $creationFailureResults = Repo::issue()->createDoi($issue);
                $failedDoiActions = array_merge($failedDoiActions, $creationFailureResults);
            }
        }

        if (!empty($failedDoiActions)) {
            return response()->json(['failedDoiActions' => array_map(
                function (DoiException $item) {
                    return $item->getMessage();
                },
                $failedDoiActions
            )], Response::HTTP_BAD_REQUEST);
        }

        return response()->json([
            'failedDoiActions' => $failedDoiActions
        ], Response::HTTP_OK);
    }

    /**
     * @copydoc PKPDoiHandler::getPubObjectHandler()
     */
    protected function getPubObjectHandler(string $type): mixed
    {
        $handler = parent::getPubObjectHandler($type);
        if ($handler !== null) {
            return $handler;
        }

        return match ($type) {
            Repo::doi()::TYPE_ISSUE => Repo::issue(),
            default => null,
        };
    }
}
