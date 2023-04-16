<?php

/**
 * @file classes/mail/variables/SubmissionEmailVariable.php
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2000-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class SubmissionEmailVariable
 *
 * @ingroup mail_variables
 *
 * @brief Represents variables associated with a submission that can be assigned to a template
 */

namespace APP\mail\variables;

use APP\core\Application;
use PKP\context\Context;

class SubmissionEmailVariable extends \PKP\mail\variables\SubmissionEmailVariable
{
    protected function getSubmissionPublishedUrl(Context $context): string
    {
        return Application::get()->getDispatcher()->url(
            Application::get()->getRequest(),
            Application::ROUTE_PAGE,
            $context->getPath(),
            'article',
            'view',
            $this->submission->getBestId()
        );
    }
}
