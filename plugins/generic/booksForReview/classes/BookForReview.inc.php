<?php

/**
 * @file plugins/generic/booksForReview/classes/BookForReview.inc.php
 *
 * Copyright (c) 2013-2014 Simon Fraser University Library
 * Copyright (c) 2003-2014 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class BookForReview
 * @ingroup plugins_generic_booksForReview
 * @see BookForReviewDAO
 *
 * @brief Basic class describing a book for review.
 */

define('BFR_STATUS_AVAILABLE',	0x01);
define('BFR_STATUS_REQUESTED',	0x02);
define('BFR_STATUS_ASSIGNED',	0x03);
define('BFR_STATUS_MAILED',		0x04);
define('BFR_STATUS_SUBMITTED',	0x05);

define('BFR_AUTHOR_TYPE_BY',		0x01);
define('BFR_AUTHOR_TYPE_EDITED_BY',	0x02);


class BookForReview extends DataObject {

	/** @var array BookForReviewAuthors of this book for review */
	var $authors;

	/** @var array IDs of BookForReviewAuthors removed from this book for review */
	var $removedAuthors;


	/**
	 * Constructor.
	 */
	function BookForReview() {
		parent::DataObject();
		$this->authors = array();
		$this->removedAuthors = array();
	}

	/**
	 * Add an author.
	 * @param $author BookForReviewAuthor
	 */
	function addAuthor($author) {
		if ($author->getBookId() == null) {
			$author->setBookId($this->getId());
		}
		if ($author->getSequence() == null) {
			$author->setSequence(count($this->authors) + 1);
		}
		array_push($this->authors, $author);
	}

	/**
	 * Remove an author.
	 * @param $authorId ID of the author to remove
	 * @return boolean author was removed
	 */
	function removeAuthor($authorId) {
		$found = false;

		if ($authorId != 0) {
			$authors = array();
			for ($i=0, $count=count($this->authors); $i < $count; $i++) {
				if ($this->authors[$i]->getId() == $authorId) {
					array_push($this->removedAuthors, $authorId);
					$found = true;
				} else {
					array_push($authors, $this->authors[$i]);
				}
			}
			$this->authors = $authors;
		}
		return $found;
	}

	/**
	 * Return string of author names, separated by the specified token
	 * @param $lastOnly boolean return list of lastnames only (default false)
	 * @param $separator string separator for names (default comma+space)
	 * @return string
	 */
	function getAuthorString($lastOnly = false, $separator = ', ') {
		$str = '';
		foreach ($this->authors as $a) {
			if (!empty($str)) {
				$str .= $separator;
			}
			$str .= $lastOnly ? $a->getLastName() : $a->getFullName();
		}
		return $str;
	}

	//
	// Get/set methods
	//

	/**
	 * Get all authors of this book for review.
	 * @return array BookForReviewAuthors
	 */
	function &getAuthors() {
		return $this->authors;
	}

	/**
	 * Get a specific author of this book for review.
	 * @param $authorId int
	 * @return BookForReviewAuthor
	 */
	function &getAuthor($authorId) {
		$author = null;

		if ($authorId != 0) {
			for ($i=0, $count=count($this->authors); $i < $count && $author == null; $i++) {
				if ($this->authors[$i]->getId() == $authorId) {
					$author =& $this->authors[$i];
				}
			}
		}
		return $author;
	}

	/**
	 * Get the IDs of all authors removed from this book for review.
	 * @return array int
	 */
	function &getRemovedAuthors() {
		return $this->removedAuthors;
	}

	/**
	 * Set authors of this book for review.
	 * @param $authors array BookForReviewAuthors
	 */
	function setAuthors($authors) {
		return $this->authors = $authors;
	}

	/**
	 * Get the user assigned to the book for review.
	 * @return User
	 */
	function &getUser() {
		$userDao =& DAORegistry::getDAO('UserDAO');
		return $userDao->getUser($this->getData('userId'));
	}

