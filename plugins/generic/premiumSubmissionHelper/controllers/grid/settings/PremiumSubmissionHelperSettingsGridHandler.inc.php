<?php

/**
 * @file controllers/grid/settings/PremiumSubmissionHelperSettingsGridHandler.inc.php
 *
 * Copyright (c) 2024 Université de Montréal
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class PremiumSubmissionHelperSettingsGridHandler
 * @ingroup plugins_generic_premiumSubmissionHelper
 *
 * @brief Gère les paramètres du plugin dans l'interface d'administration.
 */

import('lib.pkp.classes.controllers.grid.GridHandler');
import('plugins.generic.premiumSubmissionHelper.controllers.grid.settings.PremiumSubmissionHelperSettingsGridRow');

class PremiumSubmissionHelperSettingsGridHandler extends GridHandler
{
    /** @var PremiumSubmissionHelperPlugin Le plugin */
    static $plugin;

    /**
     * @copydoc GridHandler::initialize()
     */
    function initialize($request, $args = null)
    {
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
    protected function loadData($request, $filter)
    {
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

        // Définir l'action pour la création d'un nouvel élément
        $router = $request->getRouter();
        $this->addAction(
            new LinkAction(
                'addSettings',
                new AjaxModal(
                    $router->url($request, null, null, 'addSettings'),
                    __('plugins.generic.premiumSubmissionHelper.settings.add'),
                    'modal_add_item'
                ),
                __('plugins.generic.premiumSubmissionHelper.settings.add'),
                'add_item'
            )
        );

        return $data;
    }

    /**
     * @copydoc GridHandler::initFeatures()
     */
    function initFeatures($request, $args)
    {
        import('lib.pkp.classes.controllers.grid.feature.PagingFeature');
        return array(new PagingFeature());
    }

    /**
     * @copydoc GridHandler::getRowInstance()
     */
    function getRowInstance()
    {
        return new PremiumSubmissionHelperSettingsGridRow();
    }

    /**
     * Définit le plugin
     * @param $plugin PremiumSubmissionHelperPlugin
     */
    static function setPlugin($plugin)
    {
        self::$plugin = $plugin;
    }
}

// Définir le plugin pour la classe
$plugin = PluginRegistry::getPlugin('generic', 'premiumsubmissionhelperplugin');
PremiumSubmissionHelperSettingsGridHandler::setPlugin($plugin);
