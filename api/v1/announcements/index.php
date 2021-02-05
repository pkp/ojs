<?php
/**
 * @defgroup api_v1_announcements Email templates API requests
 */

/**
 * @file api/v1/announcements/index.php
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2003-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @ingroup api_v1_announcements
 * @brief Handle API requests for announcements.
 */
import('lib.pkp.api.v1.announcements.PKPAnnouncementHandler');
return new PKPAnnouncementHandler();
