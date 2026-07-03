<?php

/**
 * @file classes/mail/variables/PublicationEmailVariable.php
 *
 * Copyright (c) 2026 Simon Fraser University
 * Copyright (c) 2026 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class PublicationEmailVariable
 *
 * @ingroup mail_variables
 *
 * @brief Represents variables associated with a publication that can be assigned to a template
 */

namespace APP\mail\variables;

use APP\core\Application;
use APP\facades\Repo;
use PKP\context\Context;

class PublicationEmailVariable extends \PKP\mail\variables\PublicationEmailVariable
{
    protected function getPublishedUrl(Context $context): string
    {
        $submission = Repo::submission()->get($this->publication->getData('submissionId'));
        return Application::get()->getDispatcher()->url(
            Application::get()->getRequest(),
            Application::ROUTE_PAGE,
            $context->getPath(),
            'article',
            'view',
            [$this->publication->getData('urlPath') ?? $this->publication->getData('submissionId'), 'version', $this->publication->getId()]
        );
    }
}
