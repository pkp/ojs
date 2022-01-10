<?php

/**
 * @file classes/statistics/MetricsDAO.inc.php
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2003-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class MetricsDAO
 * @ingroup statistics
 *
 * @brief Operations for retrieving and adding statistics data.
 */

namespace APP\statistics;

use APP\facades\Repo;
use PKP\db\DAORegistry;
use PKP\statistics\PKPMetricsDAO;

use PKP\statistics\PKPStatisticsHelper;

class MetricsDAO extends PKPMetricsDAO
{
    /**
     * @copydoc PKPMetricsDAO::getMetrics()
     *
     * @param null|mixed $range
     */
    public function &getMetrics($metricType, $columns = [], $filters = [], $orderBy = [], $range = null, $nonAdditive = true)
    {
        // Translate the issue dimension to a generic one used in pkp library.
        // Do not move this into foreach: https://github.com/pkp/pkp-lib/issues/1615
        $worker = [&$columns, &$filters, &$orderBy];
        foreach ($worker as &$parameter) { // Reference needed.
            if ($parameter === $filters && array_key_exists(StatisticsHelper::STATISTICS_DIMENSION_ISSUE_ID, $parameter)) {
                $parameter[PKPStatisticsHelper::STATISTICS_DIMENSION_ASSOC_OBJECT_TYPE] = ASSOC_TYPE_ISSUE;
            }

            if (in_array(StatisticsHelper::STATISTICS_DIMENSION_ISSUE_ID, $parameter)) {
                $parameter[] = PKPStatisticsHelper::STATISTICS_DIMENSION_ASSOC_OBJECT_TYPE;
            }
            unset($parameter);
        }

        return parent::getMetrics($metricType, $columns, $filters, $orderBy, $range, $nonAdditive);
    }

    /**
     * @copydoc PKPMetricsDAO::foreignKeyLookup()
     *
     * @param null|mixed $representationId
     */
    protected function foreignKeyLookup($assocType, $assocId, $representationId = null)
    {
        [$contextId, $sectionId, $assocObjType,
            $assocObjId, $submissionId, $representationId] = parent::foreignKeyLookup($assocType, $assocId, $representationId);

        $isFile = false;

        if (!$contextId) {
            switch ($assocType) {
                case ASSOC_TYPE_ISSUE_GALLEY:
                    $issueGalleyDao = DAORegistry::getDAO('IssueGalleyDAO'); /** @var IssueGalleyDAO $issueGalleyDao */
                    $issueGalley = $issueGalleyDao->getById($assocId);
                    if (!$issueGalley) {
                        throw new Exception('Cannot load record: invalid issue galley id.');
                    }

                    $assocObjType = ASSOC_TYPE_ISSUE;
                    $assocObjId = $issueGalley->getIssueId();
                    $isFile = true;
                    // Don't break but go on to retrieve the issue.
                    // no break
                case ASSOC_TYPE_ISSUE:
                    if (!$isFile) {
                        $assocObjType = $assocObjId = null;
                        $issueId = $assocId;
                    } else {
                        $issueId = $assocObjId;
                    }

                    $issue = Repo::issue()->get($issueId);

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
    protected function getAssocObjectInfo($submissionId, $contextId)
    {
        $returnArray = parent::getAssocObjectInfo($submissionId, $contextId);

        // Submissions in OJS are associated with an Issue.
        $submission = Repo::submission()->get($submissionId);
        if ($submission->getCurrentPublication()->getData('issueId')) {
            $returnArray = [ASSOC_TYPE_ISSUE, $submission->getCurrentPublication()->getData('issueId')];
        }
        return $returnArray;
    }
}

if (!PKP_STRICT_MODE) {
    class_alias('\APP\statistics\MetricsDAO', '\MetricsDAO');
}
