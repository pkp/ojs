<?php

/**
 * @file controllers/PremiumSubmissionHelperSettingsHandler.inc.php
 *
 * @class PremiumSubmissionHelperSettingsHandler
 * @ingroup controllers
 *
 * @brief Gère les pages de paramètres du plugin.
 */

declare(strict_types=1);

namespace APP\plugins\generic\premiumSubmissionHelper\controllers;

// Application classes
use APP\core\Application;
use APP\core\Request;
use APP\handler\Handler;
use APP\notification\NotificationManager;
use APP\plugins\generic\premiumSubmissionHelper\PremiumSubmissionHelperPlugin;
use APP\template\TemplateManager;
// PKP classes
use PKP\db\DAORegistry;
use PKP\plugins\PluginRegistry;
use PKP\security\authorization\ContextRequiredPolicy;
use PKP\security\Role;

class PremiumSubmissionHelperSettingsHandler extends Handler
{
    protected PremiumSubmissionHelperPlugin $plugin;

    /**
     * Affiche la page de paramètres du plugin
     * @param array $args Arguments
     * @param Request $request La requête
     */
    public function settings($args, $request)
    {
        $plugin = $this->plugin;
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
            ->addGroup('general', ['label' => __('plugins.generic.premiumHelper.settings.generalSettings')])
                ->addField(new FieldOptions('enabled', [
                    'label' => __('plugins.generic.premiumHelper.settings.enabled'),
                    'description' => __('plugins.generic.premiumHelper.settings.enabled.description'),
                    'type' => 'radio',
                    'options' => [
                        ['value' => true, 'label' => __('common.enable')],
                        ['value' => false, 'label' => __('common.disable')],
                    ],
                    'value' => $settings['enabled'] ?? true,
                ]))
                ->addField(new FieldText('minWordCount', [
                    'label' => __('plugins.generic.premiumHelper.settings.minWordCount'),
                    'description' => __('plugins.generic.premiumHelper.settings.minWordCount.description'),
                    'value' => $settings['minWordCount'] ?? 50,
                    'size' => 5,
                ]))
                ->addField(new FieldText('maxWordCount', [
                    'label' => __('plugins.generic.premiumHelper.settings.maxWordCount'),
                    'description' => __('plugins.generic.premiumHelper.settings.maxWordCount.description'),
                    'value' => $settings['maxWordCount'] ?? 300,
                    'size' => 5,
                ]));

        // Section d'affichage
        $settingsForm->addPage('display', __('plugins.generic.premiumHelper.settings.display'))
            ->addGroup('display', ['label' => __('plugins.generic.premiumHelper.settings.displaySettings')])
                ->addField(new FieldOptions('showWordCount', [
                    'label' => __('plugins.generic.premiumHelper.settings.showWordCount'),
                    'description' => __('plugins.generic.premiumHelper.settings.showWordCount.description'),
                    'type' => 'radio',
                    'options' => [
                        ['value' => true, 'label' => __('common.yes')],
                        ['value' => false, 'label' => __('common.no')],
                    ],
                    'value' => $settings['showWordCount'] ?? true,
                ]))
                ->addField(new FieldOptions('showSentenceCount', [
                    'label' => __('plugins.generic.premiumHelper.settings.showSentenceCount'),
                    'description' => __('plugins.generic.premiumHelper.settings.showSentenceCount.description'),
                    'type' => 'radio',
                    'options' => [
                        ['value' => true, 'label' => __('common.yes')],
                        ['value' => false, 'label' => __('common.no')],
                    ],
                    'value' => $settings['showSentenceCount'] ?? true,
                ]))
                ->addField(new FieldOptions('showReadabilityScore', [
                    'label' => __('plugins.generic.premiumHelper.settings.showReadabilityScore'),
                    'description' => __('plugins.generic.premiumHelper.settings.showReadabilityScore.description'),
                    'type' => 'radio',
                    'options' => [
                        ['value' => true, 'label' => __('common.yes')],
                        ['value' => false, 'label' => __('common.no')],
                    ],
                    'value' => $settings['showReadabilityScore'] ?? true,
                ]));

        // Section avancée
        $settingsForm->addPage('advanced', __('plugins.generic.premiumHelper.settings.advanced'))
            ->addGroup('advanced', ['label' => __('plugins.generic.premiumHelper.settings.advancedSettings')])
                ->addField(new FieldOptions('enableAdvancedAnalysis', [
                    'label' => __('plugins.generic.premiumHelper.settings.enableAdvancedAnalysis'),
                    'description' => __('plugins.generic.premiumHelper.settings.enableAdvancedAnalysis.description'),
                    'type' => 'radio',
                    'options' => [
                        ['value' => true, 'label' => __('common.enable')],
                        ['value' => false, 'label' => __('common.disable')],
                    ],
                    'value' => $settings['enableAdvancedAnalysis'] ?? false,
                ]))
                ->addField(new FieldTextArea('customStopWords', [
                    'label' => __('plugins.generic.premiumHelper.settings.customStopWords'),
                    'description' => __('plugins.generic.premiumHelper.settings.customStopWords.description'),
                    'value' => $settings['customStopWords'] ?? '',
                    'size' => 'large',
                ]))
                ->addField(new FieldOptions('enableDebugMode', [
                    'label' => __('plugins.generic.premiumHelper.settings.enableDebugMode'),
                    'description' => __('plugins.generic.premiumHelper.settings.enableDebugMode.description'),
                    'type' => 'radio',
                    'options' => [
                        ['value' => true, 'label' => __('common.enable')],
                        ['value' => false, 'label' => __('common.disable')],
                    ],
                    'value' => $settings['enableDebugMode'] ?? false,
                ]));

        // Assign template variables
        $templateMgr->assign([
            'settingsForm' => $settingsForm,
            'pluginName' => $plugin->getName(),
        ]);

        // Display the template
        // Afficher le template
        return $templateMgr->display($plugin->getTemplateResource('settings.tpl'));
    }

    /**
     * @copydoc Handler::authorize()
     */
    public function authorize($request, &$args, $roleAssignments)
    {
        $plugin = $this->plugin;
        $templateMgr = TemplateManager::getManager($request);

        // Vérifier les autorisations
        $context = $request->getContext();
        if (!$context) {
            $request->redirect(null, 'index');
        }

        // Call parent authorize to check standard permissions
        return parent::authorize($request, $args, $roleAssignments);
    }

    /**
     * Enregistre les paramètres
     * @param array $args
     * @param Request $request
     * @return JSONMessage
     */
    public function saveSettings($args, $request)
    {
        $plugin = $this->plugin;
        $context = $request->getContext();
        $contextId = $context ? $context->getId() : 0;

        // Vérifier le jeton CSRF
        $this->validateCsrf();

        // Récupérer les données du formulaire
        $settings = [
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
        ];

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
     * Set the plugin.
     * @param PremiumSubmissionHelperPlugin $plugin
     */
    public function setPlugin($plugin)
    {
        $this->plugin = $plugin;
    }
}
