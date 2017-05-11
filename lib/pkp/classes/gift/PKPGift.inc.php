<?php

/**
 * @defgroup gift Gift
 * Implements the representation of a gift.
 */

/**
 * @file classes/gift/PKPGift.inc.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2000-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class PKPGift
 * @ingroup gift
 * @see GiftDAO, PKPGiftDAO
 *
 * @brief Basic class describing a gift.
 */

define('GIFT_STATUS_AWAITING_MANUAL_PAYMENT', 0x01);
define('GIFT_STATUS_AWAITING_ONLINE_PAYMENT', 0x02);
define('GIFT_STATUS_NOT_REDEEMED', 0x03);
define('GIFT_STATUS_REDEEMED', 0x04);
define('GIFT_STATUS_OTHER', 0x10);

class PKPGift extends DataObject {
	/**
	 * Constructor
	 */
	function __construct() {
		parent::__construct();
	}

	//
	// Get/set methods
	//

	/**
	 * Get assoc type for this gift.
	 * @return int
	 */
	function getAssocType() {
		return $this->getData('assocType');
	}

	/**
	 * Set assoc type for this gift.
	 * @param $assocType int
	 */
	function setAssocType($assocType) {
		$this->setData('assocType', $assocType);
	}

	/**
	 * Get assoc ID for this gift.
	 * @return int
	 */
	function getAssocId() {
		return $this->getData('assocId');
	}

	/**
	 * Set assoc ID for this gift.
	 * @param $assocId int
	 */
	function setAssocId($assocId) {
		$this->setData('assocId', $assocId);
	}

	/**
	 * Get the gift status of the gift.
	 * @return int
	 */
	function getStatus() {
		return $this->getData('status');
	}

	/**
	 * Set the gift status of the gift.
	 * @param $status int
	 */
	function setStatus($status) {
		$this->setData('status', $status);
	}

	/**
	 * Get the gift type of the gift.
	 * @return int
	 */
	function getGiftType() {
		return $this->getData('giftType');
	}

	/**
	 * Set the gift type of the gift.
	 * @param $type int
	 */
	function setGiftType($type) {
		$this->setData('giftType', $type);
	}

	/**
	 * Get the name of the gift based on gift type.
	 * @param $locale string
	 * @return string
	 */
	function getGiftName($locale = null) {
		// Must be implemented by sub-classes
		assert(false);
	}

	/**
	 * Get the gift assoc id.
	 * @return in
	 */
	function getGiftAssocId() {
		return $this->getData('giftAssocId');
	}

	/**
	 * Set the gift assoc id.
	 * @param $giftAssocId int
	 */
	function setGiftAssocId($giftAssocId) {
		$this->setData('giftAssocId', $giftAssocId);
	}

	/**
	 * Get the gift buyer first name.
	 * @return string
	 */
	function getBuyerFirstName() {
		return $this->getData('buyerFirstName');
	}

	/**
	 * Set the gift buyer first name.
	 * @param $buyerFirstName string
	 */
	function setBuyerFirstName($buyerFirstName) {
		$this->setData('buyerFirstName', $buyerFirstName);
	}

	/**
	 * Get the gift buyer middle name.
	 * @return string
	 */
	function getBuyerMiddleName() {
		return $this->getData('buyerMiddleName');
	}

	/**
	 * Set the gift buyer middle name.
	 * @param $buyerMiddleName string
	 */
	function setBuyerMiddleName($buyerMiddleName) {
		$this->setData('buyerMiddleName', $buyerMiddleName);
	}

	/**
	 * Get the gift buyer last name.
	 * @return string
	 */
	function getBuyerLastName() {
		return $this->getData('buyerLastName');
	}

	/**
	 * Set the gift buyer last name.
	 * @param $buyerLastName string
	 */
	function setBuyerLastName($buyerLastName) {
		$this->setData('buyerLastName', $buyerLastName);
	}

	/**
	 * Get the buyer's complete name.
	 * Includes first name, middle name (if applicable), and last name.
	 * @param $lastFirst boolean return in "LastName, FirstName" format
	 * @return string
	 */
	function getBuyerFullName($lastFirst = false) {
		$firstName = $this->getData('buyerFirstName');
		$middleName = $this->getData('buyerMiddleName');
		$lastName = $this->getData('buyerLastName');
		if ($lastFirst) {
			return "$lastName, " . $firstName . ($middleName != ''?" $middleName":'');
		} else {
			return "$firstName " . ($middleName != ''?"$middleName ":'') . $lastName;
		}
	}

	/**
	 * Get the gift buyer email.
	 * @return string
	 */
	function getBuyerEmail() {
		return $this->getData('buyerEmail');
	}

