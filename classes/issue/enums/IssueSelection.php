<?php

/**
 * @file classes/issue/enums/IssueSelection.php
 *
 * Copyright (c) 2014-2025 Simon Fraser University
 * Copyright (c) 2003-2025 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class IssueSelection
 * 
 * @brief provide category options for issue selection
 */

namespace APP\issue\enums;

enum IssueSelection: int
{
    case NO_ISSUE = 0;
    case FUTURE_ISSUES = -1;
    case CURRENT_ISSUE = -2;
    case BACK_ISSUES = -3;

    /*
     * Get the label for the issue selection option
     */
    public function getLabel(): string
    {
        return match ($this) {
            static::NO_ISSUE => '',
            static::FUTURE_ISSUES => '------    ' . __('editor.issues.futureIssues') . '    ------',
            static::CURRENT_ISSUE => '------    ' . __('editor.issues.currentIssue') . '    ------',
            static::BACK_ISSUES => '------    ' . __('editor.issues.backIssues') . '    ------',
        };
    }

    /*
     * Get the unselectable options
     */ 
    public static function unselectableOptionsValue(): array
    {
        return [
            static::FUTURE_ISSUES->value,
            static::CURRENT_ISSUE->value,
            static::BACK_ISSUES->value,
        ];
    }
}
