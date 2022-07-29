<?php

/**
 * @file /pages/dois/DoiManagementHandler.inc.php
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2003-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class DoisHandler
 * @ingroup pages_doi
 *
 * @brief Handle requests for DOI management functions.
 */

use APP\components\listPanels\DoiListPanel;
use APP\facades\Repo;

import('lib.pkp.pages.dois.PKPDoisHandler');

class DoisHandler extends PKPDoisHandler
{
    /**
     * Set app-specific state components to appear on DOI management page
     */
    protected function getAppStateComponents(\APP\core\Request $request, array $enabledDoiTypes, array $commonArgs): array
    {
        $context = $request->getContext();

        $stateComponents = [];

        // Publication and Galley DOIs
        if (count(array_intersect($enabledDoiTypes, [Repo::doi()::TYPE_PUBLICATION, Repo::doi()::TYPE_REPRESENTATION])) > 0) {
            $submissionDoiListPanel = new DoiListPanel(
                'submissionDoiListPanel',
                __('doi.manager.submissionDois'),
                array_merge(
                    $commonArgs,
                    [
                        'apiUrl' => $request->getDispatcher()->url($request, PKPApplication::ROUTE_API, $context->getPath(), 'submissions'),
                        'getParams' => [
                            'stageIds' => [WORKFLOW_STAGE_ID_EDITING, WORKFLOW_STAGE_ID_PRODUCTION],
                        ],
                        'isSubmission' => true,
                        'includeIssuesFilter' => true,
                        'itemType' => 'submission'
                    ]
                )
            );
            $stateComponents[$submissionDoiListPanel->id] = $submissionDoiListPanel->getConfig();
        }

        // Issues DOIs
        if (in_array(Repo::doi()::TYPE_ISSUE, $enabledDoiTypes)) {
            $issueDoiListPanel = new DoiListPanel(
                'issueDoiListPanel',
                __('doi.manager.issueDois'),
                array_merge(
                    $commonArgs,
                    [
                        'apiUrl' => $request->getDispatcher()->url($request, PKPApplication::ROUTE_API, $context->getPath(), 'issues'),
                        'getParams' => [],
                        'isSubmission' => false,
                        'includeIssuesFilter' => false,
                        'itemType' => 'issue',
                    ]
                )
            );
            $stateComponents[$issueDoiListPanel->id] = $issueDoiListPanel->getConfig();
        }
        return $stateComponents;
    }

    /**
     * Set Smarty template variables. Which tabs to display are set by the APP.
     */
    protected function getTemplateVariables(array $enabledDoiTypes): array
    {
        $templateVariables = parent::getTemplateVariables($enabledDoiTypes);
        return array_merge(
            $templateVariables,
            [
                'displaySubmissionsTab' => count(array_intersect($enabledDoiTypes, [Repo::doi()::TYPE_PUBLICATION, Repo::doi()::TYPE_REPRESENTATION])) > 0,
                'displayIssuesTab' => in_array(Repo::doi()::TYPE_ISSUE, $enabledDoiTypes),
            ]
        );
    }
}
