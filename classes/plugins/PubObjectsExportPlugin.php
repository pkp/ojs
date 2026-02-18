<?php

/**
 * @file classes/plugins/PubObjectsExportPlugin.php
 *
 * Copyright (c) 2014-2025 Simon Fraser University
 * Copyright (c) 2003-2025 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class PubObjectsExportPlugin
 *
 * @ingroup plugins
 *
 * @brief Basis class for XML metadata export plugins
 */

namespace APP\plugins;

use APP\core\Application;
use APP\core\Request;
use APP\facades\Repo;
use APP\issue\Issue;
use APP\journal\Journal;
use APP\journal\JournalDAO;
use APP\notification\NotificationManager;
use APP\publication\Publication;
use APP\submission\Submission;
use APP\template\TemplateManager;
use PKP\context\Context;
use PKP\core\EntityDAO;
use PKP\core\JSONMessage;
use PKP\db\DAO;
use PKP\db\DAORegistry;
use PKP\db\SchemaDAO;
use PKP\file\FileManager;
use PKP\filter\FilterDAO;
use PKP\galley\Galley;
use PKP\linkAction\LinkAction;
use PKP\linkAction\request\AjaxModal;
use PKP\linkAction\request\NullAction;
use PKP\notification\Notification;
use PKP\plugins\Hook;
use PKP\plugins\importexport\native\filter\NativeExportFilter;
use PKP\plugins\importexport\PKPImportExportDeployment;
use PKP\plugins\ImportExportPlugin;
use PKP\plugins\PluginRegistry;
use PKP\publication\PKPPublication;
use PKP\submission\PKPSubmission;
use PKP\user\User;

abstract class PubObjectsExportPlugin extends ImportExportPlugin
{
    // The statuses
    public const EXPORT_STATUS_ANY = '';
    // A help const, to get all depositable objects (with status = not registered, stale, error)
    public const EXPORT_STATUS_DEPOSITABLE = 'depositable';
    public const EXPORT_STATUS_NOT_DEPOSITED = 'notDeposited';
    public const EXPORT_STATUS_MARKEDREGISTERED = 'markedRegistered';
    public const EXPORT_STATUS_REGISTERED = 'registered';
    public const EXPORT_STATUS_SUBMITTED = 'submitted';
    public const EXPORT_STATUS_STALE = 'stale';
    public const EXPORT_STATUS_ERROR = 'error';

    // The actions
    public const EXPORT_ACTION_EXPORT = 'export';
    public const EXPORT_ACTION_MARKREGISTERED = 'markRegistered';
    public const EXPORT_ACTION_DEPOSIT = 'deposit';
    // Configuration errors.
    public const EXPORT_CONFIG_ERROR_SETTINGS = 2;

    /** @var PubObjectCache */
    public $_cache;

    /**
     * Get the plugin cache
     *
     * @return PubObjectCache
     */
    public function getCache()
    {
        if (!$this->_cache instanceof PubObjectCache) {
            // Instantiate the cache.
            $this->_cache = new PubObjectCache();
        }
        return $this->_cache;
    }

    /**
     * @copydoc Plugin::register()
     *
     * @param null|mixed $mainContextId
     */
    public function register($category, $path, $mainContextId = null)
    {
        if (!parent::register($category, $path, $mainContextId)) {
            return false;
        }

        if (Application::isUnderMaintenance()) {
            return true;
        }

        $this->addLocaleData();

        foreach ($this->_getDAOs() as $dao) {
            if ($dao instanceof SchemaDAO) {
                Hook::add('Schema::get::' . $dao->schemaName, $this->addToSchema(...));
            } elseif ($dao instanceof EntityDAO) {
                Hook::add('Schema::get::' . $dao->schema, $this->addToSchema(...));
            } else {
                Hook::add(strtolower(get_class($dao)) . '::getAdditionalFieldNames', $this->getAdditionalFieldNames(...));
            }
        }
        return true;
    }

