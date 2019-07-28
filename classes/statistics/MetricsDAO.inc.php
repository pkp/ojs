<?php

/**
 * @file classes/statistics/MetricsDAO.inc.php
 *
 * Copyright (c) 2014-2019 Simon Fraser University
 * Copyright (c) 2003-2019 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class MetricsDAO
 * @ingroup statistics
 *
 * @brief Operations for retrieving and adding statistics data.
 */

import('lib.pkp.classes.statistics.PKPMetricsDAO');

class MetricsDAO extends PKPMetricsDAO {

	/**
	 * @copydoc PKPMetricsDAO::getMetrics()
	 */
	function &getMetrics($metricType, $columns = [], $filters = [], $orderBy = [], $range = null, $nonAdditive = true) {
		// Translate the issue dimension to a generic one used in pkp library.
		// Do not move this into foreach: https://github.com/pkp/pkp-lib/issues/1615
		$worker = [&$columns, &$filters, &$orderBy];
		foreach ($worker as &$parameter) { // Reference needed.
			if ($parameter === $filters && array_key_exists(STATISTICS_DIMENSION_ISSUE_ID, $parameter)) {
				$parameter[STATISTICS_DIMENSION_ASSOC_OBJECT_TYPE] = ASSOC_TYPE_ISSUE;
			}

			$key = array_search(STATISTICS_DIMENSION_ISSUE_ID, $parameter);
			if ($key !== false) {
				$parameter[] = STATISTICS_DIMENSION_ASSOC_OBJECT_TYPE;
			}
			unset($parameter);
		}

		return parent::getMetrics($metricType, $columns, $filters, $orderBy, $range, $nonAdditive);
	}

	/**
	 * @copydoc PKPMetricsDAO::foreignKeyLookup()
	 */
	protected function foreignKeyLookup($assocType, $assocId) {
		list($contextId, $sectionId, $assocObjType,
			$assocObjId, $submissionId, $representationId) = parent::foreignKeyLookup($assocType, $assocId);

		$isFile = false;

		if (!$contextId) {
			switch ($assocType) {
				case ASSOC_TYPE_ISSUE_GALLEY:
					$issueGalleyDao = DAORegistry::getDAO('IssueGalleyDAO');
					$issueGalley = $issueGalleyDao->getById($assocId);
					if (!$issueGalley) {
						throw new Exception('Cannot load record: invalid issue galley id.');
					}

					$assocObjType = ASSOC_TYPE_ISSUE;
					$assocObjId = $issueGalley->getIssueId();
					$isFile = true;
					// Don't break but go on to retrieve the issue.
				case ASSOC_TYPE_ISSUE:
					if (!$isFile) {
						$assocObjType = $assocObjId = null;
						$issueId = $assocId;
					} else {
						$issueId = $assocObjId;
					}

					$issueDao = DAORegistry::getDAO('IssueDAO');
					$issue = $issueDao->getById($issueId);

					if (!$issue) {
						throw new Exception('Cannot load record: invalid issue id.');
					}

					$contextId = $issue->getJournalId();
					break;
			}
		}

		return [$contextId, $sectionId, $assocObjType, $assocObjId, $submissionId, $representationId];
	}

	/**
	 * @copydoc PKPMetricsDAO::getAssocObjectInfo()
	 */
	protected function getAssocObjectInfo($submissionId, $contextId) {
		$returnArray = parent::getAssocObjectInfo($submissionId, $contextId);

		// Submissions in OJS are associated with an Issue.
		$publishedArticleDao = DAORegistry::getDAO('PublishedArticleDAO');
		$publishedArticle = $publishedArticleDao->getByArticleId($submissionId, $contextId, true);
		if ($publishedArticle) {
			$returnArray = [ASSOC_TYPE_ISSUE, $publishedArticle->getIssueId()];
		}
		return $returnArray;
	}

