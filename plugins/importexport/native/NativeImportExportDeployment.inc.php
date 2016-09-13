<?php

/**
 * @file plugins/importexport/native/NativeImportExportDeployment.inc.php
 *
 * Copyright (c) 2014-2016 Simon Fraser University Library
 * Copyright (c) 2000-2016 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class NativeImportExportDeployment
 * @ingroup plugins_importexport_native
 *
 * @brief Class configuring the native import/export process to this
 * application's specifics.
 */

import('lib.pkp.plugins.importexport.native.PKPNativeImportExportDeployment');

class NativeImportExportDeployment extends PKPNativeImportExportDeployment {

	var $_issue;

	/**
	 * Constructor
	 * @param $context Context
	 * @param $user User
	 */
	function NativeImportExportDeployment($context, $user) {
		parent::PKPNativeImportExportDeployment($context, $user);
	}

	//
	// Deploymenturation items for subclasses to override
	//
	/**
	 * Get the submission node name
	 * @return string
	 */
	function getSubmissionNodeName() {
		return 'article';
	}

	/**
	 * Get the submissions node name
	 * @return string
	 */
	function getSubmissionsNodeName() {
		return 'articles';
	}

	/**
	 * Get the representation node name
	 */
	function getRepresentationNodeName() {
		return 'article_galley';
	}

	/**
	 * Get the schema filename.
	 * @return string
	 */
	function getSchemaFilename() {
		return 'native.xsd';
	}

	/**
	 * Set the import/export issue.
	 * @param $issue Issue
	 */
	function setIssue($issue) {
		$this->_issue = $issue;
	}

	/**
	 * Get the import/export issue.
	 * @return Issue
	 */
	function getIssue() {
		return $this->_issue;
	}
}

?>
