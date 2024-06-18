<?php

/**
 * @file classes/orcid/OrcidReview.php
 *
 * Copyright (c) 2014-2024 Simon Fraser University
 * Copyright (c) 2000-2024 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class OrcidReview
 *
 * @brief Builds ORCID review object for deposit
 */

namespace APP\orcid;

use APP\core\Application;
use APP\submission\Submission;
use Carbon\Carbon;
use PKP\context\Context;
use PKP\doi\Doi;
use PKP\i18n\LocaleConversion;
use PKP\orcid\OrcidManager;
use PKP\submission\reviewAssignment\ReviewAssignment;

class OrcidReview
{
    private array $data;

    public function __construct(
        private Submission $submission,
        private ReviewAssignment $review,
        private Context $context,
    ) {
        $this->data = $this->build();
    }

    /**
     * Returns ORCID review data as an associative array, ready for deposit.
     */
    public function toArray(): array
    {
        return $this->data;
    }

    /**
     * Builds the internal structure for the ORCID review.
     */
    private function build(): array
    {
        $publicationUrl = Application::get()->getDispatcher()->url(
            Application::get()->getRequest(),
            Application::ROUTE_PAGE,
            $this->context->getPath(),
            'article',
            'view',
            $this->submission->getId(),
            urlLocaleForPage: '',
        );

        $submissionLocale = $this->submission->getData('locale');
        $currentPublication = $this->submission->getCurrentPublication();

        if (empty($this->review->getData('dateCompleted')) || empty($this->context->getData('onlineIssn'))) {
            return [];
        }

        $reviewCompletionDate = Carbon::parse($this->review->getData('dateCompleted'));

        $orcidReview = [
            'reviewer-role' => 'reviewer',
            'review-type' => 'review',
            'review-completion-date' => [
                'year' => [
                    'value' => $reviewCompletionDate->format('Y')
                ],
                'month' => [
                    'value' => $reviewCompletionDate->format('m')
                ],
                'day' => [
                    'value' => $reviewCompletionDate->format('d')
                ]
            ],
            'review-group-id' => 'issn:' . $this->context->getData('onlineIssn'),

            'convening-organization' => [
                'name' => $this->context->getData('publisherInstitution'),
                'address' => [
                    'city' => OrcidManager::getCity($this->context),
                    'country' => OrcidManager::getCountry($this->context),

                ]
            ],
            'review-identifiers' => ['external-id' => [
                [
                    'external-id-type' => 'source-work-id',
                    'external-id-value' => $this->review->getData('reviewRoundId'),
                    'external-id-relationship' => 'part-of']
            ]]
        ];
        if ($this->review->getReviewMethod() == ReviewAssignment::SUBMISSION_REVIEW_METHOD_OPEN) {
            $orcidReview['subject-url'] = ['value' => $publicationUrl];
            $orcidReview['review-url'] = ['value' => $publicationUrl];
            $orcidReview['subject-type'] = 'journal-article';
            $orcidReview['subject-name'] = [
                'title' => ['value' => $this->submission->getCurrentPublication()->getLocalizedData('title') ?? '']
            ];

            if (!empty($currentPublication->getDoi())) {
                /** @var Doi $doiObject */
                $doiObject = $currentPublication->getData('doiObject');
                $externalIds = [
                    'external-id-type' => 'doi',
                    'external-id-value' => $doiObject->getDoi(),
                    'external-id-url' => [
                        'value' => $doiObject->getResolvingUrl(),
                    ],
                    'external-id-relationship' => 'self'

                ];
                $orcidReview['subject-external-identifier'] = $externalIds;
            }
        }

        $allTitles = $currentPublication->getData('title');
        foreach ($allTitles as $locale => $title) {
            if ($locale !== $submissionLocale) {
                $orcidReview['subject-name']['translated-title'] = ['value' => $title, 'language-code' => LocaleConversion::getIso1FromLocale($locale)];
            }
        }

        return $orcidReview;
    }
}
