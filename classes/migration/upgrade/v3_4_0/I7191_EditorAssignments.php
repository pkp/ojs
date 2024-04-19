<?php

/**
 * @file classes/migration/upgrade/v3_4_0/I7191_EditorAssignments.php
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2000-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class I7191_EditorAssignments
 *
 * @brief Update the subeditor_submission_group table to accommodate new editor assignment settings
 */

namespace APP\migration\upgrade\v3_4_0;

class I7191_EditorAssignments extends \PKP\migration\upgrade\v3_4_0\I7191_EditorAssignments
{
    protected function getSectionTable(): string
    {
        return 'sections';
    }

    protected function getSectionId(): string
    {
        return 'section_id';
    }

    protected function getContextId(): string
    {
        return 'journal_id';
    }
}
