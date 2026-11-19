<?php

/**
 * @file plugins/generic/doaj/tests/DOAJExportPluginTest.php
 *
 * Copyright (c) 2026 Simon Fraser University
 * Copyright (c) 2026 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class DOAJExportPluginTest
 *
 * @brief Tests for the status the DOAJ plugin records when a registration or deletion fails
 */

namespace APP\plugins\generic\doaj\tests;

use APP\journal\Journal;
use APP\plugins\generic\doaj\DOAJExportPlugin;
use APP\plugins\PubObjectsExportPlugin;
use APP\submission\Submission;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PKP\core\Registry;
use PKP\tests\PKPTestCase;

#[CoversClass(DOAJExportPlugin::class)]
class DOAJExportPluginTest extends PKPTestCase
{
    protected const JSON = '{"bibjson":{"identifier":[{"type":"doi","id":"10.1234/article.1"}],"link":[{"url":"https://example.org/article/1"}]}}';

    /**
     * @copydoc PKPTestCase::getMockedRegistryKeys()
     */
    protected function getMockedRegistryKeys(): array
    {
        return [...parent::getMockedRegistryKeys(), PKPTestCase::MOCKED_GUZZLE_CLIENT_NAME];
    }

    /**
     * Make the HTTP client answer the request with the given response or exception
     */
    protected function mockResult(Response|\Throwable $result): void
    {
        $client = new Client(['handler' => HandlerStack::create(new MockHandler([$result]))]);
        Registry::set(PKPTestCase::MOCKED_GUZZLE_CLIENT_NAME, $client);
    }

    /**
     * Get a context mock
     */
    protected function getContext(): Journal|MockInterface
    {
        $context = Mockery::mock(Journal::class);
        $context->shouldReceive('getId')->andReturn(1);
        return $context;
    }

    /**
     * Get a partial plugin mock that expects the given status for the object
     *
     * @param string|null $expectedMessage Message expected to be stored with the status, or null for none
     */
    protected function getPlugin(Submission $object, string $expectedStatus, ?string $expectedMessage = null): DOAJExportPlugin|MockInterface
    {
        $plugin = Mockery::mock(DOAJExportPlugin::class)->makePartial();
        $plugin->shouldReceive('getSetting')->andReturn('');
        $expectation = $plugin->shouldReceive('updateStatus')->once();
        if ($expectedMessage === null) {
            $expectation->with($object, $expectedStatus);
        } else {
            $expectation->with($object, $expectedStatus, $expectedMessage);
        }
        return $plugin;
    }

    /**
     * A registration that cannot connect is recorded as an error, not left as submitted
     */
    public function testRegisterConnectionFailureRecordsError(): void
    {
        $message = 'cURL error 7: Failed to connect to doaj.org port 443';
        $this->mockResult(new ConnectException($message, new Request('POST', 'https://doaj.org/api/articles')));
        $object = new Submission();
        $plugin = $this->getPlugin($object, PubObjectsExportPlugin::EXPORT_STATUS_ERROR, $message);

        $result = $plugin->registerObject(self::JSON, $object, $this->getContext());

        $this->assertSame([['plugins.importexport.doaj.register.error.mdsError', $message]], $result);
    }

    /**
     * A deletion that cannot connect is recorded as an error, not left as submitted
     */
    public function testDeleteConnectionFailureRecordsError(): void
    {
        $message = 'cURL error 28: Operation timed out';
        $this->mockResult(new ConnectException($message, new Request('DELETE', 'https://doaj.org/api/articles/abc')));
        $object = new Submission();
        $plugin = $this->getPlugin($object, PubObjectsExportPlugin::EXPORT_STATUS_ERROR, $message);

        $result = $plugin->deleteObject('abc', $object, $this->getContext());

        $this->assertSame([['plugins.importexport.doaj.delete.error.mdsError', $message]], $result);
    }

    /**
     * A transfer error without a response is recorded as an error
     */
    public function testRequestExceptionWithoutResponseRecordsError(): void
    {
        $message = 'cURL error 56: Recv failure';
        $this->mockResult(new RequestException($message, new Request('POST', 'https://doaj.org/api/articles')));
        $object = new Submission();
        $plugin = $this->getPlugin($object, PubObjectsExportPlugin::EXPORT_STATUS_ERROR, $message);

        $result = $plugin->registerObject(self::JSON, $object, $this->getContext());

        $this->assertSame([['plugins.importexport.doaj.register.error.mdsError', $message]], $result);
    }

    /**
     * A response that DOAJ sent is still recorded as an error with the response in the message
     */
    public function testErrorResponseRecordsError(): void
    {
        $message = 'Unauthorized (401 Unauthorized)';
        $this->mockResult(new Response(401, [], 'Unauthorized'));
        $object = new Submission();
        $plugin = $this->getPlugin($object, PubObjectsExportPlugin::EXPORT_STATUS_ERROR, $message);

        $result = $plugin->registerObject(self::JSON, $object, $this->getContext());

        $this->assertSame([['plugins.importexport.doaj.register.error.mdsError', $message]], $result);
    }

    /**
     * A registration that DOAJ accepts is still recorded as registered
     */
    public function testAcceptedRegistrationRecordsRegistered(): void
    {
        $this->mockResult(new Response(201, [], '{"id":"abc","status":"OK"}'));
        $object = new Submission();
        $plugin = $this->getPlugin($object, PubObjectsExportPlugin::EXPORT_STATUS_REGISTERED);

        $this->assertTrue($plugin->registerObject(self::JSON, $object, $this->getContext()));
        $this->assertSame('abc', $object->getData($plugin->getIdSettingName()));
    }
}
