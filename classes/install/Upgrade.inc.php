<?php

/**
 * @file classes/install/Upgrade.inc.php
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2003-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class Upgrade
 * @ingroup install
 *
 * @brief Perform system upgrade.
 */
use Illuminate\Database\Capsule\Manager as Capsule;

import('lib.pkp.classes.install.Installer');

class Upgrade extends Installer {
	/**
	 * Constructor.
	 * @param $params array upgrade parameters
	 * @param $installFile string Name of XML descriptor to install
	 * @param $isPlugin boolean True iff the installer is for a plugin.
	 */
	function __construct($params, $installFile = 'upgrade.xml', $isPlugin = false) {
		parent::__construct($installFile, $params, $isPlugin);
	}


	/**
	 * Returns true iff this is an upgrade process.
	 * @return boolean
	 */
	function isUpgrade() {
		return true;
	}

	//
	// Upgrade actions
	//

	/**
	 * Rebuild the search index.
	 * @return boolean
	 */
	function rebuildSearchIndex() {
		$submissionSearchIndex = Application::getSubmissionSearchIndex();
		$submissionSearchIndex->rebuildIndex();
		return true;
	}

	/**
	 * Clear the CSS cache files (needed when changing LESS files)
	 * @return boolean
	 */
	function clearCssCache() {
		$request = Application::get()->getRequest();
		$templateMgr = TemplateManager::getManager($request);
		$templateMgr->clearCssCache();
		return true;
	}

	/**
	 * Set the missing uploader user id to a journal manager.
	 * @return boolean True indicates success.
	 */
	function setFileUploader() {
		$journalDao = DAORegistry::getDAO('JournalDAO'); /* @var $journalDao JournalDAO */
		$userGroupDao = DAORegistry::getDAO('UserGroupDAO'); /* @var $userGroupDao UserGroupDAO */
		$submissionFileDao = DAORegistry::getDAO('SubmissionFileDAO'); /* @var $submissionFileDao SubmissionFileDAO */
		$journalIterator = $journalDao->getAll();
		while ($journal = $journalIterator->next()) {
			$managerUserGroup = $userGroupDao->getDefaultByRoleId($journal->getId(), ROLE_ID_MANAGER);
			$managerUsers = $userGroupDao->getUsersById($managerUserGroup->getId(), $journal->getId());
			$creatorUserId = $managerUsers->next()->getId();
			switch (Config::getVar('database', 'driver')) {
				case 'mysql':
				case 'mysqli':
					$submissionFileDao->update('UPDATE submission_files sf, submissions s SET sf.uploader_user_id = ? WHERE sf.uploader_user_id IS NULL AND sf.submission_id = s.submission_id AND s.context_id = ?', array($creatorUserId, $journal->getId()));
					break;
				case 'postgres':
				case 'postgres64':
				case 'postgres7':
				case 'postgres8':
				case 'postgres9':
					$submissionFileDao->update('UPDATE submission_files SET uploader_user_id = ? FROM submissions s WHERE submission_files.uploader_user_id IS NULL AND submission_files.submission_id = s.submission_id AND s.context_id = ?', array($creatorUserId, $journal->getId()));
					break;
				default: fatalError('Unknown database type!');
			}
			unset($managerUsers, $managerUserGroup);
		}
		return true;
	}

	/**
	 * Set the missing file names.
	 * @return boolean True indicates success.
	 */
	function setFileName() {
		$journalDao = DAORegistry::getDAO('JournalDAO'); /* @var $journalDao JournalDAO */
		$submissionDao = DAORegistry::getDAO('SubmissionDAO'); /* @var $submissionDao SubmissionDAO */
		$submissionFileDao = DAORegistry::getDAO('SubmissionFileDAO'); /* @var $submissionFileDao SubmissionFileDAO */

		$contexts = $journalDao->getAll();
		while ($context = $contexts->next()) {
			$submissions = $submissionDao->getByContextId($context->getId());
			while ($submission = $submissions->next()) {
				$submissionFilesIterator = Services::get('submissionFile')->getMany([
					'submissionIds' => [$submission->getId()],
					'includeDependentFiles' => true,
				]);
				foreach ($submissionFilesIterator as $submissionFile) {
					$reviewStage = $submissionFile->getFileStage() == SUBMISSION_FILE_REVIEW_FILE ||
						$submissionFile->getFileStage() == SUBMISSION_FILE_REVIEW_ATTACHMENT ||
						$submissionFile->getFileStage() == SUBMISSION_FILE_REVIEW_REVISION;
					if (!$submissionFile->getName(AppLocale::getPrimaryLocale())) {
						if ($reviewStage) {
							$submissionFile->setName($submissionFile->_generateName(true), AppLocale::getPrimaryLocale());
						} else {
							$submissionFile->setName($submissionFile->_generateName(), AppLocale::getPrimaryLocale());
						}
					}
					$submissionFileDao->updateObject($submissionFile);
				}
			}
		}
		return true;
	}

	/**
	 * Convert comments to editors to queries.
	 * @return boolean True indicates success.
	 */
	function convertCommentsToEditor() {
		$submissionDao = DAORegistry::getDAO('SubmissionDAO'); /* @var $submissionDao SubmissionDAO */
		$stageAssignmentDao = DAORegistry::getDAO('StageAssignmentDAO'); /* @var $stageAssignmentDao StageAssignmentDAO */
		$queryDao = DAORegistry::getDAO('QueryDAO'); /* @var $queryDao QueryDAO */
		$noteDao = DAORegistry::getDAO('NoteDAO'); /* @var $noteDao NoteDAO */
		$userGroupDao = DAORegistry::getDAO('UserGroupDAO'); /* @var $userGroupDao UserGroupDAO */

		import('lib.pkp.classes.security.Role'); // ROLE_ID_...

		$commentsResult = $submissionDao->retrieve(
			'SELECT s.submission_id, s.context_id, s.comments_to_ed, s.date_submitted
			FROM submissions_tmp s
			WHERE s.comments_to_ed IS NOT NULL AND s.comments_to_ed != \'\''
		);
		foreach ($commentsResult as $row) {
			$commentsToEd = PKPString::stripUnsafeHtml($row->comments_to_ed);
			if ($commentsToEd != ''){
				$userId = null;
				$authorAssignments = $stageAssignmentDao->getBySubmissionAndRoleId($row->submission_id, ROLE_ID_AUTHOR);
				if ($authorAssignment = $authorAssignments->next()) {
					// We assume the results are ordered by stage_assignment_id i.e. first author assignment is first
					$userId = $authorAssignment->getUserId();
				} else {
					$managerUserGroup = $userGroupDao->getDefaultByRoleId($row->context_id, ROLE_ID_MANAGER);
					$managerUsers = $userGroupDao->getUsersById($managerUserGroup->getId(), $row->context_id);
					$userId = $managerUsers->next()->getId();
				}
				assert($userId);

				$query = $queryDao->newDataObject();
				$query->setAssocType(ASSOC_TYPE_SUBMISSION);
				$query->setAssocId($row->submission_id);
				$query->setStageId(WORKFLOW_STAGE_ID_SUBMISSION);
				$query->setSequence(REALLY_BIG_NUMBER);

				$queryDao->insertObject($query);
				$queryDao->resequence(ASSOC_TYPE_SUBMISSION, $row->submission_id);
				$queryDao->insertParticipant($query->getId(), $userId);

				$queryId = $query->getId();

				$note = $noteDao->newDataObject();
				$note->setUserId($userId);
				$note->setAssocType(ASSOC_TYPE_QUERY);
				$note->setTitle('Comments for the Editor');
				$note->setContents($commentsToEd);
				$note->setDateCreated(strtotime($row->date_submitted));
				$note->setDateModified(strtotime($row->date_submitted));
				$note->setAssocId($queryId);
				$noteDao->insertObject($note);
			}
		}

		// remove temporary table
		$submissionDao->update('DROP TABLE submissions_tmp');

		return true;
	}


