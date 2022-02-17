<?php

/**
 * @file controllers/grid/toc/TocGridRow.inc.php
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2003-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class TocGridRow
 * @ingroup controllers_grid_settings_issue
 *
 * @brief Handle issue grid row requests.
 */

use PKP\controllers\grid\GridRow;
use PKP\linkAction\LinkAction;
use PKP\linkAction\request\RedirectAction;
use PKP\linkAction\request\RemoteActionConfirmationModal;

class TocGridRow extends GridRow
{
    /** @var int */
    public $issueId;

    /**
     * Constructor
     *
     * @param int $issueId
     */
    public function __construct($issueId)
    {
        parent::__construct();
        $this->issueId = $issueId;
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

        $dispatcher = $request->getDispatcher();
        $this->addAction(
            new LinkAction(
                'workflow',
                new RedirectAction(
                    $dispatcher->url($request, PKPApplication::ROUTE_PAGE, null, 'workflow', 'access', [$this->getId()])
                ),
                __('submission.submission'),
                'information'
            )
        );

        $router = $request->getRouter();
        $this->addAction(
            new LinkAction(
                'removeArticle',
                new RemoteActionConfirmationModal(
                    $request->getSession(),
                    __('editor.article.remove.confirm'),
                    __('grid.action.removeArticle'),
                    $router->url($request, null, null, 'removeArticle', null, ['articleId' => $this->getId(), 'issueId' => $this->issueId]),
                    'modal_delete'
                ),
                __('editor.article.remove'),
                'delete'
            )
        );
    }
}
