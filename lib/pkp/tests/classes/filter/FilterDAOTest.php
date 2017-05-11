<?php

/**
 * @file tests/classes/filter/FilterDAOTest.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2000-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class FilterDAOTest
 * @ingroup tests_classes_filter
 * @see FilterDAO
 *
 * @brief Test class for FilterDAO.
 */

import('lib.pkp.tests.DatabaseTestCase');
import('lib.pkp.classes.filter.FilterDAO');
import('lib.pkp.classes.filter.FilterGroup');

class FilterDAOTest extends DatabaseTestCase {
	/**
	 * @see DatabaseTestCase::getAffectedTables()
	 */
	protected function getAffectedTables() {
		return array('filters', 'filter_settings', 'filter_groups');
	}

	protected function setUp() {
		parent::setUp();

		// Create a test filter group.
		$someGroup = new FilterGroup();
		$someGroup->setSymbolic('test-filter-group');
		$someGroup->setDisplayName('some.test.filter.group.display');
		$someGroup->setDescription('some.test.filter.group.description');
		$someGroup->setInputType('primitive::string');
		$someGroup->setOutputType('primitive::string');
		$filterGroupDao = DAORegistry::getDAO('FilterGroupDAO'); /* @var $filterGroupDao FilterGroupDAO */
		self::assertTrue(is_integer($filterGroupId = $filterGroupDao->insertObject($someGroup)));
	}

	/**
	 * @covers FilterDAO
	 */
	public function testFilterCrud() {
		$filterDao = DAORegistry::getDAO('FilterDAO'); /* @var $filterDao FilterDAO */

		// Install a test filter object.
		$settings = array('seq' => '1', 'some-key' => 'some-value');
		$testFilter = $filterDao->configureObject('lib.pkp.tests.classes.filter.PersistableTestFilter', 'test-filter-group', $settings, false, 9999);
		self::assertInstanceOf('PersistableFilter', $testFilter);
		$filterId = $testFilter->getId();
		self::assertTrue(is_integer($filterId));

		// Insert filter instance.
		self::assertTrue(is_numeric($filterId));
		self::assertTrue($filterId > 0);

		// Retrieve filter instance by id.
		$filterById = $filterDao->getObjectById($filterId);
		self::assertEquals($testFilter, $filterById);

		// Retrieve filter by group.
		$filtersByGroup = $filterDao->getObjectsByGroup('test-filter-group', 9999);
		self::assertTrue(count($filtersByGroup) == 1);
		$filterByGroup = array_pop($filtersByGroup);
		self::assertEquals($testFilter, $filterByGroup);

		// Retrieve filter by class.
		$filtersByClassFactory = $filterDao->getObjectsByClass('lib.pkp.tests.classes.filter.PersistableTestFilter', 9999);
		self::assertTrue($filtersByClassFactory->getCount() == 1);
		$filterByClass = $filtersByClassFactory->next();
		self::assertEquals($testFilter, $filterByClass);

		// Retrieve filter by group and class.
		$filtersByGroupAndClassFactory = $filterDao->getObjectsByGroupAndClass('test-filter-group', 'lib.pkp.tests.classes.filter.PersistableTestFilter', 9999);
		self::assertTrue($filtersByGroupAndClassFactory->getCount() == 1);
		$filterByGroupAndClass = $filtersByGroupAndClassFactory->next();
		self::assertEquals($testFilter, $filterByGroupAndClass);

		// Update filter instance.
		$testFilter->setData('some-key', 'another value');
		$testFilter->setIsTemplate(true);

		$filterDao->updateObject($testFilter);
		$filterAfterUpdate = $filterDao->getObject($testFilter);
		self::assertEquals($testFilter, $filterAfterUpdate);

		// Delete filter instance.
		$filterDao->deleteObject($testFilter);
		self::assertNull($filterDao->getObjectById($filterId));
	}

