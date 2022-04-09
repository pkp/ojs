<?php

/**
 * @file classes/mail/variables/ContextEmailVariable.inc.php
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2000-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class ContextEmailVariable
 * @ingroup mail_variables
 *
 * @brief Represents journal-specific email template variables
 */

namespace APP\mail\variables;

use PKP\mail\variables\ContextEmailVariable as PKPContextEmailVariable;

class ContextEmailVariable extends PKPContextEmailVariable
{
    public const CONTEXT_NAME = 'journalName';
    public const CONTEXT_URL = 'journalUrl';
    public const CONTEXT_SIGNATURE = 'journalSignature';

    /**
     * @copydoc Variable::descriptions()
     */
    public static function descriptions(): array
    {
        return array_merge(
            parent::descriptions(),
            [
                self::CONTEXT_NAME => __('emailTemplate.variable.context.contextName'),
                self::CONTEXT_URL => __('emailTemplate.variable.context.contextUrl'),
                self::CONTEXT_SIGNATURE => __('emailTemplate.variable.context.contextSignature')
            ]
        );
    }

    /**
     * @copydoc Variable::values()
     */
    public function values(string $locale): array
    {
        return array_merge(
            parent::values($locale),
            [
                self::CONTEXT_NAME => $this->context->getLocalizedData('name', $locale),
                self::CONTEXT_URL => $this->getContextUrl(),
                self::CONTEXT_SIGNATURE => $this->getContextSignature()
            ]
        );
    }
}
