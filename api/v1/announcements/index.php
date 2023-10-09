<?php
/**
 * @defgroup api_v1_announcements Email templates API requests
 */

/**
 * @file api/v1/announcements/index.php
 *
 * Copyright (c) 2023 Simon Fraser University
 * Copyright (c) 2023 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @ingroup api_v1_announcements
 *
 * @brief Handle API requests for announcements.
 */

return new \PKP\handler\APIHandler(new \PKP\API\v1\announcements\PKPAnnouncementController());
