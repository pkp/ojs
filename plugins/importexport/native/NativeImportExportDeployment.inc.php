<?php

/**
 * @file plugins/importexport/native/NativeImportExportDeployment.inc.php
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2000-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
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
	function __construct($context, $user) {
		parent::__construct($context, $user);
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

	/**
	 * @see PKPNativeImportExportDeployment::getObjectTypes()
	 */
	protected function getObjectTypes() {
		$objectTypes = parent::getObjectTypes();
		AppLocale::requireComponents(LOCALE_COMPONENT_APP_EDITOR);
		$objectTypes = $objectTypes + array(
				ASSOC_TYPE_JOURNAL => __('context.context'),
				ASSOC_TYPE_SECTION => __('section.section'),
				ASSOC_TYPE_ISSUE => __('issue.issue'),
				ASSOC_TYPE_ISSUE_GALLEY => __('editor.issues.galley'),
				ASSOC_TYPE_PUBLICATION => __('common.publication'),
		);

		return $objectTypes;
	}
}


