<?php

/**
 * @file classes/journal/JournalStatisticsDAO.inc.php
 *
 * Copyright (c) 2003-2013 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class JournalStatisticsDAO
 * @ingroup journal
 *
 * @brief Operations for retrieving journal statistics.
 */

define('REPORT_TYPE_JOURNAL',	0x00001);
define('REPORT_TYPE_EDITOR',	0x00002);
define('REPORT_TYPE_REVIEWER',	0x00003);
define('REPORT_TYPE_SECTION',	0x00004);

class JournalStatisticsDAO extends DAO {
	/**
	 * Get statistics about articles in the system.
	 * Returns a map of name => value pairs.
	 * @param $journalId int The journal to fetch statistics for
	 * @param $sectionId int The section to query stats for (optional)
	 * @param $dateStart date The submit date to search from; optional
	 * @param $dateEnd date The submit date to search to; optional
	 * @return array
	 */
	function getArticleStatistics($journalId, $sectionIds = null, $dateStart = null, $dateEnd = null) {
		// Bring in status constants
		import('classes.article.Article');

		$params = array($journalId);
		if (!empty($sectionIds)) {
			$sectionSql = ' AND (a.section_id = ?';
			$params[] = array_shift($sectionIds);
			foreach ($sectionIds as $sectionId) {
				$sectionSql .= ' OR a.section_id = ?';
				$params[] = $sectionId;
			}
			$sectionSql .= ')';
		} else $sectionSql = '';

		$sql =	'SELECT	a.submission_id,
				a.date_submitted,
				pa.date_published,
				pa.published_submission_id,
				d.decision,
				a.status
			FROM	submissions a
				LEFT JOIN published_submissions pa ON (a.submission_id = pa.submission_id)
				LEFT JOIN edit_decisions d ON (d.submission_id = a.submission_id)
			WHERE	a.journal_id = ?' .
			($dateStart !== null ? ' AND a.date_submitted >= ' . $this->datetimeToDB($dateStart) : '') .
			($dateEnd !== null ? ' AND a.date_submitted <= ' . $this->datetimeToDB($dateEnd) : '') .
			$sectionSql .
			' ORDER BY a.submission_id, d.date_decided DESC';

		$result = $this->retrieve($sql, $params);

		$returner = array(
			'numSubmissions' => 0,
			'numReviewedSubmissions' => 0,
			'numPublishedSubmissions' => 0,
			'submissionsAccept' => 0,
			'submissionsDecline' => 0,
			'submissionsAcceptPercent' => 0,
			'submissionsDeclinePercent' => 0,
			'daysToPublication' => 0
		);

		// Track which articles we're including
		$articleIds = array();

		$totalTimeToPublication = 0;
		$timeToPublicationCount = 0;

		while (!$result->EOF) {
			$row = $result->GetRowAssoc(false);

			// For each article, pick the most recent editor
			// decision only and ignore the rest. Depends on sort
			// order. FIXME -- there must be a better way of doing
			// this that's database independent.
			if (!in_array($row['submission_id'], $articleIds)) {
				$articleIds[] = $row['submission_id'];
				$returner['numSubmissions']++;

				if (!empty($row['published_submission_id']) && $row['status'] == STATUS_PUBLISHED) {
					$returner['numPublishedSubmissions']++;
				}

				if (!empty($row['date_submitted']) && !empty($row['date_published']) && $row['status'] == STATUS_PUBLISHED) {
					$timeSubmitted = strtotime($this->datetimeFromDB($row['date_submitted']));
					$timePublished = strtotime($this->datetimeFromDB($row['date_published']));
					if ($timePublished > $timeSubmitted) {
						$totalTimeToPublication += ($timePublished - $timeSubmitted);
						$timeToPublicationCount++;
					}
				}

				import('classes.submission.common.Action');
				switch ($row['decision']) {
					case SUBMISSION_EDITOR_DECISION_ACCEPT:
						$returner['submissionsAccept']++;
						$returner['numReviewedSubmissions']++;
						break;
					case SUBMISSION_EDITOR_DECISION_DECLINE:
						$returner['submissionsDecline']++;
						$returner['numReviewedSubmissions']++;
						break;
				}
			}

			$result->MoveNext();
		}

		$result->Close();
		unset($result);

		// Calculate percentages where necessary
		if ($returner['numReviewedSubmissions'] != 0) {
			$returner['submissionsAcceptPercent'] = round($returner['submissionsAccept'] * 100 / $returner['numReviewedSubmissions']);
			$returner['submissionsDeclinePercent'] = round($returner['submissionsDecline'] * 100 / $returner['numReviewedSubmissions']);
		}

		if ($timeToPublicationCount != 0) {
			// Keep one sig fig
			$returner['daysToPublication'] = round($totalTimeToPublication / $timeToPublicationCount / 60 / 60 / 24);
		}

		return $returner;
	}

