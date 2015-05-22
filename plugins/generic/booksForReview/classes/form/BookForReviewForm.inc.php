<?php

/**
 * @file plugins/generic/booksForReview/classes/form/BookForReviewForm.inc.php
 *
 * Copyright (c) 2013-2015 Simon Fraser University Library
 * Copyright (c) 2003-2015 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class BookForReviewForm
 * @ingroup plugins_generic_bookForReview
 *
 * @brief Form for journal managers to create/edit books for review.
 */

import('lib.pkp.classes.form.Form');

define('BFR_COVER_PAGE_IMAGE_NAME', 'coverPage');

class BookForReviewForm extends Form {
	/** @var $parentPluginName string Name of parent plugin */
	var $parentPluginName;

	/** @var book BookForReview the book being edited */
	var $book;

	/** @var validStatus array keys are valid status values */
	var $validStatus;

	/** @var validAuthorTypes array keys are valid author type values */
	var $validAuthorTypes;

	/** @var validLanguages array keys are valid language code values */
	var $validLanguages;

	/** @var validEditions array keys are valid edition values */
	var $validEditions;

	/**
	 * Constructor
	 * @param bookId int leave as default for new book
	 */
	function BookForReviewForm($parentPluginName, $bookId = null) {
		$this->parentPluginName = $parentPluginName;
		$bfrPlugin =& PluginRegistry::getPlugin('generic', $parentPluginName);
		$bfrPlugin->import('classes.BookForReview');

		$bfrDao =& DAORegistry::getDAO('BookForReviewDAO');
		if (!empty($bookId)) {
			$this->book =& $bfrDao->getBookForReview((int) $bookId);
		} else {
			$this->book = null;
		}

		$this->validStatus = array (
			BFR_STATUS_AVAILABLE => __('plugins.generic.booksForReview.status.available'),
			BFR_STATUS_REQUESTED => __('plugins.generic.booksForReview.status.requested'),
			BFR_STATUS_ASSIGNED => __('plugins.generic.booksForReview.status.assigned'),
			BFR_STATUS_MAILED => __('plugins.generic.booksForReview.status.mailed'),
			BFR_STATUS_SUBMITTED => __('plugins.generic.booksForReview.status.submitted')
		);

		$this->validAuthorTypes = array (
			BFR_AUTHOR_TYPE_BY => __('plugins.generic.booksForReview.authorType.by'),
			BFR_AUTHOR_TYPE_EDITED_BY => __('plugins.generic.booksForReview.authorType.editedBy')
		);

		$languageDao =& DAORegistry::getDAO('LanguageDAO');
		$languages =& $languageDao->getLanguages();
		$this->validLanguages = array();
		while (list(, $language) = each($languages)) {
			$this->validLanguages[$language->getCode()] = $language->getName();
		}

		$this->validEditions = array_merge( array(0 => ''), range(1,20));

		$journal =& Request::getJournal();
		parent::Form($bfrPlugin->getTemplatePath() . 'editor' . '/' . 'bookForReviewForm.tpl');

		// Title is provided
		$this->addCheck(new FormValidatorLocale($this, 'title', 'required', 'plugins.generic.booksForReview.editor.form.titleRequired'));

		// Author Type is provided and is valid value
		$this->addCheck(new FormValidator($this, 'authorType', 'required', 'plugins.generic.booksForReview.editor.form.authorTypeRequired'));	
		$this->addCheck(new FormValidatorInSet($this, 'authorType', 'required', 'plugins.generic.booksForReview.editor.form.authorTypeValid', array_keys($this->validAuthorTypes)));

		// Authors are provided
		$this->addCheck(new FormValidatorArray($this, 'authors', 'required', 'plugins.generic.booksForReview.editor.form.authorRequiredFields', array('firstName', 'lastName')));

		// Publisher is provided
		$this->addCheck(new FormValidator($this, 'publisher', 'required', 'plugins.generic.booksForReview.editor.form.publisherRequired'));

		// Year is provided and is a valid value
		$this->addCheck(new FormValidator($this, 'year', 'required', 'plugins.generic.booksForReview.editor.form.yearRequired'));
		$this->addCheck(new FormValidatorCustom($this, 'year', 'required', 'plugins.generic.booksForReview.editor.form.yearValid', create_function('$year', 'return $year > 1900 && $year < 2100 ? true : false;'), array()));

		// Language is provided and is valid value
		$this->addCheck(new FormValidator($this, 'language', 'required', 'plugins.generic.booksForReview.editor.form.languageRequired'));	
		$this->addCheck(new FormValidatorInSet($this, 'language', 'required', 'plugins.generic.booksForReview.editor.form.languageValid', array_keys($this->validLanguages)));

		// If provided, edition is valid value
		$this->addCheck(new FormValidatorInSet($this, 'edition', 'optional', 'plugins.generic.booksForReview.editor.form.editionValid', array_keys($this->validEditions)));

		// If provided, pages is a valid value
		$this->addCheck(new FormValidatorCustom($this, 'pages', 'optional', 'plugins.generic.booksForReview.editor.form.pagesValid', create_function('$pages', 'return $pages > 0 && $pages < 10000 ? true : false;'), array()));

		$this->addCheck(new FormValidatorPost($this));
	}

