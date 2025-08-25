<?php

namespace APP\plugins\generic\premiumSubmissionHelper\controllers\grid\settings;

use PKP\controllers\grid\GridRow;
use PKP\core\PKPApplication;
use APP\core\Application;
use APP\template\TemplateManager;
use PKP\linkAction\LinkAction;
use PKP\linkAction\request\RemoteActionConfirmationModal;
use PKP\plugins\PluginRegistry;

/**
 * @file controllers/grid/settings/PremiumSubmissionHelperSettingsGridRow.inc.php
 *
 * Copyright (c) 2024 Université de Montréal
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class PremiumSubmissionHelperSettingsGridRow
 * @ingroup plugins_generic_premiumSubmissionHelper
 *
 * @brief Gère les lignes de la grille des paramètres.
 */

import('lib.pkp.classes.controllers.grid.GridRow');

class PremiumSubmissionHelperSettingsGridRow extends GridRow
{
    /**
     * @copydoc GridRow::initialize()
     */
    public function initialize($request, $template = null)
    {
        parent::initialize($request, $template);

        // Actions disponibles pour chaque ligne
        $plugin = PluginRegistry::getPlugin('generic', 'premiumsubmissionhelperplugin');
        $router = $request->getRouter();

        $this->addAction(
            new LinkAction(
                'edit',
                new AjaxModal(
                    $router->url(
                        $request,
                        null,
                        null,
                        'editSetting',
                        null,
                        array('settingName' => $this->getId())
                    ),
                    __('grid.action.edit'),
                    'modal_edit',
                    true
                ),
                __('grid.action.edit'),
                'edit'
            )
        );
    }
}
