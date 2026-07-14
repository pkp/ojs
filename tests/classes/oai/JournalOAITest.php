<?php

/**
 * @file tests/classes/oai/JournalOAITest.php
 *
 * Copyright (c) 2026 Simon Fraser University
 * Copyright (c) 2026 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class JournalOAITest
 *
 * @ingroup tests_classes_oai
 *
 * @see JournalOAI
 *
 * @brief Tests for the article/version OAI identifier handling added for
 * per-version OAI records (pkp/pkp-lib#12922).
 */

namespace APP\tests\classes\oai;

use APP\oai\ojs\JournalOAI;
use PKP\oai\OAIConfig;
use PKP\tests\PKPTestCase;

class JournalOAITest extends PKPTestCase
{
    private const REPO_ID = 'test.oai.repo';

    /**
     * Build a JournalOAI whose identifier methods can be exercised in isolation.
     *
     * The full OAI constructor opens an output buffer and needs a request/DAO
     * context; the identifier helpers only rely on the config, so instantiate
     * without the constructor and set just the config.
     */
    private function getOAI(): JournalOAI
    {
        $oai = (new \ReflectionClass(JournalOAI::class))->newInstanceWithoutConstructor();
        $oai->config = new OAIConfig('https://example.org/index.php/testj/oai', self::REPO_ID);
        return $oai;
    }

    private function identifier(string $suffix): string
    {
        return 'oai:' . self::REPO_ID . ':article/' . $suffix;
    }

    public function testArticleIdToIdentifierIsBareWithoutVersion(): void
    {
        $oai = $this->getOAI();
        $this->assertSame($this->identifier('5'), $oai->articleIdToIdentifier(5));
        $this->assertSame($this->identifier('5'), $oai->articleIdToIdentifier(5, null));
    }

    public function testArticleIdToIdentifierIsVersionedWithVersionMajor(): void
    {
        $oai = $this->getOAI();
        $this->assertSame($this->identifier('5/version/2'), $oai->articleIdToIdentifier(5, 2));
    }

    public function testIdentifierToArticleAndVersionMajorParsesBothForms(): void
    {
        $oai = $this->getOAI();
        $this->assertSame([5, null], $oai->identifierToArticleAndVersionMajor($this->identifier('5')));
        $this->assertSame([5, 2], $oai->identifierToArticleAndVersionMajor($this->identifier('5/version/2')));
    }

    public function testIdentifierRoundTrip(): void
    {
        $oai = $this->getOAI();
        foreach ([[5, null], [5, 2], [123, 45]] as [$articleId, $versionMajor]) {
            $identifier = $oai->articleIdToIdentifier($articleId, $versionMajor);
            $this->assertSame(
                [$articleId, $versionMajor],
                $oai->identifierToArticleAndVersionMajor($identifier),
                $identifier
            );
        }
    }

    public function testIdentifierToArticleIdStaysBackwardCompatible(): void
    {
        $oai = $this->getOAI();
        // Both the bare and versioned forms resolve to the article (submission) id.
        $this->assertSame(5, $oai->identifierToArticleId($this->identifier('5')));
        $this->assertSame(5, $oai->identifierToArticleId($this->identifier('5/version/2')));
    }

    public function testValidIdentifierAcceptsBareAndVersionedForms(): void
    {
        $oai = $this->getOAI();
        $this->assertTrue($oai->validIdentifier($this->identifier('5')));
        $this->assertTrue($oai->validIdentifier($this->identifier('5/version/2')));
    }

    public function testMalformedIdentifiersAreRejected(): void
    {
        $oai = $this->getOAI();
        $cases = [
            'oai:other.repo:article/5',          // wrong repository id
            $this->identifier('abc'),            // non-numeric article id
            $this->identifier('5/version/x'),    // non-numeric version
            $this->identifier('5/version/'),     // empty version
            $this->identifier('5/foo/12'),       // unknown path segment
            $this->identifier('5/version/12/34'), // trailing segment
            'garbage',
        ];
        foreach ($cases as $identifier) {
            $this->assertSame([false, null], $oai->identifierToArticleAndVersionMajor($identifier), $identifier);
            $this->assertFalse($oai->identifierToArticleId($identifier), $identifier);
            $this->assertFalse($oai->validIdentifier($identifier), $identifier);
        }
    }
}
