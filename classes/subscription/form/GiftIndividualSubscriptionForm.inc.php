<?php

/**
 * @defgroup subscription_form
 */

/**
 * @file classes/subscription/form/GiftIndividualSubscriptionForm.inc.php
 *
 * Copyright (c) 2013-2014 Simon Fraser University Library
 * Copyright (c) 2003-2014 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class GiftIndividualSubscriptionForm
 * @ingroup subscription_form
 *
 * @brief Form class for purchase of individual subscription gift.
 */

import('lib.pkp.classes.form.Form');

class GiftIndividualSubscriptionForm extends Form {
	/** @var $request PKPRequest */
	var $request;

	/** @var userId int the buyer associated with the gift purchase */
	var $buyerUserId;

	/** @var subscriptionTypes Array subscription types */
	var $subscriptionTypes;

	/**
	 * Constructor
	 * @param buyerUserId int
	 */
	function GiftIndividualSubscriptionForm($request, $buyerUserId = null) {
		parent::Form('subscription/giftIndividualSubscriptionForm.tpl');

		$this->buyerUserId = isset($buyerUserId) ? (int) $buyerUserId : null;
		$this->request =& $request;
		$journal =& $this->request->getJournal();
		$journalId = $journal->getId();

		$subscriptionTypeDao =& DAORegistry::getDAO('SubscriptionTypeDAO');
		$subscriptionTypes =& $subscriptionTypeDao->getSubscriptionTypesByInstitutional($journalId, false, false);
		$this->subscriptionTypes =& $subscriptionTypes->toArray();

		// Require buyer and recipient names and emails
		$this->addCheck(new FormValidator($this, 'buyerFirstName', 'required', 'user.profile.form.firstNameRequired'));
		$this->addCheck(new FormValidator($this, 'buyerLastName', 'required', 'user.profile.form.lastNameRequired'));
		$this->addCheck(new FormValidatorEmail($this, 'buyerEmail', 'required', 'user.profile.form.emailRequired'));
		$this->addCheck(new FormValidatorCustom($this, 'buyerEmail', 'required', 'user.register.form.emailsDoNotMatch', create_function('$buyerEmail,$form', 'return $buyerEmail == $form->getData(\'confirmBuyerEmail\');'), array(&$this)));
		$this->addCheck(new FormValidator($this, 'recipientFirstName', 'required', 'user.profile.form.firstNameRequired'));
		$this->addCheck(new FormValidator($this, 'recipientLastName', 'required', 'user.profile.form.lastNameRequired'));
		$this->addCheck(new FormValidatorEmail($this, 'recipientEmail', 'required', 'user.profile.form.emailRequired'));
		$this->addCheck(new FormValidatorCustom($this, 'recipientEmail', 'required', 'user.register.form.emailsDoNotMatch', create_function('$recipientEmail,$form', 'return $recipientEmail == $form->getData(\'confirmRecipientEmail\');'), array(&$this)));

		// Require gift note title and note from buyer
		$this->addCheck(new FormValidator($this, 'giftNoteTitle', 'required', 'gifts.noteTitleRequired'));
		$this->addCheck(new FormValidator($this, 'giftNote', 'required', 'gifts.noteRequired'));

		// Ensure subscription type is valid
		$this->addCheck(new FormValidatorCustom($this, 'typeId', 'required', 'user.subscriptions.form.typeIdValid', create_function('$typeId, $journalId', '$subscriptionTypeDao =& DAORegistry::getDAO(\'SubscriptionTypeDAO\'); return ($subscriptionTypeDao->subscriptionTypeExistsByTypeId($typeId, $journalId) && $subscriptionTypeDao->getSubscriptionTypeInstitutional($typeId) == 0) && $subscriptionTypeDao->getSubscriptionTypeDisablePublicDisplay($typeId) == 0;'), array($journal->getId())));

		// Ensure a locale is provided and valid
		$this->addCheck(
			new FormValidator(
				$this,
				'giftLocale',
				'required',
				'gifts.localeRequired'
			),
			create_function(
				'$giftLocale, $availableLocales',
				'return in_array($giftLocale, $availableLocales);'
			),
			array_keys($journal->getSupportedLocaleNames())
		);

		// Form was POSTed
		$this->addCheck(new FormValidatorPost($this));
	}

