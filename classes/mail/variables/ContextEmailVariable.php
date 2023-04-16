<?php

/**
 * @file classes/mail/variables/ContextEmailVariable.php
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2000-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class ContextEmailVariable
 *
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
    public const CONTEXT_ACRONYM = 'journalAcronym';

    /**
     * @copydoc Variable::descriptions()
     */
    public static function descriptions(): array
    {
        return array_merge(
            parent::descriptions(),
            [
                static::CONTEXT_ACRONYM => __('emailTemplate.variable.context.contextAcronym'),
            ]
        );
    }

    /**
     * @copydoc Variable::values()
     */
    public function values(string $locale): array
    {
        $values = array_merge(
            parent::values($locale),
            [
                static::CONTEXT_ACRONYM => htmlspecialchars($this->context->getLocalizedData('acronym')),
            ],
        );

        // Pass the values into the context signature so variables
        // used in the signature can be rendered.
        $values[static::CONTEXT_SIGNATURE] = $this->getContextSignature($values);

        return $values;
    }
}
