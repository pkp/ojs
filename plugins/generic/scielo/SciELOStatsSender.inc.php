<?php

/**
 * @file plugins/generic/scielo/SciELOStatsSender.php
 *
 * Copyright (c) 2003-2012 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class SciELOStatsSender
 * @ingroup plugins_generic_scielo
 *
 * @brief Scheduled task to send statistics to SciELO ratchet tool.
 * @see http://docs.scielo.org/projects/ratchet/en/latest/api.html
 */

import('lib.pkp.classes.scheduledTask.ScheduledTask');
import('lib.pkp.classes.webservice.JSONWebService');

class SciELOStatsSender extends ScheduledTask {

	/**
	 * Constructor.
	 * @param $argv array task arguments
	 */
	function SciELOStatsSender($args) {
		parent::ScheduledTask($args);

	}

	/**
	 * @see FileLoader::execute()
	 */
	function execute() {
		$plugin = PluginRegistry::getPlugin('generic', 'SciELOPlugin');
		$unregisteredSettingName = 'scieloUnregisteredStats';

		// Get journals with stats to be sent.
		$journalDao = DAORegistry::getDAO('JournalDAO'); /* @var $journalDao JournalDAO */
		$journalSettingsDao = DAORegistry::getDAO('JournalSettingsDAO'); /* @var $journalSettingsDao JournalSettingsDAO */
		$journalFactory = $journalDao->getBySetting($unregisteredSettingName, true);

		// Send journal stats.
		while ($journal = $journalFactory->next()) {
			if (!$this->_claimObject($journalSettingsDao, $journalId)) continue;
			$lastStatsSendingDate = $journalSettingsDao->getSetting($journalId, 'scieloLastStatsSendingDate');

			if (!$this->_isMigratedStatsSent($journalSettingsDao, $journalId)) {
				$this->_sendMigratedStats($journalSettingsDao, ASSOC_TYPE_JOURNAL, $journal, $lastStatsSendingDate);
			}

			$metrics = $journal->getMetrics(OJS_METRIC_TYPE_COUNTER);
		}


		$serverUrl = '200.136.72.14:8860/';
		$jsonMessage = json_encode(array(
			'code' => '123456',
			'journal' => '0034-8910',
    		'issue' => '0034-891020090004',
    		'article.y2011.m10.d01' => 100,
    		'article.y2011.m10.d02' => 100,
    		'article.y2011.m10.d03' => 100,
    		'article.y2012.m11.d01' => 10,
    		'article.y2012.m11.a02' => 10,
    		'article.y2012.m11.a03' => 10,
    		'article.y2012.m10.total' => 300,
    		'article.y2012.m11.total' => 30,
    		'article.y2012.total' => 330,
    		'total' => 330,
    		'bra' => 200,
    		'mex' => 100,
    		'arg' => 10,
    		'col' => 20
    	));

		$serverUrl = $serverUrl . 'api/v1/article/bulk?data=' . $jsonMessage ;
		$webServiceRequest = new WebServiceRequest($serverUrl);
		$webServiceRequest->setMethod('POST');
		$webServiceRequest->setAccept('application/json');
		$webServiceRequest->setHeader('Content-Type', 'application/json');

		$webService = new WebService();
		$webService->call($webServiceRequest);

		// Check the reponse status.
		if ($webService->getLastResponseStatus() == '200') {
			return true;
		} else {
			return false;
		}
	}


	//
	// Private helper methdos.
	//
	/**
	 * Tries to claim the passed object id to start the statistics
	 * send process.
	 * @param $settingsDao SettingsDAO
	 * @param $objectId int
	 * @return boolean Whether or not the claim was successful.
	 */
	private function _claimObject($settingsDao, $objectId) {
		if (!is_a($settingsDao, 'SettingsDAO')) assert(false);

		$claimedSettingName = 'scieloObjectClaimed';
		if (!$settingsDao->getSetting($objectId, $claimedSettingName)) {
			// Claim the object.
			$settingsDao->updateSetting($objectId, $claimedSettingName, true);
			return true;
		} else {
			// Already claimed by another instance of this script.
			return false;
		}
	}

	/**
	 * Check if the statistics collected by previous versions of
	 * OJS and migrated when upgrading were already sent to SciELO.
	 * @param $settingsDao SettingsDAO
	 * @param $objectId int
	 * @return boolean
	 */
	private function _isMigratedStatsSent($settingsDao, $objectId) {
		if (!is_a($settingsDao, 'SettingsDAO')) assert(false);
		return $settingsDao->getSetting($objectId, 'scieloMigratedStatsSent');
	}

	/**
	 * Send the statistics collected by previous versions of OJS
	 * and migrated when upgrading to SciELO.
	 * @param $settingsDao SettingsDAO
	 * @param $assocType int
	 * @param $assocObject DataObject
	 * @param $lastStatsSendingDate timestamp
	 */
	private function _sendMigratedStats($settingsDao, $assocType, $assocObject, $lastStatsSendingDate) {
		if (!is_a($settingsDao, 'SettingsDAO')) assert(false);
		switch ($assocType) {
			case ASSOC_TYPE_JOURNAL:
				// Get the statistics.
				$metrics = $assocObject->getMetrics(OJS_METRIC_TYPE_LEGACY_COUNTER, array(STATISTICS_DIMENSION_MONTH));
				if($metrics) {
					$message = array(
						'code' => $assocObject->getId(),
						'journal.y2011.m10.d01' => 100,
			    		'article.y2011.m10.d02' => 100,
			    		'article.y2011.m10.d03' => 100,
			    		'article.y2012.m11.d01' => 10,
			    		'article.y2012.m11.a02' => 10,
			    		'article.y2012.m11.a03' => 10,
			    		'article.y2012.m10.total' => 300,
			    		'article.y2012.m11.total' => 30,
			    		'article.y2012.total' => 330,
			    		'total' => 330,
			    		'bra' => 200,
			    		'mex' => 100,
			    		'arg' => 10,
			    		'col' => 20
					);
				}
		}
	}
}
?>