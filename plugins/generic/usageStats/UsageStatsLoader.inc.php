<?php

/**
 * @file plugins/generic/usageStats/UsageStatsLoader.php
 *
 * Copyright (c) 2003-2012 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class UsageStatsLoader
 * @ingroup plugins_generic_usageStats
 *
 * @brief Scheduled task to extract transform and load usage statistics data into database.
 */

import('lib.pkp.classes.task.FileLoader');

/** Geo location tool wrapper class. If you want to change the geo location tool,
 * change the code inside this class, keeping the public interface, so this ETL tool
 * will work with no modification required. */
include('GeoLocationTool.inc.php');

class UsageStatsLoader extends FileLoader {

	/** @var int Minimum time between same requests with the same ip to
	 * consider a valid request. */
	var $_minTimeBetweenRequests;

	/** @var A GeoLocationTool object instance to provide geo location based on ip. */
	var $_geoLocationTool;

	/** @var $_plugin Plugin */
	var $_plugin;

	/**
	 * Constructor.
	 * @param $argv array task arguments
	 */
	function UsageStatsLoader($args) {
		parent::FileLoader($args);

		$this->_geoLocationTool = new GeoLocationTool();

		$plugin = PluginRegistry::getPlugin('generic', 'usagestatsplugin');
		// Load the metric type constant.
		PluginRegistry::loadCategory('reports');
		$this->_plugin = $plugin;

		$this->_minTimeBetweenRequests = $plugin->getSetting(0, 'minTimeBetweenRequests');

		$this->_plugin->import('UsageStatsTemporaryRecordDAO');
		$statsDao = new UsageStatsTemporaryRecordDAO();
		DAORegistry::registerDAO('UsageStatsTemporaryRecordDAO', $statsDao);

		$this->checkFolderStructure(true);
	}

	/**
	 * @see FileLoader::processFile()
	 */
	protected function processFile($filePath) {
		$fhandle = fopen($filePath, 'r');
		if (!$fhandle) {
			throw new Exception(__('plugins.generic.usageStats.openFileFailed', array('file' => $filePath)));
		}

		$loadId = basename($filePath);
		$statsDao = DAORegistry::getDAO('UsageStatsTemporaryRecordDAO'); /* @var $statsDao UsageStatsTemporaryRecordDAO */
		// Make sure we don't have any temporary records associated
		// with the current load id in database.
		$statsDao->deleteByLoadId($loadId);

		$extractedData = array();
		$lastInsertedEntries = array();
		$lineNumber = 0;

		while(!feof($fhandle)) {
			$lineNumber++;
			$line = fgets($fhandle);
			if ($line == '') continue;
			$entryData = $this->_getDataFromLogEntry($line);
			if (!$this->_isLogEntryValid($entryData, $lineNumber)) {
				throw new Exception(__('plugins.generic.usageStats.invalidLogEntry',
					array('file' => $filePath, 'lineNumber' => $lineNumber)));
			}

			list($assocId, $assocType) = $this->_getAssocFromReferer($entryData['referer']);
			if(!$assocId || !$assocType) continue;

			list($countryCode, $cityName, $region) = $this->_geoLocationTool->getGeoLocation($entryData['ip']);
			$day = date('Ymd', $entryData['date']);

			// Check downloaded file type, if any.
			$galley = null;
			$type = null;
			switch($assocType) {
				case ASSOC_TYPE_GALLEY:
					$articleGalleyDao = DAORegistry::getDAO('ArticleGalleyDAO'); /* @var $articleGalleyDao ArticleGalleyDAO */
					$galley = $articleGalleyDao->getGalley($assocId);
					break;
				case ASSOC_TYPE_ISSUE_GALLEY;
					$issueGalleyDao = DAORegistry::getDAO('IssueGalleyDAO'); /* @var $issueGalleyDao IssueGalleyDAO */
					$galley = $issueGalleyDao->getById($assocId);
					break;
			}

			if ($galley && !is_a($galley, 'ArticleGalley') && !is_a($galley, 'IssueGalley')) {
				// This object id was tested before, why
				// it is not the type we expect now?
				assert(false);
			} else if ($galley) {
				if ($galley->isPdfGalley()) {
					$type = USAGE_STATS_REPORT_PLUGIN_FILE_TYPE_PDF;
				} else if (is_a($galley, 'ArticleGalley') && $galley->isHtmlGalley()) {
					$type = USAGE_STATS_REPORT_PLUGIN_FILE_TYPE_HTML;
				} else {
					$type = USAGE_STATS_REPORT_PLUGIN_FILE_TYPE_OTHER;
				}
			}

			$entryHash = $assocType . $assocId . $entryData['date'] . $entryData['ip'];

			// Clean the last inserted entries, removing the entries that have
			// no importance for the time between requests check.
			foreach($lastInsertedEntries as $hash => $time) {
				if ($time + $this->_minTimeBetweenRequests < $entryData['date']) {
					unset($lastInsertedEntries[$hash]);
				}
			}

			// Time between requests check.
			if (!isset($lastInsertedEntries[$entryHash])) {
				 $lastInsertedEntries[$entryHash] = $entryData['date'];
			} else {
				if ($entryData['date'] - $lastInsertedEntries[$entryHash] > $this->_minTimeBetweenRequests) {
					$lastInsertedEntries[$entryHash] = $entryData['date'];
				} else {
					continue;
				}
			}

			$statsDao->insert($assocType, $assocId, $day, $countryCode, $region, $cityName, $type, $loadId);
		}

		fclose($fhandle);
		$loadResult = $this->_loadData($loadId);
		$statsDao->deleteByLoadId($loadId);

		if (!$loadResult) {
			return FILE_LOADER_RETURN_TO_STAGING;
		} else {
			return true;
		}
	}


