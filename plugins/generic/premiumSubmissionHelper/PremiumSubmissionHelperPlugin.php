<?php
declare(strict_types=1);

/**
 * @file plugins/generic/premiumSubmissionHelper/PremiumSubmissionHelper.php
 * @class PremiumSubmissionHelperPlugin
 * @ingroup plugins_generic_premiumSubmissionHelper
 * @brief Plugin d'aide à la soumission premium pour OJS
 */

namespace APP\plugins\generic\premiumSubmissionHelper;

// Framework imports
use APP\core\Application;
use APP\facades\Repo;
use APP\notification\form\ValidationForm;
use PKP\core\JSONMessage;
use PKP\core\PKPString;
use PKP\notification\NotificationManager;
use PKP\plugins\GenericPlugin;
use PKP\plugins\Hook;
use PKP\plugins\PluginRegistry;
use PKP\security\Role;
use PKP\security\authorization\PolicySet;
use PKP\security\authorization\RoleBasedHandlerOperationPolicy;
use PKP\security\authorization\UserRequiredPolicy;
use PKP\security\authorization\UserRolesRequiredPolicy;

// Plugin imports
use APP\plugins\generic\premiumSubmissionHelper\classes\{
    PremiumSubmissionHelperLog,
    PremiumSubmissionHelperLogDAO
};
use APP\plugins\generic\premiumSubmissionHelper\classes\form\SettingsForm;
use APP\plugins\generic\premiumSubmissionHelper\controllers\{
    PremiumSubmissionHelperSettingsHandler,
    grid\settings\PremiumSubmissionHelperSettingsGridHandler
};
use APP\plugins\generic\premiumSubmissionHelper\{
    scheduledTasks\PremiumSubmissionHelperScheduledTask,
    upgrade\PremiumSubmissionHelperUpgrade
};

/**
 * Classe principale du plugin Premium Helper
 *
 * Gère l'initialisation du plugin, l'injection des éléments d'interface utilisateur
 * et la configuration des routes d'API.
 *
 * @package APP\plugins\generic\premiumSubmissionHelper
 */
class PremiumSubmissionHelperPlugin extends GenericPlugin
{
    /**
     * Rôles autorisés à utiliser la fonctionnalité premium
     * @var array<int>
     */
    protected const ALLOWED_ROLES = [
        ROLE_ID_MANAGER,
        ROLE_ID_SUB_EDITOR,
        ROLE_ID_AUTHOR
    ];

    /**
     * Enregistre le plugin
     *
     * Cette méthode est appelée lors du chargement du plugin et permet d'enregistrer
     * les hooks et les gestionnaires nécessaires.
     *
     * @param string $category Catégorie du plugin
     * @param string $path Chemin du plugin
     * @param int|null $mainContextId ID du contexte principal
     *
     * @return bool True si l'enregistrement a réussi
     */
    public function register(string $category, string $path, ?int $mainContextId = null): bool
    {
        // Enregistrer le plugin même s'il n'est pas activé
        $success = parent::register($category, $path, $mainContextId);

        if ($success && $this->getEnabled()) {
            // Enregistrer les hooks
            Hook::add('TemplateManager::display', [$this, 'injectAnalysisButton']);
            Hook::add('LoadHandler', [$this, 'setupAPIHandler']);
            Hook::add('TemplateManager::include', [$this, 'addScripts']);

            // Register components that are required for the plugin to work
            $this->import('classes.PremiumSubmissionHelperLog');
            $this->import('classes.PremiumSubmissionHelperLogDAO');

            // Register the DAO for the log entries
            $logDao = new PremiumSubmissionHelperLogDAO();
            DAORegistry::registerDAO('PremiumSubmissionHelperLogDAO', $logDao);

            // Register the scheduled task
            $this->import('scheduledTasks.PremiumSubmissionHelperScheduledTask');
            Hook::add('Schema::get::premiumSubmissionHelperLog', [$this, 'addLogSchema']);
        }

        return $success;
    }

    /**
     * Récupère le nom d'affichage du plugin
     *
     * Cette méthode retourne le nom localisé du plugin qui sera affiché dans
     * l'interface d'administration d'OJS.
     *
     * @return string Le nom d'affichage du plugin
     */
    public function getDisplayName(): string
    {
        return (string) __('plugins.generic.premiumSubmissionHelper.displayName');
    }

    /**
     * Récupère la description du plugin
     *
     * Cette méthode retourne la description localisée du plugin qui sera affichée
     * dans l'interface d'administration d'OJS.
     *
     * @return string La description du plugin
     */
    public function getDescription(): string
    {
        return (string) __('plugins.generic.premiumSubmissionHelper.description');
    }