    /**
     * @copydoc Plugin::manage()
     */
    public function manage($args, $request)
    {
        $user = $request->getUser();
        $router = $request->getRouter();
        $context = $router->getContext($request);

        $form = $this->_instantiateSettingsForm($context);
        $notificationManager = new NotificationManager();
        switch ($request->getUserVar('verb')) {
            case 'save':
                $form->readInputData();
                if ($form->validate()) {
                    $form->execute();
                    $notificationManager->createTrivialNotification($user->getId(), Notification::NOTIFICATION_TYPE_SUCCESS);
                    return new JSONMessage(true);
                } else {
                    return new JSONMessage(true, $form->fetch($request));
                }
                // no break
            case 'index':
                $form->initData();
                return new JSONMessage(true, $form->fetch($request));
            case 'statusMessage':
                $statusMessage = $this->getStatusMessage($request);
                if (!empty($statusMessage)) {
                    $templateMgr = TemplateManager::getManager($request);
                    $templateMgr->assign([
                        'statusMessage' => htmlentities($statusMessage),
                    ]);
                    return new JSONMessage(true, $templateMgr->fetch($this->getTemplateResource('statusMessage.tpl')));
                }
        }
        return parent::manage($args, $request);
    }

    /**
     * @copydoc ImportExportPlugin::display()
     */
    public function display($args, $request)
    {
        parent::display($args, $request);

        $context = $request->getContext();
        switch (array_shift($args)) {
            case 'index':
            case '':
                // Check for configuration errors:
                $configurationErrors = [];
                // missing plugin settings
                /** @var PubObjectsExportSettingsForm $form */
                $form = $this->_instantiateSettingsForm($context);
                foreach ($form->getFormFields() as $fieldName => $fieldType) {
                    if ($form->isOptional($fieldName)) {
                        continue;
                    }
                    $pluginSetting = $this->getSetting($context->getId(), $fieldName);
                    if (empty($pluginSetting)) {
                        $configurationErrors[] = PubObjectsExportPlugin::EXPORT_CONFIG_ERROR_SETTINGS;
                        break;
                    }
                }

                // Add link actions
                $actions = $this->getExportActions($context);
                $actionNames = array_intersect_key($this->getExportActionNames(), array_flip($actions));
                $linkActions = [];
                foreach ($actionNames as $action => $actionName) {
                    $linkActions[] = new LinkAction($action, new NullAction(), $actionName);
                }
                $templateMgr = TemplateManager::getManager($request);
                $templateMgr->registerClass(PubObjectsExportPlugin::class, PubObjectsExportPlugin::class);
                $templateMgr->assign([
                    'plugin' => $this,
                    'actionNames' => $actionNames,
                    'configurationErrors' => $configurationErrors,
                    'doiVersioning' => $context->getData(Context::SETTING_DOI_VERSIONING) ?? false,
                ]);
                break;
            case 'exportSubmissions':
            case 'exportPublications':
            case 'exportIssues':
            case 'exportRepresentations':
                $this->prepareAndExportPubObjects($request, $context);
        }
    }

    /**
     * Gathers relevant pub objects and runs export action
     *
     * @param Request $request
     * @param Journal $context
     * @param array $args Optional args for passing in submissionIds from external API calls
     */
    public function prepareAndExportPubObjects($request, $context, $args = [])
    {
        $selectedSubmissions = (array) $request->getUserVar('selectedSubmissions');
        $selectedPublications = (array) $request->getUserVar('selectedPublications');
        $selectedIssues = (array) $request->getUserVar('selectedIssues');
        $selectedRepresentations = (array) $request->getUserVar('selectedRepresentations');
        $tab = (string) $request->getUserVar('tab');
        $noValidation = $request->getUserVar('validation') ? false : true;

        if (!empty($args['submissionIds'])) {
            $selectedSubmissions = (array) $args['submissionIds'];
        }
        if (!empty($args['issueIds'])) {
            $selectedIssues = (array) $args['issueIds'];
        }
        if (empty($selectedSubmissions) && empty($selectedPublications) && empty($selectedIssues) && empty($selectedRepresentations)) {
            throw new \Exception(__('plugins.importexport.common.error.noObjectsSelected'));
        }
        if (!empty($selectedSubmissions)) {
            $objects = $this->getPublishedSubmissions($selectedSubmissions, $context);
            $filter = $this->getSubmissionFilter();
            $objectsFileNamePart = 'articles';
        } elseif (!empty($selectedPublications)) {
            $objects = $this->getPublishedPublications($selectedPublications, $context);
            $filter = $this->getPublicationFilter();
            $objectsFileNamePart = 'publications';
        } elseif (!empty($selectedIssues)) {
            $objects = $this->getPublishedIssues($selectedIssues, $context);
            $filter = $this->getIssueFilter();
            $objectsFileNamePart = 'issues';
        } elseif (!empty($selectedRepresentations)) {
            $objects = $this->getArticleGalleys($selectedRepresentations, $context);
            $filter = $this->getRepresentationFilter();
            $objectsFileNamePart = 'galleys';
        }

        // Execute export action
        $this->executeExportAction($request, $objects, $filter, $tab, $objectsFileNamePart, $noValidation);
    }

