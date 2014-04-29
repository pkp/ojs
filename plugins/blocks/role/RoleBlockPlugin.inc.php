<?php

/**
 * @file plugins/blocks/role/RoleBlockPlugin.inc.php
 *
 * Copyright (c) 2013-2014 Simon Fraser University Library
 * Copyright (c) 2003-2014 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class RoleBlockPlugin
 * @ingroup plugins_blocks_role
 *
 * @brief Class for role block plugin
 */

import('lib.pkp.classes.plugins.BlockPlugin');

class RoleBlockPlugin extends BlockPlugin {
	/**
	 * Install default settings on journal creation.
	 * @return string
	 */
	function getContextSpecificPluginSettingsFile() {
		return $this->getPluginPath() . '/settings.xml';
	}

	/**
	 * Get the display name of this plugin.
	 * @return String
	 */
	function getDisplayName() {
		return __('plugins.block.role.displayName');
	}

	/**
	 * Get a description of the plugin.
	 */
	function getDescription() {
		return __('plugins.block.role.description');
	}

	/**
	 * Override the block contents based on the current role being
	 * browsed.
	 * @return string
	 */
	function getBlockTemplateFilename() {
		$journal =& Request::getJournal();
		$user =& Request::getUser();
		if (!$journal || !$user) return null;

		$userId = $user->getId();
		$journalId = $journal->getId();

		$templateMgr =& TemplateManager::getManager();

		switch (Request::getRequestedPage()) {
			case 'author': switch (Request::getRequestedOp()) {
				case 'submit':
				case 'saveSubmit':
				case 'submitSuppFile':
				case 'saveSubmitSuppFile':
				case 'deleteSubmitSuppFile':
				case 'expediteSubmission':
					// Block disabled for submission
					return null;
				default:
					$authorSubmissionDao =& DAORegistry::getDAO('AuthorSubmissionDAO');
					$submissionsCount = $authorSubmissionDao->getSubmissionsCount($userId, $journalId);
					$templateMgr->assign('submissionsCount', $submissionsCount);
					return 'author.tpl';
			}
			case 'copyeditor':
				$copyeditorSubmissionDao =& DAORegistry::getDAO('CopyeditorSubmissionDAO');
				$submissionsCount = $copyeditorSubmissionDao->getSubmissionsCount($userId, $journalId);
				$templateMgr->assign('submissionsCount', $submissionsCount);
				return 'copyeditor.tpl';
			case 'layoutEditor':
				$layoutEditorSubmissionDao =& DAORegistry::getDAO('LayoutEditorSubmissionDAO');
				$submissionsCount = $layoutEditorSubmissionDao->getSubmissionsCount($userId, $journalId);
				$templateMgr->assign('submissionsCount', $submissionsCount);
				return 'layoutEditor.tpl';
			case 'editor':
				if (Request::getRequestedOp() == 'index') return null;
				$editorSubmissionDao =& DAORegistry::getDAO('EditorSubmissionDAO');
				$submissionsCount =& $editorSubmissionDao->getEditorSubmissionsCount($journal->getId());
				$templateMgr->assign('submissionsCount', $submissionsCount);
				return 'editor.tpl';
			case 'sectionEditor':
				$sectionEditorSubmissionDao =& DAORegistry::getDAO('SectionEditorSubmissionDAO');
				$submissionsCount =& $sectionEditorSubmissionDao->getSectionEditorSubmissionsCount($userId, $journalId);
				$templateMgr->assign('submissionsCount', $submissionsCount);
				return 'sectionEditor.tpl';
			case 'proofreader':
				$proofreaderSubmissionDao =& DAORegistry::getDAO('ProofreaderSubmissionDAO');
				$submissionsCount = $proofreaderSubmissionDao->getSubmissionsCount($userId, $journalId);
				$templateMgr->assign('submissionsCount', $submissionsCount);
				return 'proofreader.tpl';
			case 'reviewer':
				$reviewerSubmissionDao =& DAORegistry::getDAO('ReviewerSubmissionDAO');
				$submissionsCount = $reviewerSubmissionDao->getSubmissionsCount($userId, $journalId);
				$templateMgr->assign('submissionsCount', $submissionsCount);
				return 'reviewer.tpl';
		}
		return null;
	}
}

?>
