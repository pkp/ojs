<?php

/**
 * @defgroup api_v1_test Test-only API requests
 */

/**
 * @file api/v1/_test/index.php
 *
 * Copyright (c) 2026 Simon Fraser University
 * Copyright (c) 2026 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @ingroup api_v1_test
 *
 * @brief Handler for test-only endpoints. Routes inside are gated by
 *        TestModeGate middleware (APPLICATION_ENV === 'test' + key).
 */

return new \PKP\handler\APIHandler(new \APP\API\v1\_test\BootstrapController());
