<?php

/**
 * @defgroup api_v1_userGroups User groups API requests
 */

/**
 * @file api/v1/userGroups/index.php
 *
 * Copyright (c) 2025 Simon Fraser University
 * Copyright (c) 2025 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @ingroup api_v1_userGroups
 *
 * @brief Handle API requests for userGroups.
 */

return new \PKP\handler\APIHandler(new \PKP\API\v1\userGroups\UserGroupController());
