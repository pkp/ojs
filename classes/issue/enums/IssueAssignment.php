<?php

/**
 * @file classes/issue/enums/IssueAssignment.php
 *
 * Copyright (c) 2014-2025 Simon Fraser University
 * Copyright (c) 2003-2025 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class IssueAssignment
 * 
 * @brief Control issue assignment for issue selection form
 */

namespace APP\issue\enums;

use APP\submission\Submission;
use APP\facades\Repo;
use PKP\context\Context;

enum IssueAssignment: int
{
    case NO_ISSUE = 1;
    case FUTURE_ISSUES_PUBLISHED = 2;
    case FUTURE_ISSUE_SCHEDULED = 3;
    case CURRENT_BACK_ISSUES_PUBLISHED = 4;

    /*
     * Get the label for the issue assignment option
     */
    public function getLabel(): string
    {
        return match ($this) {
            static::NO_ISSUE => __('publication.assignToIssue.noIssue'),
            static::FUTURE_ISSUES_PUBLISHED => __('publication.assignToIssue.futureIssuePublish'),
            static::FUTURE_ISSUE_SCHEDULED => __('publication.assignToIssue.futureIssueSchedule'),
            static::CURRENT_BACK_ISSUES_PUBLISHED => __('publication.assignToIssue.currentBackIssue'),
        };
    }

    /*
     * Get the publication status for the issue assignment option
     */
    public function getPublicationStatus(): int
    {
        return match ($this) {
            static::NO_ISSUE => Submission::STATUS_READY_TO_PUBLISH,
            static::FUTURE_ISSUES_PUBLISHED => Submission::STATUS_READY_TO_PUBLISH,
            static::FUTURE_ISSUE_SCHEDULED => Submission::STATUS_READY_TO_SCHEDULE,
            static::CURRENT_BACK_ISSUES_PUBLISHED => Submission::STATUS_READY_TO_PUBLISH,
        };
    }

    /*
     * Get the issue publish status for the issue assignment option
     */
    public function getIssuePublishStatus(): ?int
    {
        return match ($this) {
            static::NO_ISSUE => null,
            static::FUTURE_ISSUES_PUBLISHED => 0,
            static::FUTURE_ISSUE_SCHEDULED => 0,
            static::CURRENT_BACK_ISSUES_PUBLISHED => 1,
        };
    }

    /*
     * Get the valid pre-publish statuses
     */
    public static function getValidPrePublishStatuses(): array
    {
        return [
            Submission::STATUS_READY_TO_PUBLISH,
            Submission::STATUS_READY_TO_SCHEDULE,
        ];
    }

    /*
     * Get all assignment options
     */
    public static function getAllAssignmentOptions(): array
    {
        $options = [];

        foreach (static::cases() as $assignmentOption) {
            $options[] = [
                'value' => $assignmentOption->value,
                'label' => $assignmentOption->getLabel(),
                'status' => $assignmentOption->getPublicationStatus(),
                'isPublished' => $assignmentOption->getIssuePublishStatus(),
            ];
        }

        return $options;
    }

    /*
     * Get the available assignment options for journal depending on available
     * future/current/back issues
     */
    public static function getAvailableAssignmentOption(Context $context): array
    {
        $options = static::getAllAssignmentOptions();

        return collect($options)
            ->map(function ($option) use ($context) {
                if ($option['isPublished'] === null) {
                    return $option;
                }

                $issueExistsQuery = Repo::issue()
                    ->getCollector()
                    ->filterByContextIds([$context->getId()])
                    ->filterByPublished((bool)$option['isPublished'])
                    ->getQueryBuilder()
                    ->exists();

                return $issueExistsQuery ? $option : null;
            })
            ->filter()
            ->toArray();
    }

    /*
     * Get the default assignment for a publication based on the context
     */
    public static function defaultAssignment(Context $context): self
    {
        $issueExists = Repo::issue()
            ->getCollector()
            ->filterByContextIds([$context->getId()])
            ->getQueryBuilder()
            ->exists();
        
        return $issueExists
            ? static::CURRENT_BACK_ISSUES_PUBLISHED
            : static::NO_ISSUE;
    }
}
