<?php

/**
 * MemcacheCache.inc.php
 *
 * Copyright (c) 2003-2006 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package cache
 *
 * Provides caching based on Memcache.
 *
 * $Id$
 */

import('cache.GenericCache');

// FIXME This should use connection pooling
// WARNING: This cache MUST be loaded in batch, or else many cache
// misses will result.

// Pseudotypes used to represent false and null values in the cache
class memcache_false {
}
class memcache_null {
}

class MemcacheCache extends GenericCache {
	/**
	 * Connection to use for caching.
	 */
	var $connection;

	/**
	 * Flag (used by Memcache::set)
	 */
	var $flag;

	/**
	 * Expiry (used by Memcache::set)
	 */
	var $expire;

	/**
	 * Instantiate a cache.
	 */
	function MemcacheCache($context, $cacheId, $fallback, $hostname, $port) {
		parent::GenericCache($context, $cacheId, $fallback);
		$this->connection =& new Memcache;

		if (!$this->connection->connect($hostname, $port)) {
			$this->connection = null;
		}

		$this->flag = null;
		$this->expire = 3600; // 1 hour default expiry
	}

	/**
	 * Set the flag (used in Memcache::set)
	 */
	function setFlag($flag) {
		$this->flag = $flag;
	}

	/**
	 * Set the expiry time (used in Memcache::set)
	 */
	function setExpiry($expiry) {
		$this->expire = $expiry;
	}

	/**
	 * Flush the cache.
	 */
	function flush() {
		$this->connection->flush();
	}

	/**
	 * Get an object from the cache.
	 * @param $id
	 */
	function getCache($id) {
		$result = $this->connection->get($this->getContext() . ':' . $this->getCacheId() . ':' . $id);
		if ($result === false) {
			return $this->cacheMiss;
		}
		switch (get_class($result)) {
			case 'memcache_false':
				$result = false;
			case 'memcache_null':
				$result = null;
		}
		return $result;
	}

	/**
	 * Set an object in the cache. This function should be overridden
	 * by subclasses.
	 * @param $id
	 * @param $value
	 */
	function setCache($id, $value) {
		if ($value === false) {
			$value = new memcache_false;
		} elseif ($value === null) {
			$value = new memcache_null;
		}
		return ($this->connection->set($this->getContext() . ':' . $this->getCacheId() . ':' . $id, $value, $this->flag, $this->expire));
	}

	/**
	 * Close the cache and free resources.
	 */
	function close() {
		$this->connection->close();
		unset ($this->connection);
		$this->contextChecked = false;
	}

	/**
	 * Get the time at which the data was cached.
	 * Note that keys expire in memcache, which means
	 * that it's possible that the date will disappear
	 * before the data -- in this case we'll have to
	 * assume the data is still good.
	 */
	function getCacheTime() {
		return null;
	}

	/**
	 * Set the entire contents of the cache.
	 * WARNING: THIS DOES NOT FLUSH THE CACHE FIRST!
	 * This is because there is no "scope restriction"
	 * for flushing within memcache and therefore
	 * a flush here would flush the entire cache,
	 * resulting in more subsequent calls to this function,
	 * resulting in more flushes, etc.
	 */
	function setEntireCache(&$contents) {
		foreach ($contents as $id => $value) {
			$this->setCache($id, $value);
		}
	}
}

?>
