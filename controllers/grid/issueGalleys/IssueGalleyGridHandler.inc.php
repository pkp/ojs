<?php

/**
 * @file controllers/grid/issueGalleys/IssueGalleyGridHandler.inc.php
 *
 * Copyright (c) 2000-2013 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class IssueGalleyGridHandler
 * @ingroup controllers_grid_issues
 *
 * @brief Handle issues grid requests.
 */

import('lib.pkp.classes.controllers.grid.GridHandler');
import('controllers.grid.issueGalleys.IssueGalleyGridRow');

class IssueGalleyGridHandler extends GridHandler {
	/**
	 * Constructor
	 */
	function IssueGalleyGridHandler() {
		parent::GridHandler();
		$this->addRoleAssignment(
			array(ROLE_ID_EDITOR, ROLE_ID_MANAGER),
			array(
				'fetchGrid', 'fetchRow'
			)
		);
	}


	//
	// Implement template methods from PKPHandler
	//
	/**
	 * @see PKPHandler::authorize()
	 * @param $request PKPRequest
	 * @param $args array
	 * @param $roleAssignments array
	 */
	function authorize(&$request, &$args, $roleAssignments) {
		import('lib.pkp.classes.security.authorization.PkpContextAccessPolicy');
		$this->addPolicy(new PkpContextAccessPolicy($request, $roleAssignments));

		import('classes.security.authorization.OjsIssueRequiredPolicy');
		$this->addPolicy(new OjsIssueRequiredPolicy($request, $args));

		// If a signoff ID was specified, authorize it.
		if ($request->getUserVar('issueGalleyId')) {
			import('classes.security.authorization.OjsIssueGalleyRequiredPolicy');
			$this->addPolicy(new OjsIssueGalleyRequiredPolicy($request, $args));
		}

		return parent::authorize($request, $args, $roleAssignments);
	}

	/**
	 * @see PKPHandler::initialize()
	 */
	function initialize(&$request, $args) {
		parent::initialize($request, $args);

		// Add action
		$router = $request->getRouter();
		import('lib.pkp.classes.linkAction.request.AjaxModal');
		$this->addAction(
			new LinkAction(
				'addIssueGalley',
				new AjaxModal(
					$router->url($request, null, null, 'add', null, array('gridId' => $this->getId())),
					__('grid.action.addIssueGalley'),
					'modal_add'
				),
				__('grid.action.addIssueGalley'),
				'add_category'
			)
		);

		// Grid columns.
		import('controllers.grid.issueGalleys.IssueGalleyGridCellProvider');
		$issueGalleyGridCellProvider = new IssueGalleyGridCellProvider();

		// Issue identification
		$this->addColumn(
			new GridColumn(
				'identification',
				'issue.issue',
				null,
				'controllers/grid/gridCell.tpl',
				$issueGalleyGridCellProvider
			)
		);
	}

	/**
	 * Get the row handler - override the default row handler
	 * @return IssueGalleyGridRow
	 */
	function getRowInstance() {
		return new IssueGalleyGridRow();
	}

	//
	// Public operations
	//
	/**
	 * An action to add a new issue
	 * @param $args array
	 * @param $request PKPRequest
	 */
	function addIssueGalley($args, $request) {
		// Calling editIssueData with an empty ID will add
		// a new issue.
		return $this->editIssueGalley($args, $request);
	}

	/**
	 * An action to edit a issue's identifying data
	 * @param $args array
	 * @param $request PKPRequest
	 * @return string Serialized JSON object
	 */
	function editIssueGalley($args, $request) {
		$issue = $this->getAuthorizedContextObject(ASSOC_TYPE_ISSUE);

		import('controllers.grid.issues.form.IssueGalleyForm');
		$issueGalleyForm = new IssueGalleyForm($issue);
		$issueGalleyForm->initData($request);
		$json = new JSONMessage(true, $issueGalleyForm->fetch($request));
		return $json->getString();
	}

	/**
	 * An action to upload an issue galley file.
	 * @param $args array
	 * @param $request PKPRequest
	 * @return string Serialized JSON object
	 */
	function uploadFile($args, $request) {
		$user = $request->getUser();

		import('lib.pkp.classes.file.TemporaryFileManager');
		$temporaryFileManager = new TemporaryFileManager();
		$temporaryFile = $temporaryFileManager->handleUpload('uploadedFile', $user->getId());
		if ($temporaryFile) {
			$json = new JSONMessage(true);
			$json->setAdditionalAttributes(array(
				'temporaryFileId' => $temporaryFile->getId()
			));
		} else {
			$json = new JSONMessage(false, __('common.uploadFailed'));
		}

		return $json->getString();
	}

	/**
	 * Update a issue
	 * @param $args array
	 * @param $request PKPRequest
	 * @return string Serialized JSON object
	 */
	function updateIssueGalley($args, $request) {
		$issue = $this->getAuthorizedContextObject(ASSOC_TYPE_ISSUE);

		import('controllers.grid.issues.form.IssueGalleyForm');
		$issueGalleyForm = new IssueGalleyForm($issue);
		$issueGalleyForm->readInputData();

		if ($issueGalleyForm->validate($request)) {
			$issueId = $issueGalleyForm->execute($request);
			return DAO::getDataChangedEvent($issueId);
		} else {
			$json = new JSONMessage(false);
			return $json->getString();
		}
	}

	/**
	 * Removes an issue galley
	 * @param $args array
	 * @param $request PKPRequest
	 */
	function delete($args, $request) {
		$issue = $this->getAuthorizedContextObject(ASSOC_TYPE_ISSUE);
		$issueId = $issue->getId();

		die('FIXME Unimplemented');

		return DAO::getDataChangedEvent($issueId);
	}
}

?>
