<?php

/**
 * @defgroup pages_announcement Announcements page
 */

/**
 * @file pages/exercise/index.php
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2003-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @ingroup pages_exercise
 *
 * @brief Handle requests for about the journal functions.
 *
 */

use APP\pages\exercise\AnnouncementHandler;

switch ($op) {
    case 'index':
    case 'users':
    case 'announcements':
        return new AnnouncementHandler();
    default:
        // Fall back on pkp-lib implementation
        return require_once('lib/pkp/pages/about/index.php');
}
