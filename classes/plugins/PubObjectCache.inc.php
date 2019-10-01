<?php

/**
 * @file classes/plugins/PubObjectCache.inc.php
 *
 * Copyright (c) 2014-2019 Simon Fraser University
 * Copyright (c) 2003-2019 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class PubObjectCache
 * @ingroup plugins
 *
 * @brief A cache for publication objects required during export.
 */


class PubObjectCache {
	/* @var array */
	var $_objectCache = array();


	//
	// Public API
	//
	/**
	 * Add a publishing object to the cache.
	 * @param $object Issue|PublishedArticle|ArticleGalley
	 * @param $parent PublishedArticle|null Only required when adding a galley.
	 */
	function add($object, $parent) {
		if (is_a($object, 'Issue')) {
			$this->_insertInternally($object, 'issues', $object->getId());
		}
		if (is_a($object, 'PublishedArticle')) {
			$this->_insertInternally($object, 'articles', $object->getId());
			$this->_insertInternally($object, 'articlesByIssue', $object->getIssueId(), $object->getId());
		}
		if (is_a($object, 'ArticleGalley')) {
			assert(is_a($parent, 'PublishedArticle'));
			$this->_insertInternally($object, 'galleys', $object->getId());
			$this->_insertInternally($object, 'galleysByArticle', $object->getSubmissionId(), $object->getId());
			$this->_insertInternally($object, 'galleysByIssue', $parent->getIssueId(), $object->getId());
		}
	}

	/**
	 * Marks the given cache id "complete", i.e. it
	 * contains all child objects for the given object
	 * id.
	 *
	 * @param $cacheId
	 * @param $objectId
	 */
	function markComplete($cacheId, $objectId) {
		assert(is_array($this->_objectCache[$cacheId][$objectId]));
		$this->_objectCache[$cacheId][$objectId]['complete'] = true;

		// Order objects in the completed cache by ID.
		ksort($this->_objectCache[$cacheId][$objectId]);
	}

	/**
	 * Retrieve (an) object(s) from the cache.
	 *
	 * NB: You must check whether an object is in the cache
	 * before you try to retrieve it with this method.
	 *
	 * @param $cacheId string
	 * @param $id1 integer
	 * @param $id2 integer
	 *
	 * @return mixed
	 */
	function get($cacheId, $id1, $id2 = null) {
		assert($this->isCached($cacheId, $id1, $id2));
		if (is_null($id2)) {
			$returner = $this->_objectCache[$cacheId][$id1];
			if (is_array($returner)) unset($returner['complete']);
			return $returner;
		} else {
			return $this->_objectCache[$cacheId][$id1][$id2];
		}
	}

	/**
	 * Check whether a given object is in the cache.
	 *
	 * @param $cacheId string
	 * @param $id1 integer
	 * @param $id2 integer
	 *
	 * @return boolean
	 */
	function isCached($cacheId, $id1, $id2 = null) {
		if (!isset($this->_objectCache[$cacheId])) return false;

		$id1 = (int)$id1;
		if (is_null($id2)) {
			if (!isset($this->_objectCache[$cacheId][$id1])) return false;
			if (is_array($this->_objectCache[$cacheId][$id1])) {
				return isset($this->_objectCache[$cacheId][$id1]['complete']);
			} else {
				return true;
			}
		} else {
			$id2 = (int)$id2;
			return isset($this->_objectCache[$cacheId][$id1][$id2]);
		}
	}


	//
	// Private helper methods
	//
	/**
	 * Insert an object into the cache.
	 *
	 * @param $object object
	 * @param $cacheId string
	 * @param $id1 integer
	 * @param $id2 integer
	 */
	function _insertInternally($object, $cacheId, $id1, $id2 = null) {
		if ($this->isCached($cacheId, $id1, $id2)) return;

		if (!isset($this->_objectCache[$cacheId])) {
			$this->_objectCache[$cacheId] = array();
		}

		$id1 = (int)$id1;
		if (is_null($id2)) {
			$this->_objectCache[$cacheId][$id1] = $object;
		} else {
			$id2 = (int)$id2;
			if (!isset($this->_objectCache[$cacheId][$id1])) {
				$this->_objectCache[$cacheId][$id1] = array();
			}
			$this->_objectCache[$cacheId][$id1][$id2] = $object;
		}
	}
}


