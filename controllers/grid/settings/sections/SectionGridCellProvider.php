<?php
/**
 * @file controllers/grid/settings/sections/SectionGridCellProvider.php
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2003-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class SectionGridCellProvider
 *
 * @ingroup controllers_grid_settings_sections
 *
* @brief Grid cell provider for section grid
 */

namespace APP\controllers\grid\settings\sections;

use PKP\controllers\grid\GridCellProvider;
use PKP\controllers\grid\GridColumn;
use PKP\controllers\grid\GridHandler;
use PKP\linkAction\LinkAction;
use PKP\linkAction\request\RemoteActionConfirmationModal;

class SectionGridCellProvider extends GridCellProvider
{
    /**
     * Extracts variables for a given column from a data element
     * so that they may be assigned to template before rendering.
     *
     * @param \PKP\controllers\grid\GridRow $row
     * @param GridColumn $column
     *
     * @return array
     */
    public function getTemplateVarsFromRowColumn($row, $column)
    {
        $element = $row->getData();
        $columnId = $column->getId();
        switch ($columnId) {
            case 'inactive':
                return ['selected' => $element['inactive']];
        }
        return parent::getTemplateVarsFromRowColumn($row, $column);
    }

    /**
     * @see GridCellProvider::getCellActions()
     */
    public function getCellActions($request, $row, $column, $position = GridHandler::GRID_ACTION_POSITION_DEFAULT)
    {
        switch ($column->getId()) {
            case 'inactive':
                $element = $row->getData(); /** @var array $element */

                $router = $request->getRouter();

                if ($element['inactive']) {
                    return [new LinkAction(
                        'activateSection',
                        new RemoteActionConfirmationModal(
                            $request->getSession(),
                            __('manager.sections.confirmActivateSection'),
                            null,
                            $router->url(
                                $request,
                                null,
                                'grid.settings.sections.SectionGridHandler',
                                'activateSection',
                                null,
                                ['sectionKey' => $row->getId()]
                            )
                        )
                    )];
                } else {
                    return [new LinkAction(
                        'deactivateSection',
                        new RemoteActionConfirmationModal(
                            $request->getSession(),
                            __('manager.sections.confirmDeactivateSection'),
                            null,
                            $router->url(
                                $request,
                                null,
                                'grid.settings.sections.SectionGridHandler',
                                'deactivateSection',
                                null,
                                ['sectionKey' => $row->getId()]
                            )
                        )
                    )];
                }
        }
        return parent::getCellActions($request, $row, $column, $position);
    }
}
