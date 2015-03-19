<?php

/**
 * @file classes/article/SubmissionFileDAO.inc.php
 *
 * Copyright (c) 2014-2015 Simon Fraser University Library
 * Copyright (c) 2003-2015 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class SubmissionFileDAO
 * @ingroup monograph
 * @see MonographFile
 * @see ArtworkFile
 * @see MonographFileDAODelegate
 * @see ArtworkFileDAODelegate
 *
 * @brief Operations for retrieving and modifying OMP-specific submission
 *  file implementations.
 */

import('lib.pkp.classes.submission.PKPSubmissionFileDAO');

class SubmissionFileDAO extends PKPSubmissionFileDAO {
	/**
	 * Constructor
	 */
	function SubmissionFileDAO() {
		return parent::PKPSubmissionFileDAO();
	}


	//
	// Implement protected template methods from PKPSubmissionFileDAO
	//
	/**
	 * @see PKPSubmissionFileDAO::getDelegateClassNames()
	 */
	function getDelegateClassNames() {
		return array_merge(
			parent::getDelegateClassNames(),
			array(
				'artworkfile' => 'classes.article.ArtworkFileDAODelegate',
			)
		);
	}

	/**
	 * @see PKPSubmissionFileDAO::getGenreCategoryMapping()
	 */
	function getGenreCategoryMapping() {
		static $genreCategoryMapping = array(
			GENRE_CATEGORY_ARTWORK => 'artworkfile',
			GENRE_CATEGORY_DOCUMENT => 'submissionfile'
		);
		return $genreCategoryMapping;
	}


	//
	// Protected helper methods
	//
	/**
	 * @see PKPSubmissionFileDAO::fromRow()
	 */
	function fromRow($row) {
		if (isset($row['artwork_file_id']) && is_numeric($row['artwork_file_id'])) {
			return parent::fromRow($row, 'ArtworkFile');
		} else {
			return parent::fromRow($row, 'SubmissionFile');
		}
	}
}

?>