	/**
	 * Get statistics about users in the system.
	 * Returns a map of name => value pairs.
	 * @param $journalId int The journal to fetch statistics for
	 * @param $dateStart date optional
	 * @param $dateEnd date optional
	 * @return array
	 */
	function getUserStatistics($journalId, $dateStart = null, $dateEnd = null) {
		$roleDao = DAORegistry::getDAO('RoleDAO');

		// Get count of total users for this journal
		$result =& $this->retrieve(
			'SELECT COUNT(DISTINCT r.user_id) FROM roles r, users u WHERE r.user_id = u.user_id AND r.journal_id = ?' .
			($dateStart !== null ? ' AND u.date_registered >= ' . $this->datetimeToDB($dateStart) : '') .
			($dateEnd !== null ? ' AND u.date_registered <= ' . $this->datetimeToDB($dateEnd) : ''),
			$journalId
		);

		$returner = array(
			'totalUsersCount' => $result->fields[0]
		);

		$result->Close();
		unset($result);

		// Get user counts for each role.
		$result =& $this->retrieve(
			'SELECT r.role_id, COUNT(r.user_id) AS role_count FROM roles r LEFT JOIN users u ON (r.user_id = u.user_id) WHERE r.journal_id = ?' .
			($dateStart !== null ? ' AND u.date_registered >= ' . $this->datetimeToDB($dateStart) : '') .
			($dateEnd !== null ? ' AND u.date_registered <= ' . $this->datetimeToDB($dateEnd) : '') .
			'GROUP BY r.role_id',
			$journalId
		);

		while (!$result->EOF) {
			$row = $result->GetRowAssoc(false);
			$role = $roleDao->newDataObject();
			$role->setId($row['role_id']);
			$returner[$role->getPath()] = $row['role_count'];
			$result->MoveNext();
		}

		$result->Close();
		unset($result);

		return $returner;
	}

	/**
	 * Get statistics about subscriptions.
	 * @param $journalId int The journal to fetch statistics for
	 * @param $dateStart date optional
	 * @param $dateEnd date optional
	 * @return array
	 */
	function getSubscriptionStatistics($journalId, $dateStart = null, $dateEnd = null) {
		$result =& $this->retrieve(
			'SELECT	st.type_id,
				sts.setting_value AS type_name,
				count(s.subscription_id) AS type_count
			FROM	subscription_types st
				LEFT JOIN journals j ON (j.journal_id = st.journal_id)
				LEFT JOIN subscription_type_settings sts ON (st.type_id = sts.type_id AND sts.setting_name = ? AND sts.locale = j.primary_locale),
				subscriptions s
			WHERE	st.journal_id = ?
				AND s.type_id = st.type_id' .
			($dateStart !== null ? ' AND s.date_start >= ' . $this->datetimeToDB($dateStart) : '') .
			($dateEnd !== null ? ' AND s.date_start <= ' . $this->datetimeToDB($dateEnd) : '') .
			' GROUP BY st.type_id, sts.setting_value',
			array('name', $journalId)
		);

		$returner = array();

		while (!$result->EOF) {
			$row = $result->getRowAssoc(false);
			$returner[$row['type_id']] = array(
				'name' => $row['type_name'],
				'count' => $row['type_count']
			);
			$result->MoveNext();
		}
		$result->Close();
		unset($result);

		return $returner;
	}

	/**
	 * Get statistics about issues in the system.
	 * Returns a map of name => value pairs.
	 * @param $journalId int The journal to fetch statistics for
	 * @param $dateStart date The publish date to search from; optional
	 * @param $dateEnd date The publish date to search to; optional
	 * @return array
	 */
	function getIssueStatistics($journalId, $dateStart = null, $dateEnd = null) {
		$result =& $this->retrieve(
			'SELECT COUNT(*) AS count, published FROM issues WHERE journal_id = ?' .
			($dateStart !== null ? ' AND date_published >= ' . $this->datetimeToDB($dateStart) : '') .
			($dateEnd !== null ? ' AND date_published <= ' . $this->datetimeToDB($dateEnd) : '') .
			' GROUP BY published',
			$journalId
		);

		$returner = array(
			'numPublishedIssues' => 0,
			'numUnpublishedIssues' => 0
		);

		while (!$result->EOF) {
			$row = $result->GetRowAssoc(false);

			if ($row['published']) {
				$returner['numPublishedIssues'] = $row['count'];
			} else {
				$returner['numUnpublishedIssues'] = $row['count'];
			}
			$result->MoveNext();
		}

		$result->Close();
		unset($result);

		$returner['numIssues'] = $returner['numPublishedIssues'] + $returner['numUnpublishedIssues'];

		return $returner;
	}

