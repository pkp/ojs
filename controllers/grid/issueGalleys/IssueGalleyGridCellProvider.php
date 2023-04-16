<?php

/**
 * @file controllers/grid/issueGalleys/IssueGalleyGridCellProvider.php
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2003-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class IssueGalleyGridCellProvider
 *
 * @ingroup issue_galley
 *
 * @brief Grid cell provider for the issue galleys grid
 */

namespace APP\controllers\grid\issueGalleys;

use PKP\controllers\grid\GridCellProvider;
use PKP\controllers\grid\GridColumn;
use PKP\facades\Locale;

class IssueGalleyGridCellProvider extends GridCellProvider
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
        $issueGalley = $row->getData();
        $columnId = $column->getId();
        assert(is_a($issueGalley, 'IssueGalley'));
        assert(!empty($columnId));

        switch ($columnId) {
            case 'label': return ['label' => $issueGalley->getLabel()];
            case 'locale': return ['label' => Locale::getMetadata($issueGalley->getLocale())->getDisplayName()];
            case 'publicGalleyId': return ['label' => $issueGalley->getStoredPubId('publisher-id')];
            default: assert(false);
                break;
        }
    }
}