	/**
	 * Retrieves several statistics about submissions, with sums and averages
	 * @param $contextId int The context id
	 * @param $dateStart string The start date filter
	 * @param $dateEnd string The end date filter
	 * @param $sectionIds array A list of section IDs to filter the result set
	 * @return array Returns a dictionary with statistics
	 */
	public function getSubmissionStatistics($contextId = null, $dateStart = null, $dateEnd = null, $sectionIds = null) {
		// Access submission constants.
		import('lib.pkp.classes.submission.Submission');

		// Access decision actions constants.
		import('classes.workflow.EditorDecisionActionsManager');

		$params = $where = $whereMinimum = [];
		if($dateStart || $dateEnd) {
			$where[] = 'ed.date_decided ' . ($dateStart && $dateEnd ? 'BETWEEN ? AND ?' : ($dateStart ? '>= ?' : '<= ?'));
			$dateStart && $params[] = $dateStart;
			$dateEnd && $params[] = $dateEnd;
		}

		if ($contextId) {
			$where[] = 's.context_id = ?';
			$params[] = $contextId;
		}

		if (count($sectionIds)) {
			$where[] = 's.section_id IN (' . substr(str_repeat(',?', count($sectionIds)), 1) . ')';
			$params = array_merge($params, $sectionIds);
		}

		$params = array_merge($params, [$dateEnd, $dateStart]);

		$sql = '
			SELECT
				statistics.*,
				-- totals averaged by year
				statistics.submission_received / years.count AS avg_submission_received,
				statistics.submission_accepted / years.count AS avg_submission_accepted,
				statistics.submission_published / years.count AS avg_submission_published,
				statistics.submission_declined_initial / years.count AS avg_submission_declined_initial,
				statistics.submission_declined / years.count AS avg_submission_declined,
				statistics.submission_declined_other / years.count AS avg_submission_declined_other,
				statistics.submission_declined_total / years.count AS avg_submission_declined_total
			FROM (
				SELECT
					-- active submissions
					COUNT(CASE WHEN s.status = ' . STATUS_QUEUED . ' THEN 0 END) AS active_total,
					COUNT(CASE WHEN s.status = ' . STATUS_QUEUED . ' AND s.stage_id = ' . WORKFLOW_STAGE_ID_SUBMISSION . ' THEN 0 END) AS active_submission,
					COUNT(CASE WHEN s.status = ' . STATUS_QUEUED . ' AND s.stage_id = ' . WORKFLOW_STAGE_ID_INTERNAL_REVIEW . ' THEN 0 END) AS active_internal_review,
					COUNT(CASE WHEN s.status = ' . STATUS_QUEUED . ' AND s.stage_id = ' . WORKFLOW_STAGE_ID_EXTERNAL_REVIEW . ' THEN 0 END) AS active_external_review,
					COUNT(CASE WHEN s.status = ' . STATUS_QUEUED . ' AND s.stage_id = ' . WORKFLOW_STAGE_ID_EDITING . ' THEN 0 END) AS active_editing,
					COUNT(CASE WHEN s.status = ' . STATUS_QUEUED . ' AND s.stage_id = ' . WORKFLOW_STAGE_ID_PRODUCTION . ' THEN 0 END) AS active_production,
				
					-- all submissions
					COUNT(0) AS submission_received,
					COUNT(CASE WHEN ed.decision = ' . SUBMISSION_EDITOR_DECISION_ACCEPT . ' THEN 0 END) AS submission_accepted,
					COUNT(CASE WHEN s.status = ' . STATUS_PUBLISHED . ' AND EXISTS (
						SELECT 0
						FROM published_submissions ps
						WHERE ps.submission_id = s.submission_id
					) THEN 0 END) AS submission_published,
					COUNT(CASE WHEN ed.decision = ' . SUBMISSION_EDITOR_DECISION_INITIAL_DECLINE . ' THEN 0 END) AS submission_declined_initial,
					COUNT(CASE WHEN ed.decision = ' . SUBMISSION_EDITOR_DECISION_DECLINE . ' THEN 0 END) AS submission_declined,
					COUNT(
						CASE WHEN s.status = ' . STATUS_DECLINED . '
							AND (ed.decision NOT IN (' . SUBMISSION_EDITOR_DECISION_DECLINE . ', ' . SUBMISSION_EDITOR_DECISION_INITIAL_DECLINE . ')
								OR ed.decision IS NULL)
							THEN 0
						END
					) AS submission_declined_other,
					COUNT(CASE WHEN s.status = ' . STATUS_DECLINED . ' THEN 0 END) AS submission_declined_total,
				
					-- average days to decide
					AVG(
						CASE WHEN ed.decision = ' . SUBMISSION_EDITOR_DECISION_ACCEPT . '
							THEN DATEDIFF(ed.date_decided, COALESCE(s.date_submitted, s.last_modified))
						END
					) AS submission_days_to_accept,
					AVG(
						CASE WHEN ed.decision IN (' . SUBMISSION_EDITOR_DECISION_DECLINE . ', ' . SUBMISSION_EDITOR_DECISION_INITIAL_DECLINE . ')
							THEN DATEDIFF(ed.date_decided, COALESCE(s.date_submitted, s.last_modified))
						END
					) AS submission_days_to_reject,
					AVG(DATEDIFF(first_ed.date_decided, COALESCE(s.date_submitted, s.last_modified))) AS submission_days_to_first_decide,
					AVG(DATEDIFF(ed.date_decided, COALESCE(s.date_submitted, s.last_modified))) AS submission_days_to_decide,
				
					-- acceptance/rejection rate
					COUNT(CASE WHEN ed.decision = ' . SUBMISSION_EDITOR_DECISION_ACCEPT . ' THEN 0 END) / COUNT(0) * 100 AS submission_acceptance_rate,
					COUNT(CASE WHEN s.status = ' . STATUS_DECLINED . ' THEN 0 END) / COUNT(0) * 100 AS submission_rejection_rate,
					COUNT(CASE WHEN ed.decision = ' . SUBMISSION_EDITOR_DECISION_INITIAL_DECLINE . ' THEN 0 END) / COUNT(0) * 100 AS submission_declined_initial_rate,
					COUNT(CASE WHEN ed.decision = ' . SUBMISSION_EDITOR_DECISION_DECLINE . ' THEN 0 END) / COUNT(0) * 100 AS submission_declined_rate,
					COUNT(
						CASE WHEN s.status = ' . STATUS_DECLINED . '
							AND (
								ed.decision NOT IN (' . SUBMISSION_EDITOR_DECISION_DECLINE . ', ' . SUBMISSION_EDITOR_DECISION_INITIAL_DECLINE . ')
								OR ed.decision IS NULL
							)
							THEN 0
						END
					) / COUNT(0) * 100 AS submission_declined_other_rate
				FROM submissions s
				LEFT JOIN edit_decisions first_ed
					ON first_ed.edit_decision_id = (
						SELECT ed.edit_decision_id
						FROM edit_decisions ed
						WHERE
							ed.submission_id = s.submission_id
						ORDER BY
							ed.stage_id ASC, ed.round ASC, ed.date_decided ASC
						LIMIT 1
					)
				LEFT JOIN edit_decisions ed
					ON ed.edit_decision_id = (
						SELECT ed.edit_decision_id
						FROM edit_decisions ed
						WHERE
							ed.submission_id = s.submission_id
						ORDER BY
							ed.stage_id DESC, ed.round DESC, ed.date_decided DESC
						LIMIT 1
					)
				WHERE
					-- skip unfinished submissions
					s.submission_progress = 0
					' . ($where ? ' AND ' . implode(' AND ', $where) : '') . '
			) AS statistics
			INNER JOIN (
				SELECT
					YEAR(COALESCE(?, CURRENT_TIMESTAMP))
					- YEAR(
						COALESCE(
							?,
							(
								SELECT s.date_submitted
								FROM submissions s
								WHERE s.date_submitted IS NOT NULL
								ORDER BY s.date_submitted
								LIMIT 1
							),
							CURRENT_TIMESTAMP
						)
					) + 1 AS count
			) AS years
				ON 1 = 1
		';
		return $this->retrieve($sql, $params)->GetRowAssoc();
	}

