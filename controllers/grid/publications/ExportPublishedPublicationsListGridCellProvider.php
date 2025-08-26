<?php

/**
 * @file controllers/grid/publications/ExportPublishedPublicationsListGridCellProvider.php
 *
 * Copyright (c) 2025 Simon Fraser University
 * Copyright (c) 2025 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class ExportPublishedPublicationsListGridCellProvider
 *
 * @ingroup controllers_grid_publications
 *
 * @brief Class for a cell provider that can retrieve labels from publications
 */

namespace APP\controllers\grid\publications;

use APP\facades\Repo;
use APP\plugins\PubObjectsExportPlugin;
use PKP\controllers\grid\DataObjectGridCellProvider;
use PKP\controllers\grid\GridColumn;
use PKP\controllers\grid\GridHandler;
use PKP\linkAction\LinkAction;
use PKP\linkAction\request\RedirectAction;

class ExportPublishedPublicationsListGridCellProvider extends DataObjectGridCellProvider
{
    public PubObjectsExportPlugin $_plugin;

    public $_authorizedRoles;

    public GridColumn $_titleColumn;

    /**
     * Constructor
     *
     * @param null|mixed $authorizedRoles
     */
    public function __construct($plugin, $authorizedRoles = null)
    {
        $this->_plugin = $plugin;
        if ($authorizedRoles) {
            $this->_authorizedRoles = $authorizedRoles;
        }
        parent::__construct();
    }

    //
    // Template methods from GridCellProvider
    //
    /**
     * Get cell actions associated with this row/column combination
     *
     * @copydoc GridCellProvider::getCellActions()
     */
    public function getCellActions($request, $row, $column, $position = GridHandler::GRID_ACTION_POSITION_DEFAULT)
    {
        $publication = $row->getData();
        $submission = Repo::submission()->get($publication->getData('submissionId'));
        $columnId = $column->getId();
        switch ($columnId) {
            case 'title':
                $this->_titleColumn = $column;
                $title = $publication->getLocalizedTitle(null, 'html');
                if (empty($title)) {
                    $title = __('common.untitled');
                }
                $authorsInTitle = $publication->getShortAuthorString();
                $title = $authorsInTitle . '; ' . $title;
                return [
                    new LinkAction(
                        'itemWorkflow',
                        new RedirectAction(
                            Repo::submission()->getWorkflowUrlByUserRoles($submission)
                        ),
                        $title
                    )
                ];
            case 'status':
                $status = $publication->getData($this->_plugin->getDepositStatusSettingName());
                $statusNames = $this->_plugin->getStatusNames();
                $statusActions = $this->_plugin->getStatusActions($publication);
                if ($status && array_key_exists($status, $statusActions)) {
                    assert(array_key_exists($status, $statusNames));
                    return [$statusActions[$status]];
                }
                break;
        }
        return parent::getCellActions($request, $row, $column, $position);
    }

    /**
     * Extracts variables for a given column from a data element
     * so that they may be assigned to template before rendering.
     *
     * @copydoc DataObjectGridCellProvider::getTemplateVarsFromRowColumn()
     */
    public function getTemplateVarsFromRowColumn($row, $column)
    {
        $publication = $row->getData();
        $columnId = $column->getId();

        switch ($columnId) {
            case 'submissionId':
                return ['label' => $publication->getData('submissionId')];
            case 'version':
                return ['label' => $publication->getData('versionMajor') . '.' . $publication->getData('versionMinor')];
            case 'title':
                return ['label' => ''];
            case 'status':
                $status = $publication->getData($this->_plugin->getDepositStatusSettingName());
                $statusNames = $this->_plugin->getStatusNames();
                $statusActions = $this->_plugin->getStatusActions($publication);
                if ($status) {
                    if (array_key_exists($status, $statusActions)) {
                        $label = '';
                    } else {
                        assert(array_key_exists($status, $statusNames));
                        $label = $statusNames[$status];
                    }
                } else {
                    $label = $statusNames[PubObjectsExportPlugin::EXPORT_STATUS_NOT_DEPOSITED];
                }
                return ['label' => $label];
        }
    }
}
