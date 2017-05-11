<?php
/**
 * @file classes/security/authorization/internal/SubmissionFileBaseAccessPolicy.inc.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2000-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class SubmissionFileBaseAccessPolicy
 * @ingroup security_authorization_internal
 *
 * @brief Abstract class for submission file access policies.
 *
 */

import('lib.pkp.classes.security.authorization.AuthorizationPolicy');

class SubmissionFileBaseAccessPolicy extends AuthorizationPolicy {
	/** @var PKPRequest */
	var $_request;

	/** @var string File id and revision, separated with a dash (e.g. 15-1) */
	var $_fileIdAndRevision;

	/**
	 * Constructor
	 * @param $request PKPRequest
	 * @param $fileIdAndRevision string If passed, this policy will try to
	 * get the submission file from this data.
	 */
	function __construct($request, $fileIdAndRevision = null) {
		parent::__construct('user.authorization.submissionFile');
		$this->_request = $request;
		$this->_fileIdAndRevision = $fileIdAndRevision;
	}


	//
	// Private methods
	//
	/**
	 * Get a cache of submission files. Used because many policy subclasses
	 * may be combined to fetch a single submission file.
	 * @return array
	 */
	function &_getCache() {
		static $cache;
		if (!isset($cache)) $cache = array();
		return $cache;
	}


	//
	// Protected methods
	//
	/**
	 * Get the requested submission file.
	 * @param $request PKPRequest
	 * @return SubmissionFile
	 */
	function getSubmissionFile($request) {
		// Try to get the submission file info.
		$fileIdAndRevision = $this->_fileIdAndRevision;
		if (!is_null($fileIdAndRevision)) {
			$fileData = explode('-', $fileIdAndRevision);
			$fileId = (int) $fileData[0];
			$revision = isset($fileData[1]) ? (int) $fileData[1] : 0; // -0 for most recent revision
			$cacheId = $fileIdAndRevision;
		} else {
			// Get the identifying info from the request
			$fileId = (int) $request->getUserVar('fileId');
			$revision = (int) $request->getUserVar('revision');
			assert($fileId>0);
			$cacheId = "$fileId-$revision"; // -0 for most recent revision
		}

		// Fetch the object, caching if possible
		$cache =& $this->_getCache();
		if (!isset($cache[$cacheId])) {
			// Cache miss
			$submissionFileDao = DAORegistry::getDAO('SubmissionFileDAO');
			if ($revision) {
				$cache[$cacheId] = $submissionFileDao->getRevision($fileId, $revision);
			} else {
				$cache[$cacheId] = $submissionFileDao->getLatestRevision($fileId);
			}
		}

		return $cache[$cacheId];
	}

	/**
	 * Get the current request object.
	 * @return PKPRequest
	 */
	function getRequest() {
		return $this->_request;
	}
}

?>
