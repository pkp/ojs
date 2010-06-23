<?php

/**
 * @file controllers/grid/citation/CitationGridHandler.inc.php
 *
 * Copyright (c) 2000-2010 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class CitationGridHandler
 * @ingroup controllers_grid_citation
 *
 * @brief Handle OJS specific parts of citation grid requests.
 */

import('lib.pkp.classes.controllers.grid.citation.PKPCitationGridHandler');

// import validation classes
import('classes.handler.validation.HandlerValidatorJournal');
import('lib.pkp.classes.handler.validation.HandlerValidatorRoles');

class CitationGridHandler extends PKPCitationGridHandler {
	/**
	 * Constructor
	 */
	function CitationGridHandler() {
		parent::PKPCitationGridHandler();
	}


	//
	// Overridden methods from PKPHandler
	//
	/**
	 * OJS-specific authorization and validation checks
	 *
	 * Checks whether the user is the assigned section editor for
	 * the citation's article, or is a managing editor.
	 *
	 * This method also identifies, validates and instantiates the
	 * article to which the citation editor will be attached.
	 *
	 * @see PKPHandler::validate()
	 */
	function validate($requiredContexts, &$request) {
		// Retrieve the request context
		$router =& $request->getRouter();
		$journal =& $router->getContext($request);

		// NB: Error messages are in plain English as they directly go to fatal errors
		// which are not directed to end users. (Validation errors in components are
		// either programming errors or somebody trying to call components directly
		// which is no legal use case.)

		// 1) We need a journal
		$this->addCheck(new HandlerValidatorJournal($this, false, 'No journal in context!'));

		// 2) Only editors or section editors may access
		$this->addCheck(new HandlerValidatorRoles($this, false, 'Insufficient privileges!', null, array(ROLE_ID_EDITOR, ROLE_ID_SECTION_EDITOR)));

		// Execute application-independent checks
		if (!parent::validate($requiredContexts, $request, $journal)) return false;

		// Retrieve and validate the article id
		$articleId =& $request->getUserVar('assocId');
		if (!is_numeric($articleId)) return false;

		// Retrieve the article associated with this citation grid
		$articleDAO =& DAORegistry::getDAO('ArticleDAO');
		$article =& $articleDAO->getArticle($articleId);

		// Article and editor validation
		if (!is_a($article, 'Article')) return false;
		if ($article->getJournalId() != $journal->getId()) return false;

		// Editors have access to all articles, section editors will be
		// checked individually.
		if (!Validation::isEditor()) {
			// Retrieve the edit assignments
			$editAssignmentDao =& DAORegistry::getDAO('EditAssignmentDAO');
			$editAssignments =& $editAssignmentDao->getEditAssignmentsByArticleId($article->getId());
			assert(is_a($editAssignments, 'DAOResultFactory'));
			$editAssignmentsArray =& $editAssignments->toArray();

			// Check whether the user is the article's editor,
			// otherwise deny access.
			$user =& $request->getUser();
			$userId = $user->getId();
			$wasFound = false;
			foreach ($editAssignmentsArray as $editAssignment) {
				if ($editAssignment->getEditorId() == $userId) {
					if ($editAssignment->getCanEdit()) $wasFound = true;
					break;
				}
			}

			if (!$wasFound) return false;
		}

		// Validation successful - associate the citation
		// editor with this article.
		$this->setAssocType(ASSOC_TYPE_ARTICLE);
		$this->setAssocObject($article);
		return true;
	}
}
