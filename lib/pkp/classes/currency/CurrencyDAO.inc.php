<?php

/**
 * @file classes/currency/CurrencyDAO.inc.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2000-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class CurrencyDAO
 * @ingroup currency
 * @see Currency
 *
 * @brief Operations for retrieving and modifying Currency objects.
 *
 */

import('lib.pkp.classes.currency.Currency');

class CurrencyDAO extends DAO {

	/**
	 * Constructor.
	 */
	function __construct() {
		parent::__construct();
	}

	function _getCache() {
		$locale = AppLocale::getLocale();
		$cache =& Registry::get('currencyCache', true, null);
		if ($cache === null) {
			$cacheManager = CacheManager::getManager();
			$cache = $cacheManager->getFileCache(
				'currencies', $locale,
				array($this, '_cacheMiss')
			);
			$cacheTime = $cache->getCacheTime();
			if ($cacheTime !== null && $cacheTime < filemtime($this->getCurrencyFilename($locale))) {
				$cache->flush();
			}
		}

		return $cache;
	}

	function _cacheMiss($cache, $id) {
		$allCurrencies =& Registry::get('allCurrencies', true, null);
		if ($allCurrencies === null) {
			// Add a locale load to the debug notes.
			$notes =& Registry::get('system.debug.notes');
			$filename = $this->getCurrencyFilename(AppLocale::getLocale());
			$notes[] = array('debug.notes.currencyListLoad', array('filename' => $filename));

			// Reload locale registry file
			$xmlDao = new XMLDAO();
			$data = $xmlDao->parseStruct($filename, array('currency'));

			// Build array with ($charKey => array(stuff))
			if (isset($data['currency'])) {
				foreach ($data['currency'] as $currencyData) {
					$allCurrencies[$currencyData['attributes']['code_alpha']] = array(
						$currencyData['attributes']['name'],
						$currencyData['attributes']['code_numeric']
					);
				}
			}
			asort($allCurrencies);
			$cache->setEntireCache($allCurrencies);
		}
		return null;
	}

	/**
	 * Get the filename of the currency database
	 * @param $locale string
	 * @return string
	 */
	function getCurrencyFilename($locale) {
		return "lib/pkp/locale/$locale/currencies.xml";
	}

	/**
	 * Retrieve a currency by alpha currency ID.
	 * @param $currencyId int
	 * @return Currency
	 */
	function getCurrencyByAlphaCode($codeAlpha) {
		$cache = $this->_getCache();
		return $this->_returnCurrencyFromRow($codeAlpha, $cache->get($codeAlpha));
	}

	/**
	 * Retrieve an array of all currencies.
	 * @return array of Currencies
	 */
	function getCurrencies() {
		$cache = $this->_getCache();
		$returner = array();
		foreach ($cache->getContents() as $codeAlpha => $entry) {
			$returner[] = $this->_returnCurrencyFromRow($codeAlpha, $entry);
		}
		return $returner;
	}

	/**
	 * Instantiate and return a new data object.
	 * @return Currency
	 */
	function newDataObject() {
		return new Currency();
	}

	/**
	 * Internal function to return a Currency object from a row.
	 * @param $row array
	 * @return Currency
	 */
	function &_returnCurrencyFromRow($codeAlpha, &$entry) {
		$currency = $this->newDataObject();
		$currency->setCodeAlpha($codeAlpha);
		$currency->setName($entry[0]);
		$currency->setCodeNumeric($entry[1]);

		HookRegistry::call('CurrencyDAO::_returnCurrencyFromRow', array(&$currency, &$codeAlpha, &$entry));

		return $currency;
	}
}

?>
