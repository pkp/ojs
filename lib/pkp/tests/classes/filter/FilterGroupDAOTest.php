<?php

/**
 * @file tests/classes/filter/FilterGroupDAOTest.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2000-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class FilterGroupDAOTest
 * @ingroup tests_classes_filter
 * @see FilterGroupDAO
 *
 * @brief Test class for FilterGroupDAO.
 */

import('lib.pkp.tests.DatabaseTestCase');
import('lib.pkp.classes.filter.FilterGroup');
import('lib.pkp.classes.filter.FilterGroupDAO');

class FilterGroupDAOTest extends DatabaseTestCase {
	/**
	 * @covers FilterGroupDAO
	 */
	public function testFilterGroupCrud() {
		$filterGroupDao = DAORegistry::getDAO('FilterGroupDAO'); /* @var $filterGroupDao FilterGroupDAO */

		// Instantiate a test filter group object.
		$testFilterGroup = new FilterGroup();
		$testFilterGroup->setSymbolic('some-symbol');
		$testFilterGroup->setDisplayName('translation.key.displayName');
		$testFilterGroup->setDescription('translation.key.description');
		$testFilterGroup->setInputType('primitive::string');
		$testFilterGroup->setOutputType('primitive::integer');

		// Insert filter group instance.
		$filterGroupId = $filterGroupDao->insertObject($testFilterGroup, 9999);
		self::assertTrue(is_numeric($filterGroupId));
		self::assertTrue($filterGroupId > 0);

		// Retrieve filter group instance by id.
		$filterGroupById =& $filterGroupDao->getObjectById($filterGroupId);
		self::assertEquals($testFilterGroup, $filterGroupById);

		// Update filter group instance.
		$testFilterGroup->setSymbolic('some-other-symbol');
		$testFilterGroup->setDisplayName('translation.key.otherDisplayName');
		$testFilterGroup->setDescription('translation.key.otherDescription');
		$testFilterGroup->setInputType('primitive::integer');
		$testFilterGroup->setOutputType('primitive::string');

		$filterGroupDao->updateObject($testFilterGroup);
		$filterGroupAfterUpdate =& $filterGroupDao->getObject($testFilterGroup);
		self::assertEquals($testFilterGroup, $filterGroupAfterUpdate);

		// Retrieve filter group instance by symbolic name.
		$filterGroupBySymbolic =& $filterGroupDao->getObjectBySymbolic('some-other-symbol');
		self::assertEquals($testFilterGroup, $filterGroupAfterUpdate);

		// Delete filter group instance.
		$filterGroupDao->deleteObjectById($filterGroupId);
		self::assertNull($filterGroupDao->getObjectById($filterGroupId));
	}
}
?>
