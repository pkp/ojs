<?php

/**
 * @defgroup pages_about About page
 */

/**
 * @file pages/about/index.php
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2003-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @ingroup pages_about
 *
 * @brief Handle requests for about the journal functions.
 *
 */

switch ($op) {
    case 'subscriptions':
        return new APP\pages\about\AboutHandler();
    default:
        // Fall back on pkp-lib implementation
        return require_once('lib/pkp/pages/about/index.php');
}
