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
        $this->assertSame($this->identifier('5'), $oai->articleIdToIdentifier(5, null, null));
        // A stage without a major (or vice versa) is not a full version reference.
        $this->assertSame($this->identifier('5'), $oai->articleIdToIdentifier(5, 'VoR', null));
        $this->assertSame($this->identifier('5'), $oai->articleIdToIdentifier(5, null, 2));
    }

    public function testArticleIdToIdentifierIsVersionedWithStageAndMajor(): void
    {
        $oai = $this->getOAI();
        $this->assertSame($this->identifier('5/version/VoR/2'), $oai->articleIdToIdentifier(5, 'VoR', 2));
        $this->assertSame($this->identifier('5/version/AO/1'), $oai->articleIdToIdentifier(5, 'AO', 1));
        $this->assertSame($this->identifier('5/version/PMUR/3'), $oai->articleIdToIdentifier(5, 'PMUR', 3));
    }

    public function testIdentifierToArticleStageAndVersionMajorParsesBothForms(): void
    {
        $oai = $this->getOAI();
        $this->assertSame([5, null, null], $oai->identifierToArticleStageAndVersionMajor($this->identifier('5')));
        $this->assertSame([5, 'VoR', 2], $oai->identifierToArticleStageAndVersionMajor($this->identifier('5/version/VoR/2')));
    }

    public function testIdentifierRoundTrip(): void
    {
        $oai = $this->getOAI();
        $cases = [[5, null, null], [5, 'VoR', 2], [123, 'AO', 45], [7, 'PMUR', 3]];
        foreach ($cases as [$articleId, $versionStage, $versionMajor]) {
            $identifier = $oai->articleIdToIdentifier($articleId, $versionStage, $versionMajor);
            $this->assertSame(
                [$articleId, $versionStage, $versionMajor],
                $oai->identifierToArticleStageAndVersionMajor($identifier),
                $identifier
            );
        }
    }

    public function testIdentifierToArticleIdStaysBackwardCompatible(): void
    {
        $oai = $this->getOAI();
        // Both the bare and versioned forms resolve to the article (submission) id.
        $this->assertSame(5, $oai->identifierToArticleId($this->identifier('5')));
        $this->assertSame(5, $oai->identifierToArticleId($this->identifier('5/version/VoR/2')));
    }

    public function testValidIdentifierAcceptsBareAndVersionedForms(): void
    {
        $oai = $this->getOAI();
        $this->assertTrue($oai->validIdentifier($this->identifier('5')));
        $this->assertTrue($oai->validIdentifier($this->identifier('5/version/VoR/2')));
    }

    public function testMalformedIdentifiersAreRejected(): void
    {
        $oai = $this->getOAI();
        $cases = [
            'oai:other.repo:article/5',            // wrong repository id
            $this->identifier('abc'),              // non-numeric article id
            $this->identifier('5/version/VoR/x'),  // non-numeric version
            $this->identifier('5/version/VoR/'),   // empty version
            $this->identifier('5/version/VoR'),    // missing version major
            $this->identifier('5/version/2'),      // missing version stage
            $this->identifier('5/version/xx/2'),   // unknown version stage
            $this->identifier('5/foo/12'),         // unknown path segment
            $this->identifier('5/version/VoR/12/34'), // trailing segment
            'garbage',
        ];
        foreach ($cases as $identifier) {
            $this->assertSame([false, null, null], $oai->identifierToArticleStageAndVersionMajor($identifier), $identifier);
            $this->assertFalse($oai->identifierToArticleId($identifier), $identifier);
            $this->assertFalse($oai->validIdentifier($identifier), $identifier);
        }
    }
}
