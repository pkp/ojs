<?php

/**
 * @file classes/notification/managerDelegate/PublicationNotificationManager.php
 *
 * Copyright (c) 2026 Simon Fraser University
 * Copyright (c) 2026 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class PublicationNotificationManager
 *
 * @ingroup managerDelegate
 *
 * @brief Publication notification types manager delegate.
 */

namespace APP\notification\managerDelegate;

use PKP\context\Context;
use PKP\core\PKPApplication;
use PKP\core\PKPRequest;
use PKP\publication\PKPPublication;

class PublicationNotificationManager extends \PKP\notification\managerDelegate\PublicationNotificationManager
{
    public function getPublicationUrl(PKPRequest $request, Context $context, PKPPublication $publication): string
    {
        $router = $request->getRouter();
        $dispatcher = $router->getDispatcher();

        return $dispatcher->url(
            $request,
            PKPApplication::ROUTE_PAGE,
            $context->getPath(),
            'article',
            'view',
            [$publication->getData('urlPath') ?? $publication->getData('submissionId'), 'version', $publication->getId()]
        );
    }
}
