<?php

/**
 * @file classes/subscription/form/SubscriptionTypeForm.inc.php
 *
 * Copyright (c) 2014-2018 Simon Fraser University
 * Copyright (c) 2003-2018 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class SubscriptionTypeForm
 * @ingroup manager_form
 *
 * @brief Form for journal managers to create/edit subscription types.
 */

import('lib.pkp.classes.form.Form');

class SubscriptionTypeForm extends Form {
	/** @var $typeId int the ID of the subscription type being edited */
	var $typeId;

	/** @var $validFormats array keys are valid subscription type formats */
	var $validFormats;

	/** @var $validCurrencies array keys are valid subscription type currencies */
	var $validCurrencies;

	/** @var $journalId int Journal ID */
	var $journalId;

	/**
	 * Constructor
	 * @param $journalId int Journal ID
	 * @param typeId int leave as default for new subscription type
	 */
	function __construct($journalId, $typeId = null) {
		$this->journalId = $journalId;

		import('classes.subscription.SubscriptionType');
		$this->validFormats = array (
			SUBSCRIPTION_TYPE_FORMAT_ONLINE => __('subscriptionTypes.format.online'),
			SUBSCRIPTION_TYPE_FORMAT_PRINT => __('subscriptionTypes.format.print'),
			SUBSCRIPTION_TYPE_FORMAT_PRINT_ONLINE => __('subscriptionTypes.format.printOnline')
		);

		$currencyDao = DAORegistry::getDAO('CurrencyDAO');
		$currencies = $currencyDao->getCurrencies();
		$this->validCurrencies = array();
		while (list(, $currency) = each($currencies)) {
			$this->validCurrencies[$currency->getCodeAlpha()] = $currency->getName() . ' (' . $currency->getCodeAlpha() . ')';
		}

		$this->typeId = isset($typeId) ? (int) $typeId : null;

		parent::__construct('payments/subscriptionTypeForm.tpl');

		// Type name is provided
		$this->addCheck(new FormValidatorLocale($this, 'name', 'required', 'manager.subscriptionTypes.form.typeNameRequired'));

		// Cost	is provided and is numeric and positive
		$this->addCheck(new FormValidator($this, 'cost', 'required', 'manager.subscriptionTypes.form.costRequired'));
		$this->addCheck(new FormValidatorCustom($this, 'cost', 'required', 'manager.subscriptionTypes.form.costNumeric', function($cost) {
			return (is_numeric($cost) && $cost >= 0);
		}));

		// Currency is provided and is valid value
		$this->addCheck(new FormValidator($this, 'currency', 'required', 'manager.subscriptionTypes.form.currencyRequired'));
		$this->addCheck(new FormValidatorInSet($this, 'currency', 'required', 'manager.subscriptionTypes.form.currencyValid', array_keys($this->validCurrencies)));

		// Format is provided and is valid value
		$this->addCheck(new FormValidator($this, 'format', 'required', 'manager.subscriptionTypes.form.formatRequired'));
		$this->addCheck(new FormValidatorInSet($this, 'format', 'required', 'manager.subscriptionTypes.form.formatValid', array_keys($this->validFormats)));

		// Institutional flag is valid value
		$this->addCheck(new FormValidatorInSet($this, 'institutional', 'optional', 'manager.subscriptionTypes.form.institutionalValid', array('0', '1')));

		$this->addCheck(new FormValidatorPost($this));
		$this->addCheck(new FormValidatorCSRF($this));
	}

	/**
	 * Get a list of localized field names for this form
	 * @return array
	 */
	function getLocaleFieldNames() {
		$subscriptionTypeDao = DAORegistry::getDAO('SubscriptionTypeDAO');
		return $subscriptionTypeDao->getLocaleFieldNames();
	}

	/**
	 * Fetch the form.
	 * @param $request PKPRequest
	 */
	function fetch($request) {
		$templateMgr = TemplateManager::getManager($request);
		$templateMgr->assign(array(
			'typeId' =>$this->typeId,
			'validCurrencies' => $this->validCurrencies,
			'validFormats' => $this->validFormats,
		));
		return parent::fetch($request);
	}

	/**
	 * Initialize form data from current subscription type.
	 */
	function initData() {
		if (isset($this->typeId)) {
			$subscriptionTypeDao = DAORegistry::getDAO('SubscriptionTypeDAO');
			$subscriptionType = $subscriptionTypeDao->getById($this->typeId, $this->journalId);

			if ($subscriptionType != null) {
				$this->_data = array(
					'name' => $subscriptionType->getName(null), // Localized
					'description' => $subscriptionType->getDescription(null), // Localized
					'cost' => $subscriptionType->getCost(),
					'currency' => $subscriptionType->getCurrencyCodeAlpha(),
					'duration' => $subscriptionType->getDuration(),
					'format' => $subscriptionType->getFormat(),
					'institutional' => $subscriptionType->getInstitutional(),
					'membership' => $subscriptionType->getMembership(),
					'disable_public_display' => $subscriptionType->getDisablePublicDisplay()
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
		$this->readUserVars(array('name', 'description', 'cost', 'currency', 'duration', 'format', 'institutional', 'membership', 'disable_public_display'));

		$this->addCheck(new FormValidatorCustom($this, 'duration', 'optional', 'manager.subscriptionTypes.form.durationNumeric', function($duration) {
			return (is_numeric($duration) && $duration >= 0);
		}));
	}

	/**
	 * Save subscription type.
	 */
	function execute() {
		$subscriptionTypeDao = DAORegistry::getDAO('SubscriptionTypeDAO');

		if (isset($this->typeId)) {
			$subscriptionType = $subscriptionTypeDao->getById($this->typeId, $this->journalId);
		}

		if (!isset($subscriptionType)) {
			$subscriptionType = $subscriptionTypeDao->newDataObject();
			$subscriptionType->setInstitutional($this->getData('institutional') == null ? 0 : $this->getData('institutional'));
		}

		$request = Application::get()->getRequest();
		$journal = $request->getJournal();
		$subscriptionType->setJournalId($journal->getId());
		$subscriptionType->setName($this->getData('name'), null); // Localized
		$subscriptionType->setDescription($this->getData('description'), null); // Localized
		$subscriptionType->setCost(round($this->getData('cost'), 2));
		$subscriptionType->setCurrencyCodeAlpha($this->getData('currency'));
		$subscriptionType->setDuration(($duration=$this->getData('duration'))?(int) $duration:null);
		$subscriptionType->setFormat($this->getData('format'));
		$subscriptionType->setMembership((int) $this->getData('membership'));
		$subscriptionType->setDisablePublicDisplay((int) $this->getData('disable_public_display'));

		// Update or insert subscription type
		if ($subscriptionType->getId() != null) {
			$subscriptionTypeDao->updateObject($subscriptionType);
		} else {
			$subscriptionType->setSequence(REALLY_BIG_NUMBER);
			$subscriptionTypeDao->insertObject($subscriptionType);

			// Re-order the subscription types so the new one is at the end of the list.
			$subscriptionTypeDao->resequenceSubscriptionTypes($subscriptionType->getJournalId());
		}
	}
}


