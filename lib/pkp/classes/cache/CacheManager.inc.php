<?php

/**
 * @file classes/cache/CacheManager.inc.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2000-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @ingroup cache
 * @see GenericCache
 *
 * @brief Provides cache management functions.
 *
 */

import('lib.pkp.classes.cache.FileCache');

define('CACHE_TYPE_FILE', 1);
define('CACHE_TYPE_OBJECT', 2);

class CacheManager {
	/**
	 * Get the static instance of the cache manager.
	 * @return object CacheManager
	 */
	static function getManager() {
		$manager =& Registry::get('cacheManager', true, null);
		if ($manager === null) {
			$manager = new CacheManager();
		}
		return $manager;
	}

	/**
	 * Get a file cache.
	 * @param $context string
	 * @param $cacheId string
	 * @param $fallback callback
	 * @return object FileCache
	 */
	function getFileCache($context, $cacheId, $fallback) {
		return new FileCache(
			$context, $cacheId, $fallback,
			$this->getFileCachePath()
		);
	}

	function getObjectCache($context, $cacheId, $fallback) {
		return $this->getCache($context, $cacheId, $fallback, CACHE_TYPE_OBJECT);
	}

	function getCacheImplementation($type) {
		switch ($type) {
			case CACHE_TYPE_FILE: return 'file';
			case CACHE_TYPE_OBJECT: return Config::getVar('cache', 'object_cache');
			default: return null;
		}
	}

	/**
	 * Get a cache.
	 * @param $context string
	 * @param $cacheId string
	 * @param $fallback callback
	 * @param $type string Type of cache: CACHE_TYPE_...
	 * @return object Cache
	 */
	function getCache($context, $cacheId, $fallback, $type = CACHE_TYPE_FILE) {
		switch ($this->getCacheImplementation($type)) {
			case 'xcache':
				import('lib.pkp.classes.cache.XCacheCache');
				$cache = new XCacheCache(
					$context, $cacheId, $fallback
				);
				break;
			case 'apc':
				import('lib.pkp.classes.cache.APCCache');
				$cache = new APCCache(
					$context, $cacheId, $fallback
				);
				break;
			case 'memcache':
				import('lib.pkp.classes.cache.MemcacheCache');
				$cache = new MemcacheCache(
					$context, $cacheId, $fallback,
					Config::getVar('cache','memcache_hostname'),
					Config::getVar('cache','memcache_port')
				);
				break;
			case '': // Provide a default if not specified
			case 'file':
				$cache = $this->getFileCache($context, $cacheId, $fallback);
				break;
			case 'none':
				import('lib.pkp.classes.cache.GenericCache');
				$cache = new GenericCache(
					$context, $cacheId, $fallback
				);
				break;
			default:
				die ("Unknown cache type \"$type\"!\n");
				break;
		}
		return $cache;
	}

	/**
	 * Get the path in which file caches will be stored.
	 * @return string The full path to the file cache directory
	 */
	static function getFileCachePath() {
		return Core::getBaseDir() . DIRECTORY_SEPARATOR . 'cache';
	}

	/**
	 * Flush an entire context, if specified, or
	 * the whole cache.
	 * @param $context string The context to flush, if only one is to be flushed
	 * @param $type string The type of cache to flush
	 */
	function flush($context = null, $type = CACHE_TYPE_FILE) {
		$cacheImplementation = $this->getCacheImplementation($type);
		switch ($cacheImplementation) {
			case 'xcache':
			case 'apc':
			case 'memcache':
				$junkCache = $this->getCache($context, null, null);
				$junkCache->flush();
				break;
			case 'file':
				$filePath = $this->getFileCachePath();
				$files = glob($filePath . DIRECTORY_SEPARATOR . 'fc-' . (isset($context)?$context . '-':'') . '*.php');
				foreach ($files as $file) {
					@unlink ($file);
				}
				break;
			case '':
			case 'none':
				// Nothing necessary.
				break;
			default:
				die ("Unknown cache type \"$cacheType\"!\n");
		}
	}
}

?>
