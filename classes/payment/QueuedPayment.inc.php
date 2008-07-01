<?php

/**
 * @file classes/payment/QueuedPayment.inc.php
 *
 * Copyright (c) 2003-2008 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class QueuedPayment
 * @ingroup payment
 * @see QueuedPaymentDAO
 *
 * @brief Queued (unfulfilled) payment data structure
 *
 */

import('payment.Payment');

class QueuedPayment extends Payment{

	function QueuedPayment($amount, $currencyCode, $userId = null, $assocId = null) {
		parent::Payment($amount, $currencyCode, $userId, $assocId);
	}
	
	function setQueuedPaymentId($queuedPaymentId) {
		parent::setPaymentId($queuedPaymentId);
	}
	
	function getQueuedPaymentId() {
		return parent::getPaymentId();
	}
}

?>
