<?php

/**
 * @file tests/plugins/importexport/PubObjectCacheTest.php
 *
 * Copyright (c) 2014-2019 Simon Fraser University
 * Copyright (c) 2000-2019 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class PubObjectCacheTest
 * @ingroup tests_plugins_importexport
 * @see PubObjectCacheTest
 *
 * @brief Test class for PubObjectCache.
 *
 * NB: This test is not in the medra or datacite package as the class
 * is used symlinked in both plug-ins.
 */

import('lib.pkp.tests.PKPTestCase');
import('classes/issue/Issue');
import('classes/article/PublishedArticle');
import('classes/article/ArticleGalley');
import('plugins.importexport.medra.classes.PubObjectCache');

class PubObjectCacheTest extends PKPTestCase {
	/**
	 * @covers PubObjectCache
	 */
	public function testAddIssue() {
		$nullVar = null;
		$cache = new PubObjectCache();

		$issue = new Issue();
		$issue->setId('1');

		self::assertFalse($cache->isCached('issues', $issue->getId()));
		$cache->add($issue, $nullVar);
		self::assertTrue($cache->isCached('issues', $issue->getId()));

		$retrievedIssue = $cache->get('issues', $issue->getId());
		self::assertEquals($issue, $retrievedIssue);
	}

	/**
	 * @covers PubObjectCache
	 */
	public function testAddArticle() {
		$nullVar = null;
		$cache = new PubObjectCache();

		$article = new PublishedArticle();
		$article->setId('2');
		$article->setIssueId('1');

		self::assertFalse($cache->isCached('articles', $article->getId()));
		self::assertFalse($cache->isCached('articlesByIssue', $article->getIssueId()));
		self::assertFalse($cache->isCached('articlesByIssue', $article->getIssueId(), $article->getId()));
		$cache->add($article, $nullVar);
		self::assertTrue($cache->isCached('articles', $article->getId()));
		self::assertFalse($cache->isCached('articlesByIssue', $article->getIssueId()));
		self::assertTrue($cache->isCached('articlesByIssue', $article->getIssueId(), $article->getId()));

		$retrievedArticle = $cache->get('articles', $article->getId());
		self::assertEquals($article, $retrievedArticle);
	}


	/**
	 * @covers PubObjectCache
	 */
	public function testAddGalley() {
		$nullVar = null;
		$cache = new PubObjectCache();

		$article = new PublishedArticle();
		$article->setId('2');
		$article->setIssueId('1');

		$articleGalley = new ArticleGalley();
		$articleGalley->setId('3');
		$articleGalley->setSubmissionId($article->getId());

		self::assertFalse($cache->isCached('galleys', $articleGalley->getId()));
		self::assertFalse($cache->isCached('galleysByArticle', $article->getId()));
		self::assertFalse($cache->isCached('galleysByArticle', $article->getId(), $articleGalley->getId()));
		self::assertFalse($cache->isCached('galleysByIssue', $article->getIssueId()));
		self::assertFalse($cache->isCached('galleysByIssue', $article->getIssueId(), $articleGalley->getId()));
		$cache->add($articleGalley, $article);
		self::assertTrue($cache->isCached('galleys', $articleGalley->getId()));
		self::assertFalse($cache->isCached('galleysByArticle', $article->getId()));
		self::assertTrue($cache->isCached('galleysByArticle', $article->getId(), $articleGalley->getId()));
		self::assertFalse($cache->isCached('galleysByIssue', $article->getIssueId()));
		self::assertTrue($cache->isCached('galleysByIssue', $article->getIssueId(), $articleGalley->getId()));

		$retrievedArticleGalley1 = $cache->get('galleys', $articleGalley->getId());
		self::assertEquals($articleGalley, $retrievedArticleGalley1);

		$retrievedArticleGalley2 = $cache->get('galleysByIssue', $article->getIssueId(), $articleGalley->getId());
		self::assertEquals($retrievedArticleGalley1, $retrievedArticleGalley2);

		$cache->markComplete('galleysByArticle', $article->getId());
		self::assertTrue($cache->isCached('galleysByArticle', $article->getId()));
		self::assertFalse($cache->isCached('galleysByIssue', $article->getIssueId()));
	}

	/**
	 * @covers PubObjectCache
	 */
	public function testAddSeveralGalleys() {
		$nullVar = null;
		$cache = new PubObjectCache();

		$article = new PublishedArticle();
		$article->setId('2');
		$article->setIssueId('1');

		$articleGalley1 = new ArticleGalley();
		$articleGalley1->setId('3');
		$articleGalley1->setSubmissionId($article->getId());

		$articleGalley2 = new ArticleGalley();
		$articleGalley2->setId('4');
		$articleGalley2->setSubmissionId($article->getId());

		// Add galleys in the wrong order.
		$cache->add($articleGalley2, $article);
		$cache->add($articleGalley1, $article);

		$cache->markComplete('galleysByArticle', $article->getId());

		// Retrieve them in the right order.
		$retrievedGalleys = $cache->get('galleysByArticle', $article->getId());
		$expectedGalleys = array(
			3 => $articleGalley1,
			4 => $articleGalley2
		);
		self::assertEquals($expectedGalleys, $retrievedGalleys);

		// And they should still be cached.
		self::assertTrue($cache->isCached('galleysByArticle', $article->getId()));
	}
}