	/**
	 * Set the gift buyer email.
	 * @param $buyerEmail string
	 */
	function setBuyerEmail($buyerEmail) {
		$this->setData('buyerEmail', $buyerEmail);
	}

	/**
	 * Get the gift buyer user id .
	 * @return int
	 */
	function getBuyerUserId() {
		return $this->getData('buyerUserId');
	}

	/**
	 * Set the gift buyer user id.
	 * @param $userId int
	 */
	function setBuyerUserId($userId) {
		$this->setData('buyerUserId', $userId);
	}

	/**
	 * Get the gift recipient first name.
	 * @return string
	 */
	function getRecipientFirstName() {
		return $this->getData('recipientFirstName');
	}

	/**
	 * Set the gift recipient first name.
	 * @param $recipientFirstName string
	 */
	function setRecipientFirstName($recipientFirstName) {
		$this->setData('recipientFirstName', $recipientFirstName);
	}

	/**
	 * Get the gift recipient middle name.
	 * @return string
	 */
	function getRecipientMiddleName() {
		return $this->getData('recipientMiddleName');
	}

	/**
	 * Set the gift recipient middle name.
	 * @param $recipientMiddleName string
	 */
	function setRecipientMiddleName($recipientMiddleName) {
		$this->setData('recipientMiddleName', $recipientMiddleName);
	}

	/**
	 * Get the gift recipient last name.
	 * @return string
	 */
	function getRecipientLastName() {
		return $this->getData('recipientLastName');
	}

	/**
	 * Set the gift recipient last name.
	 * @param $recipientLastName string
	 */
	function setRecipientLastName($recipientLastName) {
		$this->setData('recipientLastName', $recipientLastName);
	}

	/**
	 * Get the recipient's complete name.
	 * Includes first name, middle name (if applicable), and last name.
	 * @param $lastFirst boolean return in "LastName, FirstName" format
	 * @return string
	 */
	function getRecipientFullName($lastFirst = false) {
		$firstName = $this->getData('recipientFirstName');
		$middleName = $this->getData('recipientMiddleName');
		$lastName = $this->getData('recipientLastName');
		if ($lastFirst) {
			return "$lastName, " . $firstName . ($middleName != ''?" $middleName":'');
		} else {
			return "$firstName " . ($middleName != ''?"$middleName ":'') . $lastName;
		}
	}

	/**
	 * Get the gift recipient email.
	 * @return string
	 */
	function getRecipientEmail() {
		return $this->getData('recipientEmail');
	}

	/**
	 * Set the gift recipient email.
	 * @param $recipientEmail string
	 */
	function setRecipientEmail($recipientEmail) {
		$this->setData('recipientEmail', $recipientEmail);
	}

	/**
	 * Get the gift recipient user id .
	 * @return int
	 */
	function getRecipientUserId() {
		return $this->getData('recipientUserId');
	}

	/**
	 * Set the gift recipient user id.
	 * @param $userId int
	 */
	function setRecipientUserId($userId) {
		$this->setData('recipientUserId', $userId);
	}

	/**
	 * Get locale.
	 * @return string
	 */
	function getLocale() {
		return $this->getData('locale');
	}

	/**
	 * Set locale.
	 * @param $locale string
	 */
	function setLocale($locale) {
		$this->setData('locale', $locale);
	}

	/**
	 * Get the gift note title from buyer.
	 * @return string
	 */
	function getGiftNoteTitle() {
		return $this->getData('giftNoteTitle');
	}

	/**
	 * Set the gift note title from buyer.
	 * @param $giftNote string
	 */
	function setGiftNoteTitle($giftNoteTitle) {
		$this->setData('giftNoteTitle', $giftNoteTitle);
	}

	/**
	 * Get the gift note from buyer.
	 * @return string
	 */
	function getGiftNote() {
		return $this->getData('giftNote');
	}

	/**
	 * Set the gift note from buyer.
	 * @param $giftNote string
	 */
	function setGiftNote($giftNote) {
		$this->setData('giftNote', $giftNote);
	}

	/**
	 * Get the gift admin notes.
	 * @return string
	 */
	function getNotes() {
		return $this->getData('notes');
	}

	/**
	 * Set the gift admin notes.
	 * @param $notes string
	 */
	function setNotes($notes) {
		$this->setData('notes', $notes);
	}

	/**
	 * Get gift redeemed datetime.
	 * @return datetime (YYYY-MM-DD HH:MM:SS)
	 */
	function getDatetimeRedeemed() {
		return $this->getData('dateRedeemed');
	}

	/**
	 * Set gift redeemed datetime.
	 * @param $datetimeRedeemed datetime (YYYY-MM-DD HH:MM:SS)
	 */
	function setDatetimeRedeemed($datetimeRedeemed) {
		$this->setData('dateRedeemed', $datetimeRedeemed);
	}
}

?>