	/**
	 * Get the user's full name assigned to the book for review.
	 * @return string 
	 */
	function getUserFullName() {
		$user =& $this->getUser();
		if ($user) return $user->getFullName(); else return '';
	}

	/**
	 * Get the user's email assigned to the book for review.
	 * @return string 
	 */
	function getUserEmail() {
		$user =& $this->getUser();
		if ($user) return $user->getEmail(); else return '';
	}

	/**
	 * Get the user's mailing address assigned to the book for review.
	 * @return string 
	 */
	function getUserMailingAddress() {
		$user =& $this->getUser();
		if ($user) return $user->getMailingAddress(); else return '';
	}

	/**
	 * Get the user's country assigned to the book for review.
	 * @return string 
	 */
	function getUserCountry() {
		$user =& $this->getUser();
		if ($user) return $user->getCountry(); else return '';
	}

	/**
	 * Get the user's contact signature assigned to the book for review.
	 * @return string 
	 */
	function getUserContactSignature() {
		$user =& $this->getUser();
		if ($user) return $user->getContactSignature(); else return '';
	}

	/**
	 * Get the editor assigned to the book for review.
	 * @return Editor
	 */
	function &getEditor() {
		$userDao =& DAORegistry::getDAO('UserDAO');
		return $userDao->getUser($this->getData('editorId'));
	}

	/**
	 * Get the editor's full name assigned to the book for review.
	 * @return string 
	 */
	function getEditorFullName() {
		$editor =& $this->getEditor();
		if ($editor) return $editor->getFullName(); else return '';
	}

	/**
	 * Get the editor's email assigned to the book for review.
	 * @return string 
	 */
	function getEditorEmail() {
		$editor =& $this->getEditor();
		if ($editor) return $editor->getEmail(); else return '';
	}

	/**
	 * Get the editor's contact signature assigned to the book for review.
	 * @return string 
	 */
	function getEditorContactSignature() {
		$editor =& $this->getEditor();
		if ($editor) return $editor->getContactSignature(); else return '';
	}

	/**
	 * Get the editor's initials assigned to the book for review.
	 * @return string 
	 */
	function getEditorInitials() {
		$editor =& $this->getEditor();
		if ($editor) {
			$initials = $editor->getInitials();
			if (!empty($initials)) {
				return $initials;
			} else {
				return substr($editor->getFirstName(), 0, 1) . substr($editor->getLastName(), 0, 1);
			}
		}
	}

	/**
	 * Get the ID of the book for review.
	 * @return int
	 */
	function getId() {
		return $this->getData('bookId');
	}

	/**
	 * Set the ID of the book for review.
	 * @param $book for reviewId int
	 */
	function setId($bookId) {
		return $this->setData('bookId', $bookId);
	}

	/**
	 * Get the journal ID of the book for review.
	 * @return int
	 */
	function getJournalId() {
		return $this->getData('journalId');
	}

	/**
	 * Set the journal ID of the book for review.
	 * @param $journalId int
	 */
	function setJournalId($journalId) {
		return $this->setData('journalId', $journalId);
	}

	/**
	 * Get the status of the book for review.
	 * @return int
	 */
	function getStatus() {
		return $this->getData('status');
	}

	/**
	 * Set the status of the book for review.
	 * @param $status int
	 */
	function setStatus($status) {
		return $this->setData('status', $status);
	}

	/**
	 * Get book for review status locale key.
	 * @return int 
	 */
	function getStatusString() {
		switch ($this->getData('status')) {
			case BFR_STATUS_AVAILABLE:
				return 'plugins.generic.booksForReview.status.available';
			case BFR_STATUS_REQUESTED:
				return 'plugins.generic.booksForReview.status.requested';
			case BFR_STATUS_ASSIGNED:
				return 'plugins.generic.booksForReview.status.assigned';
			case BFR_STATUS_MAILED:
				return 'plugins.generic.booksForReview.status.mailed';
			case BFR_STATUS_SUBMITTED:
				return 'plugins.generic.booksForReview.status.submitted';
			default:
				return 'plugins.generic.booksForReview.status';
		}
	}

