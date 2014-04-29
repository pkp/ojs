<?php

/**
 * @file plugins/generic/booksForReview/classes/BookForReviewAuthor.inc.php 
 *
 * Copyright (c) 2013-2014 Simon Fraser University Library
 * Copyright (c) 2003-2014 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class BookForReviewAuthor
 * @ingroup plugins_generic_booksForReview
 * @see BookForReviewAuthorDAO
 *
 * @brief Book for review author metadata class.
 */

class BookForReviewAuthor extends DataObject {

	/**
	 * Constructor.
	 */
	function BookForReviewAuthor() {
		parent::DataObject();
		$this->setId(0);
	}

	/**
	 * Get the author's complete name.
	 * Includes first name, middle name (if applicable), and last name.
	 * @return string
	 */
	function getFullName() {
		return $this->getData('firstName') . ' ' . ($this->getData('middleName') != '' ? $this->getData('middleName') . ' ' : '') . $this->getData('lastName');
	}

	//
	// Get/set methods
	//

	/**
	 * Get ID of author.
	 * @return int
	 */
	function getId() {
		return $this->getData('authorId');
	}

	/**
	 * Set ID of author.
	 * @param $authorId int
	 */
	function setId($authorId) {
		return $this->setData('authorId', $authorId);
	}

	/**
	 * Get ID of book.
	 * @return int
	 */
	function getBookId() {
		return $this->getData('bookId');
	}

	/**
	 * Set ID of book.
	 * @param $bookId int
	 */
	function setBookId($bookId) {
		return $this->setData('bookId', $bookId);
	}

	/**
	 * Get first name.
	 * @return string
	 */
	function getFirstName() {
		return $this->getData('firstName');
	}

	/**
	 * Set first name.
	 * @param $firstName string
	 */
	function setFirstName($firstName)
	{
		return $this->setData('firstName', $firstName);
	}

	/**
	 * Get middle name.
	 * @return string
	 */
	function getMiddleName() {
		return $this->getData('middleName');
	}

	/**
	 * Set middle name.
	 * @param $middleName string
	 */
	function setMiddleName($middleName) {
		return $this->setData('middleName', $middleName);
	}

	/**
	 * Get last name.
	 * @return string
	 */
	function getLastName() {
		return $this->getData('lastName');
	}

	/**
	 * Set last name.
	 * @param $lastName string
	 */
	function setLastName($lastName) {
		return $this->setData('lastName', $lastName);
	}

	/**
	 * Get sequence of author in book's author list.
	 * @return float
	 */
	function getSequence() {
		return $this->getData('sequence');
	}

	/**
	 * Set sequence of author in book's author list.
	 * @param $sequence float
	 */
	function setSequence($sequence) {
		return $this->setData('sequence', $sequence);
	}

}

?>
