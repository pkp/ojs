<?php

/**
 * @defgroup author_form_submit
 */

/**
 * @file classes/author/form/submit/AuthorSubmitForm.inc.php
 *
 * Copyright (c) 2003-2010 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class AuthorSubmitForm
 * @ingroup author_form_submit
 *
 * @brief Base class for journal author submit forms.
 */

// $Id$


import('lib.pkp.classes.form.Form');

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
		$this->step = (int) $step;
		$this->article = $article;
		$this->articleId = $article ? $article->getId() : null;
	}

	/**
	 * Display the form.
	 */
	function display() {
		$templateMgr =& TemplateManager::getManager();
		$templateMgr->assign('articleId', $this->articleId);
		$templateMgr->assign('submitStep', $this->step);

		if (isset($this->article)) {
			$templateMgr->assign('submissionProgress', $this->article->getSubmissionProgress());
		}

		switch($this->step) {
			case 3:
				$helpTopicId = 'submission.indexingAndMetadata';
				break;
			case 4:
				$helpTopicId = 'submission.supplementaryFiles';
				break;
			default:
				$helpTopicId = 'submission.index';
		}
		$templateMgr->assign('helpTopicId', $helpTopicId);

		$journal =& Request::getJournal();
		$settingsDao =& DAORegistry::getDAO('JournalSettingsDAO');
		$templateMgr->assign_by_ref('journalSettings', $settingsDao->getJournalSettings($journal->getId()));

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
		$sectionEditors =& $sectionEditorsDao->getEditorsBySectionId($journal->getId(), $sectionId);

		foreach ($sectionEditors as $sectionEditorEntry) {
			$editAssignment = new EditAssignment();
			$editAssignment->setArticleId($article->getId());
			$editAssignment->setEditorId($sectionEditorEntry['user']->getId());
			$editAssignment->setCanReview($sectionEditorEntry['canReview']);
			$editAssignment->setCanEdit($sectionEditorEntry['canEdit']);
			$editAssignmentDao->insertEditAssignment($editAssignment);
			unset($editAssignment);
		}

		return $sectionEditors;
	}
}

?>
