<?php

/**
 * AuthorSubmitStep2Form.inc.php
 *
 * Copyright (c) 2003-2004 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package author.form.submit
 *
 * Form for Step 2 of author article submission.
 *
 * $Id$
 */

import("author.form.submit.AuthorSubmitForm");

class AuthorSubmitStep2Form extends AuthorSubmitForm {
	
	/**
	 * Constructor.
	 */
	function AuthorSubmitStep2Form($articleId) {
		parent::AuthorSubmitForm($articleId, 2);
		
		$journal = &Request::getJournal();
		
		// Validation checks for this form
		$this->addCheck(new FormValidatorCustom(&$this, 'authors', 'required', 'author.submit.form.authorRequired', create_function('$authors', 'return count($authors) > 0;')));
		$this->addCheck(new FormValidatorArray(&$this, 'authors', 'required', 'author.submit.form.authorRequiredFields', array('firstName', 'lastName', 'email')));
		$this->addCheck(new FormValidator(&$this, 'title', 'required', 'author.submit.form.titleRequired'));
	}
	
	/**
	 * Initialize form data from current article.
	 */
	function initData() {
		$sectionDao = &DAORegistry::getDAO('SectionDAO');

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
				'sponsor' => $article->getSponsor(),
				'section' => $sectionDao->getSection($article->getSectionId())
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

		// Load the section. This is used in the step 2 form to
		// determine whether or not to display indexing options.
		$sectionDao = &DAORegistry::getDAO('SectionDAO');
		$this->_data['section'] = &$sectionDao->getSection($this->article->getSectionId());
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
		if ($article->getSubmissionProgress() <= $this->step) {
			$article->stampStatusModified();
			$article->setSubmissionProgress($this->step + 1);
		}
		
		// Update authors
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
			}
		}
		
		// Remove deleted authors
		$deletedAuthors = explode(':', $this->getData('deletedAuthors'));
		for ($i=0, $count=count($deletedAuthors); $i < $count; $i++) {
			$article->removeAuthor($deletedAuthors[$i]);
		}
		
		// Save the article
		$articleDao->updateArticle($article);
		
		return $this->articleId;
	}
	
}

?>