	/**
	 * Display the form.
	 */
	function display() {
		if ($this->book != null) {
			$book =& $this->book;

			$this->_data['bookId'] = $book->getId();
			$this->_data['status'] = $book->getStatus();
			$this->_data['dateRequested'] = $book->getDateRequested(); 
			$this->_data['dateAssigned'] = $book->getDateAssigned(); 
			$this->_data['dateMailed'] = $book->getDateMailed(); 
			$this->_data['dateDue'] = $book->getDateDue(); 
			$this->_data['dateSubmitted'] = $book->getDateSubmitted(); 
		}

		$templateMgr =& TemplateManager::getManager();
		$templateMgr->assign('bookForReview', $this->book);
		$templateMgr->assign('validStatus', $this->validStatus);
		$templateMgr->assign('validAuthorTypes', $this->validAuthorTypes);
		$templateMgr->assign('validLanguages', $this->validLanguages);
		$templateMgr->assign('validEditions', $this->validEditions);

		parent::display();
	}

	/**
	 * Initialize form data.
	 */
	function initData() {
		if ($this->book != null) {
			$book =& $this->book;

			$this->_data = array(
				'authorType' => $book->getAuthorType(),
				'publisher' => $book->getPublisher(),
				'year' => $book->getYear(),
				'language' => $book->getLanguage(),
				'copy' => $book->getCopy(),
				'url' => $book->getUrl(),
				'edition' => $book->getEdition(),
				'pages' => $book->getPages(),
				'isbn' => $book->getISBN(),
				'userId' => $book->getUserId(),
				'articleId' => $book->getArticleId(),
				'notes' => $book->getNotes(),
				'title' => $book->getTitle(null),  // Localized
				'description' => $book->getDescription(null), // Localized
				'authors' => array(),
				'deletedAuthors' => array(),
				'coverPageAltText' => $book->getCoverPageAltText(null), // Localized
				'originalFileName' => $book->getOriginalFileName(null), // Localized
				'fileName' => $book->getFileName(null), // Localized
				'width' => $book->getWidth(null), // Localized
				'height' => $book->getHeight(null) // Localized
			);

			$authors =& $book->getAuthors();
			for ($i=0, $count=count($authors); $i < $count; $i++) {
				array_push(
					$this->_data['authors'],
					array(
						'authorId' => $authors[$i]->getId(),
						'firstName' => $authors[$i]->getFirstName(),
						'middleName' => $authors[$i]->getMiddleName(),
						'lastName' => $authors[$i]->getLastName(),
						'seq' => $authors[$i]->getSequence()
					)
				);
			}
		}
	}

	/**
	 * Get the field names for which data can be localized
	 * @return array
	 */
	function getLocaleFieldNames() {
		return array(
			'title',
			'description',
			'coverPageAltText', 
			'originalFileName',
			'fileName',
			'width',
			'height'
		);
	}

	/**
	 * Assign form data to user-submitted data.
	 */
	function readInputData() {
		$this->readUserVars(
			array(
				'authorType',
				'publisher',
				'year',
				'language',
				'copy',
				'url',
				'edition',
				'pages',
				'isbn',
				'dateDueYear',
				'dateDueMonth',
				'dateDueDay',
				'userId',
				'articleId',
				'notes',
				'title',
				'description',
				'authors',
				'deletedAuthors',
				'coverPageAltText',
				'originalFileName',
				'fileName',
				'width',
				'height'
			)
		);

		if (!empty($this->_data['dateDueYear']) && !empty($this->_data['dateDueMonth']) && !empty($this->_data['dateDueDay'])) {
			$this->_data['dateDue'] = $this->_data['dateDueYear'] . '-' . $this->_data['dateDueMonth'] . '-' . $this->_data['dateDueDay'] . ' 00:00:00'; 
		} else {
			$this->_data['dateDue'] = '';
		}

		// If a url is provided, ensure it includes a proper prefix (i.e. http:// or https://).
		if (!empty($this->_data['url'])) {
			$this->addCheck(new FormValidatorCustom($this, 'url', 'required', 'plugins.generic.booksForReview.editor.form.urlPrefixIncluded', create_function('$url', 'return strpos(trim(strtolower_codesafe($url)), \'http://\') === 0 || strpos(trim(strtolower_codesafe($url)), \'https://\') === 0 ? true : false;'), array()));
		}
	}

