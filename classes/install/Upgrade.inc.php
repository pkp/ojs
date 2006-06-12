<?php

/**
 * Upgrade.inc.php
 *
 * Copyright (c) 2003-2006 John Willinsky
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
			$rt->setBibFormat($row['bib_format']);
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
}

?>
