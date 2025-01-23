<?php

/**
 * @file jobs/doi/DepositOrcidReview.php
 *
 * Copyright (c) 2014-2024 Simon Fraser University
 * Copyright (c) 2000-2024 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class DepositOrcidReview
 *
 * @ingroup jobs
 *
 * @brief Job to deposit peer review contribution to reviewer's ORCID profile
 */

namespace APP\jobs\orcid;

use APP\core\Application;
use APP\facades\Repo;
use APP\orcid\OrcidReview;
use Carbon\Carbon;
use GuzzleHttp\Exception\ClientException;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use PKP\config\Config;
use PKP\jobs\BaseJob;
use PKP\jobs\orcid\SendUpdateScopeMail;
use PKP\orcid\enums\OrcidDepositType;
use PKP\orcid\OrcidManager;
use PKP\submission\reviewAssignment\ReviewAssignment;

class DepositOrcidReview extends BaseJob implements ShouldBeUnique
{
    public function __construct(
        private int $reviewAssignmentId
    ) {
        parent::__construct();
    }

    /**
     * @inheritDoc
     */
    public function handle(): void
    {
        $reviewAssignment = Repo::reviewAssignment()->get($this->reviewAssignmentId);
        if ($reviewAssignment === null) {
            $this->fail('Review assignment does not exist.');
        }

        if (!in_array($reviewAssignment->getStatus(), ReviewAssignment::REVIEW_COMPLETE_STATUSES)) {
            return;
        }

        $submission = Repo::submission()->get($reviewAssignment->getSubmissionId());
        $context = Application::getContextDAO()->getById($submission->getData('contextId'));

        // If the application is set to sandbox mode, it will not reach out to external services
        if (Config::getVar('general', 'sandbox', false)) {
            $this->fail('Application is set to sandbox mode and will not interact with the ORCID service');
        }

        if (!OrcidManager::isEnabled($context)) {
            return;
        }

        if (!OrcidManager::isMemberApiEnabled($context)) {
            return;
        }

        if (!OrcidManager::getCity($context) || !OrcidManager::getCountry($context)) {
            return;
        }

        $reviewer = Repo::user()->get($reviewAssignment->getData('reviewerId'));

        if ($reviewer->getOrcid() && $reviewer->getData('orcidAccessToken')) {
            // Check user scope, if public API, stop here and request member scope
            if ($reviewer->getData('orcidAccessScope') !== OrcidManager::ORCID_API_SCOPE_MEMBER) {
                // Request member scope and retry deposit
                dispatch(new SendUpdateScopeMail($reviewer, $context->getId(), $this->reviewAssignmentId, OrcidDepositType::REVIEW));
                return;
            }

            $orcidAccessExpiresOn = Carbon::parse($reviewer->getData('orcidAccessExpiresOn'));
            if ($orcidAccessExpiresOn->isFuture()) {
                # Extract only the ORCID from the stored ORCID uri
                $orcid = basename(parse_url($reviewer->getOrcid(), PHP_URL_PATH));

                $orcidReview = (new OrcidReview($submission, $reviewAssignment, $context))->toArray();

                $uri = OrcidManager::getApiPath($context) . OrcidManager::ORCID_API_VERSION_URL . $orcid . '/' . OrcidManager::ORCID_REVIEW_URL;
                $method = 'POST';
                if ($putCode = $reviewAssignment->getData('orcidReviewPutCode')) {
                    $uri .= '/' . $putCode;
                    $method = 'PUT';
                    $orcidReview['put-code'] = $putCode;
                }
                $headers = [
                    'Content-Type' => ' application/vnd.orcid+json; qs=4',
                    'Accept' => 'application/json',
                    'Authorization' => 'Bearer ' . $reviewer->getData('orcidAccessToken')
                ];
                $httpClient = Application::get()->getHttpClient();

                try {
                    $response = $httpClient->request(
                        $method,
                        $uri,
                        [
                            'headers' => $headers,
                            'json' => $orcidReview,
                        ]
                    );

                    $httpStatus = $response->getStatusCode();
                    OrcidManager::logInfo("Response status: {$httpStatus}");
                    $responseHeaders = $response->getHeaders();
                    switch ($httpStatus) {
                        case 200:
                            OrcidManager::logInfo("Review updated in profile, putCode: {$putCode}");
                            break;
                        case 201:
                            $location = $responseHeaders['location'][0];
                            // Extract the ORCID work put code for updates/deletion.
                            $putCode = basename(parse_url($location, PHP_URL_PATH));
                            $reviewAssignment->setData('orcidReviewPutCode', $putCode);
                            Repo::reviewAssignment()->edit($reviewAssignment, ['orcidReviewPutCode']);
                            OrcidManager::logInfo("Review added to profile, putCode: {$putCode}");
                            break;
                        default:
                            OrcidManager::logError("Unexpected status {$httpStatus} response, body: " . json_encode($responseHeaders));
                    }
                } catch (ClientException $exception) {
                    $reason = $exception->getResponse()->getBody()->getContents();
                    OrcidManager::logError("Publication fail: {$reason}");

                    $this->fail($exception);
                }
            }
        }
    }

    public function uniqueId(): string
    {
        return (string) $this->reviewAssignmentId;
    }
}
