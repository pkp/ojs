<?php

/**
 * @file controllers/grid/pubIds/PubIdExportSubmissionsListGridHandler.php
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2000-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class PubIdExportSubmissionsListGridHandler
 *
 * @ingroup controllers_grid_pubIds
 *
 * @brief Handle exportable submissions with pub ids list grid requests.
 */

namespace APP\controllers\grid\pubIds;

use APP\controllers\grid\submissions\ExportPublishedSubmissionsListGridHandler;
use APP\core\Application;
use APP\facades\Repo;
use PKP\controllers\grid\DataObjectGridCellProvider;
use PKP\controllers\grid\GridColumn;

class PubIdExportSubmissionsListGridHandler extends ExportPublishedSubmissionsListGridHandler
{
    /**
     * @copydoc GridHandler::loadData()
     */
    protected function loadData($request, $filter)
    {
        $context = $request->getContext();
        [$search, $column, $issueId, $statusId] = $this->getFilterValues($filter);
        $title = $author = null;
        if ($column == 'title') {
            $title = $search;
        } elseif ($column == 'author') {
            $author = $search;
        }
        $pubIdStatusSettingName = null;
        if ($statusId) {
            $pubIdStatusSettingName = $this->_plugin->getDepositStatusSettingName();
        }
        return Repo::submission()->dao->getExportable(
            $context->getId(),
            $this->_plugin->getPubIdType(),
            $title,
            $author,
            $issueId,
            $pubIdStatusSettingName,
            $statusId,
            $this->getGridRangeInfo($request, $this->getId())
        );
    }

    /**
     * @copydoc ExportPublishedSubmissionsListGridHandler::getGridCellProvider()
     */
    public function getGridCellProvider()
    {
        // Fetch the authorized roles.
        $authorizedRoles = $this->getAuthorizedContextObject(Application::ASSOC_TYPE_USER_ROLES);
        return new PubIdExportSubmissionsListGridCellProvider($this->_plugin, $authorizedRoles);
    }

    /**
     * Get the grid cell provider instance
     *
     * @return DataObjectGridCellProvider
     */
    public function addAdditionalColumns($cellProvider)
    {
        $this->addColumn(
            new GridColumn(
                'pubId',
                null,
                $this->_plugin->getPubIdDisplayType(),
                null,
                $cellProvider,
                ['alignment' => GridColumn::COLUMN_ALIGNMENT_LEFT,
                    'width' => 15]
            )
        );
    }
}
