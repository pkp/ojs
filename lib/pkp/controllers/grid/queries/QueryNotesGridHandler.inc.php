<?php

/**
 * @file controllers/grid/queries/QueryNotesGridHandler.inc.php
 *
 * Copyright (c) 2016-2017 Simon Fraser University
 * Copyright (c) 2000-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class QueryNotesGridHandler
 * @ingroup controllers_grid_query
 *
 * @brief base PKP class to handle query grid requests.
 */

// import grid base classes
import('lib.pkp.classes.controllers.grid.GridHandler');
import('lib.pkp.classes.controllers.grid.DataObjectGridCellProvider');

// Link action & modal classes
import('lib.pkp.classes.linkAction.request.AjaxModal');

class QueryNotesGridHandler extends GridHandler {
	/** @var User */
	var $_user;

	/**
	 * Constructor
	 */
	function __construct() {
		parent::__construct();
		$this->addRoleAssignment(
			array(ROLE_ID_MANAGER, ROLE_ID_AUTHOR, ROLE_ID_SUB_EDITOR, ROLE_ID_ASSISTANT),
			array('fetchGrid', 'fetchRow', 'addNote', 'insertNote', 'deleteNote'));
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
	 * Get the query.
	 * @return Query
	 */
	function getQuery() {
		return $this->getAuthorizedContextObject(ASSOC_TYPE_QUERY);
	}

	/**
	 * Get the stage id.
	 * @return integer
	 */
	function getStageId() {
		return $this->getAuthorizedContextObject(ASSOC_TYPE_WORKFLOW_STAGE);
	}

	//
	// Overridden methods from PKPHandler.
	// Note: this is subclassed in application-specific grids.
	//
	/**
	 * @copydoc PKPHandler::authorize()
	 */
	function authorize($request, &$args, $roleAssignments) {
		$stageId = $request->getUserVar('stageId'); // This is being validated in WorkflowStageAccessPolicy

		// Get the access policy
		import('lib.pkp.classes.security.authorization.QueryAccessPolicy');
		$this->addPolicy(new QueryAccessPolicy($request, $args, $roleAssignments, $stageId));
		return parent::authorize($request, $args, $roleAssignments);
	}

	/**
	 * @copydoc GridHandler::initialize()
	 */
	function initialize($request, $args = null) {
		parent::initialize($request, $args);
		$this->setTitle('submission.query.messages');

		// Load pkp-lib translations
		AppLocale::requireComponents(
			LOCALE_COMPONENT_PKP_SUBMISSION,
			LOCALE_COMPONENT_PKP_USER,
			LOCALE_COMPONENT_PKP_EDITOR
		);

		import('lib.pkp.controllers.grid.queries.QueryNotesGridCellProvider');
		$cellProvider = new QueryNotesGridCellProvider($this->getSubmission());

		// Columns
		$this->addColumn(
			new GridColumn(
				'contents',
				'common.note',
				null,
				null,
				$cellProvider,
				array('width' => 80, 'html' => true)
			)
		);
		$this->addColumn(
			new GridColumn(
				'from',
				'submission.query.from',
				null,
				null,
				$cellProvider,
				array('html' => true)
			)
		);

		$this->_user = $request->getUser();
	}


	//
	// Overridden methods from GridHandler
	//
	/**
	 * @copydoc GridHandler::getRowInstance()
	 * @return QueryNotesGridRow
	 */
	function getRowInstance() {
		import('lib.pkp.controllers.grid.queries.QueryNotesGridRow');
		return new QueryNotesGridRow($this->getRequestArgs(), $this->getQuery(), $this);
	}

	/**
	 * Get the arguments that will identify the data in the grid.
	 * Overridden by child grids.
	 * @return array
	 */
	function getRequestArgs() {
		return array(
			'submissionId' => $this->getSubmission()->getId(),
			'stageId' => $this->getStageId(),
			'queryId' => $this->getQuery()->getId(),
		);
	}

	/**
	 * @copydoc GridHandler::loadData()
	 */
	function loadData($request, $filter = null) {
		return $this->getQuery()->getReplies(null, NOTE_ORDER_DATE_CREATED, SORT_DIRECTION_ASC, $this->getCanManage(null));
	}

	//
	// Public Query Notes Grid Actions
	//
	/**
	 * Present the form to add a new note.
	 * @param $args array
	 * @param $request PKPRequest
	 */
	function addNote($args, $request) {
		import('lib.pkp.controllers.grid.queries.form.QueryNoteForm');
		$queryNoteForm = new QueryNoteForm($this->getRequestArgs(), $this->getQuery(), $request->getUser());
		$queryNoteForm->initData();
		return new JSONMessage(true, $queryNoteForm->fetch($request));
	}

	/**
	 * Insert a new note.
	 * @param $args array
	 * @param $request PKPRequest
	 */
	function insertNote($args, $request) {
		import('lib.pkp.controllers.grid.queries.form.QueryNoteForm');
		$queryNoteForm = new QueryNoteForm($this->getRequestArgs(), $this->getQuery(), $request->getUser(), $request->getUserVar('noteId'));
		$queryNoteForm->readInputData();
		if ($queryNoteForm->validate()) {
			$note = $queryNoteForm->execute($request);
			return DAO::getDataChangedEvent($this->getQuery()->getId());
		} else {
			return new JSONMessage(true, $queryNoteForm->fetch($request));
		}
	}

	/**
	 * Determine whether the current user can manage (delete) a note.
	 * @param $note Note optional
	 * @return boolean
	 */
	function getCanManage($note) {
		$isAdmin = (0 != count(array_intersect(
			$this->getAuthorizedContextObject(ASSOC_TYPE_USER_ROLES),
			array(ROLE_ID_MANAGER, ROLE_ID_ASSISTANT, ROLE_ID_SUB_EDITOR)
		)));

		if ($note === null) {
			return $isAdmin;
		} else {
			return ($note->getUserId() == $this->_user->getId() || $isAdmin);
		}
	}

	/**
	 * Delete a query note.
	 * @param $args array
	 * @param $request PKPRequest
	 * @return JSONMessage JSON object
	 */
	function deleteNote($args, $request) {
		$query = $this->getQuery();
		$noteDao = DAORegistry::getDAO('NoteDAO');
		$note = $noteDao->getById($request->getUserVar('noteId'));
		$user = $request->getUser();

		if (!$request->checkCSRF() || !$note || $note->getAssocType() != ASSOC_TYPE_QUERY || $note->getAssocId() != $query->getId()) {
			// The note didn't exist or has the wrong assoc info.
			return new JSONMessage(false);
		}

		if (!$this->getCanManage($note)) {
			// The user doesn't own the note and isn't priveleged enough to delete it.
			return new JSONMessage(false);
		}

		$noteDao->deleteObject($note);
		return DAO::getDataChangedEvent($note->getId());
	}

}

?>
