<?php

/**
 * @file controllers/grid/users/subscriberSelect/SubscriberSelectGridHandler.php
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2000-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class SubscriberSelectGridHandler
 *
 * @ingroup controllers_grid_users_subscriberSelect
 *
 * @brief Handle subscriber selector grid requests.
 */

namespace APP\controllers\grid\users\subscriberSelect;

use APP\core\Application;
use APP\facades\Repo;
use PKP\controllers\grid\feature\CollapsibleGridFeature;
use PKP\controllers\grid\feature\InfiniteScrollingFeature;
use PKP\controllers\grid\GridColumn;
use PKP\controllers\grid\GridHandler;
use PKP\controllers\grid\users\userSelect\UserSelectGridCellProvider;
use PKP\security\authorization\ContextAccessPolicy;
use PKP\security\Role;

class SubscriberSelectGridHandler extends GridHandler
{
    /** @var array (user group ID => user group name) */
    public $_userGroupOptions;

    /**
     * Constructor
     */
    public function __construct()
    {
        parent::__construct();
        $this->addRoleAssignment(
            [Role::ROLE_ID_MANAGER, Role::ROLE_ID_SITE_ADMIN, Role::ROLE_ID_SUBSCRIPTION_MANAGER],
            ['fetchGrid', 'fetchRows']
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
        $this->addPolicy(new ContextAccessPolicy($request, $roleAssignments));
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

        $stageId = $this->getAuthorizedContextObject(Application::ASSOC_TYPE_WORKFLOW_STAGE);
        $userGroups = Repo::userGroup()->getUserGroupsByStage(
            $request->getContext()->getId(),
            $stageId
        );
        $this->_userGroupOptions = [];
        foreach ($userGroups as $userGroup) {
            $this->_userGroupOptions[$userGroup->getId()] = $userGroup->getLocalizedName();
        }

        $this->setTitle('editor.submission.findAndSelectUser');

        // Columns
        $cellProvider = new UserSelectGridCellProvider($request->getUserVar('userId'));
        $this->addColumn(
            new GridColumn(
                'select',
                '',
                null,
                'controllers/grid/users/userSelect/userSelectRadioButton.tpl',
                $cellProvider,
                ['width' => 5]
            )
        );
        $this->addColumn(
            new GridColumn(
                'name',
                'common.name',
                null,
                null,
                $cellProvider,
                ['alignment' => GridColumn::COLUMN_ALIGNMENT_LEFT,
                    'width' => 30
                ]
            )
        );
    }


    //
    // Overridden methods from GridHandler
    //
    /**
     * @copydoc GridHandler::initFeatures()
     */
    public function initFeatures($request, $args)
    {
        return [new InfiniteScrollingFeature('infiniteScrolling', $this->getItemsNumber()), new CollapsibleGridFeature()];
    }

    /**
     * @copydoc GridHandler::loadData()
     */
    protected function loadData($request, $filter)
    {
        // Get the context.
        $context = $request->getContext();

        // Get all users for this context that match search criteria.
        $rangeInfo = $this->getGridRangeInfo($request, $this->getId());

        // The user interface uses filter['userGroup'] and $filter['search']
        $userGroupSearchTerm = $filter['userGroup'] ? [$filter['userGroup']] : null;

        $userCollector = Repo::user()->getCollector()
            ->filterByContextIds([$context->getId()])
            ->searchPhrase($filter['search'])
            ->filterByUserGroupIds($userGroupSearchTerm)
            ->limit($rangeInfo->getCount())
            ->offset($rangeInfo->getOffset() + max(0, $rangeInfo->getPage() - 1) * $rangeInfo->getCount());

        $users = $userCollector->getMany();

        $totalCount = $userCollector->getCount();
        return new \PKP\core\VirtualArrayIterator(iterator_to_array($users, true), $totalCount, $rangeInfo->getPage(), $rangeInfo->getCount());
    }

    /**
     * @copydoc GridHandler::renderFilter()
     */
    public function renderFilter($request, $filterData = [])
    {
        $context = $request->getContext();
        $userGroups = Repo::userGroup()->getCollector()
            ->filterByContextIds([$context->getId()])
            ->getMany();

        $userGroupOptions = ['' => __('grid.user.allRoles')];
        foreach ($userGroups as $userGroup) {
            $userGroupOptions[$userGroup->getId()] = $userGroup->getLocalizedName();
        }

        return parent::renderFilter(
            $request,
            [
                'userGroupOptions' => $userGroupOptions,
            ]
        );
    }

    /**
     * @copydoc GridHandler::getFilterSelectionData()
     *
     * @return array Filter selection data.
     */
    public function getFilterSelectionData($request)
    {
        // If we're editing an existing subscription, use the filter form to ensure that
        // the already-selected user is chosen.
        if (($userId = (int) $request->getUserVar('userId')) && !$request->getUserVar('clientSubmit')) {
            return [
                'userGroup' => null,
                'searchField' => Repo::user()->dao::USER_FIELD_USERNAME,
                'searchMatch' => 'is',
                'search' => Repo::user()->get($userId)->getUsername(),
            ];
        }

        return [
            'userGroup' => $request->getUserVar('userGroup') ? (int) $request->getUserVar('userGroup') : null,
            'searchField' => $request->getUserVar('searchField'),
            'searchMatch' => $request->getUserVar('searchMatch'),
            'search' => (string) $request->getUserVar('search'),
        ];
    }

    /**
     * @copydoc GridHandler::getFilterForm()
     *
     * @return string Filter template.
     */
    protected function getFilterForm()
    {
        return 'controllers/grid/users/exportableUsers/userGridFilter.tpl';
    }

    /**
     * Determine whether a filter form should be collapsible.
     *
     * @return bool
     */
    protected function isFilterFormCollapsible()
    {
        return false;
    }

    /**
     * Define how many items this grid will start loading.
     *
     * @return int
     */
    protected function getItemsNumber()
    {
        return 5;
    }

    /**
     * @copydoc GridHandler::getRequestArgs()
     */
    public function getRequestArgs()
    {
        $request = Application::get()->getRequest();
        return array_merge(parent::getRequestArgs(), [
            'userId' => $request->getUserVar('userId'),
        ]);
    }
}
