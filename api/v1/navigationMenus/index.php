<?php

/**
 * @defgroup api_v1_navigationMenus Navigation Menus API requests
 */

/**
 * @file api/v1/navigationMenus/index.php
 *
 * Copyright (c) 2026 Simon Fraser University
 * Copyright (c) 2026 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @ingroup api_v1_navigationMenus
 *
 * @brief Handle API requests for navigation menus.
 */

return new \PKP\handler\APIHandler(new \PKP\API\v1\navigationMenus\PKPNavigationMenuController());
