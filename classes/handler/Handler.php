<?php

/**
 * @file classes/handler/Handler.php
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2003-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class Handler
 *
 * @ingroup handler
 *
 * @brief Base request handler application class
 */

namespace APP\handler;

use PKP\handler\PKPHandler;

class Handler extends PKPHandler
{
}

if (!PKP_STRICT_MODE) {
    class_alias('\APP\handler\Handler', '\Handler');
}
