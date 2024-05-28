<?php

/**
 * @file controllers/grid/pubIds/PubIdExportIssuesListGridCellProvider.php
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2000-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class PubIdExportIssuesListGridCellProvider
 *
 * @ingroup controllers_grid_pubIds
 *
 * @brief Class for a cell provider that can retrieve labels from issues with pub ids
 */

namespace APP\controllers\grid\pubIds;

use APP\core\Application;
use APP\plugins\PubObjectsExportPlugin;
use PKP\controllers\grid\DataObjectGridCellProvider;
use PKP\controllers\grid\GridHandler;
use PKP\core\PKPApplication;
use PKP\linkAction\LinkAction;
use PKP\linkAction\request\AjaxModal;
use PKP\linkAction\request\RedirectAction;
use PKP\plugins\ImportExportPlugin;

class PubIdExportIssuesListGridCellProvider extends DataObjectGridCellProvider
{
    /** @var ImportExportPlugin */
    public $_plugin;

    public $_authorizedRoles;

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
        $publishedIssue = $row->getData();
        $columnId = $column->getId();
        assert(is_a($publishedIssue, 'Issue') && !empty($columnId));

        switch ($columnId) {
            case 'identification':
                // Link to the issue edit modal
                $application = Application::get();
                $dispatcher = $application->getDispatcher();
                return [
                    new LinkAction(
                        'edit',
                        new AjaxModal(
                            $dispatcher->url($request, PKPApplication::ROUTE_COMPONENT, null, 'grid.issues.BackIssueGridHandler', 'editIssue', null, ['issueId' => $publishedIssue->getId()]),
                            __('plugins.importexport.common.settings.DOIPluginSettings')
                        ),
                        $publishedIssue->getIssueIdentification(),
                        null
                    )
                ];
            case 'status':
                $status = $publishedIssue->getData($this->_plugin->getDepositStatusSettingName());
                $statusNames = $this->_plugin->getStatusNames();
                $statusActions = $this->_plugin->getStatusActions($publishedIssue);
                if ($status && array_key_exists($status, $statusActions)) {
                    assert(array_key_exists($status, $statusNames));
                    return [
                        new LinkAction(
                            'edit',
                            new RedirectAction(
                                $statusActions[$status],
                                '_blank'
                            ),
                            $statusNames[$status]
                        )
                    ];
                }
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
        $publishedIssue = $row->getData();
        $columnId = $column->getId();
        assert(is_a($publishedIssue, 'Issue') && !empty($columnId));

        switch ($columnId) {
            case 'identification':
                return ['label' => ''];
            case 'published':
                return ['label' => $publishedIssue->getDatePublished()];
            case 'pubId':
                return ['label' => $publishedIssue->getStoredPubId($this->_plugin->getPubIdType())];
            case 'status':
                $status = $publishedIssue->getData($this->_plugin->getDepositStatusSettingName());
                $statusNames = $this->_plugin->getStatusNames();
                $statusActions = $this->_plugin->getStatusActions($publishedIssue);
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
