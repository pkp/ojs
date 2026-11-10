<?php

/**
 * @file plugins/generic/datacite/tests/DataciteXmlFilterTest.php
 *
 * Copyright (c) 2026 Simon Fraser University
 * Copyright (c) 2026 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file LICENSE.
 *
 * @class DataciteXmlFilterTest
 *
 * @brief Tests for DataciteXmlFilter.
 */

namespace APP\plugins\generic\datacite\tests;

use APP\plugins\generic\datacite\DataciteExportDeployment;
use APP\plugins\generic\datacite\filter\DataciteXmlFilter;
use DOMDocument;
use Mockery;
use PHPUnit\Framework\Attributes\CoversClass;
use PKP\tests\PKPTestCase;

#[CoversClass(DataciteXmlFilter::class)]
class DataciteXmlFilterTest extends PKPTestCase
{
    /**
     * Issues have no publication or submission, so they get no funding references.
     */
    public function testFundingReferencesNodeForIssue(): void
    {
        $filter = $this->getMockBuilder(DataciteXmlFilter::class)
            ->disableOriginalConstructor()
            ->onlyMethods([])
            ->getMock();
        $filter->setDeployment(Mockery::mock(DataciteExportDeployment::class));

        self::assertNull($filter->createFundingReferencesNode(new DOMDocument(), null, null));
    }
}