    /**
     * Execute export action.
     *
     * @param Request $request
     * @param array $objects Array of objects to be exported
     * @param string $filter Filter to use
     * @param string $tab Tab to return to
     * @param string $objectsFileNamePart Export file name part for this kind of objects
     * @param bool $noValidation If set to true no XML validation will be done
     * @param bool $shouldRedirect If set to true, will redirect to `$tab`. Should be true if executed within an ImportExportPlugin.
     */
    public function executeExportAction($request, $objects, $filter, $tab, $objectsFileNamePart, $noValidation = null, $shouldRedirect = true)
    {
        $context = $request->getContext();
        $path = ['plugin', $this->getName()];
        if ($this->_checkForExportAction(PubObjectsExportPlugin::EXPORT_ACTION_EXPORT)) {
            $onlyValidateExport = ($request->getUserVar('onlyValidateExport')) ? true : false;
            if ($onlyValidateExport) {
                $noValidation = false;
            }

            // Get the XML
            $exportXml = $this->exportXML($objects, $filter, $context, $noValidation);

            if ($onlyValidateExport) {
                if (isset($exportXml)) {
                    $this->_sendNotification(
                        $request->getUser(),
                        'plugins.importexport.common.validation.success',
                        Notification::NOTIFICATION_TYPE_SUCCESS
                    );
                } else {
                    $this->_sendNotification(
                        $request->getUser(),
                        'plugins.importexport.common.validation.fail',
                        Notification::NOTIFICATION_TYPE_ERROR
                    );
                }

                if ($shouldRedirect) {
                    $request->redirect(null, null, null, $path, null, $tab);
                }
            } else {
                $fileManager = new FileManager();
                $exportFileName = $this->getExportFileName($this->getExportPath(), $objectsFileNamePart, $context, '.xml');
                $fileManager->writeFile($exportFileName, $exportXml);
                $fileManager->downloadByPath($exportFileName);
                $fileManager->deleteByPath($exportFileName);
            }
        } elseif ($this->_checkForExportAction(PubObjectsExportPlugin::EXPORT_ACTION_DEPOSIT)) {
            // Get the XML
            $exportXml = $this->exportXML($objects, $filter, $context, $noValidation);
            // Write the XML to a file.
            // export file name example: crossref-20160723-160036-articles-1.xml
            $fileManager = new FileManager();
            $exportFileName = $this->getExportFileName($this->getExportPath(), $objectsFileNamePart, $context, '.xml');
            $fileManager->writeFile($exportFileName, $exportXml);
            // Deposit the XML file.
            $result = $this->depositXML($objects, $context, $exportFileName);
            // send notifications
            if ($result === true) {
                $this->_sendNotification(
                    $request->getUser(),
                    $this->getDepositSuccessNotificationMessageKey(),
                    Notification::NOTIFICATION_TYPE_SUCCESS
                );
            } else {
                if (is_array($result)) {
                    foreach ($result as $error) {
                        $this->_sendNotification(
                            $request->getUser(),
                            $error[0],
                            Notification::NOTIFICATION_TYPE_ERROR,
                            ($error[1] ?? null)
                        );
                    }
                }
            }
            // Remove all temporary files.
            $fileManager->deleteByPath($exportFileName);
            if ($shouldRedirect) {
                // redirect back to the right tab
                $request->redirect(null, null, null, $path, null, $tab);
            }
        } elseif ($this->_checkForExportAction(PubObjectsExportPlugin::EXPORT_ACTION_MARKREGISTERED)) {
            $this->markRegistered($objects);
            if ($shouldRedirect) {
                // redirect back to the right tab
                $request->redirect(null, null, null, $path, null, $tab);
            }
        } else {
            throw new \Symfony\Component\HttpKernel\Exception\NotFoundHttpException();
        }
    }

