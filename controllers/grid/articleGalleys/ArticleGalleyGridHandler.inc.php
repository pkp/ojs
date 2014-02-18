<?php

/**
 * @file controllers/grid/articleGalleys/ArticleGalleyGridHandler.inc.php
 *
 * Copyright (c) 2014 Simon Fraser University Library
 * Copyright (c) 2000-2014 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class ArticleGalleyGridHandler
 * @ingroup controllers_grid_issues
 *
 * @brief Handle issues grid requests.
 */

import('lib.pkp.classes.controllers.grid.GridHandler');
import('controllers.grid.articleGalleys.ArticleGalleyGridRow');

class ArticleGalleyGridHandler extends GridHandler {
	/**
	 * Constructor
	 */
	function ArticleGalleyGridHandler() {
		parent::GridHandler();
		$this->addRoleAssignment(
			array(ROLE_ID_EDITOR, ROLE_ID_MANAGER),
			array(
				'fetchGrid', 'fetchRow', 'saveSequence',
				'add', 'edit', 'update', 'delete',
				'setAvailable'
			)
		);
	}


	//
	// Implement template methods from PKPHandler
	//
	/**
	 * @copydoc PKPHandler::authorize()
	 */
	function authorize($request, &$args, $roleAssignments) {
		import('lib.pkp.classes.security.authorization.PkpContextAccessPolicy');
		$this->addPolicy(new PkpContextAccessPolicy($request, $roleAssignments));

		import('classes.security.authorization.SubmissionAccessPolicy');
		$this->addPolicy(new SubmissionAccessPolicy($request, $args, $roleAssignments));

		// If a signoff ID was specified, authorize it.
		if ($request->getUserVar('articleGalleyId')) {
			import('classes.security.authorization.GalleyRequiredPolicy');
			$this->addPolicy(new GalleyRequiredPolicy($request, $args));
		}

		return parent::authorize($request, $args, $roleAssignments);
	}

	/**
	 * @copydoc GridHandler::getDataElementSequence()
	 */
	function getDataElementSequence($articleGalley) {
		return $articleGalley->getSeq();
	}

	/**
	 * @copydoc GridHandler::setDataElementSequence()
	 */
	function setDataElementSequence($request, $rowId, &$articleGalley, $newSequence) {
		$articleGalleyDao = DAORegistry::getDAO('ArticleGalleyDAO'); /* @var $articleGalleyDao ArticleGalleyDAO */
		$articleGalley->setSeq($newSequence);
		$articleGalleyDao->updateObject($articleGalley);
	}

	/**
	 * @copydoc GridHandler::addFeatures()
	 */
	function initFeatures($request, $args) {
		import('lib.pkp.classes.controllers.grid.feature.OrderGridItemsFeature');
		return array(new OrderGridItemsFeature());
	}

	/**
	 * @copydoc GridDataProvider::getRequestArgs()
	 */
	function getRequestArgs() {
		$submission = $this->getAuthorizedContextObject(ASSOC_TYPE_SUBMISSION);
		$articleGalley = $this->getAuthorizedContextObject(ASSOC_TYPE_GALLEY);
		$requestArgs = (array) parent::getRequestArgs();
		$requestArgs['submissionId'] = $submission->getId();
		if ($articleGalley) $requestArgs['articleGalleyId'] = $articleGalley->getId();
		return $requestArgs;
	}

	/**
	 * @copydoc PKPHandler::initialize()
	 */
	function initialize($request, $args) {
		parent::initialize($request, $args);

		AppLocale::requireComponents(LOCALE_COMPONENT_APP_EDITOR, LOCALE_COMPONENT_PKP_SUBMISSION,LOCALE_COMPONENT_APP_SUBMISSION);

		$this->setTitle('submission.layout.galleys');

		// Add action
		$router = $request->getRouter();
		import('lib.pkp.classes.linkAction.request.AjaxModal');
		$this->addAction(
			new LinkAction(
				'add',
				new AjaxModal(
					$router->url($request, null, null, 'add', null, $this->getRequestArgs() + array('gridId' => $this->getId())),
					__('submission.layout.addGalley'),
					'modal_add'
				),
				__('submission.layout.addGalley'),
				'add_category'
			)
		);

		// Grid columns.
		import('controllers.grid.articleGalleys.ArticleGalleyGridCellProvider');
		$articleGalleyGridCellProvider = new ArticleGalleyGridCellProvider();

		// Issue identification
		$this->addColumn(
			new GridColumn(
				'label',
				'submission.layout.galleyLabel',
				null,
				'controllers/grid/gridCell.tpl',
				$articleGalleyGridCellProvider
			)
		);

		// Language, if more than one is supported
		$journal = $request->getJournal();
		if (count($journal->getSupportedLocaleNames())>1) {
			$this->addColumn(
				new GridColumn(
					'locale',
					'common.language',
					null,
					'controllers/grid/gridCell.tpl',
					$articleGalleyGridCellProvider
				)
			);
		}

		// Public ID, if enabled
		if ($journal->getSetting('enablePublicGalleyId')) {
			$this->addColumn(
				new GridColumn(
					'publicGalleyId',
					'submission.layout.publicGalleyId',
					null,
					'controllers/grid/gridCell.tpl',
					$articleGalleyGridCellProvider
				)
			);
		}

		$this->addColumn(
			new GridColumn(
				'isAvailable',
				'common.available',
				null,
				'controllers/grid/common/cell/statusCell.tpl',
				$articleGalleyGridCellProvider
			)
		);
	}


