<?php
/**
 * @file classes/security/authorization/SubmissionFileAccessPolicy.inc.php
 *
 * Copyright (c) 2014-2015 Simon Fraser University Library
 * Copyright (c) 2000-2015 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class SubmissionFileAccessPolicy
 * @ingroup security_authorization
 *
 * @brief Class to control (write) access to submissions and (read) access to
 * submission details in OJS.
 */

import('lib.pkp.classes.security.authorization.PKPSubmissionFileAccessPolicy');

class SubmissionFileAccessPolicy extends PKPSubmissionFileAccessPolicy {
	/**
	 * Constructor
	 * @param $request PKPRequest
	 * @param $args array request parameters
	 * @param $roleAssignments array
	 * @param $mode int bitfield SUBMISSION_FILE_ACCESS_...
	 * @param $fileIdAndRevision string
	 * @param $submissionParameterName string the request parameter we expect
	 *  the submission id in.
	 */
	function SubmissionFileAccessPolicy($request, $args, $roleAssignments, $mode, $fileIdAndRevision = null, $submissionParameterName = 'submissionId') {
		parent::PKPSubmissionFileAccessPolicy($request, $args, $roleAssignments, $mode, $fileIdAndRevision, $submissionParameterName);

		$fileAccessPolicy = $this->_baseFileAccessPolicy;
		//
		// Series editor role
		//
		if (isset($roleAssignments[ROLE_ID_SUB_EDITOR])) {
			// 1) Series editors can access all operations on submissions ...
			$sectionEditorFileAccessPolicy = new PolicySet(COMBINING_DENY_OVERRIDES);
			$sectionEditorFileAccessPolicy->addPolicy(new RoleBasedHandlerOperationPolicy($request, ROLE_ID_SUB_EDITOR, $roleAssignments[ROLE_ID_SUB_EDITOR]));

			// 2) ... but only if the requested submission is part of their section.
			import('classes.security.authorization.internal.SectionAssignmentPolicy');
			$sectionEditorFileAccessPolicy->addPolicy(new SectionAssignmentPolicy($request));
			$fileAccessPolicy->addPolicy($sectionEditorFileAccessPolicy);
		}

		$this->addPolicy($fileAccessPolicy);
	}
}

?>
