<?php
/**
 * @defgroup plugins_importexport_native Native import/export plugin
 */

/**
 * @file plugins/importexport/native/PKPNativeImportExportDeployment.inc.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2000-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class PKPNativeImportExportDeployment
 * @ingroup plugins_importexport_native
 *
 * @brief Base class configuring the native import/export process to an
 * application's specifics.
 */

import('lib.pkp.classes.plugins.importexport.PKPImportExportDeployment');

class PKPNativeImportExportDeployment extends PKPImportExportDeployment {
	/**
	 * Constructor
	 * @param $context Context
	 * @param $user User
	 */
	function __construct($context, $user) {
		parent::__construct($context, $user);
	}

	//
	// Deployment items for subclasses to override
	//
	/**
	 * Get the submission node name
	 * @return string
	 */
	function getSubmissionNodeName() {
		return 'submission';
	}

	/**
	 * Get the submissions node name
	 * @return string
	 */
	function getSubmissionsNodeName() {
		return 'submissions';
	}

	/**
	 * Get the namespace URN
	 * @return string
	 */
	function getNamespace() {
		return 'http://pkp.sfu.ca';
	}

	/**
	 * Get the schema filename.
	 * @return string
	 */
	function getSchemaFilename() {
		return 'pkp-native.xsd';
	}

	/**
	 * Get the mapping between stage names in XML and their numeric consts
	 * @return array
	 */
	function getStageNameStageIdMapping() {
		import('lib.pkp.classes.submission.SubmissionFile'); // Get file constants
		return array(
			'public' => SUBMISSION_FILE_PUBLIC,
			'submission' => SUBMISSION_FILE_SUBMISSION,
			'note' => SUBMISSION_FILE_NOTE,
			'review_file' => SUBMISSION_FILE_REVIEW_FILE,
			'review_attachment' => SUBMISSION_FILE_REVIEW_ATTACHMENT,
			'final' => SUBMISSION_FILE_FINAL,
			'fair_copy' => SUBMISSION_FILE_FAIR_COPY,
			'editor' => SUBMISSION_FILE_EDITOR,
			'copyedit' => SUBMISSION_FILE_COPYEDIT,
			'proof' => SUBMISSION_FILE_PROOF,
			'production_ready' => SUBMISSION_FILE_PRODUCTION_READY,
			'attachment' => SUBMISSION_FILE_ATTACHMENT,
			'review_revision' => SUBMISSION_FILE_REVIEW_REVISION,
			'dependent' => SUBMISSION_FILE_DEPENDENT,
			'query' => SUBMISSION_FILE_QUERY,
		);
	}
}

?>
