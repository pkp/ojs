<?php
/**
 * @file classes/components/form/publication/traits/HasFieldIssueSelection.php
 *
 * Copyright (c) 2025 Simon Fraser University
 * Copyright (c) 2025 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class HasFieldIssueSelection
 *
 * @brief Add issue selection field to the form
 */

namespace APP\components\forms\publication\traits;

use APP\publication\Publication;
use APP\issue\Issue;
use PKP\components\forms\FieldOptions;
use APP\facades\Repo;
use APP\issue\enums\IssueAssignment;
use PKP\components\forms\FieldSelect;
use PKP\context\Context;

trait HasFieldIssueSelection
{
    public function addFieldIssueSelection(
        Publication $publication,
        Context $context
    ): static
    {
        $currentIssueAssignmentStatus = Repo::publication()->getIssueAssignmentStatus($publication, $context);
        $issuesOptions = $currentIssueAssignmentStatus->getIssuePublishStatus() === null
            ? []
            :Repo::issue()
                ->getCollector()
                ->filterByContextIds([$context->getId()])
                ->filterByPublished((bool)$currentIssueAssignmentStatus->getIssuePublishStatus())
                ->getMany()
                ->map(fn (Issue $issue): array => [
                    'label' => htmlspecialchars($issue->getIssueIdentification()),
                    'value' => (int )$issue->getId(),
                ])
                ->filter()
                ->values()
                ->toArray();

        $this
            // ->addGroup([
            //     'label' => __('publication.assignToIssue.label'),
            //     'description' => __('publication.assignToIssue.assignmentTypeDescription'),
            //     'id' => 'issueSelection',
            // ])
            ->addField(new FieldOptions('assignment', [
                // 'groupId' => 'issueSelection',
                'label' => __('publication.assignToIssue.assignmentType'),
                'type' => 'radio',
                'options' => IssueAssignment::getAvailableAssignmentOption($context),
                'value' => $currentIssueAssignmentStatus->value,
                'isRequired' => true,
            ]))
            ->addField(new FieldSelect('issueId', [
                // 'groupId' => 'issueSelection',
                'label' => __('issue.issue'),
                'options' => $issuesOptions,
                'value' => $publication->getData('issueId'),
                'size' => 'large',
                'isRequired' => true,
                'showWhen' => [
                    'assignment',
                    collect(IssueAssignment::getIssueRequiredOptions())
                        ->map(fn (IssueAssignment $option): int => $option->value)
                        ->toArray()
                ],
            ]))
            ->addHiddenField(
                'status',
                in_array(
                    $publication->getData('status'),
                    [Publication::STATUS_PUBLISHED, Publication::STATUS_SCHEDULED]
                ) ? null : $currentIssueAssignmentStatus->getPublicationStatus()

            );
        
        return $this;
    }
}
