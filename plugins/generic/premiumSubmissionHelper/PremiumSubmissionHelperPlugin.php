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
     * @param string $category Catégorie du plugin
     * @param string $path Chemin du plugin
     * @param int|null $mainContextId ID du contexte principal
     *
     * @return bool True si l'enregistrement a réussi
     */
    public function register(string $category, string $path, ?int $mainContextId = null): bool
    {
        $success = parent::register($category, $path, $mainContextId);

        if ($success && $this->getEnabled()) {
            Hook::add('TemplateManager::display', [$this, 'injectAnalysisButton']);
            Hook::add('LoadHandler', [$this, 'setupAPIHandler']);
            Hook::add('TemplateManager::include', [$this, 'addScripts']);

            $this->import('classes.PremiumSubmissionHelperLog');
            $this->import('classes.PremiumSubmissionHelperLogDAO');

            $logDao = new PremiumSubmissionHelperLogDAO();
            DAORegistry::registerDAO('PremiumSubmissionHelperLogDAO', $logDao);

            $this->import('scheduledTasks.PremiumSubmissionHelperScheduledTask');
            Hook::add('Schema::get::premiumSubmissionHelperLog', [$this, 'addLogSchema']);
        }

        return $success;
    }

    public function getDisplayName(): string
    {
        return (string) __('plugins.generic.premiumSubmissionHelper.displayName');
    }

    public function getDescription(): string
    {
        return (string) __('plugins.generic.premiumSubmissionHelper.description');
    }

    public function injectAnalysisButton(string $hookName, array $args): bool
    {
        $templateMgr = $args[0];
        $template = $args[1];

        if ($template !== 'submission/form/step1.tpl') {
            return false;
        }

        $request = Application::get()->getRequest();
        $user = $request->getUser();
        $context = $request->getContext();

        if (!$user || !$context) {
            return false;
        }

        $isPremiumUser = $this->isUserPremium($context->getId());

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

        $templateMgr->display($this->getTemplateResource('premiumSubmissionHelper.tpl'));

        return false;
    }

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

    public function addScripts(string $hookName, array $args): bool
    {
        $templateMgr = TemplateManager::getManager();
        $request = Application::get()->getRequest();
        $router = $request->getRouter();

        if (!$router) {
            return false;
        }

        $requestedPage = $router->getRequestedPage($request);
        $requestedOp = $router->getRequestedOp($request);

        if ($requestedPage !== 'submission' || $requestedOp !== 'wizard') {
            return false;
        }

        $templateMgr->addStyleSheet(
            'premiumSubmissionHelperStyles',
            $request->getBaseUrl() . '/' . $this->getPluginPath() . '/styles/premiumSubmissionHelper.css',
            ['contexts' => 'backend', 'priority' => STYLE_SEQUENCE_LAST]
        );

        $templateMgr->addJavaScript(
            'premiumSubmissionHelperScripts',
            $request->getBaseUrl() . '/' . $this->getPluginPath() . '/js/main.js',
            ['contexts' => ['frontend'], 'priority' => STYLE_SEQUENCE_LAST]
        );

        return false;
    }

    public function isUserPremium(int $contextId): bool
    {
        $request = Application::get()->getRequest();
        $user = $request->getUser();

        if (!$user) {
            return false;
        }

        $allowedRoles = [
            ROLE_ID_MANAGER,
            ROLE_ID_SUB_EDITOR,
            ROLE_ID_SITE_ADMIN
        ];

        $userRoles = $user->getRoles($contextId);

        foreach ($userRoles as $role) {
            if (in_array($role->getRoleId(), $allowedRoles, true)) {
                return true;
            }
        }

        return false;
    }
}

