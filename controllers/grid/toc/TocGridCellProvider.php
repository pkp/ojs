<?php

/**
 * @file controllers/grid/toc/TocGridCellProvider.php
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2003-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class TocGridCellProvider
 *
 * @ingroup controllers_grid_toc
 *
 * @brief Grid cell provider for the TOC (Table of Contents) category grid
 */

namespace APP\controllers\grid\toc;

use APP\submission\Submission;
use PKP\controllers\grid\GridCellProvider;
use PKP\controllers\grid\GridColumn;
use PKP\controllers\grid\GridHandler;
use PKP\linkAction\LinkAction;
use PKP\linkAction\request\AjaxAction;

class TocGridCellProvider extends GridCellProvider
{
    /**
     * Extracts variables for a given column from a data element
     * so that they may be assigned to template before rendering.
     *
     * @param \PKP\controllers\grid\GridRow $row
     * @param GridColumn $column
     *
     * @return array
     */
    public function getTemplateVarsFromRowColumn($row, $column)
    {
        $element = $row->getData();
        $columnId = $column->getId();
        assert(!empty($columnId));
        switch ($columnId) {
            case 'title':
                return ['label' => $element->getLocalizedTitle()];
            case 'access':
                return ['selected' => $element->getCurrentPublication()->getData('accessStatus') == Submission::ARTICLE_ACCESS_OPEN];
            default: assert(false);
        }
    }

    /**
     * @copydoc GridCellProvider::getCellActions()
     */
    public function getCellActions($request, $row, $column, $position = GridHandler::GRID_ACTION_POSITION_DEFAULT)
    {
        switch ($column->getId()) {
            case 'access':
                $article = $row->getData(); /** @var Submission $article */
                return [new LinkAction(
                    'disable',
                    new AjaxAction(
                        $request->url(
                            null,
                            null,
                            'setAccessStatus',
                            null,
                            array_merge(
                                [
                                    'articleId' => $article->getId(),
                                    'status' => ($article->getCurrentPublication()->getData('accessStatus') == Submission::ARTICLE_ACCESS_OPEN) ? Submission::ARTICLE_ACCESS_ISSUE_DEFAULT : Submission::ARTICLE_ACCESS_OPEN,
                                    'csrfToken' => $request->getSession()->getCSRFToken(),
                                ],
                                $row->getRequestArgs()
                            )
                        )
                    ),
                    __('manager.plugins.disable'),
                    null
                )];
        }
        return parent::getCellActions($request, $row, $column, $position);
    }
}
