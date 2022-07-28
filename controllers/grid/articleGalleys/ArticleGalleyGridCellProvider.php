<?php

/**
 * @file controllers/grid/articleGalleys/ArticleGalleyGridCellProvider.inc.php
 *
 * Copyright (c) 2016-2021 Simon Fraser University
 * Copyright (c) 2000-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class ArticleGalleyGridCellProvider
 * @ingroup controllers_grid_articleGalleys
 *
 * @brief Base class for a cell provider for article galleys.
 */

use APP\facades\Repo;
use PKP\controllers\grid\DataObjectGridCellProvider;
use PKP\controllers\grid\GridHandler;

class ArticleGalleyGridCellProvider extends DataObjectGridCellProvider
{
    /** @var Submission */
    public $_submission;

    /** @var Publication */
    public $_publication;

    public $_isEditable;

    /**
     * Constructor
     *
     * @param Submission $submission
     */
    public function __construct($submission, $publication, $isEditable)
    {
        parent::__construct();
        $this->_submission = $submission;
        $this->_publication = $publication;
        $this->_isEditable = $isEditable;
    }

    //
    // Template methods from GridCellProvider
    //
    /**
     * @copydoc GridCellProvider::getTemplateVarsFromRowColumn()
     */
    public function getTemplateVarsFromRowColumn($row, $column)
    {
        $element = $row->getData();
        $columnId = $column->getId();
        assert($element instanceof \PKP\core\DataObject && !empty($columnId));

        switch ($columnId) {
            case 'label':
                return [
                    'label' => ($element->getRemoteUrl() == '' && $element->getData('submissionFileId')) ? '' : $element->getLabel()
                ];
                break;
            default: assert(false);
        }
        return parent::getTemplateVarsFromRowColumn($row, $column);
    }

    /**
     * Get request arguments.
     *
     * @param \PKP\controllers\grid\GridRow $row
     *
     * @return array
     */
    public function getRequestArgs($row)
    {
        return [
            'submissionId' => $this->_submission->getId(),
            'publicationId' => $this->_publication->getId(),
        ];
    }

    /**
     * @copydoc GridCellProvider::getCellActions()
     */
    public function getCellActions($request, $row, $column, $position = GridHandler::GRID_ACTION_POSITION_DEFAULT)
    {
        switch ($column->getId()) {
            case 'label':
                $element = $row->getData();
                if ($element->getRemoteUrl() != '' || !$element->getData('submissionFileId')) {
                    break;
                }

                $submissionFile = Repo::submissionFile()
                    ->get($element->getData('submissionFileId'));
                import('lib.pkp.controllers.api.file.linkAction.DownloadFileLinkAction');
                return [new DownloadFileLinkAction($request, $submissionFile, WORKFLOW_STAGE_ID_PRODUCTION, $element->getLabel())];
        }
        return parent::getCellActions($request, $row, $column, $position);
    }
}
