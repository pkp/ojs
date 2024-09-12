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
use PKP\config\Config;
use PKP\jobs\BaseJob;
use PKP\orcid\OrcidManager;

class DepositOrcidReview extends BaseJob
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

        $submission = Repo::submission()->get($reviewAssignment->getSubmissionId());
        $context = Application::getContextDAO()->getById($submission->getData('contextId'));

        // If the application is set to sandbox mode, it will not reach out to external services
        if (Config::getVar('general', 'sandbox', false)) {
            $this->fail('Application is set to sandbox mode and will not interact with the ORCID service');
        }

        if (!OrcidManager::isMemberApiEnabled($context)) {
            return;
        }

        if (!OrcidManager::getCity($context) || !OrcidManager::getCountry($context)) {
            return;
        }

        $reviewer = Repo::user()->get($reviewAssignment->getData('reviewerId'));

        if ($reviewer->getOrcid() && $reviewer->getData('orcidAccessToken')) {
            $orcidAccessExpiresOn = Carbon::parse($reviewer->getData('orcidAccessExpiresOn'));
            if ($orcidAccessExpiresOn->isFuture()) {
                # Extract only the ORCID from the stored ORCID uri
                $orcid = basename(parse_url($reviewer->getOrcid(), PHP_URL_PATH));

                $orcidReview = new OrcidReview($submission, $reviewAssignment, $context);

                $uri = OrcidManager::getApiPath($context) . OrcidManager::ORCID_API_VERSION_URL . $orcid . '/' . OrcidManager::ORCID_REVIEW_URL;
                $method = 'POST';
                if ($putCode = $reviewer->getData('orcidReviewPutCode')) {
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
                            'json' => $orcidReview->toArray(),
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
                            $location = $responseHeaders['Location'][0];
                            // Extract the ORCID work put code for updates/deletion.
                            $putCode = basename(parse_url($location, PHP_URL_PATH));
                            $reviewer->setData('orcidReviewPutCode', $putCode);
                            Repo::user()->edit($reviewer, ['orcidReviewPutCode']);
                            OrcidManager::logInfo("Review added to profile, putCode: {$putCode}");
                            break;
                        default:
                            OrcidManager::logError("Unexpected status {$httpStatus} response, body: " . json_encode($responseHeaders));
                    }
                } catch (ClientException $exception) {
                    $reason = $exception->getResponse()->getBody();
                    OrcidManager::logError("Publication fail: {$reason}");

                    $this->fail($exception);
                }
            }
        }
    }
}
