<?php

/**
 * @file classes/install/Upgrade.inc.php
 *
 * Copyright (c) 2014-2019 Simon Fraser University
 * Copyright (c) 2003-2019 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class Upgrade
 * @ingroup install
 *
 * @brief Perform system upgrade.
 */


import('lib.pkp.classes.install.Installer');

define('OJS2_ROLE_ID_EDITOR',	0x00000100);
define('OJS2_ROLE_ID_SECTION_EDITOR',	0x00000200);
define('OJS2_ROLE_ID_LAYOUT_EDITOR',	0x00000300);
define('OJS2_ROLE_ID_COPYEDITOR', 0x00002000);
define('OJS2_ROLE_ID_PROOFREADER', 0x00003000);

class Upgrade extends Installer {
	/**
	 * Constructor.
	 * @param $params array upgrade parameters
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
		$articleSearchIndex = Application::getSubmissionSearchIndex();
		$articleSearchIndex->rebuildIndex();
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
	 * For 3.0.0 upgrade: Remove the review round and the review file if editor is not assigned.
	 * @return boolean
	 */
	function removeReviewEntries() {
		import('lib.pkp.classes.file.SubmissionFileManager');

		$submissionDao = DAORegistry::getDAO('SubmissionDAO');
		// Get review file IDs to be removed (from articles that have no editor assigned)
		$reviewFileResult = $submissionDao->retrieve('SELECT article_id, journal_id, review_file_id FROM articles WHERE article_id NOT IN (SELECT article_id FROM edit_assignments)');
		while (!$reviewFileResult->EOF) {
			$row = $reviewFileResult->GetRowAssoc(false);
			$articleId = (int)$row['article_id'];
			$journalId = (int)$row['journal_id'];
			$fileId = (int)$row['review_file_id'];

			// Delete the files in the files_dir:
			$submissionFileManager = new SubmissionFileManager($journalId, $articleId);
			$basePath = $submissionFileManager->getBasePath() . '/';
			// Get all file revisions
			$fileResult = $submissionDao->retrieve('SELECT file_id, revision, file_name FROM article_files WHERE file_id = ?', array($fileId));
			while (!$fileResult->EOF) {
				$fileRow = $fileResult->GetRowAssoc(false);
				$globPattern = $fileRow['file_name'];
				// Search for the file name in the appropriate journal and article folder of the files_dir
				$pattern1 = glob($basePath . '*/*/' . $globPattern);
				$pattern2 = glob($basePath . '*/' . $globPattern);
				if (!is_array($pattern1)) $pattern1 = array();
				if (!is_array($pattern2)) $pattern2 = array();
				$matchedResults = array_merge($pattern1, $pattern2);
				if (count($matchedResults)>1) {
					// Too many filenames matched. Continue with the first; this is just a warning.
					error_log("WARNING: Duplicate potential files for \"$globPattern\" in \"" . $submissionFileManager->getBasePath() . "\". Taking the first.");
				} elseif (count($matchedResults)==0) {
					// No filenames matched. Skip migrating.
					error_log("WARNING: Unable to find a match for \"$globPattern\" in \"" . $submissionFileManager->getBasePath() . "\". Skipping this file.");
					$fileResult->MoveNext();
					continue;
				}
				$discoveredFilename = array_shift($matchedResults);
				// If the file exists, delete it
				if (file_exists($discoveredFilename)) {
					unlink($discoveredFilename);
				} else {
					error_log("WARNING: File \"$discoveredFilename\" does not exist.");
					$fileResult->MoveNext();
					continue;
				}
				$fileResult->MoveNext();
			}
			$fileResult->Close();

			// Delete the file entries in the DB
			$submissionDao->update('DELETE FROM article_files WHERE file_id = ?', array($fileId));
			// Set review_file_id to NULL
			$submissionDao->update('UPDATE articles SET review_file_id=NULL WHERE review_file_id = ?', array($fileId));
			// Delete the review round for that article
			$submissionDao->update('DELETE FROM review_rounds WHERE submission_id = ?', array($articleId));

			$reviewFileResult->MoveNext();
		}
		$reviewFileResult->Close();
		return true;
	}

