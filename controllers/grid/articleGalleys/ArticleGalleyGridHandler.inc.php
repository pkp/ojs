<?php

/**
 * @file controllers/grid/articleGalleys/ArticleGalleyGridHandler.inc.php
 *
 * Copyright (c) 2016 Simon Fraser University Library
 * Copyright (c) 2000-2016 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class ArticleGalleyGridHandler
 * @ingroup controllers_grid_articleGalleys
 *
 * @brief Handle article galley grid requests.
 */

// import grid base classes
import('lib.pkp.classes.controllers.grid.GridHandler');

// Link action & modal classes
import('lib.pkp.classes.linkAction.request.AjaxModal');

class ArticleGalleyGridHandler extends GridHandler {

	/** @var PKPRequest */
	var $_request;

	/**
	 * Constructor
	 */
	function ArticleGalleyGridHandler() {
		parent::GridHandler();
		$this->addRoleAssignment(
			array(ROLE_ID_MANAGER, ROLE_ID_SUB_EDITOR, ROLE_ID_ASSISTANT),
			array('fetchGrid', 'fetchRow', 'addGalley', 'editGalley', 'updateGalley', 'deleteGalley'));
	}


	//
	// Getters/Setters
	//
	/**
	 * Get the authorized submission.
	 * @return Submission
	 */
	function getSubmission() {
		return $this->getAuthorizedContextObject(ASSOC_TYPE_SUBMISSION);
	}

	/**
	 * Get the authorized galley.
	 * @return ArticleGalley
	 */
	function getGalley() {
		return $this->getAuthorizedContextObject(ASSOC_TYPE_REPRESENTATION);
	}


	//
	// Overridden methods from PKPHandler.
	//
	/**
	 * @see GridHandler::getJSHandler()
	 */
	public function getJSHandler() {
		return '$.pkp.controllers.grid.articleGalleys.ArticleGalleyGridHandler';
	}

	/**
	 * @copydoc PKPHandler::authorize()
	 */
	function authorize($request, &$args, $roleAssignments) {
		$this->_request = $request;

		import('lib.pkp.classes.security.authorization.WorkflowStageAccessPolicy');
		$this->addPolicy(new WorkflowStageAccessPolicy($request, $args, $roleAssignments, 'submissionId', WORKFLOW_STAGE_ID_PRODUCTION));

		if ($request->getUserVar('representationId')) {
			import('lib.pkp.classes.security.authorization.internal.RepresentationRequiredPolicy');
			$this->addPolicy(new RepresentationRequiredPolicy($request, $args));
		}

		return parent::authorize($request, $args, $roleAssignments);
	}

	/**
	 * Configure the grid
	 * @param $request PKPRequest
	 */
	function initialize($request) {
		parent::initialize($request);
		$this->setTitle('submission.layout.galleys');

		// Load pkp-lib translations
		AppLocale::requireComponents(
			LOCALE_COMPONENT_PKP_SUBMISSION,
			LOCALE_COMPONENT_PKP_USER,
			LOCALE_COMPONENT_PKP_EDITOR,
			LOCALE_COMPONENT_APP_EDITOR
		);

		import('controllers.grid.articleGalleys.ArticleGalleyGridCellProvider');
		$cellProvider = new ArticleGalleyGridCellProvider($this->getSubmission());

		// Columns
		$this->addColumn(new GridColumn(
			'label',
			'common.name',
			null,
			null,
			$cellProvider
		));

		$router = $request->getRouter();
		$this->addAction(new LinkAction(
			'addGalley',
			new AjaxModal(
				$router->url($request, null, null, 'addGalley', null, $this->getRequestArgs()),
				__('submission.layout.newGalley'),
				'modal_add_item'
			),
			__('grid.action.addGalley'),
			'add_item'
		));
	}


	//
	// Overridden methods from GridHandler
	//
	/**
	 * @copydoc GridHandler::getRowInstance()
	 * @return ArticleGalleyGridRow
	 */
	function getRowInstance() {
		import('controllers.grid.articleGalleys.ArticleGalleyGridRow');
		return new ArticleGalleyGridRow(
			$this->getSubmission()
		);
	}

	/**
	 * Get the arguments that will identify the data in the grid.
	 * Overridden by child grids.
	 * @return array
	 */
	function getRequestArgs() {
		return array(
			'submissionId' => $this->getSubmission()->getId(),
		);
	}

	/**
	 * @copydoc GridHandler::loadData()
	 */
	function loadData($request, $filter = null) {
		$galleyDao = DAORegistry::getDAO('ArticleGalleyDAO');
		return $galleyDao->getBySubmissionId($this->getSubmission()->getId());
	}

	//
	// Public Galley Grid Actions
	//
	/**
	 * Add a galley
	 * @param $args array
	 * @param $request PKPRequest
	 * @return JSONMessage JSON object
	 */
	function addGalley($args, $request) {
		import('controllers.grid.articleGalleys.form.ArticleGalleyForm');
		$galleyForm = new ArticleGalleyForm(
			$request,
			$this->getSubmission()
		);
		$galleyForm->initData();
		return new JSONMessage(true, $galleyForm->fetch($request, $this->getRequestArgs()));
	}



	/**
	 * Delete a galley.
	 * @param $args array
	 * @param $request PKPRequest
	 * @return JSONMessage JSON object
	 */
	function deleteGalley($args, $request) {
		$galley = $this->getGalley();
		if (!$galley) return new JSONMessage(false);

		$galleyDao = DAORegistry::getDAO('ArticleGalleyDAO');
		$galleyDao->deleteObject($galley);

		if ($galley->getFileId()) {
			$submissionFileDao = DAORegistry::getDAO('SubmissionFileDAO');
			$submissionFileDao->deleteAllRevisionsById($galley->getFileId());
		}

		$notificationDao = DAORegistry::getDAO('NotificationDAO');
		$notificationDao->deleteByAssoc(ASSOC_TYPE_REPRESENTATION, $galley->getId());

		return DAO::getDataChangedEvent($galley->getId());
	}

	/**
	 * Edit a galley
	 * @param $args array
	 * @param $request PKPRequest
	 * @return JSONMessage JSON object
	 */
	function editGalley($args, $request) {
		// Form handling
		import('controllers.grid.articleGalleys.form.ArticleGalleyForm');
		$galleyForm = new ArticleGalleyForm(
			$request,
			$this->getSubmission(),
			$this->getGalley()
		);
		$galleyForm->initData();
		return new JSONMessage(true, $galleyForm->fetch($request, $this->getRequestArgs()));
	}

	/**
	 * Save a galley
	 * @param $args array
	 * @param $request PKPRequest
	 * @return JSONMessage JSON object
	 */
	function updateGalley($args, $request) {
		$galley = $this->getGalley();

		import('controllers.grid.articleGalleys.form.ArticleGalleyForm');
		$galleyForm = new ArticleGalleyForm($request, $this->getSubmission(), $galley);
		$galleyForm->readInputData();

		if ($galleyForm->validate($request)) {
			$galley = $galleyForm->execute($request);
			return DAO::getDataChangedEvent($galley->getId());
		}
		return new JSONMessage(true, $galleyForm->fetch());
	}
	
	/**
	 * @copydoc GridHandler::fetchRow()
	 */
	function fetchRow($args, $request) {
		$json = parent::fetchRow($args, $request);
		if ($row = $this->getRequestedRow($request, $args)) {
			$galley = $row->getData();
			if ($galley->getRemoteUrl()=='' && !$galley->getFileId()) {
				$json->setEvent('uploadFile', $galley->getId());
			}
		}

		return $json;
	}
}

?>
