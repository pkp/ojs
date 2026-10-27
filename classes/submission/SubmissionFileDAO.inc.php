<?php

/**
 * @file classes/submission/SubmissionFileDAO.inc.php
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2003-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class SubmissionFileDAO
 * @ingroup submission
 * @see SubmissionFile
 *
 * @brief Operations for retrieving and modifying submission files
 */
import('lib.pkp.classes.submission.PKPSubmissionFileDAO');

class SubmissionFileDAO extends PKPSubmissionFileDAO {

	/**
	 * @copydoc SchemaDAO::insertObject
	 */
	public function insertObject($submissionFile) {
		$galley = null;
		if ($submissionFile->getData('assocType') === ASSOC_TYPE_REPRESENTATION) {
			$galleyDao = DAORegistry::getDAO('ArticleGalleyDAO'); /* @var $galleyDao ArticleGalleyDAO */
			$galley = $galleyDao->getById($submissionFile->getData('assocId'));
			$publication = $galley ? Services::get('publication')->get($galley->getData('publicationId')) : null;
			// The galley must belong to the file's own submission
			if (!$publication || $publication->getData('submissionId') != $submissionFile->getData('submissionId')) {
				throw new Exception('Galley not found when adding submission file.');
			}
		}

		parent::insertObject($submissionFile);

		if ($galley) {
			$galley->setFileId($submissionFile->getId());
			$galleyDao->updateObject($galley);
		}

		return $submissionFile->getId();
	}
}
