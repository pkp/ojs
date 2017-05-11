<?php

/**
 * @file tests/classes/core/DataObjectTest.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2000-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class DataObjectTest
 * @ingroup tests_classes_core
 * @see DataObject
 *
 * @brief Tests for the DataObject class.
 */

import('lib.pkp.tests.PKPTestCase');
import('lib.pkp.classes.core.DataObject');

class DataObjectTest extends PKPTestCase {
	/** @var DataObject */
	protected $dataObject;

	protected function setUp() {
		parent::setUp();
		$this->dataObject = new DataObject();
	}

	/**
	 * @covers DataObject::setData
	 * @covers DataObject::getData
	 * @covers DataObject::getAllData
	 */
	public function testSetGetData() {
		// Set data with and without locale
		$this->dataObject->setData('testVar1', 'testVal1');
		$this->dataObject->setData('testVar2', 'testVal2_US', 'en_US');
		$this->dataObject->setData('testVar2', 'testVal2_DE', 'de_DE');
		$expectedResult = array(
			'testVar1' => 'testVal1',
			'testVar2' => array(
				'en_US' => 'testVal2_US',
				'de_DE' => 'testVal2_DE'
			)
		);
		self::assertEquals($expectedResult, $this->dataObject->getAllData());
		self::assertEquals('testVal1', $this->dataObject->getData('testVar1'));
		// test for http://bugs.php.net/bug.php?id=29848
		self::assertNull($this->dataObject->getData('testVar1', 'en_US'));
		self::assertEquals('testVal2_US', $this->dataObject->getData('testVar2', 'en_US'));

		// Unset a few values
		$this->dataObject->setData('testVar1', null);
		$this->dataObject->setData('testVar2', null, 'en_US');
		$expectedResult = array(
			'testVar2' => array(
				'de_DE' => 'testVal2_DE'
			)
		);
		self::assertEquals($expectedResult, $this->dataObject->getAllData());

		// Make sure that un-setting a non-existent value doesn't hurt
		$this->dataObject->setData('testVar1', null);
		$this->dataObject->setData('testVar2', null, 'en_US');
		self::assertEquals($expectedResult, $this->dataObject->getAllData());

		// Make sure that getting a non-existent value doesn't hurt
		self::assertNull($this->dataObject->getData('testVar1'));
		self::assertNull($this->dataObject->getData('testVar1', 'en_US'));
		self::assertNull($this->dataObject->getData('testVar2', 'en_US'));

		// Make sure that unsetting the last translation will also kill the variable
		$this->dataObject->setData('testVar2', null, 'de_DE');
		self::assertEquals(array(), $this->dataObject->getAllData());

		// Test by-ref behaviour
		$testVal1 = 'testVal1';
		$testVal2 = 'testVal2';
		$this->dataObject->setData('testVar1', $testVal1);
		$this->dataObject->setData('testVar2', $testVal2, 'en_US');
		$testVal1 = $testVal2 = 'something else';
		$expectedResult = array(
			'testVar1' => 'testVal1',
			'testVar2' => array(
				'en_US' => 'testVal2'
			)
		);
		$result =& $this->dataObject->getAllData();
		self::assertEquals($expectedResult, $result);

		// Should be returned by-ref:
		$testVal1 =& $this->dataObject->getData('testVar1');
		$testVal2 =& $this->dataObject->getData('testVar2', 'en_US');
		$testVal1 = $testVal2 = 'something else';
		$expectedResult = array(
			'testVar1' => 'something else',
			'testVar2' => array(
				'en_US' => 'something else'
			)
		);
		$result =& $this->dataObject->getAllData();
		self::assertEquals($expectedResult, $result);
	}

	/**
	 * @covers DataObject::setAllData
	 */
	public function testSetAllData() {
		$expectedResult = array('someKey' => 'someVal');
		$this->dataObject->setAllData($expectedResult);
		$result =& $this->dataObject->getAllData();
		self::assertEquals($expectedResult, $result);

		// Test by-ref
		$expectedResult = array('someOtherKey' => 'someOtherVal');
		self::assertEquals($expectedResult, $result);
	}

	/**
	 * @covers DataObject::hasData
	 */
	public function testHasData() {
		$testData = array(
			'testVar1' => 'testVal1',
			'testVar2' => array(
				'en_US' => 'testVal2'
			)
		);
		$this->dataObject->setAllData($testData);
		self::assertTrue($this->dataObject->hasData('testVar1'));
		self::assertTrue($this->dataObject->hasData('testVar2'));
		self::assertTrue($this->dataObject->hasData('testVar2', 'en_US'));
		self::assertFalse($this->dataObject->hasData('testVar1', 'en_US'));
		self::assertFalse($this->dataObject->hasData('testVar2', 'de_DE'));
		self::assertFalse($this->dataObject->hasData('testVar3'));
		self::assertFalse($this->dataObject->hasData('testVar3', 'en_US'));
	}
}
?>
