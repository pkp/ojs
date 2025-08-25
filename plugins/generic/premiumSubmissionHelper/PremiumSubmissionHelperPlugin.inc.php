<?php

namespace APP\plugins\generic\premiumSubmissionHelper;

/**
 * @file plugins/generic/premiumSubmissionHelper/PremiumSubmissionHelperPlugin.inc.php
 *
 * Copyright (c) 2025 Université de Montréal
 * Copyright (c) 2025 Saliou Ngom
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class PremiumSubmissionHelperPlugin
 * @ingroup plugins_generic_premiumSubmissionHelper
 *
 * @brief Plugin d'aide à la rédaction pour les utilisateurs premium
 *
 * Ce plugin offre des fonctionnalités avancées d'analyse de texte pour les utilisateurs premium.
 * Il permet d'analyser les résumés en temps réel et de fournir des suggestions d'amélioration.
 *
 * Fonctionnalités principales :
 * - Analyse en temps réel des résumés
 * - Comptage des mots et des phrases
 * - Calcul d'un score de lisibilité
 * - Extraction automatique de mots-clés pertinents
 * - Interface utilisateur intégrée au formulaire de soumission OJS
 * - Support multilingue (français par défaut)
 * - Restriction aux utilisateurs premium
 *
 * @author Saliou Ngom <saliou.ngom@umontreal.ca>
 * @github https://github.com/Salioungom/ojs
 */

import('lib.pkp.classes.plugins.GenericPlugin');

/**
 * Classe principale du plugin Premium Helper
 *
 * Gère l'initialisation du plugin, l'injection des éléments d'interface utilisateur
 * et la configuration des routes d'API.
 */
class PremiumSubmissionHelperPlugin extends GenericPlugin
{
    /** @var array Rôles autorisés à utiliser la fonctionnalité premium */
    protected const ALLOWED_ROLES = [
        ROLE_ID_MANAGER,
        ROLE_ID_SUB_EDITOR,
        ROLE_ID_AUTHOR
    ];

    /**
     * @copydoc Plugin::register()
     */
    public function register($category, $path, $mainContextId = null)
    {
        $success = parent::register($category, $path, $mainContextId);

        if ($success && $this->getEnabled()) {
            // Enregistrer les hooks
            HookRegistry::register('TemplateManager::display', array($this, 'injectAnalysisButton'));
            HookRegistry::register('LoadHandler', array($this, 'setupAPIHandler'));

            // Ajouter le CSS et JS nécessaires
            HookRegistry::register('TemplateManager::include', array($this, 'addScripts'));
        }

        return $success;
    }

    /**
     * @copydoc Plugin::getDisplayName()
     */
    public function getDisplayName()
    {
        return __('plugins.generic.premiumHelper.displayName');
    }

    /**
     * @copydoc Plugin::getDescription()
     */
    public function getDescription()
    {
        return __('plugins.generic.premiumHelper.description');
    }

    /**
     * Injecte le bouton d'analyse dans le formulaire de soumission
     * @param $hookName string Le nom du hook
     * @param $args array Les arguments du hook
     * @return bool
     */
    public function injectAnalysisButton($hookName, $args)
    {
        $templateMgr = $args[0];
        $template = $args[1];

        // Ne s'applique qu'au formulaire de soumission
        if ($template !== 'submission/form/step1.tpl') {
            return false;
        }

        // Vérifier si l'utilisateur est premium
        $request = Application::get()->getRequest();
        $user = $request->getUser();
        $context = $request->getContext();

        if (!$user || !$context) {
            return false;
        }

        $isPremiumUser = $this->isUserPremium($context->getId());

        // Ajouter les données au template
        $apiUrl = $request->getDispatcher()->url(
            $request,
            ROUTE_PAGE,
            null,
            self::API_URL
        );

        $templateMgr->assign([
            'isPremiumUser' => $isPremiumUser,
            'apiUrl' => $apiUrl,
            'pluginUrl' => $request->getBaseUrl() . '/' . $this->getPluginPath(),
        ]);

        // Ajouter le template du bouton
        $templateMgr->display($this->getTemplateResource('premiumSubmissionHelper.tpl'));

        return false;
    }

    /**
     * Configure le gestionnaire d'API
     * @param $hookName string Le nom du hook
     * @param $args array Les arguments du hook
     * @return bool
     */
    public function setupAPIHandler($hookName, $args)
    {
        $page = $args[0];
        $op = $args[1];
        $sourceFile =& $args[2];

        if ($page === self::API_URL) {
            $this->import('pages.APIHandler');
            $handler = new APIHandler($this);
            $handler->handle($op, $sourceFile);
            return true;
        }

        return false;
    }

