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
    /**
     * @copydoc Variable::values()
     */
    public function values(string $locale): array
    {
        $values = parent::values($locale);

        // Pass the values into the context signature so variables
        // used in the signature can be rendered.
        $values[static::CONTEXT_SIGNATURE] = $this->getContextSignature($values);

        return $values;
    }
}