	/**
	 * Get the row handler - override the default row handler
	 * @return ArticleGalleyGridRow
	 */
	function getRowInstance() {
		$submission = $this->getAuthorizedContextObject(ASSOC_TYPE_SUBMISSION);
		return new ArticleGalleyGridRow($submission->getId());
	}

	//
	// Public operations
	//
	/**
	 * An action to add a new galley.
	 * @param $args array
	 * @param $request PKPRequest
	 */
	function add($args, $request) {
		// Calling editArticleData with an empty ID will add
		// a new issue.
		return $this->edit($args, $request);
	}

	/**
	 * An action to edit a submission galley
	 * @param $args array
	 * @param $request PKPRequest
	 * @return string Serialized JSON object
	 */
	function edit($args, $request) {
		$submission = $this->getAuthorizedContextObject(ASSOC_TYPE_SUBMISSION);
		$articleGalley = $this->getAuthorizedContextObject(ASSOC_TYPE_GALLEY);

		import('controllers.grid.articleGalleys.form.ArticleGalleyForm');
		$articleGalleyForm = new ArticleGalleyForm($request, $submission, $articleGalley);
		$articleGalleyForm->initData($request);
		$json = new JSONMessage(true, $articleGalleyForm->fetch($request));
		return $json->getString();
	}

	/**
	 * Update a issue
	 * @param $args array
	 * @param $request PKPRequest
	 * @return string Serialized JSON object
	 */
	function update($args, $request) {
		$submission = $this->getAuthorizedContextObject(ASSOC_TYPE_SUBMISSION);
		$articleGalley = $this->getAuthorizedContextObject(ASSOC_TYPE_GALLEY);

		import('controllers.grid.articleGalleys.form.ArticleGalleyForm');
		$articleGalleyForm = new ArticleGalleyForm($request, $submission, $articleGalley);
		$articleGalleyForm->readInputData();

		if ($articleGalleyForm->validate($request)) {
			$galleyId = $articleGalleyForm->execute($request);
			return DAO::getDataChangedEvent($galleyId);
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
		$articleGalleyDao = DAORegistry::getDAO('ArticleGalleyDAO');
		$articleGalley = $this->getAuthorizedContextObject(ASSOC_TYPE_GALLEY);
		$articleGalleyId = $articleGalley->getId();
		$articleGalleyDao->deleteGalley($articleGalley);
		return DAO::getDataChangedEvent();
	}

	/**
	 * Set a galley's "available" state
	 * @param $args array
	 * @param $request PKPRequest
	 * @return string Serialized JSON object
	 */
	function setAvailable($args, $request) {
		$context = $request->getContext();
		$submission = $this->getAuthorizedContextObject(ASSOC_TYPE_SUBMISSION);
		$articleGalleyDao = DAORegistry::getDAO('ArticleGalleyDAO');
		$articleGalley =& $articleGalleyDao->getGalleyByBestGalleyId(
				$request->getUserVar('articleGalleyId'),
				$submission->getId() // Make sure to validate the context.
		);

		if ($articleGalley) {
			$newAvailableState = (int) $request->getUserVar('newAvailableState');
			$articleGalley->setIsAvailable($newAvailableState);
			$articleGalleyDao->updateObject($articleGalley);

			// log the state changing of the format.
			import('lib.pkp.classes.log.SubmissionLog');
			import('classes.log.SubmissionEventLogEntry');
			SubmissionLog::logEvent(
				$request, $submission,
				$newAvailableState?SUBMISSION_LOG_LAYOUT_GALLEY_AVAILABLE:SUBMISSION_LOG_LAYOUT_GALLEY_UNAVAILABLE,
				$newAvailableState?'submission.event.articleGalleyMadeAvailable':'submission.event.articleGalleyMadeUnavailable',
				array('galleyFormatName' => $articleGalley->getLocalizedName())
			);

			return DAO::getDataChangedEvent($articleGalley->getId());
		} else {
			$json = new JSONMessage(false, __('manager.setup.errorDeletingItem'));
			return $json->getString();
		}

	}

	/**
	 * @copydoc GridHandler::loadData()
	 */
	function loadData($request, $filter) {
		$submission = $this->getAuthorizedContextObject(ASSOC_TYPE_SUBMISSION);
		$articleGalleyDao = DAORegistry::getDAO('ArticleGalleyDAO');
		return $articleGalleyDao->getBySubmissionId($submission->getId());
	}
}

?>