    /**
     * Injecte le bouton d'analyse dans le formulaire de soumission
     *
     * Cette méthode est appelée par le hook TemplateManager::display et ajoute un bouton
     * d'analyse dans le formulaire de soumission pour les utilisateurs premium.
     *
     * @param string $hookName Le nom du hook appelant
     * @param array $args Les arguments du hook
     *
     * @return bool Retourne false pour permettre aux autres hooks de s'exécuter
     */
    public function injectAnalysisButton(string $hookName, array $args): bool
    {
        $templateMgr = $args[0];
        $template = $args[1];

        // Ne s'applique qu'au formulaire de soumission
        if ($template !== 'submission/form/step1.tpl') {
            return false;
        }

        // Vérifier si l'utilisateur est connecté et dans un contexte valide
        $request = Application::get()->getRequest();
        $user = $request->getUser();
        $context = $request->getContext();

        if (!$user || !$context) {
            return false;
        }

        // Vérifier si l'utilisateur a les droits premium
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
     *
     * @param string $hookName Le nom du hook
     * @param array $args Les arguments du hook
     *
     * @return bool Retourne true si le gestionnaire a été configuré, false sinon
     */
    public function setupAPIHandler(string $hookName, array $args): bool
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
     *
     * @param string $hookName Le nom du hook
     * @param array $args Les arguments du hook
     *
     * @return bool Retourne false si les scripts n'ont pas pu être chargés
     */
    public function addScripts(string $hookName, array $args): bool
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
                'priority' => STYLE_SEQUENCE_LAST,
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
     * Cette méthode retourne un tableau d'actions disponibles pour ce plugin
     * dans l'interface d'administration d'OJS.
     *
     * @param \PKP\core\Request $request La requête en cours
     * @param array $actionArgs Les arguments d'action
     *
     * @return array<\PKP\linkAction\LinkAction> Tableau d'actions disponibles pour ce plugin
     */
    public function getActions(\PKP\core\Request $request, array $actionArgs): array
    {
        // Obtenir les actions existantes du parent
        $actions = parent::getActions($request, $actionArgs);

        // Vérifier que l'utilisateur est connecté
        $user = $request->getUser();
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
     * Cette méthode est le point d'entrée principal pour les actions du plugin
     * dans l'interface d'administration. Elle gère les différentes actions
     * comme la sauvegarde des paramètres.
     *
     * @param array $args Les arguments de la requête
     * @param \PKP\core\Request $request L'objet de requête
     *
     * @return \PKP\core\JSONMessage Le résultat de l'action
     *
     * @throws \Exception En cas d'erreur lors du traitement de l'action
     */
    public function manage(array $args, \PKP\core\Request $request): \PKP\core\JSONMessage
    {
        $verb = (string) $request->getUserVar('verb');

        switch ($verb) {
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
     *
     * @return bool Retourne true si le gestionnaire a été configuré, false sinon
     */
    public function setupSettingsHandler(string $hookName, array $params): bool
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
     * Vérifie si l'utilisateur actuel est un utilisateur premium
     *
     * Cette méthode vérifie si l'utilisateur connecté a les droits premium
     * en fonction de son rôle ou d'autres critères métier.
     *
     * @param int $contextId ID du contexte (journal/revue)
     *
     * @return bool Retourne true si l'utilisateur a les droits premium, false sinon
     */
    public function isUserPremium(int $contextId): bool
    {
        $request = Application::get()->getRequest();
        $user = $request->getUser();

        // Si aucun utilisateur n'est connecté, retourner false
        if (!$user) {
            return false;
        }

        // Rôles autorisés à accéder aux fonctionnalités premium
        $allowedRoles = [
            ROLE_ID_MANAGER,
            ROLE_ID_SUB_EDITOR,
            ROLE_ID_SITE_ADMIN
        ];

        // Vérifier les rôles de l'utilisateur
        $userRoles = $user->getRoles($contextId);

        foreach ($userRoles as $role) {
            if (in_array($role->getRoleId(), $allowedRoles, true)) {
                return true;
            }
        }

        // Ici, vous pouvez ajouter d'autres vérifications spécifiques
        // comme la vérification d'abonnements, de forfaits, etc.
        // Exemple : return $this->checkUserSubscription($user->getId(), $contextId);

        return false;
    }

    /**
     * Ajoute un lien vers les paramètres dans la liste des plugins
     *
     * Cette méthode ajoute un lien "Paramètres" à côté du nom du plugin
     * dans la liste des plugins de l'administration.
     *
     * @param string $hookName Nom du hook appelant
     * @param array $params Paramètres du hook (TemplateManager, template, output, etc.)
     *
     * @return bool Retourne false pour permettre aux autres hooks de s'exécuter
     */
    public function addSettingsLink(string $hookName, array $params): bool
    {
        $templateMgr = $params[0];
        $template = $params[1];
        $request = Application::get()->getRequest();

        // Vérifier que nous sommes sur la bonne page de template
        if ($template !== 'controllers/grid/plugins/plugins.tpl') {
            return false;
        }

        $pluginName = $this->getName();
        $displayName = $this->getDisplayName();
        $output =& $params[2];
        $searchString = '>' . htmlspecialchars($displayName, ENT_QUOTES, 'UTF-8') . '</span>';
        $pos = strpos($output, $searchString);

        if ($pos === false) {
            return false;
        }

        // Construire l'URL des paramètres du plugin
        $dispatcher = $request->getDispatcher();
        $url = $dispatcher->url(
            $request,
            ROUTE_PAGE,
            null,
            'management',
            'settings',
            null,
            [
                'plugin' => $pluginName,
                'category' => 'generic',
                'verb' => 'settings'
            ]
        );

        // Créer le lien HTML
        $link = sprintf(
            '<a href="%s" class="action">%s</a>',
            htmlspecialchars($url, ENT_QUOTES, 'UTF-8'),
            htmlspecialchars(__('plugins.generic.premiumHelper.settings'), ENT_QUOTES, 'UTF-8')
        );

        // Insérer le lien après le nom du plugin
        $output = substr_replace(
            $output,
            $searchString . ' ' . $link,
            $pos,
            strlen($searchString)
        );

        // Assigner l'URL du plugin pour une utilisation dans le template
        $templateMgr->assign([
            'pluginUrl' => $request->getBaseUrl() . '/' . $this->getPluginPath()
        ]);

        return false;
    }

    /**
     * Configure le gestionnaire de rappel pour l'API
     *
     * Cette méthode configure le gestionnaire de rappel pour les webhooks et autres
     * appels d'API entrants. Elle est appelée automatiquement par le routeur d'OJS.
     *
     * @param string $hookName Nom du hook appelant
     * @param array $params Paramètres du hook (page, opération, sourceFile, etc.)
     *
     * @return bool Retourne true si le gestionnaire a été configuré, false sinon
     */
    public function setupCallbackHandler(string $hookName, array $params): bool
    {
        $page = (string) $params[0];
        $op = (string) $params[1];
        $sourceFile = $params[2] ?? null;

        // Vérifier si c'est une requête pour notre gestionnaire de rappel
        if ($page !== 'premiumHelper' || $op !== 'callback') {
            return false;
        }

        try {
            // Importer et initialiser le gestionnaire d'API
            $this->import('pages.APIHandler');
            $handler = new APIHandler($this);

            // Traiter la requête
            if ($sourceFile !== null) {
                $handler->handle($op, $sourceFile);
            }

            return true;
        } catch (\Exception $e) {
            error_log('Erreur dans le gestionnaire de rappel: ' . $e->getMessage());
            return false;
        }
    }



    /**
     * Surcharge les templates du plugin
     *
     * Cette méthode permet de surcharger les templates du plugin avec des versions personnalisées.
     * Elle est appelée automatiquement par le système de template d'OJS.
     *
     * @param string $hookName Le nom du hook appelant
     * @param array $args Les arguments du hook (template, templateMgr, etc.)
     *
     * @return bool Retourne false pour permettre aux autres hooks de s'exécuter
     */
    public function overridePluginTemplates(string $hookName, array $args): bool
    {
        if (count($args) < 2) {
            return false;
        }

        $template = (string) $args[0];
        $templateMgr = $args[1];

        if (!($templateMgr instanceof \PKP\template\PKPTemplateManager)) {
            return false;
        }

        $request = Application::get()->getRequest();
        if (!$request) {
            return false;
        }

        // Surcharger les templates spécifiques
        switch ($template) {
            case 'controllers/grid/plugins/plugins.tpl':
                $dispatcher = $request->getDispatcher();
                if (!$dispatcher) {
                    break;
                }

                $pluginSettingsUrl = $dispatcher->url(
                    $request,
                    ROUTE_PAGE,
                    null,
                    'management',
                    'settings',
                    null,
                    [
                        'plugin' => $this->getName(),
                        'category' => 'generic',
                        'verb' => 'settings'
                    ]
                );
                
                $templateMgr->assign('pluginSettingsUrl', $pluginSettingsUrl);
                break;

            // Ajouter d'autres cas de surcharge de templates ici si nécessaire
        }

        return false;
    }
}