    /**
     * Get the locale key used in the notification for
     * the successful deposit.
     */
    public function getDepositSuccessNotificationMessageKey()
    {
        return 'plugins.importexport.common.register.success';
    }

    /**
     * Deposit XML document.
     * This must be implemented in the subclasses, if the action is supported.
     *
     * @param mixed $objects Array of or single published submission, publication, issue or galley
     * @param Journal $context
     * @param string $filename Export XML filename
     *
     * @return bool|array Whether the XML document has been registered
     */
    abstract public function depositXML($objects, $context, $filename);


    /**
     * Get the submission filter.
     *
     * @return string|null
     */
    public function getSubmissionFilter()
    {
        return null;
    }

    /**
     * Get the publication filter.
     */
    public function getPublicationFilter(): ?string
    {
        return null;
    }

    /**
     * Get the issue filter.
     *
     * @return string|null
     */
    public function getIssueFilter()
    {
        return null;
    }

    /**
     * Get the representation filter.
     *
     * @return string|null
     */
    public function getRepresentationFilter()
    {
        return null;
    }

    /**
     * Get action names.
     *
     * @return array (string action => string text)
     */
    public function getExportActionNames(): array
    {
        return [
            PubObjectsExportPlugin::EXPORT_ACTION_DEPOSIT => __('plugins.importexport.common.action.register'),
            PubObjectsExportPlugin::EXPORT_ACTION_EXPORT => __('plugins.importexport.common.action.export'),
            PubObjectsExportPlugin::EXPORT_ACTION_MARKREGISTERED => __('plugins.importexport.common.action.markRegistered'),
        ];
    }

    /**
     * Get actions.
     *
     * @param Journal $context
     *
     * @return array
     */
    public function getExportActions($context)
    {
        $actions = [PubObjectsExportPlugin::EXPORT_ACTION_EXPORT, PubObjectsExportPlugin::EXPORT_ACTION_MARKREGISTERED];
        if ($this->getSetting($context->getId(), 'username') && $this->getSetting($context->getId(), 'password')) {
            array_unshift($actions, PubObjectsExportPlugin::EXPORT_ACTION_DEPOSIT);
        }
        return $actions;
    }

    /**
     * Get status names for the filter search option.
     */
    public function getStatusNames(): array
    {
        return [
            PubObjectsExportPlugin::EXPORT_STATUS_ANY => __('plugins.importexport.common.status.any'),
            PubObjectsExportPlugin::EXPORT_STATUS_NOT_DEPOSITED => __('plugins.importexport.common.status.notDeposited'),
            PubObjectsExportPlugin::EXPORT_STATUS_MARKEDREGISTERED => __('plugins.importexport.common.status.markedRegistered'),
            PubObjectsExportPlugin::EXPORT_STATUS_REGISTERED => __('plugins.importexport.common.status.registered'),
            PubObjectsExportPlugin::EXPORT_STATUS_SUBMITTED => __('plugins.importexport.common.status.submitted'),
            PubObjectsExportPlugin::EXPORT_STATUS_STALE => __('plugins.importexport.common.status.stale'),
            PubObjectsExportPlugin::EXPORT_STATUS_ERROR => __('plugins.importexport.common.status.error'),
        ];
    }

