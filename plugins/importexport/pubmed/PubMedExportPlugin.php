<?php

/**
 * @file plugins/importexport/pubmed/PubMedExportPlugin.php
 *
 * Copyright (c) 2014-2022 Simon Fraser University
 * Copyright (c) 2003-2022 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class PubMedExportPlugin
 *
 * @brief PubMed/MEDLINE XML metadata export plugin
 */

namespace APP\plugins\importexport\pubmed;

use APP\core\Application;
use APP\facades\Repo;
use APP\journal\JournalDAO;
use APP\template\TemplateManager;
use Exception;
use PKP\core\PKPApplication;
use PKP\db\DAORegistry;
use PKP\file\FileManager;
use PKP\filter\FilterDAO;
use PKP\plugins\ImportExportPlugin;

class PubMedExportPlugin extends ImportExportPlugin
{
    /**
     * @copydoc Plugin::register()
     *
     * @param null|mixed $mainContextId
     */
    public function register($category, $path, $mainContextId = null)
    {
        $success = parent::register($category, $path, $mainContextId);
        $this->addLocaleData();
        return $success;
    }

    /**
     * Get the name of this plugin. The name must be unique within
     * its category.
     *
     * @return string name of plugin
     */
    public function getName()
    {
        return 'PubMedExportPlugin';
    }

    /**
     * Get the display name.
     *
     * @return string
     */
    public function getDisplayName()
    {
        return __('plugins.importexport.pubmed.displayName');
    }

    /**
     * Get the display description.
     *
     * @return string
     */
    public function getDescription()
    {
        return __('plugins.importexport.pubmed.description');
    }

    /**
     * Display the plugin.
     *
     * @param array $args
     * @param \APP\core\Request $request
     */
    public function display($args, $request)
    {
        parent::display($args, $request);
        $templateMgr = TemplateManager::getManager($request);
        $context = $request->getContext();
        switch (array_shift($args)) {
            case 'index':
            case '':
                $apiUrl = $request->getDispatcher()->url($request, PKPApplication::ROUTE_API, $context->getPath(), 'submissions');
                $submissionsListPanel = new \APP\components\listPanels\SubmissionsListPanel(
                    'submissions',
                    __('common.publications'),
                    [
                        'apiUrl' => $apiUrl,
                        'count' => 100,
                        'getParams' => new \stdClass(),
                        'lazyLoad' => true,
                    ]
                );
                $submissionsConfig = $submissionsListPanel->getConfig();
                $submissionsConfig['addUrl'] = '';
                $submissionsConfig['filters'] = array_slice($submissionsConfig['filters'], 1);
                $templateMgr->setState([
                    'components' => [
                        'submissions' => $submissionsConfig,
                    ],
                ]);
                $templateMgr->assign([
                    'pageComponent' => 'ImportExportPage',
                ]);
                $templateMgr->display($this->getTemplateResource('index.tpl'));
                break;
            case 'exportSubmissions':
                $exportXml = $this->exportSubmissions(
                    (array) $request->getUserVar('selectedSubmissions'),
                    $request->getContext(),
                    $request->getUser()
                );
                $fileManager = new FileManager();
                $exportFileName = $this->getExportFileName($this->getExportPath(), 'articles', $context, '.xml');
                $fileManager->writeFile($exportFileName, $exportXml);
                $fileManager->downloadByPath($exportFileName);
                $fileManager->deleteByPath($exportFileName);
                break;
            case 'exportIssues':
                $exportXml = $this->exportIssues(
                    (array) $request->getUserVar('selectedIssues'),
                    $request->getContext(),
                    $request->getUser()
                );
                $fileManager = new FileManager();
                $exportFileName = $this->getExportFileName($this->getExportPath(), 'issues', $context, '.xml');
                $fileManager->writeFile($exportFileName, $exportXml);
                $fileManager->downloadByPath($exportFileName);
                $fileManager->deleteByPath($exportFileName);
                break;
            default:
                $dispatcher = $request->getDispatcher();
                $dispatcher->handle404();
        }
    }

    /**
     * @copydoc ImportExportPlugin::getPluginSettingsPrefix()
     */
    public function getPluginSettingsPrefix()
    {
        return 'pubmed';
    }

