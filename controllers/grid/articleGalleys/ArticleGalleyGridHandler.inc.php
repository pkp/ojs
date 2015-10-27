<?php

/**
 * @file controllers/grid/articleGalleys/ArticleGalleyGridHandler.inc.php
 *
 * Copyright (c) 2014-2015 Simon Fraser University Library
 * Copyright (c) 2000-2015 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class ArticleGalleyGridHandler
 * @ingroup controllers_grid_articleGalleys
 *
 * @brief Handle article galley grid requests.
 */

import('lib.pkp.controllers.grid.representations.RepresentationsGridHandler');

class ArticleGalleyGridHandler extends RepresentationsGridHandler {
	/** @var PublicationFormatGridCellProvider */
	var $_cellProvider;

	/**
	 * Constructor
	 */
	function ArticleGalleyGridHandler() {
		parent::RepresentationsGridHandler();
	}


	/**
	 * Configure the grid
	 * @param $request PKPRequest
	 */
	function initialize($request) {
		parent::initialize($request);

		$this->setTitle('submission.layout.galleys');

		// Load submission-specific translations
		AppLocale::requireComponents(
			LOCALE_COMPONENT_APP_SUBMISSION,
			LOCALE_COMPONENT_APP_DEFAULT,
			LOCALE_COMPONENT_APP_EDITOR
		);

		// Grid actions
		$router = $request->getRouter();
		$actionArgs = $this->getRequestArgs();
		$this->addAction(
			new LinkAction(
				'addFormat',
				new AjaxModal(
					$router->url($request, null, null, 'addFormat', null, $actionArgs),
					__('submission.layout.addGalley'),
					'modal_add_item'
				),
				__('submission.layout.addGalley'),
				'add_item'
			)
		);

		// Columns
		$submission = $this->getSubmission();
		import('lib.pkp.controllers.grid.representations.RepresentationsGridCellProvider');
		$this->_cellProvider = new RepresentationsGridCellProvider($submission->getId());
		$this->addColumn(
			new GridColumn(
				'name',
				'common.name',
				null,
				null,
				$this->_cellProvider,
				array('width' => 60, 'anyhtml' => true)
			)
		);
		$this->addColumn(
			new GridColumn(
				'isComplete',
				'common.complete',
				null,
				'controllers/grid/common/cell/statusCell.tpl',
				$this->_cellProvider,
				array('width' => 20)
			)
		);
	}


	//
	// Overridden methods from GridHandler
	//
	/**
	 * @see GridHandler::getRowInstance()
	 * @return RepresentationsGridCategoryRow
	 */
	function getCategoryRowInstance() {
		return new RepresentationsGridCategoryRow($this->getSubmission(), $this->_cellProvider);
	}


	//
	// Public Publication Format Grid Actions
	//
	/**
	 * Edit a format
	 * @param $args array
	 * @param $request PKPRequest
	 * @return JSONMessage JSON object
	 */
	function editFormat($args, $request) {
		$submission = $this->getSubmission();
		$representationDao = Application::getRepresentationDAO();
		$representation = $representationDao->getById(
			$request->getUserVar('representationId'),
			$submission->getId()
		);

		import('controllers.grid.articleGalleys.form.ArticleGalleyForm');
		$articleGalleyForm = new ArticleGalleyForm($request, $submission, $representation);
		$articleGalleyForm->initData();

		return new JSONMessage(true, $articleGalleyForm->fetch($request));
	}

	/**
	 * Update a format
	 * @param $args array
	 * @param $request PKPRequest
	 * @return JSONMessage JSON object
	 */
	function updateFormat($args, $request) {
		$submission = $this->getSubmission();
		$representationDao = Application::getRepresentationDAO();
		$representation = $representationDao->getById(
			$request->getUserVar('representationId'),
			$submission->getId()
		);

		import('controllers.grid.articleGalleys.form.ArticleGalleyForm');
		$articleGalleyForm = new ArticleGalleyForm($request, $submission, $representation);
		$articleGalleyForm->readInputData();
		if ($articleGalleyForm->validate($request)) {
			$articleGalleyForm->execute($request);
			return DAO::getDataChangedEvent();
		}
		return new JSONMessage(true, $articleGalleyForm->fetch($request));
	}

	/**
	 * Delete a format
	 * @param $args array
	 * @param $request PKPRequest
	 * @return JSONMessage JSON object
	 */
	function deleteFormat($args, $request) {
		$context = $request->getContext();
		$submission = $this->getSubmission();
		$representationDao = Application::getRepresentationDAO();
		$representation = $representationDao->getById(
			$request->getUserVar('representationId'),
			$submission->getId()
		);

		if (!$representation || !$representationDao->deleteById($representation->getId())) {
			return new JSONMessage(false, __('manager.setup.errorDeletingItem'));
		}

		return DAO::getDataChangedEvent();
	}

	/**
	 * Set a format's "approved" state
	 * @param $args array
	 * @param $request PKPRequest
	 * @return JSONMessage JSON object
	 */
	function setApproved($args, $request) {
		$submission = $this->getSubmission();
		$representationDao = Application::getRepresentationDAO();
		$representation = $representationDao->getById(
			$request->getUserVar('representationId'),
			$submission->getId()
		);

		if (!$representation) return new JSONMessage(false, __('manager.setup.errorDeletingItem'));

		$newApprovedState = (int) $request->getUserVar('newApprovedState');
		$representation->setIsApproved($newApprovedState);
		$representationDao->updateObject($representation);

		return DAO::getDataChangedEvent($representation->getId());
	}
}

?>
