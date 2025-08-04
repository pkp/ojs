<?php
/**
 * @file classes/components/form/publication/AssignToIssueForm.php
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2000-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class AssignToIssueForm
 *
 * @ingroup classes_controllers_form
 *
 * @brief A preset form for setting a publication's issue.
 */

namespace APP\components\forms\publication;

use APP\facades\Repo;
use APP\submission\Submission;
use APP\issue\enums\IssueSelection;
use PKP\components\forms\FieldOptions;
use PKP\components\forms\FieldSelect;
use PKP\components\forms\FormComponent;

define('FORM_ASSIGN_TO_ISSUE', 'assignToIssue');

class AssignToIssueForm extends FormComponent
{
    public const FORM_ASSIGN_TO_ISSUE = 'assignToIssue';
    public $id = self::FORM_ASSIGN_TO_ISSUE;
    public $method = 'PUT';

    /**
     * Constructor
     *
     * @param string $action URL to submit the form to
     * @param \APP\publication\Publication $publication The publication to change settings for
     * @param \APP\journal\Journal $publicationContext The context of the publication
     */
    public function __construct($action, $publication, $publicationContext)
    {
        $this->action = $action;

        $issueOptions = [
            [
                'value' => IssueSelection::NO_ISSUE->value,
                'label' => IssueSelection::NO_ISSUE->getLabel()
            ]
        ];

        $unpublishedIssueIds = [];
        $unpublishedIssues = Repo::issue()->getCollector()
            ->filterByContextIds([$publicationContext->getId()])
            ->filterByPublished(false)
            ->getMany();

        if ($unpublishedIssues->count() > 0) {
            $issueOptions[] = [
                'value' => IssueSelection::FUTURE_ISSUES->value,
                'label' => IssueSelection::FUTURE_ISSUES->getLabel()
            ];

            foreach ($unpublishedIssues as $issue) {
                $unpublishedIssueIds[] = (int) $issue->getId();
                $issueOptions[] = [
                    'value' => (int) $issue->getId(),
                    'label' => htmlspecialchars($issue->getIssueIdentification()),
                ];
            }
        }

        $publishedIssues = Repo::issue()->getCollector()
            ->filterByContextIds([$publicationContext->getId()])
            ->filterByPublished(true)
            ->getMany();

        if ($publishedIssues->count() > 0) {
            $issueOptions[] = [
                'value' => IssueSelection::BACK_ISSUES->value,
                'label' => IssueSelection::BACK_ISSUES->getLabel()
            ];

            foreach ($publishedIssues as $issue) {
                $issueOptions[] = [
                    'value' => (int) $issue->getId(),
                    'label' => htmlspecialchars($issue->getIssueIdentification()),
                ];
            }
        }

        $this
            ->addField(new FieldSelect('issueId', [
                'label' => __('issue.issue'),
                'options' => $issueOptions,
                'value' => $publication->getData('issueId') ?? IssueSelection::NO_ISSUE->value
            ]))
            ->addField(new FieldOptions('continuousPublication', [
                'label' => __('manager.setup.continuousPublication'),
                'description' => __('publication.publish.continuousPublication.description'),
                'options' => [
                    [
                        'value' => true,
                        'label' => __('publication.publish.continuousPublication.label'),
                    ],
                ],
                'value' => (int)$publication->getData('status') === Submission::STATUS_READY_TO_PUBLISH,
                'showWhen' => ['issueId', array_merge($unpublishedIssueIds, [IssueSelection::NO_ISSUE->value])],
            ]));        
    }
}
