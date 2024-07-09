<?php

/**
 * @file classes/core/Services.php
 *
 * Copyright (c) 2014-2024 Simon Fraser University
 * Copyright (c) 2000-2024 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class Services
 *
 * @brief Pimple Dependency Injection Container.
 *
 * @deprecated 3.5.0 Consider using {@see app()->get('SERVICE_NAME')}
 * @see app()->get('SERVICE_NAME')
 */

namespace APP\core;

class Services extends \PKP\core\PKPServices
{
}

if (!PKP_STRICT_MODE) {
    class_alias('\APP\core\Services', '\Services');
}
