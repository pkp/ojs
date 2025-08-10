<?php

/**
 * @file classes/components/form/publication/PublishForm.php
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2000-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class PublishForm
 *
 * @ingroup classes_controllers_form
 *
 * @brief A preset form for confirming a publication's issue before publishing.
 *   It may also be used for scheduling a publication in an issue for later
 *   publication.
 */

namespace APP\components\forms\publication;

use APP\facades\Repo;
use APP\submission\Submission;
use APP\publication\Publication;
use PKP\components\forms\FieldHTML;
use PKP\components\forms\FormComponent;
use PKP\core\Core;
use PKP\core\PKPString;
use PKP\facades\Locale;

class PublishForm extends FormComponent
{
    public const FORM_PUBLISH = 'publish';

    /** @copydoc FormComponent::$id */
    public $id = self::FORM_PUBLISH;

    /** @copydoc FormComponent::$method */
    public $method = 'PUT';

    /** @var \APP\publication\Publication */
    public $publication;

    /** @var \APP\journal\Journal */
    public $submissionContext;

    /**
     * Constructor
     *
     * @param string $action URL to submit the form to
     * @param \APP\publication\Publication $publication The publication to change settings for
     * @param \APP\journal\Journal $submissionContext journal or press
     * @param array $requirementErrors A list of pre-publication requirements that are not met.
     */
    public function __construct($action, $publication, $submissionContext, $requirementErrors)
    {
        $this->action = $action;
        $this->errors = $requirementErrors;
        $this->publication = $publication;
        $this->submissionContext = $submissionContext;

        // Set separate messages and buttons if publication requirements have passed
        if (empty($requirementErrors)) {
            $issue = null;
            $msg = __('publication.publish.confirmation');
            $submitLabel = __('publication.publish');
            
            if ($publication->getData('issueId')) {
                $issue = Repo::issue()->get($publication->getData('issueId'));
                if ($issue) {
                    if ($issue->getData('published')) {
                        $msg = __('publication.publish.confirmation.backIssue', ['issue' => htmlspecialchars($issue->getIssueIdentification())]);
                    } else {
                        $msg = __('publication.publish.confirmation.futureIssue', ['issue' => htmlspecialchars($issue->getIssueIdentification())]);
                        $submitLabel = __('editor.submission.schedulePublication');
                    }
                }
            }

            // If the publication is marked as ready to publish, 
            // it will be published immediately regardless of the issue assignment
            // or to a future issue, it will be published immediately
            if ((int)$publication->getData('status') === Submission::STATUS_READY_TO_PUBLISH) {
                $msg = match($issue?->getData('published')) {
                    true => __('publication.publish.confirmation'),
                    false => __('publication.publish.confirmation.continuousPublication', ['issue' => htmlspecialchars($issue->getIssueIdentification())]),
                    null => __('publication.publish.confirmation.issueLess'),
                };
                $submitLabel = __('publication.publish');
            }
            
            // If a publication date has already been set and the date has passed this will
            // be published immediately regardless of the issue assignment
            if ($publication->getData('datePublished') && $publication->getData('datePublished') <= Core::getCurrentDate()) {
                $dateFormatLong = PKPString::convertStrftimeFormat($submissionContext->getLocalizedDateFormatLong());
                $msg = __(
                    'publication.publish.confirmation.datePublishedInPast',
                    [
                        'datePublished' => (new \Carbon\Carbon($publication->getData('datePublished')))
                            ->locale(Locale::getLocale())
                            ->translatedFormat($dateFormatLong),
                    ]
                );
                $submitLabel = __('publication.publish');
            }

            // If publication does not have a version stage assigned
            $publicationVersion = $publication->getVersion();
            if (!isset($publicationVersion)) {
                $submission = Repo::submission()->get($publication->getData('submissionId'));
                $nextVersion = Repo::submission()->getNextAvailableVersion($submission, Publication::DEFAULT_VERSION_STAGE, false);

                $msg .= '<p>' . __('publication.required.versionStage') . '</p>';
                $msg .= '<p>' . __('publication.required.versionStage.assignment', [
                    'versionString' => $nextVersion
                ]) . '</p>';
            } else {
                $msg .= '<p>' . __('publication.required.versionStage.alreadyAssignment', [
                    'versionString' => $publicationVersion
                ]) . '</p>';
            }

            $this->addPage([
                'id' => 'default',
                'submitButton' => [
                    'label' => $submitLabel,
                ],
            ]);
        } else {
            $msg = '<p>' . __('publication.publish.requirements') . '</p>';
            $msg .= '<ul>';
            foreach ($requirementErrors as $error) {
                $msg .= '<li>' . $error . '</li>';
            }
            $msg .= '</ul>';
            $this->addPage([
                'id' => 'default',
            ]);
        }

        $this
            ->addGroup([
                'id' => 'default',
                'pageId' => 'default',
            ])
            ->addField(new FieldHTML('validation', [
                'description' => $msg,
                'groupId' => 'default',
            ]));
    }
}
