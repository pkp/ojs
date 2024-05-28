<?php

/**
 * @file plugins/importexport/doaj/DOAJExportPlugin.php
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2003-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class DOAJExportPlugin
 *
 * @brief DOAJ export plugin
 */

namespace APP\plugins\importexport\doaj;

use APP\core\Application;
use APP\plugins\PubObjectsExportPlugin;
use APP\template\TemplateManager;
use PKP\db\DAORegistry;
use PKP\filter\FilterDAO;
use PKP\notification\PKPNotification;

define('DOAJ_XSD_URL', 'https://www.doaj.org/schemas/doajArticles.xsd');
define('DOAJ_API_DEPOSIT_OK', 201);
define('DOAJ_API_URL', 'https://doaj.org/api/');
define('DOAJ_API_URL_DEV', 'https://testdoaj.cottagelabs.com/api/');
define('DOAJ_API_OPERATION', 'articles');

class DOAJExportPlugin extends PubObjectsExportPlugin
{
    /**
     * @copydoc Plugin::getName()
     */
    public function getName()
    {
        return 'DOAJExportPlugin';
    }

    /**
     * @copydoc Plugin::getDisplayName()
     */
    public function getDisplayName()
    {
        return __('plugins.importexport.doaj.displayName');
    }

    /**
     * @copydoc Plugin::getDescription()
     */
    public function getDescription()
    {
        return __('plugins.importexport.doaj.description');
    }

    /**
     * @copydoc ImportExportPlugin::display()
     */
    public function display($args, $request)
    {
        parent::display($args, $request);
        switch (array_shift($args)) {
            case 'index':
            case '':
                $templateMgr = TemplateManager::getManager($request);
                $templateMgr->display($this->getTemplateResource('index.tpl'));
                break;
        }
    }

    /**
     * @copydoc ImportExportPlugin::getPluginSettingsPrefix()
     */
    public function getPluginSettingsPrefix()
    {
        return 'doaj';
    }

    /**
     * @copydoc PubObjectsExportPlugin::getSubmissionFilter()
     */
    public function getSubmissionFilter()
    {
        return 'article=>doaj-xml';
    }

    /**
     * @copydoc PubObjectsExportPlugin::getExportActions()
     */
    public function getExportActions($context)
    {
        $actions = [PubObjectsExportPlugin::EXPORT_ACTION_EXPORT, PubObjectsExportPlugin::EXPORT_ACTION_MARKREGISTERED ];
        if ($this->getSetting($context->getId(), 'apiKey')) {
            array_unshift($actions, PubObjectsExportPlugin::EXPORT_ACTION_DEPOSIT);
        }
        return $actions;
    }

    /**
     * @copydoc PubObjectsExportPlugin::getExportDeploymentClassName()
     */
    public function getExportDeploymentClassName()
    {
        return '\APP\plugins\importexport\doaj\DOAJExportDeployment';
    }

    /**
     * @copydoc PubObjectsExportPlugin::getSettingsFormClassName()
     */
    public function getSettingsFormClassName()
    {
        return '\APP\plugins\importexport\doaj\classes\form\DOAJSettingsForm';
    }

    /**
     * @see PubObjectsExportPlugin::depositXML()
     *
     * @param \APP\submission\Submission $objects
     * @param \PKP\context\Context $context
     * @param string $jsonString Export JSON string
     *
     * @return bool|array Whether the JSON string has been registered
     */
    public function depositXML($objects, $context, $jsonString)
    {
        $apiKey = $this->getSetting($context->getId(), 'apiKey');
        $httpClient = Application::get()->getHttpClient();
        try {
            $response = $httpClient->request(
                'POST',
                ($this->isTestMode($context) ? DOAJ_API_URL_DEV : DOAJ_API_URL) . DOAJ_API_OPERATION,
                [
                    'query' => ['api_key' => $apiKey],
                    'json' => json_decode($jsonString)
                ]
            );
        } catch (\Exception $e) {
            return [['plugins.importexport.doaj.register.error.mdsError', $e->getMessage()]];
        }
        if (($status = $response->getStatusCode()) != DOAJ_API_DEPOSIT_OK) {
            return [['plugins.importexport.doaj.register.error.mdsError', $status . ' - ' . $response->getBody()]];
        }
        // Deposit was received; set the status
        $objects->setData($this->getDepositStatusSettingName(), PubObjectsExportPlugin::EXPORT_STATUS_REGISTERED);
        $this->updateObject($objects);
        return true;
    }

    /**
     * @copydoc PubObjectsExportPlugin::executeExportAction()
     *
     * @param null|mixed $noValidation
     */
    public function executeExportAction($request, $objects, $filter, $tab, $objectsFileNamePart, $noValidation = null, $shouldRedirect = true)
    {
        $context = $request->getContext();
        $path = ['plugin', $this->getName()];
        if ($request->getUserVar(PubObjectsExportPlugin::EXPORT_ACTION_DEPOSIT)) {
            assert($filter != null);
            // Set filter for JSON
            $filter = 'article=>doaj-json';
            $resultErrors = [];
            foreach ($objects as $object) {
                // Get the JSON
                $exportJson = $this->exportJSON($object, $filter, $context);
                // Deposit the JSON
                $result = $this->depositXML($object, $context, $exportJson);
                if (is_array($result)) {
                    $resultErrors[] = $result;
                }
            }
            // send notifications
            if (empty($resultErrors)) {
                $this->_sendNotification(
                    $request->getUser(),
                    $this->getDepositSuccessNotificationMessageKey(),
                    PKPNotification::NOTIFICATION_TYPE_SUCCESS
                );
            } else {
                foreach ($resultErrors as $errors) {
                    foreach ($errors as $error) {
                        assert(is_array($error) && count($error) >= 1);
                        $this->_sendNotification(
                            $request->getUser(),
                            $error[0],
                            PKPNotification::NOTIFICATION_TYPE_ERROR,
                            ($error[1] ?? null)
                        );
                    }
                }
            }
            // redirect back to the right tab
            $request->redirect(null, null, null, $path, null, $tab);
        } else {
            return parent::executeExportAction($request, $objects, $filter, $tab, $objectsFileNamePart, $noValidation);
        }
    }

    /**
     * Get the JSON for selected objects.
     *
     * @param \APP\submission\Submission $object
     * @param string $filter
     * @param \PKP\context\Context $context
     *
     * @return string JSON variable.
     */
    public function exportJSON($object, $filter, $context)
    {
        $filterDao = DAORegistry::getDAO('FilterDAO'); /** @var FilterDAO $filterDao */
        $exportFilters = $filterDao->getObjectsByGroup($filter);
        assert(count($exportFilters) == 1); // Assert only a single serialization filter
        $exportFilter = array_shift($exportFilters);
        $exportDeployment = $this->_instantiateExportDeployment($context);
        $exportFilter->setDeployment($exportDeployment);
        return $exportFilter->execute($object, true);
    }
}
