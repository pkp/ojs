<?php

/**
 * CacheManager.inc.php
 *
 * Copyright (c) 2003-2005 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package cache
 *
 * Provides cache management functions.
 *
 * $Id$
 */

class CacheManager {
	function getManager() {
		static $manager;
		if (!isset($manager)) {
			$manager =& new CacheManager();
		}
		return $manager;
	}

	function &getFileCache($context, $cacheId, $fallback) {
		import('cache.FileCache');
		return new FileCache(
			$context, $cacheId, $fallback,
			$this->getFileCachePath()
		);
	}

	function &getCache($context, $cacheId, $fallback) {
		$cacheType = Config::getVar('cache','cache');
		switch ($cacheType) {
			case 'memcache':
				import('cache.MemcacheCache');
				$cache =& new MemcacheCache(
					$context, $cacheId, $fallback,
					Config::getVar('cache','memcache_hostname'),
					Config::getVar('cache','memcache_port')
				);
				break;
			case 'file':
				$cache =& $this->getFileCache($context, $cacheId, $fallback);
				break;
			case 'none':
				import('cache.GenericCache');
				$cache =& new GenericCache(
					$context, $cacheId, $fallback
				);
				break;
			default:
				die ("Unknown cache type \"$cacheType\"!\n");
				break;
		}
		return $cache;
	}

	function getFileCachePath() {
		return Core::getBaseDir() . '/cache';
	}

	/**
	 * Flush an entire context, if specified, or
	 * the whole cache.
	 */
	function flush($context = null) {
		$cacheType = Config::getVar('cache','cache');
		switch ($cacheType) {
			case 'memcache':
				// There is no(t yet) selective flushing in memcache;
				// invalidate the whole thing.
				$junkCache =& $this->getCache(null, null, null);
				$junkCache->flush();
				break;
			case 'file':
				$filePath = $this->getFileCachePath();
				$files = glob("$filePath/fc-" . (isset($context)?$context . '-':'') . '*.php');
				foreach ($files as $file) {
					unlink ($file);
				}
				break;
			case 'none':
				// Nothing necessary.
				break;
			default:
				die ("Unknown cache type \"$cacheType\"!\n");
		}
	}
}

?>
