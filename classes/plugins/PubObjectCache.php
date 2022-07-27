<?php

/**
 * @file classes/plugins/PubObjectCache.inc.php
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2003-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class PubObjectCache
 * @ingroup plugins
 *
 * @brief A cache for publication objects required during export.
 */

namespace APP\plugins;

use APP\issue\Issue;

use APP\submission\Submission;
use PKP\galley\Galley;
use PKP\submission\Genre;

class PubObjectCache
{
    /** @var array */
    public $_objectCache = [];


    //
    // Public API
    //
    /**
     * Add a publishing object to the cache.
     *
     * @param Issue|Submission|Galley $object
     * @param Submission|null $parent Only required when adding a galley.
     */
    public function add($object, $parent)
    {
        if ($object instanceof Issue) {
            $this->_insertInternally($object, 'issues', $object->getId());
        }
        if ($object instanceof Submission) {
            $this->_insertInternally($object, 'articles', $object->getId());
            $this->_insertInternally($object, 'articlesByIssue', $object->getcurrentPublication()->getData('issueId'), $object->getId());
        }
        if ($object instanceof Galley) {
            assert($parent instanceof Submission);
            $this->_insertInternally($object, 'galleys', $object->getId());
            $this->_insertInternally($object, 'galleysByArticle', $object->getData('submissionId'), $object->getId());
            $this->_insertInternally($object, 'galleysByIssue', $parent->getIssueId(), $object->getId());
        }
        if ($object instanceof Genre) {
            $this->_insertInternally($object, 'genres', $object->getId());
        }
    }

    /**
     * Marks the given cache id "complete", i.e. it
     * contains all child objects for the given object
     * id.
     *
     * @param string $cacheId
     * @param string $objectId
     */
    public function markComplete($cacheId, $objectId)
    {
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
     * @param string $cacheId
     * @param int $id1
     * @param int $id2
     *
     */
    public function get($cacheId, $id1, $id2 = null)
    {
        assert($this->isCached($cacheId, $id1, $id2));
        if (is_null($id2)) {
            $returner = $this->_objectCache[$cacheId][$id1];
            if (is_array($returner)) {
                unset($returner['complete']);
            }
            return $returner;
        } else {
            return $this->_objectCache[$cacheId][$id1][$id2];
        }
    }

    /**
     * Check whether a given object is in the cache.
     *
     * @param string $cacheId
     * @param int $id1
     * @param int $id2
     *
     * @return bool
     */
    public function isCached($cacheId, $id1, $id2 = null)
    {
        if (!isset($this->_objectCache[$cacheId])) {
            return false;
        }

        $id1 = (int)$id1;
        if (is_null($id2)) {
            if (!isset($this->_objectCache[$cacheId][$id1])) {
                return false;
            }
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
     * @param object $object
     * @param string $cacheId
     * @param int $id1
     * @param int $id2
     */
    public function _insertInternally($object, $cacheId, $id1, $id2 = null)
    {
        if ($this->isCached($cacheId, $id1, $id2)) {
            return;
        }

        if (!isset($this->_objectCache[$cacheId])) {
            $this->_objectCache[$cacheId] = [];
        }

        $id1 = (int)$id1;
        if (is_null($id2)) {
            $this->_objectCache[$cacheId][$id1] = $object;
        } else {
            $id2 = (int)$id2;
            if (!isset($this->_objectCache[$cacheId][$id1])) {
                $this->_objectCache[$cacheId][$id1] = [];
            }
            $this->_objectCache[$cacheId][$id1][$id2] = $object;
        }
    }
}

if (!PKP_STRICT_MODE) {
    class_alias('\APP\plugins\PubObjectCache', '\PubObjectCache');
}