	/**
	 * Localize issue cover images.
	 * @return boolean True indicates success.
	 */
	function localizeIssueCoverImages() {
		$issueDao = DAORegistry::getDAO('IssueDAO'); /* @var $issueDao IssueDAO */
		$publicFileManager = new PublicFileManager();
		// remove strange old cover images with array values in the DB - from 3.alpha or 3.beta?
		$issueDao->update('DELETE FROM issue_settings WHERE setting_name = \'coverImage\' AND setting_type = \'object\'');

		// remove empty 3.0 cover images
		$issueDao->update('DELETE FROM issue_settings WHERE setting_name = \'coverImage\' AND locale = \'\' AND setting_value = \'\'');
		$issueDao->update('DELETE FROM issue_settings WHERE setting_name = \'coverImageAltText\' AND locale = \'\' AND setting_value = \'\'');

		// get cover image duplicates, from 2.4.x and 3.0
		$result = $issueDao->retrieve(
			'SELECT DISTINCT iss1.issue_id, iss1.setting_value, i.journal_id
			FROM issue_settings iss1
			LEFT JOIN issues i ON (i.issue_id = iss1.issue_id)
			JOIN issue_settings iss2 ON (iss2.issue_id = iss1.issue_id AND iss2.setting_name = \'coverImage\')
			WHERE iss1.setting_name = \'fileName\''
		);
		// remove the old 2.4.x cover images, for which a new cover image exists
		foreach ($result as $row) {
			$oldFileName = $row->setting_value;
			if ($publicFileManager->fileExists($publicFileManager->getContextFilesPath($row->journal_id) . '/' . $oldFileName)) {
				$publicFileManager->removeContextFile($row->journal_id, $oldFileName);
			}
			$issueDao->update('DELETE FROM issue_settings WHERE issue_id = ? AND setting_name = \'fileName\' AND setting_value = ?', array((int) $row->issue_id, $oldFileName));
		}

		// retrieve names for unlocalized issue cover images
		$result = $issueDao->retrieve(
			'SELECT iss.issue_id, iss.setting_value, j.journal_id, j.primary_locale
			FROM issue_settings iss, issues i, journals j
			WHERE iss.setting_name = \'coverImage\' AND iss.locale = \'\'
				AND i.issue_id = iss.issue_id AND j.journal_id = i.journal_id'
		);
		// for all unlocalized issue cover images
		// rename (copy + remove) the cover images files in the public folder,
		// considering the locale (using the journal primary locale)
		foreach ($result as $row) {
			$oldFileName = $row->setting_value;
			$newFileName = str_replace('.', '_' . $row->primary_locale . '.', $oldFileName);
			if ($publicFileManager->fileExists($publicFileManager->getContextFilesPath($row->journal_id) . '/' . $oldFileName)) {
				$publicFileManager->copyContextFile($row->journal_id, $publicFileManager->getContextFilesPath($row->journal_id) . '/' . $oldFileName, $newFileName);
				$publicFileManager->removeContextFile($row->journal_id, $oldFileName);
			}
		}
		switch (Config::getVar('database', 'driver')) {
			case 'mysql':
			case 'mysqli':
				// Update cover image names in the issue_settings table
				$issueDao->update(
					'UPDATE issue_settings iss, issues i, journals j
					SET iss.locale = j.primary_locale, iss.setting_value = CONCAT(LEFT( iss.setting_value, LOCATE(\'.\', iss.setting_value) - 1 ), \'_\', j.primary_locale, \'.\', SUBSTRING_INDEX(iss.setting_value,\'.\',-1))
					WHERE iss.setting_name = \'coverImage\' AND iss.locale = \'\' AND i.issue_id = iss.issue_id AND j.journal_id = i.journal_id'
				);
				// Update cover image alt texts in the issue_settings table
				$issueDao->update(
					'UPDATE issue_settings iss, issues i, journals j SET iss.locale = j.primary_locale WHERE iss.setting_name = \'coverImageAltText\' AND iss.locale = \'\' AND i.issue_id = iss.issue_id AND j.journal_id = i.journal_id'
				);
				break;
			case 'postgres':
			case 'postgres64':
			case 'postgres7':
			case 'postgres8':
			case 'postgres9':
				// Update cover image names in the issue_settings table
				$issueDao->update(
					'UPDATE issue_settings
					SET locale = j.primary_locale, setting_value = REGEXP_REPLACE(issue_settings.setting_value, \'[\.]\', CONCAT(\'_\', j.primary_locale, \'.\'))
					FROM issues i, journals j
					WHERE issue_settings.setting_name = \'coverImage\' AND issue_settings.locale = \'\' AND i.issue_id = issue_settings.issue_id AND j.journal_id = i.journal_id'
				);
				// Update cover image alt texts in the issue_settings table
				$issueDao->update(
					'UPDATE issue_settings
					SET locale = j.primary_locale
					FROM issues i, journals j
					WHERE issue_settings.setting_name = \'coverImageAltText\' AND issue_settings.locale = \'\' AND i.issue_id = issue_settings.issue_id AND j.journal_id = i.journal_id'
				);
				break;
			default: fatalError('Unknown database type!');
		}
		$issueDao->flushCache();
		return true;
	}

	/**
	 * Localize article cover images.
	 * @return boolean True indicates success.
	 */
	function localizeArticleCoverImages() {
		$submissionDao = DAORegistry::getDAO('SubmissionDAO'); /* @var $submissionDao SubmissionDAO */
		$publicFileManager = new PublicFileManager();
		// remove strange old cover images with array values in the DB - from 3.alpha or 3.beta?
		$submissionDao->update('DELETE FROM submission_settings WHERE setting_name = \'coverImage\' AND setting_type = \'object\'');

		// remove empty 3.0 cover images
		$submissionDao->update('DELETE FROM submission_settings WHERE setting_name = \'coverImage\' AND locale = \'\' AND setting_value = \'\'');
		$submissionDao->update('DELETE FROM submission_settings WHERE setting_name = \'coverImageAltText\' AND locale = \'\' AND setting_value = \'\'');

		// get cover image duplicates, from 2.4.x and 3.0
		$result = $submissionDao->retrieve(
			'SELECT DISTINCT ss1.submission_id, ss1.setting_value, s.context_id
			FROM submission_settings ss1
			LEFT JOIN submissions s ON (s.submission_id = ss1.submission_id)
			JOIN submission_settings ss2 ON (ss2.submission_id = ss1.submission_id AND ss2.setting_name = \'coverImage\')
			WHERE ss1.setting_name = \'fileName\''
		);
		// remove the old 2.4.x cover images, for which a new cover image exists
		foreach ($result as $row) {
			$submissionId = $row->submission_id;
			$oldFileName = $row->setting_value;
			if ($publicFileManager->fileExists($publicFileManager->getContextFilesPath($row->context_id) . '/' . $oldFileName)) {
				$publicFileManager->removeContextFile($row->journal_id, $oldFileName);
			}
			$submissionDao->update('DELETE FROM submission_settings WHERE submission_id = ? AND setting_name = \'fileName\' AND setting_value = ?', [(int) $submissionId, $oldFileName]);
		}

		// retrieve names for unlocalized article cover images
		$result = $submissionDao->retrieve(
			'SELECT ss.submission_id, ss.setting_value, j.journal_id, j.primary_locale
			FROM submission_settings ss, submissions s, journals j
			WHERE ss.setting_name = \'coverImage\' AND ss.locale = \'\'
				AND s.submission_id = ss.submission_id AND j.journal_id = s.context_id'
		);
		// for all unlocalized article cover images
		// rename (copy + remove) the cover images files in the public folder,
		// considering the locale (using the journal primary locale)
		foreach ($result as $row) {
			$oldFileName = $row->setting_value;
			$newFileName = str_replace('.', '_' . $row->primary_locale . '.', $oldFileName);
			if ($publicFileManager->fileExists($publicFileManager->getContextFilesPath($row->journal_id) . '/' . $oldFileName)) {
				$publicFileManager->copyContextFile($row->journal_id, $publicFileManager->getContextFilesPath($row->journal_id) . '/' . $oldFileName, $newFileName);
				$publicFileManager->removeContextFile($row->journal_id, $oldFileName);
			}
		}
		switch (Config::getVar('database', 'driver')) {
			case 'mysql':
			case 'mysqli':
				// Update cover image names in the submission_settings table
				$submissionDao->update(
					'UPDATE submission_settings ss, submissions s, journals j
					SET ss.locale = j.primary_locale, ss.setting_value = CONCAT(LEFT( ss.setting_value, LOCATE(\'.\', ss.setting_value) - 1 ), \'_\', j.primary_locale, \'.\', SUBSTRING_INDEX(ss.setting_value,\'.\',-1))
					WHERE ss.setting_name = \'coverImage\' AND ss.locale = \'\' AND s.submission_id = ss.submission_id AND j.journal_id = s.context_id'
				);
				// Update cover image alt texts in the submission_settings table
				$submissionDao->update(
					'UPDATE submission_settings ss, submissions s, journals j
					SET ss.locale = j.primary_locale
					WHERE ss.setting_name = \'coverImageAltText\' AND ss.locale = \'\' AND s.submission_id = ss.submission_id AND j.journal_id = s.context_id'
				);
				break;
			case 'postgres':
			case 'postgres64':
			case 'postgres7':
			case 'postgres8':
			case 'postgres9':
				// Update cover image names in the submission_settings table
				$submissionDao->update(
					'UPDATE submission_settings
					SET locale = j.primary_locale, setting_value = REGEXP_REPLACE(submission_settings.setting_value, \'[\.]\', CONCAT(\'_\', j.primary_locale, \'.\'))
					FROM submissions s, journals j
					WHERE submission_settings.setting_name = \'coverImage\' AND submission_settings.locale = \'\' AND s.submission_id = submission_settings.submission_id AND j.journal_id = s.context_id'
				);
				// Update cover image alt texts in the submission_settings table
				$submissionDao->update(
					'UPDATE submission_settings
					SET locale = j.primary_locale
					FROM submissions s, journals j
					WHERE submission_settings.setting_name = \'coverImageAltText\' AND submission_settings.locale = \'\' AND s.submission_id = submission_settings.submission_id AND j.journal_id = s.context_id'
				);
				break;
			default: fatalError('Unknown database type!');
		}
		$submissionDao->flushCache();
		return true;
	}

	/**
	 * For 3.1.0 upgrade (#2467): In multi-journal upgrades from OJS 2.x, the
	 * user_group_id column in the authors table may be updated to point to
	 * user groups in other journals.
	 * @return boolean
	 */
	function fixAuthorGroup() {
		$userGroupDao = DAORegistry::getDAO('UserGroupDAO'); /* @var $userGroupDao UserGroupDAO */
		$result = $userGroupDao->retrieve(
			'SELECT a.author_id, s.context_id FROM authors a JOIN submissions s ON (a.submission_id = s.submission_id) JOIN user_groups g ON (a.user_group_id = g.user_group_id) WHERE g.context_id <> s.context_id'
		);
		foreach ($result as $row) {
			$authorGroup = $userGroupDao->getDefaultByRoleId($row->context_id, ROLE_ID_AUTHOR);
			if ($authorGroup) $userGroupDao->update('UPDATE authors SET user_group_id = ? WHERE author_id = ?', [(int) $authorGroup->getId(), $row->author_id]);
		}
		return true;
	}

	/**
	 * For 3.0.0 - 3.0.2 upgrade: first part of the fix for the migrated reviewer files.
	 * The files are renamed and moved from 'review' to 'review/attachment' folder.
	 * @return boolean
	 */
	function moveReviewerFiles() {
		import('lib.pkp.classes.submission.SubmissionFile'); // SUBMISSION_FILE_...
		import('lib.pkp.classes.file.FileManager');
		$fileManager = new FileManager();
		$fileRows = Capsule::table('review_assignments as ra')
			->leftJoin('submissions as s', 's.submission_id', '=', 'ra.submission_id')
			->whereNotNull('ra.reviewer_file_id')
			->get();
		foreach ($fileRows as $fileRow) {
			$submissionDir = Services::get('submissionFile')->getSubmissionDir($fileRow->context_id, $fileRow->submission_id);
			$revisionRows = Capsule::table('submission_files')
				->where('file_id', '=' , $fileRow->reviewer_file_id)
				->get();
			if (empty($revisionRows)) {
				error_log('ERROR: Reviewer files with ID ' . $fileRow->reviewer_file_id . ' from review assignment ' .$fileRow->review_id . ' could not be found in the database table submission_files');
			}
			foreach ($revisionRows as $revisionRow) {
				// Reproduces the removed method SubmissionFile::_generateFileName()
				// genre is %s because it can be blank with review attachments
				$wrongFilename = sprintf(
					'%d-%s-%d-%d-%d-%s.%s',
					$revisionRow->submission_id,
					$revisionRow->genre_id,
					$revisionRow->file_id,
					$revisionRow->revision,
					$revisionRow->file_stage,
					date('Ymd', strtotime($revisionRow->date_uploaded)),
					strtolower_codesafe($fileManager->parseFileExtension($revisionRow->original_file_name))
				);
				$newFilename = sprintf(
					'%d-%s-%d-%d-%d-%s.%s',
					$revisionRow->submission_id,
					$revisionRow->genre_id,
					$revisionRow->file_id,
					$revisionRow->revision,
					SUBMISSION_FILE_REVIEW_ATTACHMENT,
					date('Ymd', strtotime($revisionRow->date_uploaded)),
					strtolower_codesafe($fileManager->parseFileExtension($revisionRow->original_file_name))
				);
				$wrongFilePath = $submissionDir . '/' . $this->_fileStageToPath($revisionRow->file_stage) . '/' . $wrongFilename;
				$newFilePath = $submissionDir . '/' . $this->_fileStageToPath(SUBMISSION_FILE_REVIEW_ATTACHMENT) . '/' . $newFilename;
				if (Services::get('file')->fs->has($newFilePath)) {
					continue;
				}
				if (!Services::get('file')->fs->rename($wrongFilePath, $newFilePath)) {
					error_log("Unable to move \"$wrongFilePath\" to \"$newFilePath\".");
				}
			}
		}

		return true;
	}

	/**
	 * For 2.4.x - 3.1.0 upgrade: remove cancelled review assignments.
	 * @return boolean
	 */
	function removeCancelledReviewAssignments() {
		$reviewAssignmentDao = DAORegistry::getDAO('ReviewAssignmentDAO'); /* @var $reviewAssignmentDao ReviewAssignmentDAO */
		// get cancelled review assignemnts
		$result = $reviewAssignmentDao->retrieve('SELECT review_id FROM review_assignments_tmp');
		foreach ($result as $row) {
			$reviewAssignmentDao->deleteById($row->review_id);
		}
		// remove temporary table
		$reviewAssignmentDao->update('DROP TABLE review_assignments_tmp');
		// update log messages
		$eventLogDao = DAORegistry::getDAO('SubmissionEventLogDAO'); /* @var $eventLogDao SubmissionEventLogDAO */
		$eventLogDao->update('UPDATE event_log SET message = \'log.review.reviewCleared\' WHERE message = \'log.review.reviewCancelled\'');
		return true;
	}

	/**
	 * Fix galley image associations (https://github.com/pkp/pkp-lib/issues/2582)
	 * @return boolean
	 */
	function repairImageAssociations() {
		$genreDao = DAORegistry::getDAO('GenreDAO'); /* @var $genreDao GenreDAO */
		$submissionDao = DAORegistry::getDAO('SubmissionDAO'); /* @var $submissionDao SubmissionDAO */
		$submissionFileDao = DAORegistry::getDAO('SubmissionFileDAO'); /* @var $submissionFileDao SubmissionFileDAO */
		$result = $submissionFileDao->retrieve('SELECT df.file_id AS dependent_file_id, gf.file_id AS galley_file_id FROM submission_files df, submission_files gf, submission_html_galley_images i, submission_galleys g WHERE i.galley_id = g.galley_id AND g.file_id = gf.file_id AND i.file_id = df.file_id');
		foreach ($result as $row) {
			$submissionFiles = $submissionFileDao->getAllRevisions($row->dependent_file_id);
			foreach ((array) $submissionFiles as $submissionFile) {
				if ($submissionFile->getFileStage() != 1) continue; // SUBMISSION_FILE_PUBLIC

				$submission = $submissionDao->getById($submissionFile->getData('submissionId'));
				$imageGenre = $genreDao->getByKey('IMAGE', $submission->getContextId());

				$submissionFile->setFileStage(SUBMISSION_FILE_DEPENDENT);
				$submissionFile->setAssocType(ASSOC_TYPE_SUBMISSION_FILE);
				$submissionFile->setAssocId($row->galley_file_id);
				$submissionFile->setGenreId($imageGenre->getId());
				$submissionFileDao->updateObject($submissionFile);
			}
		}
		$submissionDao->update('DROP TABLE submission_html_galley_images');
		return true;
	}

	/**
	 * For 2.4.x - 3.1.0 upgrade: repair already migrated keywords and subjects.
	 * @return boolean
	 */
	function repairKeywordsAndSubjects() {
		$request = Application::get()->getRequest();
		$site = $request->getSite();
		$installedLocales = $site->getInstalledLocales();
		$submissionSubjectDao = DAORegistry::getDAO('SubmissionSubjectDAO'); /* @var $submissionSubjectDao SubmissionSubjectDAO */
		$submissionKeywordDao = DAORegistry::getDAO('SubmissionKeywordDAO'); /* @var $submissionKeywordDao SubmissionKeywordDAO */
		$submissionSubjectEntryDao = DAORegistry::getDAO('SubmissionSubjectEntryDAO'); /* @var $submissionSubjectEntryDao SubmissionSubjectEntryDAO */

		// insert and correct old keywords migration:
		// get old keywords
		$subjectsToKeep = array();
		$oldKeywordsFound = false;
		$result = $submissionKeywordDao->retrieve('SELECT * FROM submission_settings WHERE setting_name = \'subject\' AND setting_value <> \'\'');
		foreach ($result as $row) {
			$submissionId = $row->submission_id;
			$locale = $row->locale;
			$oldKeywordsArray = preg_split('/[,;:]/', $row->setting_value);
			$oldKeywords = array_map('trim', $oldKeywordsArray);
			// get current keywords
			$newKeywords = array();
			$newKeywordsArray = $submissionKeywordDao->getKeywords($submissionId, [$locale], ASSOC_TYPE_SUBMISSION);
			if (array_key_exists($locale, $newKeywordsArray)) {
				$newKeywords = array_map('trim', $newKeywordsArray[$locale]);
			}
			// get the difference and insert them
			$keywordsToAdd = array_diff($oldKeywords, $newKeywords);
			if (!empty($keywordsToAdd)) {
				$submissionKeywordDao->insertKeywords(array($locale => $keywordsToAdd), $submissionId, false, ASSOC_TYPE_SUBMISSION);
			}

			// correct the old keywords migration:
			// because the old keywords were already migrated as subjects earlier:
			// get current subjects for all possible locales, in order to also
			// consider locales other than old keywords locales (for example if added after the migration),
			// in order not to remove those when inserting below
			if (!array_key_exists($submissionId, $subjectsToKeep)) {
				$newSubjectsArray = $submissionSubjectDao->getSubjects($submissionId, $installedLocales, ASSOC_TYPE_SUBMISSION);
				$subjectsToKeep[$submissionId] = $newSubjectsArray;
			}
			// if subjects for the current locale exist
			if (array_key_exists($locale, $subjectsToKeep[$submissionId])) {
				// get current subjects for the current locale
				$newSubjects = array_map('trim', $subjectsToKeep[$submissionId][$locale]);
				// get the difference to keep only them
				$subjectsToKeep[$submissionId][$locale] = array_diff($newSubjects, $oldKeywords);
			}
			$oldKeywordsFound = true;
		}
		unset($newSubjects);
		unset($newSubjectsArray);

		// if old keywords were found, it means that this this function is executed for the first time
		// i.e. the subjects should be corrected
		if ($oldKeywordsFound) {
			// insert the subjects that should be kept, overriding the existing ones
			// also if they are empty, because then they should be deleted
			foreach ($subjectsToKeep as $submissionId => $submissionSubjects) {
				$submissionSubjectDao->insertSubjects($submissionSubjects, $submissionId, true, ASSOC_TYPE_SUBMISSION);
			}
		}

		// insert old subjects
		$result = $submissionKeywordDao->retrieve('SELECT * FROM submission_settings WHERE setting_name = \'subjectClass\' AND setting_value <> \'\'');
		foreach ($result as $row) {
			$submissionId = $row->submission_id;
			$locale = $row->locale;
			$oldSubjectsArray = preg_split('/[,;:]/', $row->setting_value);
			$oldSubjects = array_map('trim', $oldSubjectsArray);
			// get current subjects
			$newSubjects = array();
			$newSubjectsArray = $submissionSubjectDao->getSubjects($submissionId, array($locale), ASSOC_TYPE_SUBMISSION);
			if (array_key_exists($locale, $newSubjectsArray)) {
				$newSubjects = array_map('trim', $newSubjectsArray[$locale]);
			}
			// get the difference and insert them
			$subjectsToAdd = array_diff($oldSubjects, $newSubjects);
			if (!empty($subjectsToAdd)) {
				$submissionSubjectDao->insertSubjects(array($locale => $subjectsToAdd), $submissionId, false, ASSOC_TYPE_SUBMISSION);
			}
		}

		// delete old settings
		$submissionKeywordDao->update('DELETE FROM submission_settings WHERE setting_name = \'discipline\' OR setting_name = \'subject\' OR setting_name = \'subjectClass\' OR setting_name = \'sponsor\'');

		return true;
	}

	/**
	 * For 3.0.x - 3.1.0 upgrade: repair enabled plugin setting for site plugins.
	 * @return boolean
	 */
	function enabledSitePlugins() {
		$allPlugins =& PluginRegistry::getAllPlugins();
		$pluginSettings = DAORegistry::getDAO('PluginSettingsDAO');
		foreach ($allPlugins as $plugin) {
			if ($plugin->isSitePlugin()) {
				$pluginName = strtolower_codesafe($plugin->getName());
				if ($pluginName != 'customblockmanagerplugin') {
					$result = $pluginSettings->update('DELETE FROM plugin_settings WHERE plugin_name = ? AND setting_name = \'enabled\' AND context_id <> 0', array($pluginName));
				}
			}
		}

		return true;
	}

	/**
	 * For 3.0.x - 3.1.0 upgrade: repair the file names in files_dir after the genres are fixed in the DB.
	 *
	 * NOTE: we can assume that the migrated file names to be fixed are with genre ID = 1, s. https://github.com/pkp/pkp-lib/issues/2506
	 *
	 * @return boolean
	 */
	function fixGenreIdInFileNames() {
		$journalDao = DAORegistry::getDAO('JournalDAO'); /* @var $journalDao JournalDAO */
		$genreDao = DAORegistry::getDAO('GenreDAO'); /* @var $genreDao GenreDAO */
		$submissionDao = DAORegistry::getDAO('SubmissionDAO'); /* @var $submissionDao SubmissionDAO */

		$contexts = $journalDao->getAll();
		while ($context = $contexts->next()) {
			$styleGenre = $genreDao->getByKey('STYLE', $context->getId());
			$submissions = $submissionDao->getByContextId($context->getId());
			while ($submission = $submissions->next()) {
				$submissionDir = Services::get('submissionFile')->getSubmissionDir($context->getId(), $submission->getId());
				import('lib.pkp.classes.file.FileManager');
				$fileManager = new FileManager();
				$rows = Capsule::table('submission_files')
					->where('submission_id', '=', $submission->getId())
					->get([
						'file_id',
						'revision',
						'submission_id',
						'genre_id',
						'file_stage',
						'date_uploaded',
						'original_file_name'
					]);
				foreach ($rows as $row) {
					// Ignore files with style genre -- if they exist, they are corrected manually i.e.
					// the moveCSSFiles function will do this, s. https://github.com/pkp/pkp-lib/issues/2758
					if ($row->genre_id != $styleGenre->getId()) {
						// Reproduces the removed method SubmissionFile::_generateFileName()
						// genre is %s because it can be blank with review attachments
						$generatedNewFilename = sprintf(
							'%d-%s-%d-%d-%d-%s.%s',
							$row->submission_id,
							$row->genre_id,
							$row->file_id,
							$row->revision,
							$row->file_stage,
							date('Ymd', strtotime($row->date_uploaded)),
							strtolower_codesafe($fileManager->parseFileExtension($row->original_file_name))
						);

						$targetFilename = $submissionDir . '/' . $this->_fileStageToPath($row->file_stage) . '/' . $generatedNewFilename;
						$timestamp = date('Ymd', strtotime($row->date_uploaded));
						$wrongFileName = $submission->getId() . '-' . '1' . '-' . $row->file_id . '-' . $row->revision . '-' . $row->file_stage . '-' . $timestamp . '.' . strtolower_codesafe($fileManager->parseFileExtension($row->original_file_name));
						$sourceFilename = $submissionDir . '/' . $this->_fileStageToPath($row->file_stage) . '/' . $wrongFileName;
						if (Services::get('file')->fs->has($targetFilename)) {
							continue;
						}
						if (!Services::get('file')->fs->rename($sourceFilename, $targetFilename)) {
							error_log("Unable to move \"$sourceFilename\" to \"$targetFilename\".");
						}
					}
				}
			}
		}
		return true;
	}

	/**
	 * For 3.0.x - 3.1.0 upgrade: repair the migration of the HTML galley CSS files in the OJS files_dir.
	 *
	 * NOTE: submission_files table should be first fixed with the SQLs from GitHub Issue: https://github.com/pkp/pkp-lib/issues/2758
	 *
	 * @return boolean
	 */
	function moveCSSFiles() {
		$journalDao = DAORegistry::getDAO('JournalDAO'); /* @var $journalDao JournalDAO */
		$genreDao = DAORegistry::getDAO('GenreDAO'); /* @var $genreDao GenreDAO */

		import('lib.pkp.classes.file.FileManager');
		import('lib.pkp.classes.submission.SubmissionFile'); // SUBMISSION_FILE_ constants

		$fileManager = new FileManager();
		$journals = $journalDao->getAll();
		while ($journal = $journals->next()) {
			// Get style genre
			$genre = $genreDao->getByKey('STYLE', $journal->getId());

			$rows = Capsule::table('submission_files')
				->where('file_stage', '=', SUBMISSION_FILE_DEPENDENT)
				->where('genre_id', '=', (int) $genre->getId())
				->where('assoc_type', '=', ASSOC_TYPE_SUBMISSION_FILE)
				->get();

			foreach ($rows as $row) {
				// Get the wrong file name (after the 3.0.x migration)
				// and the correct file name
				$timestamp = date('Ymd', strtotime($row->date_uploaded));
				$extension = $fileManager->parseFileExtension($row->original_file_name);
				$wrongServerName = 	$row->submission_id . '-' . '1' . '-' . $row->file_id . '-' . $row->revision . '-' . '1' . '-' . $timestamp . '.' . strtolower_codesafe($extension);
				$newServerName = 	$row->submission_id . '-' . $genre->getId() . '-' . $row->file_id . '-' . $row->revision . '-' . SUBMISSION_FILE_DEPENDENT . '-' . $timestamp . '.' . strtolower_codesafe($extension);
				// Get the old file path (after the 3.0.x migration, i.e. from OJS 2.4.x)
				// and the correct file path
				$submissionDir = Services::get('submissionFile')->getSubmissionDir($journal->getId(), $row->submission_id);
				$sourceFilename = $submissionDir . '/public' . '/' . $wrongServerName;
				$targetFilename = $submissionDir . '/submission/proof' . '/' . $newServerName;
				if (!Services::get('file')->fs->rename($sourceFilename, $targetFilename)) {
					error_log("Unable to move \"$sourceFilename\" to \"$targetFilename\".");
				}
			}
		}
		return true;
	}

	/**
	 * For 3.0.x - 3.1.1 upgrade: repair the migration of the supp files.
	 * @return boolean True indicates success.
	 */
	function repairSuppFilesFilestage() {
		$submissionFileDao = DAORegistry::getDAO('SubmissionFileDAO'); /* @var $submissionFileDao SubmissionFileDAO */
		import('lib.pkp.classes.file.FileManager');
		import('lib.pkp.classes.submission.SubmissionFile'); // SUBMISSION_FILE_ constants
		$fileManager = new FileManager();

		$rows = Capsule::table('submission_supplementary_files as ssf')
			->leftJoin('submission_files as sf', 'sf.file_id', '=', 'ssf.file_id')
			->leftJoin('submissions as s', 's.submission_id', '=', 'sf.submission_id')
			->where('sf.file_stage', '=', SUBMISSION_FILE_SUBMISSION)
			->where('sf.assoc_type', '=', ASSOC_TYPE_REPRESENTATION)
			->where('sf.revision', '=', 'ssf.revision')
			->get();

		foreach ($rows as $row) {
			$submissionDir = Services::get('submissionFile')->getSubmissionDir($row->context_id, $row->submission_id);
			$generatedOldFilename = sprintf(
				'%d-%s-%d-%d-%d-%s.%s',
				$row->submission_id,
				$row->genre_id,
				$row->file_id,
				$row->revision,
				$row->file_stage,
				date('Ymd', strtotime($row->date_uploaded)),
				strtolower_codesafe($fileManager->parseFileExtension($row->original_file_name))
			);
			$generatedNewFilename = sprintf(
				'%d-%s-%d-%d-%d-%s.%s',
				$row->submission_id,
				$row->genre_id,
				$row->file_id,
				$row->revision,
				SUBMISSION_FILE_PROOF,
				date('Ymd', strtotime($row->date_uploaded)),
				strtolower_codesafe($fileManager->parseFileExtension($row->original_file_name))
			);
			$oldFileName = $submissionDir . '/' . $submissionFileRevision->_fileStageToPath($submissionFileRevision->getFileStage()) . '/' . $generatedOldFilename;
			$newFileName = $submissionDir . '/' . $submissionFileRevision->_fileStageToPath($submissionFileRevision->getFileStage()) . '/' . $generatedNewFilename;
			if (!Services::get('file')->fs->rename($oldFileName, $newFileName)) {
				error_log("Unable to move \"$oldFileName\" to \"$newFileName\".");
			}
			Capsule::table('submission_files')
				->where('file_id', '=', $row->file_id)
				->where('revision', '=', $row->revision)
				->update(['file_stage' => SUBMISSION_FILE_PROOF]);
		}
		return true;
	}

	/**
	 * If StaticPages table exists we should port the data as NMIs
	 * @return boolean
	 */
	function migrateStaticPagesToNavigationMenuItems() {
		if ($this->tableExists('static_pages')) {
			$contextDao = Application::getContextDAO();
			$navigationMenuItemDao = DAORegistry::getDAO('NavigationMenuItemDAO'); /* @var $navigationMenuItemDao NavigationMenuItemDAO */

			import('plugins.generic.staticPages.classes.StaticPagesDAO');

			$staticPagesDao = new StaticPagesDAO();

			$contexts = $contextDao->getAll();
			while ($context = $contexts->next()) {
				$contextStaticPages = $staticPagesDao->getByContextId($context->getId())->toAssociativeArray();
				foreach($contextStaticPages as $staticPage) {
					$retNMIId = $navigationMenuItemDao->portStaticPage($staticPage);
					if ($retNMIId) {
						$staticPagesDao->deleteById($staticPage->getId());
					} else {
						error_log('WARNING: The StaticPage "' . $staticPage->getLocalizedTitle() . '" uses a path (' . $staticPage->getPath() . ') that conflicts with an existing Custom Navigation Menu Item path. Skipping this StaticPage.');
					}
				}
			}
		}

		return true;
	}

	/**
	 * Migrate sr_SR locale to the new sr_RS@latin.
	 * @return boolean
	 */
	function migrateSRLocale() {
		$oldLocale = 'sr_SR';
		$newLocale = 'sr_RS@latin';

		$oldLocaleStringLength = 's:5';

		$journalSettingsDao = DAORegistry::getDAO('JournalSettingsDAO'); /* @var $journalSettingsDao JournalSettingsDAO */

		// Check if the sr_SR is used, and if not do not run further
		$srExistResult = $journalSettingsDao->retrieve('SELECT COUNT(*) AS row_count FROM site WHERE installed_locales LIKE ?', ['%'.$oldLocale.'%']);
		$row = $srExistResult->current();
		$srExist = $row && $row->row_count;
		if (!$srExist) return true;

		// Consider all DB tables that have locale column:
		$dbTables = array(
			'announcement_settings', 'announcement_type_settings', 'author_settings', 'books_for_review_settings', 'citation_settings', 'controlled_vocab_entry_settings',
			'data_object_tombstone_settings', 'email_templates_data', 'email_templates_default_data', 'external_feed_settings', 'filter_settings', 'genre_settings', 'group_settings',
			'issue_galleys', 'issue_galley_settings', 'issue_settings', 'journal_settings', 'library_file_settings', 'metadata_description_settings',
			'navigation_menu_item_assignment_settings', 'navigation_menu_item_settings', 'notification_settings', 'referral_settings',
			'review_form_element_settings', 'review_form_settings', 'review_object_metadata_settings', 'review_object_type_settings', 'section_settings', 'site_settings',
			'static_page_settings', 'submissions', 'submission_file_settings', 'submission_galleys', 'submission_galley_settings', 'submission_settings', 'subscription_type_settings',
			'user_group_settings', 'user_settings',
		);
		foreach ($dbTables as $dbTable) {
			if ($this->tableExists($dbTable)) {
				$journalSettingsDao->update('UPDATE '.$dbTable.' SET locale = ? WHERE locale = ?', array($newLocale, $oldLocale));
			}
		}
		// Consider other locale columns
		$journalSettingsDao->update('UPDATE journals SET primary_locale = ? WHERE primary_locale = ?', array($newLocale, $oldLocale));
		$journalSettingsDao->update('UPDATE site SET primary_locale = ? WHERE primary_locale = ?', array($newLocale, $oldLocale));
		$journalSettingsDao->update('UPDATE site SET installed_locales = REPLACE(installed_locales, ?, ?)', array($oldLocale, $newLocale));
		$journalSettingsDao->update('UPDATE site SET supported_locales = REPLACE(supported_locales, ?, ?)', array($oldLocale, $newLocale));
		$journalSettingsDao->update('UPDATE users SET locales = REPLACE(locales, ?, ?)', array($oldLocale, $newLocale));

		// journal_settings
		// Consider array setting values from the setting names:
		// supportedFormLocales, supportedLocales, supportedSubmissionLocales
		$settingNames = "('supportedFormLocales', 'supportedLocales', 'supportedSubmissionLocales')";
		// As a precaution use $oldLocaleStringLength, to exclude that the text contain the old locale string
		$settingValueResult = $journalSettingsDao->retrieve('SELECT * FROM journal_settings WHERE setting_name IN ' .$settingNames .' AND setting_value LIKE ? AND setting_type = \'object\'', array('%' .$oldLocaleStringLength .':"' .$oldLocale .'%'));
		foreach ($settingValueResult as $row) {
			$arraySettingValue = $journalSettingsDao->getSetting($row->journal_id, $row->setting_name);
			for($i = 0; $i < count($arraySettingValue); $i++) {
				if ($arraySettingValue[$i] == $oldLocale) {
					$arraySettingValue[$i] = $newLocale;
				}
			}
			$journalSettingsDao->updateSetting($row->journal_id, $row->setting_name, $arraySettingValue);
		}

		// Consider journal images
		// Note that the locale column values are already changed above
		$publicFileManager = new PublicFileManager();
		$settingNames = "('homeHeaderLogoImage', 'homeHeaderTitleImage', 'homepageImage', 'journalFavicon', 'journalThumbnail', 'pageHeaderLogoImage', 'pageHeaderTitleImage')";
		$settingValueResult = $journalSettingsDao->retrieve('SELECT * FROM journal_settings WHERE setting_name IN ' .$settingNames .' AND locale = ? AND setting_value LIKE ? AND setting_type = \'object\'', array($newLocale, '%' .$oldLocale .'%'));
		foreach ($settingValueResult as $row) {
			$arraySettingValue = $journalSettingsDao->getSetting($row->journal_id, $row->setting_name, $newLocale);
			$oldUploadName = $arraySettingValue['uploadName'];
			$newUploadName = str_replace('_'.$oldLocale.'.', '_'.$newLocale.'.', $oldUploadName);
			if ($publicFileManager->fileExists($publicFileManager->getContextFilesPath($row->journal_id) . '/' . $oldUploadName)) {
				$publicFileManager->copyContextFile($row->journal_id, $publicFileManager->getContextFilesPath($row->journal_id) . '/' . $oldUploadName, $newUploadName);
				$publicFileManager->removeContextFile($row->journal_id, $oldUploadName);
			}
			$arraySettingValue['uploadName'] = $newUploadName;
			$newArraySettingValue[$newLocale] = $arraySettingValue;
			$journalSettingsDao->updateSetting($row->journal_id, $row->setting_name, $newArraySettingValue, 'object', true);
		}

		// Consider issue cover images
		// Note that the locale column values are already changed above
		$settingValueResult = $journalSettingsDao->retrieve('SELECT a.*, b.journal_id FROM issue_settings a, issues b WHERE a.setting_name = \'fileName\' AND a.locale = ? AND a.setting_value LIKE ? AND a.setting_type = \'string\' AND b.issue_id = a.issue_id', array($newLocale, '%' .$oldLocale .'%'));
		foreach ($settingValueResult as $row) {
			$oldCoverImage = $row->setting_value;
			$newCoverImage = str_replace('_'.$oldLocale.'.', '_'.$newLocale.'.', $oldCoverImage);
			if ($publicFileManager->fileExists($publicFileManager->getContextFilesPath($row->journal_id) . '/' . $oldCoverImage)) {
				$publicFileManager->copyContextFile($row->journal_id, $publicFileManager->getContextFilesPath($row->journal_id) . '/' . $oldCoverImage, $newCoverImage);
				$publicFileManager->removeContextFile($row->journal_id, $oldCoverImage);
			}
			$journalSettingsDao->update('UPDATE issue_settings SET setting_value = ? WHERE issue_id = ? AND setting_name = \'fileName\' AND locale = ?', [$newCoverImage, (int) $row->issue_id, $newLocale]);
		}

		// Consider article cover images
		// Note that the locale column values are already changed above
		$settingValueResult = $journalSettingsDao->retrieve('SELECT a.*, b.context_id FROM submission_settings a, submissions b WHERE a.setting_name = \'fileName\' AND a.locale = ? AND a.setting_value LIKE ? AND b.submission_id = a.submission_id', array($newLocale, '%' .$oldLocale .'%'));
		foreach ($settingValueResult as $row) {
			$oldCoverImage = $row->setting_value;
			$newCoverImage = str_replace('_'.$oldLocale.'.', '_'.$newLocale.'.', $oldCoverImage);
			if ($publicFileManager->fileExists($publicFileManager->getContextFilesPath($row->context_id) . '/' . $oldCoverImage)) {
				$publicFileManager->copyContextFile($row->context_id, $publicFileManager->getContextFilesPath($row->context_id) . '/' . $oldCoverImage, $newCoverImage);
				$publicFileManager->removeContextFile($row->context_id, $oldCoverImage);
			}
			$journalSettingsDao->update('UPDATE submission_settings SET setting_value = ? WHERE submission_id = ? AND setting_name = \'fileName\' AND locale = ?', array($newCoverImage, (int) $row->submission_id, $newLocale));
		}

		// plugin_settings
		// Consider array setting values from the setting names:
		// blockContent (from a custom block plugin), additionalInformation (from objects for review plugin)
		$pluginSettingsDao = DAORegistry::getDAO('PluginSettingsDAO'); /* @var $pluginSettingsDao PluginSettingsDAO */
		$settingNames = "('blockContent', 'additionalInformation')";
		$settingValueResult = $pluginSettingsDao->retrieve('SELECT * FROM plugin_settings WHERE setting_name IN ' .$settingNames .' AND setting_value LIKE ?', array('%' .$oldLocaleStringLength .':"' .$oldLocale .'%'));
		foreach ($settingValueResult as $row) {
			$arraySettingValue = $pluginSettingsDao->getSetting($row->context_id, $row->plugin_name, $row->setting_name);
			$arraySettingValue[$newLocale] = $arraySettingValue[$oldLocale];
			unset($arraySettingValue[$oldLocale]);
			$pluginSettingsDao->updateSetting($row->context_id, $row->plugin_name, $row->setting_name, $arraySettingValue);
		}

		return true;
	}

	/**
	 * Migrate first and last user names as multilingual into the DB table user_settings.
	 * @return boolean
	 */
	function migrateUserAndAuthorNames() {
		$userDao = DAORegistry::getDAO('UserDAO'); /* @var $userDao UserDAO */
		import('lib.pkp.classes.identity.Identity'); // IDENTITY_SETTING_...
		// the user names will be saved in the site's primary locale
		$userDao->update("INSERT INTO user_settings (user_id, locale, setting_name, setting_value, setting_type) SELECT DISTINCT u.user_id, s.primary_locale, ?, u.first_name, 'string' FROM users_tmp u, site s", array(IDENTITY_SETTING_GIVENNAME));
		$userDao->update("INSERT INTO user_settings (user_id, locale, setting_name, setting_value, setting_type) SELECT DISTINCT u.user_id, s.primary_locale, ?, u.last_name, 'string' FROM users_tmp u, site s", array(IDENTITY_SETTING_FAMILYNAME));
		// the author names will be saved in the submission's primary locale
		$userDao->update("INSERT INTO author_settings (author_id, locale, setting_name, setting_value, setting_type) SELECT DISTINCT a.author_id, s.locale, ?, a.first_name, 'string' FROM authors_tmp a, submissions s WHERE s.submission_id = a.submission_id", array(IDENTITY_SETTING_GIVENNAME));
		$userDao->update("INSERT INTO author_settings (author_id, locale, setting_name, setting_value, setting_type) SELECT DISTINCT a.author_id, s.locale, ?, a.last_name, 'string' FROM authors_tmp a, submissions s WHERE s.submission_id = a.submission_id", array(IDENTITY_SETTING_FAMILYNAME));

		// middle name will be migrated to the given name
		// note that given names are already migrated to the settings table
		switch (Config::getVar('database', 'driver')) {
			case 'mysql':
			case 'mysqli':
				// the alias for _settings table cannot be used for some reason -- syntax error
				$userDao->update("UPDATE user_settings, users_tmp u SET user_settings.setting_value = CONCAT(user_settings.setting_value, ' ', u.middle_name) WHERE user_settings.setting_name = ? AND u.user_id = user_settings.user_id AND u.middle_name IS NOT NULL AND u.middle_name <> ''", array(IDENTITY_SETTING_GIVENNAME));
				$userDao->update("UPDATE author_settings, authors_tmp a SET author_settings.setting_value = CONCAT(author_settings.setting_value, ' ', a.middle_name) WHERE author_settings.setting_name = ? AND a.author_id = author_settings.author_id AND a.middle_name IS NOT NULL AND a.middle_name <> ''", array(IDENTITY_SETTING_GIVENNAME));
				break;
			case 'postgres':
			case 'postgres64':
			case 'postgres7':
			case 'postgres8':
			case 'postgres9':
				$userDao->update("UPDATE user_settings SET setting_value = CONCAT(setting_value, ' ', u.middle_name) FROM users_tmp u WHERE user_settings.setting_name = ? AND u.user_id = user_settings.user_id AND u.middle_name IS NOT NULL AND u.middle_name <> ''", array(IDENTITY_SETTING_GIVENNAME));
				$userDao->update("UPDATE author_settings SET setting_value = CONCAT(setting_value, ' ', a.middle_name) FROM authors_tmp a WHERE author_settings.setting_name = ? AND a.author_id = author_settings.author_id AND a.middle_name IS NOT NULL AND a.middle_name <> ''", array(IDENTITY_SETTING_GIVENNAME));
				break;
			default: fatalError('Unknown database type!');
		}

		// salutation and suffix will be migrated to the preferred public name
		// user preferred public names will be inserted for each supported site locales
		$siteDao = DAORegistry::getDAO('SiteDAO'); /* @var $siteDao SiteDAO */
		$site = $siteDao->getSite();
		$supportedLocales = $site->getSupportedLocales();
		$userResult = $userDao->retrieve(
			"SELECT user_id, first_name, last_name, middle_name, salutation, suffix FROM users_tmp
			WHERE (salutation IS NOT NULL AND salutation <> '') OR
			(suffix IS NOT NULL AND suffix <> '')"
		);
		foreach ($userResult as $row) {
			$userId = $row->user_id;
			$firstName = $row->first_name;
			$lastName = $row->last_name;
			$middleName = $row->middle_name;
			$salutation = $row->salutation;
			$suffix = $row->suffix;
			foreach ($supportedLocales as $siteLocale) {
				$preferredPublicName = ($salutation != '' ? "$salutation " : '') . "$firstName " . ($middleName != '' ? "$middleName " : '') . $lastName . ($suffix != '' ? ", $suffix" : '');
				if (AppLocale::isLocaleWithFamilyFirst($siteLocale)) {
					$preferredPublicName = "$lastName, " . ($salutation != '' ? "$salutation " : '') . $firstName . ($middleName != '' ? " $middleName" : '');
				}
				$userDao->update(
					"INSERT INTO user_settings (user_id, locale, setting_name, setting_value, setting_type) VALUES (?, ?, 'preferredPublicName', ?, 'string')",
					[(int) $userId, $siteLocale, $preferredPublicName]
				);
			}
		}

		// author suffix will be migrated to the author preferred public name
		// author preferred public names will be inserted for each journal supported locale
		// get supported locales for all journals
		$journalDao = DAORegistry::getDAO('JournalDAO'); /* @var $journalDao JournalDAO */
		$journals = $journalDao->getAll();
		$journalsSupportedLocales = array();
		while ($journal = $journals->next()) {
			$journalsSupportedLocales[$journal->getId()] = $journal->getSupportedLocales();
		}
		// get all authors with a suffix
		$authorResult = $userDao->retrieve(
			"SELECT a.author_id, a.first_name, a.last_name, a.middle_name, a.suffix, j.journal_id FROM authors_tmp a
			LEFT JOIN submissions s ON (s.submission_id = a.submission_id)
			LEFT JOIN journals j ON (j.journal_id = s.context_id)
			WHERE suffix IS NOT NULL AND suffix <> ''"
		);
		foreach ($authorResult as $row) {
			$authorId = $row->author_id;
			$firstName = $row->first_name;
			$lastName = $row->last_name;
			$middleName = $row->middle_name;
			$suffix = $row->suffix;
			$journalId = $row->journal_id;
			$supportedLocales = $journalsSupportedLocales[$journalId];
			foreach ($supportedLocales as $locale) {
				$preferredPublicName = "$firstName " . ($middleName != '' ? "$middleName " : '') . $lastName . ($suffix != '' ? ", $suffix" : '');
				if (AppLocale::isLocaleWithFamilyFirst($locale)) {
					$preferredPublicName = "$lastName, " . $firstName . ($middleName != '' ? " $middleName" : '');
				}
				$userDao->update(
					"INSERT INTO author_settings (author_id, locale, setting_name, setting_value, setting_type) VALUES (?, ?, 'preferredPublicName', ?, 'string')",
					[(int) $authorId, $locale, $preferredPublicName]
				);
			}
		}

		// remove temporary table
		$siteDao->update('DROP TABLE users_tmp');
		$siteDao->update('DROP TABLE authors_tmp');
		return true;
	}

	/**
	* Update assoc_id for assoc_type ASSOC_TYPE_SUBMISSION_FILE_COUNTER_OTHER = 531
	* @return boolean True indicates success.
	*/
	function updateSuppFileMetrics() {
		$submissionFileDao = DAORegistry::getDAO('SubmissionFileDAO'); /* @var $submissionFileDao SubmissionFileDAO */
		$metricsDao = DAORegistry::getDAO('MetricsDAO'); /* @var $metricsDao MetricsDAO */
		// Copy 531 assoc_type data to temp table
		$metricsDao->update(
			'CREATE TABLE metrics_supp AS (SELECT * FROM metrics WHERE assoc_type = 531)'
		);
		// Fetch submission_file data with old-supp-id
		$result = $submissionFileDao->retrieve(
			'SELECT * FROM submission_file_settings WHERE setting_name =  ?',
			['old-supp-id']
		);
		// Loop through the data and save to temp table
		foreach ($result as $row) {
			// Use assoc_type 2531 to prevent collisions between old assoc_id and new assoc_id
			$metricsDao->update(
				'UPDATE metrics_supp SET assoc_id = ?, assoc_type = ? WHERE assoc_type = ? AND assoc_id = ?',
				[(int) $row->file_id, 2531, 531, (int) $row->setting_value]
			);
		}
		// update temprorary 2531 values to 531 values
		$metricsDao->update('UPDATE metrics_supp SET assoc_type = ? WHERE assoc_type = ?', [531, 2531]);
		// delete all existing 531 values from the actual metrics table
		$metricsDao->update('DELETE FROM metrics WHERE assoc_type = 531');
		// copy updated 531 values from metrics_supp to metrics table
		$metricsDao->update('INSERT INTO metrics SELECT * FROM metrics_supp');
		// Drop metrics_supp table
		$metricsDao->update('DROP TABLE metrics_supp');
		return true;
	}

	/**
	 * Add an entry for the site stylesheet to the site_settings database when it
	 * exists
	 */
	function migrateSiteStylesheet() {
		$siteDao = DAORegistry::getDAO('SiteDAO'); /* @var $siteDao SiteDAO */

		import('classes.file.PublicFileManager');
		$publicFileManager = new PublicFileManager();

		if (!file_exists($publicFileManager->getSiteFilesPath() . '/sitestyle.css')) {
			return true;
		}

		$site = $siteDao->getSite();
		$site->setData('styleSheet', 'sitestyle.css');
		$siteDao->updateObject($site);

		return true;
	}

	/**
	 * Copy a context's copyrightNotice to a new licenseTerms setting, leaving
	 * the copyrightNotice in place.
	 */
	function createLicenseTerms() {
		$contextDao = Application::getContextDao();

		$result = $contextDao->retrieve('SELECT * from ' . $contextDao->settingsTableName . " WHERE setting_name='copyrightNotice'");
		foreach ($result as $row) {
			$row = (array) $row;
			$contextDao->update('
				INSERT INTO ' . $contextDao->settingsTableName . ' (
					' . $contextDao->primaryKeyColumn . ',
					locale,
					setting_name,
					setting_value
				) VALUES (?, ?, ?, ?)',
				[
					$row[$contextDao->primaryKeyColumn],
					$row['locale'],
					'licenseTerms',
					$row['setting_value'],
				]
			);
		}
		return true;
	}

	/**
	 * Update permit_metadata_edit and can_change_metadata for user_groups and stage_assignments tables.
	 *
	 * @return boolean True indicates success.
	 */
	function changeUserRolesAndStageAssignmentsForStagePermitSubmissionEdit() {
		$stageAssignmentDao = DAORegistry::getDAO('StageAssignmentDAO'); /** @var $stageAssignmentDao StageAssignmentDAO */
		$userGroupDao = DAORegistry::getDAO('UserGroupDAO'); /** @var $userGroupDao UserGroupDAO */

		$roles = UserGroupDAO::getNotChangeMetadataEditPermissionRoles();
		$roleString = '(' . implode(",", $roles) . ')';

		$userGroupDao->update('UPDATE user_groups SET permit_metadata_edit = 1 WHERE role_id IN ' . $roleString);
		switch (Config::getVar('database', 'driver')) {
			case 'mysql':
			case 'mysqli':
				$stageAssignmentDao->update('UPDATE stage_assignments sa JOIN user_groups ug on sa.user_group_id = ug.user_group_id SET sa.can_change_metadata = 1 WHERE ug.role_id IN ' . $roleString);
				break;
			case 'postgres':
			case 'postgres64':
			case 'postgres7':
			case 'postgres8':
			case 'postgres9':
				$stageAssignmentDao->update('UPDATE stage_assignments sa SET can_change_metadata=1 FROM user_groups ug WHERE sa.user_group_id = ug.user_group_id AND ug.role_id IN ' . $roleString);
				break;
			default: fatalError("Unknown database type!");
			}

		return true;
	}

	/**
	 * Update how submission cover images are stored
	 *
	 * Combines the coverImage and coverImageAltText settings in the
	 * submissions table into an assoc array stored under the coverImage
	 * setting.
	 *
	 * This will be migrated to the publication_settings table in
	 * 3.2.0_versioning.xml.
	 */
	function migrateSubmissionCoverImages() {
		$coverImagesBySubmission = [];

		$submissionDao = DAORegistry::getDAO('SubmissionDAO'); /* @var $submissionDao SubmissionDAO */
		$result = $submissionDao->retrieve(
			'SELECT * from submission_settings WHERE setting_name=\'coverImage\' OR setting_name=\'coverImageAltText\''
		);
		foreach ($result as $row) {
			$submissionId = $row->submission_id;
			if (empty($coverImagesBySubmission[$submissionId])) {
				$coverImagesBySubmission[$submissionId] = [];
			}
			if ($row->setting_name === 'coverImage') {
				$coverImagesBySubmission[$submissionId]['uploadName'] = $row->setting_value;
				$coverImagesBySubmission[$submissionId]['dateUploaded'] = Core::getCurrentDate();
			} elseif ($row->setting_name === 'coverImageAltText') {
				$coverImagesBySubmission[$submissionId]['altText'] = $row->setting_value;
			}
		}

		foreach ($coverImagesBySubmission as $submissionId => $coverImagesBySubmission) {
			$submissionDao->update(
				'UPDATE submission_settings
					SET setting_value = ?
					WHERE submission_id = ? AND setting_name = ?',
				[
					serialize($coverImagesBySubmission),
					$submissionId,
					'coverImage',
				]
			);
		}

		return true;
	}

	/**
	 * Get the directory of a file based on its file stage
	 *
	 * @param int $fileStage ONe of SUBMISSION_FILE_ constants
	 * @return string
	 */
	function _fileStageToPath($fileStage) {
		import('lib.pkp.classes.submission.SubmissionFile');
		static $fileStagePathMap = [
			SUBMISSION_FILE_SUBMISSION => 'submission',
			SUBMISSION_FILE_NOTE => 'note',
			SUBMISSION_FILE_REVIEW_FILE => 'submission/review',
			SUBMISSION_FILE_REVIEW_ATTACHMENT => 'submission/review/attachment',
			SUBMISSION_FILE_REVIEW_REVISION => 'submission/review/revision',
			SUBMISSION_FILE_FINAL => 'submission/final',
			SUBMISSION_FILE_COPYEDIT => 'submission/copyedit',
			SUBMISSION_FILE_DEPENDENT => 'submission/proof',
			SUBMISSION_FILE_PROOF => 'submission/proof',
			SUBMISSION_FILE_PRODUCTION_READY => 'submission/productionReady',
			SUBMISSION_FILE_ATTACHMENT => 'attachment',
			SUBMISSION_FILE_QUERY => 'submission/query',
		];

		if (!isset($fileStagePathMap[$fileStage])) {
			throw new Exception('A file assigned to the file stage ' . $fileStage . ' could not be migrated.');
		}

		return $fileStagePathMap[$fileStage];
	}
}