	/**
	 * Get the localized title of the book for review.
	 * @return string
	 */
	function getLocalizedTitle() {
		return $this->getLocalizedData('title');
	}

	/**
	 * Get the title of the book for review.
	 * @param $locale
	 * @return string
	 */
	function getTitle($locale) {
		return $this->getData('title', $locale);
	}

	/**
	 * Set the title of the book for review.
	 * @param $title string
	 * @param $locale
	 */
	function setTitle($title, $locale) {
		return $this->setData('title', $title?$title:'', $locale);
	}

	/**
	 * Get the localized description of the book for review.
	 * @return string
	 */
	function getLocalizedDescription() {
		return $this->getLocalizedData('description');
	}

	/**
	 * Get the localized, truncated description of the book for review.
	 * @return string
	 */
	function getLocalizedDescriptionShort() {
		$end ='';
		if (String::strlen($this->getLocalizedData('description'))) {
			$end = ' ...';
		}
		return String::substr($this->getLocalizedData('description'), 0, 250) . $end;
	}

	/**
	 * Get the description of the book for review.
	 * @param $locale
	 * @return string
	 */
	function getDescription($locale) {
		return $this->getData('description', $locale);
	}

	/**
	 * Set the description of the book for review.
	 * @param $description string
	 * @param $locale
	 */
	function setDescription($description, $locale) {
		return $this->setData('description', $description?$description:'', $locale);
	}

	/**
	 * Get the user ID of the book for review.
	 * @return int
	 */
	function getUserId() {
		return $this->getData('userId');
	}

	/**
	 * Set the user ID of the book for review.
	 * @param $user int
	 */
	function setUserId($userId) {
		return $this->setData('userId', $userId);
	}

	/**
	 * Get the editor ID of the book for review.
	 * @return int
	 */
	function getEditorId() {
		return $this->getData('editorId');
	}

	/**
	 * Set the editor ID of the book for review.
	 * @param $editor int
	 */
	function setEditorId($editorId) {
		return $this->setData('editorId', $editorId);
	}

	/**
	 * Get the authorType of the book for review.
	 * @return int
	 */
	function getAuthorType() {
		return $this->getData('authorType');
	}

	/**
	 * Set the authorType of the book for review.
	 * @param $authorType int
	 */
	function setAuthorType($authorType) {
		return $this->setData('authorType', $authorType);
	}

	/**
	 * Get the authorType string for the book for review.
	 * @return int
	 */
	function getAuthorTypeString() {
		switch ($this->getData('authorType')) {
			case BFR_AUTHOR_TYPE_BY:
				return 'plugins.generic.booksForReview.authorType.by';
			case BFR_AUTHOR_TYPE_EDITED_BY:
				return 'plugins.generic.booksForReview.authorType.editedBy';
			default:
				return 'plugins.generic.booksForReview.authorType.by';
		}
	}

	/**
	 * Get the publisher of the book for review.
	 * @return int
	 */
	function getPublisher() {
		return $this->getData('publisher');
	}

	/**
	 * Set the publisher of the book for review.
	 * @param $publisher string
	 */
	function setPublisher($publisher) {
		return $this->setData('publisher', $publisher);
	}

	/**
	 * Get the publisher url of the book for review.
	 * @return string
	 */
	function getUrl() {
		return $this->getData('url');
	}

	/**
	 * Set the publisher url of the book for review.
	 * @param $publisherUrl string
	 */
	function setUrl($url) {
		return $this->setData('url', $url);
	}

	/**
	 * Get the publication year of the book for review.
	 * @return int
	 */
	function getYear() {
		return $this->getData('year');
	}

	/**
	 * Set the publication year of the book for review.
	 * @param $year int
	 */
	function setYear($year) {
		return $this->setData('year', $year);
	}

