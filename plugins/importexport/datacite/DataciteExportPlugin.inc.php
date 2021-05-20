<?php

/**
 * @file plugins/importexport/datacite/DataciteExportPlugin.inc.php
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2003-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class DataciteExportPlugin
 * @ingroup plugins_importexport_datacite
 *
 * @brief DataCite export/registration plugin.
 */

use PKP\file\FileManager;
use PKP\notification\PKPNotification;

use APP\plugins\DOIPubIdExportPlugin;

// DataCite API
define('DATACITE_API_RESPONSE_OK', 201);
define('DATACITE_API_URL', 'https://mds.datacite.org/');
define('DATACITE_API_URL_TEST', 'https://mds.test.datacite.org/');

// Export file types.
define('DATACITE_EXPORT_FILE_XML', 0x01);
define('DATACITE_EXPORT_FILE_TAR', 0x02);

use APP\submission\Submission;
use APP\article\ArticleGalley;
use APP\issue\Issue;

class DataciteExportPlugin extends DOIPubIdExportPlugin
{
    /**
     * @see Plugin::getName()
     */
    public function getName()
    {
        return 'DataciteExportPlugin';
    }

    /**
     * @see Plugin::getDisplayName()
     */
    public function getDisplayName()
    {
        return __('plugins.importexport.datacite.displayName');
    }

    /**
     * @see Plugin::getDescription()
     */
    public function getDescription()
    {
        return __('plugins.importexport.datacite.description');
    }

    /**
     * @copydoc PubObjectsExportPlugin::getSubmissionFilter()
     */
    public function getSubmissionFilter()
    {
        return 'article=>datacite-xml';
    }

    /**
     * @copydoc PubObjectsExportPlugin::getIssueFilter()
     */
    public function getIssueFilter()
    {
        return 'issue=>datacite-xml';
    }

    /**
     * @copydoc PubObjectsExportPlugin::getRepresentationFilter()
     */
    public function getRepresentationFilter()
    {
        return 'galley=>datacite-xml';
    }

    /**
     * @copydoc ImportExportPlugin::getPluginSettingsPrefix()
     */
    public function getPluginSettingsPrefix()
    {
        return 'datacite';
    }

    /**
     * @copydoc DOIPubIdExportPlugin::getSettingsFormClassName()
     */
    public function getSettingsFormClassName()
    {
        return 'DataciteSettingsForm';
    }

    /**
     * @copydoc PubObjectsExportPlugin::getExportDeploymentClassName()
     */
    public function getExportDeploymentClassName()
    {
        return 'DataciteExportDeployment';
    }

