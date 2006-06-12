<?php

/**
 * HelpTocDAO.inc.php
 *
 * Copyright (c) 2003-2006 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package help
 *
 * Class for HelpToc DAO.
 * Operations for retrieving HelpToc objects.
 *
 * $Id$
 */

import('help.HelpToc');

class HelpTocDAO extends XMLDAO {

	/**
	 * Constructor.
	 */
	function HelpTocDAO() {
		parent::XMLDAO();
	}

	function &_getCache($tocId) {
		static $cache;
		if (!isset($cache)) {
			import('cache.CacheManager');
			$cacheManager =& CacheManager::getManager();
			$cache =& $cacheManager->getFileCache('help-toc-' . Help::getLocale(), $tocId, array($this, '_cacheMiss'));

			// Check to see if the cache info is outdated.
			$cacheTime = $cache->getCacheTime();
			if ($cacheTime !== null && $cacheTime < filemtime($this->getFilename($tocId))) {
				// The cached data is out of date.
				$cache->flush();
			}
		}
		return $cache;
	}

	function _cacheMiss(&$cache, $id) {
		static $data;
		if (!isset($data)) {
			$helpFile = $this->getFilename($cache->getCacheId());
			$data = &$this->parseStruct($helpFile);

			// check if data exists before saving it to cache
			if ($data === false) {
				$returner = false;
				return $returner;
			}
			$cache->setEntireCache($data);
		}
		return null;
	}
	
	function getFilename($tocId) {
		return sprintf('help/%s/%s.xml', Help::getLocale(), $tocId);
	}

	/**
	 * Retrieves a toc by its ID.
	 * @param $tocId string
	 * @return HelpToc
	 */
	function &getToc($tocId) {
		$cache =& $this->_getCache($tocId);
		$data = $cache->getContents();

		// check if data exists after loading
		if (!is_array($data)) {
			$returner = false;
			return $returner;
		}

		$toc = &new HelpToc();

		$toc->setId($data['toc'][0]['attributes']['id']);
		$toc->setTitle($data['toc'][0]['attributes']['title']);
		if (isset($data['toc'][0]['attributes']['parent_topic'])) {
			$toc->setParentTopicId($data['toc'][0]['attributes']['parent_topic']);
		}

		if (isset($data['topic'])) {
			foreach ($data['topic'] as $topicData) {
				$topic = &new HelpTopic();
				$topic->setId($topicData['attributes']['id']);
				$topic->setTitle($topicData['attributes']['title']);
				$toc->addTopic($topic);
			}
		}

		if (isset($data['breadcrumb'])) {
			foreach ($data['breadcrumb'] as $breadcrumbData) {
				$toc->addBreadcrumb($breadcrumbData['attributes']['title'], $breadcrumbData['attributes']['url']);
			}
		}

		return $toc;
	}
}

?>
