<?php

/**
 * @file controllers/grid/submissions/ExportPublishedSubmissionsListGridHandler.php
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2000-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class ExportPublishedSubmissionsListGridHandler
 *
 * @ingroup controllers_grid_submissions
 *
 * @brief Handle exportable published submissions list grid requests.
 */

namespace APP\controllers\grid\submissions;

use APP\core\Application;
use APP\facades\Repo;
use APP\issue\Collector;
use APP\plugins\PubObjectsExportPlugin;
use PKP\controllers\grid\feature\PagingFeature;
use PKP\controllers\grid\feature\selectableItems\SelectableItemsFeature;
use PKP\controllers\grid\GridColumn;
use PKP\controllers\grid\GridHandler;
use PKP\plugins\ImportExportPlugin;
use PKP\plugins\PluginRegistry;
use PKP\security\authorization\PolicySet;
use PKP\security\authorization\RoleBasedHandlerOperationPolicy;
use PKP\security\Role;

class ExportPublishedSubmissionsListGridHandler extends GridHandler
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
        $context = $request->getContext();

        // Basic grid configuration.
        $this->setTitle('plugins.importexport.common.export.articles');

        $pluginCategory = $request->getUserVar('category');
        $pluginPathName = $request->getUserVar('plugin');

        // Allow for plugins that have already been loaded (e.g. by injection from a generic plugin)
        $this->_plugin = PluginRegistry::getPlugin($pluginCategory, $pluginPathName);

        // Otherwise, load the plugin as specified
        $this->_plugin ??= PluginRegistry::loadPlugin($pluginCategory, $pluginPathName);

        assert(isset($this->_plugin));

        // Grid columns.
        $cellProvider = $this->getGridCellProvider();
        $this->addColumn(
            new GridColumn(
                'id',
                null,
                __('common.id'),
                'controllers/grid/gridCell.tpl',
                $cellProvider,
                ['alignment' => GridColumn::COLUMN_ALIGNMENT_LEFT,
                    'width' => 10]
            )
        );
        $this->addColumn(
            new GridColumn(
                'title',
                'grid.submission.itemTitle',
                null,
                null,
                $cellProvider,
                ['html' => true,
                    'alignment' => GridColumn::COLUMN_ALIGNMENT_LEFT]
            )
        );
        $this->addColumn(
            new GridColumn(
                'issue',
                'issue.issue',
                null,
                null,
                $cellProvider,
                ['alignment' => GridColumn::COLUMN_ALIGNMENT_LEFT,
                    'width' => 20]
            )
        );
        if (method_exists($this, 'addAdditionalColumns')) {
            $this->addAdditionalColumns($cellProvider);
        }
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
        return 'selectedSubmissions';
    }

    /**
     * @copydoc GridHandler::getFilterForm()
     */
    protected function getFilterForm()
    {
        return 'controllers/grid/submissions/exportPublishedSubmissionsGridFilter.tpl';
    }

    /**
     * @copydoc GridHandler::renderFilter()
     */
    public function renderFilter($request, $filterData = [])
    {
        $context = $request->getContext();
        $issues = Repo::issue()->getCollector()
            ->filterByContextIds([$context->getId()])
            ->filterByPublished(true)
            ->orderBy(Collector::ORDERBY_PUBLISHED_ISSUES)
            ->getMany();
        foreach ($issues as $issue) {
            $issueOptions[$issue->getId()] = $issue->getIssueIdentification();
        }
        $issueOptions[0] = __('plugins.importexport.common.filter.issue');
        ksort($issueOptions);
        $statusNames = $this->_plugin->getStatusNames();
        $filterColumns = $this->getFilterColumns();
        $allFilterData = array_merge(
            $filterData,
            [
                'columns' => $filterColumns,
                'issues' => $issueOptions,
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
        $search = (string) $request->getUserVar('search');
        $column = (string) $request->getUserVar('column');
        $issueId = (int) $request->getUserVar('issueId');
        $statusId = (string) $request->getUserVar('statusId');
        return [
            'search' => $search,
            'column' => $column,
            'issueId' => $issueId,
            'statusId' => $statusId,
        ];
    }

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
            null,
            $title,
            $author,
            $issueId,
            $pubIdStatusSettingName,
            $statusId,
            $this->getGridRangeInfo($request, $this->getId())
        );
    }


    //
    // Own protected methods
    //
    /**
     * Get which columns can be used by users to filter data.
     *
     * @return array
     */
    protected function getFilterColumns()
    {
        return [
            'title' => __('submission.title'),
            'author' => __('submission.authors')
        ];
    }

    /**
     * Process filter values, assigning default ones if
     * none was set.
     *
     * @return array
     */
    protected function getFilterValues($filter)
    {
        if (isset($filter['search']) && $filter['search']) {
            $search = $filter['search'];
        } else {
            $search = null;
        }
        if (isset($filter['column']) && $filter['column']) {
            $column = $filter['column'];
        } else {
            $column = null;
        }
        if (isset($filter['issueId']) && $filter['issueId']) {
            $issueId = $filter['issueId'];
        } else {
            $issueId = null;
        }
        if (isset($filter['statusId']) && $filter['statusId'] != PubObjectsExportPlugin::EXPORT_STATUS_ANY) {
            $statusId = $filter['statusId'];
        } else {
            $statusId = null;
        }
        return [$search, $column, $issueId, $statusId];
    }

    /**
     * Get the grid cell provider instance
     *
     * @return ExportPublishedSubmissionsListGridCellProvider
     */
    public function getGridCellProvider()
    {
        // Fetch the authorized roles.
        $authorizedRoles = $this->getAuthorizedContextObject(Application::ASSOC_TYPE_USER_ROLES);
        return new ExportPublishedSubmissionsListGridCellProvider($this->_plugin, $authorizedRoles);
    }
}
