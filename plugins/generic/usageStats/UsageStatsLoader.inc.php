<?php

/**
 * @file plugins/generic/usageStats/UsageStatsLoader.php
 *
 * Copyright (c) 2013-2014 Simon Fraser University Library
 * Copyright (c) 2003-2014 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class UsageStatsLoader
 * @ingroup plugins_generic_usageStats
 *
 * @brief Scheduled task to extract transform and load usage statistics data into database.
 */

import('lib.pkp.classes.task.FileLoader');

/** These are rules defined by the COUNTER project.
 * See http://www.projectcounter.org/code_practice.htmlcode */
define('COUNTER_DOUBLE_CLICK_TIME_FILTER_SECONDS_HTML', 10);
define('COUNTER_DOUBLE_CLICK_TIME_FILTER_SECONDS_OTHER', 30);

class UsageStatsLoader extends FileLoader {

	/** @var A GeoLocationTool object instance to provide geo location based on ip. */
	var $_geoLocationTool;

	/** @var $_plugin Plugin */
	var $_plugin;

	/** @var $_counterRobotsListFile string */
	var $_counterRobotsListFile;

	/** @var $_journalsByPath array */
	var $_journalsByPath;

	/** @var $_autoStage string */
	var $_autoStage;

	/** @var $_externalLogFiles string */
	var $_externalLogFiles;


	/**
	 * Constructor.
	 * @param $argv array task arguments
	 */
	function UsageStatsLoader($args) {
		$plugin =& PluginRegistry::getPlugin('generic', 'usagestatsplugin'); /* @var $plugin UsageStatsPlugin */
		$this->_plugin =& $plugin;

		$arg = current($args);

		switch ($arg) {
			case 'autoStage':
				if ($plugin->getSetting(0, 'createLogFiles')) {
					$this->_autoStage = true;
				}
				break;
			case 'externalLogFiles':
				$this->_externalLogFiles = true;
				break;
		}


		// Define the base filesystem path.
		$args[0] = $plugin->getFilesPath();

		parent::FileLoader($args);

		if ($plugin->getEnabled()) {
			// Load the metric type constant.
			PluginRegistry::loadCategory('reports');

			$geoLocationTool =& StatisticsHelper::getGeoLocationTool();
			$this->_geoLocationTool =& $geoLocationTool;

			$plugin->import('UsageStatsTemporaryRecordDAO');
			$statsDao = new UsageStatsTemporaryRecordDAO();
			DAORegistry::registerDAO('UsageStatsTemporaryRecordDAO', $statsDao);

			$this->_counterRobotsListFile = $this->_getCounterRobotListFile();

			$journalDao =& DAORegistry::getDAO('JournalDAO'); /* @var $journalDao JournalDAO */
			$journalFactory =& $journalDao->getJournals(); /* @var $journalFactory DAOResultFactory */
			$journalsByPath = array();
			while ($journal =& $journalFactory->next()) { /* @var $journal Journal */
				$journalsByPath[$journal->getPath()] =& $journal;
			}
			$this->_journalsByPath = $journalsByPath;

			$this->checkFolderStructure(true);

			if ($this->_autoStage) {
				// Copy all log files to stage directory, except the current day one.
				$fileMgr = new FileManager();
				$logFiles = array();
				$logsDirFiles =  glob($plugin->getUsageEventLogsPath() . DIRECTORY_SEPARATOR . '*');

				// It's possible that the processing directory have files that
				// were being processed but the php process was stopped before
				// finishing the processing. Just copy them to the stage directory too.
				$processingDirFiles = glob($this->getProcessingPath() . DIRECTORY_SEPARATOR . '*');

				if (is_array($logsDirFiles)) {
					$logFiles = array_merge($logFiles, $logsDirFiles);
				}

				if (is_array($processingDirFiles)) {
					$logFiles = array_merge($logFiles, $processingDirFiles);
				}

				foreach ($logFiles as $filePath) {
					// Make sure it's a file.
					if ($fileMgr->fileExists($filePath)) {
						// Avoid current day file.
						$filename = pathinfo($filePath, PATHINFO_BASENAME);
						$currentDayFilename = $plugin->getUsageEventCurrentDayLogName();
						if ($filename == $currentDayFilename) continue;
						if ($fileMgr->copyFile($filePath, $this->getStagePath() . DIRECTORY_SEPARATOR . $filename)) {
							$fileMgr->deleteFile($filePath);
						}
					}
				}
			}
		}
	}

