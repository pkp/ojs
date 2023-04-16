<?php

/**
 * @file controllers/grid/issues/IssueGridHandler.php
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2000-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class ExportableIssuesListGridHandler
 *
 * @ingroup controllers_grid_issues
 *
 * @brief Handle exportable issues grid requests.
 */

namespace APP\controllers\grid\issues;

use APP\facades\Repo;
use PKP\controllers\grid\feature\PagingFeature;
use PKP\controllers\grid\feature\selectableItems\SelectableItemsFeature;
use PKP\controllers\grid\GridRow;

class ExportableIssuesListGridHandler extends IssueGridHandler
{
    //
    // Implemented methods from GridHandler.
    //
    /**
     * @copydoc GridHandler::isDataElementSelected()
     */
    public function isDataElementSelected($gridDataElement)
    {
        return false; // Nothing is selected by default
    }

    /**
     * @copydoc GridHandler::getSelectName()
     */
    public function getSelectName()
    {
        return 'selectedIssues';
    }

    /**
     * @copydoc GridHandler::loadData()
     */
    protected function loadData($request, $filter)
    {
        $journal = $request->getJournal();

        // Handle grid paging (deprecated style)

        $rangeInfo = $this->getGridRangeInfo($request, $this->getId());
        $collector = Repo::issue()->getCollector()
            ->filterByContextIds([$journal->getId()]);

        $totalCount = $collector->getCount();
        $collector->limit($rangeInfo->getCount());
        $collector->offset($rangeInfo->getOffset() + max(0, $rangeInfo->getPage() - 1) * $rangeInfo->getCount());

        return new \PKP\core\VirtualArrayIterator($collector->getMany()->toArray(), $totalCount, $rangeInfo->getPage(), $rangeInfo->getCount());
    }

    /**
     * @copydoc GridHandler::initFeatures()
     */
    public function initFeatures($request, $args)
    {
        return [new SelectableItemsFeature(), new PagingFeature()];
    }

    /**
     * Get the row handler - override the parent row handler. We do not need grid row actions.
     *
     * @return GridRow
     */
    protected function getRowInstance()
    {
        return new GridRow();
    }
}
