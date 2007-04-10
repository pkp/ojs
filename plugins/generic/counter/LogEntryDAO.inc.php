<?php

/**
 * LogEntryDAO.inc.php
 *
 * Copyright (c) 2003-2007 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package plugins
 *
 * Class for Log Entry DAO.
 * Operations for retrieving and modifying log entry objects.
 *
 * $Id$
 */

require_once (dirname(__FILE__) . '/LogEntry.inc.php');

class LogEntryDAO extends DAO {

	/**
	 * Constructor.
	 */
	function LogEntryDAO() {
		parent::DAO();
	}

	function getLogFilename() {
		return (dirname(__FILE__) . '/log.txt');
	}

	function addEntry(&$logEntry, $file = null) {
		if (!isset($file)) $file = $this->getLogFilename();

		$stamp = strtr(Core::getCurrentDate(), "\t", " ");
		$user = strtr($logEntry->getUser(), "\t", " ");
		$site = strtr($logEntry->getSite(), "\t", " ");
		$journal = strtr($logEntry->getJournal(), "\t", " ");
		$publisher = strtr($logEntry->getPublisher(), "\t", " ");
		$printIssn = strtr($logEntry->getPrintIssn(), "\t", " ");
		$onlineIssn = strtr($logEntry->getOnlineIssn(), "\t", " ");
		$type = strtr($logEntry->getType(), "\t", " ");
		$value = strtr($logEntry->getValue(), "\t", " ");
		$journalUrl = strtr($logEntry->getJournalUrl(), "\t", " ");

		$line = "$stamp\t$user\t$site\t$journal\t$publisher\t$printIssn\t$onlineIssn\t$type\t$value\t$journalUrl\n";

		$fp = fopen($file, 'a');
		if (!$fp) return false;

		if (!flock($fp, LOCK_EX)) {
			fclose($fp);
			return false;
		}

		fwrite($fp, $line);
		fclose($fp);
	}

	function &parse($file = null, $year = null) {
		if (!isset($file)) $file = $this->getLogFilename();

		$entries = array();

		$fp = fopen($file, 'r');
		if (!$fp) {
			$result = false;
			return $result;
		}

		while ($data = fgets($fp, 4096)) {
			list($stamp, $user, $site, $journal, $publisher, $printIssn, $onlineIssn, $type, $value, $journalUrl) = explode("\t", trim($data));
			$entryYear = strftime('%Y', strtotime($stamp));
			if ($year === null || $entryYear == $year) {
				$logEntry = &new LogEntry();
				$logEntry->setStamp($stamp);
				$logEntry->setUser($user);
				$logEntry->setSite($site);
				$logEntry->setJournal($journal);
				$logEntry->setPublisher($publisher);
				$logEntry->setPrintIssn($printIssn);
				$logEntry->setOnlineIssn($onlineIssn);
				$logEntry->setType($type);
				$logEntry->setValue($value);
				$logEntry->setJournalUrl($journalUrl);
				$entries[] = &$logEntry;
			}
		}

		fclose ($fp);
		return $entries;
	}

	function clearLog($file = null) {
		if (!isset($file)) $file = $this->getLogFilename();

		$fp = fopen($file, 'w');
		if (!$fp) return false;

		if (!flock($fp, LOCK_EX)) {
			fclose($fp);
			return false;
		}

		fclose($fp);
	}
}

?>
