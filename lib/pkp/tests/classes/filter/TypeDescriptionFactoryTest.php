<?php

/**
 * @file tests/classes/filter/TypeDescriptionFactoryTest.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2000-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class TypeDescriptionFactoryTest
 * @ingroup tests_classes_filter
 * @see TypeDescriptionFactory
 *
 * @brief Test class for TypeDescriptionFactory.
 */

import('lib.pkp.tests.PKPTestCase');
import('lib.pkp.classes.filter.TypeDescriptionFactory');
import('lib.pkp.tests.classes.filter.TestClass1');
import('lib.pkp.tests.classes.filter.TestClass2');

class TypeDescriptionFactoryTest extends PKPTestCase {
	/**
	 * @covers TypeDescriptionFactory
	 */
	public function testInstantiateTypeDescription() {
		$typeDescriptionFactory = TypeDescriptionFactory::getInstance();

		// Instantiate a primitive type
		$typeDescription = $typeDescriptionFactory->instantiateTypeDescription('primitive::string');
		self::assertInstanceOf('PrimitiveTypeDescription', $typeDescription);
		self::assertTrue($typeDescription->isCompatible($object = 'some string'));
		self::assertFalse($typeDescription->isCompatible($object = 5));

		// Instantiate a class type
		$typeDescription = $typeDescriptionFactory->instantiateTypeDescription('class::lib.pkp.tests.classes.filter.TestClass1');
		self::assertInstanceOf('ClassTypeDescription', $typeDescription);
		$compatibleObject = new TestClass1();
		$wrongObject = new TestClass2();
		self::assertTrue($typeDescription->isCompatible($compatibleObject));
		self::assertFalse($typeDescription->isCompatible($wrongObject));

		// Test invalid descriptions
		self::assertNull($typeDescriptionFactory->instantiateTypeDescription('string'));
		self::assertNull($typeDescriptionFactory->instantiateTypeDescription('unknown-namespace::xyz'));
	}
}
?>
