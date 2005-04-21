<?php

/**
 * MetadataForm.inc.php
 *
 * Copyright (c) 2003-2004 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package submission.form
 *
 * Form to change metadata information for a submission.
 *
 * $Id$
 */

class MetadataForm extends Form {
	/** @var Article current article */
	var $article;
	
	/** @var boolean can edit metadata */
	var $canEdit;
	
	/** @var boolean can view authors */
	var $canViewAuthors;
	
	/**
	 * Constructor.
	 */
	function MetadataForm($article) {
		$roleDao = &DAORegistry::getDAO('RoleDAO');
		$copyAssignmentDao = &DAORegistry::getDAO('CopyAssignmentDAO');

		$user = &Request::getUser();
		$roleId = $roleDao->getRoleIdFromPath(Request::getRequestedPage());
		
		// If the user is an editor or an author of this article, make the form editable.
		$this->canEdit = false;
		if ($roleId != null && ($roleId == ROLE_ID_EDITOR || $roleId == ROLE_ID_SECTION_EDITOR || $roleId == ROLE_ID_AUTHOR)) {
			$this->canEdit = true;
		}

		// Copy editors are also allowed to edit metadata, but only if they have
		// a current assignment to the article.
		if ($roleId != null && ($roleId == ROLE_ID_COPYEDITOR)) {
			$copyAssignment = $copyAssignmentDao->getCopyAssignmentByArticleId($article->getArticleId());
			if ($copyAssignment != null) {
				if ($copyAssignment->getDateNotified() != null && $copyAssignment->getDateFinalCompleted() == null) {
					$this->canEdit = true;
				}
			}
		}

		if ($this->canEdit) {
			parent::Form('submission/metadata/metadataEdit.tpl');
		} else {
			parent::Form('submission/metadata/metadataView.tpl');
		}
		
		// If the user is a reviewer of this article, do not show authors.
		$this->canViewAuthors = true;
		if ($roleId != null && $roleId == ROLE_ID_REVIEWER) {
			$this->canViewAuthors = false;
		}
		
		$this->article = $article;
	}
	
	/**
	 * Initialize form data from current article.
	 */
	function initData() {
		if (isset($this->article)) {
			$article = &$this->article;
			$this->_data = array(
				'authors' => array(),
				'title' => $article->getTitle(),
				'titleAlt1' => $article->getTitleAlt1(),
				'titleAlt2' => $article->getTitleAlt2(),
				'abstract' => $article->getAbstract(),
				'abstractAlt1' => $article->getAbstractAlt1(),
				'abstractAlt2' => $article->getAbstractAlt2(),
				'discipline' => $article->getDiscipline(),
				'subjectClass' => $article->getSubjectClass(),
				'subject' => $article->getSubject(),
				'coverageGeo' => $article->getCoverageGeo(),
				'coverageChron' => $article->getCoverageChron(),
				'coverageSample' => $article->getCoverageSample(),
				'type' => $article->getType(),
				'language' => $article->getLanguage(),
				'sponsor' => $article->getSponsor()
			);
		
			$authors = &$article->getAuthors();
			for ($i=0, $count=count($authors); $i < $count; $i++) {
				array_push(
					$this->_data['authors'],
					array(
						'authorId' => $authors[$i]->getAuthorId(),
						'firstName' => $authors[$i]->getFirstName(),
						'middleName' => $authors[$i]->getMiddleName(),
						'lastName' => $authors[$i]->getLastName(),
						'affiliation' => $authors[$i]->getAffiliation(),
						'email' => $authors[$i]->getEmail(),
						'biography' => $authors[$i]->getBiography()
					)
				);
				if ($authors[$i]->getPrimaryContact()) {
					$this->setData('primaryContact', $i);
				}
			}
		}
	}
	
	/**
	 * Display the form.
	 */
	function display() {
		$journal = &Request::getJournal();
		$settingsDao = &DAORegistry::getDAO('JournalSettingsDAO');
		$roleDao = &DAORegistry::getDAO('RoleDAO');
		
		$templateMgr = &TemplateManager::getManager();
		$templateMgr->assign('articleId', isset($this->article)?$this->article->getArticleId():null);
		$templateMgr->assign('journalSettings', $settingsDao->getJournalSettings($journal->getJournalId()));
		$templateMgr->assign('rolePath', Request::getRequestedPage());
		$templateMgr->assign('canViewAuthors', $this->canViewAuthors);
		$templateMgr->assign('helpTopicId','submission.indexingAndMetadata');
		parent::display();
	}
	
	
	/**
	 * Assign form data to user-submitted data.
	 */
	function readInputData() {
		$this->readUserVars(
			array(
				'authors',
				'deletedAuthors',
				'primaryContact',
				'title',
				'titleAlt1',
				'titleAlt2',
				'abstract',
				'abstractAlt1',
				'abstractAlt2',
				'discipline',
				'subjectClass',
				'subject',
				'coverageGeo',
				'coverageChron',
				'coverageSample',
				'type',
				'language',
				'sponsor'
			)
		);
	}

