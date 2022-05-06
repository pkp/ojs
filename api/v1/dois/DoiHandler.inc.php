<?php

/**
 * @file api/v1/dois/DoiHandler.inc.php
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2003-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class DoiHandler
 * @ingroup api_v1_dois
 *
 * @brief Handle API requests for DOI operations.
 *
 */

use APP\facades\Repo;
use PKP\context\Context;
use PKP\core\APIResponse;
use PKP\security\Role;

use Slim\Http\Request as SlimRequest;
use Slim\Http\Response;

import('lib.pkp.api.v1.dois.PKPDoiHandler');

class DoiHandler extends PKPDoiHandler
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
                    'pattern' => $this->getEndpointPattern() . '/issues/assignDois',
                    'handler' => [$this, 'assignIssueDois'],
                    'roles' => [Role::ROLE_ID_MANAGER, Role::ROLE_ID_SITE_ADMIN]
                ]
            ]
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

        /** @var Context $context */
        $context = $this->getRequest()->getContext();

        $validIds = Repo::issue()->getIds(
            Repo::issue()
                ->getCollector()
                ->filterByContextIds([$context->getId()])
                ->filterByPublished(true)
        )->toArray();

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

        /** @var Context $context */
        $context = $this->getRequest()->getContext();

        $validIds = Repo::issue()->getIds(
            Repo::issue()
                ->getCollector()
                ->filterByContextIds([$context->getId()])
                ->filterByPublished(true)
        )->toArray();

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

        $responseData = $agency->depositIssues($issues, $context);
        if ($responseData['hasErrors']) {
            return $response->withStatus(400)->withJsonError($responseData['responseMessage']);
        }
        return $response->withJson(['responseMessage' => $responseData['responseMessage']], 200);
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

        $validIds = Repo::issue()->getIds(
            Repo::issue()
                ->getCollector()
                ->filterByContextIds([$context->getId()])
                ->filterByPublished(true)
        )->toArray();

        $invalidIds = array_diff($requestIds, $validIds);
        if (count($invalidIds)) {
            return $response->withStatus(400)->withJsonError('api.dois.400.invalidPubObjectIncluded');
        }

        $idsWithErrors = [];
        foreach ($requestIds as $id) {
            $doiIds = Repo::doi()->getDoisForIssue($id);
            foreach ($doiIds as $doiId) {
                Repo::doi()->markRegistered($doiId);
            }
        }

        if (!empty($idsWithErrors)) {
            return $response->withStatus(400)->withJsonError('api.dois.400.markRegisteredFailed');
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

        // Assign DOIs
        foreach ($ids as $id) {
            $issue = Repo::issue()->get($id);
            if ($issue !== null) {
                Repo::issue()->createDoi($issue);
            }
        }

        return $response->withStatus(200);
    }
}
