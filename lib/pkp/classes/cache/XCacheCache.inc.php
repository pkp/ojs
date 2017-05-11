<?php

/**
 * @file classes/cache/XCacheCache.inc.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2000-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class XCacheCache
 * @ingroup cache
 * @see GenericCache
 *
 * @brief Provides caching based on XCache's variable store.
 */


import('lib.pkp.classes.cache.GenericCache');

class XCacheCache extends GenericCache {
	/**
	 * Instantiate a cache.
	 */
	function __construct($context, $cacheId, $fallback) {
		parent::__construct($context, $cacheId, $fallback);
	}

	/**
	 * Flush the cache.
	 */
	function flush() {
		$prefix = INDEX_FILE_LOCATION . ':' . $this->getContext() . ':' . $this->getCacheId();
		if (function_exists('xcache_unset_by_prefix')) {
			// If possible, just flush the context
			xcache_unset_by_prefix(prefix);
		} else {
			// Otherwise, we need to do this manually
			for ($i = 0; $i < xcache_count(XC_TYPE_VAR); $i++) {
				$cache = xcache_list(XC_TYPE_VAR, $i);
				foreach ($cache['cache_list'] as $entry) {
					if (substr($entry['name'], 0, strlen($prefix)) == $prefix) xcache_unset($entry['name']);
				}
			}
		}
	}

	/**
	 * Get an object from the cache.
	 * @param $id
	 */
	function getCache($id) {
		$key = INDEX_FILE_LOCATION . ':'. $this->getContext() . ':' . $this->getCacheId() . ':' . $id;
		if (!xcache_isset($key)) return $this->cacheMiss;
		$returner = unserialize(xcache_get($key));
		return $returner;
	}

	/**
	 * Set an object in the cache. This function should be overridden
	 * by subclasses.
	 * @param $id
	 * @param $value
	 */
	function setCache($id, $value) {
		return (xcache_set(INDEX_FILE_LOCATION . ':' . $this->getContext() . ':' . $this->getCacheId() . ':' . $id, serialize($value)));
	}

	/**
	 * Get the time at which the data was cached.
	 * Not implemented in this type of cache.
	 */
	function getCacheTime() {
		return null;
	}

	/**
	 * Set the entire contents of the cache.
	 * WARNING: THIS DOES NOT FLUSH THE CACHE FIRST!
	 */
	function setEntireCache($contents) {
		foreach ($contents as $id => $value) {
			$this->setCache($id, $value);
		}
	}
}

?>
