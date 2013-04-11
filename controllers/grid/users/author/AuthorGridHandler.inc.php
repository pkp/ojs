<?php

/**
 * @file controllers/grid/users/author/AuthorGridHandler.inc.php
 *
 * Copyright (c) 2000-2012 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class AuthorGridHandler
 * @ingroup controllers_grid_users_author
 *
 * @brief Handle author grid requests for articles.
 */

// import grid base classes
import('lib.pkp.classes.controllers.grid.GridHandler');
import('lib.pkp.controllers.grid.users.author.PKPAuthorGridHandler');


// import author grid specific classes
import('controllers.grid.users.author.AuthorGridRow');

// Link action & modal classes
import('lib.pkp.classes.linkAction.request.AjaxModal');

class AuthorGridHandler extends PKPAuthorGridHandler {

	/**
	 * Constructor
	 */
	function AuthorGridHandler() {
		parent::PKPAuthorGridHandler();
		$this->addRoleAssignment(
				array(ROLE_ID_MANAGER, ROLE_ID_EDITOR, ROLE_ID_AUTHOR, ROLE_ID_SECTION_EDITOR),
				array('fetchGrid', 'fetchRow', 'addAuthor', 'editAuthor',
				'updateAuthor', 'deleteAuthor'));
		$this->addRoleAssignment(ROLE_ID_REVIEWER, array('fetchGrid', 'fetchRow'));
		$this->addRoleAssignment(array(ROLE_ID_MANAGER, ROLE_ID_EDITOR), array('addUser'));
	}


	//
	// Overridden methods from PKPHandler
	//
	/**
	 * @see PKPHandler::authorize()
	 * @param $request PKPRequest
	 * @param $args array
	 * @param $roleAssignments array
	 */
	function authorize($request, &$args, $roleAssignments) {
		import('classes.security.authorization.OjsSubmissionAccessPolicy');
		$this->addPolicy(new OjsSubmissionAccessPolicy($request, $args, $roleAssignments));
		return parent::authorize($request, $args, $roleAssignments);
	}

	/*
	 * Configure the grid
	 * @param $request PKPRequest
	 */
	function initialize($request) {

		// Retrieve the authorized monograph.
		$this->setSubmission($this->getAuthorizedContextObject(ASSOC_TYPE_ARTICLE));

		// Load submission-specific translations
		AppLocale::requireComponents(
			LOCALE_COMPONENT_PKP_SUBMISSION,
			LOCALE_COMPONENT_APP_DEFAULT
		);

		parent::initialize($request);
	}


	//
	// Overridden methods from GridHandler
	//

	/**
	 * Get the arguments that will identify the data in the grid
	 * In this case, the article.
	 * @return array
	 */
	function getRequestArgs() {
		$submission =& $this->getSubmission();
		return array(
			'articleId' => $submission->getId()
		);
	}

	/**
	 * Determines if there should be an 'add user' action on this grid.
	 * @return boolean
	 */
	function hasAddAction() {
		$article =& $this->getSubmission();
		$userRoles = $this->getAuthorizedContextObject(ASSOC_TYPE_USER_ROLES);
		if ($article->getDateSubmitted() == null || array_intersect(array(ROLE_ID_MANAGER, ROLE_ID_EDITOR), $userRoles))
			return true;
		else
			return false;
	}

	/**
	 * Fetches the application-specific submission id from the request object.
	 * @param PKPRequest $request
	 * @return int
	 */
	function getRequestedSubmissionId($request) {
		return $request->getUserVar('articleId');
	}

	/**
	 * Fetches the application-specific submission id field name, for forms.
	 * @return string
	 */
	function getSubmissionFieldIdName() {
		return 'articleId';
	}
}

?>
