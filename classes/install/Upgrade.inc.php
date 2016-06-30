<?php

/**
 * @file classes/install/Upgrade.inc.php
 *
 * Copyright (c) 2014-2016 Simon Fraser University Library
 * Copyright (c) 2003-2016 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class Upgrade
 * @ingroup install
 *
 * @brief Perform system upgrade.
 */


import('lib.pkp.classes.install.Installer');

class Upgrade extends Installer {
	/**
	 * Constructor.
	 * @param $params array upgrade parameters
	 */
	function Upgrade($params, $installFile = 'upgrade.xml', $isPlugin = false) {
		parent::Installer($installFile, $params, $isPlugin);
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
		import('classes.search.ArticleSearchIndex');
		$articleSearchIndex = new ArticleSearchIndex();
		$articleSearchIndex->rebuildIndex();
		return true;
	}

	/**
	 * Clear the data cache files (needed because of direct tinkering
	 * with settings tables)
	 * @return boolean
	 */
	function clearDataCache() {
		$cacheManager = CacheManager::getManager();
		$cacheManager->flush(null, CACHE_TYPE_FILE);
		$cacheManager->flush(null, CACHE_TYPE_OBJECT);
		return true;
	}

	/**
	 * For 3.0.0 upgrade: Convert string-field semi-colon separated metadata to controlled vocabularies.
	 * @return boolean
	 */
	function migrateArticleMetadata() {
		$journalDao = DAORegistry::getDAO('JournalDAO');
		$articleDao = DAORegistry::getDAO('ArticleDAO');

		// controlled vocabulary DAOs.
		$submissionSubjectDao = DAORegistry::getDAO('SubmissionSubjectDAO');
		$submissionDisciplineDao = DAORegistry::getDAO('SubmissionDisciplineDAO');
		$submissionAgencyDao = DAORegistry::getDAO('SubmissionAgencyDAO');
		$submissionLanguageDao = DAORegistry::getDAO('SubmissionLanguageDAO');
		$controlledVocabDao = DAORegistry::getDAO('ControlledVocabDAO');

		// check to see if there are any existing controlled vocabs for submissionAgency, submissionDiscipline, submissionSubject, or submissionLanguage.
		// IF there are, this implies that this code has run previously, so return.
		$vocabTestResult = $controlledVocabDao->retrieve('SELECT count(*) AS total FROM controlled_vocabs WHERE symbolic = \'submissionAgency\' OR symbolic = \'submissionDiscipline\' OR symbolic = \'submissionSubject\' OR symbolic = \'submissionLanguage\'');
		$testRow = $vocabTestResult->GetRowAssoc(false);
		if ($testRow['total'] > 0) return true;

		$journals = $journalDao->getAll();
		while ($journal = $journals->next()) {
			// for languages, we depend on the journal locale settings since languages are not localized.
			// Use Journal locales, or primary if no defined submission locales.
			$supportedLocales = $journal->getSupportedSubmissionLocales();

			if (empty($supportedLocales)) $supportedLocales = array($journal->getPrimaryLocale());
			else if (!is_array($supportedLocales)) $supportedLocales = array($supportedLocales);

			$result = $articleDao->retrieve('SELECT a.submission_id FROM submissions a WHERE a.context_id = ?', array((int)$journal->getId()));
			while (!$result->EOF) {
				$row = $result->GetRowAssoc(false);
				$articleId = (int)$row['submission_id'];
				$settings = array();
				$settingResult = $articleDao->retrieve('SELECT setting_value, setting_name, locale FROM submission_settings WHERE submission_id = ? AND (setting_name = \'discipline\' OR setting_name = \'subject\' OR setting_name = \'sponsor\');', array((int)$articleId));
				while (!$settingResult->EOF) {
					$settingRow = $settingResult->GetRowAssoc(false);
					$locale = $settingRow['locale'];
					$settingName = $settingRow['setting_name'];
					$settingValue = $settingRow['setting_value'];
					$settings[$settingName][$locale] = $settingValue;
					$settingResult->MoveNext();
				}
				$settingResult->Close();

				$languageResult = $articleDao->retrieve('SELECT language FROM submissions WHERE submission_id = ?', array((int)$articleId));
				$languageRow = $languageResult->getRowAssoc(false);
				// language is NOT localized originally.
				$language = $languageRow['language'];
				$languageResult->Close();
				// test for locales for each field since locales may have been modified since
				// the article was last edited.

				$disciplines = $subjects = $agencies = array();

				if (array_key_exists('discipline', $settings)) {
					$disciplineLocales = array_keys($settings['discipline']);
					if (is_array($disciplineLocales)) {
						foreach ($disciplineLocales as &$locale) {
							$disciplines[$locale] = preg_split('/[,;:]/', $settings['discipline'][$locale]);
						}
						$submissionDisciplineDao->insertDisciplines($disciplines, $articleId, false);
					}
					unset($disciplineLocales);
					unset($disciplines);
				}

				if (array_key_exists('subject', $settings)) {
					$subjectLocales = array_keys($settings['subject']);
					if (is_array($subjectLocales)) {
						foreach ($subjectLocales as &$locale) {
							$subjects[$locale] = preg_split('/[,;:]/', $settings['subject'][$locale]);
						}
						$submissionSubjectDao->insertSubjects($subjects, $articleId, false);
					}
					unset($subjectLocales);
					unset($subjects);
				}

				if (array_key_exists('sponsor', $settings)) {
					$sponsorLocales = array_keys($settings['sponsor']);
					if (is_array($sponsorLocales)) {
						foreach ($sponsorLocales as &$locale) {
							// note array name change.  Sponsor -> Agency
							$agencies[$locale] = preg_split('/[,;:]/', $settings['sponsor'][$locale]);
						}
						$submissionAgencyDao->insertAgencies($agencies, $articleId, false);
					}
					unset($sponsorLocales);
					unset($agencies);
				}

				$languages = array();
				foreach ($supportedLocales as &$locale) {
					$languages[$locale] = preg_split('/\s+/', $language);
				}

				$submissionLanguageDao->insertLanguages($languages, $articleId, false);

				unset($languages);
				unset($language);
				unset($settings);
				$result->MoveNext();
			}
			$result->Close();
			unset($supportedLocales);
			unset($result);
			unset($journal);
		}

		return true;
	}

