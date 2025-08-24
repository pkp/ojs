<?php
/**
 * @file controllers/grid/settings/PremiumHelperSettingsGridHandler.inc.php
 *
 * Copyright (c) 2024 Université de Montréal
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class PremiumHelperSettingsGridHandler
 * @ingroup plugins_generic_premiumHelper
 *
 * @brief Gère les paramètres du plugin dans l'interface d'administration.
 */

import('lib.pkp.classes.controllers.grid.GridHandler');
import('plugins.generic.premiumHelper.controllers.grid.settings.PremiumHelperSettingsGridRow');

class PremiumHelperSettingsGridHandler extends GridHandler {
    /** @var PremiumHelperPlugin Le plugin */
    static $plugin;

    /**
     * @copydoc GridHandler::initialize()
     */
    function initialize($request, $args = null) {
        parent::initialize($request, $args);
        
        // Charger les traductions
        AppLocale::requireComponents(LOCALE_COMPONENT_APP_ADMIN);
        
        // Configuration de base de la grille
        $this->setTitle('plugins.generic.premiumHelper.settings');
        
        // Colonnes
        $this->addColumn(new GridColumn('name', 'common.name'));
        $this->addColumn(new GridColumn('value', 'common.value'));
    }

    /**
     * @copydoc GridHandler::loadData()
     */
    protected function loadData($request, $filter) {
        $plugin = self::$plugin;
        $contextId = $request->getContext() ? $request->getContext()->getId() : 0;
        
        // Récupérer les paramètres actuels
        $settings = $plugin->getSetting($contextId, 'settings');
        
        // Si aucun paramètre n'est défini, utiliser les valeurs par défaut
        if (empty($settings)) {
            $settings = array(
                'enabled' => true,
                'minWordCount' => 50,
                'maxWordCount' => 300,
                'readabilityThreshold' => 60,
                'showWordCount' => true,
                'showSentenceCount' => true,
                'showReadabilityScore' => true,
                'maxKeywords' => 10,
                'enableAdvancedAnalysis' => false,
                'customStopWords' => '',
                'enableDebugMode' => false
            );
        }
        
        // Préparer les données pour la grille
        $data = array();
        
        foreach ($settings as $key => $value) {
            $data[] = array(
                'id' => $key,
                'name' => $plugin->getDisplayName() . '.settings.' . $key,
                'value' => is_bool($value) ? ($value ? __('common.yes') : __('common.no')) : $value
            );
        }
        
        return $data;
    }

    /**
     * @copydoc GridHandler::initFeatures()
     */
    function initFeatures($request, $args) {
        import('lib.pkp.classes.controllers.grid.feature.PagingFeature');
        return array(new PagingFeature());
    }

    /**
     * @copydoc GridHandler::getRowInstance()
     */
    function getRowInstance() {
        return new PremiumHelperSettingsGridRow();
    }

    /**
     * Définit le plugin
     * @param $plugin PremiumHelperPlugin
     */
    static function setPlugin($plugin) {
        self::$plugin = $plugin;
    }
}

// Définir le plugin pour la classe
$plugin = PluginRegistry::getPlugin('generic', 'premiumhelperplugin');
PremiumHelperSettingsGridHandler::setPlugin($plugin);