    /**
     * @copydoc PubObjectsExportPlugin::executeExportAction()
     *
     * @param null|mixed $noValidation
     */
    public function executeExportAction($request, $objects, $filter, $tab, $objectsFileNamePart, $noValidation = null)
    {
        $context = $request->getContext();
        $path = ['plugin', $this->getName()];

        $fileManager = new FileManager();

        // Export
        if ($request->getUserVar(EXPORT_ACTION_EXPORT)) {
            $result = $this->_checkForTar();
            if ($result === true) {
                $exportedFiles = [];
                foreach ($objects as $object) {
                    // Get the XML
                    $exportXml = $this->exportXML($object, $filter, $context, $noValidation);
                    // Write the XML to a file.
                    // export file name example: datacite-20160723-160036-articles-1-1.xml
                    $objectFileNamePart = $objectsFileNamePart . '-' . $object->getId();
                    $exportFileName = $this->getExportFileName($this->getExportPath(), $objectFileNamePart, $context, '.xml');
                    $fileManager->writeFile($exportFileName, $exportXml);
                    $exportedFiles[] = $exportFileName;
                }
                // If we have more than one export file we package the files
                // up as a single tar before going on.
                assert(count($exportedFiles) >= 1);
                if (count($exportedFiles) > 1) {
                    // tar file name: e.g. datacite-20160723-160036-articles-1.tar.gz
                    $finalExportFileName = $this->getExportFileName($this->getExportPath(), $objectFileNamePart, $context, '.tar.gz');
                    $this->_tarFiles($this->getExportPath(), $finalExportFileName, $exportedFiles);
                    // remove files
                    foreach ($exportedFiles as $exportedFile) {
                        $fileManager->deleteByPath($exportedFile);
                    }
                } else {
                    $finalExportFileName = array_shift($exportedFiles);
                }
                $fileManager->downloadByPath($finalExportFileName);
                $fileManager->deleteByPath($finalExportFileName);
            } else {
                if (is_array($result)) {
                    foreach ($result as $error) {
                        assert(is_array($error) && count($error) >= 1);
                        $this->_sendNotification(
                            $request->getUser(),
                            $error[0],
                            PKPNotification::NOTIFICATION_TYPE_ERROR,
                            ($error[1] ?? null)
                        );
                    }
                }
                // redirect back to the right tab
                $request->redirect(null, null, null, $path, null, $tab);
            }
        } elseif ($request->getUserVar(EXPORT_ACTION_DEPOSIT)) {
            $resultErrors = [];
            foreach ($objects as $object) {
                // Get the XML
                $exportXml = $this->exportXML($object, $filter, $context, $noValidation);
                // Write the XML to a file.
                // export file name example: datacite-20160723-160036-articles-1-1.xml
                $objectFileNamePart = $objectsFileNamePart . '-' . $object->getId();
                $exportFileName = $this->getExportFileName($this->getExportPath(), $objectFileNamePart, $context, '.xml');
                $fileManager->writeFile($exportFileName, $exportXml);
                // Deposit the XML file.
                $result = $this->depositXML($object, $context, $exportFileName);
                if (is_array($result)) {
                    $resultErrors[] = $result;
                }
                // Remove all temporary files.
                $fileManager->deleteByPath($exportFileName);
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
            return parent::executeExportAction($request, $objects, $filter, $tab, $objectsFileNamePart);
        }
    }

    /**
     * @copydoc PubObjectsExportPlugin::depositXML()
     */
    public function depositXML($object, $context, $filename)
    {
        $request = Application::get()->getRequest();
        // Get the DOI and the URL for the object.
        $doi = $object->getStoredPubId('doi');
        assert(!empty($doi));
        $testDOIPrefix = null;
        if ($this->isTestMode($context)) {
            $testDOIPrefix = $this->getSetting($context->getId(), 'testDOIPrefix');
            assert(!empty($testDOIPrefix));
            $doi = PKPString::regexp_replace('#^[^/]+/#', $testDOIPrefix . '/', $doi);
        }
        $url = $this->_getObjectUrl($request, $context, $object);
        assert(!empty($url));

        $dataCiteAPIUrl = DATACITE_API_URL;
        $username = $this->getSetting($context->getId(), 'username');
        $password = $this->getSetting($context->getId(), 'password');
        if ($this->isTestMode($context)) {
            $dataCiteAPIUrl = DATACITE_API_URL_TEST;
            $username = $this->getSetting($context->getId(), 'testUsername');
            $password = $this->getSetting($context->getId(), 'testPassword');
        }

        // Prepare HTTP session.
        assert(is_readable($filename));
        $httpClient = Application::get()->getHttpClient();
        try {
            $response = $httpClient->request('POST', $dataCiteAPIUrl . 'metadata', [
                'auth' => [$username, $password],
                'body' => fopen($filename, 'r'),
                'headers' => [
                    'Content-Type' => 'application/xml;charset=UTF-8',
                ],
            ]);
        } catch (GuzzleHttp\Exception\RequestException $e) {
            $returnMessage = $e->getMessage();
            if ($e->hasResponse()) {
                $returnMessage = $e->getResponse()->getBody(true) . ' (' . $e->getResponse()->getStatusCode() . ' ' . $e->getResponse()->getReasonPhrase() . ')';
            }
            return [['plugins.importexport.common.register.error.mdsError', "Registering DOI ${doi}: ${returnMessage}"]];
        }

        // Mint a DOI.
        $httpClient = Application::get()->getHttpClient();
        try {
            $response = $httpClient->request('POST', $dataCiteAPIUrl . 'doi', [
                'auth' => [$username, $password],
                'headers' => [
                    'Content-Type' => 'text/plain;charset=UTF-8',
                ],
                'body' => "doi=${doi}\nurl=${url}",
            ]);
        } catch (GuzzleHttp\Exception\RequestException $e) {
            $returnMessage = $e->getMessage();
            if ($e->hasResponse()) {
                $returnMessage = $e->getResponse()->getBody(true) . ' (' . $e->getResponse()->getStatusCode() . ' ' . $e->getResponse()->getReasonPhrase() . ')';
            }
            return [['plugins.importexport.common.register.error.mdsError', "Registering DOI ${doi}: ${returnMessage}"]];
        }
        $object->setData($this->getDepositStatusSettingName(), EXPORT_STATUS_REGISTERED);
        $this->saveRegisteredDoi($context, $object, $testDOIPrefix);
        return true;
    }

    /**
     * @copydoc PKPImportExportPlugin::executeCLI()
     */
    public function executeCLICommand($scriptName, $command, $context, $outputFile, $objects, $filter, $objectsFileNamePart)
    {
        $fileManager = new FileManager();
        switch ($command) {
            case 'export':
                $result = $this->_checkForTar();
                if ($result === true) {
                    $exportedFiles = [];
                    foreach ($objects as $object) {
                        // Get the XML
                        $exportXml = $this->exportXML($object, $filter, $context);
                        // Write the XML to a file.
                        // export file name example: datacite-20160723-160036-articles-1-1.xml
                        $objectFileNamePart = $objectsFileNamePart . '-' . $object->getId();
                        $exportFileName = $this->getExportFileName($this->getExportPath(), $objectFileNamePart, $context, '.xml');
                        $fileManager->writeFile($exportFileName, $exportXml);
                        $exportedFiles[] = $exportFileName;
                    }
                    // If we have more than one export file we package the files
                    // up as a single tar before going on.
                    assert(count($exportedFiles) >= 1);
                    if (count($exportedFiles) > 1) {
                        // tar file name: e.g. datacite-20160723-160036-articles-1.tar.gz
                        $finalExportFileName = $this->getExportFileName($this->getExportPath(), $objectFileNamePart, $context, '.tar.gz');
                        $finalExportFileType = DATACITE_EXPORT_FILE_TAR;
                        $this->_tarFiles($this->getExportPath(), $finalExportFileName, $exportedFiles);
                    } else {
                        $finalExportFileName = array_shift($exportedFiles);
                        $finalExportFileType = DATACITE_EXPORT_FILE_XML;
                    }
                    $outputFileExtension = ($finalExportFileType == DATACITE_EXPORT_FILE_TAR ? '.tar.gz' : '.xml');
                    if (substr($outputFile, -strlen($outputFileExtension)) != $outputFileExtension) {
                        $outputFile .= $outputFileExtension;
                    }
                    $fileManager->copyFile($finalExportFileName, $outputFile);
                    foreach ($exportedFiles as $exportedFile) {
                        $fileManager->deleteByPath($exportedFile);
                    }
                    $fileManager->deleteByPath($finalExportFileName);
                } else {
                    echo __('plugins.importexport.common.cliError') . "\n";
                    echo __('manager.plugins.tarCommandNotFound') . "\n\n";
                    $this->usage($scriptName);
                }
                break;
            case 'register':
                $resultErrors = [];
                foreach ($objects as $object) {
                    // Get the XML
                    $exportXml = $this->exportXML($object, $filter, $context);
                    // Write the XML to a file.
                    // export file name example: datacite-20160723-160036-articles-1-1.xml
                    $objectFileNamePart = $objectsFileNamePart . '-' . $object->getId();
                    $exportFileName = $this->getExportFileName($this->getExportPath(), $objectFileNamePart, $context, '.xml');
                    $fileManager->writeFile($exportFileName, $exportXml);
                    // Deposit the XML file.
                    $result = $this->depositXML($object, $context, $exportFileName);
                    if (is_array($result)) {
                        $resultErrors[] = $result;
                    }
                    // Remove all temporary files.
                    $fileManager->deleteByPath($exportFileName);
                }
                if (empty($resultErrors)) {
                    echo __('plugins.importexport.common.register.success') . "\n";
                } else {
                    echo __('plugins.importexport.common.cliError') . "\n";
                    foreach ($resultErrors as $errors) {
                        foreach ($errors as $error) {
                            assert(is_array($error) && count($error) >= 1);
                            $errorMessage = __($error[0], ['param' => ($error[1] ?? null)]);
                            echo "*** ${errorMessage}\n";
                        }
                    }
                    echo "\n";
                    $this->usage($scriptName);
                }
                break;
        }
    }

    /**
     * Test whether the tar binary is available.
     *
     * @return boolean|array Boolean true if available otherwise
     *  an array with an error message.
     */
    public function _checkForTar()
    {
        $tarBinary = Config::getVar('cli', 'tar');
        if (empty($tarBinary) || !is_executable($tarBinary)) {
            $result = [
                ['manager.plugins.tarCommandNotFound']
            ];
        } else {
            $result = true;
        }
        return $result;
    }

    /**
     * Create a tar archive.
     *
     * @param $targetPath string
     * @param $targetFile string
     * @param $sourceFiles array
     */
    public function _tarFiles($targetPath, $targetFile, $sourceFiles)
    {
        assert((bool) $this->_checkForTar());
        // GZip compressed result file.
        $tarCommand = Config::getVar('cli', 'tar') . ' -czf ' . escapeshellarg($targetFile);
        // Do not reveal our internal export path by exporting only relative filenames.
        $tarCommand .= ' -C ' . escapeshellarg($targetPath);
        // Do not reveal our webserver user by forcing root as owner.
        $tarCommand .= ' --owner 0 --group 0 --';
        // Add each file individually so that other files in the directory
        // will not be included.
        foreach ($sourceFiles as $sourceFile) {
            assert(dirname($sourceFile) . '/' === $targetPath);
            if (dirname($sourceFile) . '/' !== $targetPath) {
                continue;
            }
            $tarCommand .= ' ' . escapeshellarg(basename($sourceFile));
        }
        // Execute the command.
        exec($tarCommand);
    }

    /**
     * Get the canonical URL of an object.
     *
     * @param $request Request
     * @param $context Context
     * @param $object Issue|Submission|ArticleGalley
     */
    public function _getObjectUrl($request, $context, $object)
    {
        $router = $request->getRouter();
        // Retrieve the article of article files.
        if ($object instanceof ArticleGalley) {
            $publication = Services::get('publication')->get($object->getData('publicationId'));
            $articleId = $publication->getData('submissionId');
            $cache = $this->getCache();
            if ($cache->isCached('articles', $articleId)) {
                $article = $cache->get('articles', $articleId);
            } else {
                $article = Services::get('submission')->get($articleId);
            }
            assert($article instanceof Submission);
        }
        $url = null;
        switch (true) {
            case $object instanceof Issue:
                $url = $router->url($request, $context->getPath(), 'issue', 'view', $object->getBestIssueId(), null, null, true);
                break;
            case $object instanceof Submission:
                $url = $router->url($request, $context->getPath(), 'article', 'view', $object->getBestId(), null, null, true);
                break;
            case $object instanceof ArticleGalley:
                $url = $router->url($request, $context->getPath(), 'article', 'view', [$article->getBestId(), $object->getBestGalleyId()], null, null, true);
                break;
        }
        if ($this->isTestMode($context)) {
            // Change server domain for testing.
            $url = PKPString::regexp_replace('#://[^\s]+/index.php#', '://example.com/index.php', $url);
        }
        return $url;
    }
}
