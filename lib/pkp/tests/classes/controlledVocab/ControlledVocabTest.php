<?php

/**
 * @file tests/classes/db/ControlledVocabTest.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2000-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class ControlledVocabTest
 * @ingroup tests_classes_db
 * @see ControlledVocab
 *
 * @brief Tests for the ControlledVocab class.
 */

import('lib.pkp.tests.DatabaseTestCase');

class ControlledVocabTest extends DatabaseTestCase {
	/**
	 * Test parsing controlled vocab data from an XML descriptor.
	 * @covers ControlledVocabDAO::installXML
	 */
	public function testParseXML() {
		$controlledVocabDao = DAORegistry::getDAO('ControlledVocabDAO');

		// Parse a controlled vocabulary
		$controlledVocabs = $controlledVocabDao->installXML(dirname(__FILE__) . '/controlledVocab.xml');
		$controlledVocab = array_shift($controlledVocabs);
		$this->assertEquals($controlledVocabs, array()); // Should just have been one CV
		$this->assertTrue(is_a($controlledVocab, 'ControlledVocab'));
		$this->assertEquals('TEST_CV', $controlledVocab->getSymbolic());
		$this->assertEquals(
			array_values($controlledVocab->enumerate()),
			array(
				'name_one',
				'name_two',
			)
		);

		// Re-parse the controlled vocabulary
		$controlledVocabsReparsed = $controlledVocabDao->installXML(dirname(__FILE__) . '/controlledVocab.xml');
		$controlledVocabReparsed = array_shift($controlledVocabsReparsed);
		$this->assertEquals($controlledVocabsReparsed, array()); // Should just have been one CV
		$this->assertTrue(is_a($controlledVocabReparsed, 'ControlledVocab'));
		// Ensure that the existing controlled vocabulary was re-used
		$this->assertEquals($controlledVocab->getId(), $controlledVocabReparsed->getId());
	}
}

?>
