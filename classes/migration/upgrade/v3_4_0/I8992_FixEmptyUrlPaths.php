<?php

/**
 * @file classes/migration/upgrade/v3_4_0/I8992_FixEmptyUrlPaths.php
 *
 * Copyright (c) 2014-2023 Simon Fraser University
 * Copyright (c) 2000-2023 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class I8992_FixEmptyUrlPaths.php
 *
 * @brief Standardize the url columns to hold NULL instead of NULL/empty string.
 *
 */

namespace APP\migration\upgrade\v3_4_0;

class I8992_FixEmptyUrlPaths extends \PKP\migration\upgrade\v3_4_0\I8992_FixEmptyUrlPaths
{
    /**
     * @copydoc \PKP\migration\upgrade\v3_4_0\I8992_FixEmptyUrlPaths::getFieldset()
     */
    protected function getFieldset(): array
    {
        return array_merge(parent::getFieldset(), [
            ['publication_galleys', 'url_path'],
            ['publication_galleys', 'remote_url'],
            ['issue_galleys', 'url_path'],
            ['issues', 'url_path']
        ]);
    }
}
