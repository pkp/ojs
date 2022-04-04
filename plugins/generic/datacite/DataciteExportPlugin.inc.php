<?php

/**
 * @file plugins/generic/datacite/DataciteExportPlugin.inc.php
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2003-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class DataciteExportPlugin
 * @ingroup plugins_generic_datacite
 *
 * @brief DataCite export/registration plugin.
 */

use APP\facades\Repo;
use APP\issue\Issue;

use APP\plugins\DOIPubIdExportPlugin;
use APP\submission\Submission;
use PKP\context\Context;
use PKP\core\DataObject;
use PKP\core\PKPString;
use PKP\doi\Doi;
use PKP\file\TemporaryFileManager;
use PKP\galley\Galley;
use PKP\submission\Representation;

// DataCite API
define('DATACITE_API_RESPONSE_OK', 201);
define('DATACITE_API_URL', 'https://mds.datacite.org/');
define('DATACITE_API_URL_TEST', 'https://mds.test.datacite.org/');

// Export file types.
define('DATACITE_EXPORT_FILE_XML', 0x01);
define('DATACITE_EXPORT_FILE_TAR', 0x02);

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
     * @param DataObject[] $objects
     *
     */
    public function exportAndDeposit(
        Context $context,
        array $objects,
        string $objectsFileNamePart,
        string &$responseMessage,
        ?bool $noValidation = null
    ): bool {
        $fileManager = new FileManager();

        $errorsOccurred = false;
        foreach ($objects as $object) {
            // Get the XML
            $exportErrors = [];
            $filter = $this->_getFilterFromObject($object);
            $exportXml = $this->exportXML($object, $filter, $context, $noValidation, $exportErrors);
            // Write the XML to a file.
            // export file name example: datacite-20160723-160036-articles-1-1.xml
            $objectFileNamePart = $objectsFileNamePart . '-' . $object->getId();
            $exportFileName = $this->getExportFileName($this->getExportPath(), $objectFileNamePart, $context, '.xml');
            $fileManager->writeFile($exportFileName, $exportXml);
            // Deposit the XML file.
            $result = $this->depositXML($object, $context, $exportFileName);
            if (!$result) {
                $errorsOccurred = true;
            }
            if (is_array($result)) {
                $resultErrors[] = $result;
            }
            // Remove all temporary files.
            $fileManager->deleteByPath($exportFileName);
        }
        // Prepare response message and return status
        if (empty($resultErrors)) {
            if ($errorsOccurred) {
                $responseMessage = 'plugins.generic.datacite.deposit.unsuccessful';
                return false;
            } else {
                $responseMessage = $this->getDepositSuccessNotificationMessageKey();
                return true;
            }
        } else {
            $responseMessage = 'api.dois.400.depositFailed';
            return false;
        }
    }

    /**
     * Exports and stores XML as a TemporaryFile
     *
     *
     * @throws Exception
     */
    public function exportAsDownload(Context $context, array $objects, string $objectsFileNamePart, ?bool $noValidation = null, ?array &$outputErrors = null): ?int
    {
        $fileManager = new TemporaryFileManager();

        // Export
        $result = $this->_checkForTar();
        if ($result === true) {
            $exportedFiles = [];
            foreach ($objects as $object) {
                $filter = $this->_getFilterFromObject($object);
                // Get the XML
                $exportXml = $this->exportXML($object, $filter, $context, $noValidation, $outputErrors);
                // Write the XML to a file.
                // export file name example: datacite-20160723-160036-articles-1-1.xml
                $objectFileNamePart = $objectsFileNamePart . '-' . $object->getId();
                $exportFileName = $this->getExportFileName(
                    $this->getExportPath(),
                    $objectFileNamePart,
                    $context,
                    '.xml'
                );
                $fileManager->writeFile($exportFileName, $exportXml);
                $exportedFiles[] = $exportFileName;
            }
            // If we have more than one export file we package the files
            // up as a single tar before going on.
            assert(count($exportedFiles) >= 1);
            if (count($exportedFiles) > 1) {
                // tar file name: e.g. datacite-20160723-160036-articles-1.tar.gz
                $finalExportFileName = $this->getExportFileName(
                    $this->getExportPath(),
                    $objectFileNamePart,
                    $context,
                    '.tar.gz'
                );
                $this->_tarFiles($this->getExportPath(), $finalExportFileName, $exportedFiles);
                // remove files
                foreach ($exportedFiles as $exportedFile) {
                    $fileManager->deleteByPath($exportedFile);
                }
            } else {
                $finalExportFileName = array_shift($exportedFiles);
            }
            $user = Application::get()->getRequest()->getUser();

            return $fileManager->createTempFileFromExisting($finalExportFileName, $user->getId());
        }

        return null;
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
            $this->updateDepositStatus($object, Doi::STATUS_ERROR);
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
            $this->updateDepositStatus($object, Doi::STATUS_ERROR);
            return [['plugins.importexport.common.register.error.mdsError', "Registering DOI ${doi}: ${returnMessage}"]];
        }
        // Test mode submits entirely different DOI and URL so the status of that should not be stored in the database
        // for the real DOI
        if (!$this->isTestMode($context)) {
            $this->updateDepositStatus($object, Doi::STATUS_REGISTERED);
        }
        return true;
    }

    /**
     * Update stored DOI status based on if deposits and registration have been successful
     *
     * @param Submission|Issue|Representation $object
     */
    public function updateDepositStatus(DataObject $object, string $status)
    {
        assert($object instanceof Submission || $object instanceof Issue || $object instanceof Representation);
        $doiObject = $object->getData('doiObject');
        $editParams = [
            'status' => $status
        ];
        Repo::doi()->edit($doiObject, $editParams);
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
                            $errorMessage = __($error[0], ['param' => $error[1] ?? null]);
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
     * @return bool|array Boolean true if available otherwise
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
     * @param string $targetPath
     * @param string $targetFile
     * @param array $sourceFiles
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
     * @param Request $request
     * @param Context $context
     * @param Issue|Submission|Galley $object
     */
    public function _getObjectUrl($request, $context, $object)
    {
        //Dispatcher needed when  called from CLI
        $dispatcher = $request->getDispatcher();
        // Retrieve the article of article files.
        if ($object instanceof Galley) {
            $publication = Repo::publication()->get($object->getData('publicationId'));
            $articleId = $publication->getData('submissionId');
            $cache = $this->getCache();
            if ($cache->isCached('articles', $articleId)) {
                $article = $cache->get('articles', $articleId);
            } else {
                $article = Repo::submission()->get($articleId);
            }
            assert($article instanceof Submission);
        }
        $url = null;
        switch (true) {
            case $object instanceof Issue:
                $url = $dispatcher->url($request, PKPApplication::ROUTE_PAGE, $context->getPath(), 'issue', 'view', $object->getBestIssueId(), null, null, true);
                break;
            case $object instanceof Submission:
                $url = $dispatcher->url($request, PKPApplication::ROUTE_PAGE, $context->getPath(), 'article', 'view', $object->getBestId(), null, null, true);
                break;
            case $object instanceof Galley:
                $url = $dispatcher->url($request, PKPApplication::ROUTE_PAGE, $context->getPath(), 'article', 'view', [$article->getBestId(), $object->getBestGalleyId()], null, null, true);
                break;
        }
        if ($this->isTestMode($context)) {
            // Change server domain for testing.
            $url = PKPString::regexp_replace('#://[^\s]+/index.php#', '://example.com/index.php', $url);
        }
        return $url;
    }

    /**
     * @param Submission|Issue|Representation $object
     *
     */
    private function _getFilterFromObject(DataObject $object): string
    {
        if ($object instanceof Submission) {
            return $this->getSubmissionFilter();
        } elseif ($object instanceof Issue) {
            return $this->getIssueFilter();
        } elseif ($object instanceof Representation) {
            return $this->getRepresentationFilter();
        } else {
            return '';
        }
    }
}
