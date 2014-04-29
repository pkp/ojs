<?php

/**
 * @defgroup pages_donations
 */
 
/**
 * @file pages/donations/index.php
 *
 * Copyright (c) 2013-2014 Simon Fraser University Library
 * Copyright (c) 2003-2014 John Willinsky
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
