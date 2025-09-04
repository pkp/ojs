<?php

/**
 * @file plugins/generic/doaj/DOAJExportPlugin.php
 *
 * Copyright (c) 2014-2025 Simon Fraser University
 * Copyright (c) 2003-2025 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class DOAJExportPlugin
 *
 * @brief DOAJ export plugin
 */

namespace APP\plugins\generic\doaj;

use APP\core\Application;
use APP\facades\Repo;
use APP\plugins\PubObjectsExportPlugin;
use APP\publication\Publication;
use APP\submission\Submission;
use APP\template\TemplateManager;
use PKP\context\Context;
use PKP\db\DAORegistry;
use PKP\filter\FilterDAO;
use PKP\notification\Notification;

define('DOAJ_XSD_URL', 'https://www.doaj.org/schemas/doajArticles.xsd');
define('DOAJ_API_DEPOSIT_OK', 201);
//define('DOAJ_API_URL', 'https://doaj.org/api/');
define('DOAJ_API_URL', 'https://static.doaj.cottagelabs.com/api/');
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
     * @copydoc PubObjectsExportPlugin::getPublicationFilter()
     */
    public function getPublicationFilter(): ?string
    {
        return 'publication=>doaj-xml';
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
        return '\APP\plugins\generic\doaj\DOAJExportDeployment';
    }

    /**
     * @copydoc PubObjectsExportPlugin::getSettingsFormClassName()
     */
    public function getSettingsFormClassName()
    {
        return '\APP\plugins\generic\doaj\classes\form\DOAJSettingsForm';
    }

    /**
     * @see PubObjectsExportPlugin::depositXML()
     *
     * @param Submission|Publication $objects
     * @param Context $context
     * @param string $jsonString Export JSON string
     *
     * @return bool|array Whether the JSON string has been registered
     */
    public function depositXML($objects, $context, $jsonString)
    {
        $apiKey = $this->getSetting($context->getId(), 'apiKey');
        $httpClient = Application::get()->getHttpClient();
        $jsonDecoded = json_decode($jsonString, true);

        $doajObjectId = $objects->getData($this->getIdSettingName());
        if (empty($doajObjectId) && $context->getData(Context::SETTING_DOI_VERSIONING)) {
            $doajObjectId = Repo::publication()->getMinorVersionsSettingValues($objects->getData('submissionId'), $objects->getData('versionStage'), $objects->getData('versionMajor'), $this->getIdSettingName())->first();
        }
        $doajDoi = $objects->getData($this->getDoiSettingName());
        $doajUrl = $objects->getData($this->getUrlSettingName());

        $identifierTypes = array_column($jsonDecoded['bibjson']['identifier'], 'id', 'type');
        $objectDoi = array_key_exists('doi', $identifierTypes) ? $identifierTypes['doi'] : null;
        $objectUrl = $jsonDecoded['bibjson']['link'][0]['url'];

        $deleted = false;
        if (!empty($doajObjectId) && ($doajDoi != $objectDoi || $doajUrl != $objectUrl)) {
            try {
                $response = $httpClient->request(
                    'DELETE',
                    DOAJ_API_URL . DOAJ_API_OPERATION . '/' . $doajObjectId,
                    [
                        'query' => ['api_key' => $apiKey, 'article_id' => $doajObjectId],
                    ]
                );
            } catch (\GuzzleHttp\Exception\RequestException $e) {
                $returnMessage = $e->getMessage();
                if ($e->hasResponse()) {
                    $returnMessage = $e->getResponse()->getBody() . ' (' . $e->getResponse()->getStatusCode() . ' ' . $e->getResponse()->getReasonPhrase() . ')';
                }
                $this->updateStatus($objects, PubObjectsExportPlugin::EXPORT_STATUS_ERROR, $returnMessage);
                return [['plugins.importexport.doaj.delete.error.mdsError', $returnMessage]];
            }
            $responseBody = json_decode($response->getBody());

            $objects->setData($this->getIdSettingName(), null);
            $objects->setData($this->getDoiSettingName(), null);
            $objects->setData($this->getUrlSettingName(), null);

            $deleted = true;

        }

        if ($deleted) {
            sleep(60); // TO-DO: to be removed once DOAJ explains why the immediate registration does not work
        }
        try {
            $response = $httpClient->request(
                'POST',
                DOAJ_API_URL . DOAJ_API_OPERATION,
                [
                    'query' => ['api_key' => $apiKey],
                    'json' => json_decode($jsonString)
                ]
            );
        } catch (\GuzzleHttp\Exception\RequestException $e) {
            $returnMessage = $e->getMessage();
            if ($e->hasResponse()) {
                $returnMessage = $e->getResponse()->getBody() . ' (' . $e->getResponse()->getStatusCode() . ' ' . $e->getResponse()->getReasonPhrase() . ')';
            }

            // only in this case, where a new deposit failed,
            // delete old doaj ID for all other sibling minor publications
            $editParams = [
                $this->getIdSettingName() => null,
            ];
            if ($deleted && $objects instanceof Publication) {
                Repo::publication()->getCollector()
                    ->filterBySubmissionIds([$objects->getData('submissionId')])
                    ->filterByVersionStage($objects->getData('versionStage'))
                    ->filterByVersionMajor($objects->getData('versionMajor'))
                    ->getMany()
                    ->filter(function (Publication $publication) use ($objects) {
                        return $publication->getId() != $objects->getId();
                    })
                    ->each(fn (Publication $publication) => Repo::publication()->edit($publication, $editParams));

            }

            $this->updateStatus($objects, PubObjectsExportPlugin::EXPORT_STATUS_ERROR, $returnMessage);
            return [['plugins.importexport.doaj.register.error.mdsError', $returnMessage]];
        }
        $responseBody = json_decode($response->getBody());

        $objects->setData($this->getIdSettingName(), $responseBody->id);
        $objects->setData($this->getDoiSettingName(), $objectDoi);
        $objects->setData($this->getUrlSettingName(), $objectUrl);
        $this->updateStatus($objects, PubObjectsExportPlugin::EXPORT_STATUS_REGISTERED);

        // set new doaj ID for all other sibling minor publications
        $editParams = [
            $this->getIdSettingName() => $responseBody->id,
        ];
        if ($objects instanceof Publication) {
            Repo::publication()->getCollector()
                ->filterBySubmissionIds([$objects->getData('submissionId')])
                ->filterByVersionStage($objects->getData('versionStage'))
                ->filterByVersionMajor($objects->getData('versionMajor'))
                ->getMany()
                ->filter(function (Publication $publication) use ($objects) {
                    return $publication->getId() != $objects->getId();
                })
                ->each(fn (Publication $publication) => Repo::publication()->edit($publication, $editParams));

        }

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
            if ($context->getData(Context::SETTING_DOI_VERSIONING)) {
                $filter = 'publication=>doaj-json';
            } else {
                $filter = 'article=>doaj-json';
            }
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
                    Notification::NOTIFICATION_TYPE_SUCCESS
                );
            } else {
                foreach ($resultErrors as $errors) {
                    foreach ($errors as $error) {
                        assert(is_array($error) && count($error) >= 1);
                        $this->_sendNotification(
                            $request->getUser(),
                            $error[0],
                            Notification::NOTIFICATION_TYPE_ERROR,
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

    /**
     * Get setting name to save the deposited object id returned by DOAJ
     */
    public function getIdSettingName(): string
    {
        return $this->getPluginSettingsPrefix() . '::id';
    }

    /**
     * Get setting name to save the deposited DOI for the object
     */
    public function getDoiSettingName(): string
    {
        return $this->getPluginSettingsPrefix() . '::doi';
    }

    /**
     * Get setting name to save the deposited URL for the object
     */
    public function getUrlSettingName(): string
    {
        return $this->getPluginSettingsPrefix() . '::url';
    }

    /**
     * @copydoc PubObjectsExportPlugin::_getObjectAdditionalSettings()
     */
    public function _getObjectAdditionalSettings()
    {
        return array_merge(parent::_getObjectAdditionalSettings(), [
            $this->getIdSettingName(),
            $this->getDoiSettingName(),
            $this->getUrlSettingName(),
        ]);
    }


}