    /**
     * Get status actions for the display to the user,
     * i.e. a link to a modal where error messages are dispalyed,
     * or link to a web site with more information about the status.
     *
     * @return array (string status => link)
     */
    public function getStatusActions(Submission|Publication $pubObject): array
    {
        $request = Application::get()->getRequest();
        $dispatcher = $request->getDispatcher();
        $objectIdName = match (true) {
            $pubObject instanceof Submission => 'articleId',
            $pubObject instanceof Publication => 'publicationId',
            // Not considering issues and galleys, because they are maybe not used at all
        };
        return [
            PubObjectsExportPlugin::EXPORT_STATUS_ERROR =>
                new LinkAction(
                    'failureMessage',
                    new AjaxModal(
                        $dispatcher->url(
                            $request,
                            Application::ROUTE_COMPONENT,
                            null,
                            'grid.settings.plugins.settingsPluginGridHandler',
                            'manage',
                            null,
                            ['plugin' => $this->getName(), 'category' => 'importexport', 'verb' => 'statusMessage', $objectIdName => $pubObject->getId()]
                        ),
                        __('plugins.importexport.common.status.error'),
                        'failureMessage'
                    ),
                    __('plugins.importexport.common.status.failed')
                )
        ];
    }

    /**
     * Get detailed message of the object status i.e. failure messages.
     * Parameters needed have to be in the request object.
     *
     * @return string Preformatted text that will be displayed in a div element in the modal
     */
    public function getStatusMessage(Request $request): ?string
    {
        $articleId = (int) $request->getUserVar('articleId');
        $publicationId = (int) $request->getUserVar('publicationId');
        if ($articleId) {
            $object = Repo::submission()->get($articleId);
        } elseif ($publicationId) {
            $object = Repo::publication()->get($publicationId);
        }
        // Not considering issues and galleys, because they are maybe not used at all

        return $object->getData($this->getFailedMsgSettingName());
    }

    /**
     * Return the name of the plugin's deployment class.
     *
     * @return string
     */
    abstract public function getExportDeploymentClassName();

    /**
     * Get the XML for selected objects.
     *
     * @param mixed $objects Array of or single published submission, publication, issue or galley
     * @param string $filter
     * @param Journal $context
     * @param bool $noValidation If set to true no XML validation will be done
     * @param null|mixed $outputErrors
     *
     * @return string XML document.
     */
    public function exportXML($objects, $filter, $context, $noValidation = null, &$outputErrors = null)
    {
        $filterDao = DAORegistry::getDAO('FilterDAO'); /** @var FilterDAO $filterDao */
        $exportFilters = $filterDao->getObjectsByGroup($filter);
        $exportFilter = array_shift($exportFilters); /** @var NativeExportFilter $exportFilter */
        $exportDeployment = $this->_instantiateExportDeployment($context);
        $exportFilter->setDeployment($exportDeployment);
        if ($noValidation) {
            $exportFilter->setNoValidation($noValidation);
        }
        libxml_use_internal_errors(true);
        $exportXml = $exportFilter->execute($objects, true);
        $xml = $exportXml->saveXml();
        $errors = array_filter(libxml_get_errors(), function ($a) {
            return $a->level == LIBXML_ERR_ERROR || $a->level == LIBXML_ERR_FATAL;
        });
        if (!empty($errors)) {
            if ($outputErrors === null) {
                $this->displayXMLValidationErrors($errors, $xml);
            } else {
                $outputErrors = $errors;
            }
        }
        return $xml;
    }

    /**
     * Mark selected submissions, publications or issues as registered.
     *
     * @param array $objects Array of published submissions, publications, issues or galleys
     */
    public function markRegistered($objects)
    {
        foreach ($objects as $object) {
            $this->updateStatus($object, PubObjectsExportPlugin::EXPORT_STATUS_MARKEDREGISTERED);
        }
    }

    /**
     * Mark publication as stale.
     */
    public function markStale(Submission|Publication $pubObject): void
    {
        $this->updateStatus($pubObject, PubObjectsExportPlugin::EXPORT_STATUS_STALE);
    }

    /**
     * Gets a repo for a pub object.
     *
     * @return mixed Returns either a repo for the given pub object
     */
    protected function getObjectRepo(Submission|Publication|Issue|Galley $object): mixed
    {
        return match (true) {
            $object instanceof Submission => Repo::submission(),
            $object instanceof Publication => Repo::publication(),
            $object instanceof Issue => Repo::issue(),
            $object instanceof Galley => Repo::galley(),
        };
    }

