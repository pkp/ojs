<?php

/**
 * @file classes/submission/SubmissionDAO.inc.php
 *
 * Copyright (c) 2014-2019 Simon Fraser University
 * Copyright (c) 2003-2019 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class SubmissionDAO
 * @ingroup submission
 * @see Submission
 *
 * @brief Operations for retrieving and modifying Article objects.
 */

import('classes.submission.Submission');
import('lib.pkp.classes.submission.PKPSubmissionDAO');

class SubmissionDAO extends PKPSubmissionDAO {

	/**
	 * Return a new data object.
	 * @return Submission
	 */
	public function newDataObject() {
		return new Submission();
	}

	/**
	 * @copydoc SchemaDAO::deleteById
	 */
	function deleteById($submissionId) {
		$result = Services::get('publication')->getMany(['submissionIds' => $submissionId]);

		foreach ($result as $publication) {
			$galleys = DAORegistry::getDAO('ArticleGalleyDAO')->getByPublicationId($publication->getId())->toArray();
			foreach ($galleys as $galley) {
				DAORegistry::getDAO('ArticleGalleyDAO')->deleteById($galley->getId());
			}
		}

		DAORegistry::getDAO('ArticleSearchDAO')->deleteSubmissionKeywords($submissionId);

		$articleSearchIndex = Application::getSubmissionSearchIndex();
		$articleSearchIndex->articleDeleted($submissionId);
		$articleSearchIndex->submissionChangesFinished();

		parent::deleteById($submissionId);

		$this->flushCache();
	}

	/**
	 * Change the status of the article
	 * @param $articleId int
	 * @param $status int
	 */
	function changeStatus($articleId, $status) {
		$this->update(
			'UPDATE submissions SET status = ? WHERE submission_id = ?',
			array((int) $status, (int) $articleId)
		);

		$this->flushCache();
	}

	/**
	 * Removes articles from a section by section ID
	 * @param $sectionId int
	 */
	function removeSubmissionsFromSection($sectionId) {
		$this->update(
			'DELETE FROM publication_settings WHERE setting_name = "sectionId" AND setting_value = ?', (int) $sectionId
		);

		$this->flushCache();
	}
}
