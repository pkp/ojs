<?php

/**
 * @file pages/sectionEditor/SectionEditorHandler.inc.php
 *
 * Copyright (c) 2013-2015 Simon Fraser University Library
 * Copyright (c) 2003-2015 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class SectionEditorHandler
 * @ingroup pages_sectionEditor
 *
 * @brief Handle requests for section editor functions.
 */

// Filter section
define('FILTER_SECTION_ALL', 0);

import('classes.submission.sectionEditor.SectionEditorAction');
import('classes.handler.Handler');

class SectionEditorHandler extends Handler {
	/** submission associated with the request **/
	var $submission;

	/**
	 * Constructor
	 */
	function SectionEditorHandler() {
		parent::Handler();

		$this->addCheck(new HandlerValidatorJournal($this));
		// FIXME This is kind of evil
		$page = Request::getRequestedPage();
		if ( $page == 'sectionEditor' )
			$this->addCheck(new HandlerValidatorRoles($this, true, null, null, array(ROLE_ID_SECTION_EDITOR)));
		elseif ( $page == 'editor' )
			$this->addCheck(new HandlerValidatorRoles($this, true, null, null, array(ROLE_ID_EDITOR)));

	}

	/**
	 * Display section editor index page.
	 * @param $args array
	 * @param $request PKPRequest
	 */
	function index($args, &$request) {
		$this->validate();
		$this->setupTemplate();

		$journal =& $request->getJournal();
		$journalId = $journal->getId();
		$user =& $request->getUser();

		$rangeInfo = $this->getRangeInfo('submissions');

		// Get the user's search conditions, if any
		$searchField = $request->getUserVar('searchField');
		$dateSearchField = $request->getUserVar('dateSearchField');
		$searchMatch = $request->getUserVar('searchMatch');
		$search = $request->getUserVar('search');

		$fromDate = $request->getUserDateVar('dateFrom', 1, 1);
		if ($fromDate !== null) $fromDate = date('Y-m-d H:i:s', $fromDate);
		$toDate = $request->getUserDateVar('dateTo', 32, 12, null, 23, 59, 59);
		if ($toDate !== null) $toDate = date('Y-m-d H:i:s', $toDate);

		$sectionDao =& DAORegistry::getDAO('SectionDAO');
		$sectionEditorSubmissionDao =& DAORegistry::getDAO('SectionEditorSubmissionDAO');

		$page = isset($args[0]) ? $args[0] : '';
		$sections =& $sectionDao->getSectionTitles($journal->getId());

		$sort = $request->getUserVar('sort');
		$sort = isset($sort) ? $sort : 'id';
		$sortDirection = $request->getUserVar('sortDirection');

		$filterSectionOptions = array(
			FILTER_SECTION_ALL => AppLocale::Translate('editor.allSections')
		) + $sections;

		switch($page) {
			case 'submissionsInEditing':
				$functionName = 'getSectionEditorSubmissionsInEditing';
				$helpTopicId = 'editorial.sectionEditorsRole.submissions.inEditing';
				break;
			case 'submissionsArchives':
				$functionName = 'getSectionEditorSubmissionsArchives';
				$helpTopicId = 'editorial.sectionEditorsRole.submissions.archives';
				break;
			default:
				$page = 'submissionsInReview';
				$functionName = 'getSectionEditorSubmissionsInReview';
				$helpTopicId = 'editorial.sectionEditorsRole.submissions.inReview';
		}

		$filterSection = $request->getUserVar('filterSection');
		if ($filterSection != '' && array_key_exists($filterSection, $filterSectionOptions)) {
			$user->updateSetting('filterSection', $filterSection, 'int', $journalId);
		} else {
			$filterSection = $user->getSetting('filterSection', $journalId);
			if ($filterSection == null) {
				$filterSection = FILTER_SECTION_ALL;
				$user->updateSetting('filterSection', $filterSection, 'int', $journalId);
			}
		}

		$submissions =& $sectionEditorSubmissionDao->$functionName(
			$user->getId(),
			$journal->getId(),
			$filterSection,
			$searchField,
			$searchMatch,
			$search,
			$dateSearchField,
			$fromDate,
			$toDate,
			$rangeInfo,
			$sort,
			$sortDirection
		);

		// If only result is returned from a search, fast-forward to it
		if ($search && $submissions && $submissions->getCount() == 1) {
			$submission =& $submissions->next();
			$request->redirect(null, null, 'submission', array($submission->getId()));
		}

		$templateMgr =& TemplateManager::getManager();
		$templateMgr->assign('helpTopicId', $helpTopicId);
		$templateMgr->assign('sectionOptions', $filterSectionOptions);
		$templateMgr->assign_by_ref('submissions', $submissions);
		$templateMgr->assign('filterSection', $filterSection);
		$templateMgr->assign('pageToDisplay', $page);
		$templateMgr->assign('sectionEditor', $user->getFullName());

		// Set search parameters
		$duplicateParameters = array(
			'searchField', 'searchMatch', 'search',
			'dateFromMonth', 'dateFromDay', 'dateFromYear',
			'dateToMonth', 'dateToDay', 'dateToYear',
			'dateSearchField'
		);
		foreach ($duplicateParameters as $param)
			$templateMgr->assign($param, $request->getUserVar($param));

		$templateMgr->assign('dateFrom', $fromDate);
		$templateMgr->assign('dateTo', $toDate);
		$templateMgr->assign('fieldOptions', array(
			SUBMISSION_FIELD_TITLE => 'article.title',
			SUBMISSION_FIELD_ID => 'article.submissionId',
			SUBMISSION_FIELD_AUTHOR => 'user.role.author',
			SUBMISSION_FIELD_EDITOR => 'user.role.editor'
		));
		$templateMgr->assign('dateFieldOptions', array(
			SUBMISSION_FIELD_DATE_SUBMITTED => 'submissions.submitted',
			SUBMISSION_FIELD_DATE_COPYEDIT_COMPLETE => 'submissions.copyeditComplete',
			SUBMISSION_FIELD_DATE_LAYOUT_COMPLETE => 'submissions.layoutComplete',
			SUBMISSION_FIELD_DATE_PROOFREADING_COMPLETE => 'submissions.proofreadingComplete'
		));

		import('classes.issue.IssueAction');
		$issueAction = new IssueAction();
		$templateMgr->register_function('print_issue_id', array($issueAction, 'smartyPrintIssueId'));
		$templateMgr->assign('sort', $sort);
		$templateMgr->assign('sortDirection', $sortDirection);

		$templateMgr->display('sectionEditor/index.tpl');
	}

