<?php

/**
 * SubscriptionTypeForm.inc.php
 *
 * Copyright (c) 2003-2004 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package manager.form
 *
 * Form for journal managers to create/edit subscription types.
 *
 * $Id$
 */

class SubscriptionTypeForm extends Form {

	/** @var typeId int the ID of the subscription type being edited */
	var $typeId;

	/** @var validFormats array keys are valid subscription type formats */
	var $validFormats;

	/** @var validCurrencies array keys are valid subscription type currencies */	
	var $validCurrencies;

	/**
	 * Constructor
	 * @param typeId int leave as default for new subscription type
	 */
	function SubscriptionTypeForm($typeId = null) {

		$this->validFormats = array (
			SUBSCRIPTION_TYPE_FORMAT_ONLINE => Locale::translate('manager.subscriptionTypes.format.online'),
			SUBSCRIPTION_TYPE_FORMAT_PRINT => Locale::translate('manager.subscriptionTypes.format.print'),
			SUBSCRIPTION_TYPE_FORMAT_PRINT_ONLINE => Locale::translate('manager.subscriptionTypes.format.printOnline')
		);

		$this->validCurrencies = array (
			SUBSCRIPTION_TYPE_CURRENCY_US => Locale::translate('manager.subscriptionTypes.currency.usLong'),
			SUBSCRIPTION_TYPE_CURRENCY_CANADA => Locale::translate('manager.subscriptionTypes.currency.canadaLong'),
			SUBSCRIPTION_TYPE_CURRENCY_EUROPE => Locale::translate('manager.subscriptionTypes.currency.europeLong')
		);

		$this->typeId = isset($typeId) ? (int) $typeId : null;
		$journal = &Request::getJournal();

		parent::Form('manager/subscription/subscriptionTypeForm.tpl');
	
		// Type name is provided
		$this->addCheck(new FormValidator(&$this, 'typeName', 'required', 'manager.subscriptionTypes.form.typeNameRequired'));

		// Type name does not already exist for this journal
		if ($this->typeId == null) {
			$this->addCheck(new FormValidatorCustom(&$this, 'typeName', 'required', 'manager.subscriptionTypes.form.typeNameExists', array(DAORegistry::getDAO('SubscriptionTypeDAO'), 'subscriptionTypeExistsByTypeName'), array($journal->getJournalId()), true));
		} else {
			$this->addCheck(new FormValidatorCustom(&$this, 'typeName', 'required', 'manager.subscriptionTypes.form.typeNameExists', create_function('$typeName, $journalId, $typeId', '$subscriptionTypeDao = &DAORegistry::getDAO(\'SubscriptionTypeDAO\'); $checkId = $subscriptionTypeDao->getSubscriptionTypeByTypeName($typeName, $journalId); return ($checkId == 0 || $checkId == $typeId) ? true : false;'), array($journal->getJournalId(), $this->typeId)));
		}

		// Cost	is provided and is numeric	
		$this->addCheck(new FormValidator(&$this, 'cost', 'required', 'manager.subscriptionTypes.form.costRequired'));	
		$this->addCheck(new FormValidatorCustom(&$this, 'cost', 'required', 'manager.subscriptionTypes.form.costNumeric', create_function('$cost', 'return is_numeric($cost);')));

		// Currency is provided and is valid value
		$this->addCheck(new FormValidator(&$this, 'currency', 'required', 'manager.subscriptionTypes.form.currencyRequired'));	
		$this->addCheck(new FormValidatorInSet(&$this, 'currency', 'required', 'manager.subscriptionTypes.form.currencyValid', array_keys($this->validCurrencies)));

		// Format is provided and is valid value
		$this->addCheck(new FormValidator(&$this, 'format', 'required', 'manager.subscriptionTypes.form.formatRequired'));	
		$this->addCheck(new FormValidatorInSet(&$this, 'format', 'required', 'manager.subscriptionTypes.form.formatValid', array_keys($this->validFormats)));

		// Institutional flag is valid value
		$this->addCheck(new FormValidatorInSet(&$this, 'institutional', 'optional', 'manager.subscriptionTypes.form.institutionalValid', array('1')));

		// Membership flag is valid value
		$this->addCheck(new FormValidatorInSet(&$this, 'membership', 'optional', 'manager.subscriptionTypes.form.membershipValid', array('1')));

		// Sequence is provided and is numeric	
		$this->addCheck(new FormValidator(&$this, 'seq', 'required', 'manager.subscriptionTypes.form.seqRequired'));	
		$this->addCheck(new FormValidatorCustom(&$this, 'seq', 'required', 'manager.subscriptionTypes.form.seqNumeric', create_function('$seq', 'return is_numeric($seq);')));

	}
	
	/**
	 * Display the form.
	 */
	function display() {
		$templateMgr = &TemplateManager::getManager();
		$templateMgr->assign('typeId', $this->typeId);
		$templateMgr->assign('validCurrencies', $this->validCurrencies);
		$templateMgr->assign('validFormats', $this->validFormats);
	
		parent::display();
	}
	
	/**
	 * Initialize form data from current subscription type.
	 */
	function initData() {
		if (isset($this->typeId)) {
			$subscriptionTypeDao = &DAORegistry::getDAO('SubscriptionTypeDAO');
			$subscriptionType = &$subscriptionTypeDao->getSubscriptionType($this->typeId);
			
			if ($subscriptionType != null) {
				$this->_data = array(
					'typeName' => $subscriptionType->getTypeName(),
					'description' => $subscriptionType->getDescription(),
					'cost' => $subscriptionType->getCost(),
					'currency' => $subscriptionType->getCurrency(),
					'format' => $subscriptionType->getFormat(),
					'institutional' => $subscriptionType->getInstitutional(),
					'membership' => $subscriptionType->getMembership(),
					'seq' => $subscriptionType->getSequence()
				);

			} else {
				$this->typeId = null;
			}
		}
	}
	
	/**
	 * Assign form data to user-submitted data.
	 */
	function readInputData() {
		$this->readUserVars(array('typeName', 'description', 'cost', 'currency', 'format', 'institutional', 'membership', 'seq'));
	}
	
	/**
	 * Save subscription type. 
	 */
	function execute() {
		$subscriptionTypeDao = &DAORegistry::getDAO('SubscriptionTypeDAO');
		$journal = &Request::getJournal();
	
		if (isset($this->typeId)) {
			$subscriptionType = &$subscriptionTypeDao->getSubscriptionType($this->typeId);
		}
		
		if (!isset($subscriptionType)) {
			$subscriptionType = &new SubscriptionType();
		}
		
		$subscriptionType->setJournalId($journal->getJournalId());
		$subscriptionType->setTypeName($this->getData('typeName'));
		$subscriptionType->setDescription($this->getData('description'));
		$subscriptionType->setCost(round($this->getData('cost'), 2));
		$subscriptionType->setCurrency($this->getData('currency'));
		$subscriptionType->setFormat($this->getData('format'));
		$subscriptionType->setInstitutional($this->getData('institutional') == null ? 0 : $this->getData('institutional'));
		$subscriptionType->setMembership($this->getData('membership') == null ? 0 : $this->getData('membership'));
		$subscriptionType->setSequence($this->getData('seq'));

		// Update or insert subscription type
		if ($subscriptionType->getTypeId() != null) {
			$subscriptionTypeDao->updateSubscriptionType($subscriptionType);
		} else {
			$subscriptionTypeDao->insertSubscriptionType($subscriptionType);
		}
	}
	
}

?>
