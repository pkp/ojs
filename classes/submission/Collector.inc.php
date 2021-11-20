<?php
/**
 * @file classes/submission/Collector.inc.php
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2000-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class submission
 *
 * @brief A helper class to configure a Query Builder to get a collection of submissions
 */

namespace APP\submission;

use Illuminate\Database\Query\Builder;

class Collector extends \PKP\submission\Collector
{
    /** @var array */
    public $issueIds = null;

    /** @var array */
    public $sectionIds = null;

    public function __construct(DAO $dao)
    {
        $this->dao = $dao;
    }

    /**
     * Limit results to submissions assigned to these issues
     */
    public function filterByIssueIds(array $issueIds): self
    {
        $this->issueIds = $issueIds;
        return $this;
    }

    /**
     * Limit results to submissions assigned to these sections
     */
    public function filterBySectionIds(array $sectionIds): self
    {
        $this->sectionIds = $sectionIds;
        return $this;
    }

    /**
     * @copydoc CollectorInterface::getQueryBuilder()
     */
    public function getQueryBuilder(): Builder
    {
        $q = parent::getQueryBuilder();

        if (is_array($this->issueIds)) {
            $q->whereIn('s.submission_id', function ($query) {
                $query->select('issue_p.submission_id')
                    ->from('publications AS issue_p')
                    ->join('publication_settings as issue_ps', 'issue_p.publication_id', '=', 'issue_ps.publication_id')
                    ->where('issue_ps.setting_name', '=', 'issueId')
                    ->whereIn('issue_ps.setting_value', $this->issueIds);
            });
        }

        if (is_array($this->sectionIds)) {
            $q->whereIn('s.submission_id', function ($query) {
                $query->select('section_p.submission_id')
                    ->from('publications AS section_p')
                    ->whereIn('section_p.section_id', $this->sectionIds);
            });
        }

        return $q;
    }
}
