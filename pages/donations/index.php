<?php

/**
 * @defgroup pages_donations "Donations" page
 * Implements the donation page, which can be used to contribute to the journal.
 */
 
/**
 * @file pages/donations/index.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2003-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @ingroup pages_donations
 * @brief Handle requests for journal donations 
 *
 *
 */

switch ($op) {
	case 'index':
	case 'thankYou':
		define('HANDLER_CLASS', 'DonationsHandler');
		import('pages.donations.DonationsHandler');
		break;
}

?>
