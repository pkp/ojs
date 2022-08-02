<?php

/**
 * @file controllers/grid/settings/sections/SectionGridRow.php
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2003-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class SectionGridRow
 * @ingroup controllers_grid_settings_section
 *
 * @brief Handle section grid row requests.
 */

namespace APP\controllers\grid\settings\sections;

use PKP\controllers\grid\GridRow;
use PKP\linkAction\LinkAction;
use PKP\linkAction\request\AjaxModal;
use PKP\linkAction\request\RemoteActionConfirmationModal;

class SectionGridRow extends GridRow
{
    //
    // Overridden template methods
    //
    /**
     * @copydoc GridRow::initialize()
     *
     * @param null|mixed $template
     */
    public function initialize($request, $template = null)
    {
        parent::initialize($request, $template);

        // Is this a new row or an existing row?
        $sectionId = $this->getId();
        if (!empty($sectionId) && is_numeric($sectionId)) {
            $router = $request->getRouter();

            $this->addAction(
                new LinkAction(
                    'editSection',
                    new AjaxModal(
                        $router->url($request, null, null, 'editSection', null, ['sectionId' => $sectionId]),
                        __('grid.action.edit'),
                        'modal_edit',
                        true
                    ),
                    __('grid.action.edit'),
                    'edit'
                )
            );

            $this->addAction(
                new LinkAction(
                    'deleteSection',
                    new RemoteActionConfirmationModal(
                        $request->getSession(),
                        __('manager.sections.confirmDelete'),
                        __('grid.action.delete'),
                        $router->url($request, null, null, 'deleteSection', null, ['sectionId' => $sectionId]),
                        'modal_delete'
                    ),
                    __('grid.action.delete'),
                    'delete'
                )
            );
        }
    }
}