	//
	// Private helper methods.
	//
	/**
	 * Validate a access log entry.
	 * @param $entry array
	 * @return boolean
	 */
	private function _isLogEntryValid($entry, $lineNumber) {
		if (empty($entry)) {
			return false;
		}

		$date = $entry['date'];
		if (!is_numeric($date) && $date <= 0) {
			return false;
		}

		return true;
	}

	/**
	 * Get data from the passed log entry.
	 * @param $entry string
	 * @return mixed array
	 */
	private function _getDataFromLogEntry($entry) {
		$plugin = $this->_plugin; /* @var $plugin Plugin */
		if (!$plugin->getSetting(0, 'createLogFiles')) {
			// User defined regex to parse external log files.
			$parseRegex = $plugin->getSetting(0, 'accessLogFileParseRegex');
		} else {
			// Regex to parse this plugin's log access files.
			$parseRegex = '/^(\S+) \S+ \S+ "(.*?)" (\S+)/';
		}

		// The default regex will parse only apache log files in combined format.
		if (!$parseRegex) $parseRegex = '/^(\S+) \S+ \S+ \[(.*?)\] "\S+.*?" \d+ \d+ "(.*?)"/';

		$returner = array();
		if (preg_match($parseRegex, $entry, $m)) {
			$returner['ip'] = $m[1];
			$returner['date'] = strtotime($m[2]);
			$returner['referer'] = $m[3];
		}

		return $returner;
	}

	/**
	 * Get the expected referer from the stats plugin.
	 * They are grouped by the object type constant that
	 * they give access to.
	 * @return array
	 * @todo The plugin will also need to know the expected referer, so we
	 * might want to retrieve this from an unique place.
	 */
	private function _getExpectedReferer() {
		return array(ASSOC_TYPE_ARTICLE => array(
				'/article/view/',
				'/article/viewArticle/',
				'/article/viewDownloadInterstitial/',
				'/article/download/'),
			ASSOC_TYPE_SUPP_FILE => array(
				'/article/downloadSuppFile/'),
			ASSOC_TYPE_ISSUE => array(
				'issue/view/',
				'issue/viewFile/',
				'issue/viewDownloadInterstitial/',
				'issue/download/')
			);
	}

