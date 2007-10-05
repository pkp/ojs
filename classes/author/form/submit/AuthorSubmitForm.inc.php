<?php

/**
 * @file AuthorSubmitForm.inc.php
 *
 * Copyright (c) 2003-2007 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package author.form.submit
 * @class AuthorSubmitForm
 *
 * Base class for journal author submit forms.
 *
 * $Id$
 */

import('form.Form');

class AuthorSubmitForm extends Form {

	/** @var int the ID of the article */
	var $articleId;

	/** @var Article current article */
	var $article;

	/** @var int the current step */
	var $step;

	/**
	 * Constructor.
	 * @param $article object
	 * @param $step int
	 */
	function AuthorSubmitForm($article, $step) {
		parent::Form(sprintf('author/submit/step%d.tpl', $step));
		$this->addCheck(new FormValidatorPost($this));
		$this->step = $step;
		$this->article = $article;
		$this->articleId = $article ? $article->getArticleId() : null;
	}

	/**
	 * Display the form.
	 */
	function display() {
		$templateMgr = &TemplateManager::getManager();
		$templateMgr->assign('articleId', $this->articleId);
		$templateMgr->assign('submitStep', $this->step);

		if (isset($this->article)) {
			$templateMgr->assign('submissionProgress', $this->article->getSubmissionProgress());
		}

		switch($this->step) {
			case '2':
				$helpTopicId = 'submission.indexingAndMetadata';
				break;
			case '4':
				$helpTopicId = 'submission.supplementaryFiles';
				break;
			default:
				$helpTopicId = 'submission.index';
		}
		$templateMgr->assign('helpTopicId', $helpTopicId);

		$journal = &Request::getJournal();
		$settingsDao = &DAORegistry::getDAO('JournalSettingsDAO');
		$templateMgr->assign_by_ref('journalSettings', $settingsDao->getJournalSettings($journal->getJournalId()));

		parent::display();
	}

	/**
	 * Automatically assign Section Editors to new submissions.
	 * @param $article object
	 * @return array of section editors
	 */
	function assignEditors(&$article) {
		$sectionId = $article->getSectionId();
		$journal =& Request::getJournal();

		$sectionEditorsDao =& DAORegistry::getDAO('SectionEditorsDAO');
		$editAssignmentDao =& DAORegistry::getDAO('EditAssignmentDAO');
		$sectionEditors =& $sectionEditorsDao->getEditorsBySectionId($journal->getJournalId(), $sectionId);

		foreach ($sectionEditors as $sectionEditorEntry) {
			$editAssignment =& new EditAssignment();
			$editAssignment->setArticleId($article->getArticleId());
			$editAssignment->setEditorId($sectionEditorEntry['user']->getUserId());
			$editAssignment->setCanEdit($sectionEditorEntry['canReview']);
			$editAssignment->setCanReview($sectionEditorEntry['canEdit']);
			$editAssignmentDao->insertEditAssignment($editAssignment);
			unset($editAssignment);
		}

		return $sectionEditors;
	}
}

?>
