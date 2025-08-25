<?php

declare(strict_types=1);

namespace APP\plugins\generic\premiumSubmissionHelper\controllers\grid\settings;

// Application classes
use APP\core\Application;
use APP\template\TemplateManager;
// PKP classes
use PKP\controllers\grid\GridRow;
use PKP\core\PKPApplication;
use PKP\linkAction\LinkAction;
use PKP\linkAction\request\RemoteActionConfirmationModal;
use PKP\plugins\PluginRegistry;

/**
 * @file controllers/grid/settings/PremiumSubmissionHelperSettingsGridRow.inc.php
 *
 * @class PremiumSubmissionHelperSettingsGridRow
 * @ingroup controllers_grid_settings
 *
 * @brief Gère les lignes de la grille des paramètres.
 */
class PremiumSubmissionHelperSettingsGridRow extends GridRow
{
    /**
     * @copydoc GridRow::initialize()
     */
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
