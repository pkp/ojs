<?php

/**
 * @defgroup pages_gifts
 */

/**
 * @file pages/gifts/index.php
 *
 * Copyright (c) 2013-2014 Simon Fraser University Library
 * Copyright (c) 2003-2014 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @ingroup pages_gifts
 * @brief Handle requests for journal gifts
 *
 *
 */

switch ($op) {
	case 'purchaseGiftSubscription':
	case 'payPurchaseGiftSubscription':
	case 'thankYou':
		define('HANDLER_CLASS', 'GiftsHandler');
		import('pages.gifts.GiftsHandler');
		break;
}

?>