    /**
     * Update the given object.
     */
    protected function updateObject(Submission|Publication|Issue|Galley $object, ?array $editParams = [])
    {
        // Register a hook for the required additional
        // object fields. We do this on a temporary
        // basis as the hook adds a performance overhead
        // and the field will "stealthily" survive even
        // when the DAO does not know about it.
        $this->getObjectRepo($object)->edit($object, $editParams);
    }

    /**
     * Update status based on if deposits and registration have been successful
     */
    public function updateStatus(Submission|Publication $object, string $status, ?string $failedMsg = null)
    {
        $statusParams = [
            $this->getDepositStatusSettingName() => $status,
            $this->getFailedMsgSettingName() => $failedMsg,
        ];
        $this->updateObject($object, $statusParams);
    }

    /**
     * Add properties for this type of public identifier to the entity's list for
     * storage in the database.
     * This is used for non-SchemaDAO-backed entities only.
     *
     * @see PubObjectsExportPlugin::addToSchema()
     *
     * @param string $hookName
     * @param DAO $dao
     * @param array $additionalFields
     *
     * @return false
     */
    public function getAdditionalFieldNames($hookName, $dao, &$additionalFields)
    {
        foreach ($this->getObjectAdditionalSettings() as $fieldName) {
            $additionalFields[] = $fieldName;
        }
        return false;
    }

    /**
     * Add properties for this type of public identifier to the entity's list for
     * storage in the database.
     * This is used for SchemaDAO-backed entities only.
     *
     * @see PKPPubIdPlugin::getAdditionalFieldNames()
     *
     * @param string $hookName `Schema::get::publication`
     * @param array $params
     */
    public function addToSchema($hookName, $params)
    {
        $schema = &$params[0];
        foreach ($this->getObjectAdditionalSettings() as $fieldName) {
            $schema->properties->{$fieldName} = (object) [
                'type' => 'string',
                'apiSummary' => true,
                'validation' => ['nullable'],
            ];
        }

        return false;
    }

    /**
     * Get a list of additional setting names that should be stored with the objects.
     */
    public function getObjectAdditionalSettings(): array
    {
        return [$this->getDepositStatusSettingName(), $this->getFailedMsgSettingName()];
    }

    /**
     * Retrieve all published submissions that should be (re)deposited:
     * those not yet registered, stale, or with status error.
     */
    public function getAllDepositableArticles(Journal $context): array
    {
        $articles = Repo::submission()->dao->getExportable(
            $context->getId(),
            null,
            null,
            null,
            null,
            $this->getDepositStatusSettingName(),
            PubObjectsExportPlugin::EXPORT_STATUS_DEPOSITABLE,
            null
        );
        return $articles->toArray();
    }

    /**
     * Retrieve all published publications that should be (re)deposited:
     * those not yet registered, stale, or with status error.
     */
    public function getAllDepositablePublications(Journal $context): array
    {
        $publications = Repo::publication()->dao->getExportable(
            $context->getId(),
            null,
            null,
            null,
            null,
            $this->getDepositStatusSettingName(),
            PubObjectsExportPlugin::EXPORT_STATUS_DEPOSITABLE,
            null
        );
        return $publications->toArray();
    }

    /**
     * Check whether we are in test mode.
     *
     * @param Journal $context
     *
     * @return bool
     */
    public function isTestMode($context)
    {
        return ($this->getSetting($context->getId(), 'testMode') == 1);
    }

    /**
     * Get deposit status setting name.
     *
     * @return string
     */
    public function getDepositStatusSettingName()
    {
        return $this->getPluginSettingsPrefix() . '::status';
    }

    /**
     * Get request failed message setting name.
     */
    public function getFailedMsgSettingName(): string
    {
        return $this->getPluginSettingsPrefix() . '_failedMsg';
    }

    /**
     * @copydoc PKPImportExportPlugin::usage
     */
    public function usage($scriptName)
    {
        echo __(
            'plugins.importexport.' . $this->getPluginSettingsPrefix() . '.cliUsage',
            [
                'scriptName' => $scriptName,
                'pluginName' => $this->getName()
            ]
        ) . "\n";
    }