	/**
	 * Setup common template variables.
	 * @param $subclass boolean set to true if caller is below this handler in the hierarchy
	 * @param $articleId int optional
	 * @param $parentPage string optional
	 * @param $showSidebar boolean optional
	 */
	function setupTemplate($subclass = false, $articleId = 0, $parentPage = null, $showSidebar = true) {
		parent::setupTemplate();
		AppLocale::requireComponents(LOCALE_COMPONENT_PKP_SUBMISSION, LOCALE_COMPONENT_OJS_EDITOR, LOCALE_COMPONENT_PKP_MANAGER, LOCALE_COMPONENT_OJS_AUTHOR, LOCALE_COMPONENT_OJS_MANAGER);
		$templateMgr =& TemplateManager::getManager();
		$isEditor = Validation::isEditor();

		if (Request::getRequestedPage() == 'editor') {
			$templateMgr->assign('helpTopicId', 'editorial.editorsRole');

		} else {
			$templateMgr->assign('helpTopicId', 'editorial.sectionEditorsRole');
		}

		$roleSymbolic = $isEditor ? 'editor' : 'sectionEditor';
		$roleKey = $isEditor ? 'user.role.editor' : 'user.role.sectionEditor';
		$pageHierarchy = $subclass ? array(array(Request::url(null, 'user'), 'navigation.user'), array(Request::url(null, $roleSymbolic), $roleKey), array(Request::url(null, $roleSymbolic), 'article.submissions'))
			: array(array(Request::url(null, 'user'), 'navigation.user'), array(Request::url(null, $roleSymbolic), $roleKey));

		import('classes.submission.sectionEditor.SectionEditorAction');
		$submissionCrumb = SectionEditorAction::submissionBreadcrumb($articleId, $parentPage, $roleSymbolic);
		if (isset($submissionCrumb)) {
			$pageHierarchy = array_merge($pageHierarchy, $submissionCrumb);
		}
		$templateMgr->assign('pageHierarchy', $pageHierarchy);
	}

