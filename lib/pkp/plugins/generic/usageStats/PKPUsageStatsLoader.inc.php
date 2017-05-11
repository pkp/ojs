<?php

/**
 * @file plugins/generic/usageStats/PKPUsageStatsLoader.php
 *
 * Copyright (c) 2013-2017 Simon Fraser University
 * Copyright (c) 2003-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class PKPUsageStatsLoader
 * @ingroup plugins_generic_usageStats
 *
 * @brief Scheduled task to extract transform and load usage statistics data into database.
 */

import('lib.pkp.classes.task.FileLoader');

/** These are rules defined by the COUNTER project.
 * See http://www.projectcounter.org/code_practice.htmlcode */
define('COUNTER_DOUBLE_CLICK_TIME_FILTER_SECONDS_HTML', 10);
define('COUNTER_DOUBLE_CLICK_TIME_FILTER_SECONDS_OTHER', 30);

abstract class PKPUsageStatsLoader extends FileLoader {

	/** @var A GeoLocationTool object instance to provide geo location based on ip. */
	var $_geoLocationTool;

	/** @var $_plugin Plugin */
	var $_plugin;

	/** @var $_counterRobotsListFile string */
	var $_counterRobotsListFile;

	/** @var $_contextsByPath array */
	var $_contextsByPath;

	/** @var $_autoStage string */
	var $_autoStage;

	/** @var $_externalLogFiles string */
	var $_externalLogFiles;

	/**
	 * Constructor.
	 * @param $argv array task arguments
	 */
	function __construct($args) {
		$plugin = PluginRegistry::getPlugin('generic', 'usagestatsplugin'); /* @var $plugin UsageStatsPlugin */
		$this->_plugin = $plugin;

		if ($plugin->getSetting(CONTEXT_ID_NONE, 'compressArchives')) {
			$this->setCompressArchives(true);
		}

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

		parent::__construct($args);

		if ($plugin->getEnabled()) {
			// Load the metric type constant.
			PluginRegistry::loadCategory('reports');

			import('classes.statistics.StatisticsHelper');
			$statsHelper = new StatisticsHelper();
			$geoLocationTool = $statsHelper->getGeoLocationTool();
			$this->_geoLocationTool = $geoLocationTool;

			$plugin->import('UsageStatsTemporaryRecordDAO');
			$statsDao = new UsageStatsTemporaryRecordDAO();
			DAORegistry::registerDAO('UsageStatsTemporaryRecordDAO', $statsDao);

			$this->_counterRobotsListFile = $this->_getCounterRobotListFile();

			$contextDao = Application::getContextDAO(); /* @var $contextDao ContextDAO */
			$contextFactory = $contextDao->getAll(); /* @var $contextFactory DAOResultFactory */
			$contextsByPath = array();
			while ($context = $contextFactory->next()) { /* @var $context Context */
				$contextsByPath[$context->getPath()] = $context;
			}
			$this->_contextsByPath = $contextsByPath;

			$this->checkFolderStructure(true);


		}
	}

	/**
	 * @copydoc FileLoader::getName()
	 */
	function getName() {
		return __('plugins.generic.usageStats.usageStatsLoaderName');
	}

	/**
	* @copydoc FileLoader::executeActions()
	*/
	protected function executeActions() {
		$plugin = $this->_plugin;
		if (!$plugin->getEnabled()) {
			$this->addExecutionLogEntry(__('plugins.generic.usageStats.pluginNotEnabled'), SCHEDULED_TASK_MESSAGE_TYPE_WARNING);
			return false;
		}

		if (!$this->_counterRobotsListFile || !file_exists($this->_counterRobotsListFile)) {
			$this->addExecutionLogEntry(__('plugins.generic.usageStats.noCounterBotList', array('botlist' => $this->_counterRobotsListFile)), SCHEDULED_TASK_MESSAGE_TYPE_WARNING);
			return false;
		}

		// It's possible that the processing directory has files that
		// were being processed but the php process was stopped before
		// finishing the processing, or there may be a concurrent process running.
		// Warn the user if this is the case.
		$processingDirFiles = glob($this->getProcessingPath() . DIRECTORY_SEPARATOR . '*');
		$processingDirError = is_array($processingDirFiles) && count($processingDirFiles);
		if ($processingDirError) {
			$this->addExecutionLogEntry(__('plugins.generic.usageStats.processingPathNotEmpty', array('directory' => $this->getProcessingPath())), SCHEDULED_TASK_MESSAGE_TYPE_ERROR);
		}

		if ($this->_autoStage) $this->autoStage();

		return (parent::executeActions() && !$processingDirError);
	}