    /**
     * @copydoc PKPImportExportPlugin::executeCLI()
     */
    public function executeCLI($scriptName, &$args)
    {
        $command = array_shift($args);
        if (!in_array($command, ['export', 'register'])) {
            $this->usage($scriptName);
            return;
        }

        $outputFile = $command == 'export' ? array_shift($args) : null;
        $contextPath = array_shift($args);
        $objectType = array_shift($args);

        $contextDao = DAORegistry::getDAO('JournalDAO'); /** @var JournalDAO $contextDao */
        $context = $contextDao->getByPath($contextPath);
        if (!$context) {
            if ($contextPath != '') {
                echo __('plugins.importexport.common.cliError') . "\n";
                echo __('plugins.importexport.common.error.unknownContext', ['contextPath' => $contextPath]) . "\n\n";
            }
            $this->usage($scriptName);
            return;
        }

        PluginRegistry::loadCategory('pubIds', true, $context->getId());

        if ($outputFile) {
            if ($this->isRelativePath($outputFile)) {
                $outputFile = PWD . '/' . $outputFile;
            }
            $outputDir = dirname($outputFile);
            if (!is_writable($outputDir) || (file_exists($outputFile) && !is_writable($outputFile))) {
                echo __('plugins.importexport.common.cliError') . "\n";
                echo __('plugins.importexport.common.export.error.outputFileNotWritable', ['param' => $outputFile]) . "\n\n";
                $this->usage($scriptName);
                return;
            }
        }

        switch ($objectType) {
            case 'articles':
                $objects = $this->getPublishedSubmissions($args, $context);
                $filter = $this->getSubmissionFilter();
                $objectsFileNamePart = 'articles';
                break;
            case 'issues':
                $objects = $this->getPublishedIssues($args, $context);
                $filter = $this->getIssueFilter();
                $objectsFileNamePart = 'issues';
                break;
            case 'galleys':
                $objects = $this->getArticleGalleys($args, $context);
                $filter = $this->getRepresentationFilter();
                $objectsFileNamePart = 'galleys';
                break;
            default:
                $this->usage($scriptName);
                return;
        }
        if (empty($objects)) {
            echo __('plugins.importexport.common.cliError') . "\n";
            echo __('plugins.importexport.common.error.unknownObjects') . "\n\n";
            $this->usage($scriptName);
            return;
        }
        if (!$filter) {
            $this->usage($scriptName);
            return;
        }

        $this->executeCLICommand($scriptName, $command, $context, $outputFile, $objects, $filter, $objectsFileNamePart);
        return;
    }

    /**
     * Execute the CLI command
     *
     * @param string $scriptName The name of the command-line script (displayed as usage info)
     * @param string $command (export or register)
     * @param Journal $context
     * @param string $outputFile Path to the file where the exported XML should be saved
     * @param array $objects Objects to be exported or registered
     * @param string $filter Filter to use
     * @param string $objectsFileNamePart Export file name part for this kind of objects
     */
    public function executeCLICommand($scriptName, $command, $context, $outputFile, $objects, $filter, $objectsFileNamePart)
    {
        $exportXml = $this->exportXML($objects, $filter, $context);
        if ($command == 'export' && $outputFile) {
            file_put_contents($outputFile, $exportXml);
        }

        if ($command == 'register') {
            $fileManager = new FileManager();
            $exportFileName = $this->getExportFileName($this->getExportPath(), $objectsFileNamePart, $context, '.xml');
            $fileManager->writeFile($exportFileName, $exportXml);
            $result = $this->depositXML($objects, $context, $exportFileName);
            if ($result === true) {
                echo __('plugins.importexport.common.register.success') . "\n";
            } else {
                echo __('plugins.importexport.common.cliError') . "\n";
                if (is_array($result)) {
                    foreach ($result as $error) {
                        $errorMessage = __($error[0], ['param' => ($error[1] ?? null)]);
                        echo "*** {$errorMessage}\n";
                    }
                    echo "\n";
                } else {
                    echo __('plugins.importexport.common.register.error.mdsError', ['param' => ' - ']) . "\n\n";
                }
                $this->usage($scriptName);
            }
            $fileManager->deleteByPath($exportFileName);
        }
    }

