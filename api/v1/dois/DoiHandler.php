<?php

/**
 * @file api/v1/dois/DoiHandler.php
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2003-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class DoiHandler
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
use PKP\context\Context;
use PKP\core\APIResponse;
use PKP\doi\Doi;
use PKP\doi\exceptions\DoiException;
use PKP\security\Role;
use Slim\Http\Request as SlimRequest;
use Slim\Http\Response;

class DoiHandler extends \PKP\API\v1\dois\PKPDoiHandler
{
    /**
     * Constructor
     */
    public function __construct()
    {
        $this->_handlerPath = 'dois';
        $this->_endpoints = array_merge_recursive($this->_endpoints, [
            'POST' => [
                [
                    'pattern' => $this->getEndpointPattern() . '/issues/assignDois',
                    'handler' => [$this, 'assignIssueDois'],
                    'roles' => [Role::ROLE_ID_MANAGER, Role::ROLE_ID_SITE_ADMIN]
                ]
            ],
            'PUT' => [
                [
                    'pattern' => $this->getEndpointPattern() . '/issues/export',
                    'handler' => [$this, 'exportIssues'],
                    'roles' => [Role::ROLE_ID_MANAGER, Role::ROLE_ID_SITE_ADMIN],
                ],
                [
                    'pattern' => $this->getEndpointPattern() . '/issues/deposit',
                    'handler' => [$this, 'depositIssues'],
                    'roles' => [Role::ROLE_ID_MANAGER, Role::ROLE_ID_SITE_ADMIN],
                ],
                [
                    'pattern' => $this->getEndpointPattern() . '/issues/markRegistered',
                    'handler' => [$this, 'markIssuesRegistered'],
                    'roles' => [Role::ROLE_ID_MANAGER, Role::ROLE_ID_SITE_ADMIN],
                ],
                [
                    'pattern' => $this->getEndpointPattern() . '/issues/markUnregistered',
                    'handler' => [$this, 'markIssuesUnregistered'],
                    'roles' => [Role::ROLE_ID_MANAGER, Role::ROLE_ID_SITE_ADMIN],
                ],
                [
                    'pattern' => $this->getEndpointPattern() . '/issues/markStale',
                    'handler' => [$this, 'markIssuesStale'],
                    'roles' => [Role::ROLE_ID_MANAGER, Role::ROLE_ID_SITE_ADMIN],
                ],
            ],
        ]);
        parent::__construct();
    }

    /**
     * Export XML for configured DOI registration agency
     */
    public function exportIssues(SlimRequest $slimRequest, APIResponse $response, array $args): Response
    {
        // Retrieve and validate issues
        $requestIds = $slimRequest->getParsedBody()['ids'] ?? [];
        if (!count($requestIds)) {
            return $response->withStatus(404)->withJsonError('api.dois.404.noPubObjectIncluded');
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
            return $response->withStatus(400)->withJsonError('api.dois.400.invalidPubObjectIncluded');
        }

        /** @var Issue[] $issues */
        $issues = [];
        foreach ($requestIds as $id) {
            $issues[] = Repo::issue()->get($id);
        }

        if (empty($issues[0])) {
            return $response->withStatus(404)->withJsonError('api.dois.404.doiNotFound');
        }

        $agency = $context->getConfiguredDoiAgency();
        if ($agency === null) {
            return $response->withStatus(400)->withJsonError('api.dois.400.noRegistrationAgencyConfigured');
        }

        // Invoke IDoiRegistrationAgency::exportIssues
        $responseData = $agency->exportIssues($issues, $context);
        if (!empty($responseData['xmlErrors'])) {
            return $response->withStatus(400)->withJsonError('api.dois.400.xmlExportFailed');
        }
        return $response->withJson(['temporaryFileId' => $responseData['temporaryFileId']], 200);
    }

    /**
     * Deposit XML for configured DOI registration agency
     */
    public function depositIssues(SlimRequest $slimRequest, APIResponse $response, array $args): Response
    {
        // Retrieve and validate issues
        $requestIds = $slimRequest->getParsedBody()['ids'] ?? [];
        if (!count($requestIds)) {
            return $response->withStatus(404)->withJsonError('api.dois.404.noPubObjectIncluded');
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
            return $response->withStatus(400)->withJsonError('api.dois.400.invalidPubObjectIncluded');
        }

        $agency = $context->getConfiguredDoiAgency();
        if ($agency === null) {
            return $response->withStatus(400)->withJsonError('api.dois.400.noRegistrationAgencyConfigured');
        }

        $doisToUpdate = [];
        foreach ($requestIds as $issueId) {
            dispatch(new DepositIssue($issueId, $context, $agency));
            array_merge($doisToUpdate, Repo::doi()->getDoisForIssue($issueId));
        }
        Repo::doi()->markSubmitted($doisToUpdate);

        return $response->withStatus(200);
    }

    /**
     * Mark submission DOIs as registered with a DOI registration agency.
     */
    public function markIssuesRegistered(SlimRequest $slimRequest, APIResponse $response, array $args): Response
    {
        // Retrieve issues
        $requestIds = $slimRequest->getParsedBody()['ids'] ?? [];
        if (!count($requestIds)) {
            return $response->withStatus(404)->withJsonError('api.dois.404.noPubObjectIncluded');
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

            return $response->withJson(
                [
                    'failedDoiActions' => array_map(
                        function (DoiException $item) {
                            return $item->getMessage();
                        },
                        $failedDoiActions
                    )
                ],
                400
            );
        }

        foreach ($requestIds as $id) {
            $doiIds = Repo::doi()->getDoisForIssue($id);
            foreach ($doiIds as $doiId) {
                Repo::doi()->markRegistered($doiId);
            }
        }

        return $response->withStatus(200);
    }

    /**
     * Mark issues DOIs as no longer registered with a DOI registration agency.
     */
    public function markIssuesUnregistered(SlimRequest $slimRequest, APIResponse $response, array $args): Response
    {
        // Retrieve issues
        $requestIds = $slimRequest->getParsedBody()['ids'] ?? [];
        if (!count($requestIds)) {
            return $response->withStatus(404)->withJsonError('api.dois.404.noPubObjectIncluded');
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

            return $response->withJson(
                [
                    'failedDoiActions' => array_map(
                        function (DoiException $item) {
                            return $item->getMessage();
                        },
                        $failedDoiActions
                    )
                ],
                400
            );
        }

        foreach ($requestIds as $id) {
            $doiIds = Repo::doi()->getDoisForIssue($id);
            foreach ($doiIds as $doiId) {
                Repo::doi()->markUnregistered($doiId);
            }
        }

        return $response->withStatus(200);
    }

    /**
     * Mark submission DOIs as stale, indicating a need to be resubmitted to registration agency with updated metadata.
     */
    public function markIssuesStale(SlimRequest $slimRequest, APIResponse $response, array $args): Response
    {
        // Retrieve issues
        $requestIds = $slimRequest->getParsedBody()['ids'] ?? [];
        if (!count($requestIds)) {
            return $response->withStatus(404)->withJsonError('api.dois.404.noPubObjectIncluded');
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

            return $response->withJson(
                [
                    'failedDoiActions' => array_map(
                        function (DoiException $item) {
                            return $item->getMessage();
                        },
                        $failedDoiActions
                    )
                ],
                400
            );
        }

        foreach ($requestIds as $id) {
            $doiIds = Repo::doi()->getDoisForIssue($id);
            Repo::doi()->markStale($doiIds);
        }

        return $response->withStatus(200);
    }

    /**
     * Assign DOIs to issue
     */
    public function assignIssueDois(SlimRequest $slimRequest, APIResponse $response, array $args): Response
    {
        // Retrieve issues
        $ids = $slimRequest->getParsedBody()['ids'] ?? [];
        if (!count($ids)) {
            return $response->withStatus(404)->withJsonError('api.issue.404.issuesNotFound');
        }

        $context = $this->getRequest()->getContext();
        $doiPrefix = $context->getData(Context::SETTING_DOI_PREFIX);
        if (empty($doiPrefix)) {
            return $response->withStatus(403)->withJsonError('api.dois.403.prefixRequired');
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
            return $response->withJson(
                [
                    'failedDoiActions' => array_map(
                        function (DoiException $item) {
                            return $item->getMessage();
                        },
                        $failedDoiActions
                    )
                ],
                400
            );
        }

        return $response->withJson(['failedDoiActions' => $failedDoiActions], 200);
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
