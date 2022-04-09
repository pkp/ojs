<?php

/**
 * @file controllers/grid/issues/IssueGridCellProvider.inc.php
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2003-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class IssueGridCellProvider
 * @ingroup controllers_grid_issues
 *
 * @brief Grid cell provider for the issue management grid
 */

use APP\core\Application;
use PKP\controllers\grid\GridCellProvider;
use PKP\controllers\grid\GridColumn;
use PKP\controllers\grid\GridHandler;
use PKP\core\PKPString;
use PKP\linkAction\LinkAction;
use PKP\linkAction\request\AjaxModal;

class IssueGridCellProvider extends GridCellProvider
{
    /** @var string */
    public $dateFormatShort;

    /**
     * Constructor
     */
    public function __construct()
    {
        parent::__construct();
        $this->dateFormatShort = PKPString::convertStrftimeFormat(Application::get()->getRequest()->getContext()->getLocalizedDateFormatShort());
    }

    /**
     * Get cell actions associated with this row/column combination
     *
     * @param \PKP\controllers\grid\GridRow $row
     * @param GridColumn $column
     *
     * @return array an array of LinkAction instances
     */
    public function getCellActions($request, $row, $column, $position = GridHandler::GRID_ACTION_POSITION_DEFAULT)
    {
        if ($column->getId() == 'identification') {
            $issue = $row->getData();
            assert(is_a($issue, 'Issue'));
            $router = $request->getRouter();
            return [
                new LinkAction(
                    'edit',
                    new AjaxModal(
                        $router->url($request, null, null, 'editIssue', null, ['issueId' => $issue->getId()]),
                        __('editor.issues.editIssue', ['issueIdentification' => $issue->getIssueIdentification()]),
                        'modal_edit',
                        true
                    ),
                    htmlspecialchars($issue->getIssueIdentification())
                )
            ];
        }
        return [];
    }

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
        $issue = $row->getData(); /** @var Issue $issue */
        $columnId = $column->getId();
        assert(is_a($issue, 'Issue'));
        assert(!empty($columnId));
        switch ($columnId) {
            case 'identification':
                return ['label' => '']; // Title returned as action
            case 'published':
                $datePublished = $issue->getDatePublished();
                if ($datePublished) {
                    $datePublished = strtotime($datePublished);
                }
                return ['label' => $datePublished ? date($this->dateFormatShort, $datePublished) : ''];
            case 'numArticles':
                return ['label' => $issue->getNumArticles()];
            default: assert(false); break;
        }
    }
}