    /**
     * Get published submissions from submission IDs.
     *
     * @param array $submissionIds
     * @param Journal $context
     *
     * @return array
     */
    public function getPublishedSubmissions($submissionIds, $context)
    {
        $allSubmissionIds = Repo::submission()
            ->getCollector()
            ->filterByContextIds([$context->getId()])
            ->filterByStatus([PKPSubmission::STATUS_PUBLISHED])
            ->getIds()
            ->toArray();
        $validSubmissionIds = array_intersect($allSubmissionIds, $submissionIds);
        return array_map(function ($submissionId) {
            return Repo::submission()->get($submissionId);
        }, $validSubmissionIds);
    }

    /**
     * Get published publications from publication IDs.
     */
    public function getPublishedPublications(array $publicationIds, Journal $context): array
    {
        $allPublicationIds = Repo::publication()
            ->getCollector()
            ->filterByContextIds([$context->getId()])
            ->filterByStatus([PKPPublication::STATUS_PUBLISHED])
            ->getIds()
            ->toArray();
        $validPublicationIds = array_intersect($allPublicationIds, $publicationIds);
        return array_map(function ($publicationId) {
            return Repo::publication()->get($publicationId);
        }, $validPublicationIds);
    }

    /**
     * Get published issues from issue IDs.
     *
     * @param array $issueIds
     * @param Journal $context
     *
     * @return array
     */
    public function getPublishedIssues($issueIds, $context)
    {
        return Repo::issue()
            ->getCollector()
            ->filterByContextIds([$context->getId()])
            ->filterByIssueIds($issueIds)
            ->filterByPublished(true)
            ->getMany()
            ->toArray();
    }

    /**
     * Get article galleys from gallley IDs.
     *
     * @param array $galleyIds
     * @param Journal $context
     *
     * @return array
     */
    public function getArticleGalleys($galleyIds, $context)
    {
        $allGalleyIds = Repo::galley()
            ->getCollector()
            ->filterByContextIds([$context->getId()])
            ->getIds()
            ->toArray();
        $validGalleyIds = array_intersect($allGalleyIds, $galleyIds);
        return array_map(function ($galleyId) {
            return Repo::submission()->get($galleyId);
        }, $validGalleyIds);
    }

    /**
     * Add a notification.
     *
     * @param User $user
     * @param string $message An i18n key.
     * @param int $notificationType One of the NOTIFICATION_TYPE_* constants.
     * @param string $param An additional parameter for the message.
     */
    public function _sendNotification($user, $message, $notificationType, $param = null)
    {
        static $notificationManager = null;
        $notificationManager ??= new NotificationManager();
        $params = is_null($param) ? [] : ['param' => $param];
        $notificationManager->createTrivialNotification(
            $user->getId(),
            $notificationType,
            ['contents' => __($message, $params)]
        );
    }

    /**
     * Instantiate the export deployment.
     *
     * @param Journal $context
     *
     * @return PKPImportExportDeployment
     */
    public function _instantiateExportDeployment($context)
    {
        $exportDeploymentClassName = $this->getExportDeploymentClassName();
        $exportDeployment = new $exportDeploymentClassName($context, $this);
        return $exportDeployment;
    }

    /**
     * Instantiate the settings form.
     *
     * @param Journal $context
     *
     * @return \PKP\form\Form
     */
    public function _instantiateSettingsForm($context)
    {
        $settingsFormClassName = $this->getSettingsFormClassName();
        return new $settingsFormClassName($this, $context->getId());
    }

    /**
     * Return the full namespace path the plugin's settings form class.
     */
    abstract public function getSettingsFormClassName(): string;

    /**
     * Get the DAOs for objects that need to be augmented with additional settings.
     *
     * @return array
     */
    protected function _getDAOs()
    {
        return [
            Repo::publication()->dao,
            Repo::submission()->dao,
            Application::getRepresentationDAO(),
            Repo::issue()->dao,
            Repo::submissionFile()->dao
        ];
    }

    /**
     * Checks for export action type as set user var and as action passed from API call
     *
     * @param string $exportAction Action to check for
     *
     * @return bool
     */
    protected function _checkForExportAction($exportAction)
    {
        $request = $this->getRequest();
        if ($request->getUserVar($exportAction)) {
            return true;
        } elseif ($request->getUserVar('action') == $exportAction) {
            return true;
        }

        return false;
    }
}
