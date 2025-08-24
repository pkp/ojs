<?php
/**
 * @file controllers/SettingsHandler.inc.php
 *
 * Copyright (c) 2024 Université de Montréal
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class SettingsHandler
 * @ingroup plugins_generic_premiumHelper
 *
 * @brief Gère les pages de paramètres du plugin.
 */

import('classes.handler.Handler');

class SettingsHandler extends Handler {
    /** @var PremiumHelperPlugin Le plugin */
    static $plugin;

    /**
     * Affiche la page de paramètres du plugin
     * @param $args array Arguments
     * @param $request Request La requête
     */
    function settings($args, $request) {
        $plugin = self::$plugin;
        $templateMgr = TemplateManager::getManager($request);
        
        // Vérifier les autorisations
        $context = $request->getContext();
        if (!$context) {
            $request->redirect(null, 'index');
        }
        
        // Charger les paramètres actuels
        $contextId = $context->getId();
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
        
        // Préparer le formulaire
        $settingsForm = new FormBuilderSchema();
        $settingsForm->setAction($request->getRouter()->url($request, null, null, 'saveSettings'));
        
        // Section principale
        $settingsForm->addPage('general', __('plugins.generic.premiumHelper.settings.general'))
            ->addGroup('general', array('label' => __('plugins.generic.premiumHelper.settings.generalSettings')))
                ->addField(new FieldOptions('enabled', array(
                    'label' => __('plugins.generic.premiumHelper.settings.enabled'),
                    'description' => __('plugins.generic.premiumHelper.settings.enabled.description'),
                    'type' => 'radio',
                    'options' => array(
                        array('value' => true, 'label' => __('common.enable')),
                        array('value' => false, 'label' => __('common.disable')),
                    ),
                    'value' => $settings['enabled'] ?? true,
                )))
                ->addField(new FieldText('minWordCount', array(
                    'label' => __('plugins.generic.premiumHelper.settings.minWordCount'),
                    'description' => __('plugins.generic.premiumHelper.settings.minWordCount.description'),
                    'value' => $settings['minWordCount'] ?? 50,
                    'size' => 5,
                )))
                ->addField(new FieldText('maxWordCount', array(
                    'label' => __('plugins.generic.premiumHelper.settings.maxWordCount'),
                    'description' => __('plugins.generic.premiumHelper.settings.maxWordCount.description'),
                    'value' => $settings['maxWordCount'] ?? 300,
                    'size' => 5,
                )));
        
        // Section d'affichage
        $settingsForm->addPage('display', __('plugins.generic.premiumHelper.settings.display'))
            ->addGroup('display', array('label' => __('plugins.generic.premiumHelper.settings.displaySettings')))
                ->addField(new FieldOptions('showWordCount', array(
                    'label' => __('plugins.generic.premiumHelper.settings.showWordCount'),
                    'description' => __('plugins.generic.premiumHelper.settings.showWordCount.description'),
                    'type' => 'radio',
                    'options' => array(
                        array('value' => true, 'label' => __('common.yes')),
                        array('value' => false, 'label' => __('common.no')),
                    ),
                    'value' => $settings['showWordCount'] ?? true,
                )))
                ->addField(new FieldOptions('showSentenceCount', array(
                    'label' => __('plugins.generic.premiumHelper.settings.showSentenceCount'),
                    'description' => __('plugins.generic.premiumHelper.settings.showSentenceCount.description'),
                    'type' => 'radio',
                    'options' => array(
                        array('value' => true, 'label' => __('common.yes')),
                        array('value' => false, 'label' => __('common.no')),
                    ),
                    'value' => $settings['showSentenceCount'] ?? true,
                )))
                ->addField(new FieldOptions('showReadabilityScore', array(
                    'label' => __('plugins.generic.premiumHelper.settings.showReadabilityScore'),
                    'description' => __('plugins.generic.premiumHelper.settings.showReadabilityScore.description'),
                    'type' => 'radio',
                    'options' => array(
                        array('value' => true, 'label' => __('common.yes')),
                        array('value' => false, 'label' => __('common.no')),
                    ),
                    'value' => $settings['showReadabilityScore'] ?? true,
                )));
        
        // Section avancée
        $settingsForm->addPage('advanced', __('plugins.generic.premiumHelper.settings.advanced'))
            ->addGroup('advanced', array('label' => __('plugins.generic.premiumHelper.settings.advancedSettings')))
                ->addField(new FieldOptions('enableAdvancedAnalysis', array(
                    'label' => __('plugins.generic.premiumHelper.settings.enableAdvancedAnalysis'),
                    'description' => __('plugins.generic.premiumHelper.settings.enableAdvancedAnalysis.description'),
                    'type' => 'radio',
                    'options' => array(
                        array('value' => true, 'label' => __('common.enable')),
                        array('value' => false, 'label' => __('common.disable')),
                    ),
                    'value' => $settings['enableAdvancedAnalysis'] ?? false,
                )))
                ->addField(new FieldTextArea('customStopWords', array(
                    'label' => __('plugins.generic.premiumHelper.settings.customStopWords'),
                    'description' => __('plugins.generic.premiumHelper.settings.customStopWords.description'),
                    'value' => $settings['customStopWords'] ?? '',
                    'size' => 'large',
                )))
                ->addField(new FieldOptions('enableDebugMode', array(
                    'label' => __('plugins.generic.premiumHelper.settings.enableDebugMode'),
                    'description' => __('plugins.generic.premiumHelper.settings.enableDebugMode.description'),
                    'type' => 'radio',
                    'options' => array(
                        array('value' => true, 'label' => __('common.enable')),
                        array('value' => false, 'label' => __('common.disable')),
                    ),
                    'value' => $settings['enableDebugMode'] ?? false,
                )));
        
        // Assigner le formulaire au template
        $templateMgr->assign('settingsForm', $settingsForm);
        $templateMgr->assign('pluginName', $plugin->getName());
        $templateMgr->assign('pluginBaseUrl', $request->getBaseUrl() . '/' . $plugin->getPluginPath());
        
        // Afficher le template
        return $templateMgr->display($plugin->getTemplateResource('settings.tpl'));
    }
    
