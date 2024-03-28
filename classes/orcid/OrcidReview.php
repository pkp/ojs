<?php

namespace APP\orcid;

use APP\core\Application;
use APP\submission\Submission;
use Carbon\Carbon;
use PKP\context\Context;
use PKP\plugins\PluginRegistry;
use PKP\submission\reviewAssignment\ReviewAssignment;

class OrcidReview
{
    private array $data;

    public function __construct(
        private Submission $submission,
        private ReviewAssignment $review,
        private Context $context,
    ) {
        $this->data = $this->buildOrcidReview();
    }

    public function toArray(): array
    {
        return $this->data;
    }

    private function buildOrcidReview(): array
    {
        $publicationUrl = Application::get()->getDispatcher()->url(
            Application::get()->getRequest(),
            Application::ROUTE_PAGE,
            $this->context->getPath(),
            'article',
            'view',
            $this->submission->getId(),
        );

        $publicationLocale = ($this->submission->getData('locale')) ? $this->submission->getData('locale') : 'en';
        // TODO: Check why it shouldn't be removed
        $pubIdPlugins = PluginRegistry::loadCategory('pubIds', true, $this->context->getId()); // DO not remove
        $supportedSubmissionLocales = $this->context->getSupportedSubmissionLocales();

        if (!empty($this->review->getData('dateCompleted')) && $this->context->getData('onlineIssn')) {
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


                if (!empty($this->submission->getData('pub-id::doi'))) {
                    $externalIds = [

                        'external-id-type' => 'doi',
                        'external-id-value' => $this->submission->getData('pub-id::doi'),
                        'external-id-url' => [
                            'value' => 'https://doi.org/' . $this->submission->getData('pub-id::doi')
                        ],
                        'external-id-relationship' => 'self'

                    ];
                    $orcidReview['subject-external-identifier'] = $externalIds;
                }
            }

            $translatedTitleAvailable = false;
            foreach ($supportedSubmissionLocales as $defaultLanguage) {
                if ($defaultLanguage !== $publicationLocale) {
                    $iso2LanguageCode = substr($defaultLanguage, 0, 2);
                    $defaultTitle = $this->submission->getLocalizedData($iso2LanguageCode);
                    if (strlen($defaultTitle) > 0 && !$translatedTitleAvailable) {
                        $orcidReview['subject-name']['translated-title'] = ['value' => $defaultTitle, 'language-code' => $iso2LanguageCode];
                        $translatedTitleAvailable = true;
                    }
                }
            }
            return $orcidReview;
        } else {
            // TODO: Check how this should be handled.
            //      It seems like this should be blocked earlier rather than letting it get to this point.
            return [];
        }
    }
}
