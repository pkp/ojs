<?php

/**
 * @defgroup pages_submissions Submissions editorial page
 */

/**
 * @file lib/pkp/pages/dashboard/index.php
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2003-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @ingroup pages_submissions
 *
 * @brief Handle requests for submissions functions.
 *
 */


switch ($op) {
    case 'index':
        return new APP\pages\dashboard\DashboardHandlerNext();
    case 'editorial':
        return new APP\pages\dashboard\DashboardHandlerNext(PKP\pages\dashboard\DashboardPage::EditorialDashboard);
    case 'mySubmissions':
        return new APP\pages\dashboard\DashboardHandlerNext(PKP\pages\dashboard\DashboardPage::MySubmissions);
    case 'reviewAssignments':
        return new APP\pages\dashboard\DashboardHandlerNext(PKP\pages\dashboard\DashboardPage::MyReviewAssignments);
}
