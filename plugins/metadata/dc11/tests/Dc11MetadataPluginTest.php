<?php

/**
 * @file plugins/metadata/dc11/tests/Dc11MetadataPluginTest.php
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2000-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class Dc11MetadataPluginTest
 * @ingroup plugins_metadata_dc11_tests
 *
 * @see Dc11MetadataPlugin
 *
 * @brief Test class for Dc11MetadataPlugin.
 */

import('classes.core.Request'); // Cause mocked Request to be loaded
import('classes.i18n.AppLocale'); // Cause mocked AppLocale to be loaded

import('lib.pkp.plugins.metadata.dc11.tests.PKPDc11MetadataPluginTest');

class Dc11MetadataPluginTest extends PKPDc11MetadataPluginTest
{
    /**
     * @covers Dc11MetadataPlugin
     * @covers PKPDc11MetadataPlugin
     */
    public function testDc11MetadataPlugin($appSpecificFilters = [])
    {
        parent::testDc11MetadataPlugin(array_merge($appSpecificFilters, [('article=>dc11')]));
    }
}