	/**
	 * For 3.0.0 upgrade: Convert string-field semi-colon separated metadata to controlled vocabularies.
	 * @return boolean
	 */
	function migrateArticleMetadata() {
		$journalDao = DAORegistry::getDAO('JournalDAO');
		$submissionDao = DAORegistry::getDAO('SubmissionDAO');

		// controlled vocabulary DAOs.
		$submissionSubjectDao = DAORegistry::getDAO('SubmissionSubjectDAO');
		$submissionKeywordDao = DAORegistry::getDAO('SubmissionKeywordDAO');
		$submissionDisciplineDao = DAORegistry::getDAO('SubmissionDisciplineDAO');
		$submissionAgencyDao = DAORegistry::getDAO('SubmissionAgencyDAO');
		$submissionLanguageDao = DAORegistry::getDAO('SubmissionLanguageDAO');
		$controlledVocabDao = DAORegistry::getDAO('ControlledVocabDAO');

		// check to see if there are any existing controlled vocabs for submissionAgency, submissionDiscipline, submissionSubject, or submissionLanguage.
		// IF there are, this implies that this code has run previously, so return.
		$vocabTestResult = $controlledVocabDao->retrieve('SELECT count(*) AS total FROM controlled_vocabs WHERE symbolic = \'submissionAgency\' OR symbolic = \'submissionDiscipline\' OR symbolic = \'submissionSubject\' OR symbolic = \'submissionKeyword\' OR symbolic = \'submissionLanguage\'');
		$testRow = $vocabTestResult->GetRowAssoc(false);
		if ($testRow['total'] > 0) return true;

		$journals = $journalDao->getAll();
		while ($journal = $journals->next()) {
			// for languages, we depend on the journal locale settings since languages are not localized.
			// Use Journal locales, or primary if no defined submission locales.
			$supportedLocales = $journal->getSupportedSubmissionLocales();

			if (empty($supportedLocales)) $supportedLocales = array($journal->getPrimaryLocale());
			else if (!is_array($supportedLocales)) $supportedLocales = array($supportedLocales);

			$result = $submissionDao->retrieve('SELECT a.submission_id FROM submissions a WHERE a.context_id = ?', array((int)$journal->getId()));
			while (!$result->EOF) {
				$row = $result->GetRowAssoc(false);
				$articleId = (int)$row['submission_id'];
				$settings = array();
				$settingResult = $submissionDao->retrieve('SELECT setting_value, setting_name, locale FROM submission_settings WHERE submission_id = ? AND setting_value <> \'\' AND (setting_name = \'discipline\' OR setting_name = \'subject\' OR setting_name = \'subjectClass\' OR setting_name = \'sponsor\')', array((int)$articleId));
				while (!$settingResult->EOF) {
					$settingRow = $settingResult->GetRowAssoc(false);
					$locale = $settingRow['locale'];
					$settingName = $settingRow['setting_name'];
					$settingValue = $settingRow['setting_value'];
					$settings[$settingName][$locale] = $settingValue;
					$settingResult->MoveNext();
				}
				$settingResult->Close();

				$languageResult = $submissionDao->retrieve('SELECT language FROM submissions WHERE submission_id = ?', array((int)$articleId));
				$languageRow = $languageResult->getRowAssoc(false);
				// language is NOT localized originally.
				$language = $languageRow['language'];
				$languageResult->Close();
				// test for locales for each field since locales may have been modified since
				// the article was last edited.

				$disciplines = $subjects = $keywords = $agencies = array();

				if (array_key_exists('discipline', $settings)) {
					$disciplineLocales = array_keys($settings['discipline']);
					if (is_array($disciplineLocales)) {
						foreach ($disciplineLocales as &$locale) {
							$disciplines[$locale] = preg_split('/[,;:]/', $settings['discipline'][$locale]);
							$disciplines[$locale] = array_map('trim', $disciplines[$locale]);
						}
						$submissionDisciplineDao->insertDisciplines($disciplines, $articleId, false);
					}
					unset($disciplineLocales);
					unset($disciplines);
				}

				if (array_key_exists('subjectClass', $settings)) {
					$subjectLocales = array_keys($settings['subjectClass']);
					if (is_array($subjectLocales)) {
						foreach ($subjectLocales as &$locale) {
							$subjects[$locale] = preg_split('/[,;:]/', $settings['subjectClass'][$locale]);
							$subjects[$locale] = array_map('trim', $subjects[$locale]);
						}
						$submissionSubjectDao->insertSubjects($subjects, $articleId, false);
					}
					unset($subjectLocales);
					unset($subjects);
				}

				if (array_key_exists('subject', $settings)) {
					$keywordLocales = array_keys($settings['subject']);
					if (is_array($keywordLocales)) {
						foreach ($keywordLocales as &$locale) {
							$keywords[$locale] = preg_split('/[,;:]/', $settings['subject'][$locale]);
							$keywords[$locale] = array_map('trim', $keywords[$locale]);
						}
						$submissionKeywordDao->insertKeywords($keywords, $articleId, false);
					}
					unset($keywordLocales);
					unset($keywords);
				}

				if (array_key_exists('sponsor', $settings)) {
					$sponsorLocales = array_keys($settings['sponsor']);
					if (is_array($sponsorLocales)) {
						foreach ($sponsorLocales as &$locale) {
							// note array name change.  Sponsor -> Agency
							$agencies[$locale] = preg_split('/[,;:]/', $settings['sponsor'][$locale]);
							$agencies[$locale] = array_map('trim', $agencies[$locale]);
						}
						$submissionAgencyDao->insertAgencies($agencies, $articleId, false);
					}
					unset($sponsorLocales);
					unset($agencies);
				}

				$languages = array();
				foreach ($supportedLocales as &$locale) {
					$languages[$locale] = preg_split('/\s+/', $language);
					$languages[$locale] = array_map('trim', $languages[$locale]);
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

		// delete old settings
		$submissionDao->update('DELETE FROM submission_settings WHERE setting_name = \'discipline\' OR setting_name = \'subject\' OR setting_name = \'subjectClass\' OR setting_name = \'sponsor\'');

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
		$userGroupDao->update('INSERT INTO user_groups (context_id, role_id, is_default) VALUES (?, ?, ?)', array(CONTEXT_SITE, ROLE_ID_SITE_ADMIN, 1));
		$userGroupId = $userGroupDao->getInsertId();

		$userResult = $userGroupDao->retrieve('SELECT user_id FROM roles WHERE journal_id = ? AND role_id = ?', array(CONTEXT_SITE, ROLE_ID_SITE_ADMIN));
		while (!$userResult->EOF) {
			$row = $userResult->GetRowAssoc(false);
			$userGroupDao->update('INSERT INTO user_user_groups (user_group_id, user_id) VALUES (?, ?)', array($userGroupId, (int) $row['user_id']));
			$userResult->MoveNext();
		}

		// iterate through all journals and assign remaining users to their respective groups.
		$journalDao = DAORegistry::getDAO('JournalDAO');
		$journals = $journalDao->getAll();

		AppLocale::requireComponents(LOCALE_COMPONENT_APP_DEFAULT, LOCALE_COMPONENT_PKP_DEFAULT);

		// fix stage id assignments for reviews.  OJS hard coded *all* of these to '1' initially. Consider OJS reviews as external reviews.
		$userGroupDao->update('UPDATE review_assignments SET stage_id = ?', array(WORKFLOW_STAGE_ID_EXTERNAL_REVIEW));

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
			$authorGroup = $userGroupDao->getDefaultByRoleId($journal->getId(), ROLE_ID_AUTHOR);
			$userResult = $journalDao->retrieve('SELECT user_id FROM roles WHERE journal_id = ? AND role_id = ?', array((int) $journal->getId(), ROLE_ID_AUTHOR));
			while (!$userResult->EOF) {
				$row = $userResult->GetRowAssoc(false);
				$userGroupDao->assignUserToGroup($row['user_id'], $authorGroup->getId());
				$userResult->MoveNext();
			}

			// Reviewers.  All existing OJS reviewers get mapped to external reviewers.
			// There should only be one user group with ROLE_ID_REVIEWER in the external review stage.
			$userGroups = $userGroupDao->getUserGroupsByStage($journal->getId(), WORKFLOW_STAGE_ID_EXTERNAL_REVIEW, ROLE_ID_REVIEWER);
			$reviewerUserGroup = null; // keep this in scope for later.

			while ($group = $userGroups->next()) {
				$reviewerUserGroup = $group;

				$userResult = $journalDao->retrieve('SELECT user_id FROM roles WHERE journal_id = ? AND role_id = ?', array((int) $journal->getId(), ROLE_ID_REVIEWER));
				while (!$userResult->EOF) {
					$row = $userResult->GetRowAssoc(false);
					$userGroupDao->assignUserToGroup($row['user_id'], $group->getId());
					$userResult->MoveNext();
				}
			}

			// regular Editors.  NOTE:  this involves a role id change from 0x100 to 0x10 (old OJS _EDITOR to PKP-lib _MANAGER).
			$userGroups = $userGroupDao->getByRoleId($journal->getId(), ROLE_ID_MANAGER);
			$editorUserGroup = null;
			while ($group = $userGroups->next()) {
				if ($group->getData('nameLocaleKey') == 'default.groups.name.editor') {
					$editorUserGroup = $group; // stash for later.
					$userResult = $journalDao->retrieve('SELECT user_id FROM roles WHERE journal_id = ? AND role_id = ?', array((int) $journal->getId(), OJS2_ROLE_ID_EDITOR));
					while (!$userResult->EOF) {
						$row = $userResult->GetRowAssoc(false);
						$userGroupDao->assignUserToGroup($row['user_id'], $group->getId());
						$userResult->MoveNext();
					}
				}
			}

			// Section Editors.
			$sectionEditorGroup = $userGroupDao->getDefaultByRoleId($journal->getId(), ROLE_ID_SUB_EDITOR);
			$userResult = $journalDao->retrieve('SELECT user_id FROM roles WHERE journal_id = ? AND role_id = ?', array((int) $journal->getId(), OJS2_ROLE_ID_SECTION_EDITOR));
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
					$userResult = $journalDao->retrieve('SELECT user_id FROM roles WHERE journal_id = ? AND role_id = ?', array((int) $journal->getId(), OJS2_ROLE_ID_LAYOUT_EDITOR));
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
					$userResult = $journalDao->retrieve('SELECT user_id FROM roles WHERE journal_id = ? AND role_id = ?', array((int) $journal->getId(), OJS2_ROLE_ID_COPYEDITOR));
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
					$userResult = $journalDao->retrieve('SELECT user_id FROM roles WHERE journal_id = ? AND role_id = ?', array((int) $journal->getId(), OJS2_ROLE_ID_PROOFREADER));
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
			$submissionResult = $submissionDao->retrieve('SELECT article_id, user_id FROM articles_migration WHERE journal_id = ?', array($journal->getId()));
			$authorGroup = $userGroupDao->getDefaultByRoleId($journal->getId(), ROLE_ID_AUTHOR);
			while (!$submissionResult->EOF) {
				$submissionRow = $submissionResult->GetRowAssoc(false);
				$submissionId = $submissionRow['article_id'];
				$submissionUserId = $submissionRow['user_id'];
				unset($submissionRow);

				// Authors get access to all stages.
				$stageAssignmentDao->build($submissionId, $authorGroup->getId(), $submissionUserId);


				// update the user_group_id column in the authors table.
				$userGroupDao->update('UPDATE authors SET user_group_id = ? WHERE submission_id = ?', array((int) $authorGroup->getId(), $submissionId));

				// Journal Editors
				// First, full editors.
				$editorsResult = $stageAssignmentDao->retrieve('SELECT e.* FROM submissions s, edit_assignments e, users u, roles r WHERE r.user_id = e.editor_id AND r.role_id = ' .
							OJS2_ROLE_ID_EDITOR . ' AND e.article_id = ? AND r.journal_id = s.context_id AND s.submission_id = e.article_id AND e.editor_id = u.user_id', array($submissionId));
				while (!$editorsResult->EOF) {
					$editorRow = $editorsResult->GetRowAssoc(false);
					$stageAssignmentDao->build($submissionId, $editorUserGroup->getId(), $editorRow['editor_id']);
					$editorsResult->MoveNext();
				}
				unset($editorsResult);

				// Section Editors.
				$editorsResult = $stageAssignmentDao->retrieve('SELECT e.* FROM submissions s LEFT JOIN edit_assignments e ON (s.submission_id = e.article_id) LEFT JOIN users u ON (e.editor_id = u.user_id)
							LEFT JOIN roles r ON (r.user_id = e.editor_id AND r.role_id = ' . OJS2_ROLE_ID_EDITOR . ' AND r.journal_id = s.context_id) WHERE e.article_id = ? AND s.submission_id = e.article_id
							AND r.role_id IS NULL', array($submissionId));
				while (!$editorsResult->EOF) {
					$editorRow = $editorsResult->GetRowAssoc(false);
					$stageAssignmentDao->build($submissionId, $sectionEditorGroup->getId(), $editorRow['editor_id']);
					$editorsResult->MoveNext();
				}
				unset($editorsResult);

				// Copyeditors.  Pull from the signoffs for SIGNOFF_COPYEDITING_INITIAL.
				// there should only be one (or no) copyeditor for each submission.

				$copyEditorResult = $stageAssignmentDao->retrieve('SELECT user_id FROM signoffs WHERE assoc_type = ? AND assoc_id = ? AND symbolic = ?',
								array(ASSOC_TYPE_SUBMISSION, $submissionId, 'SIGNOFF_COPYEDITING_INITIAL'));

				if ($copyEditorResult->NumRows() == 1) { // the signoff exists.
					$copyEditorRow = $copyEditorResult->GetRowAssoc(false);
					$copyEditorId = (int) $copyEditorRow['user_id'];
					if ($copyEditorId > 0) { // there is a user assigned.
						$stageAssignmentDao->build($submissionId, $copyEditorGroup->getId(), $copyEditorId);
					}
				}

				// Layout editors.  Pull from the signoffs for SIGNOFF_LAYOUT.
				// there should only be one (or no) layout editor for each submission.

				$layoutEditorResult = $stageAssignmentDao->retrieve('SELECT user_id FROM signoffs WHERE assoc_type = ? AND assoc_id = ? AND symbolic = ?',
						array(ASSOC_TYPE_SUBMISSION, $submissionId, 'SIGNOFF_LAYOUT'));

				if ($layoutEditorResult->NumRows() == 1) { // the signoff exists.
					$layoutEditorRow = $layoutEditorResult->GetRowAssoc(false);
					$layoutEditorId = (int) $layoutEditorRow['user_id'];
					if ($layoutEditorId > 0) { // there is a user assigned.
						$stageAssignmentDao->build($submissionId, $layoutEditorGroup->getId(), $layoutEditorId);
					}
				}

				// Proofreaders.  Pull from the signoffs for SIGNOFF_PROOFREADING_PROOFREADER.
				// there should only be one (or no) layout editor for each submission.

				$proofreaderResult = $stageAssignmentDao->retrieve('SELECT user_id FROM signoffs WHERE assoc_type = ? AND assoc_id = ? AND symbolic = ?',
						array(ASSOC_TYPE_SUBMISSION, $submissionId, 'SIGNOFF_PROOFREADING_PROOFREADER'));

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
	 * For 2.4 upgrade: migrate COUNTER statistics to the metrics table.
	 */
	function migrateCounterPluginUsageStatistics() {
		$metricsDao = DAORegistry::getDAO('MetricsDAO'); /* @var $metricsDao MetricsDAO */
		$result = $metricsDao->retrieve('SELECT * FROM counter_monthly_log');
		if ($result->EOF) return true;

		$loadId = '3.0.0-upgrade-counter';
		$metricsDao->purgeLoadBatch($loadId);

		$fileTypeCounts = array(
			'count_html' => STATISTICS_FILE_TYPE_HTML,
			'count_pdf' => STATISTICS_FILE_TYPE_PDF,
			'count_other' => STATISTICS_FILE_TYPE_OTHER
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
						'metric_type' => 'ojs::legacyCounterPlugin',
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

		import('lib.pkp.plugins.generic.usageStats.GeoLocationTool');
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
			$tempStatsDao->insert($assocType, $assocId, $day, strtotime($row['date']), $countryId, $region, $cityName, null, $loadId);
			$result->MoveNext();
		}

		// Articles.
		$params = array('ojs::timedViews', $loadId, ASSOC_TYPE_SUBMISSION);
		$tempStatsDao->update(
					'INSERT INTO metrics (load_id, metric_type, assoc_type, assoc_id, day, country_id, region, city, submission_id, metric, context_id, assoc_object_type, assoc_object_id)
					SELECT tr.load_id, ?, tr.assoc_type, tr.assoc_id, tr.day, tr.country_id, tr.region, tr.city, tr.assoc_id, count(tr.metric), a.context_id, ' . ASSOC_TYPE_ISSUE . ', pa.issue_id
					FROM usage_stats_temporary_records AS tr
					LEFT JOIN submissions AS a ON a.submission_id = tr.assoc_id
					LEFT JOIN published_submissions AS pa ON pa.submission_id = tr.assoc_id
					WHERE tr.load_id = ? AND tr.assoc_type = ? AND a.context_id IS NOT NULL AND pa.issue_id IS NOT NULL
					GROUP BY tr.assoc_type, tr.assoc_id, tr.day, tr.country_id, tr.region, tr.city, tr.file_type, tr.load_id', $params
		);

		// Galleys.
		$params = array('ojs::timedViews', $loadId, ASSOC_TYPE_GALLEY);
		$tempStatsDao->update(
					'INSERT INTO metrics (load_id, metric_type, assoc_type, assoc_id, day, country_id, region, city, submission_id, metric, context_id, assoc_object_type, assoc_object_id)
					SELECT tr.load_id, ?, tr.assoc_type, tr.assoc_id, tr.day, tr.country_id, tr.region, tr.city, ag.submission_id, count(tr.metric), a.context_id, ' . ASSOC_TYPE_ISSUE . ', pa.issue_id
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
		$insertIntoClause = 'INSERT INTO metrics (file_type, load_id, metric_type, assoc_type, assoc_id, submission_id, metric, context_id, assoc_object_type, assoc_object_id)';
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

			$params = array($case['fileType'], $loadId, 'ojs::legacyDefault', $case['assocType']);

			if ($case['assocType'] == ASSOC_TYPE_GALLEY) {
				array_push($params, (int) $case['isHtml']);
				$selectClause = ' SELECT ?, ?, ?, ?, ag.galley_id, ag.article_id, ag.views, a.context_id, ' . ASSOC_TYPE_ISSUE . ', pa.issue_id
					FROM article_galleys_stats_migration as ag
					LEFT JOIN submissions AS a ON ag.article_id = a.submission_id
					LEFT JOIN published_submissions as pa on ag.article_id = pa.submission_id
					LEFT JOIN submission_files as af on ag.file_id = af.file_id
					WHERE a.submission_id is not null AND ag.views > 0 AND ag.html_galley = ?
						AND af.file_type ';
			} else {
				if ($this->tableExists('issue_galleys_stats_migration')) {
					$selectClause = 'SELECT ?, ?, ?, ?, ig.galley_id, 0, ig.views, i.journal_id, ' . ASSOC_TYPE_ISSUE . ', ig.issue_id
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

		// Published submissions.
		$params = array(null, $loadId, 'ojs::legacyDefault', ASSOC_TYPE_SUBMISSION);
		$metricsDao->update($insertIntoClause .
			' SELECT ?, ?, ?, ?, pa.article_id, pa.article_id, pa.views, i.journal_id, ' . ASSOC_TYPE_ISSUE . ', pa.issue_id
			FROM published_articles_stats_migration as pa
			LEFT JOIN issues AS i ON pa.issue_id = i.issue_id
			WHERE pa.views > 0 AND i.issue_id is not null;', $params, false);

		// Set the site default metric type.
		$siteDao = DAORegistry::getDAO('SiteDAO');
		$site = $siteDao->getSite();
		$site->setData('defaultMetricType', METRIC_TYPE_COUNTER);
		$siteDao->updateObject($site);

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
			$reviewFormDao->Replace('review_form_element_settings', $row, array('review_form_element_id', 'locale', 'setting_name'));
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

		return true;
	}

	/**
	 * For 2.4.6 upgrade: to enable localization of a CustomBlock,
	 * the blockContent values are converted from string to array (key: primary_language)
	 */
	function localizeCustomBlockSettings() {
		$pluginSettingsDao = DAORegistry::getDAO('PluginSettingsDAO');
		$journalDao = DAORegistry::getDAO('JournalDAO');
		$journals = $journalDao->getAll();

		while ($journal = $journals->next()) {
			$journalId = $journal->getId();
			$primaryLocale = $journal->getPrimaryLocale();

			$blocks = $pluginSettingsDao->getSetting($journalId, 'customblockmanagerplugin', 'blocks');
			if ($blocks) foreach ($blocks as $block) {
				$blockContent = $pluginSettingsDao->getSetting($journalId, $block, 'blockContent');

				if (!is_array($blockContent)) {
					$pluginSettingsDao->updateSetting($journalId, $block, 'blockContent', array($primaryLocale => $blockContent));
				}
			}
			unset($journal);
		}

		return true;
	}

	/**
	 * Migrate submission filenames from OJS 2.x
	 * @param $upgrade Upgrade
	 * @param $params array
	 * @return boolean
	 */
	function migrateFiles($upgrade, $params) {
		$journalDao = DAORegistry::getDAO('JournalDAO');
		$submissionDao = DAORegistry::getDAO('SubmissionDAO');
		$submissionFileDao = DAORegistry::getDAO('SubmissionFileDAO');
		DAORegistry::getDAO('GenreDAO'); // Load constants
		$siteDao = DAORegistry::getDAO('SiteDAO'); /* @var $siteDao SiteDAO */
		$site = $siteDao->getSite();
		$adminEmail = $site->getLocalizedContactEmail();

		// get file names form OJS 2.4.x table article_files i.e.
		// from the temporary table article_files_migration
		$ojs2FileNames = array();
		$result = $submissionFileDao->retrieve('SELECT file_id, revision, file_name FROM article_files_migration');
		while (!$result->EOF) {
			$row = $result->GetRowAssoc(false);
			$ojs2FileNames[$row['file_id']][$row['revision']] = $row['file_name'];
			$result->MoveNext();
		}
		$result->Close();

		import('lib.pkp.classes.file.SubmissionFileManager');

		$contexts = $journalDao->getAll();
		while ($context = $contexts->next()) {
			$submissions = $submissionDao->getByContextId($context->getId());
			while ($submission = $submissions->next()) {
				$submissionFileManager = new SubmissionFileManager($context->getId(), $submission->getId());
				$submissionFiles = $submissionFileDao->getBySubmissionId($submission->getId());
				foreach ($submissionFiles as $submissionFile) {
					$generatedFilename = $submissionFile->getServerFileName();
					$basePath = $submissionFileManager->getBasePath() . '/';
					$globPattern = $ojs2FileNames[$submissionFile->getFileId()][$submissionFile->getRevision()];

					$pattern1 = glob($basePath . '*/*/' . $globPattern);
					$pattern2 = glob($basePath . '*/' . $globPattern);
					if (!is_array($pattern1)) $pattern1 = array();
					if (!is_array($pattern2)) $pattern2 = array();
					$matchedResults = array_merge($pattern1, $pattern2);

					if (count($matchedResults)>1) {
						// Too many filenames matched. Continue with the first; this is just a warning.
						error_log("WARNING: Duplicate potential files for \"$globPattern\" in \"" . $submissionFileManager->getBasePath() . "\". Taking the first.");
					} elseif (count($matchedResults)==0) {
						// No filenames matched. Skip migrating.
						error_log("WARNING: Unable to find a match for \"$globPattern\" in \"" . $submissionFileManager->getBasePath() . "\". Skipping this file.");
						continue;
					}
					$discoveredFilename = array_shift($matchedResults);
					$targetFilename = $basePath . $submissionFile->_fileStageToPath($submissionFile->getFileStage()) . '/' . $generatedFilename;
					if (file_exists($targetFilename)) continue; // Skip existing files/links
					if (!file_exists($path = dirname($targetFilename)) && !$submissionFileManager->mkdirtree($path)) {
						error_log("Unable to make directory \"$path\"");
					}
					if (!rename($discoveredFilename, $targetFilename)) {
						error_log("Unable to move \"$discoveredFilename\" to \"$targetFilename\".");
					}
				}
			}
		}
		return true;
	}

	/**
	 * Set the missing uploader user id to a journal manager.
	 * @return boolean True indicates success.
	 */
	function setFileUploader() {
		$journalDao = DAORegistry::getDAO('JournalDAO');
		$userGroupDao = DAORegistry::getDAO('UserGroupDAO');
		$submissionFileDao = DAORegistry::getDAO('SubmissionFileDAO');
		$journalIterator = $journalDao->getAll();
		$driver = $submissionFileDao->getDriver();
		while ($journal = $journalIterator->next()) {
			$managerUserGroup = $userGroupDao->getDefaultByRoleId($journal->getId(), ROLE_ID_MANAGER);
			$managerUsers = $userGroupDao->getUsersById($managerUserGroup->getId(), $journal->getId());
			$creatorUserId = $managerUsers->next()->getId();
			switch ($driver) {
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
		$journalDao = DAORegistry::getDAO('JournalDAO');
		$submissionDao = DAORegistry::getDAO('SubmissionDAO');
		$submissionFileDao = DAORegistry::getDAO('SubmissionFileDAO');

		$contexts = $journalDao->getAll();
		while ($context = $contexts->next()) {
			$submissions = $submissionDao->getByContextId($context->getId());
			while ($submission = $submissions->next()) {
				$submissionFiles = $submissionFileDao->getBySubmissionId($submission->getId());
				foreach ($submissionFiles as $submissionFile) {
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
	 * Convert supplementary files to submission files.
	 * @return boolean True indicates success.
	 */
	function convertSupplementaryFiles() {
		$genreDao = DAORegistry::getDAO('GenreDAO');
		$journalDao = DAORegistry::getDAO('JournalDAO');
		$submissionDao = DAORegistry::getDAO('SubmissionDAO');
		$articleGalleyDao = DAORegistry::getDAO('ArticleGalleyDAO');
		$userGroupDao = DAORegistry::getDAO('UserGroupDAO');
		$journal = null;

		$submissionFileDao = DAORegistry::getDAO('SubmissionFileDAO');
		$suppFilesResult = $submissionFileDao->retrieve('SELECT a.context_id, sf.* FROM article_supplementary_files sf, submissions a WHERE a.submission_id = sf.article_id'); // COMMENT_TYPE_EDITOR_DECISION
		while (!$suppFilesResult->EOF) {
			$row = $suppFilesResult->getRowAssoc(false);
			$suppFilesResult->MoveNext();
			if (!$journal || $journal->getId() != $row['context_id']) {
				$journal = $journalDao->getById($row['context_id']);
				$managerUserGroup = $userGroupDao->getDefaultByRoleId($journal->getId(), ROLE_ID_MANAGER);
				$managerUsers = $userGroupDao->getUsersById($managerUserGroup->getId(), $journal->getId());
				$creatorUserId = $managerUsers->next()->getId();
			}
			$article = $submissionDao->getById($row['article_id']);
			if (!$article) {
				error_log('WARNING: Unable to fetch article for article_supplementary_files.supp_id = ' . $row['supp_id'] . '. Skipping.');
				continue;
			}

			// if it is a remote supp file and article is published, convert it to a remote galley
			if (!$row['file_id'] && $row['remote_url'] != '' && $article->getStatus() == STATUS_PUBLISHED) {
				$remoteSuppFileSettingsResult = $submissionFileDao->retrieve('SELECT * FROM article_supp_file_settings WHERE supp_id = ? AND setting_value IS NOT NULL', array($row['supp_id']));
				$extraRemoteGalleySettings = $remoteSuppFileTitle = array();
				while (!$remoteSuppFileSettingsResult->EOF) {
					$rsfRow = $remoteSuppFileSettingsResult->getRowAssoc(false);
					$remoteSuppFileSettingsResult->MoveNext();
					switch ($rsfRow['setting_name']) {
						case 'title':
							$remoteSuppFileTitle[$rsfRow['locale']] = $rsfRow['setting_value'];
							break;
						case 'pub-id::doi':
						case 'pub-id::other::urn':
						case 'pub-id::publisher-id':
						case 'urnSuffix':
						case 'doiSuffix':
						case 'datacite::registeredDoi':
							$extraRemoteGalleySettings[$rsfRow['setting_name']] = $rsfRow['setting_value'];
							break;
						default:
							// other settings are not relevant for remote galleys
							break;
					}
				}
				$remoteSuppFileSettingsResult->Close();

				$articleGalley = $articleGalleyDao->newDataObject();
				$articleGalley->setSubmissionId($article->getId());
				$articleGalley->setLabel($remoteSuppFileTitle[$article->getLocale()]);
				$articleGalley->setRemoteURL($row['remote_url']);
				$articleGalley->setLocale($article->getLocale());
				$articleGalleyDao->insertObject($articleGalley);

				// Preserve extra settings. (Plugins may not be loaded, so other mechanisms might not work.)
				foreach ($extraRemoteGalleySettings as $name => $value) {
					$submissionFileDao->update(
						'INSERT INTO submission_galley_settings (galley_id, setting_name, setting_value, setting_type) VALUES (?, ?, ?, ?)',
						array(
							$articleGalley->getId(),
							$name,
							$value,
							'string'
						)
					);
				}

				// continue with the next supp file
				continue;
			}

			$genre = null;
			switch ($row['type']) {
				// author.submit.suppFile.dataAnalysis
				case 'Análise de Dados':
				case 'Análises de dados':
				case 'Anàlisi de les dades':
				case 'Analisi di dati':
				case 'Analisis Data':
				case 'Análisis de datos':
				case 'Análisis de los datos':
				case 'Analiza podataka':
				case 'Analize de date':
				case 'Analizy':
				case 'Analyse de données':
				case 'Analyse':
				case 'Analys':
				case 'Analýza dat':
				case 'Dataanalyse':
				case 'Data Analysis':
				case 'Datenanalyse':
				case 'Datu-analisia':
				case 'Gegevensanalyse':
				case 'Phân tích dữ liệu':
				case 'Veri Analizi':
				case 'آنالیز داده':
				case 'تحليل بيانات':
				case 'Ανάλυση δεδομένων':
				case 'Анализа на податоци':
				case 'Анализ данных':
				case 'Аналіз даних':
				case 'ഡേറ്റാ വിശകലനം':
				case 'データ分析':
				case '数据分析':
				case '資料分析':
					$genre = $genreDao->getByKey('DATAANALYSIS', $journal->getId());
					break;
				// author.submit.suppFile.dataSet
				case 'Baza podataka':
				case 'Conjunt de dades':
				case 'Conjunto de Dados':
				case 'Conjunto de datos':
				case 'Conjuntos de datos':
				case 'Conxuntos de dados':
				case 'Datasæt':
				case 'Data Set':
				case 'Dataset':
				case 'Datasett':
				case 'Datensatz':
				case 'Datový soubor':
				case 'Datu multzoa':
				case 'Ensemble de données':
				case 'Forskningsdata':
				case 'データセット':
				case 'Set Data':
				case 'Set di dati':
				case 'Set podataka':
				case 'Seturi de date':
				case 'Tập hợp dữ liệu':
				case 'Veri Seti':
				case 'Zbiory danych':
				case 'مجموعة بيانات':
				case 'مجموعه ناده':
				case 'Σύνολο δεδομένων':
				case 'Збирка на податоци':
				case 'Набір даних':
				case 'Набор данных':
				case 'ഡേറ്റാ സെറ്റ്':
				case '数据集':
				case '資料或數據組':
					$genre = $genreDao->getByKey('DATASET', $journal->getId());
					break;
				// author.submit.suppFile.researchInstrument
				case 'Araştırma Enstürmanları':
				case 'Công cụ nghiên cứu':
				case 'Forschungsinstrument':
				case 'Forskningsinstrument':
				case 'Herramienta de investigación':
				case 'Ikerketa-tresna':
				case 'Instrumen Riset':
				case 'Instrument de cercetare':
				case 'Instrument de recerca':
				case 'Instrument de recherche':
				case 'Instrumenti istraživanja':
				case 'Instrumento de investigación':
				case 'Instrumento de Pesquisa':
				case 'Istraživački instrument':
				case 'Narzędzie badawcze':
				case 'Onderzoeksinstrument':
				case 'Research Instrument':
				case 'Strumento di ricerca':
				case 'Výzkumný nástroj':
				case 'ابزار پژوهشی':
				case 'أداة بحث':
				case 'Όργανο έρευνας':
				case 'Дослідний інструмент':
				case 'Инструмент исследования':
				case 'Истражувачки инструмент':
				case 'ഗവേഷണ ഉപകരണങ്ങള്‍':
				case '研究方法或工具':
				case '研究装置':
				case '科研仪器':
					$genre = $genreDao->getByKey('RESEARCHINSTRUMENT', $journal->getId());
					break;
				// author.submit.suppFile.researchMaterials
				case 'Araştırma Materyalleri':
				case 'Các tài liệu nghiên cứu':
				case 'Documents de recherche':
				case 'Forschungsmaterial':
				case 'Forskningsmateriale':
				case 'Forskningsmaterialer':
				case 'Forskningsmaterial':
				case 'Ikerketako materialak':
				case 'Istraživački materijali':
				case 'Istraživački materijal':
				case 'Materiais de investigación':
				case 'Material de Pesquisa':
				case 'Materiale de cercetare':
				case 'Materiales de investigación':
				case 'Materiali di ricerca':
				case 'Materials de recerca':
				case 'Materiały badawcze':
				case 'Materi/ Bahan Riset':
				case 'Onderzoeksmaterialen':
				case 'Research Materials':
				case 'Výzkumné materiály':
				case 'مواد بحث':
				case 'مواد پژوهشی':
				case 'Υλικά έρευνας':
				case 'Дослідні матеріали':
				case 'Истражувачки материјали':
				case 'Материалы исследования':
				case 'ഗവേഷണ സാമഗ്രികള്‍':
				case '研究材料':
				case '科研资料':
					$genre = $genreDao->getByKey('RESEARCHMATERIALS', $journal->getId());
					break;
				// author.submit.suppFile.researchResults
				case 'Araştırma Sonuçları':
				case 'Forschungsergebnisse':
				case 'Forskningsresultater':
				case 'Forskningsresultat':
				case 'Hasil Riset':
				case 'Ikerketaren emaitza':
				case 'Istraživački rezultati':
				case 'Kết quả nghiên cứu':
				case 'Onderzoeksresultaten':
				case 'Research Results':
				case 'Resultados de investigación':
				case 'Resultados de la investigación':
				case 'Resultados de Pesquisa':
				case 'Resultats de la recerca':
				case 'Résultats de recherche':
				case 'Rezultate de cercetare':
				case 'Rezultati istraživanja':
				case 'Rezultaty z badań':
				case 'Risultati di ricerca':
				case 'Výsledky výzkumu':
				case 'نتایج پژوهش':
				case 'نتائج بحث':
				case 'Αποτελέσματα έρευνας':
				case 'Истражувачки резултати':
				case 'Результати дослідження':
				case 'Результаты исследования':
				case 'ഗവേഷണ ഫലങ്ങള്‍':
				case '研究結果':
				case '科研结果':
					$genre = $genreDao->getByKey('RESEARCHRESULTS', $journal->getId());
					break;
				// author.submit.suppFile.sourceText
				case 'Brontekst':
				case 'Iturburu-testua':
				case 'Izvorni tekst':
				case 'Källtext':
				case 'Kaynak Metin':
				case 'Kildetekst':
				case 'ソーステキスト':
				case 'Quellentext':
				case 'Source Text':
				case 'Teks Sumber':
				case 'Tekst źródłowy':
				case 'Testo della fonte':
				case 'Texte source':
				case 'Texte sursă':
				case 'Texto fonte':
				case 'Texto fuente':
				case 'Texto Original':
				case 'Text original':
				case 'Văn bản (text) nguồn':
				case 'Zdrojový text':
				case 'متن منبع':
				case 'نص مصدر':
				case 'Πηγαίο κείμενο':
				case 'Изворен текст':
				case 'Исходный текст':
				case 'Текст першоджерела':
				case 'സോഴ്സ് ടെക്സ്റ്റ്':
				case '來源文獻':
				case '源文本':
					$genre = $genreDao->getByKey('SOURCETEXTS', $journal->getId());
					break;
				// author.submit.suppFile.transcripts
				case 'Afskrifter':
				case 'Kopya / Suret':
				case 'Lời thoại':
				case 'Reproduktioner':
				case 'Transcrição':
				case 'Transcricións':
				case 'Transcripciones':
				case 'Transcripcions':
				case 'Transcripties':
				case 'Transcriptions':
				case 'Transcripts':
				case 'Transcripturi':
				case 'Transkrip':
				case 'Transkripsjoner':
				case 'Transkripte':
				case 'Transkripti':
				case 'Transkript':
				case 'Transkripty':
				case 'Transkripzioak':
				case 'Transkrypcje':
				case 'Trascrizioni':
				case 'رونوشت':
				case 'نصوص':
				case 'Καταγραφή':
				case 'Стенограми':
				case 'Транскрипти':
				case 'Транскрипты':
				case 'പകര്‍പ്പുകള്‍':
				case '副本':
				case '筆記録':
					$genre = $genreDao->getByKey('TRANSCRIPTS', $journal->getId());
					break;
				default:
					$genre = $genreDao->getByKey('OTHER', $journal->getId());
					break;
			}
			assert(isset($genre));

			// Set genres for files
			$submissionFiles = $submissionFileDao->getAllRevisions($row['file_id']);
			foreach ((array) $submissionFiles as $submissionFile) {
				$submissionFile->setGenreId($genre->getId());
				$submissionFile->setUploaderUserId($creatorUserId);
				$fileStage = $article->getStatus() == STATUS_PUBLISHED ? SUBMISSION_FILE_PROOF : SUBMISSION_FILE_SUBMISSION;
				$submissionFile->setFileStage($fileStage);
				$submissionFileDao->updateObject($submissionFile);
			}

			// Reload the files now that they're cast; set metadata
			$submissionFiles = $submissionFileDao->getAllRevisions($row['file_id']);
			foreach ((array) $submissionFiles as $submissionFile) {
				$suppFileSettingsResult = $submissionFileDao->retrieve('SELECT * FROM article_supp_file_settings WHERE supp_id = ? AND setting_value IS NOT NULL', array($row['supp_id']));
				$extraSettings = $extraGalleySettings = array();
				while (!$suppFileSettingsResult->EOF) {
					$sfRow = $suppFileSettingsResult->getRowAssoc(false);
					$suppFileSettingsResult->MoveNext();
					switch ($sfRow['setting_name']) {
						case 'creator':
							$submissionFile->setCreator($sfRow['setting_value'], $sfRow['locale']);
							break;
						case 'description':
							$submissionFile->setDescription($sfRow['setting_value'], $sfRow['locale']);
							break;
						case 'publisher':
							$submissionFile->setPublisher($sfRow['setting_value'], $sfRow['locale']);
							break;
						case 'source':
							$submissionFile->setSource($sfRow['setting_value'], $sfRow['locale']);
							break;
						case 'sponsor':
							$submissionFile->setSponsor($sfRow['setting_value'], $sfRow['locale']);
							break;
						case 'subject':
							$submissionFile->setSubject($sfRow['setting_value'], $sfRow['locale']);
							break;
						case 'title':
							$submissionFile->setName($sfRow['setting_value'], $sfRow['locale']);
							break;
						case 'typeOther': break; // Discard (at least for now)
						case 'excludeDoi': break; // Discard (no longer relevant)
						case 'excludeURN': break; // Discard (no longer relevant)
						case 'pub-id::doi':
						case 'pub-id::other::urn':
						case 'pub-id::publisher-id':
						case 'urnSuffix':
						case 'doiSuffix':
						case 'datacite::registeredDoi':
							$extraGalleySettings[$sfRow['setting_name']] = $sfRow['setting_value'];
							break;
						default:
							error_log('Unknown supplementary file setting "' . $sfRow['setting_name'] . '"!');
							break;
					}
				}
				$suppFileSettingsResult->Close();

				// Store the old supp ID so that we can redirect requests for old URLs.
				$extraSettings['old-supp-id'] = $row['supp_id'];

				$submissionFileDao->updateObject($submissionFile);

				// Preserve extra settings. (Plugins may not be loaded, so other mechanisms might not work.)
				foreach ($extraSettings as $name => $value) {
					$submissionFileDao->update(
						'INSERT INTO submission_file_settings (file_id, setting_name, setting_value, setting_type) VALUES (?, ?, ?, ?)',
						array(
							$submissionFile->getFileId(),
							$name,
							$value,
							'string'
						)
					);
				}

				if ($article->getStatus() == STATUS_PUBLISHED) {
					$articleGalley = $articleGalleyDao->newDataObject();
					$articleGalley->setFileId($submissionFile->getFileId());
					$articleGalley->setSubmissionId($article->getId());
					$articleGalley->setLabel($submissionFile->getName($article->getLocale()));
					$articleGalley->setLocale($article->getLocale());
					$articleGalleyDao->insertObject($articleGalley);

					// Preserve extra settings. (Plugins may not be loaded, so other mechanisms might not work.)
					foreach ($extraGalleySettings as $name => $value) {
						$submissionFileDao->update(
							'INSERT INTO submission_galley_settings (galley_id, setting_name, setting_value, setting_type) VALUES (?, ?, ?, ?)',
							array(
								$articleGalley->getId(),
								$name,
								$value,
								'string'
							)
						);
					}
				}
			}
		}
		$suppFilesResult->Close();
		return true;
	}

	/**
	 * Provide supplementary files of active submissions for review.
	 * @return boolean True indicates success.
	 */
	function provideSupplementaryFilesForReview() {
		$submissionFileDao = DAORegistry::getDAO('SubmissionFileDAO');
		$reviewRoundDao = DAORegistry::getDAO('ReviewRoundDAO');
		$reviewAssignmentDao = DAORegistry::getDAO('ReviewAssignmentDAO');
		$reviewFilesDao = DAORegistry::getDAO('ReviewFilesDAO');
		import('lib.pkp.classes.file.SubmissionFileManager');
		// Get supp files with show_reviewers = 1
		// We cannot support/consider remote supp files
		$suppFilesResult = $submissionFileDao->retrieve('SELECT a.context_id, sf.* FROM article_supplementary_files sf, submissions a WHERE a.submission_id = sf.article_id AND sf.file_id <> 0 AND sf.show_reviewers = 1 AND sf.remote_url IS NULL and sf.file_id in (select f.file_id from submission_files f)');
		while (!$suppFilesResult->EOF) {
			$suppFilesRow = $suppFilesResult->getRowAssoc(false);
			$suppFilesResult->MoveNext();
			$reviewRounds = $reviewRoundDao->getBySubmissionId($suppFilesRow['article_id'], WORKFLOW_STAGE_ID_EXTERNAL_REVIEW);
			// If a review round exists
			// copy the supp file to the submissin review stage, add it to each existing review round, and as a review round file
			if ($reviewRounds->getCount() != 0) {
				$submissionFileManager = new SubmissionFileManager($suppFilesRow['context_id'], $suppFilesRow['article_id']);
				// Retrieve the supp file last revision number, although they probably only have revision 1.
				$revisionNumber = $submissionFileDao->getLatestRevisionNumber($suppFilesRow['file_id']);
				// copy the supp file to the submissin review stage
				list($newFileId, $newRevision) = $submissionFileManager->copyFileToFileStage($suppFilesRow['file_id'], $revisionNumber, SUBMISSION_FILE_REVIEW_FILE, null, true);
				while ($reviewRound = $reviewRounds->next()) {
					// add it to the review round
					$submissionFileDao->assignRevisionToReviewRound($newFileId, $newRevision, $reviewRound);
					// Get all review assignments
					$reviewAssignments = $reviewAssignmentDao->getBySubmissionId($suppFilesRow['article_id'], $reviewRound->getId(), WORKFLOW_STAGE_ID_EXTERNAL_REVIEW);
					foreach ($reviewAssignments as $reviewAssignment) {
						// add it to the review files
						$reviewFilesDao->grant($reviewAssignment->getId(), $newFileId);
					}
				}
			}
		}
		$suppFilesResult->Close();
		return true;
	}

	function _createQuery($stageId, $submissionId, $sequence, $title, $dateNotified = null) {
		$queryDao = DAORegistry::getDAO('QueryDAO');
		$noteDao = DAORegistry::getDAO('NoteDAO');

		$query = $queryDao->newDataObject();
		$query->setAssocType(ASSOC_TYPE_SUBMISSION);
		$query->setAssocId($submissionId);
		$query->setStageId($stageId);
		$query->setSequence($sequence);
		$queryDao->insertObject($query);

		$headNote = $noteDao->newDataObject();
		$headNote->setAssocType(ASSOC_TYPE_QUERY);
		$headNote->setAssocId($query->getId());
		$headNote->setTitle($title);
		$headNote->setDateCreated($dateNotified?$dateNotified:time());
		$headNote->setDateModified($dateNotified?$dateNotified:time());
		$noteDao->insertObject($headNote);

		return $query;
	}

	/**
	 * Convert signoffs to queries.
	 * @return boolean True indicates success.
	 */
	function convertQueries() {
		$submissionFileDao = DAORegistry::getDAO('SubmissionFileDAO');
		import('lib.pkp.classes.submission.SubmissionFile');

		$signoffsResult = $submissionFileDao->retrieve('SELECT * FROM signoffs WHERE user_id IS NOT NULL AND user_id <> 0');

		$queryDao = DAORegistry::getDAO('QueryDAO');
		$noteDao = DAORegistry::getDAO('NoteDAO');
		$userDao = DAORegistry::getDAO('UserDAO');
		$stageAssignmentDao = DAORegistry::getDAO('StageAssignmentDAO');

		// Go through all signoffs and migrate them into queries.
		$copyeditingQueries = $proofreadingQueries = $layoutQueries = array();
		while (!$signoffsResult->EOF) {
			$row = $signoffsResult->getRowAssoc(false);
			$fileId = $row['file_id'];
			$symbolic = $row['symbolic'];
			$dateNotified = $row['date_notified']?strtotime($row['date_notified']):null;
			$dateCompleted = $row['date_completed']?strtotime($row['date_completed']):null;
			$userId = $row['user_id'];
			$signoffId = $row['signoff_id'];
			assert($row['assoc_type'] == ASSOC_TYPE_SUBMISSION); // Already changed from ASSOC_TYPE_ARTICLE
			$assocId = $row['assoc_id'];
			$signoffsResult->MoveNext();

			// Stage 1. Create or look up the query object.
			switch ($symbolic) {
				case 'SIGNOFF_COPYEDITING_INITIAL':
				case 'SIGNOFF_COPYEDITING_AUTHOR':
				case 'SIGNOFF_COPYEDITING_FINAL':
					if (isset($copyeditingQueries[$assocId])) $query = $copyeditingQueries[$assocId];
					else {
						$query = $copyeditingQueries[$assocId] = $this->_createQuery(WORKFLOW_STAGE_ID_EDITING, $assocId, 1, 'Copyediting', $dateNotified);
					}
					break;
				case 'SIGNOFF_LAYOUT':
					$query = $layoutQueries[$assocId] = $this->_createQuery(WORKFLOW_STAGE_ID_PRODUCTION, $assocId, 1, 'Layout Editing', $dateNotified);
					break;
				case 'SIGNOFF_PROOFREADING_AUTHOR':
				case 'SIGNOFF_PROOFREADING_PROOFREADER':
				case 'SIGNOFF_PROOFREADING_LAYOUT':
					if (isset($proofreadingQueries[$assocId])) $query = $proofreadingQueries[$assocId];
					else {
						$query = $proofreadingQueries[$assocId] = $this->_createQuery(WORKFLOW_STAGE_ID_PRODUCTION, $assocId, 2, 'Proofreading', $dateNotified);
					}
					break;
			}
			assert(isset($query)); // We've created or looked up a query.

			$assignedUserIds = array($userId);
			foreach (array(ROLE_ID_MANAGER, ROLE_ID_SUB_EDITOR, ROLE_ID_ASSISTANT) as $roleId) {
				$stageAssignments = $stageAssignmentDao->getBySubmissionAndRoleId($assocId, $roleId, $query->getStageId());
				while ($stageAssignment = $stageAssignments->next()) {
					$assignedUserIds[] = $stageAssignment->getUserId();
				}
			}

			// Ensure that the necessary users are assigned to the query
			foreach (array_unique($assignedUserIds) as $assignedUserId) {
				if (count($queryDao->getParticipantIds($query->getId(), $assignedUserId))!=0) continue;
				$queryDao->insertParticipant($query->getId(), $assignedUserId);
			}

			$submissionFiles = $submissionFileDao->getAllRevisions($fileId);
			foreach((array) $submissionFiles as $submissionFile) {
				$submissionFile->setAssocType(ASSOC_TYPE_NOTE);
				$submissionFile->setAssocId($query->getHeadNote()->getId());
				$submissionFile->setFileStage(SUBMISSION_FILE_QUERY);
				$submissionFileDao->updateObject($submissionFile);
			}
		}
		$signoffsResult->Close();

		// Migrate related notes into the queries
		$commentsResult = $submissionFileDao->retrieve('SELECT * FROM submission_comments WHERE comment_type IN (3, 4, 5)');
		while (!$commentsResult->EOF) {
			$row = $commentsResult->getRowAssoc(false);
			$commentsResult->MoveNext();

			$note = $noteDao->newDataObject();
			$note->setAssocType(ASSOC_TYPE_QUERY);
			$note->setDateCreated(strtotime($row['date_posted']));
			$note->setDateModified(strtotime($row['date_modified']));
			switch ($row['comment_type']) {
				case 3: // COMMENT_TYPE_COPYEDIT
					$note->setTitle('Copyediting');
					if (!isset($copyeditingQueries[$row['submission_id']])) {
						$copyeditingQueries[$row['submission_id']] = $this->_createQuery(WORKFLOW_STAGE_ID_EDITING, $row['submission_id'], 1, 'Copyediting');
					}
					$note->setAssocId($copyeditingQueries[$row['submission_id']]->getId());
					break;
				case 4: // COMMENT_TYPE_LAYOUT
					$note->setTitle('Layout Editing');
					if (!isset($layoutQueries[$row['submission_id']])) {
						$layoutQueries[$row['submission_id']] = $this->_createQuery(WORKFLOW_STAGE_ID_PRODUCTION, $row['submission_id'], 1, 'Layout Editing');
					}
					$note->setAssocId($layoutQueries[$row['submission_id']]->getId());
					break;
				case 5: // COMMENT_TYPE_PROOFREAD
					$note->setTitle('Proofreading');
					if (!isset($proofreadingQueries[$row['submission_id']])) {
						$proofreadingQueries[$row['submission_id']] = $this->_createQuery(WORKFLOW_STAGE_ID_PRODUCTION, $row['submission_id'], 2, 'Proofreading');
					}
					$note->setAssocId($proofreadingQueries[$row['submission_id']]->getId());
					break;
			}
			$note->setContents(nl2br($row['comments']));
			$note->setUserId($row['author_id']);
			$noteDao->insertObject($note);
		}
		$commentsResult->Close();

		$submissionFileDao->update('DELETE FROM submission_comments WHERE comment_type IN (3, 4, 5)'); // COMMENT_TYPE_EDITOR_DECISION
		return true;
	}

	/**
	 * Convert editor decision notes to a query.
	 * @return boolean True indicates success.
	 */
	function convertEditorDecisionNotes() {
		$noteDao = DAORegistry::getDAO('NoteDAO');
		$queryDao = DAORegistry::getDAO('QueryDAO');
		$stageAssignmentDao = DAORegistry::getDAO('StageAssignmentDAO');

		$commentsResult = $noteDao->retrieve('SELECT sc.*, a.user_id FROM submission_comments sc, articles_migration a WHERE sc.submission_id = a.article_id AND sc.comment_type=2 ORDER BY sc.submission_id, sc.comment_id ASC'); // COMMENT_TYPE_EDITOR_DECISION
		$submissionId = 0;
		$query = null; // Avoid Scrutinizer warnings
		while (!$commentsResult->EOF) {
			$row = $commentsResult->getRowAssoc(false);
			$commentsResult->MoveNext();

			if ($submissionId != $row['submission_id']) {
				$submissionId = $row['submission_id'];
				$query = $queryDao->newDataObject();
				$query->setAssocType(ASSOC_TYPE_SUBMISSION);
				$query->setAssocId($submissionId);
				$query->setStageId(WORKFLOW_STAGE_ID_EXTERNAL_REVIEW);
				$query->setSequence(REALLY_BIG_NUMBER);
				$queryDao->insertObject($query);
				$queryDao->resequence(ASSOC_TYPE_SUBMISSION, $submissionId);

				$assignedUserIds = array($row['user_id']);
				foreach (array(ROLE_ID_MANAGER, ROLE_ID_SUB_EDITOR, ROLE_ID_ASSISTANT) as $roleId) {
					$stageAssignments = $stageAssignmentDao->getBySubmissionAndRoleId($submissionId, $roleId, $query->getStageId());
					while ($stageAssignment = $stageAssignments->next()) {
						$assignedUserIds[] = $stageAssignment->getUserId();
					}
				}

				// Ensure that the necessary users are assigned to the query
				foreach (array_unique($assignedUserIds) as $assignedUserId) {
					if (count($queryDao->getParticipantIds($query->getId(), $assignedUserId))!=0) continue;
					$queryDao->insertParticipant($query->getId(), $assignedUserId);
				}
			}

			$note = $noteDao->newDataObject();
			$note->setAssocType(ASSOC_TYPE_QUERY);
			$note->setAssocId($query->getId());
			$note->setContents(nl2br($row['comments']));
			$note->setTitle('Editor Decision');
			$note->setDateCreated(strtotime($row['date_posted']));
			$note->setDateModified(strtotime($row['date_modified']));
			$note->setUserId($row['author_id']);
			$noteDao->insertObject($note);
		}
		$commentsResult->Close();

		$noteDao->update('DELETE FROM submission_comments WHERE comment_type=2'); // COMMENT_TYPE_EDITOR_DECISION
		return true;
	}

	/**
	 * Convert comments to editors to queries.
	 * @return boolean True indicates success.
	 */
	function convertCommentsToEditor() {
		$submissionDao = Application::getSubmissionDAO();
		$stageAssignmetDao = DAORegistry::getDAO('StageAssignmentDAO');
		$queryDao = DAORegistry::getDAO('QueryDAO');
		$noteDao = DAORegistry::getDAO('NoteDAO');
		$userGroupDao = DAORegistry::getDAO('UserGroupDAO');

		import('lib.pkp.classes.security.Role'); // ROLE_ID_...

		$commentsResult = $submissionDao->retrieve(
			'SELECT s.submission_id, s.context_id, s.comments_to_ed, s.date_submitted
			FROM submissions_tmp s
			WHERE s.comments_to_ed IS NOT NULL AND s.comments_to_ed != \'\''
		);
		while (!$commentsResult->EOF) {
			$row = $commentsResult->getRowAssoc(false);
			$comments_to_ed = PKPString::stripUnsafeHtml($row['comments_to_ed']);
			if ($comments_to_ed != ""){
				$userId = null;
				$authorAssignmentsResult = $stageAssignmetDao->getBySubmissionAndRoleId($row['submission_id'], ROLE_ID_AUTHOR);
				if ($authorAssignmentsResult->getCount() != 0) {
					// We assume the results are ordered by stage_assignment_id i.e. first author assignemnt is first
					$userId = $authorAssignmentsResult->next()->getUserId();
				} else {
					$managerUserGroup = $userGroupDao->getDefaultByRoleId($row['context_id'], ROLE_ID_MANAGER);
					$managerUsers = $userGroupDao->getUsersById($managerUserGroup->getId(), $row['context_id']);
					$userId = $managerUsers->next()->getId();
				}
				assert($userId);

				$query = $queryDao->newDataObject();
				$query->setAssocType(ASSOC_TYPE_SUBMISSION);
				$query->setAssocId($row['submission_id']);
				$query->setStageId(WORKFLOW_STAGE_ID_SUBMISSION);
				$query->setSequence(REALLY_BIG_NUMBER);

				$queryDao->insertObject($query);
				$queryDao->resequence(ASSOC_TYPE_SUBMISSION, $row['submission_id']);
				$queryDao->insertParticipant($query->getId(), $userId);

				$queryId = $query->getId();

				$note = $noteDao->newDataObject();
				$note->setUserId($userId);
				$note->setAssocType(ASSOC_TYPE_QUERY);
				$note->setTitle('Comments for the Editor');
				$note->setContents($comments_to_ed);
				$note->setDateCreated(strtotime($row['date_submitted']));
				$note->setDateModified(strtotime($row['date_submitted']));
				$note->setAssocId($queryId);
				$noteDao->insertObject($note);
			}
			$commentsResult->MoveNext();
		}
		$commentsResult->Close();

		// remove temporary table
		$submissionDao->update('DROP TABLE submissions_tmp');

		return true;
	}


	/**
	 * Localize issue cover images.
	 * @return boolean True indicates success.
	 */
	function localizeIssueCoverImages() {
		$issueDao = DAORegistry::getDAO('IssueDAO');
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
		while (!$result->EOF) {
			$row = $result->GetRowAssoc(false);
			$oldFileName = $row['setting_value'];
			if ($publicFileManager->fileExists($publicFileManager->getContextFilesPath($row['journal_id']) . '/' . $oldFileName)) {
				$publicFileManager->removeContextFile($row['journal_id'], $oldFileName);
			}
			$issueDao->update('DELETE FROM issue_settings WHERE issue_id = ? AND setting_name = \'fileName\' AND setting_value = ?', array((int) $row['issue_id'], $oldFileName));
			$result->MoveNext();
		}
		$result->Close();

		// retrieve names for unlocalized issue cover images
		$result = $issueDao->retrieve(
			'SELECT iss.issue_id, iss.setting_value, j.journal_id, j.primary_locale
			FROM issue_settings iss, issues i, journals j
			WHERE iss.setting_name = \'coverImage\' AND iss.locale = \'\'
				AND i.issue_id = iss.issue_id AND j.journal_id = i.journal_id'
		);
		// for all unlocalized issue cover images
		// rename (copy + remove) the cover images files in the public folder,
		// considereing the locale (using the journal primary locale)
		while (!$result->EOF) {
			$row = $result->GetRowAssoc(false);
			$oldFileName = $row['setting_value'];
			$newFileName = str_replace('.', '_' . $row['primary_locale'] . '.', $oldFileName);
			if ($publicFileManager->fileExists($publicFileManager->getContextFilesPath($row['journal_id']) . '/' . $oldFileName)) {
				$publicFileManager->copyContextFile($row['journal_id'], $publicFileManager->getContextFilesPath($row['journal_id']) . '/' . $oldFileName, $newFileName);
				$publicFileManager->removeContextFile($row['journal_id'], $oldFileName);
			}
			$result->MoveNext();
		}
		$result->Close();
		$driver = $issueDao->getDriver();
		switch ($driver) {
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
		$submissionDao = DAORegistry::getDAO('SubmissionDAO');
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
		while (!$result->EOF) {
			$row = $result->GetRowAssoc(false);
			$submissionId = $row['submission_id'];
			$oldFileName = $row['setting_value'];
			if ($publicFileManager->fileExists($publicFileManager->getContextFilesPath($row['context_id']) . '/' . $oldFileName)) {
				$publicFileManager->removeContextFile($row['journal_id'], $oldFileName);
			}
			$submissionDao->update('DELETE FROM submission_settings WHERE submission_id = ? AND setting_name = \'fileName\' AND setting_value = ?', array((int) $submissionId, $oldFileName));
			$result->MoveNext();
		}
		$result->Close();

		// retrieve names for unlocalized article cover images
		$result = $submissionDao->retrieve(
			'SELECT ss.submission_id, ss.setting_value, j.journal_id, j.primary_locale
			FROM submission_settings ss, submissions s, journals j
			WHERE ss.setting_name = \'coverImage\' AND ss.locale = \'\'
				AND s.submission_id = ss.submission_id AND j.journal_id = s.context_id'
		);
		// for all unlocalized article cover images
		// rename (copy + remove) the cover images files in the public folder,
		// considereing the locale (using the journal primary locale)
		while (!$result->EOF) {
			$row = $result->GetRowAssoc(false);
			$oldFileName = $row['setting_value'];
			$newFileName = str_replace('.', '_' . $row['primary_locale'] . '.', $oldFileName);
			if ($publicFileManager->fileExists($publicFileManager->getContextFilesPath($row['journal_id']) . '/' . $oldFileName)) {
				$publicFileManager->copyContextFile($row['journal_id'], $publicFileManager->getContextFilesPath($row['journal_id']) . '/' . $oldFileName, $newFileName);
				$publicFileManager->removeContextFile($row['journal_id'], $oldFileName);
			}
			$result->MoveNext();
		}
		$result->Close();
		$driver = $submissionDao->getDriver();
		switch ($driver) {
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
		$userGroupDao = DAORegistry::getDAO('UserGroupDAO');
		$result = $userGroupDao->retrieve(
			'SELECT a.author_id, s.context_id FROM authors a JOIN submissions s ON (a.submission_id = s.submission_id) JOIN user_groups g ON (a.user_group_id = g.user_group_id) WHERE g.context_id <> s.context_id'
		);
		while (!$result->EOF) {
			$row = $result->GetRowAssoc(false);
			$authorGroup = $userGroupDao->getDefaultByRoleId($row['context_id'], ROLE_ID_AUTHOR);
			if ($authorGroup) $userGroupDao->update('UPDATE authors SET user_group_id = ? WHERE author_id = ?', array((int) $authorGroup->getId(), $row['author_id']));
			$result->MoveNext();
		}
		$result->Close();
		return true;
	}

	/**
	 * For 3.0.0 - 3.0.2 upgrade: first part of the fix for the migrated reviewer files.
	 * The files are renamed and moved from 'review' to 'review/attachment' folder.
	 * @return boolean
	 */
	function moveReviewerFiles() {
		$submissionFileDao = DAORegistry::getDAO('SubmissionFileDAO');

		import('lib.pkp.classes.file.SubmissionFileManager');

		// get reviewer file ids
		$result = $submissionFileDao->retrieve(
			'SELECT ra.review_id, ra.submission_id, ra.review_round_id, ra.review_id, ra.reviewer_file_id, s.context_id
			FROM review_assignments ra, submissions s
			WHERE ra.reviewer_file_id IS NOT NULL AND s.submission_id = ra.submission_id'
		);
		while (!$result->EOF) {
			$row = $result->GetRowAssoc(false);

			$submissionFileManager = new SubmissionFileManager($row['context_id'], $row['submission_id']);
			$revisions = $submissionFileDao->getAllRevisions($row['reviewer_file_id']);
			if (!empty($revisions)) {
				foreach ($revisions as $revision) {
					$wrongFilePath = $revision->getFilePath();
					$revision->setFileStage(SUBMISSION_FILE_REVIEW_ATTACHMENT);
					$newFilePath = $revision->getFilePath();
					if (!file_exists($newFilePath)) {
						if (!file_exists($path = dirname($newFilePath)) && !$submissionFileManager->mkdirtree($path)) {
							error_log("ERROR: Unable to make directory \"$path\"");
						}
						if (!rename($wrongFilePath, $newFilePath)) {
							error_log("ERROR: Unable to move \"$wrongFilePath\" to \"$newFilePath\".");
						}
					}
				}
			} else {
				error_log('ERROR: Reviewer files with ID ' . $row['reviewer_file_id'] . ' from review assignment ' .$row['review_id'] . ' could not be found in the database table submission_files');
			}

			$result->MoveNext();
		}
		$result->Close();
		return true;
	}

	/**
	 * For 2.4.x - 3.1.0 upgrade: remove cancelled review assignments.
	 * @return boolean
	 */
	function removeCancelledReviewAssignments() {
		$reviewAssignmentDao = DAORegistry::getDAO('ReviewAssignmentDAO');
		// get cancelled review assignemnts
		$result = $reviewAssignmentDao->retrieve('SELECT review_id FROM review_assignments_tmp');
		while (!$result->EOF) {
			$row = $result->GetRowAssoc(false);
			$reviewAssignmentDao->deleteById($row['review_id']);
			$result->MoveNext();
		}
		$result->Close();
		// remove temporary table
		$reviewAssignmentDao->update('DROP TABLE review_assignments_tmp');
		// update log messages
		$eventLogDao = DAORegistry::getDAO('SubmissionEventLogDAO');
		$eventLogDao->update('UPDATE event_log SET message = \'log.review.reviewCleared\' WHERE message = \'log.review.reviewCancelled\'');
		return true;
	}

	/**
	 * For 2.4.x - 3.1.0 upgrade: concatenate removed journal setting fields into the new journal setting 'about'.
	 * @return boolean
	 */
	function concatenateIntoAbout() {
		$journalDao = DAORegistry::getDAO('JournalDAO');
		$journalSettingsDao = DAORegistry::getDAO('JournalSettingsDAO');
		$journals = $journalDao->getAll();
		while ($journal = $journals->next()) {
			$settings = $journalSettingsDao->loadSettings($journal->getId());
			$supportedFormLocales = $journal->getSupportedFormLocales();
			$focusAndScope = $journalSettingsDao->getSetting('focusScopeDesc');
			$focusAndScope['localeKey'] = 'about.focusAndScope';
			$reviewPolicy = $journalSettingsDao->getSetting('reviewPolicy');
			$reviewPolicy['localeKey'] = 'about.peerReviewProcess';
			$pubFreqPolicy = $journalSettingsDao->getSetting('pubFreqPolicy');
			$pubFreqPolicy['localeKey'] = 'about.publicationFrequency';
			$oaPolicy = array();
			if ($journal->getSetting('publishingMode') == PUBLISHING_MODE_OPEN) {
				$oaPolicy = $journalSettingsDao->getSetting('openAccessPolicy');
				$oaPolicy['localeKey'] = 'about.openAccessPolicy';
			}
			// the elements order accords to how they were displayed on the about page
			$editorialPolicySettings = array(
				'focusAndScope' => $focusAndScope,
				'peerReviewProcess' => $reviewPolicy,
				'publicationFrequency' => $pubFreqPolicy,
				'openAccessPolicy' => $oaPolicy,
			);

			$customAboutItems = $journalSettingsDao->getSetting('customAboutItems');

			$sponsorNote = $journalSettingsDao->getSetting('sponsorNote');
			$sponsors = $journalSettingsDao->getSetting('sponsors');
			$contributorNote = $journalSettingsDao->getSetting('contributorNote');
			$contributorNote['localeKey'] = 'grid.contributor.title';
			$contributors = $journalSettingsDao->getSetting('contributors');
			$history = $journalSettingsDao->getSetting('history');
			$history['localeKey'] = 'about.history';
			// the elements order accords to how they were displayed on the about page
			$otherSettings = array(
				'sponsors' => $sponsorNote,
				'contributors' => $contributorNote,
				'history' => $history,
			);

			$aboutJournal = array();
			foreach ($supportedFormLocales as $locale) {
				AppLocale::requireComponents(LOCALE_COMPONENT_APP_COMMON, LOCALE_COMPONENT_PKP_GRID, $locale);
				$aboutJournal[$locale] = '';
				// concatenate editorial policies first
				foreach ($editorialPolicySettings as $divId => $editorialPolicySetting) {
					if (!empty($editorialPolicySetting[$locale])) {
						$aboutJournal[$locale] .= '
							<div id="'.$divId.'">
							<h3>'.__($editorialPolicySetting['localeKey'], array(), $locale).'</h3>
							<p>'.nl2br($editorialPolicySetting[$locale]).'</p>
							</div>';
					}
				}
				// concatenate then the custom about items
				if (!empty($customAboutItems[$locale])) {
					foreach ($customAboutItems[$locale] as $index => $customItem) {
						if (!empty($customItem['title']) && !empty($customItem['content'])) {
							$aboutJournal[$locale] .= '
								<div id="custom-'.$index.'">
								<h3>'.$customItem['title'].'</h3>
								<p>'.nl2br($customItem['content']).'</p>
								</div>';
						}
					}
				}
				// finally, concatenate the other settings
				foreach ($otherSettings as $divId => $otherSetting) {
					if ($divId == 'sponsors') {
						if (!empty($otherSetting[$locale]) || !empty($sponsors)) {
							$aboutJournal[$locale] .= '
								<div id="'.$divId.'">
								<h3>Sponsors</h3>'; // hard coded because the locale key does no exist any more
							if (!empty($otherSetting[$locale])) {
								$aboutJournal[$locale] .= '
								<p>'.nl2br($otherSetting[$locale]).'</p>';
							}
							if (!empty($sponsors)) {
								$aboutJournal[$locale] .= '<ul>';
								foreach ($sponsors as $sponsor) {
									$aboutJournal[$locale] .= '<li>';
									if (!empty($sponsor['url'])) {
										$aboutJournal[$locale] .= '
											<a href="'.htmlspecialchars($sponsor['url']).'">'.htmlspecialchars($sponsor['institution']).'</a>';
									} else {
										$aboutJournal[$locale] .= htmlspecialchars($sponsor['institution']);
									}
									$aboutJournal[$locale] .= '</li>';
								}
								$aboutJournal[$locale] .= '</ul>';
							}
							$aboutJournal[$locale] .= '</div>';
						}
					} elseif ($divId == 'contributors') {
						if (!empty($otherSetting[$locale]) || !empty($contributors)) {
							$aboutJournal[$locale] .= '
								<div id="'.$divId.'">
								<h3>'.__($otherSetting['localeKey'], array(), $locale).'</h3>';
							if (!empty($otherSetting[$locale])) {
								$aboutJournal[$locale] .= '
									<p>'.nl2br($otherSetting[$locale]).'</p>';
							}
							if (!empty($contributors)) {
								$aboutJournal[$locale] .= '<ul>';
								foreach ($contributors as $contributor) {
									$aboutJournal[$locale] .= '<li>';
									if (!empty($contributor['url'])) {
										$aboutJournal[$locale] .= '
											<a href="'.htmlspecialchars($contributor['url']).'">'.htmlspecialchars($contributor['name']).'</a>';
									} else {
										$aboutJournal[$locale] .= htmlspecialchars($contributor['name']);
									}
									$aboutJournal[$locale] .= '</li>';
								}
								$aboutJournal[$locale] .= '</ul>';
							}
							$aboutJournal[$locale] .= '</div>';
						}
					} else {
						if (!empty($otherSetting[$locale])) {
							$aboutJournal[$locale] .= '
								<div id="'.$divId.'">
								<h3>'.__($otherSetting['localeKey'], array(), $locale).'</h3>
								<p>'.nl2br($otherSetting[$locale]).'</p>
								</div>';
						}
					}
				}
			}
			$journalSettingsDao->updateSetting($journal->getId(), 'about', $aboutJournal, 'string', true);
			unset($journal);
		}
		return true;
	}

	/**
	 * For 2.4.x - 3.1.0 upgrade: concatenate editorialTeam and displayMembership page to new journal setting 'masthead'.
	 * @return boolean
	 */
	function concatenateIntoMasthead() {
		$roles = array(OJS2_ROLE_ID_EDITOR, OJS2_ROLE_ID_SECTION_EDITOR, OJS2_ROLE_ID_LAYOUT_EDITOR, OJS2_ROLE_ID_COPYEDITOR, OJS2_ROLE_ID_PROOFREADER);
		$localeKeys = array(
			OJS2_ROLE_ID_EDITOR => array('user.role.editor', 'user.role.editors'),
			OJS2_ROLE_ID_SECTION_EDITOR => array('user.role.subEditor', 'user.role.subEditors'),
			OJS2_ROLE_ID_LAYOUT_EDITOR => array('user.role.layoutEditor', 'user.role.layoutEditors'),
			OJS2_ROLE_ID_COPYEDITOR => array('user.role.copyeditor', 'user.role.copyeditors'),
			OJS2_ROLE_ID_PROOFREADER => array('user.role.proofreader', 'user.role.proofreaders'),
		);

		$roleDao = DAORegistry::getDAO('RoleDAO');
		$userDao = DAORegistry::getDAO('UserDAO');
		$journalDao = DAORegistry::getDAO('JournalDAO');
		$journalSettingsDao = DAORegistry::getDAO('JournalSettingsDAO');
		$countryDao = DAORegistry::getDAO('CountryDAO');
		$countries = $countryDao->getCountries();

		$journals = $journalDao->getAll();
		while ($journal = $journals->next()) {
			$settings = $journalSettingsDao->loadSettings($journal->getId());
			if ($journalSettingsDao->getSetting('boardEnabled')) {
				// get all users by group ID
				$groupUsers = array();
				$groupPrimaryLocaleTitles = array();
				// get groups sorted by context -- that accords to the order they are displayed on the about page
				$dataSource = $roleDao->getDataSource();
				$allGroupsResult = $roleDao->retrieve('SELECT * FROM ' . $dataSource->nameQuote . 'groups' . $dataSource->nameQuote . ' WHERE assoc_type = ? AND assoc_id = ? AND about_displayed = 1 ORDER BY context, seq', array((int) ASSOC_TYPE_JOURNAL, (int) $journal->getId()));
				while (!$allGroupsResult->EOF) {
					$groupRow = $allGroupsResult->getRowAssoc(false);
					$groupMembershipsResult = $roleDao->retrieve('SELECT * FROM group_memberships WHERE group_id = ? AND about_displayed = 1 ORDER BY seq', $groupRow['group_id']);
					while (!$groupMembershipsResult->EOF) {
						$groupMembershipRow = $groupMembershipsResult->getRowAssoc(false);
						$user = $userDao->getById($groupMembershipRow['user_id']);
						if ($user) {
							$groupUsers[$groupRow['group_id']][] = $user;
							$groupPrimaryLocaleTitleResult = $roleDao->retrieve('SELECT setting_value FROM group_settings WHERE group_id = ?  AND locale = ? AND setting_name = \'title\'', array((int) $groupRow['group_id'], $journal->getPrimaryLocale()));
							$groupPrimaryLocaleTitle = $groupPrimaryLocaleTitleResult->getRowAssoc(false);
							$groupPrimaryLocaleTitles[$groupRow['group_id']] = $groupPrimaryLocaleTitle['setting_value'];
						}
						$groupMembershipsResult->MoveNext();
					}
					$groupMembershipsResult->Close();
					$allGroupsResult->MoveNext();
				}
				$allGroupsResult->Close();
			} else {
				// get all users by role ID
				$roleUsers = array();
				foreach ($roles as $roleId) {
					$allUsersResult = $roleDao->retrieve('SELECT DISTINCT user_id FROM roles WHERE role_id = ? AND journal_id = ?', array((int) $roleId, (int) $journal->getId()));
					while (!$allUsersResult->EOF) {
						$allUsersRow = $allUsersResult->getRowAssoc(false);
						$user = $userDao->getById($allUsersRow['user_id']);
						if ($user) $roleUsers[$roleId][] = $user;
						$allUsersResult->MoveNext();
					}
					$allUsersResult->Close();
				}
			}

			$supportedFormLocales = $journal->getSupportedFormLocales();
			$masthead = array();
			foreach ($supportedFormLocales as $locale) {
				AppLocale::requireComponents(LOCALE_COMPONENT_APP_COMMON, LOCALE_COMPONENT_PKP_USER, $locale);
				$masthead[$locale] = '';
				if ($journalSettingsDao->getSetting('boardEnabled')) {
					// The Editorial Team feature has been enabled.
					// Generate information using Group data.
					foreach ($groupUsers as $groupId => $usersArray) {
						$groupTitleResult = $roleDao->retrieve('SELECT setting_value FROM group_settings WHERE group_id = ?  AND locale = ? AND setting_name = \'title\'', array((int) $groupId, $locale));
						if ($groupTitleResult->RecordCount() == 0) {
							$groupTitle = $groupPrimaryLocaleTitles[$groupId];
						} else {
							$groupTitleRow = $groupTitleResult->getRowAssoc(false);
							$groupTitle = $groupTitleRow['setting_value'];
						}
						$masthead[$locale] .= '<h4>'.$groupTitle.'</h4>';
						foreach ($usersArray as $user) {
							$masthead[$locale] .= '<p>'.htmlspecialchars($user->getFullName());
							if ($user->getAffiliation($locale)) {
								$masthead[$locale] .= ', '.htmlspecialchars($user->getAffiliation($locale));
							}
							if ($user->getCountry()) {
								$masthead[$locale] .= ', '.htmlspecialchars($countries[$user->getCountry()]);
							}
							$masthead[$locale] .= '</p>';
						}
					}
					if (!empty($masthead[$locale])) {
						$masthead[$locale] = '<div id="group">' .$masthead[$locale] .'</div>';
					}
				} else {
					// Don't use the Editorial Team feature. Generate
					// Editorial Team information using Role info.
					foreach ($roleUsers as $roleId => $usersArray) {
						$masthead[$locale] .= '<div id="'.__($localeKeys[$roleId][1], array(), $locale).'">';
						if (count($usersArray) == 1) {
							$masthead[$locale] .= '<h3>'.__($localeKeys[$roleId][0], array(), $locale).'</h3>';
						} else {
							$masthead[$locale] .= '<h3>'.__($localeKeys[$roleId][1], array(), $locale).'</h3>';
						}
						foreach ($usersArray as $user) {
							$masthead[$locale] .= '<p>'.htmlspecialchars($user->getFullName());
							if ($user->getAffiliation($locale)) {
								$masthead[$locale] .= ', '.htmlspecialchars($user->getAffiliation($locale));
							}
							if ($user->getCountry()) {
								$masthead[$locale] .= ', '.htmlspecialchars($countries[$user->getCountry()]);
							}
							$masthead[$locale] .= '</p>';
						}
						$masthead[$locale] .= '</div>';
					}
					if (!empty($masthead[$locale])) {
						$masthead[$locale] = '<div id="editorialTeam">' .$masthead[$locale] .'</div>';
					}
				}
			}
			$journalSettingsDao->updateSetting($journal->getId(), 'editorialTeam', $masthead, 'string', true);
			unset($journal);
		}
		return true;
	}

	/**
	 * Fix galley image associations (https://github.com/pkp/pkp-lib/issues/2582)
	 * @return boolean
	 */
	function repairImageAssociations() {
		$genreDao = DAORegistry::getDAO('GenreDAO');
		$submissionDao = Application::getSubmissionDAO();
		$submissionFileDao = DAORegistry::getDAO('SubmissionFileDAO');
		$result = $submissionFileDao->retrieve('SELECT df.file_id AS dependent_file_id, gf.file_id AS galley_file_id FROM submission_files df, submission_files gf, submission_html_galley_images i, submission_galleys g WHERE i.galley_id = g.galley_id AND g.file_id = gf.file_id AND i.file_id = df.file_id');
		while (!$result->EOF) {
			$row = $result->GetRowAssoc(false);
			$submissionFiles = $submissionFileDao->getAllRevisions($row['dependent_file_id']);
			foreach ((array) $submissionFiles as $submissionFile) {
				if ($submissionFile->getFileStage() != SUBMISSION_FILE_PUBLIC) continue;

				$submission = $submissionDao->getById($submissionFile->getSubmissionId());
				$imageGenre = $genreDao->getByKey('IMAGE', $submission->getContextId());

				$submissionFile->setFileStage(SUBMISSION_FILE_DEPENDENT);
				$submissionFile->setAssocType(ASSOC_TYPE_SUBMISSION_FILE);
				$submissionFile->setAssocId($row['galley_file_id']);
				$submissionFile->setGenreId($imageGenre->getId());
				$submissionFileDao->updateObject($submissionFile);
			}
			$result->MoveNext();
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
		$submissionSubjectDao = DAORegistry::getDAO('SubmissionSubjectDAO');
		$submissionKeywordDao = DAORegistry::getDAO('SubmissionKeywordDAO');
		$submissionSubjectEntryDao = DAORegistry::getDAO('SubmissionSubjectEntryDAO');

		// insert and correct old keywords migration:
		// get old keywords
		$subjectsToKeep = array();
		$oldKeywordsFound = false;
		$result = $submissionKeywordDao->retrieve('SELECT * FROM submission_settings WHERE setting_name = \'subject\' AND setting_value <> \'\'');
		if ($result->RecordCount() > 0) $oldKeywordsFound = true;
		while (!$result->EOF) {
			$row = $result->GetRowAssoc(false);
			$submissionId = $row['submission_id'];
			$locale = $row['locale'];
			$oldKeywordsArray = preg_split('/[,;:]/', $row['setting_value']);
			$oldKeywords = array_map('trim', $oldKeywordsArray);
			// get current keywords
			$newKeywords = array();
			$newKeywordsArray = $submissionKeywordDao->getKeywords($submissionId, array($locale));
			if (array_key_exists($locale, $newKeywordsArray)) {
				$newKeywords = array_map('trim', $newKeywordsArray[$locale]);
			}
			// get the difference and insert them
			$keywordsToAdd = array_diff($oldKeywords, $newKeywords);
			if (!empty($keywordsToAdd)) {
				$submissionKeywordDao->insertKeywords(array($locale => $keywordsToAdd), $submissionId, false);
			}

			// correct the old keywords migration:
			// because the old keywords were already migrated as subjects earlier:
			// get current subjects for all possible locales, in order to also
			// consider locales other than old keywords locales (for example if added after the migration),
			// in order not to remove those when inserting below
			if (!array_key_exists($submissionId, $subjectsToKeep)) {
				$newSubjectsArray = $submissionSubjectDao->getSubjects($submissionId, $installedLocales);
				$subjectsToKeep[$submissionId] = $newSubjectsArray;
			}
			// if subjects for the current locale exist
			if (array_key_exists($locale, $subjectsToKeep[$submissionId])) {
				// get current subjects for the current locale
				$newSubjects = array_map('trim', $subjectsToKeep[$submissionId][$locale]);
				// get the difference to keep only them
				$subjectsToKeep[$submissionId][$locale] = array_diff($newSubjects, $oldKeywords);
			}
			$result->MoveNext();
		}
		$result->Close();
		unset($newSubjects);
		unset($newSubjectsArray);

		// if old keywords were found, it means that this this function is executed for the first time
		// i.e. the subjects should be corrected
		if ($oldKeywordsFound) {
			// insert the subjects that should be kept, overriding the existing ones
			// also if they are empty, because then they should be deleted
			foreach ($subjectsToKeep as $submissionId => $submissionSubjects) {
				$submissionSubjectDao->insertSubjects($submissionSubjects, $submissionId);
			}
		}

		// insert old subjects
		$result = $submissionKeywordDao->retrieve('SELECT * FROM submission_settings WHERE setting_name = \'subjectClass\' AND setting_value <> \'\'');
		while (!$result->EOF) {
			$row = $result->GetRowAssoc(false);
			$submissionId = $row['submission_id'];
			$locale = $row['locale'];
			$oldSubjectsArray = preg_split('/[,;:]/', $row['setting_value']);
			$oldSubjects = array_map('trim', $oldSubjectsArray);
			// get current subjects
			$newSubjects = array();
			$newSubjectsArray = $submissionSubjectDao->getSubjects($submissionId, array($locale));
			if (array_key_exists($locale, $newSubjectsArray)) {
				$newSubjects = array_map('trim', $newSubjectsArray[$locale]);
			}
			// get the difference and insert them
			$subjectsToAdd = array_diff($oldSubjects, $newSubjects);
			if (!empty($subjectsToAdd)) {
				$submissionSubjectDao->insertSubjects(array($locale => $subjectsToAdd), $submissionId, false);
			}
			$result->MoveNext();
		}
		$result->Close();

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
		$journalDao = DAORegistry::getDAO('JournalDAO');
		$genreDao = DAORegistry::getDAO('GenreDAO');
		$submissionDao = DAORegistry::getDAO('SubmissionDAO');
		$submissionFileDao = DAORegistry::getDAO('SubmissionFileDAO');

		import('lib.pkp.classes.file.SubmissionFileManager');

		$contexts = $journalDao->getAll();
		while ($context = $contexts->next()) {
			$styleGenre = $genreDao->getByKey('STYLE', $context->getId());
			$submissions = $submissionDao->getByContextId($context->getId());
			while ($submission = $submissions->next()) {
				$submissionFileManager = new SubmissionFileManager($context->getId(), $submission->getId());
				$basePath = $submissionFileManager->getBasePath() . '/';
				$submissionFiles = $submissionFileDao->getBySubmissionId($submission->getId());
				foreach ($submissionFiles as $submissionFile) {
					// Ignore files with style genre -- if they exist, they are corrected manually i.e.
					// the moveCSSFiles function will do this, s. https://github.com/pkp/pkp-lib/issues/2758
					if ($submissionFile->getGenreId() != $styleGenre->getId()) {
						$generatedNewFilename = $submissionFile->getServerFileName();
						$targetFilename = $basePath . $submissionFile->_fileStageToPath($submissionFile->getFileStage()) . '/' . $generatedNewFilename;
						$timestamp = date('Ymd', strtotime($submissionFile->getDateUploaded()));
						$wrongFileName = $submission->getId() . '-' . '1' . '-' . $submissionFile->getFileId() . '-' . $submissionFile->getRevision() . '-' . $submissionFile->getFileStage() . '-' . $timestamp . '.' . strtolower_codesafe($submissionFile->getExtension());
						$sourceFilename = $basePath . $submissionFile->_fileStageToPath($submissionFile->getFileStage()) . '/' . $wrongFileName;
						if (file_exists($targetFilename)) continue; // Skip existing files/links
						if (!file_exists($path = dirname($targetFilename)) && !$submissionFileManager->mkdirtree($path)) {
							error_log("Unable to make directory \"$path\"");
						}
						if (!rename($sourceFilename, $targetFilename)) {
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
		$journalDao = DAORegistry::getDAO('JournalDAO');
		$genreDao = DAORegistry::getDAO('GenreDAO');
		$submissionFileDao = DAORegistry::getDAO('SubmissionFileDAO');

		import('lib.pkp.classes.file.FileManager');
		import('lib.pkp.classes.file.SubmissionFileManager');
		import('lib.pkp.classes.submission.SubmissionFile');

		$journals = $journalDao->getAll();
		while ($journal = $journals->next()) {
			// Get style genre
			$genre = $genreDao->getByKey('STYLE', $journal->getId());

			// get CSS file names from the corrected submission_files table
			$result = $submissionFileDao->retrieve('SELECT file_id, revision, original_file_name, date_uploaded, submission_id FROM submission_files WHERE file_stage = ? AND genre_id = ? AND assoc_type = ?',
				array((int) SUBMISSION_FILE_DEPENDENT, (int) $genre->getId(), (int) ASSOC_TYPE_SUBMISSION_FILE));
			while (!$result->EOF) {
				$row = $result->GetRowAssoc(false);
				// Get the wrong file name (after the 3.0.x migration)
				// and the correct file name
				$timestamp = date('Ymd', strtotime($row['date_uploaded']));
				$fileManager = new FileManager();
				$extension = $fileManager->parseFileExtension($row['original_file_name']);
				$wrongServerName = 	$row['submission_id'] . '-' . '1' . '-' . $row['file_id'] . '-' . $row['revision'] . '-' . '1' . '-' . $timestamp . '.' . strtolower_codesafe($extension);
				$newServerName = 	$row['submission_id'] . '-' . $genre->getId() . '-' . $row['file_id'] . '-' . $row['revision'] . '-' . SUBMISSION_FILE_DEPENDENT . '-' . $timestamp . '.' . strtolower_codesafe($extension);
				// Get the old file path (after the 3.0.x migration, i.e. from OJS 2.4.x)
				// and the correct file path
				$submissionFileManager = new SubmissionFileManager($journal->getId(), $row['submission_id']);
				$basePath = $submissionFileManager->getBasePath() . '/';
				$sourceFilename = $basePath . 'public' . '/' . $wrongServerName;
				$targetFilename = $basePath . 'submission/proof' . '/' . $newServerName;
				// Move the file
				if (!file_exists($targetFilename) && file_exists($sourceFilename)) {
					if (!file_exists($path = dirname($targetFilename)) && !$submissionFileManager->mkdirtree($path)) {
						error_log("Unable to make directory \"$path\"");
					}
					if (!rename($sourceFilename, $targetFilename)) {
						error_log("Unable to move \"$sourceFilename\" to \"$targetFilename\".");
					}
				}
				$result->MoveNext();
			}
			$result->Close();
			unset($journal);
		}
		return true;
	}

	/**
	 * For 3.0.x - 3.1.1 upgrade: repair the migration of the supp files.
	 * @return boolean True indicates success.
	 */
	function repairSuppFilesFilestage() {
		$submissionFileDao = DAORegistry::getDAO('SubmissionFileDAO');

		import('lib.pkp.classes.submission.SubmissionFile');
		import('lib.pkp.classes.file.SubmissionFileManager');

		// get reviewer file ids
		$result = $submissionFileDao->retrieve(
			'SELECT ssf.*, s.context_id
			FROM submission_supplementary_files ssf, submission_files sf, submissions s
			WHERE sf.file_id = ssf.file_id AND sf.file_stage = ? AND sf.assoc_type = ? AND sf.revision = ssf.revision AND s.submission_id = sf.submission_id',
			array((int)SUBMISSION_FILE_SUBMISSION, (int)ASSOC_TYPE_REPRESENTATION)
		);
		while (!$result->EOF) {
			$row = $result->GetRowAssoc(false);
			$submissionFileRevision = $submissionFileDao->getRevision($row['file_id'], $row['revision']);
			$submissionFileManager = new SubmissionFileManager($row['context_id'], $submissionFileRevision->getSubmissionId());
			$basePath = $submissionFileManager->getBasePath() . '/';
			$generatedOldFilename = $submissionFileRevision->getServerFileName();
			$oldFileName = $basePath . $submissionFileRevision->_fileStageToPath($submissionFileRevision->getFileStage()) . '/' . $generatedOldFilename;
			$submissionFileRevision->setFileStage(SUBMISSION_FILE_PROOF);
			$generatedNewFilename = $submissionFileRevision->getServerFileName();
			$newFileName = $basePath . $submissionFileRevision->_fileStageToPath($submissionFileRevision->getFileStage()) . '/' . $generatedNewFilename;
			if (!file_exists($newFileName)) {
				if (!file_exists($path = dirname($newFileName)) && !$submissionFileManager->mkdirtree($path)) {
					error_log("Unable to make directory \"$path\"");
				}
				if (!rename($oldFileName, $newFileName)) {
					error_log("Unable to move \"$oldFileName\" to \"$newFileName\".");
				} else {
					$submissionFileDao->updateObject($submissionFileRevision);
				}
			}
			$result->MoveNext();
		}
		$result->Close();
		return true;
	}

	/**
	 * If StaticPages table exists we should port the data as NMIs
	 * @return boolean
	 */
	function migrateStaticPagesToNavigationMenuItems() {
		if ($this->tableExists('static_pages')) {
			$contextDao = Application::getContextDAO();
			$navigationMenuItemDao = DAORegistry::getDAO('NavigationMenuItemDAO');

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

		$journalSettingsDao = DAORegistry::getDAO('JournalSettingsDAO');

		// Check if the sr_SR is used, and if not do not run further
		$srExistResult = $journalSettingsDao->retrieve('SELECT COUNT(*) FROM site WHERE installed_locales LIKE ?', array('%'.$oldLocale.'%'));
		$srExist = $srExistResult->fields[0] ? true : false;
		$srExistResult->Close();
		if (!$srExist) return true;

		// Consider all DB tables that have locale column:
		$dbTables = array(
			'announcement_settings', 'announcement_type_settings', 'author_settings', 'books_for_review_settings', 'citation_settings', 'controlled_vocab_entry_settings',
			'data_object_tombstone_settings', 'email_templates_data', 'email_templates_default_data', 'external_feed_settings', 'filter_settings', 'genre_settings', 'group_settings',
			'issue_galleys', 'issue_galley_settings', 'issue_settings', 'journal_settings', 'library_file_settings', 'metadata_description_settings',
			'navigation_menu_item_assignment_settings', 'navigation_menu_item_settings', 'notification_settings', 'referral_settings',
			'review_form_element_settings', 'review_form_settings', 'review_object_metadata_settings', 'review_object_type_settings', 'rt_versions', 'section_settings', 'site_settings',
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
		while (!$settingValueResult->EOF) {
			$row = $settingValueResult->getRowAssoc(false);
			$arraySettingValue = $journalSettingsDao->getSetting($row['journal_id'], $row['setting_name']);
			for($i = 0; $i < count($arraySettingValue); $i++) {
				if ($arraySettingValue[$i] == $oldLocale) {
					$arraySettingValue[$i] = $newLocale;
				}
			}
			$journalSettingsDao->updateSetting($row['journal_id'], $row['setting_name'], $arraySettingValue);
			$settingValueResult->MoveNext();
		}
		$settingValueResult->Close();

		// Consider journal images
		// Note that the locale column values are already changed above
		$publicFileManager = new PublicFileManager();
		$settingNames = "('homeHeaderLogoImage', 'homeHeaderTitleImage', 'homepageImage', 'journalFavicon', 'journalThumbnail', 'pageHeaderLogoImage', 'pageHeaderTitleImage')";
		$settingValueResult = $journalSettingsDao->retrieve('SELECT * FROM journal_settings WHERE setting_name IN ' .$settingNames .' AND locale = ? AND setting_value LIKE ? AND setting_type = \'object\'', array($newLocale, '%' .$oldLocale .'%'));
		while (!$settingValueResult->EOF) {
			$row = $settingValueResult->getRowAssoc(false);
			$arraySettingValue = $journalSettingsDao->getSetting($row['journal_id'], $row['setting_name'], $newLocale);
			$oldUploadName = $arraySettingValue['uploadName'];
			$newUploadName = str_replace('_'.$oldLocale.'.', '_'.$newLocale.'.', $oldUploadName);
			if ($publicFileManager->fileExists($publicFileManager->getContextFilesPath($row['journal_id']) . '/' . $oldUploadName)) {
				$publicFileManager->copyContextFile($row['journal_id'], $publicFileManager->getContextFilesPath($row['journal_id']) . '/' . $oldUploadName, $newUploadName);
				$publicFileManager->removeContextFile($row['journal_id'], $oldUploadName);
			}
			$arraySettingValue['uploadName'] = $newUploadName;
			$newArraySettingValue[$newLocale] = $arraySettingValue;
			$journalSettingsDao->updateSetting($row['journal_id'], $row['setting_name'], $newArraySettingValue, 'object', true);
			$settingValueResult->MoveNext();
		}
		$settingValueResult->Close();

		// Consider issue cover images
		// Note that the locale column values are already changed above
		$settingValueResult = $journalSettingsDao->retrieve('SELECT a.*, b.journal_id FROM issue_settings a, issues b WHERE a.setting_name = \'fileName\' AND a.locale = ? AND a.setting_value LIKE ? AND a.setting_type = \'string\' AND b.issue_id = a.issue_id', array($newLocale, '%' .$oldLocale .'%'));
		while (!$settingValueResult->EOF) {
			$row = $settingValueResult->getRowAssoc(false);
			$oldCoverImage = $row['setting_value'];
			$newCoverImage = str_replace('_'.$oldLocale.'.', '_'.$newLocale.'.', $oldCoverImage);
			if ($publicFileManager->fileExists($publicFileManager->getContextFilesPath($row['journal_id']) . '/' . $oldCoverImage)) {
				$publicFileManager->copyContextFile($row['journal_id'], $publicFileManager->getContextFilesPath($row['journal_id']) . '/' . $oldCoverImage, $newCoverImage);
				$publicFileManager->removeContextFile($row['journal_id'], $oldCoverImage);
			}
			$journalSettingsDao->update('UPDATE issue_settings SET setting_value = ? WHERE issue_id = ? AND setting_name = \'fileName\' AND locale = ?', array($newCoverImage, (int) $row['issue_id'], $newLocale));
			$settingValueResult->MoveNext();
		}
		$settingValueResult->Close();

		// Consider article cover images
		// Note that the locale column values are already changed above
		$settingValueResult = $journalSettingsDao->retrieve('SELECT a.*, b.context_id FROM submission_settings a, submissions b WHERE a.setting_name = \'fileName\' AND a.locale = ? AND a.setting_value LIKE ? AND a.setting_type = \'string\' AND b.submission_id = a.submission_id', array($newLocale, '%' .$oldLocale .'%'));
		while (!$settingValueResult->EOF) {
			$row = $settingValueResult->getRowAssoc(false);
			$oldCoverImage = $row['setting_value'];
			$newCoverImage = str_replace('_'.$oldLocale.'.', '_'.$newLocale.'.', $oldCoverImage);
			if ($publicFileManager->fileExists($publicFileManager->getContextFilesPath($row['context_id']) . '/' . $oldCoverImage)) {
				$publicFileManager->copyContextFile($row['context_id'], $publicFileManager->getContextFilesPath($row['context_id']) . '/' . $oldCoverImage, $newCoverImage);
				$publicFileManager->removeContextFile($row['context_id'], $oldCoverImage);
			}
			$journalSettingsDao->update('UPDATE submission_settings SET setting_value = ? WHERE submission_id = ? AND setting_name = \'fileName\' AND locale = ?', array($newCoverImage, (int) $row['submission_id'], $newLocale));
			$settingValueResult->MoveNext();
		}
		$settingValueResult->Close();

		// plugin_settings
		// Consider array setting values from the setting names:
		// blockContent (from a custom block plugin), additionalInformation (from objects for review plugin)
		$pluginSettingsDao = DAORegistry::getDAO('PluginSettingsDAO');
		$settingNames = "('blockContent', 'additionalInformation')";
		$settingValueResult = $pluginSettingsDao->retrieve('SELECT * FROM plugin_settings WHERE setting_name IN ' .$settingNames .' AND setting_value LIKE ? AND setting_type = \'object\'', array('%' .$oldLocaleStringLength .':"' .$oldLocale .'%'));
		while (!$settingValueResult->EOF) {
			$row = $settingValueResult->getRowAssoc(false);
			$arraySettingValue = $pluginSettingsDao->getSetting($row['context_id'], $row['plugin_name'], $row['setting_name']);
			$arraySettingValue[$newLocale] = $arraySettingValue[$oldLocale];
			unset($arraySettingValue[$oldLocale]);
			$pluginSettingsDao->updateSetting($row['context_id'], $row['plugin_name'], $row['setting_name'], $arraySettingValue);
			$settingValueResult->MoveNext();
		}
		$settingValueResult->Close();

		return true;
	}

	/**
	 * Migrate first and last user names as multilingual into the DB table user_settings.
	 * @return boolean
	 */
	function migrateUserAndAuthorNames() {
		$userDao = DAORegistry::getDAO('UserDAO');
		import('lib.pkp.classes.identity.Identity'); // IDENTITY_SETTING_...
		// the user names will be saved in the site's primary locale
		$userDao->update("INSERT INTO user_settings (user_id, locale, setting_name, setting_value, setting_type) SELECT DISTINCT u.user_id, s.primary_locale, ?, u.first_name, 'string' FROM users_tmp u, site s", array(IDENTITY_SETTING_GIVENNAME));
		$userDao->update("INSERT INTO user_settings (user_id, locale, setting_name, setting_value, setting_type) SELECT DISTINCT u.user_id, s.primary_locale, ?, u.last_name, 'string' FROM users_tmp u, site s", array(IDENTITY_SETTING_FAMILYNAME));
		// the author names will be saved in the submission's primary locale
		$userDao->update("INSERT INTO author_settings (author_id, locale, setting_name, setting_value, setting_type) SELECT DISTINCT a.author_id, s.locale, ?, a.first_name, 'string' FROM authors_tmp a, submissions s WHERE s.submission_id = a.submission_id", array(IDENTITY_SETTING_GIVENNAME));
		$userDao->update("INSERT INTO author_settings (author_id, locale, setting_name, setting_value, setting_type) SELECT DISTINCT a.author_id, s.locale, ?, a.last_name, 'string' FROM authors_tmp a, submissions s WHERE s.submission_id = a.submission_id", array(IDENTITY_SETTING_FAMILYNAME));

		// middle name will be migrated to the given name
		// note that given names are already migrated to the settings table
		$driver = $userDao->getDriver();
		switch ($driver) {
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
		$siteDao = DAORegistry::getDAO('SiteDAO');
		$site = $siteDao->getSite();
		$supportedLocales = $site->getSupportedLocales();
		$userResult = $userDao->retrieve("
			SELECT user_id, first_name, last_name, middle_name, salutation, suffix FROM users_tmp
			WHERE (salutation IS NOT NULL AND salutation <> '') OR
			(suffix IS NOT NULL AND suffix <> '')
		");
		while (!$userResult->EOF) {
			$row = $userResult->GetRowAssoc(false);
			$userId = $row['user_id'];
			$firstName = $row['first_name'];
			$lastName = $row['last_name'];
			$middleName = $row['middle_name'];
			$salutation = $row['salutation'];
			$suffix = $row['suffix'];
			foreach ($supportedLocales as $siteLocale) {
				$preferredPublicName = ($salutation != '' ? "$salutation " : '') . "$firstName " . ($middleName != '' ? "$middleName " : '') . $lastName . ($suffix != '' ? ", $suffix" : '');
				if (AppLocale::isLocaleWithFamilyFirst($siteLocale)) {
					$preferredPublicName = "$lastName, " . ($salutation != '' ? "$salutation " : '') . $firstName . ($middleName != '' ? " $middleName" : '');
				}
				$params = array((int) $userId, $siteLocale, $preferredPublicName);
				$userDao->update("INSERT INTO user_settings (user_id, locale, setting_name, setting_value, setting_type) VALUES (?, ?, 'preferredPublicName', ?, 'string')", $params);
			}
			$userResult->MoveNext();
		}
		$userResult->Close();

		// author suffix will be migrated to the author preferred public name
		// author preferred public names will be inserted for each journal supported locale
		// get supported locales for all journals
		$journalDao = DAORegistry::getDAO('JournalDAO');
		$journals = $journalDao->getAll();
		$journalsSupportedLocales = array();
		while ($journal = $journals->next()) {
			$journalsSupportedLocales[$journal->getId()] = $journal->getSupportedLocales();
		}
		// get all authors with a suffix
		$authorResult = $userDao->retrieve("
			SELECT a.author_id, a.first_name, a.last_name, a.middle_name, a.suffix, j.journal_id FROM authors_tmp a
			LEFT JOIN submissions s ON (s.submission_id = a.submission_id)
			LEFT JOIN journals j ON (j.journal_id = s.context_id)
			WHERE suffix IS NOT NULL AND suffix <> ''
		");
		while (!$authorResult->EOF) {
			$row = $authorResult->GetRowAssoc(false);
			$authorId = $row['author_id'];
			$firstName = $row['first_name'];
			$lastName = $row['last_name'];
			$middleName = $row['middle_name'];
			$suffix = $row['suffix'];
			$journalId = $row['journal_id'];
			$supportedLocales = $journalsSupportedLocales[$journalId];
			foreach ($supportedLocales as $locale) {
				$preferredPublicName = "$firstName " . ($middleName != '' ? "$middleName " : '') . $lastName . ($suffix != '' ? ", $suffix" : '');
				if (AppLocale::isLocaleWithFamilyFirst($locale)) {
					$preferredPublicName = "$lastName, " . $firstName . ($middleName != '' ? " $middleName" : '');
				}
				$params = array((int) $authorId, $locale, $preferredPublicName);
				$userDao->update("INSERT INTO author_settings (author_id, locale, setting_name, setting_value, setting_type) VALUES (?, ?, 'preferredPublicName', ?, 'string')", $params);
			}
			$authorResult->MoveNext();
		}
		$authorResult->Close();

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
 		$submissionFileDao = DAORegistry::getDAO('SubmissionFileDAO');
		$metricsDao = DAORegistry::getDAO('MetricsDAO');
 		# Copy 531 assoc_type data to temp table
		$result = $metricsDao->update(
			'CREATE TABLE metrics_supp AS (SELECT * FROM metrics WHERE assoc_type = 531)'
		);
 		# Fetch submission_file data with old-supp-id
		$result = $submissionFileDao->retrieve(
			'SELECT * FROM submission_file_settings WHERE setting_name =  ?',
			'old-supp-id'
		);
 		# Loop through the data and save to temp table
		while (!$result->EOF) {
			$row = $result->GetRowAssoc(false);
 			# Use assoc_type 2531 to prevent collisions between old assoc_id and new assoc_id
			$metricsDao->update(
			'UPDATE metrics_supp SET assoc_id = ?, assoc_type = ? WHERE assoc_type = ? AND assoc_id = ?',
			array((int) $row['file_id'], 2531, 531, (int) $row['setting_value'])
			);
			$result->MoveNext();
		}
		$result->Close();
 		# update temprorary 2531 values to 531 values
		$metricsDao->update(
			'UPDATE metrics_supp SET assoc_type = ? WHERE assoc_type = ?',
			array(531, 2531)
		);
 		# delete all existing 531 values from the actual metrics table
		$metricsDao->update('DELETE FROM metrics WHERE assoc_type = 531');
 		# copy updated 531 values from metrics_supp to metrics table
		$metricsDao->update('INSERT INTO metrics SELECT * FROM metrics_supp');
 		# Drop metrics_supp table
		$metricsDao->update('DROP TABLE metrics_supp');
 		return true;
	}

	/**
	 * Add an entry for the site stylesheet to the site_settings database when it
	 * exists
	 */
	function migrateSiteStylesheet() {
		$siteDao = DAORegistry::getDAO('SiteDAO');

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

		$result = $contextDao->retrieve('SELECT * from ' . $contextDao->settingsTableName . ' WHERE setting_name="copyrightNotice"');
		while (!$result->EOF) {
			$row = $result->getRowAssoc(false);
			$contextDao->update('
				INSERT INTO ' . $contextDao->settingsTableName . ' SET
					' . $contextDao->primaryKeyColumn . ' = ?,
					locale = ?,
					setting_name = ?,
					setting_value = ?
				',
				[
					$row[$contextDao->primaryKeyColumn],
					$row['locale'],
					'licenseTerms',
					$row['setting_value'],
				]
			);
			$result->MoveNext();
		}
		$result->Close();

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

		$userGroupDao->update(
			'UPDATE user_groups 
			SET permit_metadata_edit = 1 
			WHERE role_id IN ' . $roleString
		);

		$stageAssignmentDao->update(
			'UPDATE stage_assignments sa
			JOIN user_groups ug on sa.user_group_id = ug.user_group_id
			SET sa.can_change_metadata = 1 
			WHERE ug.role_id IN ' . $roleString
		);

		return true;
	}
}
