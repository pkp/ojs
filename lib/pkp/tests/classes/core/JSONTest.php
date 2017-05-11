<?php

/**
 * @file tests/classes/core/JSONTest.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2000-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class JSONTest
 * @ingroup tests_classes_core
 * @see JSONMessage
 *
 * @brief Tests for the JSON class.
 */

import('lib.pkp.tests.PKPTestCase');
import('lib.pkp.classes.core.JSONMessage');

class JSONTest extends PKPTestCase {
	/**
	 * @covers JSONMessage
	 */
	public function testGetString() {
		// Create a test object.
		$testObject = new stdClass();
		$testObject->someInt = 5;
		$testObject->someFloat = 5.5;
		$json = new JSONMessage($status = true, $content = 'test content',
				$elementId = '0', $additionalAttributes = array('testObj' => $testObject));
		$json->setEvent('someEvent', array('eventDataKey' => array('item1', 'item2')));

		// Render the JSON message.
		$expectedString = '{"status":true,"content":"test content",'.
				'"elementId":"0","testObj":{"someInt":5,"someFloat":5.5},'.
				'"event":{"name":"someEvent","data":{"eventDataKey":["item1","item2"]}}}';
		self::assertEquals($expectedString, $json->getString());
	}
}
?>