	/**
	 * Display the form.
	 */
	function display() {
		$journal =& $this->request->getJournal();
		$templateMgr =& TemplateManager::getManager();
		$templateMgr->assign('supportedLocales', $journal->getSupportedLocaleNames());
		$templateMgr->assign_by_ref('subscriptionTypes', $this->subscriptionTypes);
		parent::display();
	}

	/**
	 * Assign form data to user-submitted data.
	 */
	function readInputData() {
		$this->readUserVars(array(
			'buyerFirstName',
			'buyerMiddleName',
			'buyerLastName',
			'buyerEmail',
			'confirmBuyerEmail',
			'recipientFirstName',
			'recipientMiddleName',
			'recipientLastName',
			'recipientEmail',
			'confirmRecipientEmail',
			'giftLocale',
			'giftNoteTitle',
			'giftNote',
			'typeId'
		));
	}

	/**
	 * Queue payment and save gift details.
	 */
	function execute() {
		$journal =& $this->request->getJournal();
		$journalId = $journal->getId();

		// Create new gift and save details
		import('classes.gift.Gift');
		import('classes.payment.ojs.OJSPaymentManager');
		$paymentManager = new OJSPaymentManager($this->request);
		$paymentPlugin =& $paymentManager->getPaymentPlugin();

		$gift = new Gift();
		if ($paymentPlugin->getName() == 'ManualPayment') {
			$gift->setStatus(GIFT_STATUS_AWAITING_MANUAL_PAYMENT);
		} else {
			$gift->setStatus(GIFT_STATUS_AWAITING_ONLINE_PAYMENT);
		}

		$gift->setAssocType(ASSOC_TYPE_JOURNAL);
		$gift->setAssocId($journalId);
		$gift->setGiftType(GIFT_TYPE_SUBSCRIPTION);
		$gift->setGiftAssocId($this->getData('typeId'));
		$gift->setBuyerFirstName($this->getData('buyerFirstName'));
		$gift->setBuyerMiddleName($this->getData('buyerMiddleName'));
		$gift->setBuyerLastName($this->getData('buyerLastName'));
		$gift->setBuyerEmail($this->getData('buyerEmail'));
		$gift->setBuyerUserId($this->buyerUserId ? $this->buyerUserId : null);
		$gift->setRecipientFirstName($this->getData('recipientFirstName'));
		$gift->setRecipientMiddleName($this->getData('recipientMiddleName'));
		$gift->setRecipientLastName($this->getData('recipientLastName'));
		$gift->setRecipientEmail($this->getData('recipientEmail'));
		$gift->setRecipientUserId(null);
		$gift->setLocale($this->getData('giftLocale'));
		$gift->setGiftNoteTitle($this->getData('giftNoteTitle'));
		$gift->setGiftNote($this->getData('giftNote'));

		$giftDao =& DAORegistry::getDAO('GiftDAO');
		$giftId = $giftDao->insertObject($gift);

		// Create new queued payment
		$subscriptionTypeDao =& DAORegistry::getDAO('SubscriptionTypeDAO');
		$subscriptionType =& $subscriptionTypeDao->getSubscriptionType($this->getData('typeId'));

		$queuedPayment =& $paymentManager->createQueuedPayment($journalId, PAYMENT_TYPE_GIFT, null, $giftId, $subscriptionType->getCost(), $subscriptionType->getCurrencyCodeAlpha());
		$queuedPaymentId = $paymentManager->queuePayment($queuedPayment);

		$paymentManager->displayPaymentForm($queuedPaymentId, $queuedPayment);
	}
}

?>
