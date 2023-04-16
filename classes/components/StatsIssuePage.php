<?php
/**
 * @file components/StatsIssuePage.php
 *
 * Copyright (c) 2022 Simon Fraser University
 * Copyright (c) 2022 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class StatsIssuePage
 *
 * @ingroup classes_controllers_stats
 *
 * @brief A class to prepare the data object for the issue statistics
 *   UI component
 */

namespace APP\components;

use PKP\components\PKPStatsComponent;
use PKP\statistics\PKPStatisticsHelper;

class StatsIssuePage extends PKPStatsComponent
{
    /** @var array A timeline of stats (eg - monthly) for a graph */
    public $timeline = [];

    /** @var string Which time segment (eg - month) is displayed in the graph */
    public $timelineInterval = PKPStatisticsHelper::STATISTICS_DIMENSION_MONTH;

    /** @var string Which views to show in the graph. Supports `abstract` or `galley`. */
    public $timelineType = '';

    /** @var array List of items to display stats for */
    public $items = [];

    /** @var int The maximum number of items that stats can be shown for */
    public $itemsMax = 0;

    /** @var int How many items to show per page */
    public $count = 30;

    /** @var string Order items by this property */
    public $orderBy = '';

    /** @var string Order items in this direction: ASC or DESC*/
    public $orderDirection = 'DESC';

    /** @var string A search phrase to filter the list of items */
    public $searchPhrase = null;

    /**
     * Retrieve the configuration data to be used when initializing this
     * handler on the frontend
     *
     * @return array Configuration data
     */
    public function getConfig()
    {
        $config = parent::getConfig();

        $config = array_merge(
            $config,
            [
                'timeline' => $this->timeline,
                'timelineInterval' => $this->timelineInterval,
                'timelineType' => $this->timelineType,
                'items' => $this->items,
                'dateRangeLabel' => __('stats.dateRange'),
                'searchPhraseLabel' => __('common.searchPhrase'),
                'itemsOfTotalLabel' => __('stats.issues.countOfTotal'),
                'betweenDatesLabel' => __('stats.downloadReport.betweenDates'),
                'allDatesLabel' => __('stats.dateRange.allDates'),
                'timelineTypeLabel' => __('stats.timelineType'),
                'timelineIntervalLabel' => __('stats.timelineInterval'),
                'viewsLabel' => __('submission.views'),
                'downloadsLabel' => __('submission.downloads'),
                'dayLabel' => __('common.day'),
                'monthLabel' => __('common.month'),
                'timelineDescriptionLabel' => __('stats.timeline.downloadReport.description'),
                'itemsMax' => $this->itemsMax,
                'count' => $this->count,
                'offset' => 0,
                'searchPhrase' => null,
                'orderBy' => $this->orderBy,
                'orderDirection' => $this->orderDirection,
                'isLoadingTimeline' => false,
            ]
        );

        return $config;
    }
}
