<?php

/**
 * @file plugins/importexport/pubmed/PubMedExportPlugin.php
 *
 * Copyright (c) 2014-2025 Simon Fraser University
 * Copyright (c) 2003-2025 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class PubMedExportPlugin
 *
 * @brief PubMed/MEDLINE XML metadata export plugin
 */

namespace APP\plugins\importexport\pubmed;

use APP\core\Application;
use APP\core\Request;
use APP\facades\Repo;
use APP\journal\JournalDAO;
use APP\notification\NotificationManager;
use APP\plugins\importexport\pubmed\filter\ArticlePubMedXmlFilter;
use APP\template\TemplateManager;
use Exception;
use PKP\context\Context;
use PKP\core\JSONMessage;
use PKP\core\PKPApplication;
use PKP\db\DAORegistry;
use PKP\file\FileManager;
use PKP\filter\FilterDAO;
use PKP\plugins\ImportExportPlugin;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class PubMedExportPlugin extends ImportExportPlugin
{
    /**
     * @copydoc Plugin::register()
     *
     * @param null|mixed $mainContextId
     */
    public function register($category, $path, $mainContextId = null): bool
    {
        $success = parent::register($category, $path, $mainContextId);
        $this->addLocaleData();
        return $success;
    }

    /**
     * Get the name of this plugin. The name must be unique within
     * its category.
     */
    public function getName(): string
    {
        return 'PubMedExportPlugin';
    }

    /**
     * Get the display name.
     */
    public function getDisplayName(): string
    {
        return __('plugins.importexport.pubmed.displayName');
    }

    /**
     * Get the display description.
     */
    public function getDescription(): string
    {
        return __('plugins.importexport.pubmed.description');
    }

    /**
     * Display the plugin.
     *
     * @param array $args
     * @param Request $request
     *
     * @throws Exception
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
                    $request->getContext()
                );
                $fileManager = new FileManager();
                $exportFileName = $this->getExportFileName($this->getExportPath(), 'articles', $context);
                $fileManager->writeFile($exportFileName, $exportXml);
                $fileManager->downloadByPath($exportFileName);
                $fileManager->deleteByPath($exportFileName);
                break;
            case 'exportIssues':
                $exportXml = $this->exportIssues(
                    (array) $request->getUserVar('selectedIssues'),
                    $request->getContext()
                );
                $fileManager = new FileManager();
                $exportFileName = $this->getExportFileName($this->getExportPath(), 'issues', $context);
                $fileManager->writeFile($exportFileName, $exportXml);
                $fileManager->downloadByPath($exportFileName);
                $fileManager->deleteByPath($exportFileName);
                break;
            default:
                throw new NotFoundHttpException();
        }
    }

    /**
     * @copydoc ImportExportPlugin::getPluginSettingsPrefix()
     */
    public function getPluginSettingsPrefix(): string
    {
        return 'pubmed';
    }

    /**
     * @copydoc Plugin::manage()
     */
    public function manage($args, $request): JSONMessage
    {
        $user = $request->getUser();
        $this->addLocaleData();
        $form = new PubMedSettingsForm($this, $request->getContext()->getId());

        switch ($request->getUserVar('verb')) {
            case 'index':
                $form->initData();
                return new JSONMessage(true, $form->fetch($request));
            case 'save':
                $form->readInputData();
                if ($form->validate()) {
                    $form->execute();
                    $notificationManager = new NotificationManager();
                    $notificationManager->createTrivialNotification($user->getId());
                    return new JSONMessage(true);
                } else {
                    return new JSONMessage(true, $form->fetch($request));
                }
        }
        return parent::manage($args, $request);
    }

    /*
     * Get the XML for a set of submissions.
     */
    public function exportSubmissions(array $submissionIds, Context $context)
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
     * @throws Exception
     *
     * @return string XML contents representing the supplied issue IDs.
     */
    public function exportIssues(array $issueIds, Context $context): string
    {
        $filterDao = DAORegistry::getDAO('FilterDAO'); /** @var FilterDAO $filterDao */
        /** @var ArticlePubMedXmlFilter[] $pubmedExportFilters */
        $pubmedExportFilters = $filterDao->getObjectsByGroup('article=>pubmed-xml');
        assert(count($pubmedExportFilters) == 1); // Assert only a single serialization filter
        $exportFilter = array_shift($pubmedExportFilters);
        $input = [];

        foreach ($issueIds as $issueId) {
            $sections = Repo::section()->getByIssueId($issueId);
            $submissionsInSections = Repo::submission()->getInSections($issueId, $context->getId());
            foreach ($sections as $section) {
                $input = array_merge($input, $submissionsInSections[$section->getId()]['articles']);
            }
        }

        libxml_use_internal_errors(true);
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
     *
     * @throws Exception
     */
    public function executeCLI($scriptName, &$args)
    {
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
                        file_put_contents($xmlFile, $this->exportSubmissions($args, $journal));
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
                        file_put_contents($xmlFile, $this->exportIssues($issues, $journal));
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
