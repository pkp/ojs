<?php

/**
 * @file classes/install/Upgrade.inc.php
 *
 * Copyright (c) 2003-2013 John Willinsky
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
	 * Fail the upgrade.
	 * @return boolean
	 */
	function abort() {
		return false;
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
	 * For 2.3.3 upgrade:  Migrate reviewing interests from free text to controlled vocab structure
	 * @return boolean
	 */
	function migrateReviewingInterests() {
		$userDao = DAORegistry::getDAO('UserDAO');
		$controlledVocabDao = DAORegistry::getDAO('ControlledVocabDAO');

		$result = $userDao->retrieve('SELECT setting_value as interests, user_id FROM user_settings WHERE setting_name = ?', 'interests');
		while (!$result->EOF) {
			$row = $result->GetRowAssoc(false);

			if(!empty($row['interests'])) {
				$userId = $row['user_id'];
				$interests = explode(',', $row['interests']);

				if (empty($interests)) $interests = array();
				elseif (!is_array($interests)) $interests = array($interests);

				$controlledVocabDao->update(
					sprintf('INSERT INTO controlled_vocabs (symbolic, assoc_type, assoc_id) VALUES (?, ?, ?)'),
					array('interest', ROLE_ID_REVIEWER, $userId)
				);
				$controlledVocabId = $controlledVocabDao->_getInsertId('controlled_vocabs', 'controlled_vocab_id');

				foreach($interests as $interest) {
					// Trim unnecessary whitespace
					$interest = trim($interest);

					$controlledVocabDao->update(
						sprintf('INSERT INTO controlled_vocab_entries (controlled_vocab_id) VALUES (?)'),
						array($controlledVocabId)
					);

					$controlledVocabEntryId = $controlledVocabDao->_getInsertId('controlled_vocab_entries', 'controlled_vocab_entry_id');

					$controlledVocabDao->update(
						sprintf('INSERT INTO controlled_vocab_entry_settings (controlled_vocab_entry_id, setting_name, setting_value, setting_type) VALUES (?, ?, ?, ?)'),
						array($controlledVocabEntryId, 'interest', $interest, 'string')
					);
				}

			}

			$result->MoveNext();
		}

		// Remove old interests from the user_setting table
		$userDao->update('DELETE FROM user_settings WHERE setting_name = ?', 'interests');

		return true;
	}

	/**
	 * For 2.4 Upgrade -- Overhaul notification structure
	 */
	function migrateNotifications() {
		$notificationDao = DAORegistry::getDAO('NotificationDAO');

		// Retrieve all notifications from pre-2.4 notifications table
		$result = $notificationDao->retrieve('SELECT * FROM notifications_old');
		while (!$result->EOF) {
			$row = $result->GetRowAssoc(false);
			$type = $row['assoc_type'];
			$url = $row['location'];

			// Get the ID of the associated object from the URL and store in $matches.
			//  This value will not be set in all cases.
			preg_match_all('/\d+/', $url, $matches);

			// Set the base data for the notification
			$notification = $notificationDao->newDataObject();
			$notification->setId($row['notification_id']);
			$notification->setUserId($row['user_id']);
			$notification->setLevel(NOTIFICATION_LEVEL_NORMAL);
			$notification->setDateCreated($notificationDao->datetimeFromDB($row['date_created']));
			$notification->setDateRead($notificationDao->datetimeFromDB($row['date_read']));
			$notification->setContextId($row['context']);
			$notification->setType($type);

			switch($type) {
				case NOTIFICATION_TYPE_COPYEDIT_COMMENT:
				case NOTIFICATION_TYPE_PROOFREAD_COMMENT:
				case NOTIFICATION_TYPE_METADATA_MODIFIED:
				case NOTIFICATION_TYPE_SUBMISSION_COMMENT:
				case NOTIFICATION_TYPE_LAYOUT_COMMENT:
				case NOTIFICATION_TYPE_SUBMISSION_SUBMITTED:
				case NOTIFICATION_TYPE_SUPP_FILE_MODIFIED:
				case NOTIFICATION_TYPE_GALLEY_MODIFIED:
				case NOTIFICATION_TYPE_REVIEWER_COMMENT:
				case NOTIFICATION_TYPE_REVIEWER_FORM_COMMENT:
				case NOTIFICATION_TYPE_EDITOR_DECISION_COMMENT:
					$id = array_pop($matches[0]);
					$notification->setAssocType(ASSOC_TYPE_ARTICLE);
					$notification->setAssocId($id);
					break;
				case NOTIFICATION_TYPE_USER_COMMENT:
					// Remove the last two elements of the array.  They refer to the
					//  galley and parent, which we no longer use
					$matches = array_slice($matches[0], -3);
					$id = array_shift($matches);
					$notification->setAssocType(ASSOC_TYPE_ARTICLE);
					$notification->setAssocId($id);
					$notification->setType(NOTIFICATION_TYPE_USER_COMMENT);
					break;
				case NOTIFICATION_TYPE_PUBLISHED_ISSUE:
					// We do nothing here, as our URL points to the current issue
					break;
				case NOTIFICATION_TYPE_NEW_ANNOUNCEMENT:
					$id = array_pop($matches[0]);
					$notification->setAssocType(ASSOC_TYPE_ANNOUNCEMENT);
					$notification->setAssocId($id);
					$notification->setType(NOTIFICATION_TYPE_NEW_ANNOUNCEMENT);
					break;
			}

			$notificationDao->update(
				sprintf('INSERT INTO notifications
						(user_id, level, date_created, date_read, context_id, type, assoc_type, assoc_id)
					VALUES
						(?, ?, %s, %s, ?, ?, ?, ?)',
					$notificationDao->datetimeToDB($notification->getDateCreated()), $notificationDao->datetimeToDB($notification->getDateRead())),
				array(
					(int) $notification->getUserId(),
					(int) $notification->getLevel(),
					(int) $notification->getContextId(),
					(int) $notification->getType(),
					(int) $notification->getAssocType(),
					(int) $notification->getAssocId()
				)
			);
			unset($notification);
			$result->MoveNext();
		}

		$result->Close();
		unset($result);


		// Retrieve all settings from pre-2.4 notification_settings table
		$result = $notificationDao->retrieve('SELECT * FROM notification_settings_old');
		while (!$result->EOF) {
			$row = $result->GetRowAssoc(false);
			$settingName = $row['setting_name'];
			$contextId = $row['context'];

			switch ($settingName) {
				case 'email':
				case 'notify':
					$notificationType = $row['setting_value'];
					$newSettingName = ($settingName == 'email' ? 'emailed_notification' : 'blocked_notification');
					$userId = $row['user_id'];

					$notificationDao->update(
						'INSERT INTO notification_subscription_settings
							(setting_name, setting_value, user_id, context, setting_type)
							VALUES
							(?, ?, ?, ?, ?)',
						array(
							$newSettingName,
							(int) $notificationType,
							(int) $userId,
							(int) $contextId,
							'int'
						)
					);
					break;
				case 'mailList':
				case 'mailListUnconfirmed':
					$confirmed = ($settingName == 'mailList') ? 1 : 0;
					$email = $row['setting_value'];
					$settingId = $row['setting_id'];

					// Get the token from the access_keys table
					$accessKeyDao = DAORegistry::getDAO('AccessKeyDAO'); /* @var $accessKeyDao AccessKeyDAO */
					$accessKey = $accessKeyDao->getAccessKeyByUserId('MailListContext', $settingId);
					if(!$accessKey) continue;
					$token = $accessKey->getKeyHash();

					// Delete the access key -- we don't need it anymore
					$accessKeyDao->deleteObject($accessKey);

					$notificationDao->update(
						'INSERT INTO notification_mail_list
							(email, context, token, confirmed)
							VALUES
							(?, ?, ?, ?)',
						array(
							$email,
							(int) $contextId,
							$token,
							$confirmed
						)
					);
					break;
			}

			$result->MoveNext();
		}

		$result->Close();
		unset($result);

		return true;
	}


	/**
	 * For 2.3.7 Upgrade -- Remove author revised file upload IDs erroneously added to copyedit signoff
	 */
	function removeAuthorRevisedFilesFromSignoffs() {
		import('classes.article.Article');
		$signoffDao = DAORegistry::getDAO('SignoffDAO'); /* @var $signoffDao SignoffDAO */

		$result = $signoffDao->retrieve(
			'SELECT DISTINCT s.signoff_id
			FROM articles a
				JOIN signoffs s ON (a.article_id = s.assoc_id)
			WHERE s.symbolic = ?
				AND s.file_id = a.revised_file_id',
			'SIGNOFF_COPYEDITING_INITIAL'
		);

		while (!$result->EOF) {
			$row = $result->GetRowAssoc(false);

			$signoff = $signoffDao->getById($row['signoff_id']); /* @var $signoff Signoff */
			$signoff->setFileId(null);
			$signoff->setFileRevision(null);
			$signoffDao->updateObject($signoff);

			$result->MoveNext();
		}

		return true;
	}

	/*
	 * For 2.3.7 upgrade: Improve reviewing interests storage structure
	 * @return boolean
	 */
	function migrateReviewingInterests2() {
		$interestDao = DAORegistry::getDAO('InterestDAO'); /* @var $interestDao InterestDAO */
		$interestEntryDao = DAORegistry::getDAO('InterestEntryDAO'); /* @var $interestEntryDao InterestEntryDAO */

		// Check if this upgrade method has already been run to prevent data corruption on subsequent upgrade attempts
		$idempotenceCheck = $interestDao->retrieve('SELECT * FROM controlled_vocabs cv WHERE symbolic = ?', array('interest'));
		$row = $idempotenceCheck->GetRowAssoc(false);
		if ($idempotenceCheck->RecordCount() == 1 && $row['assoc_id'] == 0 && $row['assoc_type'] == 0) return true;
		unset($idempotenceCheck);

		// Get all interests for all users
		$result = $interestDao->retrieve(
			'SELECT DISTINCT cves.setting_value as interest_keyword,
				cv.assoc_id as user_id
			FROM	controlled_vocabs cv
				LEFT JOIN controlled_vocab_entries cve ON (cve.controlled_vocab_id = cv.controlled_vocab_id)
				LEFT JOIN controlled_vocab_entry_settings cves ON (cves.controlled_vocab_entry_id = cve.controlled_vocab_entry_id)
			WHERE	cv.symbolic = ?',
			array('interest')
		);

		$oldEntries = $interestDao->retrieve(
			'SELECT	controlled_vocab_entry_id
			FROM	controlled_vocab_entry_settings cves
			WHERE	cves.setting_name = ?',
			array('interest')
		);
		while (!$oldEntries->EOF) {
			$row = $oldEntries->GetRowAssoc(false);
			$controlledVocabEntryId = (int) $row['controlled_vocab_entry_id'];
			$interestDao->update('DELETE FROM controlled_vocab_entries WHERE controlled_vocab_entry_id = ?', $controlledVocabEntryId);
			$interestDao->update('DELETE FROM controlled_vocab_entry_settings WHERE controlled_vocab_entry_id = ?', $controlledVocabEntryId);
			$oldEntries->MoveNext();
		}
		$oldEntries->Close();
		unset($oldEntries);

		$controlledVocab = $interestDao->build();

		// Insert the user interests using the new storage structure
		while (!$result->EOF) {
			$row = $result->GetRowAssoc(false);

			$userId = (int) $row['user_id'];
			$interest = $row['interest_keyword'];

			$interestEntry = $interestEntryDao->getBySetting($interest, $controlledVocab->getSymbolic(),
				$controlledVocab->getAssocId(), $controlledVocab->getAssocType(),
				$controlledVocab->getSymbolic()
			);

			if(!$interestEntry) {
				$interestEntry = $interestEntryDao->newDataObject(); /* @var $interestEntry InterestEntry */
				$interestEntry->setInterest($interest);
				$interestEntry->setControlledVocabId($controlledVocab->getId());
				$interestEntryDao->insertObject($interestEntry);
			}

			$interestEntryDao->update(
				'INSERT INTO user_interests (user_id, controlled_vocab_entry_id) VALUES (?, ?)',
				array($userId, (int) $interestEntry->getId())
			);

			$result->MoveNext();
		}
		$result->Close();
		unset($result);

		// Remove the obsolete interest data
		$interestDao->update('DELETE FROM controlled_vocabs WHERE symbolic = ?  AND assoc_type > 0', array('interest'));

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

		$journals = $journalDao->getJournals();
		while ($journal = $journals->next()) {
			// for languages, we depend on the journal locale settings since languages are not localized.
			// Use Journal locales, or primary if no defined submission locales.
			$supportedLocales = $journal->getSupportedSubmissionLocales();

			if (empty($supportedLocales)) $supportedLocales = array($journal->getPrimaryLocale());
			else if (!is_array($supportedLocales)) $supportedLocales = array($supportedLocales);

			$result = $articleDao->retrieve('SELECT a.article_id FROM articles a WHERE a.journal_id = ?', array((int)$journal->getId()));
			while (!$result->EOF) {
				$row = $result->GetRowAssoc(false);
				$articleId = (int)$row['article_id'];
				$settings = array();
				$settingResult = $articleDao->retrieve('SELECT setting_value, setting_name, locale FROM article_settings WHERE article_id = ? AND (setting_name = \'discipline\' OR setting_name = \'subject\' OR setting_name = \'sponsor\');', array((int)$articleId));
				while (!$settingResult->EOF) {
					$settingRow = $settingResult->GetRowAssoc(false);
					$locale = $settingRow['locale'];
					$settingName = $settingRow['setting_name'];
					$settingValue = $settingRow['setting_value'];
					$settings[$settingName][$locale] = $settingValue;
					$settingResult->MoveNext();
				}
				$settingResult->Close();

				$languageResult = $articleDao->retrieve('SELECT language FROM articles WHERE article_id = ?', array((int)$articleId));
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

		// First, do Admins.
		$userGroupDao = DAORegistry::getDAO('UserGroupDAO');
		// create the admin user group.
		$userGroupDao->update('INSERT INTO user_groups (user_group_id, context_id, role_id, path, is_default) VALUES (?, ?, ?, ?, ?)', array(1, 0, ROLE_ID_SITE_ADMIN, 'admin', 1));
		$userResult = $userGroupDao->retrieve('SELECT user_id FROM roles WHERE journal_id = ? AND role_id = ?', array(0, ROLE_ID_SITE_ADMIN));
		while (!$userResult->EOF) {
			$row = $userResult->GetRowAssoc(false);
			$userGroupDao->update('INSERT INTO user_user_groups (user_group_id, user_id) VALUES (?, ?)', array(1, (int) $row['user_id']));
			$userResult->MoveNext();
		}

		// iterate through all journals and assign remaining users to their respective groups.
		$journalDao = DAORegistry::getDAO('JournalDAO');
		$journals = $journalDao->getJournals();

		AppLocale::requireComponents(LOCALE_COMPONENT_APP_DEFAULT, LOCALE_COMPONENT_PKP_DEFAULT);

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
			while ($group = $userGroups->next()) {
				// make sure.
				if ($group->getRoleId() != ROLE_ID_REVIEWER) continue;
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

			while ($group = $userGroups->next()) {
				if ($group->getData('nameLocaleKey') == 'default.groups.name.editor') {
					$userResult = $journalDao->retrieve('SELECT user_id FROM roles WHERE journal_id = ? AND role_id = ?', array((int) $journal->getId(), ROLE_ID_EDITOR));
					while (!$userResult->EOF) {
						$row = $userResult->GetRowAssoc(false);
						$userGroupDao->assignUserToGroup($row['user_id'], $group->getId());
						$userResult->MoveNext();
					}
				}
			}

			// Section Editors.
			$group = $userGroupDao->getDefaultByRoleId($journal->getId(), ROLE_ID_SECTION_EDITOR);
			$userResult = $journalDao->retrieve('SELECT DISTINCT user_id FROM section_editors WHERE journal_id = ?', array((int) $journal->getId()));;
			while (!$userResult->EOF) {
				$row = $userResult->GetRowAssoc(false);
				$userGroupDao->assignUserToGroup($row['user_id'], $group->getId());
				$userResult->MoveNext();
			}

			// Layout Editors. NOTE:  this involves a role id change from 0x300 to 0x1001 (old OJS _LAYOUT_EDITOR to PKP-lib _ASSISTANT).
			$userGroups = $userGroupDao->getByRoleId($journal->getId(), ROLE_ID_ASSISTANT);
			while ($group = $userGroups->next()) {
				if ($group->getData('nameLocaleKey') == 'default.groups.name.layoutEditor') {
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
			while ($group = $userGroups->next()) {
				if ($group->getData('nameLocaleKey') == 'default.groups.name.copyeditor') {
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
			while ($group = $userGroups->next()) {
				if ($group->getData('nameLocaleKey') == 'default.groups.name.proofreader') {
					$userResult = $journalDao->retrieve('SELECT user_id FROM roles WHERE journal_id = ? AND role_id = ?', array((int) $journal->getId(), ROLE_ID_PROOFREADER));
					while (!$userResult->EOF) {
						$row = $userResult->GetRowAssoc(false);
						$userGroupDao->assignUserToGroup($row['user_id'], $group->getId());
						$userResult->MoveNext();
					}
				}
			}
		}
		return true;
	}
}

?>