	/**
	 * For 3.0.0 upgrade:  Migrate the static user role structure to
	 * user groups and stage assignments.
	 * @return boolean
	 */
	function migrateUserRoles() {

		// if there are any user_groups created, then this has already run.  Return immediately in that case.

		$userGroupDao = DAORegistry::getDAO('UserGroupDAO');
		$userGroupTest = $userGroupDao->retrieve('SELECT count(*) AS total FROM user_groups');
		$testRow = $userGroupTest->GetRowAssoc(false);
		if ($testRow['total'] > 0) return true;

		// First, do Admins.
		// create the admin user group.
		$userGroupDao->update('INSERT INTO user_groups (user_group_id, context_id, role_id, is_default) VALUES (?, ?, ?, ?)', array(1, CONTEXT_SITE, ROLE_ID_SITE_ADMIN, 1));
		$userResult = $userGroupDao->retrieve('SELECT user_id FROM roles WHERE journal_id = ? AND role_id = ?', array(CONTEXT_SITE, ROLE_ID_SITE_ADMIN));
		while (!$userResult->EOF) {
			$row = $userResult->GetRowAssoc(false);
			$userGroupDao->update('INSERT INTO user_user_groups (user_group_id, user_id) VALUES (?, ?)', array(1, (int) $row['user_id']));
			$userResult->MoveNext();
		}

		// iterate through all journals and assign remaining users to their respective groups.
		$journalDao = DAORegistry::getDAO('JournalDAO');
		$journals = $journalDao->getAll();

		AppLocale::requireComponents(LOCALE_COMPONENT_APP_DEFAULT, LOCALE_COMPONENT_PKP_DEFAULT);

		define('ROLE_ID_LAYOUT_EDITOR',	0x00000300);
		define('ROLE_ID_COPYEDITOR', 0x00002000);
		define('ROLE_ID_PROOFREADER', 0x00003000);

		while ($journal = $journals->next()) {
			// Install default user groups so we can assign users to them.
			$userGroupDao->installSettings($journal->getId(), 'registry/userGroups.xml');

			// Readers.
			$group = $userGroupDao->getDefaultByRoleId($journal->getId(), ROLE_ID_READER);
			$userResult = $journalDao->retrieve('SELECT user_id FROM roles WHERE journal_id = ? AND role_id = ?', array((int) $journal->getId(), ROLE_ID_READER));
			while (!$userResult->EOF) {
				$row = $userResult->GetRowAssoc(false);
				$userGroupDao->assignUserToGroup($row['user_id'], $group->getId());
				$userResult->MoveNext();
			}

			// Subscription Managers.
			$group = $userGroupDao->getDefaultByRoleId($journal->getId(), ROLE_ID_SUBSCRIPTION_MANAGER);
			$userResult = $journalDao->retrieve('SELECT user_id FROM roles WHERE journal_id = ? AND role_id = ?', array((int) $journal->getId(), ROLE_ID_SUBSCRIPTION_MANAGER));
			while (!$userResult->EOF) {
				$row = $userResult->GetRowAssoc(false);
				$userGroupDao->assignUserToGroup($row['user_id'], $group->getId());
				$userResult->MoveNext();
			}

			// Managers.
			$group = $userGroupDao->getDefaultByRoleId($journal->getId(), ROLE_ID_MANAGER);
			$userResult = $journalDao->retrieve('SELECT user_id FROM roles WHERE journal_id = ? AND role_id = ?', array((int) $journal->getId(), ROLE_ID_MANAGER));
			while (!$userResult->EOF) {
				$row = $userResult->GetRowAssoc(false);
				$userGroupDao->assignUserToGroup($row['user_id'], $group->getId());
				$userResult->MoveNext();
			}

			// Authors.
			$group = $userGroupDao->getDefaultByRoleId($journal->getId(), ROLE_ID_AUTHOR);
			$userResult = $journalDao->retrieve('SELECT user_id FROM roles WHERE journal_id = ? AND role_id = ?', array((int) $journal->getId(), ROLE_ID_AUTHOR));
			while (!$userResult->EOF) {
				$row = $userResult->GetRowAssoc(false);
				$userGroupDao->assignUserToGroup($row['user_id'], $group->getId());
				$userResult->MoveNext();
			}

			// update the user_group_id colun in the authors table.
			$userGroupDao->update('UPDATE authors SET user_group_id = ?', array((int) $group->getId()));

			// Reviewers.  All existing OJS reviewers get mapped to external reviewers.
			// There should only be one user group with ROLE_ID_REVIEWER in the external review stage.
			$userGroups = $userGroupDao->getUserGroupsByStage($journal->getId(), WORKFLOW_STAGE_ID_EXTERNAL_REVIEW, true, false, ROLE_ID_REVIEWER);
			$reviewerUserGroup = null; // keep this in scope for later.

			while ($group = $userGroups->next()) {
				// make sure.
				if ($group->getRoleId() != ROLE_ID_REVIEWER) continue;
				$reviewerUserGroup = $group;

				$userResult = $journalDao->retrieve('SELECT user_id FROM roles WHERE journal_id = ? AND role_id = ?', array((int) $journal->getId(), ROLE_ID_REVIEWER));
				while (!$userResult->EOF) {
					$row = $userResult->GetRowAssoc(false);
					$userGroupDao->assignUserToGroup($row['user_id'], $group->getId());
					$userResult->MoveNext();
				}
			}

			// fix stage id assignments for reviews.  OJS hard coded *all* of these to '1' initially. Consider OJS reviews as external reviews.
			$userGroupDao->update('UPDATE review_assignments SET stage_id = ?', array(WORKFLOW_STAGE_ID_EXTERNAL_REVIEW));

			// Guest editors.
			$userGroupIds = $userGroupDao->getUserGroupIdsByRoleId(ROLE_ID_GUEST_EDITOR, $journal->getId());
			$userResult = $journalDao->retrieve('SELECT user_id FROM roles WHERE journal_id = ? AND role_id = ?', array((int) $journal->getId(), ROLE_ID_GUEST_EDITOR));

			while (!$userResult->EOF) {
				$row = $userResult->GetRowAssoc(false);
				// there should only be one guest editor group id.
				$userGroupDao->assignUserToGroup($row['user_id'], $userGroupIds[0]);
				$userResult->MoveNext();
			}

			// regular Editors.  NOTE:  this involves a role id change from 0x100 to 0x10 (old OJS _EDITOR to PKP-lib _MANAGER).
			$userGroups = $userGroupDao->getByRoleId($journal->getId(), ROLE_ID_MANAGER);
			$editorUserGroup = null;
			while ($group = $userGroups->next()) {
				if ($group->getData('nameLocaleKey') == 'default.groups.name.editor') {
					$editorUserGroup = $group; // stash for later.
					$userResult = $journalDao->retrieve('SELECT user_id FROM roles WHERE journal_id = ? AND role_id = ?', array((int) $journal->getId(), 0x00000100 /* ROLE_ID_EDITOR */));
					while (!$userResult->EOF) {
						$row = $userResult->GetRowAssoc(false);
						$userGroupDao->assignUserToGroup($row['user_id'], $group->getId());
						$userResult->MoveNext();
					}
				}
			}

			// Section Editors.
			$sectionEditorGroup = $userGroupDao->getDefaultByRoleId($journal->getId(), ROLE_ID_SECTION_EDITOR);
			$userResult = $journalDao->retrieve('SELECT DISTINCT user_id FROM section_editors WHERE journal_id = ?', array((int) $journal->getId()));;
			while (!$userResult->EOF) {
				$row = $userResult->GetRowAssoc(false);
				$userGroupDao->assignUserToGroup($row['user_id'], $sectionEditorGroup->getId());
				$userResult->MoveNext();
			}

			// Layout Editors. NOTE:  this involves a role id change from 0x300 to 0x1001 (old OJS _LAYOUT_EDITOR to PKP-lib _ASSISTANT).
			$userGroups = $userGroupDao->getByRoleId($journal->getId(), ROLE_ID_ASSISTANT);
			$layoutEditorGroup = null;
			while ($group = $userGroups->next()) {
				if ($group->getData('nameLocaleKey') == 'default.groups.name.layoutEditor') {
					$layoutEditorGroup = $group;
					$userResult = $journalDao->retrieve('SELECT user_id FROM roles WHERE journal_id = ? AND role_id = ?', array((int) $journal->getId(), ROLE_ID_LAYOUT_EDITOR));
					while (!$userResult->EOF) {
						$row = $userResult->GetRowAssoc(false);
						$userGroupDao->assignUserToGroup($row['user_id'], $group->getId());
						$userResult->MoveNext();
					}
				}
			}

			// Copyeditors. NOTE:  this involves a role id change from 0x2000 to 0x1001 (old OJS _COPYEDITOR to PKP-lib _ASSISTANT).
			$userGroups = $userGroupDao->getByRoleId($journal->getId(), ROLE_ID_ASSISTANT);
			$copyEditorGroup = null;
			while ($group = $userGroups->next()) {
				if ($group->getData('nameLocaleKey') == 'default.groups.name.copyeditor') {
					$copyEditorGroup = $group;
					$userResult = $journalDao->retrieve('SELECT user_id FROM roles WHERE journal_id = ? AND role_id = ?', array((int) $journal->getId(), ROLE_ID_COPYEDITOR));
					while (!$userResult->EOF) {
						$row = $userResult->GetRowAssoc(false);
						$userGroupDao->assignUserToGroup($row['user_id'], $group->getId());
						$userResult->MoveNext();
					}
				}
			}

			// Proofreaders. NOTE:  this involves a role id change from 0x3000 to 0x1001 (old OJS _PROOFREADER to PKP-lib _ASSISTANT).
			$userGroups = $userGroupDao->getByRoleId($journal->getId(), ROLE_ID_ASSISTANT);
			$proofreaderGroup = null;
			while ($group = $userGroups->next()) {
				if ($group->getData('nameLocaleKey') == 'default.groups.name.proofreader') {
					$proofreaderGroup = $group;
					$userResult = $journalDao->retrieve('SELECT user_id FROM roles WHERE journal_id = ? AND role_id = ?', array((int) $journal->getId(), ROLE_ID_PROOFREADER));
					while (!$userResult->EOF) {
						$row = $userResult->GetRowAssoc(false);
						$userGroupDao->assignUserToGroup($row['user_id'], $group->getId());
						$userResult->MoveNext();
					}
				}
			}

			// Now, migrate stage assignments. This code is based on the default stage assignments outlined in registry/userGroups.xml
			$submissionDao = Application::getSubmissionDAO();
			$stageAssignmentDao = DAORegistry::getDAO('StageAssignmentDAO');
			$submissionResult = $submissionDao->retrieve('SELECT submission_id, user_id FROM submissions');
			$authorGroup = $userGroupDao->getDefaultByRoleId($journal->getId(), ROLE_ID_AUTHOR);
			while (!$submissionResult->EOF) {
				$submissionRow = $submissionResult->GetRowAssoc(false);
				$submissionId = $submissionRow['submission_id'];
				$submissionUserId = $submissionRow['user_id'];
				unset($submissionRow);

				// Authors get access to all stages.
				$stageAssignmentDao->build($submissionId, $authorGroup->getId(), $submissionUserId);

				// Reviewers get access to the external review stage.
				$reviewersResult = $stageAssignmentDao->retrieve('SELECT reviewer_id FROM review_assignments WHERE submission_id = ?', array($submissionId));
				while (!$reviewersResult->EOF) {
					$reviewerRow = $reviewersResult->GetRowAssoc(false);
					$stageAssignmentDao->build($submissionId, $reviewerUserGroup->getId(), $reviewerRow['reviewer_id']);
					$reviewersResult->MoveNext();
				}
				unset($reviewersResult);

				// Journal Editors
				// First, full editors.
				$editorsResult = $stageAssignmentDao->retrieve('SELECT e.* FROM submissions s, edit_assignments e, users u, roles r WHERE r.user_id = e.editor_id AND r.role_id = ' .
							0x00000100 /* ROLE_ID_EDITOR */ . ' AND e.article_id = ? AND r.journal_id = s.context_id AND s.submission_id = e.article_id AND e.editor_id = u.user_id', array($submissionId));
				while (!$editorsResult->EOF) {
					$editorRow = $editorsResult->GetRowAssoc(false);
					$stageAssignmentDao->build($submissionId, $editorUserGroup->getId(), $editorRow['editor_id']);
					$editorsResult->MoveNext();
				}
				unset($editorsResult);

				// Section Editors.
				$editorsResult = $stageAssignmentDao->retrieve('SELECT e.* FROM submissions s LEFT JOIN edit_assignments e ON (s.submission_id = e.article_id) LEFT JOIN users u ON (e.editor_id = u.user_id)
							LEFT JOIN roles r ON (r.user_id = e.editor_id AND r.role_id = ' . 0x00000100 /* ROLE_ID_EDITOR */ . ' AND r.journal_id = s.context_id) WHERE e.article_id = ? AND s.submission_id = e.article_id
							AND r.role_id IS NULL', array($submissionId));
				while (!$editorsResult->EOF) {
					$editorRow = $editorsResult->GetRowAssoc(false);
					$stageAssignmentDao->build($submissionId, $sectionEditorGroup->getId(), $editorRow['editor_id']);
					$editorsResult->MoveNext();
				}
				unset($editorsResult);

				// Copyeditors.  Pull from the signoffs for SIGNOFF_COPYEDITING_INITIAL.
				// there should only be one (or no) copyeditor for each submission.
				// 257 === 0x0000101 (the old assoc type for ASSOC_TYPE_ARTICLE)

				$copyEditorResult = $stageAssignmentDao->retrieve('SELECT user_id FROM signoffs WHERE assoc_type = ? AND assoc_id = ? AND symbolic = ?',
								array(257, $submissionId, 'SIGNOFF_COPYEDITING_INITIAL'));

				if ($copyEditorResult->NumRows() == 1) { // the signoff exists.
					$copyEditorRow = $copyEditorResult->GetRowAssoc(false);
					$copyEditorId = (int) $copyEditorRow['user_id'];
					if ($copyEditorId > 0) { // there is a user assigned.
						$stageAssignmentDao->build($submissionId, $copyEditorGroup->getId(), $copyEditorId);
					}
				}

				// Layout editors.  Pull from the signoffs for SIGNOFF_LAYOUT.
				// there should only be one (or no) layout editor for each submission.
				// 257 === 0x0000101 (the old assoc type for ASSOC_TYPE_ARTICLE)

				$layoutEditorResult = $stageAssignmentDao->retrieve('SELECT user_id FROM signoffs WHERE assoc_type = ? AND assoc_id = ? AND symbolic = ?',
						array(257, $submissionId, 'SIGNOFF_LAYOUT'));

				if ($layoutEditorResult->NumRows() == 1) { // the signoff exists.
					$layoutEditorRow = $layoutEditorResult->GetRowAssoc(false);
					$layoutEditorId = (int) $layoutEditorRow['user_id'];
					if ($layoutEditorId > 0) { // there is a user assigned.
						$stageAssignmentDao->build($submissionId, $layoutEditorGroup->getId(), $layoutEditorId);
					}
				}

				// Proofreaders.  Pull from the signoffs for SIGNOFF_PROOFREADING_PROOFREADER.
				// there should only be one (or no) layout editor for each submission.
				// 257 === 0x0000101 (the old assoc type for ASSOC_TYPE_ARTICLE)

				$proofreaderResult = $stageAssignmentDao->retrieve('SELECT user_id FROM signoffs WHERE assoc_type = ? AND assoc_id = ? AND symbolic = ?',
						array(257, $submissionId, 'SIGNOFF_PROOFREADING_PROOFREADER'));

				if ($proofreaderResult->NumRows() == 1) { // the signoff exists.
					$proofreaderRow = $proofreaderResult->GetRowAssoc(false);
					$proofreaderId = (int) $proofreaderRow['user_id'];
					if ($proofreaderId > 0) { // there is a user assigned.
						$stageAssignmentDao->build($submissionId, $proofreaderGroup->getId(), $proofreaderId);
					}
				}

				$submissionResult->MoveNext();
			}
		}

		return true;
	}

	/**
	 * For 3.0.0 upgrade.  Genres are required to migrate files.
	 */
	function installDefaultGenres() {
		$genreDao = DAORegistry::getDAO('GenreDAO');
		$siteDao = DAORegistry::getDAO('SiteDAO');
		$site = $siteDao->getSite();
		$contextsResult = $genreDao->retrieve('SELECT journal_id FROM journals');
		while (!$contextsResult->EOF) {

			$row = $contextsResult->GetRowAssoc(false);
			$genreDao->installDefaults($row['journal_id'], $site->getInstalledLocales());
			$contextsResult->MoveNext();
		}

		return true;
	}

	/**
	 * For 3.0.0 upgrade.  Migrates submission files to new paths.
	 */
	function migrateSubmissionFilePaths() {

		$submissionFileDao = DAORegistry::getDAO('SubmissionFileDAO');
		import('lib.pkp.classes.submission.SubmissionFile');
		$genreDao = DAORegistry::getDAO('GenreDAO');
		$journalDao = DAORegistry::getDAO('JournalDAO');

		$submissionFile = new SubmissionFile();

		import('lib.pkp.classes.file.FileManager');
		$fileManager = new FileManager();

		$submissionFilesResult = $submissionFileDao->retrieve('SELECT af.*, s.submission_id, s.context_id FROM article_files_migration af, submissions s WHERE af.article_id = s.submission_id');
		$filesDir = Config::getVar('files', 'files_dir') . '/journals/';
		while (!$submissionFilesResult->EOF){
			$row = $submissionFilesResult->GetRowAssoc(false);
			// Assemble the old file path.
			$oldFilePath = $filesDir . $row['context_id'] . '/articles/' . $row['submission_id'] . '/';
			if (isset($row['type'])) { // pre 2.4 upgrade.
				$oldFilePath .= $row['type'];
			} else { // post 2.4, we have file_stage instead.
				switch ($row['file_stage']) {
					case 1:
						$oldFilePath .= 'submission/original';
						break;
					case 2:
						$oldFilePath .= 'submission/review';
						break;
					case 3:
						$oldFilePath .= 'submission/editor';
						break;
					case 4:
						$oldFilePath .= 'submission/copyedit';
						break;
					case 5:
						$oldFilePath .= 'submission/layout';
						break;
					case 6:
						$oldFilePath .= 'supp';
						break;
					case 7:
						$oldFilePath .= 'public';
						break;
					case 8:
						$oldFilePath .= 'note';
						break;
					case 9:
						$oldFilePath .= 'attachment';
						break;
				}
			}

			$oldFilePath .= '/' . $row['file_name'];
			if (file_exists($oldFilePath)) { // sanity check.

				$newFilePath = $filesDir . $row['context_id'] . '/articles/' . $row['submission_id'] . '/';

				// Since we cannot be sure that we had a file_stage column before, query the new submission_files table.
				$submissionFileResult = $submissionFileDao->retrieve('SELECT genre_id, file_stage, date_uploaded, original_file_name
							FROM submission_files WHERE file_id = ? and revision = ?', array($row['file_id'], $row['revision']));
				$submissionFileRow = $submissionFileResult->GetRowAssoc(false);

				$newFilePath .= $submissionFile->_fileStageToPath($submissionFileRow['file_stage']);

				$genre = $genreDao->getById($submissionFileRow['genre_id']);
				// pull in the primary locale for this journal without loading the whole object.
				$localeResult = $journalDao->retrieve('SELECT primary_locale FROM journals WHERE journal_id = ?', array($row['context_id']));
				$localeRow = $localeResult->GetRowAssoc(false);

				$newFilePath .= '/' . $row['submission_id'] . '-' . $genre->getDesignation() . '_' . $genre->getName($localeRow['primary_locale']) . '-' .
					$row['file_id'] . '-' . $row['revision'] . '-' . $submissionFileRow['file_stage'] . '-' . date('Ymd', strtotime($submissionFileRow['date_uploaded'])) . '.' .
					strtolower_codesafe($fileManager->parseFileExtension($submissionFileRow['original_file_name']));

				$fileManager->copyFile($oldFilePath, $newFilePath);
				if (file_exists($newFilePath)) {
					$fileManager->deleteFile($oldFilePath);
				}
			}

			$submissionFilesResult->MoveNext();
			unset($localeResult);
			unset($submissionFileResult);
			unset($localeRow);
			unset($submissionFileRow);
		}

		return true;
	}

	/**
	 * For 2.4 upgrade: migrate COUNTER statistics to the metrics table.
	 */
	function migrateCounterPluginUsageStatistics() {
		$metricsDao = DAORegistry::getDAO('MetricsDAO'); /* @var $metricsDao MetricsDAO */
		$result = $metricsDao->retrieve('SELECT * FROM counter_monthly_log');
		if ($result->EOF) return true;

		$loadId = '3.0.0-upgrade-counter';
		$metricsDao->purgeLoadBatch($loadId);

		$fileTypeCounts = array(
			'count_html' => USAGE_STATS_REPORT_PLUGIN_FILE_TYPE_HTML,
			'count_pdf' => USAGE_STATS_REPORT_PLUGIN_FILE_TYPE_PDF,
			'count_other' => USAGE_STATS_REPORT_PLUGIN_FILE_TYPE_OTHER
		);

		while(!$result->EOF) {
			$row = $result->GetRowAssoc(false);
			foreach ($fileTypeCounts as $countType => $fileType) {
				$month = (string) $row['month'];
				if (strlen($month) == 1) {
					$month = '0' . $month;
				}
				if ($row[$countType]) {
					$record = array(
						'load_id' => $loadId,
						'assoc_type' => ASSOC_TYPE_JOURNAL,
						'assoc_id' => $row['journal_id'],
						'metric_type' => OJS_METRIC_TYPE_LEGACY_COUNTER,
						'metric' => $row[$countType],
						'file_type' => $fileType,
						'month' => $row['year'] . $month
					);
					$metricsDao->insertRecord($record);
				}
			}
			$result->MoveNext();
		}

		// Remove the plugin settings.
		$metricsDao->update('DELETE FROM plugin_settings WHERE plugin_name = ?', array('counterplugin'), false);

		return true;
	}

	/**
	 * For 2.4 upgrade: migrate Timed views statistics to the metrics table.
	 */
	function migrateTimedViewsUsageStatistics() {
		$metricsDao = DAORegistry::getDAO('MetricsDAO'); /* @var $metricsDao MetricsDAO */
		$result =& $metricsDao->retrieve('SELECT * FROM timed_views_log');
		if ($result->EOF) return true;

		$loadId = '3.0.0-upgrade-timedViews';
		$metricsDao->purgeLoadBatch($loadId);

		$plugin = PluginRegistry::getPlugin('generic', 'usagestatsplugin');
		$plugin->import('UsageStatsTemporaryRecordDAO');
		$tempStatsDao = new UsageStatsTemporaryRecordDAO();
		$tempStatsDao->deleteByLoadId($loadId);

		import('plugins.generic.usageStats.GeoLocationTool');
		$geoLocationTool = new GeoLocationTool();

		while(!$result->EOF) {
			$row = $result->GetRowAssoc(false);
			list($countryId, $cityName, $region) = $geoLocationTool->getGeoLocation($row['ip_address']);
			if ($row['galley_id']) {
				$assocType = ASSOC_TYPE_GALLEY;
				$assocId = $row['galley_id'];
			} else {
				$assocType = ASSOC_TYPE_SUBMISSION;
				$assocId = $row['submission_id'];
			};

			$day = date('Ymd', strtotime($row['date']));
			$tempStatsDao->insert($assocType, $assocId, $day, $countryId, $region, $cityName, null, $loadId);
			$result->MoveNext();
		}

		// Articles.
		$params = array(OJS_METRIC_TYPE_TIMED_VIEWS, $loadId, ASSOC_TYPE_SUBMISSION);
		$tempStatsDao->update(
					'INSERT INTO metrics (load_id, metric_type, assoc_type, assoc_id, day, country_id, region, city, submission_id, metric, context_id, issue_id)
					SELECT tr.load_id, ?, tr.assoc_type, tr.assoc_id, tr.day, tr.country_id, tr.region, tr.city, tr.assoc_id, count(tr.metric), a.context_id, pa.issue_id
					FROM usage_stats_temporary_records AS tr
					LEFT JOIN submissions AS a ON a.submission_id = tr.assoc_id
					LEFT JOIN published_submissions AS pa ON pa.submission_id = tr.assoc_id
					WHERE tr.load_id = ? AND tr.assoc_type = ? AND a.context_id IS NOT NULL AND pa.issue_id IS NOT NULL
					GROUP BY tr.assoc_type, tr.assoc_id, tr.day, tr.country_id, tr.region, tr.city, tr.file_type, tr.load_id', $params
		);

		// Galleys.
		$params = array(OJS_METRIC_TYPE_TIMED_VIEWS, $loadId, ASSOC_TYPE_GALLEY);
		$tempStatsDao->update(
					'INSERT INTO metrics (load_id, metric_type, assoc_type, assoc_id, day, country_id, region, city, submission_id, metric, context_id, issue_id)
					SELECT tr.load_id, ?, tr.assoc_type, tr.assoc_id, tr.day, tr.country_id, tr.region, tr.city, ag.submission_id, count(tr.metric), a.context_id, pa.issue_id
					FROM usage_stats_temporary_records AS tr
					LEFT JOIN submission_galleys AS ag ON ag.galley_id = tr.assoc_id
					LEFT JOIN submissions AS a ON a.submission_id = ag.submission_id
					LEFT JOIN published_submissions AS pa ON pa.submission_id = ag.submission_id
					WHERE tr.load_id = ? AND tr.assoc_type = ? AND a.context_id IS NOT NULL AND pa.issue_id IS NOT NULL
					GROUP BY tr.assoc_type, tr.assoc_id, tr.day, tr.country_id, tr.region, tr.city, tr.file_type, tr.load_id', $params
		);

		$tempStatsDao->deleteByLoadId($loadId);

		// Remove the plugin settings.
		$metricsDao->update('DELETE FROM plugin_settings WHERE plugin_name = ?', array('timedviewplugin'), false);

		return true;
	}

	/**
	 * For 2.4 upgrade: migrate OJS default statistics to the metrics table.
	 */
	function migrateDefaultUsageStatistics() {
		$loadId = '3.0.0-upgrade-ojsViews';
		$metricsDao = DAORegistry::getDAO('MetricsDAO');
		$insertIntoClause = 'INSERT INTO metrics (file_type, load_id, metric_type, assoc_type, assoc_id, submission_id, metric, context_id, issue_id)';
		$selectClause = null; // Conditionally set later

		// Galleys.
		$galleyUpdateCases = array(
			array('fileType' => STATISTICS_FILE_TYPE_PDF, 'isHtml' => false, 'assocType' => ASSOC_TYPE_GALLEY),
			array('fileType' => STATISTICS_FILE_TYPE_HTML, 'isHtml' => true, 'assocType' => ASSOC_TYPE_GALLEY),
			array('fileType' => STATISTICS_FILE_TYPE_OTHER, 'isHtml' => false, 'assocType' => ASSOC_TYPE_GALLEY),
			array('fileType' => STATISTICS_FILE_TYPE_PDF, 'assocType' => ASSOC_TYPE_ISSUE_GALLEY),
			array('fileType' => STATISTICS_FILE_TYPE_OTHER, 'assocType' => ASSOC_TYPE_ISSUE_GALLEY),
		);

		foreach ($galleyUpdateCases as $case) {
			$skipQuery = false;
			if ($case['fileType'] == STATISTICS_FILE_TYPE_PDF) {
				$pdfFileTypeWhereCheck = 'IN';
			} else {
				$pdfFileTypeWhereCheck = 'NOT IN';
			}

			$params = array($case['fileType'], $loadId, OJS_METRIC_TYPE_LEGACY_DEFAULT, $case['assocType']);

			if ($case['assocType'] == ASSOC_TYPE_GALLEY) {
				array_push($params, (int) $case['isHtml']);
				$selectClause = ' SELECT ?, ?, ?, ?, ag.galley_id, ag.article_id, ag.views, a.context_id, pa.issue_id
					FROM article_galleys_stats_migration as ag
					LEFT JOIN submissions AS a ON ag.article_id = a.submission_id
					LEFT JOIN published_submissions as pa on ag.article_id = pa.submission_id
					LEFT JOIN submission_files as af on ag.file_id = af.file_id
					WHERE a.submission_id is not null AND ag.views > 0 AND ag.html_galley = ?
						AND af.file_type ';
			} else {
				if ($this->tableExists('issue_galleys_stats_migration')) {
					$selectClause = 'SELECT ?, ?, ?, ?, ig.galley_id, 0, ig.views, i.journal_id, ig.issue_id
						FROM issue_galleys_stats_migration AS ig
						LEFT JOIN issues AS i ON ig.issue_id = i.issue_id
						LEFT JOIN issue_files AS ifi ON ig.file_id = ifi.file_id
						WHERE ig.views > 0 AND i.issue_id is not null AND ifi.file_type ';
				} else {
					// Upgrading from a version that
					// didn't support issue galleys. Skip.
					$skipQuery = true;
				}
			}

			array_push($params, 'application/pdf', 'application/x-pdf', 'text/pdf', 'text/x-pdf');

			if (!$skipQuery) {
				$metricsDao->update($insertIntoClause . $selectClause . $pdfFileTypeWhereCheck . ' (?, ?, ?, ?)', $params, false);
			}
		}

		// Published articles.
		$params = array(null, $loadId, OJS_METRIC_TYPE_LEGACY_DEFAULT, ASSOC_TYPE_SUBMISSION);
		$metricsDao->update($insertIntoClause .
			' SELECT ?, ?, ?, ?, pa.article_id, pa.article_id, pa.views, i.journal_id, pa.issue_id
			FROM published_articles_stats_migration as pa
			LEFT JOIN issues AS i ON pa.issue_id = i.issue_id
			WHERE pa.views > 0 AND i.issue_id is not null;', $params, false);

		// Set the site default metric type.
		$siteSettingsDao = DAORegistry::getDAO('SiteSettingsDAO'); /* @var $siteSettingsDao SiteSettingsDAO */
		$siteSettingsDao->updateSetting('defaultMetricType', OJS_METRIC_TYPE_COUNTER);

		return true;
	}

	/**
	 * Synchronize the ASSOC_TYPE_SERIES constant to ASSOC_TYPE_SECTION defined in PKPApplication.
	 * @return boolean
	 */
	function syncSeriesAssocType() {
		// Can be any DAO.
		$dao =& DAORegistry::getDAO('UserDAO'); /* @var $dao DAO */
		$tablesToUpdate = array(
			'announcements',
			'announcements_types',
			'user_settings',
			'notification',
			'email_templates',
			'email_templates_data',
			'controlled_vocabs',
			'gifts',
			'event_log',
			'email_log',
			'metadata_descriptions',
			'metrics',
			'notes',
			'item_views',
			'data_object_tombstone_oai_set_objects');

		foreach ($tablesToUpdate as $tableName) {
			if ($this->tableExists($tableName)) {
				$dao->update('UPDATE ' . $tableName . ' SET assoc_type = ' . ASSOC_TYPE_SECTION . ' WHERE assoc_type = ' . "'526'");
			}
		}

		return true;
	}

	/**
	 * Modernize review form storage from OJS 2.x
	 * @return boolean
	 */
	function fixReviewForms() {
		// 1. Review form possible options were stored with 'order'
		//    and 'content' attributes. Just store by content.
		$reviewFormDao = DAORegistry::getDAO('ReviewFormDAO');
		$result = $reviewFormDao->retrieve(
			'SELECT * FROM review_form_element_settings WHERE setting_name = ?',
			'possibleResponses'
		);
		while (!$result->EOF) {
			$row = $result->GetRowAssoc(false);
			$options = unserialize($row['setting_value']);
			$newOptions = array();
			foreach ($options as $key => $option) {
				$newOptions[$key] = $option['content'];
			}
			$row['setting_value'] = serialize($newOptions);
			$reviewFormDao->Replace('review_form_element_settings', $row, array('review_form_id', 'locale', 'setting_name'));
			$result->MoveNext();
		}
		$result->Close();

		// 2. Responses were stored with indexes offset by 1. Fix.
		import('lib.pkp.classes.reviewForm.ReviewFormElement'); // Constants
		$result = $reviewFormDao->retrieve(
			'SELECT	rfe.element_type AS element_type,
				rfr.response_value AS response_value,
				rfr.review_id AS review_id,
				rfe.review_form_element_id AS review_form_element_id
			FROM	review_form_responses rfr
				JOIN review_form_elements rfe ON (rfe.review_form_element_id = rfr.review_form_element_id)
			WHERE	rfe.element_type IN (?, ?, ?)',
			array(
				REVIEW_FORM_ELEMENT_TYPE_CHECKBOXES,
				REVIEW_FORM_ELEMENT_TYPE_RADIO_BUTTONS,
				REVIEW_FORM_ELEMENT_TYPE_DROP_DOWN_BOX
			)
		);
		while (!$result->EOF) {
			$row = $result->GetRowAssoc(false);
			$value = $row['response_value'];
			switch ($row['element_type']) {
				case REVIEW_FORM_ELEMENT_TYPE_CHECKBOXES:
					// Stored as a serialized object.
					$oldValue = unserialize($value);
					$value = array();
					foreach ($oldValue as $k => $v) {
						$value[$k] = $v-1;
					}
					$value = serialize($value);
					break;
				case REVIEW_FORM_ELEMENT_TYPE_RADIO_BUTTONS:
				case REVIEW_FORM_ELEMENT_TYPE_DROP_DOWN_BOX:
					// Stored as a simple number.
					$value-=1;
					break;
			}
			$reviewFormDao->update(
				'UPDATE review_form_responses SET response_value = ? WHERE review_id = ? AND review_form_element_id = ?',
				array($value, $row['review_id'], $row['review_form_element_id'])
			);
			$result->MoveNext();
		}
		$result->Close();

		return true;
	}

	/**
	 * Convert email templates to HTML.
	 * @return boolean True indicates success.
	 */
	function htmlifyEmailTemplates() {
		$emailTemplateDao = DAORegistry::getDAO('EmailTemplateDAO');

		// Convert the email templates in email_templates_data to localized
		$result = $emailTemplateDao->retrieve('SELECT * FROM email_templates_data');
		while (!$result->EOF) {
			$row = $result->getRowAssoc(false);
			$emailTemplateDao->update(
				'UPDATE	email_templates_data
				SET	body = ?
				WHERE	email_key = ? AND
					locale = ? AND
					assoc_type = ? AND
					assoc_id = ?',
				array(
					preg_replace('/{\$[a-zA-Z]+Url}/', '<a href="\0">\0</a>', nl2br($row['body'])),
					$row['email_key'],
					$row['locale'],
					$row['assoc_type'],
					$row['assoc_id']
				)
			);
			$result->MoveNext();
		}
		$result->Close();

		// Convert the email templates in email_templates_default_data to localized
		$result = $emailTemplateDao->retrieve('SELECT * FROM email_templates_default_data');
		while (!$result->EOF) {
			$row = $result->getRowAssoc(false);
			$emailTemplateDao->update(
				'UPDATE	email_templates_default_data
				SET	body = ?
				WHERE	email_key = ? AND
					locale = ?',
				array(
					preg_replace('/{\$[a-zA-Z]+Url}/', '<a href="\0">\0</a>', nl2br($row['body'])),
					$row['email_key'],
					$row['locale'],
				)
			);
			$result->MoveNext();
		}
		$result->Close();

		// Localize the email header and footer fields.
		$contextDao = DAORegistry::getDAO('JournalDAO');
		$settingsDao = DAORegistry::getDAO('JournalSettingsDAO');
		$contexts = $contextDao->getAll();
		while ($context = $contexts->next()) {
			foreach (array('emailFooter', 'emailSignature') as $settingName) {
				$settingsDao->updateSetting(
					$context->getId(),
					$settingName,
					$context->getSetting('emailHeader'),
					'string'
				);
			}
		}

		return true;
	}
}

?>
