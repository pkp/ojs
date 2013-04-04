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

			$entryData['assocId'] = $assocId;
			$entryData['assocType'] = $assocType;
			list($countryCode, $cityName, $region) = $this->_geoLocationTool->getGeoLocation($entryData['ip']);
			$day = date('Ymd', $entryData['date']);
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

			$statsDao->insert($assocType, $assocId, $day, $countryCode, $region, $cityName, $loadId);
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
			$parseRegex = $plugin->getSetting(0, 'accessLogFileParseRegex');
		}

		if (!$parseRegex) $parseRegex = '/^(\S+) \S+ \S+ \[(.*?)\] "(\S+).*?" \d+ \d+ "(.*?)" "(.*?)"/';

		$returner = array();
		if (preg_match($parseRegex, $entry, $m)) {
			$returner['ip'] = $m[1];
			$returner['date'] = strtotime($m[2]);
			$returner['method'] = $m[3];
			$returner['referer'] = $m[4];
			$returner['browser'] = $m[5];
		}

		return $returner;
	}

	/**
	 * Get the expected referer from the stats plugin.
	 * @return array
	 * @todo The plugin will also need to know the expected referer, so we
	 * might want to retrieve this from an unique place.
	 */
	private function _getExpectedReferer() {
		return array(ASSOC_TYPE_ARTICLE => array(
			'/article/view/',
			'/article/viewArticle/',
			'/article/viewDownloadInterstitial/',
			'/article/download/'
		));
	}

	/**
	 * Get the assoc type and id of the object that
	 * is accessed through the passed referer.
	 * @param $referer string
	 * @return array
	 */
	private function _getAssocFromReferer($referer) {
		// Check the referer.
		$assocId = $assocType = false;
		$expectedReferer = $this->_getExpectedReferer();

		// Is it a request to our current site?
		$baseUrl = Config::getVar('general', 'base_url');
		if (strpos($referer, $baseUrl) !== false) {
			$refererCheck = false;
			foreach ($expectedReferer as $workingAssocType => $workingReferers) {
				foreach($workingReferers as $workingReferer) {
					if (strpos($referer, $workingReferer) !== false) {
						$refererCheck = true;
						break 2;
					}
				}
			}

			if ($refererCheck) {
				$explodedString = explode($workingReferer, $referer);
				$explodedString = explode('/', $explodedString[1]);
				$assocId = $explodedString[0];

				if (!is_numeric($assocId)) {
					$assocId = false;
				}

				$assocType = $workingAssocType;
			}
		}

		return array($assocId, $assocType);
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
			$record['metric_type'] = 'ojs::counter';
			$metricsDao->insertRecord($record);
		}

		return true;
	}
}
?>