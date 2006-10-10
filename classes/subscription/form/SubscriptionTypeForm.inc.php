<?php

/**
 * SubscriptionTypeForm.inc.php
 *
 * Copyright (c) 2003-2006 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package manager.form
 *
 * Form for journal managers to create/edit subscription types.
 *
 * $Id$
 */

import('form.Form');

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

		$currencyDao = &DAORegistry::getDAO('CurrencyDAO');
		$currencies = &$currencyDao->getCurrencies();
		$this->validCurrencies = array();
		while (list(, $currency) = each($currencies)) {
			$this->validCurrencies[$currency->getCodeAlpha()] = $currency->getName() . ' (' . $currency->getCodeAlpha() . ')';
		}

		$this->typeId = isset($typeId) ? (int) $typeId : null;
		$journal = &Request::getJournal();

		parent::Form('subscription/subscriptionTypeForm.tpl');
	
		// Type name is provided
		$this->addCheck(new FormValidator($this, 'typeName', 'required', 'manager.subscriptionTypes.form.typeNameRequired'));

		// Type name does not already exist for this journal
		if ($this->typeId == null) {
			$this->addCheck(new FormValidatorCustom($this, 'typeName', 'required', 'manager.subscriptionTypes.form.typeNameExists', array(DAORegistry::getDAO('SubscriptionTypeDAO'), 'subscriptionTypeExistsByTypeName'), array($journal->getJournalId()), true));
		} else {
			$this->addCheck(new FormValidatorCustom($this, 'typeName', 'required', 'manager.subscriptionTypes.form.typeNameExists', create_function('$typeName, $journalId, $typeId', '$subscriptionTypeDao = &DAORegistry::getDAO(\'SubscriptionTypeDAO\'); $checkId = $subscriptionTypeDao->getSubscriptionTypeByTypeName($typeName, $journalId); return ($checkId == 0 || $checkId == $typeId) ? true : false;'), array($journal->getJournalId(), $this->typeId)));
		}

		// Cost	is provided and is numeric and positive	
		$this->addCheck(new FormValidator($this, 'cost', 'required', 'manager.subscriptionTypes.form.costRequired'));	
		$this->addCheck(new FormValidatorCustom($this, 'cost', 'required', 'manager.subscriptionTypes.form.costNumeric', create_function('$cost', 'return (is_numeric($cost) && $cost >= 0);')));

		// Currency is provided and is valid value
		$this->addCheck(new FormValidator($this, 'currency', 'required', 'manager.subscriptionTypes.form.currencyRequired'));	
		$this->addCheck(new FormValidatorInSet($this, 'currency', 'required', 'manager.subscriptionTypes.form.currencyValid', array_keys($this->validCurrencies)));

		// Duration is provided and is numeric and positive
		$this->addCheck(new FormValidator($this, 'duration', 'required', 'manager.subscriptionTypes.form.durationRequired'));	
		$this->addCheck(new FormValidatorCustom($this, 'duration', 'required', 'manager.subscriptionTypes.form.durationNumeric', create_function('$duration', 'return (is_numeric($duration) && $duration >= 0);')));

		// Format is provided and is valid value
		$this->addCheck(new FormValidator($this, 'format', 'required', 'manager.subscriptionTypes.form.formatRequired'));	
		$this->addCheck(new FormValidatorInSet($this, 'format', 'required', 'manager.subscriptionTypes.form.formatValid', array_keys($this->validFormats)));

		// Institutional flag is valid value
		$this->addCheck(new FormValidatorInSet($this, 'institutional', 'optional', 'manager.subscriptionTypes.form.institutionalValid', array('1')));

		// Membership flag is valid value
		$this->addCheck(new FormValidatorInSet($this, 'membership', 'optional', 'manager.subscriptionTypes.form.membershipValid', array('1')));

		// Public flag is valid value
		$this->addCheck(new FormValidatorInSet($this, 'public', 'optional', 'manager.subscriptionTypes.form.publicValid', array('1')));
	}
	
	/**
	 * Display the form.
	 */
	function display() {
		$templateMgr = &TemplateManager::getManager();
		$templateMgr->assign('typeId', $this->typeId);
		$templateMgr->assign('validCurrencies', $this->validCurrencies);
		$templateMgr->assign('validFormats', $this->validFormats);
		$templateMgr->assign('helpTopicId', 'journal.managementPages.subscriptions');
	
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
					'currency' => $subscriptionType->getCurrencyCodeAlpha(),
					'duration' => $subscriptionType->getDuration(),
					'format' => $subscriptionType->getFormat(),
					'institutional' => $subscriptionType->getInstitutional(),
					'membership' => $subscriptionType->getMembership(),
					'public' => $subscriptionType->getPublic()
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
		$this->readUserVars(array('typeName', 'description', 'cost', 'currency', 'duration', 'format', 'institutional', 'membership', 'public'));
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
		$subscriptionType->setCurrencyCodeAlpha($this->getData('currency'));
		$subscriptionType->setDuration((int)$this->getData('duration'));
		$subscriptionType->setFormat($this->getData('format'));
		$subscriptionType->setInstitutional($this->getData('institutional') == null ? 0 : $this->getData('institutional'));
		$subscriptionType->setMembership($this->getData('membership') == null ? 0 : $this->getData('membership'));
		$subscriptionType->setPublic($this->getData('public') == null ? 0 : $this->getData('public'));

		// Update or insert subscription type
		if ($subscriptionType->getTypeId() != null) {
			$subscriptionTypeDao->updateSubscriptionType($subscriptionType);
		} else {
			// Kludge: Assume we'll have less than 10,000 subscription types.
			$subscriptionType->setSequence(10000);

			$subscriptionTypeDao->insertSubscriptionType($subscriptionType);

			// Re-order the subscription types so the new one is at the end of the list.
			$subscriptionTypeDao->resequenceSubscriptionTypes($subscriptionType->getJournalId());
		}
	}
	
}

?>
