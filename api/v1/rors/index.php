<?php

/**
 * @defgroup api_v1_rors Ror API requests
 */

/**
 * @file api/v1/rors/index.php
 *
 * Copyright (c) 2024 Simon Fraser University
 * Copyright (c) 2024 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @ingroup api_v1_rors
 *
 * @brief Handle API requests for rors.
 */

return new \PKP\handler\APIHandler(new \PKP\API\v1\rors\PKPRorController());
