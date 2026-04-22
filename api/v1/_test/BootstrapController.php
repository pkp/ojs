<?php

/**
 * @file api/v1/_test/BootstrapController.php
 *
 * Copyright (c) 2026 Simon Fraser University
 * Copyright (c) 2026 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class BootstrapController
 *
 * @ingroup api_v1_test
 *
 * @brief OJS-specific subclass for the test bootstrap endpoint. Inherits
 *        the shared PKP implementation — OJS-only baseline pieces (if any)
 *        will be added here as processors.
 */

namespace APP\API\v1\_test;

use PKP\API\v1\_test\PKPBootstrapController;

class BootstrapController extends PKPBootstrapController
{
    // OJS currently uses the shared implementation as-is. Override
    // getGroupRoutes() or bootstrap() here to wire in OJS-only processors.
}
