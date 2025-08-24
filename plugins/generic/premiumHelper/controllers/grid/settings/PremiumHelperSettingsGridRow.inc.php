<?php
/**
 * @file controllers/grid/settings/PremiumHelperSettingsGridRow.inc.php
 *
 * Copyright (c) 2024 Université de Montréal
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class PremiumHelperSettingsGridRow
 * @ingroup plugins_generic_premiumHelper
 *
 * @brief Gère les lignes de la grille des paramètres.
 */

import('lib.pkp.classes.controllers.grid.GridRow');

class PremiumHelperSettingsGridRow extends GridRow {
    /**
     * @copydoc GridRow::initialize()
     */
    function initialize($request, $template = null) {
        parent::initialize($request, $template);
        
        // Actions disponibles pour chaque ligne
        $plugin = PluginRegistry::getPlugin('generic', 'premiumhelperplugin');
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