	/**
	 * Retrieves statistics about user registrations, with sums and averages
	 * @param $contextId int The context id
	 * @param $dateStart string The start date filter
	 * @param $dateEnd string The end date filter
	 * @return array Returns a dictionary with statistics
	 */
	public function getUserStatistics($contextId = null, $dateStart = null, $dateEnd = null) {
		$where = $params = [];

		if ($contextId) {
			$where[] = 'ug.context_id = ?';
			$params[] = $contextId;
		}

		if ($dateStart || $dateEnd) {
			$where[] = 'u.date_registered ' . ($dateStart && $dateEnd ? 'BETWEEN ? AND ?' : ($dateStart ? '>= ?' : '<= ?'));
			$dateStart && $params[] = $dateStart;
			$dateEnd && $params[] = $dateEnd;
		}

		$params = array_merge($params, $params, [$dateEnd, $dateStart]);

		$sql = '
			SELECT
				statistics.*, statistics.total / years.count AS average
			FROM (
				SELECT roles.role_id, COUNT(0) AS total
				FROM (
					-- grouping by user_id and role_id to remove duplicates
					SELECT ug.role_id
					FROM user_user_groups uug
					INNER JOIN users u
						ON u.user_id = uug.user_id
					INNER JOIN user_groups ug
						ON ug.user_group_id = uug.user_group_id
					WHERE 1 = 1
					' . ($where ? ' AND ' . implode(' AND ', $where) : '') . '

					GROUP BY uug.user_id, ug.role_id
				) roles
				GROUP BY roles.role_id

				UNION ALL

				SELECT 0, COUNT(0)
				FROM users u
				WHERE EXISTS(
					SELECT 0
					FROM user_user_groups uug
					LEFT JOIN user_groups ug
						ON ug.user_group_id = uug.user_group_id
					WHERE uug.user_id = u.user_id
					' . ($where ? ' AND ' . implode(' AND ', $where) : '') . '
				)
			) AS statistics
			INNER JOIN (
				SELECT
					YEAR(COALESCE(?, CURRENT_TIMESTAMP))
					- YEAR(
						COALESCE(
							?,
							(
								SELECT u.date_registered
								FROM users u
								WHERE u.date_registered IS NOT NULL
								ORDER BY u.date_registered
								LIMIT 1
							),
							CURRENT_TIMESTAMP
						)
					) + 1 AS count
			) AS years
				ON 1 = 1
			GROUP BY
				role_id
		';
		return array_reduce($this->retrieve($sql, $params)->GetAll(), function ($data, $row) {
			$data[$row['role_id']] = $row;
			return $data;
		}, []);
	}
}
