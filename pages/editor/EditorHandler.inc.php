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
		EditorHandler::setupTemplate();
		
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
		$queuedSubmissions = &$editorSubmissionDao->getEditorSubmissions($journal->getJournalId());
	
		$templateMgr->assign('sectionOptions', array('' => Locale::Translate('editor.allSections')) + $sections);
		$templateMgr->assign('section', Request::getUserVar('section'));
		$templateMgr->assign('queuedSubmissions', $queuedSubmissions);
		$templateMgr->assign('sectionEditors', $sectionEditors);
		$templateMgr->display('editor/submissionQueue.tpl');
	}
	
	function updateSubmissionQueue() {
		EditorHandler::validate();
		$journal = &Request::getJournal();
		
		$editorSubmissionDao = &DAORegistry::getDAO('EditorSubmissionDAO');
		
		$articleIdArray = Request::getUserVar('articleId');
		if (is_array($articleIdArray) && count($articleIdArray) > 0) {
			foreach ($articleIdArray as $articleId) {
				$editorId = Request::getUserVar('editor_'.$articleId);
				if ($editorId != null && $editorId != '') {
					$editorSubmission = &$editorSubmissionDao->getEditorSubmission($articleId);
					
					$editorSubmission->setEditorId($editorId);
					
					if ($editorSubmission->getEditId() == null) {
						$editorSubmissionDao->insertEditorSubmission($editorSubmission);
					} else {
						$editorSubmissionDao->updateEditorSubmission($editorSubmission);
					}
				}
			}
		}
		$articlesToNotify = Request::getUserVar('notify');
		if (is_array($articlesToNotify) && count($articlesToNotify) > 0) {
			$editorAction = new EditorAction();
			foreach ($articlesToNotify as $articleId) {
				$editorAction->notifySectionEditor($articleId);
			}
		}
		
		Request::redirect('editor/submissionQueue');
	}
	
	function submissionArchive() {
		EditorHandler::validate();
		EditorHandler::setupTemplate(true);
		$journal = &Request::getJournal();
		
		$templateMgr = &TemplateManager::getManager();
		$sectionDao = &DAORegistry::getDAO('SectionDAO');
		$sections = &$sectionDao->getSectionTitles($journal->getJournalId());
		$templateMgr->assign('sectionOptions', array('' => Locale::Translate('editor.allSections')) + $sections);
		$templateMgr->assign('section', Request::getUserVar('section'));
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
	function setupTemplate($subclass = false) {
		$templateMgr = &TemplateManager::getManager();
		$templateMgr->assign('pageHierarchy',
			$subclass ? array(array('user', 'navigation.user'), array('editor', 'editor.journalEditor'))
				: array(array('user', 'navigation.user'))
		);
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