    public function exportSubmissions($submissionIds, $context, $user)
    {
        $filterDao = DAORegistry::getDAO('FilterDAO'); /** @var FilterDAO $filterDao */
        $pubmedExportFilters = $filterDao->getObjectsByGroup('article=>pubmed-xml');
        assert(count($pubmedExportFilters) == 1); // Assert only a single serialization filter
        $exportFilter = array_shift($pubmedExportFilters);
        $submissions = [];
        foreach ($submissionIds as $submissionId) {
            $submission = Repo::submission()->get($submissionId);
            if ($submission && $submission->getData('contextId') == $context->getId()) {
                $submissions[] = $submission;
            }
        }
        libxml_use_internal_errors(true);
        $submissionXml = $exportFilter->execute($submissions, true);
        $xml = $submissionXml->saveXml();
        $errors = array_filter(libxml_get_errors(), function ($a) {
            return $a->level == LIBXML_ERR_ERROR || $a->level == LIBXML_ERR_FATAL;
        });
        if (!empty($errors)) {
            $this->displayXMLValidationErrors($errors, $xml);
        }
        return $xml;
    }

    /**
     * Get the XML for a set of issues.
     *
     * @param array $issueIds Array of issue IDs
     * @param \PKP\context\Context $context
     * @param \PKP\user\User $user
     *
     * @return string XML contents representing the supplied issue IDs.
     */
    public function exportIssues($issueIds, $context, $user)
    {
        $filterDao = DAORegistry::getDAO('FilterDAO'); /** @var FilterDAO $filterDao */
        /** @var \APP\plugins\importexport\pubmed\filter\ArticlePubMedXmlFilter[] $pubmedExportFilters */
        $pubmedExportFilters = $filterDao->getObjectsByGroup('article=>pubmed-xml');
        assert(count($pubmedExportFilters) == 1); // Assert only a single serialization filter
        $exportFilter = array_shift($pubmedExportFilters);
        $submissions = Repo::submission()
            ->getCollector()
            ->filterByContextIds([$context->getId()])
            ->filterByIssueIds($issueIds)
            ->getMany();

        libxml_use_internal_errors(true);
        $input = $submissions->toArray();
        $submissionXml = $exportFilter->execute($input, true);
        $xml = $submissionXml->saveXml();
        $errors = array_filter(libxml_get_errors(), function ($a) {
            return $a->level == LIBXML_ERR_ERROR || $a->level == LIBXML_ERR_FATAL;
        });
        if (!empty($errors)) {
            $this->displayXMLValidationErrors($errors, $xml);
        }
        return $xml;
    }

    /**
     * Execute import/export tasks using the command-line interface.
     *
     * @param array $args Parameters to the plugin
     */
    public function executeCLI($scriptName, &$args)
    {
        // $command = array_shift($args);
        $xmlFile = array_shift($args);
        $journalPath = array_shift($args);

        $journalDao = DAORegistry::getDAO('JournalDAO'); /** @var JournalDAO $journalDao */

        $journal = $journalDao->getByPath($journalPath);

        if (!$journal) {
            if ($journalPath != '') {
                echo __('plugins.importexport.pubmed.cliError') . "\n";
                echo __('plugins.importexport.pubmed.error.unknownJournal', ['journalPath' => $journalPath]) . "\n\n";
            }
            $this->usage($scriptName);
            return;
        }

        $user = Application::get()->getRequest()->getUser();

        if ($xmlFile != '') {
            switch (array_shift($args)) {
                case 'articles':
                    try {
                        file_put_contents($xmlFile, $this->exportSubmissions($args, $journal, $user));
                    } catch (Exception $e) {
                        echo $e->getMessage() . "\n\n";
                    }
                    return;
                case 'issue':
                    $issueId = array_shift($args);
                    $issue = Repo::issue()->getByBestId($issueId, $journal->getId());
                    if ($issue == null) {
                        echo __('plugins.importexport.pubmed.cliError') . "\n";
                        echo __('plugins.importexport.pubmed.export.error.issueNotFound', ['issueId' => $issueId]) . "\n\n";
                        return;
                    }
                    $issues = [$issue];
                    try {
                        file_put_contents($xmlFile, $this->exportIssues($issues, $journal, $user));
                    } catch (Exception $e) {
                        echo $e->getMessage() . "\n\n";
                    }
                    return;
            }
        }
        $this->usage($scriptName);
    }

    /**
     * Display the command-line usage information
     */
    public function usage($scriptName)
    {
        echo __('plugins.importexport.pubmed.cliUsage', [
            'scriptName' => $scriptName,
            'pluginName' => $this->getName()
        ]) . "\n";
    }
}
