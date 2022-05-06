<?php

/**
 * @file controllers/grid/pubIds/PubIdExportRepresentationsListGridHandler.inc.php
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2000-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class PubIdExportRepresentationsListGridHandler
 * @ingroup controllers_grid_pubIds
 *
 * @brief Handle exportable representations with pub ids list grid requests.
 */

use APP\facades\Repo;
use APP\issue\Collector;
use PKP\controllers\grid\feature\PagingFeature;
use PKP\controllers\grid\feature\selectableItems\SelectableItemsFeature;
use PKP\controllers\grid\GridColumn;
use PKP\controllers\grid\GridHandler;
use PKP\security\authorization\PolicySet;
use PKP\security\authorization\RoleBasedHandlerOperationPolicy;
use PKP\security\Role;

import('controllers.grid.pubIds.PubIdExportRepresentationsListGridCellProvider');

class PubIdExportRepresentationsListGridHandler extends GridHandler
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
        $this->_plugin = PluginRegistry::loadPlugin($pluginCategory, $pluginPathName);
        assert(isset($this->_plugin));

        // Fetch the authorized roles.
        $authorizedRoles = $this->getAuthorizedContextObject(ASSOC_TYPE_USER_ROLES);

        // Grid columns.
        $cellProvider = new PubIdExportRepresentationsListGridCellProvider($this->_plugin, $authorizedRoles);
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
        $this->addColumn(
            new GridColumn(
                'galley',
                'submission.layout.galleyLabel',
                null,
                null,
                $cellProvider,
                ['alignment' => GridColumn::COLUMN_ALIGNMENT_LEFT,
                    'width' => 20]
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
        return 'selectedRepresentations';
    }

    /**
     * @copydoc GridHandler::getFilterForm()
     */
    protected function getFilterForm()
    {
        return 'controllers/grid/pubIds/pubIdExportRepresentationsGridFilter.tpl';
    }

    /**
     * @copydoc GridHandler::renderFilter()
     */
    public function renderFilter($request, $filterData = [])
    {
        $context = $request->getContext();
        $publishedIssuesCollector = Repo::issue()->getCollector()
            ->filterByContextIds([$context->getId()])
            ->filterByPublished(true)
            ->orderBy(Collector::ORDERBY_PUBLISHED_ISSUES);
        $issues = Repo::issue()->getMany($publishedIssuesCollector);
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
        return Repo::galley()->dao->getExportable(
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
        if (isset($filter['statusId']) && $filter['statusId'] != EXPORT_STATUS_ANY) {
            $statusId = $filter['statusId'];
        } else {
            $statusId = null;
        }
        return [$search, $column, $issueId, $statusId];
    }
}