    /**
     * Ajoute les scripts et styles nécessaires
     * @param $hookName string Le nom du hook
     * @param $args array Les arguments du hook
     * @return bool
     */
    public function addScripts($hookName, $args)
    {
        $templateMgr = TemplateManager::getManager();
        $request = Application::get()->getRequest();

        // Vérifier que nous sommes sur la page de soumission
        $router = $request->getRouter();
        if (!$router) {
            return false;
        }

        $requestedPage = $router->getRequestedPage($request);
        $requestedOp = $router->getRequestedOp($request);

        if ($requestedPage !== 'submission' || $requestedOp !== 'wizard') {
            return false;
        }

        // Ajouter le CSS
        $templateMgr->addStyleSheet(
            'premiumSubmissionHelperStyles',
            $request->getBaseUrl() . '/' . $this->getPluginPath() . '/styles/premiumSubmissionHelper.css',
            [
                'contexts' => 'backend',
                'priority' => STYLE_SEQUENCE_LAST
            ]
        );

        // Ajouter le JavaScript
        $templateMgr->addJavaScript(
            'premiumSubmissionHelperScripts',
            $request->getBaseUrl() . '/' . $this->getPluginPath() . '/js/main.js',
            [
                'contexts' => ['frontend'],
                'priority' => STYLE_SEQUENCE_LAST
            ]
        );

        return false;
    }

    /**
     * Obtient les actions disponibles pour ce plugin
     *
     * @param Request $request La requête en cours
     * @param array $actionArgs Les arguments d'action
     * @return array Tableau d'actions disponibles pour ce plugin
     */
    public function getActions($request, $actionArgs)
    {
        // Obtenir les actions existantes
        $actions = parent::getActions($request, $actionArgs);

        // Vérifier les autorisations
        $user = $request->getUser();
        $dispatcher = $request->getDispatcher();

        if (!$user) {
            return $actions;
        }

        // Ajouter un lien vers les paramètres
        import('lib.pkp.classes.linkAction.request.RedirectAction');
        $router = $request->getRouter();

        $linkAction = new LinkAction(
            'settings',
            new AjaxModal(
                $router->url(
                    $request,
                    null,
                    null,
                    'settings',
                    null,
                    array('plugin' => $this->getName(), 'category' => 'generic')
                ),
                $this->getDisplayName()
            ),
            __('plugins.generic.premiumHelper.settings'),
            null
        );

        // Ajouter l'action en première position
        array_unshift($actions, $linkAction);

        return $actions;
    }

    /**
     * Gère les actions du plugin
     *
     * @param array $args Les arguments de la requête
     * @param Request $request L'objet de requête
     * @return JSONMessage Le résultat de l'action
     */
    public function manage($args, $request)
    {
        switch ($request->getUserVar('verb')) {
            case 'settings':
                $context = $request->getContext();
                $contextId = $context ? $context->getId() : 0;

                $settingsForm = new SettingsForm($this, $contextId);

                if ($request->getUserVar('save')) {
                    $settingsForm->readInputData();
                    if ($settingsForm->validate()) {
                        $settingsForm->execute();
                        $notificationManager = new NotificationManager();
                        $notificationManager->createTrivialNotification(
                            $request->getUser()->getId(),
                            NOTIFICATION_TYPE_SUCCESS,
                            array('contents' => __('plugins.generic.premiumHelper.settings.saved'))
                        );
                        return new JSONMessage(true);
                    }
                } else {
                    $settingsForm->initData();
                }

                return new JSONMessage(true, $settingsForm->fetch($request));
        }

        return parent::manage($args, $request);
    }

    /**
     * Configure le gestionnaire de paramètres
     *
     * @param string $hookName Nom du hook
     * @param array $params Paramètres du hook
     * @return bool
     */
    public function setupSettingsHandler($hookName, $params)
    {
        $page = $params[0];
        $op = $params[1];
        $handler =& $params[3];

        if ($page === 'premiumSubmissionHelper' && $op === 'settings') {
            $this->import('controllers.PremiumSubmissionHelperSettingsHandler');
            $handler = new PremiumSubmissionHelperSettingsHandler($this);
            return true;
        }

        return false;
    }

