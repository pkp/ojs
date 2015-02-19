<?php

/**
 * @file plugins/generic/timedView/TimedViewReportDAO.inc.php
 *
 * Copyright (c) 2014-2015 Simon Fraser University Library
 * Copyright (c) 2003-2015 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class TimedViewReportDAO
 * @ingroup plugins_generic_timedView
 *
 * @brief Timed view report DAO
 */

class TimedViewReportDAO extends DAO {
	/**
	 * Get the abstract view count for each article in a journal.
	 * @param $journalId int
	 * @param $startDate int
	 * @param $endDate int
	 * @return array
	 */
	function getAbstractViewCount($journalId, $startDate = null, $endDate = null) {
		if ($startDate && $endDate) {
			$result = $this->retrieve(
				sprintf('SELECT tvl.submission_id, COUNT(tvl.submission_id) AS total_abstract_views
						FROM timed_views_log tvl
						WHERE tvl.galley_id IS NULL
							AND tvl.journal_id = ?
							AND tvl.date >= %s
							AND tvl.date <= %s
						GROUP BY submission_id',
						$this->datetimeToDB($startDate),
						$this->datetimeToDB($endDate)),
						array((int) $journalId)
				);
		} else {
			$result = $this->retrieve(
				'SELECT tvl.submission_id, COUNT(tvl.submission_id) AS total_abstract_views
						FROM timed_views_log tvl
						WHERE tvl.galley_id IS NULL
							AND tvl.journal_id = ?
						GROUP BY submission_id',
				array((int) $journalId)
			);
		}
		return new DBRowIterator($result);
	}

	/**
	 * Get the view count for each article's galleys.
	 * @param $journalId int
	 * @param $startDate int
	 * @param $endDate int
	 * @return array
	 */
	function getGalleyViewCountsForArticle($articleId, $startDate = null, $endDate = null) {
		if ($startDate && $endDate) {
			$result = $this->retrieve(
				sprintf('SELECT tvl.submission_id, tvl.galley_id, COUNT(tvl.galley_id) AS total_galley_views, ag.label
						FROM timed_views_log tvl
						LEFT JOIN submission_galleys ag ON (tvl.galley_id = ag.galley_id)
						WHERE tvl.galley_id IS NOT NULL
							AND tvl.date >= %s
							AND tvl.date <= %s
							AND tvl.submission_id = ?
						GROUP BY galley_id, submission_id',
						$this->datetimeToDB($startDate),
						$this->datetimeToDB($endDate)),
						array((int) $articleId)
				);
		} else {
			$result = $this->retrieve(
				'SELECT tvl.submission_id, tvl.galley_id, COUNT(tvl.galley_id) AS total_galley_views, ag.label
						FROM timed_views_log tvl
						LEFT JOIN submission_galleys ag ON (tvl.galley_id = ag.galley_id)
						WHERE tvl.galley_id IS NOT NULL
							AND tvl.submission_id = ?
						GROUP BY galley_id, submission_id',
				array((int) $articleId)
			);
		}
		return new DBRowIterator($result);
	}

	/**
	 * Increment the view count for a published article
	 * @param $journalId int
	 * @param $pubId int
	 * @param $ipAddress string
	 * @param $userAgent string
	 */
	function incrementViewCount($journalId, $articleId, $galleyId = null, $ipAddress = null, $userAgent = null) {
		$this->update(
			sprintf('INSERT INTO timed_views_log
				(submission_id, galley_id, journal_id, date, ip_address, user_agent)
				VALUES
				(?, ?, ?, %s, ?, ?)',
				$this->datetimeToDB(Core::getCurrentDate())),
			array(
				(int) $articleId,
				isset($galleyId) ? (int) $galleyId : null,
				(int) $journalId,
				$ipAddress,
				$userAgent
			)
		);
	}

	/**
	 * Clear records prior to the given date
	 * @param $dateClear string
	 * @param $journalId int
	 */
	function clearLogs($dateClear, $journalId) {
		return $this->update(sprintf('DELETE FROM timed_views_log WHERE date < %s AND journal_id = ?', $this->datetimeToDB($dateClear)), (int) $journalId);
	}
}

?>
