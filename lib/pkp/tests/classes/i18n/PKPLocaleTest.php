<?php

/**
 * @defgroup tests_classes_i18n I18N Class Test Suite
 */

/**
 * @file tests/classes/i18n/PKPLocaleTest.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2000-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class PKPLocaleTest
 * @ingroup tests_classes_i18n
 * @see PKPLocale
 *
 * @brief Tests for the PKPLocale class.
 */


require_mock_env('env1');

import('lib.pkp.tests.PKPTestCase');
import('lib.pkp.classes.i18n.PKPLocale');

class PKPLocaleTest extends PKPTestCase {
	/**
	 * @covers PKPLocale
	 */
	public function testGetLocaleStylesheet() {
		self::assertNull(AppLocale::getLocaleStyleSheet('en_US'));
		self::assertEquals('pt.css', AppLocale::getLocaleStyleSheet('pt_BR'));
		self::assertNull(AppLocale::getLocaleStyleSheet('xx_XX'));
	}

	/**
	 * @covers PKPLocale
	 */
	public function testIsLocaleComplete() {
		self::assertTrue(AppLocale::isLocaleComplete('en_US'));
		self::assertFalse(AppLocale::isLocaleComplete('pt_BR'));
		self::assertFalse(AppLocale::isLocaleComplete('xx_XX'));
	}

	/**
	 * @covers PKPLocale
	 */
	public function testGetAllLocales() {
		$expectedLocales = array(
			'en_US' => 'English',
			'pt_BR' => 'Portuguese (Brazil)',
			'pt_PT' => 'Portuguese (Portugal)',
			'de_DE' => 'German'
		);
		self::assertEquals($expectedLocales, AppLocale::getAllLocales());
	}

	/**
	 * @covers PKPLocale
	 */
	public function testGet3LetterFrom2LetterIsoLanguage() {
		self::assertEquals('eng', AppLocale::get3LetterFrom2LetterIsoLanguage('en'));
		self::assertEquals('por', AppLocale::get3LetterFrom2LetterIsoLanguage('pt'));
		self::assertNull(AppLocale::get3LetterFrom2LetterIsoLanguage('xx'));
	}

	/**
	 * @covers PKPLocale
	 */
	public function testGet2LetterFrom3LetterIsoLanguage() {
		self::assertEquals('en', AppLocale::get2LetterFrom3LetterIsoLanguage('eng'));
		self::assertEquals('pt', AppLocale::get2LetterFrom3LetterIsoLanguage('por'));
		self::assertNull(AppLocale::get2LetterFrom3LetterIsoLanguage('xxx'));
	}

	/**
	 * @covers PKPLocale
	 */
	public function testGet3LetterIsoFromLocale() {
		self::assertEquals('eng', AppLocale::get3LetterIsoFromLocale('en_US'));
		self::assertEquals('por', AppLocale::get3LetterIsoFromLocale('pt_BR'));
		self::assertEquals('por', AppLocale::get3LetterIsoFromLocale('pt_PT'));
		self::assertNull(AppLocale::get3LetterIsoFromLocale('xx_XX'));
	}

	/**
	 * @covers PKPLocale
	 */
	public function testGetLocaleFrom3LetterIso() {
		// A locale that does not have to be disambiguated.
		self::assertEquals('en_US', AppLocale::getLocaleFrom3LetterIso('eng'));

		// The primary locale will be used if that helps
		// to disambiguate.
		AppLocale::setSupportedLocales(array('en_US' => 'English', 'pt_BR' => 'Portuguese (Brazil)', 'pt_PT' => 'Portuguese (Portugal)'));
		AppLocale::setPrimaryLocale('pt_BR');
		self::assertEquals('pt_BR', AppLocale::getLocaleFrom3LetterIso('por'));
		AppLocale::setPrimaryLocale('pt_PT');
		self::assertEquals('pt_PT', AppLocale::getLocaleFrom3LetterIso('por'));

		// If the primary locale doesn't help then use the first supported locale found.
		AppLocale::setPrimaryLocale('en_US');
		self::assertEquals('pt_BR', AppLocale::getLocaleFrom3LetterIso('por'));
		AppLocale::setSupportedLocales(array('en_US' => 'English', 'pt_PT' => 'Portuguese (Portugal)', 'pt_BR' => 'Portuguese (Brazil)'));
		self::assertEquals('pt_PT', AppLocale::getLocaleFrom3LetterIso('por'));

		// If the locale isn't even in the supported localse then use the first locale found.
		AppLocale::setSupportedLocales(array('en_US' => 'English'));
		self::assertEquals('pt_PT', AppLocale::getLocaleFrom3LetterIso('por'));

		// Unknown language.
		self::assertNull(AppLocale::getLocaleFrom3LetterIso('xxx'));
	}
}
?>
