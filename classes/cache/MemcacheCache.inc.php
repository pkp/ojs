<?php

/**
 * MemcacheCache.inc.php
 *
 * Copyright (c) 2003-2005 The Public Knowledge Project
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

	var $contextChecked;

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
		$this->contextChecked = false;
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
		$this->contextChecked = false;
	}

	/**
	 * Get an object from the cache.
	 * @param $id
	 */
	function getCache($id) {
		if (!$this->contextChecked) {
			if (!$this->contextChecked = $this->connection->get($this->context)) {
				return CACHE_MISS;
			}
		}
		return $this->connection->get($this->getContext() . ':' . $this->getCacheId() . ':' . $id);
	}

	/**
	 * Set an object in the cache. This function should be overridden
	 * by subclasses.
	 * @param $id
	 * @param $value
	 */
	function setCache($id, $value) {
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

	function setEntireCache(&$contents) {
		parent::setEntireCache($contents);
		// Set the cache date to the current time.
		$this->connection->set($this->context, time(), $this->flag, $this->expire);
	}

	/**
	 * Get the time at which the data was cached.
	 */
	function getCacheTime() {
		return $this->connection->get($this->context);
	}
}

?>
