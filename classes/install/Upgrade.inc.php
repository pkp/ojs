<?php

/**
 * @file classes/install/Upgrade.inc.php
 *
 * Copyright (c) 2003-2008 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class Upgrade
 * @ingroup install
 *
 * @brief Perform system upgrade.
 */

// $Id$


import('install.Installer');

class Upgrade extends Installer {

	/**
	 * Constructor.
	 * @param $params array upgrade parameters
	 */
	function Upgrade($params) {
		parent::Installer('upgrade.xml', $params);
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
		import('search.ArticleSearchIndex');
		ArticleSearchIndex::rebuildIndex();
		return true;
	}

	/**
	 * For upgrade to 2.1.1: Designate original versions as review versions
	 * in all cases where review versions aren't designated. (#2144)
	 * @return boolean
	 */
	function designateReviewVersions() {
		$journalDao =& DAORegistry::getDAO('JournalDAO');
		$articleDao =& DAORegistry::getDAO('ArticleDAO');
		$authorSubmissionDao =& DAORegistry::getDAO('AuthorSubmissionDAO');
		import('submission.author.AuthorAction');

		$journals =& $journalDao->getJournals();
		while ($journal =& $journals->next()) {
			$articles =& $articleDao->getArticlesByJournalId($journal->getJournalId());
			while ($article =& $articles->next()) {
				if (!$article->getReviewFileId() && $article->getSubmissionProgress() == 0) {
					$authorSubmission =& $authorSubmissionDao->getAuthorSubmission($article->getArticleId());
					AuthorAction::designateReviewVersion($authorSubmission, true);
				}
				unset($article);
			}
			unset($journal);
		}
		return true;
	}

	/**
	 * For upgrade to 2.1.1: Migrate the RT settings from the rt_settings
	 * table to journal settings and drop the rt_settings table.
	 * @return boolean
	 */
	function migrateRtSettings() {
		$rtDao =& DAORegistry::getDAO('RTDAO');
		$journalDao =& DAORegistry::getDAO('JournalDAO');

		// Bring in the comments constants.
		$commentDao = &DAORegistry::getDao('CommentDAO');

		$result =& $rtDao->retrieve('SELECT * FROM rt_settings');
		while (!$result->EOF) {
			$row = $result->GetRowAssoc(false);
			$journalId = $row['journal_id'];
			$journal =& $journalDao->getJournal($journalId);
			$rt =& new JournalRT($journalId);
			$rt->setEnabled(true); // No toggle in prior OJS; assume true
			$rt->setVersion($row['version_id']);
			$rt->setAbstract(true); // No toggle in prior OJS; assume true
			$rt->setCaptureCite($row['capture_cite']);
			$rt->setViewMetadata($row['view_metadata']);
			$rt->setSupplementaryFiles($row['supplementary_files']);
			$rt->setPrinterFriendly($row['printer_friendly']);
			$rt->setAuthorBio($row['author_bio']);
			$rt->setDefineTerms($row['define_terms']);

			$journal->updateSetting('enableComments', $row['add_comment']?COMMENTS_AUTHENTICATED:COMMENTS_DISABLED);

			$rt->setEmailAuthor($row['email_author']);
			$rt->setEmailOthers($row['email_others']);
			$rtDao->updateJournalRT($rt);
			unset($rt);
			unset($journal);
			$result->MoveNext();
		}
		$result->Close();
		unset($result);

		// Drop the table once all settings are migrated.
		$rtDao->update('DROP TABLE rt_settings');
		return true;
	}

	/**
	 * For upgrade to OJS 2.2.0: Migrate the currency settings so the
	 * currencies table can be dropped in favour of XML.
	 * @return boolean
	 */
	function correctCurrencies() {
		$currencyDao =& DAORegistry::getDAO('CurrencyDAO');
		$result =& $currencyDao->retrieve('SELECT st.type_id AS type_id, c.code_alpha AS code_alpha FROM subscription_types st LEFT JOIN currencies c ON (c.currency_id = st.currency_id)');
		while (!$result->EOF) {
			$row = $result->GetRowAssoc(false);
			$currencyDao->update('UPDATE subscription_types SET currency_code_alpha = ? WHERE type_id = ?', array($row['code_alpha'], $row['type_id']));
			$result->MoveNext();
		}
		unset($result);
		return true;
	}

