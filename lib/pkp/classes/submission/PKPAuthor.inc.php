<?php

/**
 * @file classes/submission/PKPAuthor.inc.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2000-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class PKPAuthor
 * @ingroup submission
 * @see PKPAuthorDAO
 *
 * @brief Author metadata class.
 */

import('lib.pkp.classes.identity.Identity');

class PKPAuthor extends Identity {
	/**
	 * Constructor.
	 */
	function __construct() {
		parent::__construct();
	}

	//
	// Get/set methods
	//

	/**
	 * Get ID of submission.
	 * @return int
	 */
	function getSubmissionId() {
		return $this->getData('submissionId');
	}

	/**
	 * Set ID of submission.
	 * @param $submissionId int
	 */
	function setSubmissionId($submissionId) {
		$this->setData('submissionId', $submissionId);
	}

	/**
	 * Set the user group id
	 * @param $userGroupId int
	 */
	function setUserGroupId($userGroupId) {
		$this->setData('userGroupId', $userGroupId);
	}

	/**
	 * Get the user group id
	 * @return int
	 */
	function getUserGroupId() {
		return $this->getData('userGroupId');
	}

	/**
	 * Set whether or not to include in browse lists.
	 * @param $include boolean
	 */
	function setIncludeInBrowse($include) {
		$this->setData('includeInBrowse', $include);
	}

	/**
	 * Get whether or not to include in browse lists.
	 * @return boolean
	 */
	function getIncludeInBrowse() {
		return $this->getData('includeInBrowse');
	}

	/**
	 * Get the "show title" flag (whether or not the title of the role
	 * should be included in the list of submission contributor names).
	 * This is fetched from the user group for performance reasons.
	 * @return boolean
	 */
	function getShowTitle() {
		return $this->getData('showTitle');
	}

	/**
	 * Set the "show title" flag. This attribute belongs to the user group,
	 * NOT the author; fetched for performance reasons only.
	 * @param $isDefault boolean
	 */
	function _setShowTitle($showTitle) {
		$this->setData('showTitle', $showTitle);
	}

	/**
	 * Get primary contact.
	 * @return boolean
	 */
	function getPrimaryContact() {
		return $this->getData('primaryContact');
	}

	/**
	 * Set primary contact.
	 * @param $primaryContact boolean
	 */
	function setPrimaryContact($primaryContact) {
		$this->setData('primaryContact', $primaryContact);
	}

	/**
	 * Get sequence of author in submissions' author list.
	 * @return float
	 */
	function getSequence() {
		return $this->getData('sequence');
	}

	/**
	 * Set sequence of author in submissions' author list.
	 * @param $sequence float
	 */
	function setSequence($sequence) {
		$this->setData('sequence', $sequence);
	}

	/**
	 * Get the user group for this contributor.
	 */
	function getUserGroup() {
		//FIXME: should this be queried when fetching Author from DB? - see #5231.
		static $userGroup; // Frequently we'll fetch the same one repeatedly
		if (!$userGroup || $this->getUserGroupId() != $userGroup->getId()) {
			$userGroupDao = DAORegistry::getDAO('UserGroupDAO');
			$userGroup = $userGroupDao->getById($this->getUserGroupId());
		}
		return $userGroup;
	}

	/**
	 * Get a localized version of the User Group
	 * @return string
	 */
	function getLocalizedUserGroupName() {
		$userGroup = $this->getUserGroup();
		return $userGroup->getLocalizedName();
	}
}

?>
