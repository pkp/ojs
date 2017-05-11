<?php

/**
 * @file tests/classes/cache/FileCacheTest.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2000-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class FileCacheTest
 * @ingroup tests_classes_cache
 * @see Config
 *
 * @brief Tests for the FileCache class.
 */


import('lib.pkp.tests.PKPTestCase');

class FileCacheTest extends PKPTestCase {

	/** @var $cacheManager CacheManager */
	var $cacheManager;

	/** @var $cacheMisses int */
	var $cacheMisses;

	var $testCacheContents = array(
		0 => 'zero',
		1 => 'one',
		2 => 'two',
		3 => 'three',
	);

	/**
	 * @covers FileCache::get
	 */
	public function testGetCache() {
		// Get the file cache.
		$fileCache = $this->getCache();

		// No cache misses should be registered.
		assert($this->cacheMisses == 0);

		// The cache has just been flushed by setUp. Try a get.
		$val1 = $fileCache->get(1);

		// Make sure the returned value was correct
		assert($val1 == 'one');

		// Make sure we registered one cache miss
		assert($this->cacheMisses == 1);

		// Try another get
		$val2 = $fileCache->get(2);

		// Make sure the value was correct
		assert($val2 == 'two');

		// Make sure we didn't have to register another cache miss
		assert($this->cacheMisses == 1);
	}

	/**
	 * @covers FileCache::get
	 */
	public function testCacheMiss() {
		$this->markTestSkipped();

		// Get the file cache.
		$fileCache = $this->getCache();

		// Try to get an item that's not in the cache
		$val1 = $fileCache->get(-1);

		// Make sure we registered one cache miss
		assert ($val1 == null);
		assert($this->cacheMisses == 1);

		// Try another get of the same item
		$val2 = $fileCache->get(-1);

		// Check to see that we got it without a second miss
		assert($val2 == null);

		// WARNING: This will trigger bug #8039 until fixed.
		assert($this->cacheMisses == 1);
	}

	//
	// Helper functions
	//
	public function _cacheMiss($cache, $id) {
		$this->cacheMisses++;
		$cache->setEntireCache($this->testCacheContents);
		if (!isset($this->testCacheContents[$id])) {
			$cache->setCache($id, null);
			return null;
		}
		return $this->testCacheContents[$id];
	}

	//
	// Protected methods.
	//
	protected function setUp() {
		$this->cacheManager = CacheManager::getManager();
		$this->cacheMisses = 0;

		if (!is_writable($this->cacheManager->getFileCachePath())) {
			$this->markTestSkipped('File cache path not writable.');
		} else {
			parent::setUp();
			$this->cacheManager->flush();
		}
	}

	/**
	 * Return a test cache.
	 */
	protected function getCache() {
		return $this->cacheManager->getFileCache('testCache', 0, array($this, '_cacheMiss'));
	}
}

?>