	/**
	 * For upgrade to 2.2.0: Migrate the issue label column and values to the new
	 * show volume, show number, etc. columns and values. Migrate the publication
	 * format settings for the journal to the new issue label format. (#2291)
	 * @return boolean
	 */
	function migrateIssueLabelAndSettings() {
		// First, migrate label_format values in issues table.
		$issueDao =& DAORegistry::getDAO('IssueDAO');
		$issueDao->update('UPDATE issues SET show_volume=0, show_number=0, show_year=0, show_title=1 WHERE label_format=4'); // ISSUE_LABEL_TITLE
		$issueDao->update('UPDATE issues SET show_volume=0, show_number=0, show_year=1, show_title=0 WHERE label_format=3'); // ISSUE_LABEL_YEAR
		$issueDao->update('UPDATE issues SET show_volume=1, show_number=0, show_year=1, show_title=0 WHERE label_format=2'); // ISSUE_LABEL_VOL_YEAR
		$issueDao->update('UPDATE issues SET show_volume=1, show_number=1, show_year=1, show_title=0 WHERE label_format=1'); // ISSUE_LABEL_NUM_VOL_YEAR

		// Drop the old label_format column once all values are migrated.
		$issueDao->update('ALTER TABLE issues DROP COLUMN label_format');

		// Migrate old publicationFormat journal setting to new journal settings. 
		$journalDao =& DAORegistry::getDAO('JournalDAO');
		$journalSettingsDao =& DAORegistry::getDAO('JournalSettingsDAO');
		$result =& $journalDao->retrieve('SELECT j.journal_id AS journal_id, js.setting_value FROM journals j LEFT JOIN journal_settings js ON (js.journal_id = j.journal_id AND js.setting_name = ?)', 'publicationFormat');
		while (!$result->EOF) {
			$row = $result->GetRowAssoc(false);
			$settings = array(
				'publicationFormatVolume' => false,
				'publicationFormatNumber' => false,
				'publicationFormatYear' => false,
				'publicationFormatTitle' => false
			);
			switch ($row['setting_value']) {
				case 4: // ISSUE_LABEL_TITLE
					$settings['publicationFormatTitle'] = true;
					break;
				case 3: // ISSUE_LABEL_YEAR
					$settings['publicationFormatYear'] = true;
					break;
 				case 2: // ISSUE_LABEL_VOL_YEAR 		
					$settings['publicationFormatVolume'] = true;
					$settings['publicationFormatYear'] = true;
					break;
				case 1: // ISSUE_LABEL_NUM_VOL_YEAR
				default:
					$settings['publicationFormatVolume'] = true;
					$settings['publicationFormatNumber'] = true;
					$settings['publicationFormatYear'] = true;
			}
			foreach ($settings as $name => $value) {
				$journalDao->update('INSERT INTO journal_settings (journal_id, setting_name, setting_value, setting_type) VALUES (?, ?, ?, ?)', array($row['journal_id'], $name, $value?1:0, 'bool'));
			}
			$result->MoveNext();
		}
		$result->Close();
		unset($result);

		$journalDao->update('DELETE FROM journal_settings WHERE setting_name = ?', array('publicationFormat'));

		return true;
	}

	/**
	 * For upgrade to 2.2: Move primary_locale from journal settings into
	 * dedicated column.
	 * @return boolean
	 */
	function setJournalPrimaryLocales() {
		$journalDao =& DAORegistry::getDAO('JournalDAO');
		$journalSettingsDao =& DAORegistry::getDAO('JournalSettingsDAO');

		$result =& $journalSettingsDao->retrieve('SELECT journal_id, setting_value FROM journal_settings WHERE setting_name = ?', array('primaryLocale'));
		while (!$result->EOF) {
			$row = $result->GetRowAssoc(false);
			$journalDao->update('UPDATE journals SET primary_locale = ? WHERE journal_id = ?', array($row['setting_value'], $row['journal_id']));
			$result->MoveNext();
		}
		$journalDao->update('UPDATE journals SET primary_locale = ? WHERE primary_locale IS NULL OR primary_locale = ?', array(INSTALLER_DEFAULT_LOCALE, ''));
		$result->Close();
		return true;
	}