	/**
	 * @copydoc FileLoader::processFile()
	 */
	protected function processFile($filePath) {
		$fhandle = fopen($filePath, 'r');
		$geoTool = $this->_geoLocationTool;
		if (!$fhandle) {
			throw new Exception(__('plugins.generic.usageStats.openFileFailed', array('file' => $filePath)));
		}

		$loadId = basename($filePath);
		$statsDao = DAORegistry::getDAO('UsageStatsTemporaryRecordDAO'); /* @var $statsDao UsageStatsTemporaryRecordDAO */

		// Make sure we don't have any temporary records associated
		// with the current load id in database.
		$statsDao->deleteByLoadId($loadId);

		$lastInsertedEntries = array();
		$lineNumber = 0;

		while(!feof($fhandle)) {
			$lineNumber++;
			$line = trim(fgets($fhandle));
			if (empty($line) || substr($line, 0, 1) === "#") continue; // Spacing or comment lines.
			$entryData = $this->_getDataFromLogEntry($line);
			if (!$this->_isLogEntryValid($entryData, $lineNumber)) {
				throw new Exception(__('plugins.generic.usageStats.invalidLogEntry',
					array('file' => $filePath, 'lineNumber' => $lineNumber)));
			}

			// Avoid internal apache requests.
			if ($entryData['url'] == '*') continue;

			// Avoid non sucessful requests.
			$sucessfulReturnCodes = array(200, 304);
			if (!in_array($entryData['returnCode'], $sucessfulReturnCodes)) continue;

			// Avoid bots.
			if (Core::isUserAgentBot($entryData['userAgent'], $this->_counterRobotsListFile)) continue;

			list($assocType, $contextPaths, $page, $op, $args) = $this->_getUrlMatches($entryData['url'], $filePath, $lineNumber);
			if ($assocType && $contextPaths && $page && $op) {
				list($assocId, $assocType) = $this->getAssoc($assocType, $contextPaths, $page, $op, $args);
			} else {
				$assocId = $assocType = null;
			}

			if(!$assocId || !$assocType) continue;

			$countryCode = $cityName = $region = null;
			$plugin = $this->_plugin;
			if (!$plugin->getSetting(CONTEXT_ID_NONE, 'dataPrivacyOption')) {
				list($countryCode, $cityName, $region) = $geoTool ? $geoTool->getGeoLocation($entryData['ip']) : array(null, null, null);
				// Check optional columns setting.
				$optionalColumns = $plugin->getSetting(CONTEXT_ID_NONE, 'optionalColumns');
				if (!in_array(STATISTICS_DIMENSION_CITY, $optionalColumns)) $cityName = null;
				if (!in_array(STATISTICS_DIMENSION_REGION, $optionalColumns)) $cityName = $region = null;
			}
			$day = date('Ymd', $entryData['date']);

			$type = $this->getFileTypeFromAssoc($assocType, $assocId);

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
		$loadResult = $this->_loadData($loadId);
		$statsDao->deleteByLoadId($loadId);

		if (!$loadResult) {
			$this->addExecutionLogEntry(__('plugins.generic.usageStats.loadDataError',
				array('file' => $filePath)), SCHEDULED_TASK_MESSAGE_TYPE_ERROR);
			return FILE_LOADER_RETURN_TO_STAGING;
		} else {
			return true;
		}
	}


	//
	// Abstract protected methods.
	//
	/**
	 * Get metric type on which this loader process statistics.
	 */
	abstract protected function getMetricType();


	//
	// Protected methods.
	//
	/**
	 * Auto stage usage stats log files, also moving files that
	 * might be in processing folder to stage folder.
	 */
	protected function autoStage() {
		$plugin = $this->_plugin;

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
				$this->moveFile(pathinfo($filePath, PATHINFO_DIRNAME), $this->getStagePath(), $filename);
			}
		}
	}

	/**
	* Based on the passed object data, get the file type.
	* @param $assocType int
	* @param $assocId int
	* @return mixed int|null Return one of the file types
	* constants STATISTICS_FILE_TYPE... if the
	* object is a file, if not, return null.
	*/
	protected function getFileTypeFromAssoc($assocType, $assocId) {
		// Check downloaded file type, if any.
		$file = null;
		$type = null;
		if ($assocType == ASSOC_TYPE_SUBMISSION_FILE) {
			$submissionFileDao = DAORegistry::getDAO('SubmissionFileDAO'); /* @var $submissionFileDao SubmissionFileDAO */
			$file = $submissionFileDao->getLatestRevision($assocId);
		}

		if ($file) $type = $this->getFileTypeFromFile($file);

		return $type;
	}

	/**
	 * Associate the passed file type with one of the file
	 * type statistics constants.
	 * @param $file PKPFile
	 * @return int One of the file type constants STATISTICS_FILE_TYPE...
	 */
	protected function getFileTypeFromFile($file) {
		if (!is_a($file, 'PKPFile')) {
			throw new Exception('Wrong object type, expected PKPFile.');
		}
		$fileType = $file->getFileType();
		$fileExtension = pathinfo($file->getOriginalFileName(), PATHINFO_EXTENSION);
		switch ($fileType) {
			case 'application/pdf':
			case 'application/x-pdf':
			case 'text/pdf':
			case 'text/x-pdf':
				$type = STATISTICS_FILE_TYPE_PDF;
				break;
			case 'application/octet-stream':
				if ($fileExtension == 'pdf') {
					$type = STATISTICS_FILE_TYPE_PDF;
				} else {
					$type = STATISTICS_FILE_TYPE_OTHER;
				}
				break;
			case 'application/msword':
				$type = STATISTICS_FILE_TYPE_DOC;
				break;
			case 'application/zip':
				if ($fileExtension == 'docx') {
					$type = STATISTICS_FILE_TYPE_DOC;
				} else {
					$type = STATISTICS_FILE_TYPE_OTHER;
				}
				break;
			case 'text/html':
				$type = STATISTICS_FILE_TYPE_HTML;
				break;
			default:
				$type = STATISTICS_FILE_TYPE_OTHER;
		}

		return $type;
	}

	/**
	 * Get assoc type and id from the passed page, operation and
	 * arguments.
	 * @param unknown_type $page
	 * @param unknown_type $op
	 * @param unknown_type $args
	 */
	protected function getAssoc($assocType, $contextPaths, $page, $op, $args) {
		$assocId = null;

		switch ($assocType) {
			case ASSOC_TYPE_SUBMISSION:
				if (!isset($args[0])) break;
				$submissionId = $args[0];
				$submissionDao = Application::getSubmissionDAO(); /* @var $submissionDao SubmissionDAO */
				$submission = $submissionDao->getById($submissionId);
				if ($submission) {
					$assocId = $submission->getId();
				}
				break;
			case Application::getContextAssocType():
				$context = $this->getContextByPath($contextPaths);
				if ($context) {
					$assocId = $context->getId();
				}
				break;
		}

		// Object not found, return no assoc info at all.
		if (!$assocId) $assocType = null;

		return array($assocType, $assocId);
	}

	/**
	 * Get the context object based on the context path
	 * array that's returned by Core::getContextPaths()
	 * @param $contextPaths array
	 * @return mixed null|Context
	 * @see Core::getContextPaths()
	 */
	protected function getContextByPath($contextPaths) {
		$application = Application::getApplication();
		$deepestContextDepthIndex = $application->getContextDepth() - 1;
		$contextPath = $contextPaths[$deepestContextDepthIndex];

		$context = null;
		if (isset($this->_contextsByPath[$contextPath])) {
			$context = $this->_contextsByPath[$contextPath];
		}

		return $context;
	}

	/**
	* Get the expected page and operation from the stats plugin.
	* They are grouped by the object type constant that
	* they give access to.
	* @return array
	*/
	protected function getExpectedPageAndOp() {
		return array(
			Application::getContextAssocType() => array(
				'index/index'
			)
		);
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
		$createLogFiles = $plugin->getSetting(0, 'createLogFiles');
		if (!$createLogFiles || $this->_externalLogFiles) {
			// User wants to process log files that were not created by
			// the usage stats plugin. Try to get a user defined regex to
			// parse those external log files then.
			$parseRegex = $plugin->getSetting(0, 'accessLogFileParseRegex');
		} else {
			// Regex to parse this plugin's log access files.
			$parseRegex = '/^(?P<ip>\S+) \S+ \S+ "(?P<date>.*?)" (?P<url>\S+) (?P<returnCode>\S+) "(?P<userAgent>.*?)"/';
		}

		// The default regex will parse only apache log files in combined format.
		if (!$parseRegex) $parseRegex = '/^(?P<ip>\S+) \S+ \S+ \[(?P<date>.*?)\] "\S+ (?P<url>\S+).*?" (?P<returnCode>\S+) \S+ ".*?" "(?P<userAgent>.*?)"/';

		$returner = array();
		if (preg_match($parseRegex, $entry, $m)) {
			$associative = count(array_filter(array_keys($m), 'is_string')) > 0;
			$returner['ip'] = $associative ? $m['ip'] : $m[1];
			$returner['date'] = strtotime($associative ? $m['date'] : $m[2]);
			$returner['url'] = urldecode($associative ? $m['url'] : $m[3]);
			$returner['returnCode'] = $associative ? $m['returnCode'] : $m[4];
			$returner['userAgent'] = $associative ? $m['userAgent'] : $m[5];
		}

		return $returner;
	}

	/**
	 * Get assoc type, page. operation and args from
	 * the passed url, if it matches anyone that's defined
	 * in UsageStatsLoader::getExpectedPageAndOp().
	 * @param $url string
	 * @param $filePath string
	 * @param $lineNumber int
	 * @return array
	 * @see UsageStatsLoader::getExpectedPageAndOp()
	 */
	private function _getUrlMatches($url, $filePath, $lineNumber) {
		$noMatchesReturner = array(null, null, null, null, null);
		// Check the passed url.
		$expectedPageAndOp = $this->getExpectedPageAndOp();

		$pathInfoDisabled = Config::getVar('general', 'disable_path_info');

		// Apache and usage stats plugin log files comes with complete or partial
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
			$this->addExecutionLogEntry( __('plugins.generic.usageStats.removeUrlError',
				array('file' => $filePath, 'lineNumber' => $lineNumber)), SCHEDULED_TASK_MESSAGE_TYPE_WARNING);
			return $noMatchesReturner;
		}

		// See bug #8698#.
		if (is_array($contextPaths) && !$page && $operation == 'index') {
			$page = 'index';
		}

		if (empty($contextPaths) || !$page || !$operation) return $noMatchesReturner;

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
			return array($workingAssocType, $contextPaths, $page, $operation, $args);
		} else {
			return $noMatchesReturner;
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
		$metricsDao = DAORegistry::getDAO('MetricsDAO'); /* @var $metricsDao PKPMetricsDAO */
		$metricsDao->purgeLoadBatch($loadId);

		while ($record = $statsDao->getNextByLoadId($loadId)) {
			$record['metric_type'] = $this->getMetricType();
			$metricsDao->insertRecord($record);
		}

		return true;
	}

	/**
	 * Get the COUNTER robot list file.
	 * @return mixed string or false in case of error.
	 */
	private function _getCounterRobotListFile() {
		$file = null;
		$dir = PKP_LIB_PATH . DIRECTORY_SEPARATOR . $this->_plugin->getPluginPath() . DIRECTORY_SEPARATOR . 'lib' . DIRECTORY_SEPARATOR . 'counter';

		// We only expect one file inside the directory.
		$fileCount = 0;
		foreach (glob($dir . DIRECTORY_SEPARATOR . "*") as $file) {
			$fileCount++;
		}
		if (!$file || $fileCount !== 1) {
			return false;
		}

		return $file;
	}
}
?>
