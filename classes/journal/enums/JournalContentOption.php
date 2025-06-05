<?php

/**
 * @file classes/journal/enums/JournalContentOption.php
 *
 * Copyright (c) 2014-2025 Simon Fraser University
 * Copyright (c) 2003-2025 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class JournalContentOption
 *
 * @brief Describes the content options for a journal.
 */

namespace APP\journal\enums;

use APP\core\Application;
use APP\facades\Repo;
use PKP\context\Context;

enum JournalContentOption: int
{
    case ISSUE_TOC = 1;
    case RECENT_PUBLISHED = 2;
    case CATEGORY_LISTING = 3;

    /**
     * Get the default content options for a journal.
     */
    public static function default(?Context $context = null): array
    {
        $context ??= Application::get()->getRequest()->getContext();
        
        if (!$context) {
            return [static::ISSUE_TOC->value];
        }

        return static::getIssueExists($context)
            ? [static::ISSUE_TOC->value]
            : [static::RECENT_PUBLISHED->value];
    }

    /**
     * Get the content options for a journal.
     */
    public static function getOptions(): array
    {
        return [
            [
                'value' => static::ISSUE_TOC->value,
                'label' => __('manager.setup.journalContentOrganization.option.issue_toc'),
            ],
            [
                'value' => static::RECENT_PUBLISHED->value,
                'label' => __('manager.setup.journalContentOrganization.option.recent_published'),
            ],
            [
                'value' => static::CATEGORY_LISTING->value,
                'label' => __('manager.setup.journalContentOrganization.option.category_listing'),
            ],
        ];
    }

    /**
     * Get whether the issue exists for a context.
     */
    protected static function getIssueExists(Context $context): bool
    {
        return Repo::issue()
            ->getCollector()
            ->filterByContextIds([$context->getId()])
            ->getQueryBuilder()
            ->exists();
    }
}
