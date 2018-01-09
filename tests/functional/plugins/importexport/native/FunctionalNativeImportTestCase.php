<?php

/**
 * @file tests/functional/plugins/importexport/native/FunctionalNativeImportTest.php
 *
 * Copyright (c) 2013-2018 Simon Fraser University
 * Copyright (c) 2000-2018 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class FunctionalNativeImportTest
 * @ingroup tests_functional_plugins_importexport_native
 *
 * @brief Test native OJS import.
 */

require_mock_env('env1');

import('lib.pkp.classes.core.PKPRouter');
import('lib.pkp.tests.functional.plugins.importexport.FunctionalImportExportBaseTestCase');

class FunctionalNativeImportTest extends FunctionalImportExportBaseTestCase {
	private $expectedDois = array(
		'Issue' => '10.1234/t.v1i1-imp-test',
		'PublishedArticle' => '10.1234/t.v1i1.1-imp-test',
		'Galley' => '10.1234/t.v1i1.1.g1-imp-test',
		'SuppFile' => '10.1234/t.v1i1.1.s1-imp-test'
	);
	private $expectedURNs = array(
		'Issue' => 'urn:nbn:de:0000-t.v1i1-imp-test8',
		'PublishedArticle' => 'urn:nbn:de:0000-t.v1i1.1-imp-test5',
		'Galley' => 'urn:nbn:de:0000-t.v1i1.1.g1-imp-test5',
		'SuppFile' => 'urn:nbn:de:0000-t.v1i1.1.s1-imp-test9'
	);

	/**
	 * @see WebTestCase::getAffectedTables()
	 */
	protected function getAffectedTables() {
		return array(
			'articles', 'article_files', 'article_galleys', 'article_galley_settings', 'article_search_object_keywords',
			'article_search_objects', 'article_settings', 'article_supp_file_settings', 'article_supplementary_files',
			'authors', 'custom_issue_orders', 'custom_section_orders', 'event_log', 'event_log_settings',
			'issue_settings', 'issues', 'published_articles', 'sessions', 'signoffs', 'temporary_files', 'users'
		);
	}

	/**
	 * @see WebTestCase::setUp()
	 */
	protected function setUp() {
		parent::setUp();
		$application =& PKPApplication::getApplication();
		$request =& $application->getRequest();
		if (is_null($request->getRouter())) {
			$router = new PKPRouter();
			$request->setRouter($router);
		}
	}

	public function testNativeDoiImport() {
		$testfile = 'tests/functional/plugins/importexport/native/testissue.xml';
		$args = array('import', $testfile, 'test', 'admin');
		$result = $this->executeCli('NativeImportExportPlugin', $args);
		self::assertRegExp('/##plugins.importexport.native.import.success.description##/', $result);

		$daos = array(
			'Issue' => 'IssueDAO',
			'PublishedArticle' => 'PublishedArticleDAO',
			'Galley' => 'ArticleGalleyDAO',
			'SuppFile' => 'SuppFileDAO'
		);
		$articelId = null;
		foreach ($daos as $objectType => $daoName) {
			$dao = DAORegistry::getDAO($daoName);
			$pubObject = call_user_func(array($dao, "get${objectType}ByPubId"), 'doi', $this->expectedDois[$objectType]);
			self::assertNotNull($pubObject, "Error while testing $objectType: object or DOI has not been imported.");
			$pubObjectByURN = call_user_func(array($dao, "get${objectType}ByPubId"), 'other::urn', $this->expectedURNs[$objectType]);
			self::assertNotNull($pubObjectByURN, "Error while testing $objectType: object or URN has not been imported.");
			if ($objectType == 'PublishedArticle') {
				$articelId = $pubObject->getId();
			}
		}

		// Trying to import the same file again should lead to an error.
		$args = array('import', $testfile, 'test', 'admin');
		$result = $this->executeCli('NativeImportExportPlugin', $args);
		self::assertRegExp('/##plugins.importexport.native.import.error.duplicatePubId##/', $result);

		// Delete inserted article files from the filesystem.
		import('classes.file.ArticleFileManager');
		$articleFileManager = new ArticleFileManager($articelId);
		$articleFileManager->deleteArticleTree();
	}

	public function testNativeDoiImportWithErrors() {
		$testfile = 'tests/functional/plugins/importexport/native/testissue-with-errors.xml';
		$args = array('import', $testfile, 'test', 'admin');
		$result = $this->executeCli('NativeImportExportPlugin', $args);
		self::assertRegExp('/##plugins.importexport.native.import.error.unknownPubId##/', $result);
	}
}
?>
