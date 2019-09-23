<?php

/**
 * @file controllers/grid/articleGalleys/ArticleGalleyGridHandler.inc.php
 *
 * Copyright (c) 2016-2019 Simon Fraser University
 * Copyright (c) 2000-2019 John Willinsky
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
	function __construct() {
		parent::__construct();
		$this->addRoleAssignment(
			array(ROLE_ID_AUTHOR, ROLE_ID_MANAGER, ROLE_ID_SUB_EDITOR, ROLE_ID_ASSISTANT),
			array('fetchGrid', 'fetchRow', 'addGalley', 'editGalley', 'updateGalley', 'deleteGalley', 'identifiers', 'updateIdentifiers', 'clearPubId', 'saveSequence'));
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
	 * Get the authorized publication.
	 * @return Publication
	 */
	function getPublication() {
		return $this->getAuthorizedContextObject(ASSOC_TYPE_PUBLICATION);
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

		import('lib.pkp.classes.security.authorization.PublicationAccessPolicy');
		$this->addPolicy(new PublicationAccessPolicy($request, $args, $roleAssignments));

		if ($request->getUserVar('representationId')) {
			import('lib.pkp.classes.security.authorization.internal.RepresentationRequiredPolicy');
			$this->addPolicy(new RepresentationRequiredPolicy($request, $args));
		}

		return parent::authorize($request, $args, $roleAssignments);
	}

	/**
	 * @copydoc GridHandler::initialize()
	 */
	function initialize($request, $args = null) {
		parent::initialize($request, $args);
		$this->setTitle('submission.layout.galleys');

		// Load pkp-lib translations
		AppLocale::requireComponents(
			LOCALE_COMPONENT_PKP_SUBMISSION,
			LOCALE_COMPONENT_PKP_USER,
			LOCALE_COMPONENT_PKP_EDITOR,
			LOCALE_COMPONENT_APP_EDITOR
		);

		import('controllers.grid.articleGalleys.ArticleGalleyGridCellProvider');
		$cellProvider = new ArticleGalleyGridCellProvider($this->getSubmission(), $this->getPublication(), $this->canEdit());

		// Columns
		$this->addColumn(new GridColumn(
			'label',
			'common.name',
			null,
			null,
			$cellProvider
		));

		
		if ($this->canEdit()) {
			$this->addAction(new LinkAction(
				'addGalley',
				new AjaxModal(
					$request->getRouter()->url($request, null, null, 'addGalley', null, $this->getRequestArgs()),
					__('submission.layout.newGalley'),
					'modal_add_item'
				),
				__('grid.action.addGalley'),
				'add_item'
			));
		}
		
	}

	//
	// Overridden methods from GridHandler
	//
	/**
	 * @copydoc GridHandler::initFeatures()
	 */
	function initFeatures($request, $args) {
		if ($this->canEdit()) {
			import('lib.pkp.classes.controllers.grid.feature.OrderGridItemsFeature');
			return array(new OrderGridItemsFeature());
		}

		return array();
	}

	/**
	 * @copydoc GridHandler::getDataElementSequence()
	 */
	function getDataElementSequence($row) {
		return $row->getSequence();
	}

	/**
	 * @copydoc GridHandler::setDataElementSequence()
	 */
	function setDataElementSequence($request, $rowId, $gridDataElement, $newSequence) {
		$galleyDao = DAORegistry::getDAO('ArticleGalleyDAO');
		$galley = $galleyDao->getById($rowId);
		$galley->setSequence($newSequence);
		$galleyDao->updateObject($galley);
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
			$this->getSubmission(),
			$this->getPublication(),
			$this->canEdit()

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
			'publicationId' => $this->getPublication()->getId(),
		);
	}

	/**
	 * @copydoc GridHandler::loadData()
	 */
	function loadData($request, $filter = null) {
		$galleyDao = DAORegistry::getDAO('ArticleGalleyDAO');
		return $galleyDao->getByPublicationId($this->getPublication()->getId());
	}

	//
	// Public Galley Grid Actions
	//
	/**
	 * Edit article galley pub ids
	 * @param $args array
	 * @param $request PKPRequest
	 * @return JSONMessage JSON object
	 */
	function identifiers($args, $request) {
		$representationDao = Application::getRepresentationDAO();
		$representation = $representationDao->getById($request->getUserVar('representationId'));
		import('controllers.tab.pubIds.form.PublicIdentifiersForm');
		$form = new PublicIdentifiersForm($representation);
		$form->initData();
		return new JSONMessage(true, $form->fetch($request));
	}

	/**
	 * Update article galley pub ids
	 * @param $args array
	 * @param $request PKPRequest
	 * @return JSONMessage JSON object
	 */
	function updateIdentifiers($args, $request) {
		$representationDao = Application::getRepresentationDAO();
		$representation = $representationDao->getById($request->getUserVar('representationId'));
		import('controllers.tab.pubIds.form.PublicIdentifiersForm');
		$form = new PublicIdentifiersForm($representation, null, array_merge($this->getRequestArgs(), ['representationId' => $representation->getId()]));
		$form->readInputData();
		if ($form->validate()) {
			$form->execute();
			return DAO::getDataChangedEvent();
		} else {
			return new JSONMessage(true, $form->fetch($request));
		}
	}

	/**
	 * Clear galley pub id
	 * @param $args array
	 * @param $request PKPRequest
	 * @return JSONMessage JSON object
	 */
	function clearPubId($args, $request) {
		if (!$request->checkCSRF()) return new JSONMessage(false);

		$submission = $this->getSubmission();
		$representationDao = Application::getRepresentationDAO();
		$representation = $representationDao->getById($request->getUserVar('representationId'));
		import('controllers.tab.pubIds.form.PublicIdentifiersForm');
		$form = new PublicIdentifiersForm($representation);
		$form->clearPubId($request->getUserVar('pubIdPlugIn'));
		return new JSONMessage(true);
	}

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
			$this->getSubmission(),
			$this->getPublication()
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
		if (!$galley || !$request->checkCSRF()) return new JSONMessage(false);

		$galleyDao = DAORegistry::getDAO('ArticleGalleyDAO');
		$galleyDao->deleteObject($galley);

		if ($galley->getFileId()) {
			$submissionFileDao = DAORegistry::getDAO('SubmissionFileDAO');
			$submissionFileDao->deleteAllRevisionsById($galley->getFileId());
		}

		$notificationDao = DAORegistry::getDAO('NotificationDAO');
		$notificationDao->deleteByAssoc(ASSOC_TYPE_REPRESENTATION, $galley->getId());

		if ($this->getSubmission()->getStageId() == WORKFLOW_STAGE_ID_EDITING ||
			$this->getSubmission()->getStageId() == WORKFLOW_STAGE_ID_PRODUCTION) {

			$notificationMgr = new NotificationManager();
			$notificationMgr->updateNotification(
				$request,
				array(NOTIFICATION_TYPE_ASSIGN_PRODUCTIONUSER, NOTIFICATION_TYPE_AWAITING_REPRESENTATIONS),
				null,
				ASSOC_TYPE_SUBMISSION,
				$this->getSubmission()->getId()
			);
		}

		//inform search index that file has been deleted
		$articleSearchIndex = Application::getSubmissionSearchIndex();
		$articleSearchIndex->submissionFileDeleted($galley->getSubmissionId());
		$articleSearchIndex->submissionChangesFinished();

		return DAO::getDataChangedEvent($galley->getId());
	}

	/**
	 * Edit a galley metadata modal
	 * @param $args array
	 * @param $request PKPRequest
	 * @return JSONMessage JSON object
	 */
	function editGalley($args, $request) {
		$galley = $this->getGalley();

		// Check if this is a remote galley
		$templateMgr = TemplateManager::getManager($request);
		$templateMgr->assign(array(
			'submissionId' => $this->getSubmission()->getId(),
			'publicationId' => $this->getPublication()->getId(),
			'representationId' => $galley->getId(),
		));
		return new JSONMessage(true, $templateMgr->fetch('controllers/grid/articleGalleys/editFormat.tpl'));
	}

	/**
	 * Edit a galley
	 * @param $args array
	 * @param $request PKPRequest
	 * @return JSONMessage JSON object
	 */
	function editGalleyTab($args, $request) {
		// Form handling
		import('controllers.grid.articleGalleys.form.ArticleGalleyForm');
		$galleyForm = new ArticleGalleyForm(
			$request,
			$this->getSubmission(),
			$this->getPublication(),
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
		$galleyForm = new ArticleGalleyForm($request, $this->getSubmission(), $this->getPublication(), $galley);
		$galleyForm->readInputData();

		if ($galleyForm->validate()) {
			$galley = $galleyForm->execute();

			if ($this->getSubmission()->getStageId() == WORKFLOW_STAGE_ID_EDITING ||
				$this->getSubmission()->getStageId() == WORKFLOW_STAGE_ID_PRODUCTION) {

				$notificationMgr = new NotificationManager();
				$notificationMgr->updateNotification(
					$request,
					array(NOTIFICATION_TYPE_ASSIGN_PRODUCTIONUSER, NOTIFICATION_TYPE_AWAITING_REPRESENTATIONS),
					null,
					ASSOC_TYPE_SUBMISSION,
					$this->getSubmission()->getId()
				);
			}

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

	/**
	 * Can the current user edit the galleys in this grid?
	 *
	 * The user must have an allowed role in one of the assigned stages.
	 * If the user is not assigned, they can edit if they are an editor
	 * or admin.
	 *
	 * @return boolean
	 */
	public function canEdit() {
		
		/*
		return Services::get('user')->canUserAccessStage(
			WORKFLOW_STAGE_ID_PRODUCTION,
			WORKFLOW_TYPE_EDITORIAL,
			$this->getAuthorizedContextObject(ASSOC_TYPE_ACCESSIBLE_WORKFLOW_STAGES),
			$this->getAuthorizedContextObject(ASSOC_TYPE_USER_ROLES)
		);
		*/

		# FIXME: this check needs revision because it will not work upon initial submission
		return true;

	}
}


