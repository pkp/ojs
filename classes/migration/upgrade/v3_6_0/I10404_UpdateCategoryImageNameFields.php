<?php

/**
 * @file classes/migration/upgrade/v3_6_0/I10404_UpdateCategoryImageNameFields.php
 *
 * Copyright (c) 2025 Simon Fraser University
 * Copyright (c) 2025 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class I10404_UpdateCategoryImageNameFields
 *
 * @brief Migration to update Category image data properties for compatibility with FieldUploadImage component
 */

namespace APP\migration\upgrade\v3_6_0;

class I10404_UpdateCategoryImageNameFields extends \PKP\migration\upgrade\v3_6_0\I10404_UpdateCategoryImageNameFields
{
    public function getContextFolderName(): string
    {
        return 'journals';
    }

    public function getContextTable(): string
    {
        return 'journals';
    }


    protected function getContextIdColumn(): string
    {
        return 'journal_id';
    }
}
