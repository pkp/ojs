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
		if (isset($this->article)) {
			$article = &$this->article;
			$this->_data = array(
				'authors' => array(),
				'title' => $article->getTitle(),
				'abstract' => $article->getAbstract(),
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
	 * Assign form data to user-submitted data.
	 */
	function readInputData() {
		$this->readUserVars(
			array(
				'authors',
				'deletedAuthors',
				'primaryContact',
				'title',
				'abstract',
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
		$article->setAbstract($this->getData('abstract'));
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
			$article->setSubmissionProgress($this->step + 1);
		}
		
		// Update authors
		$article->setAuthors(array());
		$authors = $this->getData('authors');
		for ($i=0, $count=count($authors); $i < $count; $i++) {
			$author = &new Author();
			$author->setAuthorId($authors[$i]['authorId']);
			$author->setFirstName($authors[$i]['firstName']);
			$author->setMiddleName($authors[$i]['middleName']);
			$author->setLastName($authors[$i]['lastName']);
			$author->setAffiliation($authors[$i]['affiliation']);
			$author->setEmail($authors[$i]['email']);
			$author->setBiography($authors[$i]['biography']);
			$author->setPrimaryContact($this->getData('primaryContact') == $i ? 1 : 0);
			
			$article->addAuthor($author);
		}
		$articleDao->updateArticle($article);
		
		// Remove deleted authors
		$deletedAuthors = explode(':', $this->getData('deletedAuthors'));
		for ($i=0, $count=count($deletedAuthors); $i < $count; $i++) {
			$authorDao->deleteAuthorById($deletedAuthors[$i], $this->articleId);
		}
		
		return $this->articleId;
	}
	
}

?>
