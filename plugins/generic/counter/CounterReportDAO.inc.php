<?php

/**
 * @file plugins/generic/counter/CounterReportDAO.inc.php
 *
 * Copyright (c) 2003-2009 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class CounterReportDAO
 * @ingroup plugins_generic_counter
 *
 * @brief Class for managing COUNTER records.
 */

// $Id$


class CounterReportDAO extends DAO {
	/**
	 * Get the month labels named in the database.
	 * @return array
	 */
	function getMonthLabels() {
		return array('count_jan', 'count_feb', 'count_mar', 'count_apr', 'count_may', 'count_jun', 'count_jul', 'count_aug', 'count_sep', 'count_oct', 'count_nov', 'count_dec');
	}

	/**
	 * Get the years for which log entries exist in the DB.
	 * @return array
	 */
	function getYears() {
		$result =& $this->retrieve(
			'SELECT DISTINCT year FROM counter_monthly_log'
		);
		$years = array();
		while (!$result->EOF) {
			$row = $result->GetRowAssoc(false);
			$years[] = $row['year'];
			$result->MoveNext();
		}
		$result->Close();
		return $years;
	}

	/**
	 * Get the valid journal IDs for which log entries exist in the DB.
	 * @return array
	 */
	function getJournalIds() {
		$result =& $this->retrieve(
			'SELECT DISTINCT journal_id FROM counter_monthly_log l'
		);
		$journalIds = array();
		while (!$result->EOF) {
			$row = $result->GetRowAssoc(false);
			$journalIds[] = $row['journal_id'];
			$result->MoveNext();
		}
		$result->Close();
		return $journalIds;
	}

	/**
	 * Retrieve a monthly log entry by date.
	 * @param $journalId int
	 * @param $year int
	 * @return array
	 */
	function getMonthlyLog($journalId, $year) {
		$result =& $this->retrieve(
			'SELECT * FROM counter_monthly_log WHERE journal_id = ? AND year = ?',
			array((int) $journalId, (int) $year)
		);

		$returner = null;
		if ($result->RecordCount() != 0) {
			$returner = $result->GetRowAssoc(false);
		}

		$result->Close();
		unset($result);

		return $returner;
	}

	/**
	 * Retrieve a monthly total by date.
	 * @param $year int
	 * @return array
	 */
	function buildMonthlyTotalLog($year) {
		$sql =	'SELECT	';
		$months = $this->getMonthLabels();
		for ($i=0; $i<12; $i++) {
			$sql .= "SUM($months[$i]) AS $months[$i], ";
		}
		$sql .= '	SUM(count_ytd_total) AS count_ytd_total,
				SUM(count_ytd_html) AS count_ytd_html,
				SUM(count_ytd_pdf) AS count_ytd_pdf
			FROM	counter_monthly_log
			WHERE	year = ?';

		$result =& $this->retrieve(
			$sql,
			array((int) $year)
		);

		if ($result->RecordCount() != 0) {
			$returner = $result->GetRowAssoc(false);
		} else {
			$returner = array();
			for ($i=0; $i<12; $i++) $returner[$months[$i]] = 0;
			$returner['count_ytd_total'] = 0;
			$returner['count_ytd_html'] = 0;
			$returner['count_ytd_pdf'] = 0;
		}

		$result->Close();
		unset($result);

		return $returner;
	}

	/**
	 * Fetch a monthly log entry, inserting if none is found.
	 * @param $journalId int
	 * @param $year int
	 */	
	function buildMonthlyLog($journalId, $year) {
		$monthlyLog = $this->getMonthlyLog($journalId, $year);
		if ($monthlyLog === null) {
			$this->update(
				'INSERT INTO counter_monthly_log (journal_id, year) VALUES (?, ?)',
				array((int) $journalId, (int) $year)
			);
			$monthlyLog = $this->getMonthlyLog($journalId, $year);
		}
		return $monthlyLog;
	}

	/**
	 * Increment counters for a journal and year.
	 * @param $journalId int
	 * @param $year int
	 * @param $month int
	 * @param $isPdf boolean
	 * @param $isHtml boolean
	 * @return boolean
	 */
	function incrementCount($journalId, $year, $month, $isPdf, $isHtml) {
		// Ensure that the log entry exists. Is this necessary,
		// or can we get an update count and insert if it's 0?
		$this->buildMonthlyLog($journalId, $year);

		// Validate months
		$months = $this->getMonthLabels();
		if (!isset($months[(int) $month])) return false;
		$monthCol = $months[(int) $month];

		$this->update(
			"UPDATE counter_monthly_log SET " .
			"count_ytd_total = count_ytd_total + 1," .
			"$monthCol = $monthCol + 1" .
			($isHtml?', count_ytd_html = count_ytd_html + 1':'') .
			($isPdf?', count_ytd_pdf = count_ytd_pdf + 1':'') .
			" WHERE journal_id = ? AND year = ?",
			array((int) $journalId, (int) $year)
		);

		return true;
	}

	function getOldLogFilename() {
		return dirname(__FILE__) . '/log.txt';
	}

	function upgradeFromLogFile() {
		$file = $this->getOldLogFilename();
		if (!file_exists($file)) return true;

		$fp = fopen($file, 'r');
		if (!$fp) return true;

		$journalDao =& DAORegistry::getDAO('JournalDAO');
		$journals =& $journalDao->getJournals();
		$journalUrlMap = array();
		while ($journal =& $journals->next()) {
			$journalUrlMap[Request::url($journal->getPath(), 'index')] = $journal->getJournalId();
			unset($journal);
		}
		unset($journals);

		while ($data = fgets($fp, 4096)) {
			$fragments = explode("\t", trim($data));
			if (sizeof($fragments) < 10) continue;
			list($stamp, $user, $site, $journal, $publisher, $printIssn, $onlineIssn, $type, $value, $journalUrl) = $fragments;

			if (!isset($journalUrlMap[$journalUrl])) continue; // Unable to match
			if ($type == 'search') continue; // Unused log entry

			$journalId = $journalUrlMap[$journalUrl];
			$stamp = strtotime($stamp);
			$year = strftime('%Y', $stamp);
			$month = strftime('%m', $stamp) - 1; // 0-based

			$this->incrementCount($journalId, $year, $month, $type == 'pdf', $type == 'html');
		}

		fclose ($fp);
		return true;
	}
}

?>
