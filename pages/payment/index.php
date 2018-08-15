<?php

/**
 * @defgroup pages_payment Payment Pages
 */
 
/**
 * @file pages/payment/index.php
 *
 * Copyright (c) 2014-2018 Simon Fraser University
 * Copyright (c) 2003-2018 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @ingroup pages_payment
 * @brief Handle requests for interactions between the payment system and external
 * sites/systems.
 */

switch ($op) {
	case 'plugin':
	case 'pay':
		define('HANDLER_CLASS', 'PaymentHandler');
		import('pages.payment.PaymentHandler');
		break;
}


