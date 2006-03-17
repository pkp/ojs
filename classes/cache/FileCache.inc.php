<?php

/**
 * FileCache.inc.php
 *
 * Copyright (c) 2003-2005 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package cache
 *
 * Provides caching based on machine-generated PHP code on the filesystem.
 *
 * $Id$
 */

import('cache.GenericCache');

class FileCache extends GenericCache {
	/**
	 * Connection to use for caching.
	 */
	var $filename;

	/**
	 * The cached data
	 */
	var $cache;

	/**
	 * Instantiate a cache.
	 */
	function FileCache($context, $cacheId, $fallback, $path) {
		parent::GenericCache($context, $cacheId, $fallback);

		$this->filename = $path . DIRECTORY_SEPARATOR . "fc-$context-" . str_replace('/', '.', $cacheId) . '.php';

		// Load the cache data if it exists.
		if (file_exists($this->filename)) {
			$this->cache = include($this->filename);
		} else {
			$this->cache = null;
		}
	}

	/**
	 * Flush the cache
	 */
	function flush() {
		unset($this->cache);
		$this->cache = null;
		if (file_exists($this->filename)) {
			unlink($this->filename);
		}
	}

	/**
	 * Get an object from the cache.
	 * @param $id
	 */
	function getCache($id) {
		if (!isset($this->cache)) return $this->cacheMiss;
		return (isset($this->cache[$id])?$this->cache[$id]:null);
	}

	/**
	 * Set an object in the cache. This function should be overridden
	 * by subclasses.
	 * @param $id
	 * @param $value
	 */
	function setCache($id, $value) {
		// Flush the cache; it will be regenerated on demand.
		$this->flush();
	}

	/**
	 * Set the entire contents of the cache.
	 */
	function setEntireCache(&$contents) {
		$fp = @fopen($this->filename, 'wb');
		// If the cache can be written, write it. If not, fall
		// back on NO CACHING AT ALL.
		if ($fp) {
			fwrite ($fp, '<?php return ' . var_export($contents, true) . '; ?>');
			fclose ($fp);
		}
		$this->cache =& $contents;
	}

	/**
	 * Get the time at which the data was cached.
	 */
	function getCacheTime() {
		if (!file_exists($this->filename)) return 0;
		return filemtime($this->filename);
	}

	/**
	 * Get the entire contents of the cache in an associative array.
	 */
	function &getContents() {
		if (!isset($this->cache)) {
			// Trigger a cache miss to load the cache.
			$this->get(null);
		}
		return $this->cache;
	}
}

?>
