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
use APP\plugins\IDoiRegistrationAgency;
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
                    'roles' => [Role::ROLE_ID_MANAGER],
                ],
                [
                    'pattern' => $this->getEndpointPattern() . '/issues/deposit',
                    'handler' => [$this, 'depositIssues'],
                    'roles' => [Role::ROLE_ID_MANAGER],
                ],
                [
                    'pattern' => $this->getEndpointPattern() . '/issues/markRegistered',
                    'handler' => [$this, 'markIssuesRegistered'],
                    'roles' => [Role::ROLE_ID_MANAGER],
                ],
                [
                    'pattern' => $this->getEndpointPattern() . '/issues/assignDois',
                    'handler' => [$this, 'assignIssueDois'],
                    'roles' => [Role::ROLE_ID_MANAGER]
                ]
            ]
        ]);
        parent::__construct();
    }

    public function exportIssues(SlimRequest $slimRequest, APIResponse $response, array $args): Response
    {
        // Retrieve and validate issues
        $ids = $this->getIdsFromRequest($slimRequest);
        if ($ids === null) {
            return $response->withStatus(404)->withJsonError('api.dois.404.noPubObjectIncluded');
        }
        /** @var Issue[] $issues */
        $issues = [];
        foreach ($ids as $id) {
            $issue = Repo::issue()->get($id);
            if (Repo::issue()->checkIfValidForDoiExport($issue)) {
                $issues[] = $issue;
            } else {
                return $response->withStatus(400)->withJsonError('api.dois.400.noUnpublishedItems');
            }
        }

        // Get configured agency
        if (empty($issues[0])) {
            return $response->withStatus(404)->withJsonError('api.dois.404.doiNotFound');
        }

        $contextId = $issues[0]->getData('journalId');
        /** @var \PKP\context\ContextDAO $contextDao */
        $contextDao = \APP\core\Application::getContextDAO();
        $context = $contextDao->getById($contextId);

        /** @var IDoiRegistrationAgency $agency */
        $agency = $this->_getAgencyFromContext($context);
        if ($agency === null) {
            return $response->withStatus(400)->withJsonError('api.dois.400.noRegistrationAgencyConfigured');
        }

        // Invoke IDoiRegistrationAgency::exportIssues
        $responseData = $agency->exportIssues($issues, $context);
        if (!empty($responseData['xmlErrors'])) {
            return $response->withStatus(400)->withJsonError('api.dois.400.xmlExportFailed');
        }
        return $response->withJson(['tempFileId' => $responseData['tempFileId']], 200);
    }

    public function depositIssues(SlimRequest $slimRequest, APIResponse $response, array $args): Response
    {
        // Retrieve and validate issues
        $ids = $this->getIdsFromRequest($slimRequest);
        if ($ids === null) {
            return $response->withStatus(404)->withJsonError('api.dois.404.noPubObjectIncluded');
        }
        /** @var Issue[] $issues */
        $issues = [];
        foreach ($ids as $id) {
            $issue = Repo::issue()->get($id);
            if (Repo::issue()->checkIfValidForDoiExport($issue)) {
                $issues[] = $issue;
            } else {
                return $response->withStatus(400)->withJsonError('api.dois.400.noUnpublishedItems');
            }
        }

        // Get configured agency
        if (empty($issues[0])) {
            return $response->withStatus(404)->withJsonError('api.dois.404.doiNotFound');
        }

        $contextId = $issues[0]->getData('journalId');
        /** @var \PKP\context\ContextDAO $contextDao */
        $contextDao = \APP\core\Application::getContextDAO();
        $context = $contextDao->getById($contextId);

        /** @var IDoiRegistrationAgency $agency */
        $agency = $this->_getAgencyFromContext($context);
        if ($agency === null) {
            return $response->withStatus(400)->withJsonError('api.dois.400.noRegistrationAgencyConfigured');
        }

        $responseData = $agency->depositIssues($issues, $context);
        if ($responseData['hasErrors']) {
            return $response->withStatus(400)->withJsonError($responseData['responseMessage']);
        }
        return $response->withJson(['responseMessage' => $responseData['responseMessage']], 200);
    }

    public function markIssuesRegistered(SlimRequest $slimRequest, APIResponse $response, array $args): Response
    {
        // Retrieve issues
        $ids = $this->getIdsFromRequest($slimRequest);
        if ($ids === null) {
            return $response->withStatus(404)->withJsonError('api.dois.404.noPubObjectIncluded');
        }

        $idsWithErrors = [];
        // TODO: #doi Should mark registered be allowed for unpublished items?
        foreach ($ids as $id) {
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
     *
     */
    public function assignIssueDois(SlimRequest $slimRequest, APIResponse $response, array $args): Response
    {
        // Retrieve issues
        $ids = $this->getIdsFromRequest($slimRequest);
        if ($ids === null) {
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
