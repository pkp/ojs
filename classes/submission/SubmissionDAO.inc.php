<?php

/**
 * @file classes/submission/SubmissionDAO.inc.php
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2003-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
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
		$publicationIds = Services::get('publication')->getIds(['submissionIds' => $submissionId]);
		$articleGalleyDao = DAORegistry::getDAO('ArticleGalleyDAO'); /* @var $articleGalleyDao ArticleGalleyDAO */

		foreach ($publicationIds as $publicationId) {
			$galleys = $articleGalleyDao->getByPublicationId($publicationId)->toArray();
			foreach ($galleys as $galley) {
				$articleGalleyDao->deleteById($galley->getId());
			}
		}

		$articleSearchDao = DAORegistry::getDAO('ArticleSearchDAO'); /* @var $articleSearchDao ArticleSearchDAO */
		$articleSearchDao->deleteSubmissionKeywords($submissionId);

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
			'DELETE FROM publication_settings WHERE setting_name = \'sectionId\' AND setting_value = ?', [(int) $sectionId]
		);

		$this->flushCache();
	}

	/**
	 * Get all published submissions (eventually with a pubId assigned and) matching the specified settings.
	 * @param $contextId integer optional
	 * @param $pubIdType string
	 * @param $title string optional
	 * @param $author string optional
	 * @param $issueId integer optional
	 * @param $pubIdSettingName string optional
	 * (e.g. crossref::status or crossref::registeredDoi)
	 * @param $pubIdSettingValue string optional
	 * @param $rangeInfo DBResultRange optional
	 * @return DAOResultFactory
	 */
	function getExportable($contextId, $pubIdType = null, $title = null, $author = null, $issueId = null, $pubIdSettingName = null, $pubIdSettingValue = null, $rangeInfo = null) {
		$params = [];
		if ($pubIdSettingName) {
			$params[] = $pubIdSettingName;
		}
		$params[] = STATUS_PUBLISHED;
		$params[] = $contextId;
		if ($pubIdType) {
			$params[] = 'pub-id::'.$pubIdType;
		}
		if ($title) {
			$params[] = 'title';
			$params[] = '%' . $title . '%';
		}
		if ($author) {
			$params[] = $author;
			$params[] = $author;
		}
		if ($issueId) {
			$params[] = $issueId;
		}
		if ($pubIdSettingName && $pubIdSettingValue && $pubIdSettingValue != EXPORT_STATUS_NOT_DEPOSITED) {
			$params[] = $pubIdSettingValue;
		}

		$result = $this->retrieveRange(
			$sql = 'SELECT	s.*
			FROM	submissions s
				LEFT JOIN publications p ON s.current_publication_id = p.publication_id
				LEFT JOIN publication_settings ps ON p.publication_id = ps.publication_id'
				. ($issueId ? ' LEFT JOIN publication_settings psi ON p.publication_id = psi.publication_id AND psi.setting_name = \'issueId\' AND psi.locale = \'\'' : '')
				. ($pubIdType != null?' LEFT JOIN publication_settings pspidt ON (p.publication_id = pspidt.publication_id)':'')
				. ($title != null?' LEFT JOIN publication_settings pst ON (p.publication_id = pst.publication_id)':'')
				. ($author != null?' LEFT JOIN authors au ON (p.publication_id = au.publication_id)
						LEFT JOIN author_settings asgs ON (asgs.author_id = au.author_id AND asgs.setting_name = \''.IDENTITY_SETTING_GIVENNAME.'\')
						LEFT JOIN author_settings asfs ON (asfs.author_id = au.author_id AND asfs.setting_name = \''.IDENTITY_SETTING_FAMILYNAME.'\')
					':'')
				. ($pubIdSettingName != null?' LEFT JOIN submission_settings pss ON (s.submission_id = pss.submission_id AND pss.setting_name = ?)':'')
			. ' WHERE	s.status = ?
				AND s.context_id = ?'
				. ($pubIdType != null?' AND pspidt.setting_name = ? AND pspidt.setting_value IS NOT NULL':'')
				. ($title != null?' AND (pst.setting_name = ? AND pst.setting_value LIKE ?)':'')
				. ($author != null?' AND (asgs.setting_value LIKE ? OR asfs.setting_value LIKE ?)':'')
				. ($issueId != null?' AND psi.setting_value = ?':'')
				. (($pubIdSettingName != null && $pubIdSettingValue != null && $pubIdSettingValue == EXPORT_STATUS_NOT_DEPOSITED)?' AND pss.setting_value IS NULL':'')
				. (($pubIdSettingName != null && $pubIdSettingValue != null && $pubIdSettingValue != EXPORT_STATUS_NOT_DEPOSITED)?' AND pss.setting_value = ?':'')
				. (($pubIdSettingName != null && is_null($pubIdSettingValue))?' AND (pss.setting_value IS NULL OR pss.setting_value = \'\')':'')
			. ' GROUP BY s.submission_id
			ORDER BY MAX(p.date_published) DESC, s.submission_id DESC',
			$params,
			$rangeInfo
		);

		return new DAOResultFactory($result, $this, '_fromRow', [], $sql, $params, $rangeInfo); // Counted via paging in CrossRef export
	}
}
