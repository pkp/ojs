<?php

/**
 * @file plugins/generic/counter/CounterReportDAO.inc.php
 *
 * Copyright (c) 2014-2016 Simon Fraser University Library
 * Copyright (c) 2003-2016 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class CounterReportDAO
 * @ingroup plugins_generic_counter
 *
 * @brief Class for managing COUNTER records.
 */

class CounterReportDAO extends DAO {

	/**
	 * Get the years for which log entries exist in the DB.
	 * @return array
	 */
	function getYears() {
		$result = $this->retrieve(
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
		$result = $this->retrieve(
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
	 * Retrieve a monthly log entry range.
	 * @param $journalId int
	 * @param $begin
	 * @param $end
	 * @return 2D array
	 */
	function getMonthlyLogRange($journalId, $begin, $end) {
		$begin 		= getdate(strtotime($begin));
		$end 		= getdate(strtotime($end));
		$beginComb 	= $begin['year'] * 100 + $begin['mon'];
		$endComb 	= $end['year'] * 100 + $end['mon'];

		$result = $this->retrieve(
			'SELECT * FROM counter_monthly_log
			WHERE journal_id = ? AND year * 100 + month >= ? AND year * 100 + month <= ?',
			array((int) $journalId, (int) $beginComb, (int) $endComb)
		);

		$returner = null;
		if ($result->RecordCount() != 0) {
			$returner = $result->GetArray();
		}

		$result->Close();
		return $returner;
	}

	/**
	 * Retrieve a monthly log entry range.
	 * @param $begin
	 * @param $end
	 * @return 2D array
	 */
	function getMonthlyTotalRange($begin, $end) {
		$begin 		= getdate(strtotime($begin));
		$end 		= getdate(strtotime($end));
		$beginComb 	= $begin['year'] * 100 + $begin['mon'];
		$endComb 	= $end['year'] * 100 + $end['mon'];

		$result = $this->retrieve(
			'SELECT month, SUM(count_html) as count_html, SUM(count_pdf) as count_pdf FROM counter_monthly_log
			WHERE year * 100 + month >= ? AND year * 100 + month <= ?
			GROUP BY month',
			array((int) $beginComb, (int) $endComb)
		);

		$returner = null;
		if ($result->RecordCount() != 0) {
			$returner = $result->GetArray();
		}

		$result->Close();
		return $returner;
	}


	/**
	 * Internal function to create the monthly record
	 */
	function _conditionalCreate($journalId, $year, $month) {
		$result = $this->retrieve(
			'SELECT * FROM counter_monthly_log WHERE journal_id = ? AND year = ? AND month = ?',
			array((int) $journalId, (int) $year, (int) $month)
		);

		$returner = false;
		if ($result->RecordCount() == 0) {
			$this->update(
				'INSERT INTO counter_monthly_log (journal_id, year, month) VALUES (?, ?, ?)',
				array((int) $journalId, (int) $year, (int) $month)
			);
		}

		$result->Close();
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
		// create the monthly record if it does not exist
		$this->_conditionalCreate($journalId, $year, $month);

		if ($month < 1 || $month > 12) return false;

		$this->update(
			"UPDATE counter_monthly_log SET " .
			' count_html = count_html + ' . ($isHtml?'1,':'0,') .
			' count_pdf = count_pdf + ' . ($isPdf?'1':'0') .
			" WHERE journal_id = ? AND year = ? AND month = ?",
			array((int) $journalId, (int) $year, (int) $month)
		);

		return true;
	}
}

?>