	/**
	 * Check to ensure that the form is correctly validated.
	 */
	function validate() {
		// Verify that book cover image, if supplied, is actually an image.
		import('classes.file.PublicFileManager');
		$publicFileManager = new PublicFileManager();
		if ($publicFileManager->uploadedFileExists(BFR_COVER_PAGE_IMAGE_NAME)) {
			$type = $publicFileManager->getUploadedFileType(BFR_COVER_PAGE_IMAGE_NAME);
			$extension = $publicFileManager->getImageExtension($type);
			if (!$extension) {
				// Not a valid image.
				$this->addError('imageFile', __('submission.layout.imageInvalid'));
				return false;
			}
		}

		// Fall back on parent validation
		return parent::validate();
	}

	/**
	 * Save book. 
	 */
	function execute() {
		$bfrPlugin =& PluginRegistry::getPlugin('generic', $this->parentPluginName);
		$bfrPlugin->import('classes.BookForReview');
		$bfrPlugin->import('classes.BookForReviewAuthor');

		$bfrDao =& DAORegistry::getDAO('BookForReviewDAO');
		$journal =& Request::getJournal();
		$journalId = $journal->getId();
		$user =& Request::getUser();
		$editorId = $user->getId(); 

		if ($this->book == null) {
			$book = new BookForReview();
			$book->setJournalId($journalId);
			$book->setEditorId($editorId);
			$book->setStatus(BFR_STATUS_AVAILABLE);
			$book->setDateCreated(Core::getCurrentDate());
		} else {
			$book =& $this->book;
		}

		$book->setAuthorType($this->getData('authorType'));
		$book->setPublisher($this->getData('publisher'));
		$book->setYear($this->getData('year'));
		$book->setLanguage($this->getData('language'));
		$book->setCopy($this->getData('copy') == null ? 0 : 1);
		$book->setUrl($this->getData('url'));
		$book->setEdition($this->getData('edition') == 0 ? null : $this->getData('edition'));
		$book->setPages($this->getData('pages') == '' ? null : $this->getData('pages'));
		$book->setISBN($this->getData('isbn'));
		$book->setDateDue($this->getData('dateDue'));
		$book->setUserId($this->getData('userId'));
		$book->setArticleId($this->getData('articleId'));
		$book->setNotes($this->getData('notes'));
		$book->setTitle($this->getData('title'), null); // Localized	
		$book->setDescription($this->getData('description'), null); // Localized	
		$book->setCoverPageAltText($this->getData('coverPageAltText'), null); // Localized

		// Update authors
		$authors = $this->getData('authors');
		for ($i=0, $count=count($authors); $i < $count; $i++) {
			if ($authors[$i]['authorId'] > 0) {
				// Update an existing author
				$author =& $book->getAuthor($authors[$i]['authorId']);
				$isExistingAuthor = true;

			} else {
				// Create a new author
				// PHP4 Requires explicit instantiation-by-reference
				if (checkPhpVersion('5.0.0')) {
					$author = new BookForReviewAuthor();
				} else {
					$author =& new BookForReviewAuthor();
				}
				$isExistingAuthor = false;
			}

			if ($author != null) {
				$author->setFirstName($authors[$i]['firstName']);
				$author->setMiddleName($authors[$i]['middleName']);
				$author->setLastName($authors[$i]['lastName']);
				$author->setSequence($authors[$i]['seq']);

				if ($isExistingAuthor == false) {
					$book->addAuthor($author);
				}
			}
		}

		// Remove deleted authors
		$deletedAuthors = explode(':', $this->getData('deletedAuthors'));
		for ($i=0, $count=count($deletedAuthors); $i < $count; $i++) {
			$book->removeAuthor($deletedAuthors[$i]);
		}

		// Insert or update book for review
		if ($book->getId() == null) {
			$bfrDao->insertObject($book);
		} else {
			$bfrDao->updateObject($book);
		}

		// Handle book for review cover image
		import('classes.file.PublicFileManager');
		$publicFileManager = new PublicFileManager();
		$formLocale = $this->getFormLocale();
		if ($publicFileManager->uploadedFileExists(BFR_COVER_PAGE_IMAGE_NAME)) {
			$originalFileName = $publicFileManager->getUploadedFileName(BFR_COVER_PAGE_IMAGE_NAME);
			$type = $publicFileManager->getUploadedFileType(BFR_COVER_PAGE_IMAGE_NAME);
			$newFileName = 'cover_bfr_' . $book->getId() . '_' . $formLocale . $publicFileManager->getImageExtension($type);
			$publicFileManager->uploadJournalFile($journalId, BFR_COVER_PAGE_IMAGE_NAME, $newFileName);
			$book->setOriginalFileName($publicFileManager->truncateFileName($originalFileName, 127), $formLocale);
			$book->setFileName($newFileName, $formLocale);

			// Store the image dimensions
			list($width, $height) = getimagesize($publicFileManager->getJournalFilesPath($journalId) . '/' . $newFileName);
			$book->setWidth($width, $formLocale);
			$book->setHeight($height, $formLocale);
		
			$bfrDao->updateObject($book);
		}
	}
}

?>
