<?php

/**
 * @file jobs/orcid/ReconcileOrcidReviewPutCode.php
 *
 * Copyright (c) 2014-2024 Simon Fraser University
 * Copyright (c) 2000-2024 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class ReconcileOrcidReviewPutCode
 *
 * @ingroup jobs
 *
 * @brief Job to retrieve previously submitted reviews from ORCiD and store their put-codes.
 */

namespace APP\jobs\orcid;

use APP\core\Application;
use APP\facades\Repo;
use Carbon\Carbon;
use GuzzleHttp\Exception\ClientException;
use PKP\context\Context;
use PKP\jobs\BaseJob;
use PKP\orcid\OrcidManager;
use PKP\submission\reviewAssignment\ReviewAssignment;
use PKP\user\User;

class ReconcileOrcidReviewPutCode extends BaseJob
{
    public function __construct(
        private int $reviewerId,
        private int $contextId
    ) {
        parent::__construct();
    }

    /**
     * @inheritDoc
     */
    public function handle(): void
    {
        $reviewer = Repo::user()->get($this->reviewerId); /** @var User $reviewer */

        if (!$reviewer || !$reviewer->getData('orcidReviewPutCode')) {
            return;
        }

        if ($reviewer->getOrcid() && $reviewer->getData('orcidAccessToken')) {
            $orcidAccessExpiresOn = Carbon::parse($reviewer->getData('orcidAccessExpiresOn'));
            if ($orcidAccessExpiresOn->isFuture()) {
                OrcidManager::logInfo('Attempting to update put-codes for reviewer ' . $reviewer->getId());

                $context = Application::getContextDAO()->getById($this->contextId); /** @var Context $context */
                $orcid = basename(parse_url($reviewer->getOrcid(), PHP_URL_PATH));
                $uri = OrcidManager::getApiPath($context) . OrcidManager::ORCID_API_VERSION_URL . $orcid . '/' . OrcidManager::ORCID_ALL_REVIEWS_URL;

                $headers = [
                    'Content-Type' => 'application/vnd.orcid+json; qs=4',
                    'Accept' => 'application/json',
                    'Authorization' => 'Bearer ' . $reviewer->getData('orcidAccessToken')
                ];

                $httpClient = Application::get()->getHttpClient();

                try {
                    // Get all deposited peer reviews for reviewer
                    $response = $httpClient->request('GET', $uri, ['headers' => $headers]);
                    $data = json_decode($response->getBody(), true);
                    $ojsReviews = [];

                    foreach ($data['group'] as $dataGroup) {
                        // Select only peer reviews submitted from OJS
                        foreach ($dataGroup['peer-review-group'] as $peerReview) {
                            if ($peerReview['peer-review-summary'][0]['source']['source-client-id']['path'] === $context->getData(OrcidManager::CLIENT_ID)) {
                                $ojsReviews[] = $peerReview;
                            }
                        }
                    }

                    foreach ($ojsReviews as $review) {
                        $reviewSummary = $review['peer-review-summary'][0];
                        $externalIds = $reviewSummary['external-ids']['external-id'];
                        $putCode = $reviewSummary['put-code'];
                        $reviewRoundId = null;

                        foreach ($externalIds as $externalId) {
                            if ($externalId['external-id-type'] === 'source-work-id') {
                                $reviewRoundId = $externalId['external-id-value'];
                                break;
                            }
                        }

                        if (!$reviewRoundId) {
                            continue;
                        }

                        OrcidManager::logInfo("Processing previously deposited review with put-code: {$putCode}, and external-id-value (reviewRoundId) {$reviewRoundId}.");

                        // Find review assignment and update put-code
                        $reviewAssignment = Repo::reviewAssignment()
                            ->getCollector()
                            ->filterByContextIds([$this->contextId])
                            ->filterByReviewerIds([$reviewer->getId()])
                            ->filterByReviewRoundIds([$reviewRoundId])
                            ->getMany()->first(); /**@var ReviewAssignment $reviewAssignment * */

                        if ($reviewAssignment) {
                            $reviewAssignment->setData('orcidReviewPutCode', $putCode);
                            Repo::reviewAssignment()->edit($reviewAssignment, ['orcidReviewPutCode']);
                            OrcidManager::logInfo("Successfully updated put-code ({$putCode}) for review assignment with ID {$reviewAssignment->getId()}, reviewRoundId {$reviewRoundId}, and userID {$reviewer->getId()}.");
                        } else {
                            OrcidManager::logInfo("Did not find a review assignment for deposited review with put-code {$putCode}, and external-id-value (reviewRoundId) {$reviewRoundId}.");
                        }
                    }

                    // Remove old put-code from user object
                    $reviewer->setData('orcidReviewPutCode', null);
                    Repo::user()->edit($reviewer, ['orcidReviewPutCode']);
                } catch (ClientException $exception) {
                    OrcidManager::logError("Failed to update put-codes for reviewer {$reviewer->getId()}. Error: {$exception->getMessage()}");
                }
            }
        }
    }
}