	/**
	 * For upgrade to 2.2: Install default settings for block plugins.
	 * @return boolean
	 */
	function installBlockPlugins() {
		$pluginSettingsDao =& DAORegistry::getDAO('PluginSettingsDAO');
		$journalDao =& DAORegistry::getDAO('JournalDAO');
		$journals =& $journalDao->getJournals();

		// Get journal IDs for insertion, including 0 for site-level
		$journalIds = array(0);
		while ($journal =& $journals->next()) {
			$journalIds[] = $journal->getJournalId();
			unset($journal);
		}

		$pluginNames = array(
			'DevelopedByBlockPlugin',
			'HelpBlockPlugin',
			'UserBlockPlugin',
			'LanguageToggleBlockPlugin',
			'NavigationBlockPlugin',
			'FontSizeBlockPlugin',
			'InformationBlockPlugin'
		);
		foreach ($journalIds as $journalId) {
			$i = 0;
			foreach ($pluginNames as $pluginName) {
				$pluginSettingsDao->updateSetting($journalId, $pluginName, 'enabled', 'true', 'bool');
				$pluginSettingsDao->updateSetting($journalId, $pluginName, 'seq', $i++, 'int');
				$pluginSettingsDao->updateSetting($journalId, $pluginName, 'context', BLOCK_CONTEXT_RIGHT_SIDEBAR, 'int');
			}
		}

		return true;
	}

	/**
	 * Clear the data cache files (needed because of direct tinkering
	 * with settings tables)
	 * @return boolean
	 */
	function clearDataCache() {
		import('cache.CacheManager');
		$cacheManager =& CacheManager::getManager();
		$cacheManager->flush();
		return true;
	}

	/**
	 * For 2.2 upgrade: add locale data to existing journal settings that
	 * were not previously localized.
	 * @return boolean
	 */
	function localizeJournalSettings() {
		$journalSettingsDao =& DAORegistry::getDAO('JournalSettingsDAO');
		$journalDao =& DAORegistry::getDAO('JournalDAO');

		$settingNames = array(
			// Setup page 1
			'title' => 'title',
			'journalInitials' => 'initials', // Rename
			'journalAbbreviation' => 'abbreviation', // Rename
			'sponsorNote' => 'sponsorNote',
			'publisherNote' => 'publisherNote',
			'contributorNote' => 'contributorNote',
			'searchDescription' => 'searchDescription',
			'searchKeywords' => 'searchKeywords',
			'customHeaders' => 'customHeaders',
			// Setup page 2
			'focusScopeDesc' => 'focusScopeDesc',
			'reviewPolicy' => 'reviewPolicy',
			'reviewGuidelines' => 'reviewGuidelines',
			'privacyStatement' => 'privacyStatement',
			'customAboutItems' => 'customAboutItems',
			'lockssLicense' => 'lockssLicense',
			// Setup page 3
			'authorGuidelines' => 'authorGuidelines',
			'submissionChecklist' => 'submissionChecklist',
			'copyrightNotice' => 'copyrightNotice',
			'metaDisciplineExamples' => 'metaDisciplineExamples',
			'metaSubjectClassTitle' => 'metaSubjectClassTitle',
			'metaSubjectClassUrl' => 'metaSubjectClassUrl',
			'metaSubjectExamples' => 'metaSubjectExamples',
			'metaCoverageGeoExamples' => 'metaCoverageGeoExamples',
			'metaCoverageChronExamples' => 'metaCoverageChronExamples',
			'metaCoverageResearchSampleExamples' => 'metaCoverageResearchSampleExamples',
			'metaTypeExamples' => 'metaTypeExamples',
			// Setup page 4
			'pubFreqPolicy' => 'pubFreqPolicy',
			'copyeditInstructions' => 'copyeditInstructions',
			'layoutInstructions' => 'layoutInstructions',
			'proofInstructions' => 'proofInstructions',
			'openAccessPolicy' => 'openAccessPolicy',
			'announcementsIntroduction' => 'announcementsIntroduction',
			// Setup page 5
			'homeHeaderTitleType' => 'homeHeaderTitleType',
			'homeHeaderTitle' => 'homeHeaderTitle',
			'homeHeaderTitleImage' => 'homeHeaderTitleImage',
			'pageHeaderTitleType' => 'pageHeaderTitleType',
			'pageHeaderTitle' => 'pageHeaderTitle',
			'pageHeaderTitleImage' => 'pageHeaderTitleImage',
			'homepageImage' => 'homepageImage',
			'readerInformation' => 'readerInformation',
			'authorInformation' => 'authorInformation',
			'librarianInformation' => 'librarianInformation',
			'journalPageHeader' => 'journalPageHeader',
			'journalPageFooter' => 'journalPageFooter',
			'additionalHomeContent' => 'additionalHomeContent',
			'description' => 'description',
			'navItems' => 'navItems'
		);

		foreach ($settingNames as $oldName => $newName) {
			$result =& $journalDao->retrieve('SELECT j.journal_id, j.primary_locale FROM journals j, journal_settings js WHERE j.journal_id = js.journal_id AND js.setting_name = ? AND (js.locale IS NULL OR js.locale = ?)', array($oldName, ''));
			while (!$result->EOF) {
				$row = $result->GetRowAssoc(false);
				$journalSettingsDao->update('UPDATE journal_settings SET locale = ?, setting_name = ? WHERE journal_id = ? AND setting_name = ? AND (locale IS NULL OR locale = ?)', array($row['primary_locale'], $newName, $row['journal_id'], $oldName, ''));
				$result->MoveNext();
			}
			$result->Close();
			unset($result);
		}

		return true;
	}

