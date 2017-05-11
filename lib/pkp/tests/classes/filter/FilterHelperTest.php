<?php

/**
 * @file tests/classes/filter/FilterHelperTest.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2000-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class FilterHelperTest
 * @ingroup tests_classes_filter
 * @see FilterHelper
 *
 * @brief Test class for FilterHelper.
 */

import('lib.pkp.tests.PKPTestCase');
import('lib.pkp.classes.filter.FilterHelper');

import('lib.pkp.classes.filter.CompositeFilter');
class OtherCompositeFilter extends CompositeFilter {
	// A test class.
}

class FilterHelperTest extends PKPTestCase {
	/**
	 * @covers FilterHelper
	 */
	public function testCompareFilters() {
		$filterHelper = new FilterHelper();

		import('lib.pkp.classes.filter.FilterGroup');
		$someGroup = new FilterGroup();
		$someGroup->setInputType('primitive::string');
		$someGroup->setOutputType('primitive::string');

		import('lib.pkp.classes.filter.PersistableFilter');
		$filterA = new PersistableFilter($someGroup);
		$filterBSettings = array('some-key' => 'some-value');
		$filterBSubfilters = array();
		self::assertFalse($filterHelper->compareFilters($filterA, $filterBSettings, $filterBSubfilters));

		import('lib.pkp.classes.filter.FilterSetting');
		$filterA->addSetting(new FilterSetting('some-key', null, null));
		self::assertFalse($filterHelper->compareFilters($filterA, $filterBSettings, $filterBSubfilters));

		$filterA->setData('some-key', 'some-value');
		self::assertTrue($filterHelper->compareFilters($filterA, $filterBSettings, $filterBSubfilters));

		$filterA = new CompositeFilter($someGroup);
		$filterBSettings = array();
		$filterBSubfilter = new CompositeFilter($someGroup);
		$filterBSubfilter->setSequence(1);
		$filterBSubfilters = array($filterBSubfilter);
		self::assertFalse($filterHelper->compareFilters($filterA, $filterBSettings, $filterBSubfilters));

		$filterASubfilter = new OtherCompositeFilter($someGroup);
		$filterA->addFilter($filterASubfilter);
		self::assertFalse($filterHelper->compareFilters($filterA, $filterBSettings, $filterBSubfilters));

		$filterA = new CompositeFilter($someGroup);
		$filterASubfilter = new CompositeFilter($someGroup);
		$filterA->addFilter($filterASubfilter);
		self::assertTrue($filterHelper->compareFilters($filterA, $filterBSettings, $filterBSubfilters));

		$filterBSubfilter->addSetting(new FilterSetting('some-key', null, null));
		$filterASubfilter->addSetting(new FilterSetting('some-key', null, null));
		$filterBSubfilter->setData('some-key', 'some-value');
		self::assertFalse($filterHelper->compareFilters($filterA, $filterBSettings, $filterBSubfilters));

		$filterASubfilter->setData('some-key', 'some-value');
		self::assertTrue($filterHelper->compareFilters($filterA, $filterBSettings, $filterBSubfilters));
	}
}
?>