	/**
	 * Get the language of the book for review.
	 * @return string
	 */
	function getLanguage() {
		return $this->getData('language');
	}

	/**
	 * Get the language (string) of the book for review.
	 * @return string
	 */
	function getLanguageString() {
		$languageDao =& DAORegistry::getDAO('LanguageDAO');
		$language =& $languageDao->getLanguageByCode($this->getData('language'));
		if ($language) return $language->getName();
	}

	/**
	 * Set the language of the book for review.
	 * @param $language string
	 */
	function setLanguage($language) {
		return $this->setData('language', $language);
	}

	/**
	 * Get the copy available of the book for review.
	 * @return int
	 */
	function getCopy() {
		return $this->getData('copy');
	}

	/**
	 * Set the copy available of the book for review.
	 * @param $copy int
	 */
	function setCopy($copy) {
		return $this->setData('copy', $copy);
	}

	/**
	 * Get the edition number of the book for review.
	 * @return int
	 */
	function getEdition() {
		return $this->getData('edition');
	}

	/**
	 * Set the edition number of the book for review.
	 * @param $edition int
	 */
	function setEdition($edition) {
		return $this->setData('edition', $edition);
	}

	/**
	 * Get the number of pages of the book for review.
	 * @return int
	 */
	function getPages() {
		return $this->getData('pages');
	}

	/**
	 * Set the number of pages of the book for review.
	 * @param $pages int
	 */
	function setPages($pages) {
		return $this->setData('pages', $pages);
	}

	/**
	 * Get the ISBN of the book for review.
	 * @return string
	 */
	function getISBN() {
		return $this->getData('isbn');
	}

	/**
	 * Set the ISBN of the book for review.
	 * @param $isbn string
	 */
	function setISBN($isbn) {
		return $this->setData('isbn', $isbn);
	}

	/**
	 * Get the dateCreated of the book for review.
	 * @return date
	 */
	function getDateCreated() {
		return $this->getData('dateCreated');
	}

	/**
	 * Set the dateCreated of the book for review.
	 * @param $dateCreated date
	 */
	function setDateCreated($dateCreated) {
		return $this->setData('dateCreated', $dateCreated);
	}

	/**
	 * Get the dateRequested of the book for review.
	 * @return date
	 */
	function getDateRequested() {
		return $this->getData('dateRequested');
	}

	/**
	 * Set the dateRequested of the book for review.
	 * @param $dateRequested date
	 */
	function setDateRequested($dateRequested) {
		return $this->setData('dateRequested', $dateRequested);
	}

	/**
	 * Get the dateAssigned of the book for review.
	 * @return date
	 */
	function getDateAssigned() {
		return $this->getData('dateAssigned');
	}

	/**
	 * Set the dateAssigned of the book for review.
	 * @param $dateAssigned date
	 */
	function setDateAssigned($dateAssigned) {
		return $this->setData('dateAssigned', $dateAssigned);
	}

	/**
	 * Get the dateMailed of the book for review.
	 * @return date
	 */
	function getDateMailed() {
		return $this->getData('dateMailed');
	}

	/**
	 * Set the dateMailed of the book for review.
	 * @param $dateMailed date
	 */
	function setDateMailed($dateMailed) {
		return $this->setData('dateMailed', $dateMailed);
	}

	/**
	 * Get the dateDue of the book for review.
	 * @return date
	 */
	function getDateDue() {
		return $this->getData('dateDue');
	}

	/**
	 * Set the dateDue of the book for review.
	 * @param $dateDue date
	 */
	function setDateDue($dateDue) {
		return $this->setData('dateDue', $dateDue);
	}

	/**
	 * Check whether book for review is past due date 
	 */
	function isLate() {
		$dateDue = $this->getData('dateDue');
		if (!empty($dateDue)) {
			if (strtotime($dateDue) > time()) {
				return false;
			} else {
				return true;
			}
		} else {
			return false;
		}
	}