    /**
     * Ajoute un lien vers les paramètres dans la liste des plugins
     *
     * @param string $hookName Nom du hook
     * @param array $params Paramètres du hook
     * @return bool
     */
    public function addSettingsLink($hookName, $params)
    {
        $templateMgr = $params[0];
        $template = $params[1];

        if ($template === 'controllers/grid/plugins/plugins.tpl') {
            $plugin = $this;

            // Vérifier si c'est notre plugin
            $pluginName = $this->getName();
            $displayName = $this->getDisplayName();

            // Récupérer le contenu actuel
            $output =& $params[2];
            $pos = strpos($output, '>' . htmlspecialchars($displayName) . '</span>');

            if ($pos !== false) {
                $request = Application::get()->getRequest();
                $dispatcher = $request->getDispatcher();
                $url = $dispatcher->url(
                    $request,
                    ROUTE_PAGE,
                    null,
                    'management',
                    'settings',
                    null,
                    array('plugin' => $pluginName, 'category' => 'generic')
                );

                $link = '<a href="' . $url . '" class="action">' .
                    __('plugins.generic.premiumHelper.settings') .
                    '</a>';

                // Insérer le lien après le nom du plugin
                $output = substr_replace($output, '</span> ' . $link, $pos + 7 + strlen($displayName), 0);
            }
        }

        return false;
    }

    /**
     * Vérifie si l'utilisateur actuel est un utilisateur premium
     *
     * @param int $contextId ID du contexte
     * @return bool
     */
    public function isUserPremium($contextId)
    {
        $user = Application::get()->getRequest()->getUser();
        if (!$user) {
            return false;
        }

        // Vérifier si l'utilisateur a un rôle d'éditeur ou d'administrateur
        $userRoles = $user->getRoles($contextId);
        $allowedRoles = [
            ROLE_ID_MANAGER,
            ROLE_ID_SUB_EDITOR,
            ROLE_ID_ASSISTANT,
            ROLE_ID_SITE_ADMIN
        ];

        foreach ($userRoles as $role) {
            if (in_array($role->getRoleId(), $allowedRoles)) {
                return true;
            }
        }

        // Vérifier les abonnements ou autres critères spécifiques
        // À implémenter selon la logique métier

        return false;
    }

    /**
     * Injecte les champs dans le formulaire de soumission
     *
     * @param string $hookName Nom du hook
     * @param array $params Paramètres du hook
     * @return bool
     */
    public function injectSubmissionFormFields($hookName, $params)
    {
        $templateMgr = TemplateManager::getManager(Application::get()->getRequest());
        $output =& $params[2];
        $request = Application::get()->getRequest();
        $context = $request->getContext();

        if (!$context) {
            return false;
        }

        // Charger les paramètres
        $settings = $this->getSetting($context->getId(), 'settings');

        // Si le plugin est désactivé, ne rien faire
        if (isset($settings['enabled']) && !$settings['enabled']) {
            return false;
        }

        // Vérifier si l'utilisateur est premium
        $isPremium = $this->isUserPremium($context->getId());

        // Préparer les données pour le template
        $templateMgr->assign([
            'isPremiumUser' => $isPremium,
            'pluginUrl' => $request->getBaseUrl() . '/' . $this->getPluginPath(),
            'apiUrl' => $request->getDispatcher()->url($request, ROUTE_PAGE, null, 'premiumHelper', 'analyze'),
            'settings' => $settings
        ]);

        // Ajouter le CSS
        $templateMgr->addStyleSheet(
            'premiumHelperStyles',
            $request->getBaseUrl() . '/' . $this->getPluginPath() . '/styles/premiumHelper.css',
            array('contexts' => array('backend', 'frontend'))
        );

        // Ajouter le JavaScript
        $templateMgr->addJavaScript(
            'premiumHelperScripts',
            $request->getBaseUrl() . '/' . $this->getPluginPath() . '/js/main.js',
            array(
                'contexts' => array('backend', 'frontend'),
                'inline' => false
            )
        );

        // Rendre le template
        $output .= $templateMgr->fetch($this->getTemplateResource('premiumHelper.tpl'));

        return false;
    }

    /**
     * Configure le gestionnaire de rappel pour l'API
     *
     * @param string $hookName Nom du hook
     * @param array $params Paramètres du hook
     * @return bool
     */
    public function setupCallbackHandler($hookName, $params)
    {
        $page = $params[0];
        $op = $params[1];
        $handler =& $params[3];

        if ($page === 'premiumHelper' && $op === 'analyze') {
            $handler = new APIHandler($this);
            return true;
        }

        return false;
    }



    /**
     * Surcharge les templates du plugin
     *
     * Cette méthode permet de surcharger les templates du plugin avec des versions personnalisées.
     * Elle est appelée automatiquement par le système de template d'OJS.
     *
     * @param string $hookName Le nom du hook
     * @param array $args Les arguments du hook
     * @return bool Retourne false pour permettre aux autres hooks de s'exécuter
     */
    protected function overridePluginTemplates($hookName, $args)
    {
        $templateMgr = &$args[0];
        $template = &$args[1];

        // Ajoutez ici la logique de surcharge des templates si nécessaire

        // Retourner false pour permettre aux autres hooks de s'exécuter
        return false;
    }
}
