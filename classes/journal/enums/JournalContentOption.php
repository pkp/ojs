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

use PKP\context\Context;
use APP\facades\Repo;

enum JournalContentOption: int
{
    case ISSUE_TOC = 1;
    case RECENT_PUBLISHED = 2;
    case CATEGORY_LISTING = 3;

    public static function default(Context $context): array
    {
        $issueExists = Repo::issue()
            ->getCollector()
            ->filterByContextIds([$context->getId()])
            ->getQueryBuilder()
            ->exists();

        return $issueExists ? [static::ISSUE_TOC] : [static::RECENT_PUBLISHED];
    }

    public static function getOptions(Context $context): array
    {
        $options = [];
        $issueExists = Repo::issue()
            ->getCollector()
            ->filterByContextIds([$context->getId()])
            ->getQueryBuilder()
            ->exists();

        if ($issueExists) {
            $options[] = [
                'value' => static::ISSUE_TOC->value,
                'label' => __('manager.setup.journalContentOrganization.option.issue_toc'),
            ];
        }

        return array_merge($options, [
            [
                'value' => static::RECENT_PUBLISHED->value,
                'label' => __('manager.setup.journalContentOrganization.option.recent_published'),
            ],
            [
                'value' => static::CATEGORY_LISTING->value,
                'label' => __('manager.setup.journalContentOrganization.option.category_listing'),
            ]
        ]);
    }
}
