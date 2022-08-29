<?php
/**
 * @file classes/emailTemplate/DAO.php
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2000-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class DAO
 *
 * @brief Read and write email templates to the database.
 */

namespace APP\emailTemplate;

class DAO extends \PKP\emailTemplate\DAO
{
    /**
     * Renames app-specific email template variables during installation
     */
    protected function variablesToRename(): array
    {
        return [
            'contextName' => 'journalName',
            'contextUrl' => 'journalUrl',
            'contextSignature' => 'journalSignature',
        ];
    }
}
