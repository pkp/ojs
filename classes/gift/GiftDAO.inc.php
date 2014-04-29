<?php

/**
 * @file classes/gift/GiftDAO.inc.php
 *
 * Copyright (c) 2013-2014 Simon Fraser University Library
 * Copyright (c) 2003-2014 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class GiftDAO
 * @ingroup gift
 * @see PKPGiftDAO
 *
 * @brief OJS extension of PKPGiftDAO
 */

import('lib.pkp.classes.gift.PKPGiftDAO');
import('classes.gift.Gift');

define('GIFT_REDEEM_STATUS_ERROR_SUBSCRIPTION_TYPE_INVALID', 0x10);
define('GIFT_REDEEM_STATUS_ERROR_SUBSCRIPTION_NON_EXPIRING', 0x11);


class GiftDAO extends PKPGiftDAO {
	/**
	 * Constructor
	 */
	function GiftDAO() {
		parent::PKPGiftDAO();
	}

	/**
	 * Construct a new data object corresponding to this DAO.
	 * @return Gift
	 */
	function newDataObject() {
		return new Gift();
	}

	/**
	 * Redeem a gift for a user.
	 * @param $assocType int
	 * @param $assocId int
	 * @param $userId int
	 * @param $giftId int
	 * @return int Status code indicating whether gift could be redeemed
	 */
	function redeemGift($assocType, $assocId, $userId, $giftId) {
		// Ensure user has this gift
		if (!$this->recipientHasGift($assocType, $assocId, $userId, $giftId)) {
			return GIFT_REDEEM_STATUS_ERROR_NO_GIFT_TO_REDEEM;
		}

		// Ensure user has not already redeemed this gift
		if (!$this->recipientHasNotRedeemedGift($assocType, $assocId, $userId, $giftId)) {
			return GIFT_REDEEM_STATUS_ERROR_GIFT_ALREADY_REDEEMED;
		}

		// Retrieve and try to redeem the gift
		$gift =& $this->getGift($giftId);
		$returner = GIFT_REDEEM_STATUS_SUCCESS;

		switch ($gift->getGiftType()) {
			case GIFT_TYPE_SUBSCRIPTION:
				$returner = $this->_redeemGiftSubscription($gift);
				break;
			default:
				$returner = GIFT_REDEEM_STATUS_ERROR_GIFT_INVALID;
		}

		// If all went well, mark gift as redeemed
		if ($returner == GIFT_REDEEM_STATUS_SUCCESS) {
			$gift->setStatus(GIFT_STATUS_REDEEMED);
			$gift->setDatetimeRedeemed(Core::getCurrentDate());
			$this->updateObject($gift);	
		}

		return $returner;
	}

	/**
	 * Redeem a gift subscription for a user.
	 * @param $gift Gift
	 * @return int Status code indicating whether gift subscription could be redeemed
	 */
	function _redeemGiftSubscription(&$gift) {
		$journalId = $gift->getAssocId();
		$userId = $gift->getRecipientUserId();
		$giftSubscriptionTypeId = $gift->getGiftAssocId();

		// Ensure subscription type exists and is for an individual subscription
		$subscriptionTypeDao =& DAORegistry::getDAO('SubscriptionTypeDAO');
		$giftSubscriptionType =& $subscriptionTypeDao->getSubscriptionType($giftSubscriptionTypeId);

		if ($giftSubscriptionType) {
			if ($giftSubscriptionType->getInstitutional()) {
				// Subscription type is not for individuals
				return GIFT_REDEEM_STATUS_ERROR_SUBSCRIPTION_TYPE_INVALID;
			}
		} else {
			// Subscription type no longer exists
			return GIFT_REDEEM_STATUS_ERROR_SUBSCRIPTION_TYPE_INVALID;
		}

		$individualSubscriptionDao =& DAORegistry::getDAO('IndividualSubscriptionDAO');
		$giftNonExpiring = $giftSubscriptionType->getNonExpiring();
		$insert = false;

		// Retrieve user's subscription if they already have one
		if ($individualSubscriptionDao->subscriptionExistsByUserForJournal($userId, $journalId)) {
			$subscription =& $individualSubscriptionDao->getSubscriptionByUserForJournal($userId, $journalId);

			// Ensure user's existing subscription is not non-expiring
			if ($subscription->isNonExpiring()) {
				return GIFT_REDEEM_STATUS_ERROR_SUBSCRIPTION_NON_EXPIRING;
			}
		} else {
			// Otherwise, create a new individual subscription for user
			import('classes.subscription.IndividualSubscription');
			$subscription = new IndividualSubscription();

			$subscription->setJournalId($journalId);
			$subscription->setUserId($userId);
			$subscription->setMembership(null);
			$subscription->setReferenceNumber(null);
			$subscription->setNotes(null);
			$insert = true;	
		}

		// Update subscription status and type
		$subscription->setStatus(SUBSCRIPTION_STATUS_ACTIVE);
		$subscription->setTypeId($giftSubscriptionTypeId);

		// Update subscription dates
		if ($giftNonExpiring) {
			$subscription->setDateStart(null);
			$subscription->setDateEnd(null);
		} else {
			// Set subscription start/end dates based on gift redemption date and duration of subscription type
			$time = time();
			$duration = $giftSubscriptionType->getDuration();
			$subscription->setDateStart(mktime(23, 59, 59, date("m", $time), date("d", $time), date("Y", $time)));
			$subscription->setDateEnd(mktime(23, 59, 59, date("m", $time)+$duration, date("d", $time), date("Y", $time)));
		}

		if ($insert) {
			$individualSubscriptionDao->insertSubscription($subscription);
		} else {
			$individualSubscriptionDao->updateSubscription($subscription);
		}

		return GIFT_REDEEM_STATUS_SUCCESS;
	}
}

?>