	/**
	 * Get statistics about reviewers in the system.
	 * Returns a map of name => value pairs.
	 * @param $journalId int The journal to fetch statistics for
	 * @param $dateStart date The publish date to search from; optional
	 * @param $dateEnd date The publish date to search to; optional
	 * @return array
	 */
	function getReviewerStatistics($journalId, $sectionIds, $dateStart = null, $dateEnd = null) {
		$params = array($journalId);
		if (!empty($sectionIds)) {
			$sectionSql = ' AND (a.section_id = ?';
			$params[] = array_shift($sectionIds);
			foreach ($sectionIds as $sectionId) {
				$sectionSql .= ' OR a.section_id = ?';
				$params[] = $sectionId;
			}
			$sectionSql .= ')';
		} else $sectionSql = '';

		$sql =	'SELECT	a.submission_id,
				af.date_uploaded AS date_rv_uploaded,
				r.review_id,
				u.date_registered,
				r.reviewer_id,
				r.quality,
				r.date_assigned,
				r.date_completed
			FROM	submissions a,
				submission_files af,
				review_assignments r
				LEFT JOIN users u ON (u.user_id = r.reviewer_id)
			WHERE	a.journal_id = ?
				AND r.submission_id = a.submission_id
				AND af.submission_id = a.submission_id
				AND af.file_id = a.review_file_id
				AND af.revision = 1' .
			($dateStart !== null ? ' AND a.date_submitted >= ' . $this->datetimeToDB($dateStart) : '') .
			($dateEnd !== null ? ' AND a.date_submitted <= ' . $this->datetimeToDB($dateEnd) : '') .
			$sectionSql;
		$result =& $this->retrieve($sql, $params);

		$returner = array(
			'reviewsCount' => 0,
			'reviewerScore' => 0,
			'daysPerReview' => 0,
			'reviewerAddedCount' => 0,
			'reviewerCount' => 0,
			'reviewedSubmissionsCount' => 0
		);

		$scoredReviewsCount = 0;
		$totalScore = 0;
		$completedReviewsCount = 0;
		$totalElapsedTime = 0;
		$reviewerList = array();
		$articleIds = array();

		while (!$result->EOF) {
			$row = $result->GetRowAssoc(false);
			$returner['reviewsCount']++;
			if (!empty($row['quality'])) {
				$scoredReviewsCount++;
				$totalScore += $row['quality'];
			}

			$articleIds[] = $row['submission_id'];

			if (!empty($row['reviewer_id']) && !in_array($row['reviewer_id'], $reviewerList)) {
				$returner['reviewerCount']++;
				$dateRegistered = strtotime($this->datetimeFromDB($row['date_registered']));
				if (($dateRegistered >= $dateStart || $dateStart === null) && ($dateRegistered <= $dateEnd || $dateEnd == null)) {
					$returner['reviewerAddedCount']++;
				}
				array_push($reviewerList, $row['reviewer_id']);
			}

			if (!empty($row['date_assigned']) && !empty($row['date_completed'])) {
				$timeReviewVersionUploaded = strtotime($this->datetimeFromDB($row['date_rv_uploaded']));
				$timeCompleted = strtotime($this->datetimeFromDB($row['date_completed']));
				if ($timeCompleted > $timeReviewVersionUploaded) {
					$completedReviewsCount++;
					$totalElapsedTime += ($timeCompleted - $timeReviewVersionUploaded);
				}
			}
			$result->MoveNext();
		}

		$result->Close();
		unset($result);

		if ($scoredReviewsCount > 0) {
			// To one decimal place
			$returner['reviewerScore'] = round($totalScore * 10 / $scoredReviewsCount) / 10;
		}
		if ($completedReviewsCount > 0) {
			$seconds = $totalElapsedTime / $completedReviewsCount;
			$returner['daysPerReview'] = $seconds / 60 / 60 / 24;
		}

		$articleIds = array_unique($articleIds);
		$returner['reviewedSubmissionsCount'] = count($articleIds);

		return $returner;
	}
}

?>