	/**
	 * Get the dateSubmitted of the book for review.
	 * @return date
	 */
	function getDateSubmitted() {
		return $this->getData('dateSubmitted');
	}

	/**
	 * Set the dateSubmitted of the book for review.
	 * @param $dateSubmitted date
	 */
	function setDateSubmitted($dateSubmitted) {
		return $this->setData('dateSubmitted', $dateSubmitted);
	}

	/**
	 * Get the articleId of the book for review.
	 * @return int
	 */
	function getArticleId() {
		return $this->getData('articleId');
	}

	/**
	 * Set the articleId of the book for review.
	 * @param $articleId int
	 */
	function setArticleId($articleId) {
		return $this->setData('articleId', $articleId);
	}

	/**
	 * Get the notes of the book for review.
	 * @return string
	 */
	function getNotes() {
		return $this->getData('notes');
	}

	/**
	 * Set the notes of the book for review.
	 * @param $notes string
	 */
	function setNotes($notes) {
		return $this->setData('notes', $notes);
	}

	/**
	 * Get the localized book for review cover filename
	 * @return string
	 */
	function getLocalizedFileName() {
		return $this->getLocalizedData('fileName');
	}

	/**
	 * get file name
	 * @param $locale string
	 * @return string
	 */
	function getFileName($locale) {
		return $this->getData('fileName', $locale);
	}

	/**
	 * set file name
	 * @param $fileName string
	 * @param $locale string
	 */
	function setFileName($fileName, $locale) {
		return $this->setData('fileName', $fileName?$fileName:'', $locale);
	}

	/**
	 * Get the localized book for review cover width
	 * @return string
	 */
	function getLocalizedWidth() {
		return $this->getLocalizedData('width');
	}

	/**
	 * get width of cover page image
	 * @param $locale string
	 * @return string
	 */
	function getWidth($locale) {
		return $this->getData('width', $locale);
	}

	/**
	 * set width of cover page image
	 * @param $locale string
	 * @param $width int
	 */
	function setWidth($width, $locale) {
		return $this->setData('width', $width?$width:'', $locale);
	}

	/**
	 * Get the localized book for review cover height
	 * @return string
	 */
	function getLocalizedHeight() {
		return $this->getLocalizedData('height');
	}

	/**
	 * get height of cover page image
	 * @param $locale string
	 * @return string
	 */
	function getHeight($locale) {
		return $this->getData('height', $locale);
	}

	/**
	 * set height of cover page image
	 * @param $locale string
	 * @param $height int
	 */
	function setHeight($height, $locale) {
		return $this->setData('height', $height?$height:'', $locale);
	}

	/**
	 * Get the localized book for review cover filename on the uploader's computer
	 * @return string
	 */
	function getLocalizedOriginalFileName() {
		return $this->getLocalizedData('originalFileName');
	}

	/**
	 * get original file name
	 * @param $locale string
	 * @return string
	 */
	function getOriginalFileName($locale) {
		return $this->getData('originalFileName', $locale);
	}

	/**
	 * set original file name
	 * @param $originalFileName string
	 * @param $locale string
	 */
	function setOriginalFileName($originalFileName, $locale) {
		return $this->setData('originalFileName', $originalFileName?$originalFileName:'', $locale);
	}

	/**
	 * Get the localized book for review cover alternate text
	 * @return string
	 */
	function getLocalizedCoverPageAltText() {
		return $this->getLocalizedData('coverPageAltText');
	}

	/**
	 * get cover page alternate text
	 * @param $locale string
	 * @return string
	 */
	function getCoverPageAltText($locale) {
		return $this->getData('coverPageAltText', $locale);
	}

	/**
	 * set cover page alternate text
	 * @param $coverPageAltText string
	 * @param $locale string
	 */
	function setCoverPageAltText($coverPageAltText, $locale) {
		return $this->setData('coverPageAltText', $coverPageAltText?$coverPageAltText:'', $locale);
	}
}

?>
