<?php

/**
 * @defgroup currency Currency
 * Implements currency data objects for managing lists of currencies for e-commerce.
 */

/**
 * @file classes/currency/Currency.inc.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2000-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class Currency
 * @ingroup currency
 * @see CurrencyDAO
 *
 * @brief Basic class describing a currency.
 *
 */

class Currency extends DataObject {
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
	 * Get the name of the currency.
	 * @return string
	 */
	function getName() {
		return $this->getData('name');
	}

	/**
	 * Set the name of the currency.
	 * @param $name string
	 */
	function setName($name) {
		$this->setData('name', $name);
	}

	/**
	 * Get currency alpha code.
	 * @return string
	 */
	function getCodeAlpha() {
		return $this->getData('codeAlpha');
	}

	/**
	 * Set currency alpha code.
	 * @param $alphaCode string
	 */
	function setCodeAlpha($codeAlpha) {
		$this->setData('codeAlpha', $codeAlpha);
	}

	/**
	 * Get currency numeric code.
	 * @return int
	 */
	function getCodeNumeric() {
		return $this->getData('codeNumeric');
	}

	/**
	 * Set currency numeric code.
	 * @param $codeNumeric string
	 */
	function setCodeNumeric($codeNumeric) {
		$this->setData('codeNumeric', $codeNumeric);
	}

	/**
	 * Format a number per a currency.
	 * @param $amount numeric|null Numeric amount, or null
	 * @return string|null Formatted amount, or null if null was supplied as amount
	 */
	function format($amount) {
		if ($amount === null) return $amount;

		// Some systems (e.g. Windows) do not provide money_format. Convert directly to string in that case.
		if (!function_exists('money_format')) return (string) $amount;
		setlocale(LC_MONETARY, 'en_US.UTF-8');
		return money_format('%n', $amount);
	}
}

?>
