<?php
/**
 * @file classes/form/SettingsForm.inc.php
 *
 * Copyright (c) 2024 Université de Montréal
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class SettingsForm
 * @ingroup plugins_generic_premiumHelper
 *
 * @brief Formulaire de configuration du plugin Premium Helper
 */

import('lib.pkp.classes.form.Form');

class SettingsForm extends Form {
    /** @var PremiumHelperPlugin Le plugin */
    protected $plugin;
    
    /** @var int ID du contexte */
    protected $contextId;
    
    /**
     * Constructeur
     * @param $plugin PremiumHelperPlugin Le plugin
     * @param $contextId int ID du contexte
     */
    public function __construct($plugin, $contextId) {
        parent::__construct($plugin->getTemplateResource('settings.tpl'));
        $this->plugin = $plugin;
        $this->contextId = $contextId;
        
        // Ajouter les validations
        $this->addCheck(new FormValidatorPost($this));
        $this->addCheck(new FormValidatorCSRF($this));
        
        // Appeler les méthodes d'initialisation
        $this->initData();
    }
    
    /**
     * @copydoc Form::initData()
     */
    public function initData() {
        $contextId = $this->contextId;
        $plugin = $this->plugin;
        
        // Charger les paramètres existants
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
        
        // Définir les données du formulaire
        $this->_data = $settings;
        
        parent::initData();
    }
    
    /**
     * @copydoc Form::readInputData()
     */
    public function readInputData() {
        $this->readUserVars(array(
            'enabled',
            'minWordCount',
            'maxWordCount',
            'readabilityThreshold',
            'showWordCount',
            'showSentenceCount',
            'showReadabilityScore',
            'maxKeywords',
            'enableAdvancedAnalysis',
            'customStopWords',
            'enableDebugMode'
        ));
        
        // Convertir les cases à cocher en booléens
        foreach (['enabled', 'showWordCount', 'showSentenceCount', 'showReadabilityScore', 'enableAdvancedAnalysis', 'enableDebugMode'] as $key) {
            $this->_data[$key] = (bool) $this->getData($key);
        }
        
        // Convertir les nombres
        foreach (['minWordCount', 'maxWordCount', 'readabilityThreshold', 'maxKeywords'] as $key) {
            $this->_data[$key] = (int) $this->getData($key);
        }
    }
    
    /**
     * @copydoc Form::fetch()
     */
    public function fetch($request, $template = null, $display = false) {
        $templateMgr = TemplateManager::getManager($request);
        $templateMgr->assign([
            'pluginName' => $this->plugin->getName(),
            'pluginBaseUrl' => $request->getBaseUrl() . '/' . $this->plugin->getPluginPath(),
            'currentPage' => $request->getUserVar('page') ?? 'general',
        ]);
        
        return parent::fetch($request, $template, $display);
    }
    
    /**
     * @copydoc Form::execute()
     */
    public function execute(...$functionArgs) {
        $plugin = $this->plugin;
        $contextId = $this->contextId;
        
        // Préparer les paramètres à enregistrer
        $settings = [
            'enabled' => (bool) $this->getData('enabled'),
            'minWordCount' => max(10, (int) $this->getData('minWordCount')),
            'maxWordCount' => max(50, (int) $this->getData('maxWordCount')),
            'readabilityThreshold' => min(100, max(0, (int) $this->getData('readabilityThreshold'))),
            'showWordCount' => (bool) $this->getData('showWordCount'),
            'showSentenceCount' => (bool) $this->getData('showSentenceCount'),
            'showReadabilityScore' => (bool) $this->getData('showReadabilityScore'),
            'maxKeywords' => min(20, max(1, (int) $this->getData('maxKeywords'))),
            'enableAdvancedAnalysis' => (bool) $this->getData('enableAdvancedAnalysis'),
            'customStopWords' => $this->getData('customStopWords'),
            'enableDebugMode' => (bool) $this->getData('enableDebugMode')
        ];
        
        // S'assurer que le nombre maximum de mots est supérieur au minimum
        if ($settings['maxWordCount'] < $settings['minWordCount']) {
            $settings['maxWordCount'] = $settings['minWordCount'];
        }
        
        // Enregistrer les paramètres
        $plugin->updateSetting($contextId, 'settings', $settings, 'object');
        
        parent::execute(...$functionArgs);
    }
}