	/**
	 * @see FileLoader::getName()
	 */
	function getName() {
		return __('plugins.generic.usageStats.usageStatsLoaderName');
	}

	/**
	 * @see FileLoader::executeActions()
	 */
	function executeActions() {
		$plugin =& $this->_plugin;
		if (!$plugin->getEnabled()) {
			$this->addExecutionLogEntry(__('plugins.generic.usageStats.openFileFailed'), SCHEDULED_TASK_MESSAGE_TYPE_WARNING);
			return true;
		}

		parent::executeActions();
	}

	/**
	 * @see FileLoader::processFile()
	 */
	function processFile($filePath, &$errorMsg) {
		$fhandle = fopen($filePath, 'r');
		$geoTool = $this->_geoLocationTool;
		if (!$fhandle) {
			$errorMsg = __('plugins.generic.usageStats.openFileFailed', array('file' => $filePath));
			return false;
		}

		$loadId = basename($filePath);
		$statsDao =& DAORegistry::getDAO('UsageStatsTemporaryRecordDAO'); /* @var $statsDao UsageStatsTemporaryRecordDAO */

		// Make sure we don't have any temporary records associated
		// with the current load id in database.
		$statsDao->deleteByLoadId($loadId);

		$extractedData = array();
		$lastInsertedEntries = array();
		$lineNumber = 0;

		while(!feof($fhandle)) {
			$lineNumber++;
			$line = trim(fgets($fhandle));
			if (empty($line) || substr($line, 0, 1) === "#") continue; // Spacing or comment lines.
			$entryData = $this->_getDataFromLogEntry($line);
			if (!$this->_isLogEntryValid($entryData, $lineNumber)) {
				$errorMsg = __('plugins.generic.usageStats.invalidLogEntry',
					array('file' => $filePath, 'lineNumber' => $lineNumber));
				return false;
			}

			// Avoid internal apache requests.
			if ($entryData['url'] == '*') continue;

			// Avoid url with file extension (accept no extension or php only).
			$fileExtension = pathinfo($entryData['url'], PATHINFO_EXTENSION);
			if ($fileExtension && substr_count($fileExtension, 'php') == false) continue;

			// Avoid non sucessful requests.
			$sucessfulReturnCodes = array(200, 304);
			if (!in_array($entryData['returnCode'], $sucessfulReturnCodes)) continue;

			// Avoid bots.
			if (Core::isUserAgentBot($entryData['userAgent'], $this->_counterRobotsListFile)) continue;

			list($assocId, $assocType) = $this->_getAssocFromUrl($entryData['url'], $filePath, $lineNumber);
			if(!$assocId || !$assocType) continue;

			list($countryCode, $cityName, $region) = $geoTool->getGeoLocation($entryData['ip']);
			$day = date('Ymd', $entryData['date']);

			$type = $this->_getFileType($assocType, $assocId);

			// Implement double click filtering.
			$entryHash = $assocType . $assocId . $entryData['ip'];

			// Clean the last inserted entries, removing the entries that have
			// no importance for the time between requests check.
			$biggestTimeFilter = COUNTER_DOUBLE_CLICK_TIME_FILTER_SECONDS_OTHER;
			foreach($lastInsertedEntries as $hash => $time) {
				if ($time + $biggestTimeFilter < $entryData['date']) {
					unset($lastInsertedEntries[$hash]);
				}
			}

			// Time between requests check.
			if (isset($lastInsertedEntries[$entryHash])) {
				// Decide what time filter to use, depending on object type.
				if ($type == STATISTICS_FILE_TYPE_PDF || $type == STATISTICS_FILE_TYPE_OTHER) {
					$timeFilter = COUNTER_DOUBLE_CLICK_TIME_FILTER_SECONDS_OTHER;
				} else {
					$timeFilter = COUNTER_DOUBLE_CLICK_TIME_FILTER_SECONDS_HTML;
				}

				$secondsBetweenRequests = $entryData['date'] - $lastInsertedEntries[$entryHash];
				if ($secondsBetweenRequests < $timeFilter) {
					// We have to store the last access,
					// so we delete the most recent one.
					$statsDao->deleteRecord($assocType, $assocId, $lastInsertedEntries[$entryHash], $loadId);
				}
			}

			$lastInsertedEntries[$entryHash] = $entryData['date'];
			$statsDao->insert($assocType, $assocId, $day, $entryData['date'], $countryCode, $region, $cityName, $type, $loadId);
		}

		fclose($fhandle);
		$loadResult = $this->_loadData($loadId, $errorMsg);
		$statsDao->deleteByLoadId($loadId);

		if (!$loadResult) {
			// Improve the error message.
			$errorMsg = __('plugins.generic.usageStats.loadDataError',
				array('file' => $filePath, 'error' => $errorMsg));
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
	function _isLogEntryValid($entry, $lineNumber) {
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
	function _getDataFromLogEntry($entry) {
		$plugin = $this->_plugin; /* @var $plugin Plugin */
		$createLogFiles = $plugin->getSetting(0, 'createLogFiles');
		if (!$createLogFiles || $this->_externalLogFiles) {
			// User wants to process log files that were not created by
			// the usage stats plugin. Try to get a user defined regex to
			// parse those external log files then.
			$parseRegex = $plugin->getSetting(0, 'accessLogFileParseRegex');
		} else {
			// Regex to parse this plugin's log access files.
			$parseRegex = '/^(\S+) \S+ \S+ "(.*?)" (\S+) (\S+) "(.*?)"/';
		}

		// The default regex will parse only apache log files in combined format.
		if (!$parseRegex) $parseRegex = '/^(\S+) \S+ \S+ \[(.*?)\] "\S+ (\S+).*?" (\S+) \S+ ".*?" "(.*?)"/';

		$returner = array();
		if (preg_match($parseRegex, $entry, $m)) {
			$returner['ip'] = $m[1];
			$returner['date'] = strtotime($m[2]);
			$returner['url'] = urldecode($m[3]);
			$returner['returnCode'] = $m[4];
			$returner['userAgent'] = $m[5];
		}

		return $returner;
	}

	/**
	 * Get the expected page and operation from the stats plugin.
	 * They are grouped by the object type constant that
	 * they give access to.
	 * @return array
	 */
	function _getExpectedPageAndOp() {
		return array(ASSOC_TYPE_ARTICLE => array(
				'article/view',
				'article/viewArticle'),
			ASSOC_TYPE_GALLEY => array(
				'article/viewFile',
				'article/download'),
			ASSOC_TYPE_SUPP_FILE => array(
				'article/downloadSuppFile'),
			ASSOC_TYPE_ISSUE => array(
				'issue/view'),
			ASSOC_TYPE_ISSUE_GALLEY => array(
				'issue/viewFile',
				'issue/download'),
			ASSOC_TYPE_JOURNAL => array(
				'index/index')
			);
	}

	/**
	 * Get the assoc type and id of the object that
	 * is accessed through the passed url.
	 * @param $url string
	 * @param $filePath string
	 * @param $lineNumber int
	 * @return array
	 */
	function _getAssocFromUrl($url, $filePath, $lineNumber) {
		// Check the passed url.
		$assocId = $assocType = $journalId = false;
		$expectedPageAndOp = $this->_getExpectedPageAndOp();

		$pathInfoDisabled = Config::getVar('general', 'disable_path_info');

		// Apache and ojs log files comes with complete or partial
		// base url, remove it so system can retrieve path, page,
		// operation and args.
		$url = Core::removeBaseUrl($url);
		if ($url) {
			$contextPaths = Core::getContextPaths($url, !$pathInfoDisabled);
			$page = Core::getPage($url, !$pathInfoDisabled);
			$operation = Core::getOp($url, !$pathInfoDisabled);
			$args = Core::getArgs($url, !$pathInfoDisabled);
		} else {
			// Could not remove the base url, can't go on.
			$this->addExecutionLogEntry(__('plugins.generic.usageStats.removeUrlError',
				array('file' => $filePath, 'lineNumber' => $lineNumber)), SCHEDULED_TASK_MESSAGE_TYPE_WARNING);
			return array(false, false);
		}

		// See bug #8698#.
		if (is_array($contextPaths) && !$page && $operation == 'index') {
			$page = 'index';
		}

		if (empty($contextPaths) || !$page || !$operation) return array(false, false);

		$pageAndOperation = $page . '/' . $operation;

		$pageAndOpMatch = false;
		// It matches the expected ones?
		foreach ($expectedPageAndOp as $workingAssocType => $workingPageAndOps) {
			foreach($workingPageAndOps as $workingPageAndOp) {
				if ($pageAndOperation == $workingPageAndOp) {
					// Expected url, don't look any futher.
					$pageAndOpMatch = true;
					break 2;
				}
			}
		}

		if ($pageAndOpMatch) {
			// Get the assoc id inside the passed url.
			if (empty($args)) {
				if ($page == 'index' && $operation == 'index') {
					// Can be a journal index page access,
					// let further checking.
					$assocType = ASSOC_TYPE_JOURNAL;
				} else {
					return array(false, false);
				}
			} else {
				$assocId = $args[0];
				$parentObjectId = null;
			}

			// Check if we have more than one url parameter.
			if (isset($args[1])) {
				// Ŝet the correct object type.
				if ($workingAssocType == ASSOC_TYPE_ARTICLE) {
					$assocType = ASSOC_TYPE_GALLEY;
				} elseif ($workingAssocType == ASSOC_TYPE_ISSUE) {
					$assocType = ASSOC_TYPE_ISSUE_GALLEY;
				}

				$parentObjectId = $args[0];
				$assocId = $args[1];
			}

			if (!$assocType) {
				$assocType = $workingAssocType;
			}

			// Get the journal object.
			$journalPath = $contextPaths[0];
			if (isset($this->_journalsByPath[$journalPath])) {
				$journal =& $this->_journalsByPath[$journalPath];
				$journalId = $journal->getId();

				if ($assocType == ASSOC_TYPE_JOURNAL) {
					$assocId = $journalId;
				}
			} else {
				return array(false, false);
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
						$suppFileDao =& DAORegistry::getDAO('SuppFileDAO'); /* @var $suppFileDao SuppFileDAO */
						if ($journal->getSetting('enablePublicSuppFileId')) {
							$suppFile =& $suppFileDao->getSuppFileByBestSuppFileId($assocId, $articleId);
						} else {
							$suppFile =& $suppFileDao->getSuppFile((int) $assocId, $articleId);
						}
						if (is_a($suppFile, 'SuppFile')) {
							$assocId = $suppFile->getId();
						} else {
							$assocId = false;
						}
						break;
					} else {
						$galleyDao =& DAORegistry::getDAO('ArticleGalleyDAO'); /* @var $galleyDao ArticleGalleyDAO */
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
					$assocId = $articleId;
				case ASSOC_TYPE_ARTICLE:
					$assocId = $this->_getInternalArticleId($assocId, $journal);
					break;
				case ASSOC_TYPE_ISSUE_GALLEY:
					$issueId = $this->_getInternalIssueId($parentObjectId, $journal);
					if (!$issueId) {
						$assocId = false;
						break;
					}
					$galleyDao =& DAORegistry::getDAO('IssueGalleyDAO'); /* @var $galleyDao IssueGalleyDAO */
					if ($journal->getSetting('enablePublicGalleyId')) {
						$galley =& $galleyDao->getGalleyByBestGalleyId($assocId, $issueId);
					} else {
						$galley =& $galleyDao->getGalley($assocId, $issueId);
					}
					if (is_a($galley, 'IssueGalley')) {
						$assocId = $galley->getId();
						break;
					} else {
						// Count as a issue view. Don't break
						// so the issue case will be handled.
						$assocType = ASSOC_TYPE_ISSUE;
						$assocId = $issueId;
					}
				case ASSOC_TYPE_ISSUE:
					$assocId = $this->_getInternalIssueId($assocId, $journal);
					break;
			}

			// Don't count some view access operations for pdf galleys,
			// otherwise we would be counting access before user really
			// access the object. If user really access the object, a download or
			// viewFile operation will be also logged and that's the one we have
			// to count.
			$articleViewAccessPageAndOp = array('article/view', 'article/viewArticle');

			if (in_array($workingPageAndOp, $articleViewAccessPageAndOp) && $assocType == ASSOC_TYPE_GALLEY &&
			isset($galley) && $galley && ($galley->isPdfGalley())) {
				$assocId = $assocType = false;
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
	function _getInternalArticleId($id, $journal) {
		$journalId = $journal->getId();
		$publishedArticleDao =& DAORegistry::getDAO('PublishedArticleDAO'); /* @var $publishedArticleDao PublishedArticleDAO */
		if ($journal->getSetting('enablePublicArticleId')) {
			$publishedArticle =& $publishedArticleDao->getPublishedArticleByBestArticleId((int) $journalId, $id, true);
		} else {
			$publishedArticle =& $publishedArticleDao->getPublishedArticleByArticleId((int) $id, (int) $journalId, true);
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
	function _getInternalIssueId($id, $journal) {
		$journalId = $journal->getId();
		$issueDao =& DAORegistry::getDAO('IssueDAO'); /* @var $issueDao IssueDAO */
		if ($journal->getSetting('enablePublicIssueId')) {
			$issue =& $issueDao->getIssueByBestIssueId($id, $journalId, true);
		} else {
			$issue =& $issueDao->getIssueById((int) $id, null, true);
		}
		if (is_a($issue, 'Issue')) {
			return $issue->getId();
		} else {
			return false;
		}
	}

	/**
	 * Get the file type of the object represented
	 * by the passed assoc type and id.
	 * @param $assocType int
	 * @param $assocId int
	 * @return int One of the STATISTICS_FILE_TYPE... constants value.
	 */
	function _getFileType($assocType, $assocId) {
		$file = null;
		$type = null;

		// Get the file.
		switch($assocType) {
			case ASSOC_TYPE_GALLEY:
				$articleGalleyDao =& DAORegistry::getDAO('ArticleGalleyDAO'); /* @var $articleGalleyDao ArticleGalleyDAO */
				$file =& $articleGalleyDao->getGalley($assocId);
				break;
			case ASSOC_TYPE_ISSUE_GALLEY;
				$issueGalleyDao =& DAORegistry::getDAO('IssueGalleyDAO'); /* @var $issueGalleyDao IssueGalleyDAO */
				$file =& $issueGalleyDao->getGalley($assocId);
				break;
			case ASSOC_TYPE_SUPP_FILE:
				$suppFileDao =& DAORegistry::getDAO('SuppFileDAO'); /* @var $suppFileDao SuppFileDAO */
				$file =& $suppFileDao->getSuppFile($assocId);
				break;
		}

		if ($file) {
			if (is_a($file, 'SuppFile')) {
				switch($file->getFileType()) {
					case 'application/pdf':
						$type = STATISTICS_FILE_TYPE_PDF;
						break;
					case 'text/html':
						$type = STATISTICS_FILE_TYPE_HTML;
						break;
					default:
						$type = STATISTICS_FILE_TYPE_OTHER;
					break;
				}
			}

			if (is_a($file, 'ArticleGalley') || is_a($file, 'IssueGalley')) {
				if ($file->isPdfGalley()) {
					$type = STATISTICS_FILE_TYPE_PDF;
				} else if (is_a($file, 'ArticleGalley') && $file->isHtmlGalley()) {
					$type = STATISTICS_FILE_TYPE_HTML;
				} else {
					$type = STATISTICS_FILE_TYPE_OTHER;
				}
			}
		}

		return $type;
	}

	/**
	 * Load the entries inside the temporary database associated with
	 * the passed load id to the metrics table.
	 * @param $loadId string The current load id.
	 * file path.
	 * @param $errorMsg string
	 * @return boolean Whether or not the process
	 * was successful.
	 */
	function _loadData($loadId, &$errorMsg) {
		$statsDao =& DAORegistry::getDAO('UsageStatsTemporaryRecordDAO'); /* @var $statsDao UsageStatsTemporaryRecordDAO */
		$metricsDao =& DAORegistry::getDAO('MetricsDAO'); /* @var $metricsDao MetricsDAO */
		$metricsDao->purgeLoadBatch($loadId);

		while ($record =& $statsDao->getNextByLoadId($loadId)) {
			$record['metric_type'] = OJS_METRIC_TYPE_COUNTER;
			$errorMsg = null;
			if (!$metricsDao->insertRecord($record, $errorMsg)) {
				return false;
			}
		}

		return true;
	}

	/**
	 * Get the COUNTER robot list file.
	 * @return mixed string or false in case of error.
	 */
	function _getCounterRobotListFile() {
		$file = null;
		$dir = $this->_plugin->getPluginPath() . DIRECTORY_SEPARATOR . 'lib' . DIRECTORY_SEPARATOR . 'counter';

		// We only expect one file inside the directory.
		$fileCount = 0;
		foreach (glob($dir . DIRECTORY_SEPARATOR . "*.*") as $file) {
			$fileCount++;
		}
		if (!$file || $fileCount !== 1) {
			return false;
		}

		return $file;
	}
}
?>
