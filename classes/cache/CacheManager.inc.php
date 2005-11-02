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
	var $cacheConfig;

	function getManager() {
		static $manager;
		if (!isset($manager)) {
			$manager =& new CacheManager();
			$cacheMisses = 0;
			$cacheHits = 0;
			Registry::set('cacheMisses', $cacheMisses);
			Registry::set('cacheHits', $cacheHits);
		}
		return $manager;
	}

	function CacheManager() {
		$this->cacheConfig = array(
			'cache' => Config::getVar('cache','cache'),
			'memcache_hostname' => Config::getVar('cache','memcache_hostname'),
			'memcache_port' => Config::getVar('cache','memcache_port'),
			'file_path' => Config::getVar('cache','file_path')
		);
	}

	function &getCache($context, $cacheId, $fallback) {
		$cacheType = $this->cacheConfig['cache'];
		switch ($cacheType) {
			case 'memcache':
				import('cache.MemcacheCache');
				$cache =& new MemcacheCache(
					$context, $cacheId, $fallback,
					$this->cacheConfig['memcache_hostname'],
					$this->cacheConfig['memcache_port']
				);
				break;
			case 'file':
				import('cache.FileCache');
				$cache =& new FileCache(
					$context, $cacheId, $fallback,
					$this->cacheConfig['file_path']
				);
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

	/**
	 * Flush an entire context, if specified, or
	 * the whole cache.
	 */
	function flush($context = null) {
		$cacheType = $this->cacheConfig['cache'];
		switch ($cacheType) {
			case 'memcache':
				// There is no(t yet) selective flushing in memcache;
				// invalidate the whole thing.
				$junkCache =& $this->getCache(null, null, null);
				$junkCache->flush();
				break;
			case 'file':
				$filePath = $this->cacheConfig('file_path');
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