	/**
	 * Save changes to article.
	 * @return int the article ID
	 */
	function execute() {
		$articleDao = &DAORegistry::getDAO('ArticleDAO');
		$authorDao = &DAORegistry::getDAO('AuthorDAO');
		
		// Update article
	
		$article = &$this->article;
		$article->setTitle($this->getData('title'));
		$article->setTitleAlt1($this->getData('titleAlt1'));
		$article->setTitleAlt2($this->getData('titleAlt2'));
		$article->setAbstract($this->getData('abstract'));
		$article->setAbstractAlt1($this->getData('abstractAlt1'));
		$article->setAbstractAlt2($this->getData('abstractAlt2'));
		$article->setDiscipline($this->getData('discipline'));
		$article->setSubjectClass($this->getData('subjectClass'));
		$article->setSubject($this->getData('subject'));
		$article->setCoverageGeo($this->getData('coverageGeo'));
		$article->setCoverageChron($this->getData('coverageChron'));
		$article->setCoverageSample($this->getData('coverageSample'));
		$article->setType($this->getData('type'));
		$article->setLanguage($this->getData('language'));
		$article->setSponsor($this->getData('sponsor'));
		
		// Update authors
		$authorText = array();
		$authors = $this->getData('authors');
		for ($i=0, $count=count($authors); $i < $count; $i++) {
			if ($authors[$i]['authorId'] > 0) {
				// Update an existing author
				$author = &$article->getAuthor($authors[$i]['authorId']);
				$isExistingAuthor = true;
				
			} else {
				// Create a new author
				$author = &new Author();
				$isExistingAuthor = false;
			}
			
			if ($author != null) {
				$author->setFirstName($authors[$i]['firstName']);
				$author->setMiddleName($authors[$i]['middleName']);
				$author->setLastName($authors[$i]['lastName']);
				$author->setAffiliation($authors[$i]['affiliation']);
				$author->setEmail($authors[$i]['email']);
				$author->setBiography($authors[$i]['biography']);
				$author->setPrimaryContact($this->getData('primaryContact') == $i ? 1 : 0);
				$author->setSequence($authors[$i]['seq']);
				
				if ($isExistingAuthor == false) {
					$article->addAuthor($author);
				}
				
				array_push($authorText, $author->getFirstName());
				array_push($authorText, $author->getMiddleName());
				array_push($authorText, $author->getLastName());
				array_push($authorText, $author->getAffiliation());
				array_push($authorText, $author->getBiography());
			}
		}
		
		// Remove deleted authors
		$deletedAuthors = explode(':', $this->getData('deletedAuthors'));
		for ($i=0, $count=count($deletedAuthors); $i < $count; $i++) {
			$article->removeAuthor($deletedAuthors[$i]);
		}
		
		// Save the article
		$articleDao->updateArticle($article);
		
		// Update search index
		$articleId = $this->article->getArticleId();
		ArticleSearchIndex::updateTextIndex($articleId, ARTICLE_SEARCH_AUTHOR, $authorText);
		ArticleSearchIndex::updateTextIndex($articleId, ARTICLE_SEARCH_TITLE, array($article->getTitle(), $article->getTitleAlt1(), $article->getTitleAlt2()));
		ArticleSearchIndex::updateTextIndex($articleId, ARTICLE_SEARCH_ABSTRACT, array($article->getAbstract(), $article->getAbstractAlt1(), $article->getAbstractAlt2()));
		ArticleSearchIndex::updateTextIndex($articleId, ARTICLE_SEARCH_DISCIPLINE, $article->getDiscipline());
		ArticleSearchIndex::updateTextIndex($articleId, ARTICLE_SEARCH_SUBJECT, array($article->getSubjectClass(), $article->getSubject()));
		ArticleSearchIndex::updateTextIndex($articleId, ARTICLE_SEARCH_TYPE, $article->getType());
		ArticleSearchIndex::updateTextIndex($articleId, ARTICLE_SEARCH_COVERAGE, array($article->getCoverageGeo(), $article->getCoverageChron(), $article->getCoverageSample()));
		// FIXME Index sponsors too?
		
		return $articleId;
	}
	
}

?>
