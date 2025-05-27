<?php

/**
 * @file classes/search/ArticleSearchDAO.php
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2003-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class ArticleSearchDAO
 *
 * @ingroup search
 *
 * @see ArticleSearch
 *
 * @brief DAO class for article search index.
 */

namespace APP\search;

use APP\journal\Journal;
use PKP\context\Context;
use PKP\search\SubmissionSearchDAO;
use PKP\submission\PKPSubmission;

class ArticleSearchDAO extends SubmissionSearchDAO
{
    /**
     * Retrieve the top results for a phrase.
     *
     * @param int|null $type Application::ASSOC_TYPE_...
     *
     * @return array of results (associative arrays)
     */
    public function getPhraseResults(
        ?Context $context,
        array $phrase,
        ?string $publishedFrom = null,
        ?string $publishedTo = null,
        ?array $categoryIds = null,
        ?array $sectionIds = null,
        ?int $type = null,
        int $limit = 500
    ): array {
        $sqlFrom = '';
        $sqlWhere = '';
        $params = [];

        for ($i = 0, $count = count($phrase); $i < $count; $i++) {
            if (empty($phrase[$i])) {
                continue;
            }

            if (!empty($sqlFrom)) {
                $sqlFrom .= ', ';
            }
            $sqlFrom .= 'submission_search_object_keywords o' . $i . ' NATURAL JOIN submission_search_keyword_list k' . $i;
            if (strstr($phrase[$i], '%') === false) {
                $sqlWhere .= ' AND k' . $i . '.keyword_text = ?';
            } else {
                $sqlWhere .= ' AND k' . $i . '.keyword_text LIKE ?';
            }
            if ($i > 0) {
                $sqlWhere .= ' AND o0.object_id = o' . $i . '.object_id AND o0.pos+' . $i . ' = o' . $i . '.pos';
            }

            $params[] = $phrase[$i];
        }

        if (is_array($categoryIds)) {
            $sqlWhere .= ' AND p.publication_id IN (SELECT publication_id FROM publication_categories WHERE category_id IN (' . implode(',', array_map(intval(...), $categoryIds)) . '))';
        }

        if (is_array($sectionIds)) {
            $sqlWhere .= ' AND p.section_id IN (' . implode(',', array_map(intval(...), $sectionIds)) . ')';
        }

        if (!empty($type)) {
            $sqlWhere .= ' AND (o.type & ?) != 0';
            $params[] = $type;
        }

        if (!empty($publishedFrom)) {
            $sqlWhere .= ' AND p.date_published >= ' . $this->datetimeToDB($publishedFrom);
        }

        if (!empty($publishedTo)) {
            $sqlWhere .= ' AND p.date_published <= ' . $this->datetimeToDB($publishedTo);
        }

        if (!empty($context)) {
            $sqlWhere .= ' AND (i.journal_id = ? OR i.journal_id is NULL)';
            $params[] = $context->getId();
        }
        
        $result = $this->retrieve(
            'SELECT
                o.submission_id,
                MAX(s.context_id) AS journal_id,
                MAX(i.date_published) AS i_pub,
                MAX(p.date_published) AS s_pub,
                COUNT(*) AS count
            FROM
                submissions s
                JOIN publications AS p ON p.publication_id = s.current_publication_id
                LEFT JOIN issues AS i ON i.issue_id = p.issue_id
                JOIN submission_search_objects AS o ON s.submission_id = o.submission_id
                JOIN journals AS j ON j.journal_id = s.context_id
                LEFT JOIN journal_settings AS js ON j.journal_id = js.journal_id
                    AND js.setting_name = \'publishingMode\'
                LEFT JOIN publication_settings AS ps ON ps.publication_id = p.publication_id
                    AND ps.setting_name = \'continuousPublication\'
                    AND ps.setting_value = 1
                NATURAL JOIN ' . $sqlFrom . '
            WHERE
                (js.setting_value <> \'' . Journal::PUBLISHING_MODE_NONE . '\' 
                OR js.setting_value IS NULL) 
                AND j.enabled = 1 
                AND s.status = ' . PKPSubmission::STATUS_PUBLISHED . '
                AND ( i.published = 1
                    OR ps.publication_id IS NOT NULL
                    OR p.issue_id IS NULL )
                AND ' . $sqlWhere . '
            GROUP BY o.submission_id
            ORDER BY count DESC
            LIMIT ' . $limit,
            $params
        );

        $returner = [];
        foreach ($result as $row) {
            $returner[$row->submission_id] = [
                'count' => $row->count,
                'journal_id' => $row->journal_id,
                'issuePublicationDate' => $this->datetimeFromDB($row->i_pub),
                'publicationDate' => $this->datetimeFromDB($row->s_pub)
            ];
        }
        return $returner;
    }
}
