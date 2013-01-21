<?php


/**
 * @file plugins/generic/oas/OasEventStagingDAO.inc.php
 *
 * Copyright (c) 2003-2012 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class OasEventStagingDAO
 * @ingroup plugins_generic_oas
 *
 * @brief Class for temporary staging of OA-S context objects before they
 *  are transferred to the OA-S service provider.
 */

class OasEventStagingDAO extends DAO {

	/**
	 * Log a new usage event to the staging table.
	 *
	 * @param $usageEvent array
	 * @param $salt string A SALT value for IP hashing.
	 */
	function stageUsageEvent($usageEvent, $salt) {
		// We currently only use 'administration' classification. TODO: Add more if we actually use them.
		$validClassifiers = array(
			OAS_PLUGIN_CLASSIFICATION_ADMIN
		);
		$identifiers = is_array($usageEvent['identifiers']) ? $usageEvent['identifiers'] : array();
		$identifiers['other::url'] = $usageEvent['canonicalUrl'];

		// Has the IP. We do this here so that it will be impossible to
		// store non-hashed IPs which would be a privacy legislation
		// violation under German law without explicit user consent.
		$hashedIp = $this->hashIp($usageEvent['ip'], $salt);
		$hashedC = $this->hashIp($this->getCClassNet($usageEvent['ip']), $salt);

		// Never store unhashed IPs!
		if ($hashedIp === false || $hashedC === false) return false;

		$this->update(
			sprintf(
				'INSERT INTO oas_event_staging
					(timestamp, admin_size, admin_document_size, admin_format, admin_service,
					  ref_ids, ref_ent_ids, requ_hashed_ip, requ_hashed_c, requ_hostname,
					  requ_classification, requ_user_agent)
				VALUES
					(%s, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)', $this->datetimeToDB($usageEvent['time'])
			),
			array(
				(int) ($usageEvent['downloadSuccess'] ? $usageEvent['docSize'] : 0),
				(int) $usageEvent['docSize'],
				$usageEvent['mimeType'],
				$usageEvent['serviceUri'],
				$this->convertToDB($identifiers, $type = 'object'),
				$usageEvent['referrer'],
				$hashedIp,
				$hashedC,
				$usageEvent['host'],
				(in_array($usageEvent['classification'], $validClassifiers) ? $usageEvent['classification'] : null),
				$usageEvent['userAgent']
			)
		);
		return $this->_getInsertId('oas_event_staging', 'event_id');
	}

	/**
	 * Update the download success flag of a usage
	 * event.
	 *
	 * @param $usageEventId integer
	 * @param $downloadSuccess boolean
	 */
	function setDownloadSuccess($usageEventId, $downloadSuccess) {
		// We simulate download success by setting the downloaded size to
		// the document size. This signals to OA-S that the event
		// should be considered a successful download. If the download
		// was not successful we simply leave the field as initialized (i.e.
		// size = 0) which signals to OA-S that the event should be
		// considered an aborted download.
		if ($downloadSuccess) {
			$this->update(
				'UPDATE oas_event_staging SET admin_size = admin_document_size
				WHERE event_id = ?', (int)$usageEventId);
		}
	}

	/**
	 * Delete usage events older than OAS_PLUGIN_MAX_STAGING_TIME.
	 */
	function clearExpiredEvents() {
		$deleteOlderThan = Core::getCurrentDate(time() - OAS_PLUGIN_MAX_STAGING_TIME * 60);
		$this->update(
			sprintf(
				'DELETE FROM oas_event_staging WHERE timestamp < %s',
				$this->dateToDB($deleteOlderThan)
			)
		);
	}

	/**
	 * Hash (SHA256) the given IP using the given SALT.
	 *
	 * NB: This implementation was taken from OA-S directly. See
	 * http://sourceforge.net/p/openaccessstati/code-0/3/tree/trunk/logfile-parser/lib/sha256.php
	 * We just do not implement the PHP4 part as OJS dropped PHP4 support.
	 *
	 * @param $ip string
	 * @param $salt string
	 * @return string|boolean The hashed IP or boolean false if something went wrong.
	 */
	function hashIp($ip, $salt) {
		if(function_exists('mhash')) {
			return bin2hex(mhash(MHASH_SHA256, $salt));
		} else {
			assert(function_exists('hash'));
			if (!function_exists('hash')) return false;
			return hash('sha256', $salt);
		}
	}

	/**
	* Get "C-class" of an IP adress, i.e. the first three bytes
	*
	* NB: This implementation was taken from OA-S directly. See
	* http://sourceforge.net/p/openaccessstati/code-0/3/tree/trunk/logfile-parser/lib/oasparser.php
	*
	* @param $ip The IP to shorten.
	* @return string C-class formatted as xxx.xxx.xxx.0.
	*/
	function getCClassNet($ip) {
		return preg_replace('/^([0-9]+\.[0-9]+\.[0-9]+)\.[0-9]+$/','\1.0', $ip);
	}
}

?>