	/**
	 * Get the assoc type and id of the object that
	 * is accessed through the passed referer.
	 * @param $referer string
	 * @return array
	 */
	private function _getAssocFromReferer($referer) {
		// Check the passed referer.
		$assocId = $assocType = false;
		$expectedReferer = $this->_getExpectedReferer();

		// Is it a request to our current site?
		$baseUrl = Config::getVar('general', 'base_url');
		if (strpos($referer, $baseUrl) !== false) {
			$refererCheck = false;
			// It matches the expected ones?
			foreach ($expectedReferer as $workingAssocType => $workingReferers) {
				foreach($workingReferers as $workingReferer) {
					if (strpos($referer, $workingReferer) !== false) {
						// Expected referer, don't look any futher.
						$refererCheck = true;
						break 2;
					}
				}
			}

			if ($refererCheck) {
				// Get the assoc id inside the passed referer.
				$explodedString = explode($workingReferer, $referer);
				$assocId = $explodedString[1];

				// Check if we are not dealing with supp files or galleys.
				$explodedString = explode('/', $assocId);
				if (isset($explodedString[1]) && !is_null($explodedString[1])) {
					$parentObjectId = $explodedString[0];
					$assocId = $explodedString[1];
					// Set the correct assoc type.
					if ($workingAssocType == ASSOC_TYPE_ARTICLE) {
						$assocType = ASSOC_TYPE_GALLEY;
					} elseif ($workingAssocType == ASSOC_TYPE_ISSUE) {
						$assocType = ASSOC_TYPE_ISSUE_GALLEY;
					}
				}

				if (!$assocType) {
					$assocType = $workingAssocType;
				}

				// Get the journal object.
				$journalPath = explode('index.php/', $referer);
				$journalPath = explode('/', $journalPath[1]);
				$journalPath = $journalPath[0];
				$journalDao = DAORegistry::getDAO('JournalDAO'); /* @var $journalDao JournalDAO */
				$journal = $journalDao->getByPath($journalPath);
				if (!is_a($journal, 'Journal')) {
					return array(false, false);
				} else {
					$journalId = $journal->getId();
				}

				// Get the internal object id (avoiding public ids).
				switch ($assocType) {
					case ASSOC_TYPE_SUPP_FILE:
					case ASSOC_TYPE_GALLEY:
						$articleId = $this->_getInternalArticleId($parentObjectId, $journal);
						if (!$articleId) {
							$assocId = false;
							break;
						}
						if ($assocType == ASSOC_TYPE_SUPP_FILE) {
							$suppFileDao = DAORegistry::getDAO('SuppFileDAO');
							if ($journal->getSetting('enablePublicSuppFileId')) {
								$suppFile = $suppFileDao->getSuppFileByBestSuppFileId($assocId, $articleId);
							} else {
								$suppFile = $suppFileDao->getSuppFile((int) $assocId, $articleId);
							}
							if (is_a($suppFile, 'SuppFile')) {
								$assocId = $suppFile->getId();
							} else {
								$assocId = false;
							}
							break;
						} else {
							$galleyDao = DAORegistry::getDAO('ArticleGalleyDAO');
							if ($journal->getSetting('enablePublicGalleyId')) {
								$galley =& $galleyDao->getGalleyByBestGalleyId($assocId, $articleId);
							} else {
								$galley =& $galleyDao->getGalley($assocId, $articleId);
							}
							if (is_a($galley, 'ArticleGalley')) {
								$assocId = $galley->getId();
								break;
							}
						}

						// Couldn't retrieve galley,
						// count as article view.
						$assocType = ASSOC_TYPE_ARTICLE;
						$assocId = $parentObjectId;
					case ASSOC_TYPE_ARTICLE:
						$assocId = $this->_getInternalArticleId($assocId, $journal);
						break;
					case ASSOC_TYPE_ISSUE_GALLEY:
						$issueId = $this->_getInternalIssueId($parentObjectId, $journal);
						if (!$issueId) {
							$assocId = false;
							break;
						}
						$galleyDao = DAORegistry::getDAO('IssueGalleyDAO');
						if ($journal->getSetting('enablePublicGalleyId')) {
							$galley = $galleyDao->getByBestId($assocId, $issueId);
						} else {
							$galley = $galleyDao->getById($assocId, $issueId);
						}
						if (is_a($galley, 'IssueGalley')) {
							$assocId = $issue->getId();
							break;
						} else {
							// Count as a issue view. Don't break
							// so the issue case will be handled.
							$assocType = ASSOC_TYPE_ISSUE;
							$assocId = $parentObjectId;
						}
					case ASSOC_TYPE_ISSUE:
						$assocId = $this->_getInternalIssueId($assocId, $journal);
						break;
				}
			}
		}

		return array($assocId, $assocType);
	}

	/**
	 * Get internal article id.
	 * @param $id string The id to be used
	 * to retrieve the object.
	 * @param $journal Journal The journal
	 * that the article belongs to.
	 * @return mixed The internal id if any
	 * object was found or false.
	 */
	private function _getInternalArticleId($id, $journal) {
		$journalId = $journal->getId();
		$publishedArticleDao = DAORegistry::getDAO('PublishedArticleDAO'); /* @var $publishedArticleDao PublishedArticleDAO */
		if ($journal->getSetting('enablePublicArticleId')) {
			$publishedArticle = $publishedArticleDao->getPublishedArticleByBestArticleId((int) $journalId, $id, true);
		} else {
			$publishedArticle = $publishedArticleDao->getPublishedArticleByArticleId((int) $id, (int) $journalId, true);
		}
		if (is_a($publishedArticle, 'PublishedArticle')) {
			return $publishedArticle->getId();
		} else {
			return false;
		}
	}

	/**
	* Get internal issue id.
	* @param $id string The id to be used
	* to retrieve the object.
	* @param $journal Journal The journal
	* that the issue belongs to.
	* @return mixed The internal id if any
	* object was found or false.
	*/
	private function _getInternalIssueId($id, $journal) {
		$journalId = $journal->getId();
		$issueDao = DAORegistry::getDAO('IssueDAO');
		if ($journal->getSetting('enablePublicIssueId')) {
			$issue = $issueDao->getByBestId($id, $journalId);
		} else {
			$issue = $issueDao->getById((int) $id, null, true);
		}
		if (is_a($issue, 'Issue')) {
			return $issue->getId();
		} else {
			return false;
		}
	}

	/**
	 * Load the entries inside the temporary database associated with
	 * the passed load id to the metrics table.
	 * @param $loadId string The current load id.
	 * file path.
	 * @return boolean Whether or not the process
	 * was successful.
	 */
	private function _loadData($loadId) {
		$statsDao = DAORegistry::getDAO('UsageStatsTemporaryRecordDAO'); /* @var $statsDao UsageStatsTemporaryRecordDAO */
		$metricsDao = DAORegistry::getDAO('MetricsDAO'); /* @var $metricsDao MetricsDAO */
		$metricsDao->purgeLoadBatch($loadId);

		while ($record = $statsDao->getNextByLoadId($loadId)) {
			$record['metric_type'] = OJS_METRIC_TYPE_COUNTER;
			$metricsDao->insertRecord($record);
		}

		return true;
	}
}
?>