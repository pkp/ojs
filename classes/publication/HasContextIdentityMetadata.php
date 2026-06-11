<?php

/**
 * @file classes/publication/HasContextIdentityMetadata.php
 *
 * Copyright (c) 2026 Simon Fraser University
 * Copyright (c) 2026 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @trait HasContextIdentityMetadata
 *
 * @brief Journal-specific extension of the generic identity resolver: adds ISSN and publisher
 *   getters on top of the shared name/country getters.
 */

namespace APP\publication;

use PKP\context\Context;

trait HasContextIdentityMetadata
{
    use \PKP\publication\HasContextIdentityMetadata;

    /**
     * Get the stamped online ISSN, falling back to the live context value.
     */
    public function getOnlineIssn(Context $context): ?string
    {
        return $this->getData('onlineIssn') ?: $context->getData('onlineIssn');
    }

    /**
     * Get the stamped print ISSN, falling back to the live context value.
     */
    public function getPrintIssn(Context $context): ?string
    {
        return $this->getData('printIssn') ?: $context->getData('printIssn');
    }

    /**
     * Get the stamped publisher, falling back to the live context value.
     */
    public function getPublisher(Context $context): ?string
    {
        return $this->getData('publisher') ?: $context->getData('publisherInstitution');
    }
}
