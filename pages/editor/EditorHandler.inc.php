<?php

/**
 * EditorHandler.inc.php
 *
 * Copyright (c) 2003-2004 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package pages.editor
 *
 * Handle requests for editor functions. 
 *
 * $Id$
 */

import('sectionEditor.SectionEditorHandler');

class EditorHandler extends SectionEditorHandler {

	/**
	 * Display editor index page.
	 */
	function index() {
		EditorHandler::validate();
		EditorHandler::setupTemplate(false, false);
		
		$templateMgr = &TemplateManager::getManager();
		$templateMgr->display('editor/index.tpl');
	}
	
	function submissionQueue() {
		EditorHandler::validate();
		EditorHandler::setupTemplate(true);
		$journal = &Request::getJournal();
		
		$templateMgr = &TemplateManager::getManager();
		$sectionDao = &DAORegistry::getDAO('SectionDAO');
		$sections = &$sectionDao->getSectionTitles($journal->getJournalId());
	
		$roleDao = &DAORegistry::getDAO('RoleDAO');
		$sectionEditors = &$roleDao->getUsersByRoleId(ROLE_ID_SECTION_EDITOR, $journal->getJournalId());
		
		$editorSubmissionDao = &DAORegistry::getDAO('EditorSubmissionDAO');
		$queuedSubmissions = &$editorSubmissionDao->getEditorSubmissions($journal->getJournalId(), 1);
	
		$templateMgr->assign('sectionOptions', array('' => Locale::Translate('editor.allSections')) + $sections);
		$templateMgr->assign('section', Request::getUserVar('section'));
		$templateMgr->assign('queuedSubmissions', $queuedSubmissions);
		$templateMgr->assign('sectionEditors', $sectionEditors);
		$templateMgr->display('editor/submissionQueue.tpl');
	}
	
	function submissionArchive() {
		EditorHandler::validate();
		EditorHandler::setupTemplate(true);
		$journal = &Request::getJournal();
		
		$editorSubmissionDao = &DAORegistry::getDAO('EditorSubmissionDAO');
		$archivedSubmissions = &$editorSubmissionDao->getEditorSubmissions($journal->getJournalId(), 0);
		
		$templateMgr = &TemplateManager::getManager();
		$sectionDao = &DAORegistry::getDAO('SectionDAO');
		$sections = &$sectionDao->getSectionTitles($journal->getJournalId());
		$templateMgr->assign('sectionOptions', array('' => Locale::Translate('editor.allSections')) + $sections);
		$templateMgr->assign('section', Request::getUserVar('section'));
		$templateMgr->assign('archivedSubmissions', $archivedSubmissions);
		$templateMgr->display('editor/submissionArchive.tpl');
	}
	
	function updateSubmissionArchive() {
		EditorHandler::submissionArchive();
	}
	
	function schedulingQueue() {
		EditorHandler::validate();
		EditorHandler::setupTemplate(true);
		$journal = &Request::getJournal();
		
		$templateMgr = &TemplateManager::getManager();
		$sectionDao = &DAORegistry::getDAO('SectionDAO');
		$sections = &$sectionDao->getSectionTitles($journal->getJournalId());
		$templateMgr->assign('sectionOptions', array('' => Locale::Translate('editor.allSections')) + $sections);
		$templateMgr->assign('section', Request::getUserVar('section'));
		$templateMgr->display('editor/schedulingQueue.tpl');
	}
	
	function updateSchedulingQueue() {
		EditorHandler::schedulingQueue();
	}
	
	/**
	 * Assigns the selected editor to the submission.
	 * Any previously assigned editors become unassigned.
	 */
	 
	function assignEditor($args) {
		EditorHandler::validate();
		EditorHandler::setupTemplate(true);
		
		$journal = &Request::getJournal();
		$articleId = isset($args[0]) ? $args[0] : 0;
		
		if (isset($args[1]) && $args[1] != null) {
			// Assign editor to article			
			EditorAction::assignEditor($articleId, $args[1]);
			Request::redirect('editor/submission/'.$articleId);
			
			// FIXME: Prompt for due date.
		} else {
			$editorSubmissionDao = &DAORegistry::getDAO('EditorSubmissionDAO');
			$sectionEditors = $editorSubmissionDao->getSectionEditorsNotAssignedToArticle($journal->getJournalId(), $articleId);
		
			$templateMgr = &TemplateManager::getManager();
		
			$templateMgr->assign('sectionEditors', $sectionEditors);
			$templateMgr->assign('articleId', $articleId);
	
			$templateMgr->display('editor/selectSectionEditor.tpl');
		}
	}
	
	/**
	 * Validate that user is an editor in the selected journal.
	 * Redirects to user index page if not properly authenticated.
	 */
	function validate() {
		parent::validate();
		$journal = &Request::getJournal();
		if (!isset($journal) || !Validation::isEditor($journal->getJournalId())) {
			Request::redirect('user');
		}
	}
	
	/**
	 * Setup common template variables.
	 * @param $subclass boolean set to true if caller is below this handler in the hierarchy
	 */
	function setupTemplate($subclass = false, $showSidebar = true) {
		$templateMgr = &TemplateManager::getManager();
		$templateMgr->assign('pageHierarchy',
			$subclass ? array(array('user', 'navigation.user'), array('editor', 'editor.journalEditor'))
				: array(array('user', 'navigation.user'))
		);
		$templateMgr->assign('pagePath', '/user/editor');
		
		if ($showSidebar) {
			$templateMgr->assign('sidebarTemplate', 'editor/navsidebar.tpl');
		}
	}
	
	
	//
	// Section Management
	//
	
	function sections() {
		SectionHandler::sections();
	}
	
	function createSection() {
		SectionHandler::createSection();
	}
	
	function editSection($args) {
		SectionHandler::editSection($args);
	}
	
	function updateSection() {
		SectionHandler::updateSection();
	}
	
	function deleteSection($args) {
		SectionHandler::deleteSection();
	}
	
	function moveSection() {
		SectionHandler::moveSection();
	}
	
}

?>