	/**
	 * For 2.2 upgrade: Migrate the "publisher" setting from a serialized
	 * array into three localized settings: publisherUrl, publisherNote,
	 * and publisherInstitution.
	 * @return boolean
	 */
	function migratePublisher() {
		$journalSettingsDao =& DAORegistry::getDAO('JournalSettingsDAO');

		$result =& $journalSettingsDao->retrieve('SELECT j.primary_locale, s.setting_value, j.journal_id FROM journal_settings s, journals j WHERE s.journal_id = j.journal_id AND s.setting_name = ?', array('publisher'));
		while (!$result->EOF) {
			$row = $result->GetRowAssoc(false);
			$publisher = null;
			$publisher = @unserialize($row['setting_value']);

			foreach (array('note' => 'publisherNote', 'institution' => 'publisherInstitution', 'url' => 'publisherUrl') as $old => $new) {
				if (isset($publisher[$old])) $journalSettingsDao->update(
					'INSERT INTO journal_settings (journal_id, setting_name, setting_value, setting_type, locale) VALUES (?, ?, ?, ?, ?)',
					array(
						$row['journal_id'],
						$new, $publisher[$old],
						'string',
						($new == 'publisherNote'?$row['primary_locale']:'')
					)
				);
			}

			$result->MoveNext();
		}
		$result->Close();
		unset($result);

		$journalSettingsDao->update('DELETE FROM journal_settings WHERE setting_name = ?', 'publisher');

		return true;
	}

	/**
	 * For 2.2 upgrade: Set locales for galleys.
	 * @return boolean
	 */
	function setGalleyLocales() {
		$articleGalleyDao =& DAORegistry::getDAO('ArticleGalleyDAO');
		$journalDao =& DAORegistry::getDAO('JournalDAO');

		$result =& $journalDao->retrieve('SELECT g.galley_id, j.primary_locale FROM journals j, articles a, article_galleys g WHERE a.journal_id = j.journal_id AND g.article_id = a.article_id AND (g.locale IS NULL OR g.locale = ?)', '');
		while (!$result->EOF) {
			$row = $result->GetRowAssoc(false);
			$articleGalleyDao->update('UPDATE article_galleys SET locale = ? WHERE galley_id = ?', array($row['primary_locale'], $row['galley_id']));
			$result->MoveNext();
		}
		$result->Close();
		unset($result);

		return true;
	}

	/**
	 * For 2.2 upgrade: Add IP ranges for all journal subscriptions.
	 * @return boolean
	 */
	function addSubscriptionIPRanges() {
		$subscriptionDao =& DAORegistry::getDAO('SubscriptionDAO');
		$subscriptions =& $subscriptionDao->getSubscriptions();

		while ($subscription =& $subscriptions->next()) {
			$subscriptionDao->insertSubscriptionIPRange($subscription->getSubscriptionId(), $subscription->getIPRange());
			unset($subscription);
		}

		return true;
	}

	/**
	 * For 2.2 upgrade: user_settings table has been renamed in order to
	 * apply the schema changes for localization. Migrate the settings from
	 * user_settings_old to user_settings now that the new schema has been
	 * applied.
	 */
	function migrateUserSettings() {
		$userSettingsDao =& DAORegistry::getDAO('UserSettingsDAO');

		$result =& $userSettingsDao->retrieve('SELECT user_id, setting_name, journal_id, setting_value, setting_type FROM user_settings_old');
		while (!$result->EOF) {
			$row = $result->GetRowAssoc(false);
			$userSettingsDao->update('INSERT INTO user_settings (user_id, setting_name, journal_id, setting_value, setting_type, locale) VALUES (?, ?, ?, ?, ?, ?)', array($row['user_id'], $row['setting_name'], $row['journal_id'], $row['setting_value'], $row['setting_type'], ''));
			$result->MoveNext();
		}
		$result->Close();
		unset($result);

		return true;
	}

