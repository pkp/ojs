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
 *   getters on top of the shared name and abbreviation getters.
 */

namespace APP\publication;

use PKP\context\Context;

trait HasContextIdentityMetadata
{
    use \PKP\publication\HasContextIdentityMetadata;

    /**
     * Get the stamped online ISSN, or the live context value if no identity has been stamped.
     */
    public function getOnlineIssn(Context $context): ?string
    {
        return $this->hasContextIdentity() ? ($this->getData('onlineIssn') ?: null) : $context->getData('onlineIssn');
    }

    /**
     * Get the stamped print ISSN, or the live context value if no identity has been stamped.
     */
    public function getPrintIssn(Context $context): ?string
    {
        return $this->hasContextIdentity() ? ($this->getData('printIssn') ?: null) : $context->getData('printIssn');
    }

    /**
     * Get the stamped publisher, or the live context value if no identity has been stamped.
     */
    public function getPublisher(Context $context): ?string
    {
        return $this->hasContextIdentity() ? ($this->getData('publisher') ?: null) : $context->getData('publisherInstitution');
    }
}
