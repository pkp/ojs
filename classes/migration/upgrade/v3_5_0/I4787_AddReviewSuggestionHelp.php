<?php

/**
 * @file classes/migration/upgrade/v3_5_0/I4787_AddReviewSuggestionHelp.php
 *
 * Copyright (c) 2025 Simon Fraser University
 * Copyright (c) 2025 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class I4787_AddReviewSuggestionHelp.php
 *
 * @brief Add reviewer suggestion related default help text
 *
 */

namespace APP\migration\upgrade\v3_5_0;

class I4787_AddReviewSuggestionHelp extends \PKP\migration\upgrade\v3_5_0\I4787_AddReviewSuggestionHelp
{
    protected string $CONTEXT_TABLE = 'journals';
    protected string $CONTEXT_SETTINGS_TABLE = 'journal_settings';
    protected string $CONTEXT_COLUMN = 'journal_id';
}
