<?php

/**
 * @file controllers/grid/users/queries/form/QueryNoteForm.inc.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2003-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class QueryNoteForm
 * @ingroup controllers_grid_users_queries_form
 *
 * @brief Form for adding/editing a new query note.
 */

import('lib.pkp.classes.form.Form');

class QueryNoteForm extends Form {
	/** @var array Action arguments */
	var $_actionArgs;

	/** @var Query */
	var $_query;

	/** @var int Note ID */
	var $_noteId;

	/** @var boolean Whether or not this is a new note */
	var $_isNew;

	/**
	 * Constructor.
	 * @param $actionArgs array Action arguments
	 * @param $query Query
	 * @param $user User The current user ID
	 * @param $noteId int The note ID to edit, or null for new.
	 */
	function __construct($actionArgs, $query, $user, $noteId = null) {
		parent::__construct('controllers/grid/queries/form/queryNoteForm.tpl');
		$this->_actionArgs = $actionArgs;
		$this->setQuery($query);

		if ($noteId === null) {
			// Create a new (placeholder) note.
			$noteDao = DAORegistry::getDAO('NoteDAO');
			$note = $noteDao->newDataObject();
			$note->setAssocType(ASSOC_TYPE_QUERY);
			$note->setAssocId($query->getId());
			$note->setUserId($user->getId());
			$note->setDateCreated(Core::getCurrentDate());
			$this->_noteId = $noteDao->insertObject($note);
			$this->_isNew = true;
		} else {
			$this->_noteId = $noteId;
			$this->_isNew = false;
		}

		// Validation checks for this form
		$this->addCheck(new FormValidator($this, 'comment', 'required', 'submission.queries.messageRequired'));
		$this->addCheck(new FormValidatorPost($this));
		$this->addCheck(new FormValidatorCSRF($this));
	}

	//
	// Getters and Setters
	//
	/**
	 * Get the query
	 * @return Query
	 */
	function getQuery() {
		return $this->_query;
	}

	/**
	 * Set the query
	 * @param @query Query
	 */
	function setQuery($query) {
		$this->_query = $query;
	}

	/**
	 * Assign form data to user-submitted data.
	 * @see Form::readInputData()
	 */
	function readInputData() {
		$this->readUserVars(array(
			'comment',
		));
	}

	/**
	 * @copydoc Form::fetch
	 */
	function fetch($request, $template = null, $display = false) {
		$templateMgr = TemplateManager::getManager($request);
		$templateMgr->assign(array(
			'actionArgs' => $this->_actionArgs,
			'noteId' => $this->_noteId,
		));
		return parent::fetch($request, $template, $display);
	}

	/**
	 * @copydoc Form::execute()
	 * @param $request PKPRequest
	 * @return Note The created note object.
	 */
	function execute($request) {
		$user = $request->getUser();

		// Create a new note.
		$noteDao = DAORegistry::getDAO('NoteDAO');
		$note = $noteDao->getById($this->_noteId);
		$note->setUserId($request->getUser()->getId());
		$note->setContents($this->getData('comment'));
		$noteDao->updateObject($note);

		// Check whether the query needs re-opening
		$query = $this->getQuery();
		if ($query->getIsClosed()) {
			$headNote = $query->getHeadNote();
			if ($user->getId() != $headNote->getUserId()) {
				// Re-open the query.
				$query->setIsClosed(false);
				$queryDao = DAORegistry::getDAO('QueryDAO');
				$queryDao->updateObject($query);
			}
		}

		$notificationDao = DAORegistry::getDAO('NotificationDAO');
		$queryDao = DAORegistry::getDAO('QueryDAO');
		$notificationManager = new NotificationManager();
		foreach ($queryDao->getParticipantIds($query->getId()) as $userId) {
			// Delete any prior notifications of the same type (e.g. prior "new" comments)
			$notificationDao->deleteByAssoc(
				ASSOC_TYPE_QUERY, $query->getId(),
				$userId, NOTIFICATION_TYPE_QUERY_ACTIVITY,
				$request->getContext()->getId()
			);

			// No need to additionally notify the posting user.
			if ($userId == $user->getId()) continue;

			// Notify the user of a new query.
			$notificationManager->createNotification(
				$request,
				$userId,
				NOTIFICATION_TYPE_QUERY_ACTIVITY,
				$request->getContext()->getId(),
				ASSOC_TYPE_QUERY,
				$query->getId(),
				NOTIFICATION_LEVEL_TASK
			);
		}

		return $note;
	}
}

?>
