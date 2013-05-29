<?php

/**
 * @file plugins/generic/driver/DRIVERPlugin.inc.php
 *
 * Copyright (c) 2003-2013 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class DRIVERPlugin
 * @ingroup plugins_generic_driver
 *
 * @brief DRIVER plugin class
 */

define('DRIVER_ACCESS_OPEN', 0);
define('DRIVER_ACCESS_CLOSED', 1);
define('DRIVER_ACCESS_EMBARGOED', 2);
define('DRIVER_ACCESS_DELAYED', 3);
define('DRIVER_ACCESS_RESTRICTED', 4);

import('lib.pkp.classes.plugins.GenericPlugin');

class DRIVERPlugin extends GenericPlugin {

	/**
	 * Called as a plugin is registered to the registry
	 * @param $category String Name of category plugin was registered to
	 * @return boolean True if plugin initialized successfully; if false,
	 * 	the plugin will not be registered.
	 */
	function register($category, $path) {
		$success = parent::register($category, $path);
		if ($success && $this->getEnabled()) {
			$this->import('DRIVERDAO');
			$driverDao = new DRIVERDAO();
			DAORegistry::registerDAO('DRIVERDAO', $driverDao);

			// Add DRIVER set to OAI results
			HookRegistry::register('OAIDAO::getJournalSets', array($this, 'sets'));
			HookRegistry::register('JournalOAI::records', array($this, 'records'));
			HookRegistry::register('JournalOAI::identifiers', array($this, 'identifiers'));
			HookRegistry::register('OAIDAO::_returnRecordFromRow', array($this, 'changeRecord'));
			HookRegistry::register('OAIDAO::_returnIdentifierFromRow', array($this, 'changeIdentifier'));

			// consider DRIVER article in article tombstones
			HookRegistry::register('ArticleTombstoneManager::insertArticleTombstone', array($this, 'insertDRIVERArticleTombstone'));
		}
		return $success;
	}

	function getDisplayName() {
		return __('plugins.generic.driver.displayName');
	}

	function getDescription() {
		return __('plugins.generic.driver.description');
	}

	/*
	 * OAI interface
	 */

	/**
	 * Add DRIVER set
	 */
	function sets($hookName, $params) {
		$sets =& $params[5];
		array_push($sets, new OAISet('driver', 'Open Access DRIVERset', ''));
		return false;
	}

	/**
	 * Change OAI records to consider the DRIVER set
	 */
	function records($hookName, $params) {
		$journalOAI =& $params[0];
		$from = $params[1];
		$until = $params[2];
		$set = $params[3];
		$offset = $params[4];
		$limit = $params[5];
		$total = $params[6];
		$records =& $params[7];

		$records = array();
		if (isset($set) && $set == 'driver') {
			$journalId = $journalOAI->journalId;
			$driverDao = DAORegistry::getDAO('DRIVERDAO');
			$driverDao->setOAI($journalOAI);
			$records = $driverDao->getDRIVERRecords($journalId, $from, $until, $offset, $limit, $total);
			return true;
		}
		return false;
	}

	/**
	 * Change OAI identifier to consider the DRIVER set
	 */
	function identifiers($hookName, $params) {
		$journalOAI =& $params[0];
		$from = $params[1];
		$until = $params[2];
		$set = $params[3];
		$offset = $params[4];
		$limit = $params[5];
		$total = $params[6];
		$records =& $params[7];

		$records = array();
		if (isset($set) && $set == 'driver') {
			$journalId = $journalOAI->journalId;
			$driverDao = DAORegistry::getDAO('DRIVERDAO');
			$driverDao->setOAI($journalOAI);
			$records = $driverDao->getDRIVERIdentifiers($journalId, $from, $until, $offset, $limit, $total);
			return true;
		}
		return false;
	}

	/**
	 * Change OAI record to consider the DRIVER set
	 */
	function changeRecord($hookName, $params) {
		$record =& $params[0];
		$row = $params[1];

		if ($this->isDRIVERRecord($row)) {
			$record->sets[] = 'driver';
		}
		return false;
	}

	/**
	 * Change OAI identifier to consider the DRIVER set
	 */
	function changeIdentifier($hookName, $params) {
		$record =& $params[0];
		$row = $params[1];

		if ($this->isDRIVERRecord($row)) {
			$record->sets[] = 'driver';
		}
		return false;
	}

	/**
	 * Consider the DRIVER article in the article tombstone
	 */
	function insertDRIVERArticleTombstone($hookName, $params) {
		$articleTombstone =& $params[0];

		if ($this->isDRIVERArticle($articleTombstone->getJournalId(), $articleTombstone->getSubmissionId())) {
			$submissionTombstoneSettingsDao = DAORegistry::getDAO('SubmissionTombstoneSettingsDAO');
			$submissionTombstoneSettingsDao->updateSetting($articleTombstone->getId(), 'driver', true, 'bool');
		}
		return false;
	}

