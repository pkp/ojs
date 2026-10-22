<?php

/**
 * @file plugins/generic/datacite/tests/DataciteExportPluginTest.php
 *
 * Copyright (c) 2014-2026 Simon Fraser University
 * Copyright (c) 2000-2026 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class DataciteExportPluginTest
 *
 * @brief Tests for the deposit status the DataCite plugin records when a deposit fails
 */

namespace APP\plugins\generic\datacite\tests;

use APP\issue\Issue;
use APP\journal\Journal;
use APP\plugins\generic\datacite\DataciteExportPlugin;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use Mockery;
use PHPUnit\Framework\Attributes\CoversClass;
use PKP\core\Registry;
use PKP\doi\Doi;
use PKP\tests\PKPTestCase;

#[CoversClass(DataciteExportPlugin::class)]
class DataciteExportPluginTest extends PKPTestCase
{
    protected string $filename;

    /**
     * @copydoc PKPTestCase::getMockedRegistryKeys()
     */
    protected function getMockedRegistryKeys(): array
    {
        return [...parent::getMockedRegistryKeys(), PKPTestCase::MOCKED_GUZZLE_CLIENT_NAME];
    }

    /**
     * @copydoc PKPTestCase::setUp()
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->mockRequest();
        $this->filename = tempnam(sys_get_temp_dir(), 'datacite');
    }

    /**
     * @copydoc PKPTestCase::tearDown()
     */
    protected function tearDown(): void
    {
        @unlink($this->filename);
        parent::tearDown();
    }

    /**
     * Run a deposit where the HTTP client returns the given results in order, and
     * expect the deposit to be recorded as an error with the given message
     *
     * @param array $results Responses to return or exceptions to throw, one per request
     *
     * @return array|bool The result of depositXML()
     */
    protected function deposit(array $results, string $expectedMessage): array|bool
    {
        $client = Mockery::mock(Client::class)->makePartial();
        $expectation = $client->shouldReceive('request')->times(count($results));
        $expectation->andReturnUsing(function () use (&$results) {
            $result = array_shift($results);
            return $result instanceof \Throwable ? throw $result : $result;
        });
        Registry::set(PKPTestCase::MOCKED_GUZZLE_CLIENT_NAME, $client);

        $context = Mockery::mock(Journal::class);
        $context->shouldReceive('getId')->andReturn(1);
        $issue = Mockery::mock(Issue::class);
        $issue->shouldReceive('getDoi')->andReturn('10.1234/issue.1');

        $plugin = Mockery::mock(DataciteExportPlugin::class)->makePartial();
        $plugin->shouldReceive('getSetting')->andReturn('');
        $plugin->shouldReceive('isTestMode')->andReturn(false);
        $plugin->shouldReceive('_getObjectUrl')->andReturn('https://example.org/issue/1');
        $plugin->shouldReceive('updateDepositStatus')
            ->once()
            ->with($issue, Doi::STATUS_ERROR, $expectedMessage);

        return $plugin->depositXML($issue, $context, $this->filename);
    }

    /**
     * A metadata request that cannot connect is recorded as an error, not left as submitted
     */
    public function testMetadataConnectionFailureRecordsError(): void
    {
        $message = 'cURL error 7: Failed to connect to mds.datacite.org port 443';
        $result = $this->deposit(
            [new ConnectException($message, new Request('POST', 'https://mds.datacite.org/metadata'))],
            $message
        );

        $this->assertSame([['plugins.importexport.common.register.error.mdsError', "Registering DOI 10.1234/issue.1: {$message}"]], $result);
    }

    /**
     * A DOI request that cannot connect is recorded as an error, not left as submitted
     */
    public function testDoiConnectionFailureRecordsError(): void
    {
        $message = 'cURL error 28: Operation timed out';
        $result = $this->deposit(
            [new Response(201), new ConnectException($message, new Request('POST', 'https://mds.datacite.org/doi'))],
            $message
        );

        $this->assertSame([['plugins.importexport.common.register.error.mdsError', "Registering DOI 10.1234/issue.1: {$message}"]], $result);
    }

    /**
     * A response that DataCite sent is still recorded as an error with the response in the message
     */
    public function testErrorResponseRecordsError(): void
    {
        $message = 'Unauthorized (401 Unauthorized)';
        $request = new Request('POST', 'https://mds.datacite.org/metadata');
        $result = $this->deposit(
            [new ClientException('Client error', $request, new Response(401, [], 'Unauthorized'))],
            $message
        );

        $this->assertSame([['plugins.importexport.common.register.error.mdsError', "Registering DOI 10.1234/issue.1: {$message}"]], $result);
    }

    /**
     * A deposit that DataCite accepts is still recorded as registered
     */
    public function testAcceptedDepositRecordsRegistered(): void
    {
        $client = Mockery::mock(Client::class)->makePartial();
        $client->shouldReceive('request')->twice()->andReturn(new Response(201));
        Registry::set(PKPTestCase::MOCKED_GUZZLE_CLIENT_NAME, $client);

        $context = Mockery::mock(Journal::class);
        $context->shouldReceive('getId')->andReturn(1);
        $issue = Mockery::mock(Issue::class);
        $issue->shouldReceive('getDoi')->andReturn('10.1234/issue.1');

        $plugin = Mockery::mock(DataciteExportPlugin::class)->makePartial();
        $plugin->shouldReceive('getSetting')->andReturn('');
        $plugin->shouldReceive('isTestMode')->andReturn(false);
        $plugin->shouldReceive('_getObjectUrl')->andReturn('https://example.org/issue/1');
        $plugin->shouldReceive('updateDepositStatus')->once()->with($issue, Doi::STATUS_REGISTERED);

        $this->assertTrue($plugin->depositXML($issue, $context, $this->filename));
    }
}
