<?php

/**
 * @file classes/cache/APCCache.inc.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2000-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class APCCache
 * @ingroup cache
 * @see GenericCache
 *
 * @brief Provides caching based on APC's variable store.
 */


import('lib.pkp.classes.cache.GenericCache');

class apc_false {};

class APCCache extends GenericCache {
	/**
	 * Instantiate a cache.
	 * @param $context string
	 * @param $cacheId mixed
	 * @param $fallback array PKP-style callback
	 */
	function __construct($context, $cacheId, $fallback) {
		parent::__construct($context, $cacheId, $fallback);
	}

	/**
	 * Flush the cache.
	 */
	function flush() {
		$prefix = INDEX_FILE_LOCATION . ':' . $this->getContext() . ':' . $this->getCacheId();
		$info = apc_cache_info('user');
		foreach ($info['cache_list'] as $entry) {
			if (substr($entry['info'], 0, strlen($prefix)) == $prefix) apc_delete($entry['info']);
		}
	}

	/**
	 * Get an object from the cache.
	 * @param $id mixed
	 * @return mixed
	 */
	function getCache($id) {
		$key = INDEX_FILE_LOCATION . ':'. $this->getContext() . ':' . $this->getCacheId() . ':' . $id;
		$returner = unserialize(apc_fetch($key));
		if ($returner === false) return $this->cacheMiss;
		if (get_class($returner) === 'apc_false') $returner = false;
		return $returner;
	}

	/**
	 * Set an object in the cache. This function should be overridden
	 * by subclasses.
	 * @param $id mixed
	 * @param $value mixed
	 */
	function setCache($id, $value) {
		$key = INDEX_FILE_LOCATION . ':'. $this->getContext() . ':' . $this->getCacheId() . ':' . $id;
		if ($value === false) $value = new apc_false;
		apc_store($key, serialize($value));
	}

	/**
	 * Get the time at which the data was cached.
	 * Not implemented in this type of cache.
	 * @return null
	 */
	function getCacheTime() {
		return null;
	}

	/**
	 * Set the entire contents of the cache.
	 * WARNING: THIS DOES NOT FLUSH THE CACHE FIRST!
	 * @param $contents array Complete cache contents.
	 */
	function setEntireCache($contents) {
		foreach ($contents as $id => $value) {
			$this->setCache($id, $value);
		}
	}
}

?>
