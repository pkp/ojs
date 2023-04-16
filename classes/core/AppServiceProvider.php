<?php

/**
 * @file classes/core/AppServiceProvider.php
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2003-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class AppServiceProvider
 *
 * @ingroup core
 *
 * @brief Resolves requests for application classes such as the request handler
 *   to support dependency injection
 */

namespace APP\core;

use PKP\core\PKPRequest;

class AppServiceProvider extends \PKP\core\AppServiceProvider
{
    /**
     * @copydoc \PKP\core\AppServiceProvider::register()
     */
    public function register()
    {
        parent::register();

        $this->app->bind(Request::class, PKPRequest::class);
    }
}