    /**
     * Sauvegarde les paramètres du plugin
     * @param $args array Arguments
     * @param $request Request La requête
     */
    function saveSettings($args, $request) {
        $plugin = self::$plugin;
        $context = $request->getContext();
        $contextId = $context ? $context->getId() : 0;
        
        // Vérifier le jeton CSRF
        $this->validateCsrf();
        
        // Récupérer les données du formulaire
        $settings = array(
            'enabled' => (bool) $request->getUserVar('enabled'),
            'minWordCount' => (int) $request->getUserVar('minWordCount'),
            'maxWordCount' => (int) $request->getUserVar('maxWordCount'),
            'readabilityThreshold' => (int) $request->getUserVar('readabilityThreshold'),
            'showWordCount' => (bool) $request->getUserVar('showWordCount'),
            'showSentenceCount' => (bool) $request->getUserVar('showSentenceCount'),
            'showReadabilityScore' => (bool) $request->getUserVar('showReadabilityScore'),
            'maxKeywords' => (int) $request->getUserVar('maxKeywords'),
            'enableAdvancedAnalysis' => (bool) $request->getUserVar('enableAdvancedAnalysis'),
            'customStopWords' => $request->getUserVar('customStopWords'),
            'enableDebugMode' => (bool) $request->getUserVar('enableDebugMode')
        );
        
        // Valider les données
        if ($settings['minWordCount'] < 10) {
            $settings['minWordCount'] = 10;
        }
        
        if ($settings['maxWordCount'] < $settings['minWordCount']) {
            $settings['maxWordCount'] = $settings['minWordCount'];
        }
        
        if ($settings['maxKeywords'] < 1) {
            $settings['maxKeywords'] = 1;
        } elseif ($settings['maxKeywords'] > 20) {
            $settings['maxKeywords'] = 20;
        }
        
        // Sauvegarder les paramètres
        $plugin->updateSetting($contextId, 'settings', $settings, 'object');
        
        // Journaliser l'événement
        import('classes.core.Services');
        $eventLog = Services::get('eventLog');
        $eventLog->log(
            $contextId,
            'premiumHelper.settings.updated',
            null,
            'plugins.generic.premiumHelper.settings.updated',
            array('userName' => $request->getUser()->getFullName())
        );
        
        // Rediriger avec un message de confirmation
        $notificationManager = new NotificationManager();
        $notificationManager->createTrivialNotification(
            $request->getUser()->getId(),
            NOTIFICATION_TYPE_SUCCESS,
            array('contents' => __('plugins.generic.premiumHelper.settings.saved'))
        );
        
        return new JSONMessage(true);
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
SettingsHandler::setPlugin($plugin);
