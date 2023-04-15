<?php

/**
 * @file controllers/grid/issues/IssueGridHandler.php
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2000-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class FutureIssueGridHandler
 *
 * @ingroup controllers_grid_issues
 *
 * @brief Handle issues grid requests.
 */

namespace APP\controllers\grid\issues;

use APP\facades\Repo;
use APP\issue\Collector;
use PKP\linkAction\LinkAction;
use PKP\linkAction\request\AjaxModal;

class FutureIssueGridHandler extends IssueGridHandler
{
    //
    // Implement template methods from PKPHandler
    //
    /**
     * @copydoc IssueGridHandler::initialize()
     *
     * @param null|mixed $args
     */
    public function initialize($request, $args = null)
    {
        // Basic grid configuration.
        $this->setTitle('editor.issues.futureIssues');

        parent::initialize($request, $args);

        // Add Create Issue action
        $router = $request->getRouter();
        $this->addAction(
            new LinkAction(
                'addIssue',
                new AjaxModal(
                    $router->url($request, null, null, 'addIssue', null, ['gridId' => $this->getId()]),
                    __('grid.action.addIssue'),
                    'modal_manage'
                ),
                __('grid.action.addIssue'),
                'add_category'
            )
        );
    }

    /**
     * @copydoc GridHandler::loadData()
     */
    protected function loadData($request, $filter)
    {
        $journal = $request->getJournal();
        return Repo::issue()->getCollector()
            ->filterByContextIds([$journal->getId()])
            ->filterByPublished(false)
            ->orderBy(Collector::ORDERBY_UNPUBLISHED_ISSUES)
            ->getMany();
    }

    /**
     * Get the js handler for this component.
     *
     * @return string
     */
    public function getJSHandler()
    {
        return '$.pkp.controllers.grid.issues.FutureIssueGridHandler';
    }
}
