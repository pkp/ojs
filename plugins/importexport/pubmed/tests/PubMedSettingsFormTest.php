<?php

/**
 * @file plugins/importexport/pubmed/tests/PubMedSettingsFormTest.php
 *
 * Copyright (c) 2026 Simon Fraser University
 * Copyright (c) 2026 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file LICENSE.
 *
 * @class PubMedSettingsFormTest
 *
 * @brief Tests for loading and saving the PubMed export plugin's NLM title abbreviation setting.
 */

namespace APP\plugins\importexport\pubmed\tests;

use APP\plugins\importexport\pubmed\PubMedExportPlugin;
use APP\plugins\importexport\pubmed\PubMedSettingsForm;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use PKP\tests\PKPTestCase;

#[CoversClass(PubMedSettingsForm::class)]
class PubMedSettingsFormTest extends PKPTestCase
{
    private const CONTEXT_ID = 1;
    private const NLM_TITLE = 'J Pub Knowl';

    /**
     * Test that the form is populated with the stored NLM title abbreviation.
     */
    public function testInitDataLoadsNlmTitle(): void
    {
        $plugin = $this->createPluginMock();
        $plugin->expects($this->once())
            ->method('getSetting')
            ->with(self::CONTEXT_ID, 'nlmTitle')
            ->willReturn(self::NLM_TITLE);

        $form = new PubMedSettingsForm($plugin, self::CONTEXT_ID);
        $form->initData();

        self::assertSame(self::NLM_TITLE, $form->getData('nlmTitle'));
    }

    /**
     * Test that saving the form stores the NLM title abbreviation as a string setting of the context.
     */
    public function testExecuteSavesNlmTitle(): void
    {
        $plugin = $this->createPluginMock();
        $plugin->expects($this->once())
            ->method('updateSetting')
            ->with(self::CONTEXT_ID, 'nlmTitle', self::NLM_TITLE, 'string');

        $form = new PubMedSettingsForm($plugin, self::CONTEXT_ID);
        $form->setData('nlmTitle', self::NLM_TITLE);
        $form->execute();
    }

    /**
     * Test that the NLM title abbreviation is the only field and that it is optional.
     */
    public function testNlmTitleIsOptional(): void
    {
        $form = new PubMedSettingsForm($this->createPluginMock(), self::CONTEXT_ID);

        self::assertSame(['nlmTitle' => 'string'], $form->getFormFields());
        self::assertTrue($form->isOptional('nlmTitle'));
    }

    /**
     * Create a PubMed export plugin that does not read or write the database.
     */
    private function createPluginMock(): PubMedExportPlugin&MockObject
    {
        return $this->getMockBuilder(PubMedExportPlugin::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getSetting', 'updateSetting', 'getTemplateResource'])
            ->getMock();
    }
}