	/**
	 * For 2.2 upgrade: index handling changed away from using the <KEY />
	 * syntax in schema descriptors in cases where AUTONUM columns were not
	 * used, in favour of specifically-named indexes using the <index ...>
	 * syntax. For this, all indexes (including potentially duplicated
	 * indexes from before) on OJS tables should be dropped prior to the new
	 * schema being applied.
	 * @return boolean
	 */
	function dropAllIndexes() {
		$siteDao =& DAORegistry::getDAO('SiteDAO');
		$dict = NewDataDictionary($siteDao->_dataSource);
		$dropIndexSql = array();

		// This is a list of tables that were used in 2.1.1 (i.e.
		// before the way indexes were used was changed). All indexes
		// from these tables will be dropped.
		$tables = array(
			'versions', 'site', 'site_settings', 'scheduled_tasks',
			'sessions', 'journal_settings',
			'plugin_settings', 'roles', 'notification_status', 
			'section_settings', 'section_editors', 'issue_settings',
			'custom_issue_orders', 'custom_section_orders',
			'article_settings', 'article_author_settings',
			'article_supp_file_settings', 'review_rounds',
			'article_html_galley_images',
			'email_templates_default_data', 'email_templates_data',
			'article_search_object_keywords',
			'oai_resumption_tokens', 'subscription_type_settings',
			'announcement_type_settings', 'announcement_settings',
			'group_settings', 'group_memberships'
		);

		// Assemble a list of indexes to be dropped
		foreach ($tables as $tableName) {
			$indexes = $dict->MetaIndexes($tableName);
			if (is_array($indexes)) foreach ($indexes as $indexName => $indexData) {
				$dropIndexSql = array_merge($dropIndexSql, $dict->DropIndexSQL($indexName, $tableName));
			}
		}

		// Execute the DROP INDEX statements.
		foreach ($dropIndexSql as $sql) {
			$siteDao->update($sql);
		}

		// Second run: Only return primary indexes. This is necessary
		// so that primary indexes can be dropped by MySQL.
		foreach ($tables as $tableName) {
			$indexes = $dict->MetaIndexes($tableName, true);
			if (!empty($indexes)) switch(Config::getVar('database', 'driver')) {
				case 'mysql':
					$siteDao->update("ALTER TABLE $tableName DROP PRIMARY KEY");
					break;
			}
		}


		return true;
	}

	/**
	 * The supportedLocales setting may be missing for journals; ensure
	 * that it is properly set.
	 */
	function ensureSupportedLocales() {
		$journalDao =& DAORegistry::getDAO('JournalDAO');
		$journalSettingsDao =& DAORegistry::getDAO('JournalSettingsDAO');
		$result =& $journalDao->retrieve(
			'SELECT	j.journal_id,
				j.primary_locale
			FROM	journals j
				LEFT JOIN journal_settings js ON (js.journal_id = j.journal_id AND js.setting_name = ?)
			WHERE	js.setting_name IS NULL',
			array('supportedLocales')
		);
		while (!$result->EOF) {
			$row = $result->GetRowAssoc(false);
			$journalSettingsDao->updateSetting(
				$row['journal_id'],
				'supportedLocales',
				array($row['primary_locale']),
				'object',
				false
			);
			$result->MoveNext();
		}
		$result->Close();
		unset($result);
		return true;
	}

	/**
	 * For 2.2.1 upgrade: Replace "payPerView" to "purchaseArticle" in settings. 
	 * @return boolean
	 */
	function renamePayPerViewSettings() {
		$journalSettingsDao =& DAORegistry::getDAO('JournalSettingsDAO');
		$journalDao =& DAORegistry::getDAO('JournalDAO');

		$settingNames = array(
			'payPerViewFeeEnabled' => 'purchaseArticleFeeEnabled',
			'payPerViewFee' => 'purchaseArticleFee',
			'payPerViewFeeName' => 'purchaseArticleFeeName',
			'payPerViewFeeDescription' => 'purchaseArticleFeeDescription'
		);

		foreach ($settingNames as $oldName => $newName) {
			$journalSettingsDao->update('UPDATE journal_settings SET setting_name = ? WHERE setting_name = ?', array($newName, $oldName));
		}

		return true;
	}
}

?>
