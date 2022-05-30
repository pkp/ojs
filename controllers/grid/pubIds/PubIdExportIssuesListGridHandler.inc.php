<?php

/**
 * @file controllers/grid/pubIds/PubIdExportIssuesListGridHandler.inc.php
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2000-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class PubIdExportIssuesListGridHandler
 * @ingroup controllers_grid_pubIds
 *
 * @brief Handle exportable issues with pub ids list grid requests.
 */

use PKP\controllers\grid\feature\PagingFeature;
use PKP\controllers\grid\feature\selectableItems\SelectableItemsFeature;
use PKP\controllers\grid\GridColumn;
use PKP\controllers\grid\GridHandler;
use PKP\security\authorization\PolicySet;
use PKP\security\authorization\RoleBasedHandlerOperationPolicy;
use PKP\security\Role;

import('controllers.grid.pubIds.PubIdExportIssuesListGridCellProvider');

class PubIdExportIssuesListGridHandler extends GridHandler
{
    /** @var ImportExportPlugin */
    public $_plugin;

    /**
     * Constructor
     */
    public function __construct()
    {
        parent::__construct();
        $this->addRoleAssignment(
            [Role::ROLE_ID_MANAGER, Role::ROLE_ID_SITE_ADMIN],
            ['fetchGrid', 'fetchRow']
        );
    }

    //
    // Implement template methods from PKPHandler
    //
    /**
     * @copydoc PKPHandler::authorize()
     */
    public function authorize($request, &$args, $roleAssignments)
    {
        $rolePolicy = new PolicySet(PolicySet::COMBINING_PERMIT_OVERRIDES);

        foreach ($roleAssignments as $role => $operations) {
            $rolePolicy->addPolicy(new RoleBasedHandlerOperationPolicy($request, $role, $operations));
        }
        $this->addPolicy($rolePolicy);

        return parent::authorize($request, $args, $roleAssignments);
    }

    /**
     * @copydoc GridHandler::initialize()
     *
     * @param null|mixed $args
     */
    public function initialize($request, $args = null)
    {
        parent::initialize($request, $args);

        // Basic grid configuration.
        $this->setTitle('plugins.importexport.common.export.issues');

        $pluginCategory = $request->getUserVar('category');
        $pluginPathName = $request->getUserVar('plugin');
        $this->_plugin = PluginRegistry::loadPlugin($pluginCategory, $pluginPathName);
        assert(isset($this->_plugin));

        // Fetch the authorized roles.
        $authorizedRoles = $this->getAuthorizedContextObject(ASSOC_TYPE_USER_ROLES);

        // Grid columns.
        $cellProvider = new PubIdExportIssuesListGridCellProvider($this->_plugin, $authorizedRoles);
        $this->addColumn(
            new GridColumn(
                'identification',
                'issue.issue',
                null,
                null,
                $cellProvider,
                ['html' => true,
                    'alignment' => GridColumn::COLUMN_ALIGNMENT_LEFT]
            )
        );
        $this->addColumn(
            new GridColumn(
                'published',
                'editor.issues.published',
                null,
                null,
                $cellProvider,
                ['html' => true,
                    'alignment' => GridColumn::COLUMN_ALIGNMENT_LEFT]
            )
        );
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
        $this->addColumn(
            new GridColumn(
                'status',
                'common.status',
                null,
                null,
                $cellProvider,
                ['alignment' => GridColumn::COLUMN_ALIGNMENT_LEFT,
                    'width' => 10]
            )
        );
    }


    //
    // Implemented methods from GridHandler.
    //
    /**
     * @copydoc GridHandler::initFeatures()
     */
    public function initFeatures($request, $args)
    {
        return [new SelectableItemsFeature(), new PagingFeature()];
    }

    /**
     * @copydoc GridHandler::getRequestArgs()
     */
    public function getRequestArgs()
    {
        return array_merge(parent::getRequestArgs(), ['category' => $this->_plugin->getCategory(), 'plugin' => basename($this->_plugin->getPluginPath())]);
    }

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
     * @copydoc GridHandler::getFilterForm()
     */
    protected function getFilterForm()
    {
        return 'controllers/grid/pubIds/pubIdExportIssuesGridFilter.tpl';
    }

    /**
     * @copydoc GridHandler::renderFilter()
     */
    public function renderFilter($request, $filterData = [])
    {
        $statusNames = $this->_plugin->getStatusNames();
        $allFilterData = array_merge(
            $filterData,
            [
                'status' => $statusNames,
                'gridId' => $this->getId(),
            ]
        );
        return parent::renderFilter($request, $allFilterData);
    }

    /**
     * @copydoc GridHandler::getFilterSelectionData()
     */
    public function getFilterSelectionData($request)
    {
        $statusId = (string) $request->getUserVar('statusId');
        return [
            'statusId' => $statusId,
        ];
    }

    /**
     * @copydoc GridHandler::loadData()
     */
    protected function loadData($request, $filter)
    {
        $context = $request->getContext();
        [$statusId] = $this->getFilterValues($filter);
        $pubIdStatusSettingName = null;
        if ($statusId) {
            $pubIdStatusSettingName = $this->_plugin->getDepositStatusSettingName();
        }
        return \APP\facades\Repo::issue()->dao->getExportable(
            $context->getId(),
            $this->_plugin->getPubIdType(),
            $pubIdStatusSettingName,
            $statusId,
            $this->getGridRangeInfo($request, $this->getId())
        );
    }

    /**
     * Process filter values, assigning default ones if
     * none was set.
     *
     * @return array
     */
    protected function getFilterValues($filter)
    {
        if (isset($filter['statusId']) && $filter['statusId'] != EXPORT_STATUS_ANY) {
            $statusId = $filter['statusId'];
        } else {
            $statusId = null;
        }
        return [$statusId];
    }
}