	/**
	 * Check if it's a DRIVER record.
	 * @param $row array of database fields
	 * @return boolean
	 */
	function isDRIVERRecord($row) {
		// if the article is alive
		if (!isset($row['tombstone_id'])) {
			$journalDao = DAORegistry::getDAO('JournalDAO');
			$publishedArticleDao = DAORegistry::getDAO('PublishedArticleDAO');
			$issueDao = DAORegistry::getDAO('IssueDAO');

			$journal = $journalDao->getById($row['journal_id']);
			$article = $publishedArticleDao->getPublishedArticleByArticleId($row['submission_id']);
			$issue = $issueDao->getById($article->getIssueId());

			// is open access
			$status = '';
			if ($journal->getSetting('publishingMode') == PUBLISHING_MODE_OPEN) {
				$status = DRIVER_ACCESS_OPEN;
			} else if ($journal->getSetting('publishingMode') == PUBLISHING_MODE_SUBSCRIPTION) {
				if ($issue->getAccessStatus() == ISSUE_ACCESS_OPEN) {
					$status = DRIVER_ACCESS_OPEN;
				} else if ($issue->getAccessStatus() == ISSUE_ACCESS_SUBSCRIPTION) {
					if (is_a($article, 'PublishedArticle') && $article->getAccessStatus() == ARTICLE_ACCESS_OPEN) {
						$status = DRIVER_ACCESS_OPEN;
					} else if ($issue->getAccessStatus() == ISSUE_ACCESS_SUBSCRIPTION && $issue->getOpenAccessDate() != NULL) {
						$status = DRIVER_ACCESS_EMBARGOED;
					} else if ($issue->getAccessStatus() == ISSUE_ACCESS_SUBSCRIPTION && $issue->getOpenAccessDate() == NULL) {
						$status = DRIVER_ACCESS_CLOSED;
					}
				}
			}
			if ($journal->getSetting('restrictSiteAccess') == 1 || $journal->getSetting('restrictArticleAccess') == 1) {
				$status = DRIVER_ACCESS_RESTRICTED;
			}

			if ($status == DRIVER_ACCESS_EMBARGOED && date('Y-m-d') >= date('Y-m-d', strtotime($issue->getOpenAccessDate()))) {
				$status = DRIVER_ACCESS_DELAYED;
			}

			// is there a full text
			$galleys =& $article->getGalleys();
			if (!empty($galleys)) {
				return $status == DRIVER_ACCESS_OPEN;
			}
			return false;
		} else {
			$submissionTombstoneSettingsDao = DAORegistry::getDAO('SubmissionTombstoneSettingsDAO');
			return $submissionTombstoneSettingsDao->getSetting($row['tombstone_id'], 'driver');
		}
	}


	/**
	 * Check if it's a DRIVER article.
	 * @param $row ...
	 * @return boolean
	 */
	function isDRIVERArticle($journalId, $articleId) {
			$journalDao = DAORegistry::getDAO('JournalDAO');
			$publishedArticleDao = DAORegistry::getDAO('PublishedArticleDAO');
			$issueDao = DAORegistry::getDAO('IssueDAO');

			$journal = $journalDao->getById($journalId);
			$article = $publishedArticleDao->getPublishedArticleByArticleId($articleId);
			$issue = $issueDao->getById($article->getIssueId());

			// is open access
			$status = '';
			if ($journal->getSetting('publishingMode') == PUBLISHING_MODE_OPEN) {
				$status = DRIVER_ACCESS_OPEN;
			} else if ($journal->getSetting('publishingMode') == PUBLISHING_MODE_SUBSCRIPTION) {
				if ($issue->getAccessStatus() == ISSUE_ACCESS_OPEN) {
					$status = DRIVER_ACCESS_OPEN;
				} else if ($issue->getAccessStatus() == ISSUE_ACCESS_SUBSCRIPTION) {
					if (is_a($article, 'PublishedArticle') && $article->getAccessStatus() == ARTICLE_ACCESS_OPEN) {
						$status = DRIVER_ACCESS_OPEN;
					} else if ($issue->getAccessStatus() == ISSUE_ACCESS_SUBSCRIPTION && $issue->getOpenAccessDate() != NULL) {
						$status = DRIVER_ACCESS_EMBARGOED;
					} else if ($issue->getAccessStatus() == ISSUE_ACCESS_SUBSCRIPTION && $issue->getOpenAccessDate() == NULL) {
						$status = DRIVER_ACCESS_CLOSED;
					}
				}
			}
			if ($journal->getSetting('restrictSiteAccess') == 1 || $journal->getSetting('restrictArticleAccess') == 1) {
				$status = DRIVER_ACCESS_RESTRICTED;
			}

			if ($status == DRIVER_ACCESS_EMBARGOED && date('Y-m-d') >= date('Y-m-d', strtotime($issue->getOpenAccessDate()))) {
				$status = DRIVER_ACCESS_DELAYED;
			}

			// is there a full text
			$galleys =& $article->getGalleys();
			if (!empty($galleys)) {
				return $status == DRIVER_ACCESS_OPEN;
			}
			return false;
	}

}
?>
