<?php

/**
 * @file classes/cache/GenericCache.inc.php
 *
 * Copyright (c) 2003-2008 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class GenericCache
 * @ingroup cache
 *
 * @brief Provides implementation-independent caching. Although this class is intended
 * to be overridden with a more specific implementation, it can be used as the
 * null cache.
 */

// $Id$


// Pseudotype to represent a cache miss
class generic_cache_miss {
}

class GenericCache {
	/**
	 * The unique string identifying the context of this cache.
	 * Must be suitable for a filename.
	 */
	var $context;

	/**
	 * The ID of this particular cache within the context
	 */
	var $cacheId;

	var $cacheMiss;

	/**
	 * The getter fallback callback (for a cache miss)
	 * This function is called with two parameters:
	 *  1. The cache object that is suffering a miss
	 *  2. The id of the value to fetch
	 * The function is responsible for loading data into the
	 * cache, using setEntireCache or setCache.
	 */
	var $fallback;

	/**
	 * Instantiate a cache.
	 */
	function GenericCache($context, $cacheId, $fallback) {
		$this->context = $context;
		$this->cacheId = $cacheId;
		$this->fallback = $fallback;
		$this->cacheMiss =& new generic_cache_miss;
	}

	/**
	 * Get an object from cache, using the fallback if necessary.
	 */
	function get($id) {
		$result = $this->getCache($id);
		if (get_class($result) === 'generic_cache_miss') {
			$result = call_user_func_array($this->fallback, array(&$this, $id));
		}
		return $result;
	}

	/**
	 * Set an object in the cache. This function should be overridden
	 * by subclasses.
	 */
	function set($id, $value) {
		return $this->setCache($id, $value);
	}

	/**
	 * Flush the cache.
	 */
	function flush() {
	}

	/**
	 * Set the entire contents of the cache. May (should) be overridden
	 * by subclasses.
	 * @param $array array of id -> value pairs
	 */
	function setEntireCache(&$contents) {
		$this->flush();
		foreach ($contents as $id => $value) {
			$this->setCache($id, $value);
		}
	}

	/**
	 * Get an object from the cache. This function should be overridden
	 * by subclasses.
	 * @param $id
	 */
	function getCache($id) {
		return $this->cacheMiss;
	}

	/**
	 * Set an object in the cache. This function should be overridden
	 * by subclasses.
	 * @param $id
	 * @param $value
	 */
	function setCache($id, $value) {
	}

	/**
	 * Close the cache. (Optionally overridden by subclasses.)
	 */
	function close() {
	}

	/**
	 * Get the context.
	 */
	function getContext() {
		return $this->context;
	}

	/**
	 * Get the cache ID within its context
	 */
	function getCacheId() {
		return $this->cacheId;
	}

	/**
	 * Get the time at which the data was cached.
	 */
	function getCacheTime() {
		// Since it's not really cached, we'll consider it to have been cached just now.
		return time();
	}
}

?>
