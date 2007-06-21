<?php

/**
 * Upgrade.inc.php
 *
 * Copyright (c) 2003-2007 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package install
 *
 * Perform system upgrade.
 *
 * $Id$
 */

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
	 */
	function migrateRtSettings() {
		$rtDao =& DAORegistry::getDAO('RTDAO');
		$result =& $rtDao->retrieve('SELECT * FROM rt_settings');
		while (!$result->EOF) {
			$row = $result->GetRowAssoc(false);
			$rt =& new JournalRT($row['journal_id']);
			$rt->setEnabled(true); // No toggle in prior OJS; assume true
			$rt->setVersion($row['version_id']);
			$rt->setAbstract(true); // No toggle in prior OJS; assume true
			$rt->setCaptureCite($row['capture_cite']);
			$rt->setViewMetadata($row['view_metadata']);
			$rt->setSupplementaryFiles($row['supplementary_files']);
			$rt->setPrinterFriendly($row['printer_friendly']);
			$rt->setAuthorBio($row['author_bio']);
			$rt->setDefineTerms($row['define_terms']);
			$rt->setAddComment($row['add_comment']);
			$rt->setEmailAuthor($row['email_author']);
			$rt->setEmailOthers($row['email_others']);
			$rtDao->updateJournalRT($rt);
			unset($rt);
			$result->MoveNext();
		}
		$result->Close();
		unset($result);

		// Drop the table once all settings are migrated.
		$rtDao->update('DROP TABLE rt_settings');
		return true;
	}

	/**
	 * For upgrade to 2.1.1: Toggle public display flag for subscription types
	 * to match UI update (#2213)
	 */
	function togglePublicDisplaySubscriptionTypes() {
		$journalDao =& DAORegistry::getDAO('JournalDAO');
		$subscriptionTypeDao =& DAORegistry::getDAO('SubscriptionTypeDAO');

		$journals =& $journalDao->getJournals();
		while ($journal =& $journals->next()) {
			$subscriptionTypes =& $subscriptionTypeDao->getSubscriptionTypesByJournalId($journal->getJournalId());
			while ($subscriptionType =& $subscriptionTypes->next()) {
				if ($subscriptionType->getPublic()) {
					$subscriptionType->setPublic(0);
				} else {
					$subscriptionType->setPublic(1);
				}
				$subscriptionTypeDao->updateSubscriptionType($subscriptionType);
				unset($subscriptionType);
			}
			unset($journal);
		}
		return true;
	}

	/**
	 * For upgrade to OJS 2.2.0: Migrate the currency settings so the
	 * currencies table can be dropped in favour of XML.
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
	 */
	function migrateIssueLabelAndSettings() {
		// First, migrate label_format values in issues table.
		$issueDao =& DAORegistry::getDAO('IssueDAO');
		$result =& $issueDao->retrieve('SELECT * FROM issues');
		while (!$result->EOF) {
			$row = $result->GetRowAssoc(false);
			$issue =& new Issue();
			$issue->setIssueId($row['issue_id']);
			$issue->setJournalId($row['journal_id']);
			$issue->setTitle($row['title']);
			$issue->setVolume($row['volume']);
			$issue->setNumber($row['number']);
			$issue->setYear($row['year']);
			$issue->setPublished($row['published']);
			$issue->setCurrent($row['current']);
			$issue->setDatePublished($row['date_published']);
			$issue->setDateNotified($row['date_notified']);
			$issue->setAccessStatus($row['access_status']);
			$issue->setOpenAccessDate($row['open_access_date']);
			$issue->setDescription($row['description']);
			$issue->setPublicIssueId($row['public_issue_id']);

			switch ($row['label_format']) {
				case 4: // ISSUE_LABEL_TITLE
					$issue->setShowVolume(0);
					$issue->setShowNumber(0);
					$issue->setShowYear(0);
					$issue->setShowTitle(1);
					break;
				case 3: // ISSUE_LABEL_YEAR
					$issue->setShowVolume(0);
					$issue->setShowNumber(0);
					$issue->setShowYear(1);
					$issue->setShowTitle(0);
					break;
 				case 2: // ISSUE_LABEL_VOL_YEAR 		
					$issue->setShowVolume(1);
					$issue->setShowNumber(0);
					$issue->setShowYear(1);
					$issue->setShowTitle(0);
					break;
				case 1: // ISSUE_LABEL_NUM_VOL_YEAR
				default:
					$issue->setShowVolume(1);
					$issue->setShowNumber(1);
					$issue->setShowYear(1);
					$issue->setShowTitle(0);
					break;
			}

			$issue->setFileName($row['file_name']);
			$issue->setOriginalFileName($row['original_file_name']);
			$issue->setWidth($row['width']);
			$issue->setHeight($row['height']);
			$issue->setCoverPageDescription($row['cover_page_description']);
			$issue->setShowCoverPage($row['show_cover_page']);
			$issue->setStyleFileName($row['style_file_name']);
			$issue->setOriginalStyleFileName($row['original_style_file_name']);
			$issueDao->updateIssue($issue);
			unset($issue);
			$result->MoveNext();
		}
		$result->Close();
		unset($result);

		// Drop the old label_format column once all values are migrated.
		$issueDao->update('ALTER TABLE issues DROP COLUMN label_format');

		// Migrate old publicationFormat journal setting to new journal settings. 
		$journalDao =& DAORegistry::getDAO('JournalDAO');
		$journalSettingsDao =& DAORegistry::getDAO('JournalSettingsDAO'); 
		$journals =& $journalDao->getJournals();

		while ($journal =& $journals->next()) {

			$publicationFormat = $journal->getSetting('publicationFormat');
			$journalSettingsDao->deleteSetting($journal->getJournalId(), 'publicationFormat');

			switch ($publicationFormat) {
				case 4: // ISSUE_LABEL_TITLE
					$journal->updateSetting('publicationFormatVolume', false, 'bool');
					$journal->updateSetting('publicationFormatNumber', false, 'bool');
					$journal->updateSetting('publicationFormatYear', false, 'bool');
					$journal->updateSetting('publicationFormatTitle', true, 'bool');
					break;
				case 3: // ISSUE_LABEL_YEAR
					$journal->updateSetting('publicationFormatVolume', false, 'bool');
					$journal->updateSetting('publicationFormatNumber', false, 'bool');
					$journal->updateSetting('publicationFormatYear', true, 'bool');
					$journal->updateSetting('publicationFormatTitle', false, 'bool');
					break;
 				case 2: // ISSUE_LABEL_VOL_YEAR 		
					$journal->updateSetting('publicationFormatVolume', true, 'bool');
					$journal->updateSetting('publicationFormatNumber', false, 'bool');
					$journal->updateSetting('publicationFormatYear', true, 'bool');
					$journal->updateSetting('publicationFormatTitle', false, 'bool');
					break;
				case 1: // ISSUE_LABEL_NUM_VOL_YEAR
				default:
					$journal->updateSetting('publicationFormatVolume', true, 'bool');
					$journal->updateSetting('publicationFormatNumber', true, 'bool');
					$journal->updateSetting('publicationFormatYear', true, 'bool');
					$journal->updateSetting('publicationFormatTitle', false, 'bool');
			}

			unset($journal);
		}

		return true;
	}
}

?>
