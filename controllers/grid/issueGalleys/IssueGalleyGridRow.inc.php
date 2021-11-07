<?php

/**
 * @file controllers/grid/issueGalleys/IssueGalleyGridRow.inc.php
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2003-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class IssueGalleyGridRow
 * @ingroup issue_galley
 *
 * @brief Handle issue galley grid row requests.
 */

use PKP\controllers\grid\GridRow;
use PKP\linkAction\LinkAction;
use PKP\linkAction\request\AjaxModal;
use PKP\linkAction\request\RemoteActionConfirmationModal;

class IssueGalleyGridRow extends GridRow
{
    /**
     * Constructor
     */
    public function __construct($issueId)
    {
        parent::__construct();
        $this->setRequestArgs(
            array_merge(
                ((array) $this->getRequestArgs()),
                ['issueId' => $issueId]
            )
        );
    }

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
        $issueGalleyId = $this->getId();
        if (!empty($issueGalleyId) && is_numeric($issueGalleyId)) {
            $issue = $this->getData();
            assert(is_a($issue, 'IssueGalley'));
            $router = $request->getRouter();

            $this->addAction(
                new LinkAction(
                    'edit',
                    new AjaxModal(
                        $router->url(
                            $request,
                            null,
                            null,
                            'edit',
                            null,
                            array_merge($this->getRequestArgs(), ['issueGalleyId' => $issueGalleyId])
                        ),
                        __('editor.issues.editIssueGalley'),
                        'modal_edit',
                        true
                    ),
                    __('grid.action.edit'),
                    'edit'
                )
            );

            $this->addAction(
                new LinkAction(
                    'delete',
                    new RemoteActionConfirmationModal(
                        $request->getSession(),
                        __('common.confirmDelete'),
                        __('grid.action.delete'),
                        $router->url(
                            $request,
                            null,
                            null,
                            'delete',
                            null,
                            array_merge($this->getRequestArgs(), ['issueGalleyId' => $issueGalleyId])
                        ),
                        'modal_delete'
                    ),
                    __('grid.action.delete'),
                    'delete'
                )
            );
        }
    }
}
