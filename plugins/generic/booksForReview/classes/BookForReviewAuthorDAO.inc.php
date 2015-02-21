<?php

/**
 * @file plugins/generic/booksForReview/classes/BookForReviewAuthorDAO.inc.php
 *
 * Copyright (c) 2013-2015 Simon Fraser University Library
 * Copyright (c) 2003-2015 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class BookForReviewAuthorDAO
 * @ingroup plugins_generic_booksForReview
 * @see BookForReviewAuthor
 *
 * @brief Operations for retrieving and modifying BookForReviewAuthor objects.
 */

class BookForReviewAuthorDAO extends DAO {
	/** @var $parentPluginName string Name of parent plugin */
	var $parentPluginName;

	/**
	 * Constructor
	 */
	function BookForReviewAuthorDAO($parentPluginName){
		$this->parentPluginName = $parentPluginName;
		parent::DAO();
	}

	/**
	 * Retrieve an author by ID.
	 * @param $authorId int
	 * @return BookForReviewAuthor
	 */
	function &getAuthor($authorId) {
		$result =& $this->retrieve(
			'SELECT * FROM books_for_review_authors WHERE author_id = ?', $authorId
		);

		$returner = null;
		if ($result->RecordCount() != 0) {
			$returner =& $this->_returnAuthorFromRow($result->GetRowAssoc(false));
		}

		$result->Close();
		unset($result);

		return $returner;
	}

	/**
	 * Retrieve all authors for a book for review.
	 * @param $bookId int
	 * @return array BookForReviewAuthors ordered by sequence
	 */
	function &getAuthorsByBookForReview($bookId) {
		$authors = array();

		$result =& $this->retrieve(
			'SELECT * FROM books_for_review_authors WHERE book_id = ? ORDER BY seq',
			$bookId
		);

		while (!$result->EOF) {
			$authors[] =& $this->_returnAuthorFromRow($result->GetRowAssoc(false));
			$result->moveNext();
		}

		$result->Close();
		unset($result);

		return $authors;
	}

	/**
	 * Retrieve the IDs of all authors for a book for review.
	 * @param $bookId int
	 * @return array int ordered by sequence
	 */
	function &getAuthorIdsByBookForReview($bookId) {
		$authors = array();

		$result =& $this->retrieve(
			'SELECT author_id FROM books_for_review_authors WHERE book_id = ? ORDER BY seq',
			$articleId
		);

		while (!$result->EOF) {
			$authors[] = $result->fields[0];
			$result->moveNext();
		}

		$result->Close();
		unset($result);

		return $authors;
	}

	/**
	 * Internal function to return a BookForReviewAuthor object from a row.
	 * @param $row array
	 * @return BookForReviewAuthor
	 */
	function &_returnAuthorFromRow(&$row) {
		$bfrPlugin =& PluginRegistry::getPlugin('generic', $this->parentPluginName);
		$bfrPlugin->import('classes.BookForReviewAuthor');

		$author = new BookForReviewAuthor();
		$author->setId($row['author_id']);
		$author->setBookId($row['book_id']);
		$author->setFirstName($row['first_name']);
		$author->setMiddleName($row['middle_name']);
		$author->setLastName($row['last_name']);
		$author->setSequence($row['seq']);

		HookRegistry::call('BookForReviewAuthorDAO::_returnAuthorFromRow', array(&$author, &$row));

		return $author;
	}

	/**
	 * Insert a new BookForReviewAuthor.
	 * @param $author BookForReviewAuthor
	 */	
	function insertAuthor(&$author) {
		$this->update(
			'INSERT INTO books_for_review_authors
				(book_id, first_name, middle_name, last_name, seq)
				VALUES
				(?, ?, ?, ?, ?)',
			array(
				$author->getBookId(),
				$author->getFirstName(),
				$author->getMiddleName() . '', // make non-null
				$author->getLastName(),
				$author->getSequence()
			)
		);

		$author->setId($this->getInsertAuthorId());
		return $author->getId();
	}

	/**
	 * Update an existing BookForReviewAuthor.
	 * @param $author BookForReviewAuthor
	 */
	function updateAuthor(&$author) {
		$returner = $this->update(
			'UPDATE books_for_review_authors
				SET
					first_name = ?,
					middle_name = ?,
					last_name = ?,
					seq = ?
				WHERE author_id = ?',
			array(
				$author->getFirstName(),
				$author->getMiddleName() . '', // make non-null
				$author->getLastName(),
				$author->getSequence(),
				$author->getId()
			)
		);
		return $returner;
	}

	/**
	 * Delete an Author.
	 * @param $author Author
	 */
	function deleteAuthor(&$author) {
		return $this->deleteAuthorById($author->getId());
	}

	/**
	 * Delete an author by ID.
	 * @param $authorId int
	 * @param $bookId int optional
	 */
	function deleteAuthorById($authorId, $bookId = null) {
		$params = array($authorId);
		if ($bookId) $params[] = $bookId;
		$returner = $this->update(
			'DELETE FROM books_for_review_authors WHERE author_id = ?' .
			($bookId?' AND book_id = ?':''),
			$params
		);
	}

	/**
	 * Delete authors by book for review.
	 * @param $bookId int
	 */
	function deleteAuthorsByBookForReview($bookId) {
		$authors =& $this->getAuthorsByBookForReview($bookId);
		foreach ($authors as $author) {
			$this->deleteAuthor($author);
		}
	}

	/**
	 * Sequentially renumber a book for review's authors in their sequence order.
	 * @param $bookId int
	 */
	function resequenceAuthors($bookId) {
		$result =& $this->retrieve(
			'SELECT author_id FROM books_for_review_authors WHERE book_id = ? ORDER BY seq', $bookId
		);

		for ($i=1; !$result->EOF; $i++) {
			list($authorId) = $result->fields;
			$this->update(
				'UPDATE books_for_review_authors SET seq = ? WHERE author_id = ?',
				array(
					$i,
					$authorId
				)
			);

			$result->moveNext();
		}

		$result->close();
		unset($result);
	}

	/**
	 * Get the ID of the last inserted author.
	 * @return int
	 */
	function getInsertAuthorId() {
		return $this->getInsertId('books_for_review_authors', 'author_id');
	}
}

?>
