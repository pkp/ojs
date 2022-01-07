<?php

/**
 * @file plugins/importexport/native/NativeImportExportPlugin.inc.php
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2003-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class NativeImportExportPlugin
 * @ingroup plugins_importexport_native
 *
 * @brief Native XML import/export plugin
 */

import('lib.pkp.plugins.importexport.native.PKPNativeImportExportPlugin');

use APP\facades\Repo;
use APP\template\TemplateManager;

class NativeImportExportPlugin extends PKPNativeImportExportPlugin
{
    /**
     * @see ImportExportPlugin::display()
     */
    public function display($args, $request)
    {
        parent::display($args, $request);

        if ($this->isResultManaged) {
            if ($this->result) {
                return $this->result;
            }

            return false;
        }

        $templateMgr = TemplateManager::getManager($request);

        switch ($this->opType) {
            case 'exportIssuesBounce':
                return $this->getBounceTab(
                    $request,
                    __('plugins.importexport.native.export.issues.results'),
                    'exportIssues',
                    ['selectedIssues' => $request->getUserVar('selectedIssues')]
                );
            case 'exportIssues':
                $selectedEntitiesIds = (array) $request->getUserVar('selectedIssues');
                $deployment = $this->getDeployment();

                $this->getExportIssuesDeployment($selectedEntitiesIds, $deployment);

                return $this->getExportTemplateResult($deployment, $templateMgr, 'issues');
            default:
                $dispatcher = $request->getDispatcher();
                $dispatcher->handle404();
        }
    }

    /**
     * Get the issues and proceed to the export
     *
     * @param array $issueIds Array of issueIds to export
     * @param PKPNativeImportExportDeployment $deployment
     * @param array $opts
     */
    public function getExportIssuesDeployment($issueIds, &$deployment, $opts = [])
    {
        $issues = [];
        foreach ($issueIds as $issueId) {
            $issue = Repo::issue()->get($issueId);
            $issue = $issue->getJournalId() == $deployment->getContext()->getId() ? $issue : null;
            if ($issue) {
                $issues[] = $issue;
            }
        }

        $deployment->export('issue=>native-xml', $issues, $opts);
    }

    /**
     * Get the XML for a set of issues.
     *
     * @param array $issueIds
     * @param Context $context
     * @param User $user
     * @param array $opts
     *
     * @return string XML contents representing the supplied issue IDs.
     */
    public function exportIssues($issueIds, $context, $user, $opts = [])
    {
        $deployment = new NativeImportExportDeployment($context, $user);
        $this->getExportIssuesDeployment($issueIds, $deployment, $opts);

        return $this->exportResultXML($deployment);
    }

    /**
     * @see PKPNativeImportExportPlugin::getImportFilter
     */
    public function getImportFilter($xmlFile)
    {
        $filter = 'native-xml=>issue';
        // is this articles import:
        $xmlString = file_get_contents($xmlFile);
        $document = new DOMDocument();
        $document->loadXml($xmlString);
        if (in_array($document->documentElement->tagName, ['article', 'articles'])) {
            $filter = 'native-xml=>article';
        }

        return [$filter, $xmlString];
    }

    /**
     * @see PKPNativeImportExportPlugin::getExportFilter
     */
    public function getExportFilter($exportType)
    {
        $filter = 'issue=>native-xml';
        if ($exportType == 'exportSubmissions') {
            $filter = 'article=>native-xml';
        }

        return $filter;
    }

    /**
     * @see PKPNativeImportExportPlugin::getAppSpecificDeployment
     */
    public function getAppSpecificDeployment($context, $user)
    {
        return new NativeImportExportDeployment($context, $user);
    }

    /**
     * @see PKPImportExportPlugin::executeCLI()
     */
    public function executeCLI($scriptName, &$args)
    {
        $result = parent::executeCLI($scriptName, $args);

        if ($result) {
            return $result;
        }

        $cliDeployment = $this->cliDeployment;
        $deployment = $this->getDeployment();

        switch ($cliDeployment->command) {
            case 'export':
                switch ($cliDeployment->exportEntity) {
                    case 'issue':
                    case 'issues':
                        $this->getExportIssuesDeployment(
                            $cliDeployment->args,
                            $deployment,
                            $cliDeployment->opts
                        );

                        $this->cliToolkit->getCLIExportResult($deployment, $cliDeployment->xmlFile);
                        $this->cliToolkit->getCLIProblems($deployment);

                        return true;
                }
        }

        $this->usage($scriptName);
    }
}
