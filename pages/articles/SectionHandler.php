<?php

/**
 * @file pages/articles/SectionHandler.php
 *
 * Copyright (c) 2014-2024 Simon Fraser University
 * Copyright (c) 2003-2024 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class SectionHandler
 *
 * @ingroup pages_articles
 *
 * @brief Handle requests for sections functions.
 *
 */

namespace APP\pages\articles;

use APP\core\Application;
use APP\facades\Repo;
use APP\security\authorization\OjsJournalMustPublishPolicy;
use APP\submission\Collector;
use APP\submission\Submission;
use APP\template\TemplateManager;
use Illuminate\Support\LazyCollection;
use PKP\context\Context;

class SectionHandler extends \PKP\pages\publication\PKPSectionHandler
{
    /**
     * @copydoc PKPHandler::authorize()
     */
    public function authorize($request, &$args, $roleAssignments)
    {
        $this->addPolicy(new OjsJournalMustPublishPolicy($request));
        return parent::authorize($request, $args, $roleAssignments);
    }

    protected function getCollector(int $sectionId, int $contextId): Collector
    {
        $collector = Repo::submission()->getCollector();
        $collector
            ->filterByContextIds([$contextId])
            ->filterBySectionIds([$sectionId])
            ->filterByStatus([Submission::STATUS_PUBLISHED])
            ->orderBy($collector::ORDERBY_DATE_PUBLISHED, $collector::ORDER_DIR_ASC);
        return $collector;
    }

    protected function assignTemplateVars(LazyCollection $submissions, Context $context)
    {
        $request = Application::get()->getRequest();
        $router = $request->getRouter();
        $issueUrls = [];
        $issueNames = [];
        foreach ($submissions as $submission) {
            $issue = Repo::issue()->getBySubmissionId($submission->getId());
            $issueUrls[$submission->getId()] = $router->url($request, $context->getPath(), 'issue', 'view', [$issue->getBestIssueId()], null, null, true);
            $issueNames[$submission->getId()] = $issue->getIssueIdentification();
        }

        $templateMgr = TemplateManager::getManager($request);
        $templateMgr->assign([
            'issueUrls' => $issueUrls,
            'issueNames' => $issueNames,
        ]);

        $templateMgr->display('frontend/pages/sections.tpl');

    }

}