	public function testCompositeFilterCrud() {
		$this->markTestSkipped();
		$filterDao = DAORegistry::getDAO('FilterDAO');

		// sub-filter 1
		$subFilter1Settings = array('seq' => 1, 'displayName' => '1st sub-filter');
		$subFilter1 = $filterDao->configureObject('lib.pkp.tests.classes.filter.PersistableTestFilter', 'test-filter-group', $subFilter1Settings, false, 9999, array(), false);

		// sub-sub-filters for sub-filter 2
		$subSubFilter1Settings = array('seq' => 1, 'displayName' => '1st sub-sub-filter');
		$subSubFilter1 = $filterDao->configureObject('lib.pkp.tests.classes.filter.PersistableTestFilter', 'test-filter-group', $subSubFilter1Settings, false, 9999, array(), false);
		$subSubFilter2Settings = array('seq' => 2, 'displayName' => '2nd sub-sub-filter');
		$subSubFilter2 = $filterDao->configureObject('lib.pkp.tests.classes.filter.PersistableTestFilter', 'test-filter-group', $subSubFilter2Settings, false, 9999, array(), false);
		$subSubFilters = array($subSubFilter1, $subSubFilter2);

		// sub-filter 2
		$subFilter2Settings = array('seq' => 2, 'displayName' => '2nd sub-filter');
		$subFilter2 = $filterDao->configureObject('lib.pkp.classes.filter.GenericMultiplexerFilter', 'test-filter-group', $subFilter2Settings, false, 9999, $subSubFilters, false);

		// Instantiate a composite test filter object
		$subFilters = array($subFilter1, $subFilter2);
		$testFilter = $filterDao->configureObject('lib.pkp.classes.filter.GenericSequencerFilter', 'test-filter-group', array('seq' => 1), false, 9999, $subFilters);
		self::assertInstanceOf('GenericSequencerFilter', $testFilter);
		$filterId = $testFilter->getId();
		self::assertTrue(is_numeric($filterId));
		self::assertTrue($filterId > 0);

		// Check that sub-filters were correctly
		// linked to the composite filter.
		$subFilters =& $testFilter->getFilters();
		self::assertEquals(2, count($subFilters));
		foreach($subFilters as $subFilter) {
			self::assertTrue($subFilter->getId() > 0);
			self::assertEquals($filterId, $subFilter->getParentFilterId());
		}
		$subSubFilters =& $subFilters[2]->getFilters();
		self::assertEquals(2, count($subSubFilters));
		foreach($subSubFilters as $subSubFilter) {
			self::assertTrue($subSubFilter->getId() > 0);
			self::assertEquals($subFilters[2]->getId(), $subSubFilter->getParentFilterId());
		}

		// Retrieve filter instance by id
		$filterById = $filterDao->getObjectById($filterId);
		self::assertEquals($testFilter, $filterById);

		// Update filter instance
		$testFilter = new GenericSequencerFilter($testFilter->getFilterGroup());
		$testFilter->setDisplayName('composite filter');
		$testFilter->setSequence(9999);
		$testFilter->setId($filterId);
		$testFilter->setIsTemplate(true);

		// leave out (sub-)sub-filter 2 but add a new (sub-)sub-filter 3
		// to test recursive update.
		$testFilter->addFilter($subFilter1);
		$subFilter3 = new GenericMultiplexerFilter($testFilter->getFilterGroup());
		$subFilter3->setDisplayName('3rd sub-filter');
		$subFilter3->addFilter($subSubFilter1);
		$subSubFilter3 = new PersistableTestFilter($testFilter->getFilterGroup());
		$subSubFilter3->setDisplayName('3rd sub-sub-filter');
		$subFilter3->addFilter($subSubFilter3);
		$testFilter->addFilter($subFilter3);

		$filterDao->updateObject($testFilter);
		$filterAfterUpdate = $filterDao->getObject($testFilter);
		self::assertEquals($testFilter, $filterAfterUpdate);

		// Delete filter instance
		$filterDao->deleteObject($testFilter);
		self::assertNull($filterDao->getObjectById($filterId));
	}
}
?>