	/**
	 * Display submission management instructions.
	 * @param $args array
	 * @param $request PKPRequest
	 */
	function instructions($args, &$request) {
		$this->setupTemplate();
		import('classes.submission.proofreader.ProofreaderAction');
		if (!isset($args[0]) || !ProofreaderAction::instructions($args[0], array('copy', 'layout', 'proof', 'referenceLinking'))) {
			$request->redirect(null, null, 'index');
		}
	}

	//
	// Validation
	//

	/**
	 * Validate that the user is the assigned section editor for
	 * the article, or is a managing editor.
	 * Redirects to sectionEditor index page if validation fails.
	 * @param $articleId int Optional article ID to validate, or null for none
	 * @param $access int Optional name of access level required -- see SECTION_EDITOR_ACCESS_... constants
	 */
	function validate($articleId = null, $access = null) {
		parent::validate();
		$isValid = true;

		$sectionEditorSubmissionDao =& DAORegistry::getDAO('SectionEditorSubmissionDAO');
		$journal =& Request::getJournal();
		$user =& Request::getUser();

		if ($articleId !== null) {
			$sectionEditorSubmission =& $sectionEditorSubmissionDao->getSectionEditorSubmission($articleId);

			if ($sectionEditorSubmission == null) {
				$isValid = false;

			} else if ($sectionEditorSubmission->getJournalId() != $journal->getId()) {
				$isValid = false;

			} else if ($sectionEditorSubmission->getDateSubmitted() == null) {
				$isValid = false;

			} else {
				$templateMgr =& TemplateManager::getManager();

				if (Validation::isEditor()) {
					// Make canReview and canEdit available to templates.
					// Since this user is an editor, both are available.
					$templateMgr->assign('canReview', true);
					$templateMgr->assign('canEdit', true);
				} else {
					// If this user isn't the submission's editor, they don't have access.
					$editAssignments =& $sectionEditorSubmission->getEditAssignments();
					$wasFound = false;
					foreach ($editAssignments as $editAssignment) {
						if ($editAssignment->getEditorId() == $user->getId()) {
							$templateMgr->assign('canReview', $editAssignment->getCanReview());
							$templateMgr->assign('canEdit', $editAssignment->getCanEdit());
							switch ($access) {
								case SECTION_EDITOR_ACCESS_EDIT:
									if ($editAssignment->getCanEdit()) {
										$wasFound = true;
									}
									break;
								case SECTION_EDITOR_ACCESS_REVIEW:
									if ($editAssignment->getCanReview()) {
										$wasFound = true;
									}
									break;

								default:
									$wasFound = true;
							}
							break;
						}
					}

					if (!$wasFound) $isValid = false;
				}
			}

			if (!$isValid) {
				return Request::redirect(null, Request::getRequestedPage());
			}

			// If necessary, note the current date and time as the "underway" date/time
			$editAssignmentDao =& DAORegistry::getDAO('EditAssignmentDAO');
			$editAssignments =& $sectionEditorSubmission->getEditAssignments();
			foreach ($editAssignments as $editAssignment) {
				if ($editAssignment->getEditorId() == $user->getId() && $editAssignment->getDateUnderway() === null) {
					$editAssignment->setDateUnderway(Core::getCurrentDate());
					$editAssignmentDao->updateEditAssignment($editAssignment);
				}
			}

			$this->submission =& $sectionEditorSubmission;
			return true;
		}
	}
}

?>
