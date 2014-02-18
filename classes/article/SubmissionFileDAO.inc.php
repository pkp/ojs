<?php

/**
 * @file classes/article/SubmissionFileDAO.inc.php
 *
 * Copyright (c) 2014 Simon Fraser University Library
 * Copyright (c) 2003-2014 John Willinsky
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
	 * @see PKPSubmissionFileDAO::getSubmissionEntityName()
	 */
	function getSubmissionEntityName() {
		return 'submission';
	}

	/**
	 * @see PKPSubmissionFileDAO::getDelegateClassNames()
	 */
	function getDelegateClassNames() {
		static $delegateClasses = array(
			'artworkfile' => 'classes.article.ArtworkFileDAODelegate',
			'articlefile' => 'classes.article.ArticleFileDAODelegate'
		);
		return $delegateClasses;
	}

	/**
	 * @see PKPSubmissionFileDAO::getGenreCategoryMapping()
	 */
	function getGenreCategoryMapping() {
		static $genreCategoryMapping = array(
			GENRE_CATEGORY_ARTWORK => 'artworkfile',
			GENRE_CATEGORY_DOCUMENT => 'articlefile'
		);
		return $genreCategoryMapping;
	}

	/**
	 * @see PKPSubmissionFileDAO::baseQueryForFileSelection()
	 */
	function baseQueryForFileSelection() {
		// Build the basic query that joins the class tables.
		// The DISTINCT is required to de-dupe the review_round_files join in
		// PKPSubmissionFileDAO.
		return 'SELECT DISTINCT
				sf.file_id AS submission_file_id, sf.revision AS submission_revision,
				af.file_id AS artwork_file_id, af.revision AS artwork_revision,
				sf.*, af.*
			FROM	submission_files sf
				LEFT JOIN submission_artwork_files af ON sf.file_id = af.file_id AND sf.revision = af.revision ';
	}


	//
	// Override methods from PKPSubmissionFileDAO
	// FIXME *6902* Move this code to PKPSubmissionFileDAO after the review round
	// refactoring is ported to other applications.
	//
	/**
	 * @see PKPSubmissionFileDAO::deleteAllRevisionsByReviewRound()
	 */
	function deleteAllRevisionsByReviewRound($reviewRoundId) {
		// Remove currently assigned review files.
		return $this->update('DELETE FROM review_round_files
						WHERE review_round_id = ?',
		array((int)$reviewRoundId));
	}

	/**
	 * @see PKPSubmissionFileDAO::assignRevisionToReviewRound()
	 */
	function assignRevisionToReviewRound($fileId, $revision, &$reviewRound) {
		if (!is_numeric($fileId) || !is_numeric($revision)) fatalError('Invalid file!');
		return $this->update('INSERT INTO review_round_files
						('.$this->getSubmissionEntityName().'_id, review_round_id, stage_id, file_id, revision)
						VALUES (?, ?, ?, ?, ?)',
		array((int)$reviewRound->getSubmissionId(), (int)$reviewRound->getId(), (int)$reviewRound->getStageId(), (int)$fileId, (int)$revision));
	}

	/**
	 * @see PKPSubmissionFileDAO::getRevisionsByReviewRound()
	 */
	function &getRevisionsByReviewRound(&$reviewRound, $fileStage = null,
			$uploaderUserId = null, $uploaderUserGroupId = null) {
		if (!is_a($reviewRound, 'ReviewRound')) {
			$nullVar = null;
			return $nullVar;
		}
		return $this->_getInternally($reviewRound->getSubmissionId(),
			$fileStage, null, null, null, null, null,
			$uploaderUserId, $uploaderUserGroupId, null, $reviewRound->getId()
		);
	}

	/**
	 * @see PKPSubmissionFileDAO::getLatestNewRevisionsByReviewRound()
	 */
	function &getLatestNewRevisionsByReviewRound($reviewRound, $fileStage = null) {
		if (!is_a($reviewRound, 'ReviewRound')) {
			$emptyArray = array();
			return $emptyArray;
		}
		return $this->_getInternally($reviewRound->getSubmissionId(),
			$fileStage, null, null, null, null, $reviewRound->getStageId(),
			null, null, null, $reviewRound->getId(), true
		);
	}

	//
	// Protected helper methods
	//
	/**
	 * @see PKPSubmissionFileDAO::fromRow()
	 */
	function fromRow($row) {
			// Identify the appropriate file implementation for the
		// given row.
		if (isset($row['artwork_file_id']) && is_numeric($row['artwork_file_id'])) {
			$fileImplementation = 'ArtworkFile';
		} else {
			$fileImplementation = 'ArticleFile';
		}

		// Let the superclass instantiate the file.
		return parent::fromRow($row, $fileImplementation);
	}
}

?>
