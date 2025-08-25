<?php

declare(strict_types=1);

namespace APP\plugins\generic\premiumSubmissionHelper\controllers\grid\settings;

// Application classes
use APP\core\Application;
use APP\notification\NotificationManager;
use APP\plugins\generic\premiumSubmissionHelper\PremiumSubmissionHelperPlugin;
use APP\template\TemplateManager;

// PKP classes
use PKP\controllers\grid\GridHandler;
use PKP\core\JSONMessage;
use PKP\core\PKPRequest;
use PKP\plugins\PluginRegistry;
use PKP\security\authorization\ContextAccessPolicy;
use PKP\security\Role;

/**
 * @file controllers/grid/settings/PremiumSubmissionHelperSettingsGridHandler.inc.php
 *
 * @class PremiumSubmissionHelperSettingsGridHandler
 * @ingroup controllers_grid_settings
 *
 * @brief Gère les paramètres du plugin dans l'interface d'administration.
 */
class PremiumSubmissionHelperSettingsGridHandler extends GridHandler
{
    protected PremiumSubmissionHelperPlugin $plugin;

    /**
     * Set the plugin.
     * @param PremiumSubmissionHelperPlugin $plugin
     */
    public function setPlugin($plugin): void
    {
        $this->plugin = $plugin;
    }

    /**
     * @copydoc GridHandler::initialize()
     */
    public function initialize($request, $args = null)
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
        $plugin = $this->plugin;
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
     * Initialize features
     */
    public function initFeatures($request, $args)
    {
        import('lib.pkp.classes.controllers.grid.feature.PagingFeature');
        return array(new PagingFeature());
    }

    /**
     * @copydoc GridHandler::getRowInstance()
     */
    /**
     * @copydoc GridHandler::getRowInstance()
     */
    protected function getRowInstance()
    {
        return new PremiumSubmissionHelperSettingsGridRow();
    }

}

