<?php

/**
 * @file controllers/grid/issues/IssueGridRow.inc.php
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2003-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class IssueGridRow
 * @ingroup controllers_grid_issues
 *
 * @brief Handle issue grid row requests.
 */

use APP\facades\Repo;
use PKP\linkAction\LinkAction;
use PKP\linkAction\request\AjaxModal;
use PKP\linkAction\request\OpenWindowAction;
use PKP\linkAction\request\RemoteActionConfirmationModal;
use PKP\controllers\grid\GridRow;

class IssueGridRow extends GridRow
{
    //
    // Overridden template methods
    //
    /**
     * @copydoc GridRow::initialize
     */
    public function initialize($request, $template = null)
    {
        parent::initialize($request, $template);

        // Is this a new row or an existing row?
        $issueId = $this->getId();
        if (!empty($issueId) && is_numeric($issueId)) {
            $issue = $this->getData();
            assert(is_a($issue, 'Issue'));
            $router = $request->getRouter();

            $this->addAction(
                new LinkAction(
                    'edit',
                    new AjaxModal(
                        $router->url($request, null, null, 'editIssue', null, ['issueId' => $issueId]),
                        __('editor.issues.editIssue', ['issueIdentification' => $issue->getIssueIdentification()]),
                        'modal_edit',
                        true
                    ),
                    __('grid.action.edit'),
                    'edit'
                )
            );

            $dispatcher = $request->getDispatcher();
            $this->addAction(
                new LinkAction(
                    $issue->getDatePublished() ? 'viewIssue' : 'previewIssue',
                    new OpenWindowAction(
                        $dispatcher->url($request, PKPApplication::ROUTE_PAGE, null, 'issue', 'view', [$issueId])
                    ),
                    __($issue->getDatePublished() ? 'grid.action.viewIssue' : 'grid.action.previewIssue'),
                    'information'
                )
            );

            if ($issue->getDatePublished()) {
                $this->addAction(
                    new LinkAction(
                        'unpublish',
                        new RemoteActionConfirmationModal(
                            $request->getSession(),
                            __('editor.issues.confirmUnpublish'),
                            __('editor.issues.unpublishIssue'),
                            $router->url($request, null, null, 'unpublishIssue', null, ['issueId' => $issueId]),
                            'modal_delete'
                        ),
                        __('editor.issues.unpublishIssue'),
                        'delete'
                    )
                );
            } else {
                $this->addAction(
                    new LinkAction(
                        'publish',
                        new AjaxModal(
                            $router->url(
                                $request,
                                null,
                                null,
                                'publishIssue',
                                null,
                                ['issueId' => $issueId]
                            ),
                            __('editor.issues.publishIssue'),
                            'modal_confirm'
                        ),
                        __('editor.issues.publishIssue'),
                        'advance'
                    )
                );
            }

            $currentIssue = Repo::issue()->getCurrent($issue->getJournalId());
            $isCurrentIssue = $currentIssue != null && $issue->getId() == $currentIssue->getId();
            if ($issue->getDatePublished() && !$isCurrentIssue) {
                $this->addAction(
                    new LinkAction(
                        'setCurrentIssue',
                        new RemoteActionConfirmationModal(
                            $request->getSession(),
                            __('editor.issues.confirmSetCurrentIssue'),
                            __('editor.issues.currentIssue'),
                            $router->url($request, null, null, 'setCurrentIssue', null, ['issueId' => $issueId]),
                            'modal_delete'
                        ),
                        __('editor.issues.currentIssue'),
                        'delete'
                    )
                );
            }

            $this->addAction(
                new LinkAction(
                    'delete',
                    new RemoteActionConfirmationModal(
                        $request->getSession(),
                        __('common.confirmDelete'),
                        __('grid.action.delete'),
                        $router->url($request, null, null, 'deleteIssue', null, ['issueId' => $issueId]),
                        'modal_delete'
                    ),
                    __('grid.action.delete'),
                    'delete'
                )
            );
        }
    }
}
