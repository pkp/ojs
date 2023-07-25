<?php

/**
 * @file classes/mail/variables/IssueEmailVariable.php
 *
 * Copyright (c) 2014-2023 Simon Fraser University
 * Copyright (c) 2000-2023 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class IssueEmailVariable
 *
 * @ingroup mail_variables
 *
 * @brief Email template variables for an issue.
 */

namespace APP\mail\variables;

use APP\core\Application;
use APP\facades\Repo;
use APP\issue\Issue;
use APP\submission\Submission;
use APP\template\TemplateManager;
use PKP\core\PKPString;
use PKP\db\DAORegistry;
use PKP\facades\Locale;
use PKP\mail\Mailable;
use PKP\mail\variables\Variable;

class IssueEmailVariable extends Variable
{
    public const ISSUE_ID = 'issueId';
    public const ISSUE_IDENTIFICATION = 'issueIdentification';
    public const ISSUE_URL = 'issueUrl';
    public const ISSUE_TOC = 'issueToc';

    protected Issue $issue;

    public function __construct(Issue $issue, Mailable $mailable)
    {
        parent::__construct($mailable);

        $this->issue = $issue;
    }

    public static function descriptions(): array
    {
        return
        [
            static::ISSUE_ID => __('emailTemplate.variable.issueId'),
            static::ISSUE_IDENTIFICATION => __('emailTemplate.variable.issue.issueIdentification'),
            static::ISSUE_URL => __('emailTemplate.variable.issue.issuePublishedUrl'),
            static::ISSUE_TOC => __('emailTemplate.variable.issue.issueTableOfContent'),
        ];
    }

    public function values(string $locale): array
    {
        return
        [
            static::ISSUE_ID => $this->issue->getId(),
            static::ISSUE_IDENTIFICATION => htmlspecialchars($this->issue->getIssueIdentification()),
            static::ISSUE_URL => $this->getIssueUrl(),
            static::ISSUE_TOC => $this->getIssueToc(),
        ];
    }

    protected function getIssueUrl(): string
    {
        return Application::get()->getDispatcher()->url(
            Application::get()->getRequest(),
            Application::ROUTE_PAGE,
            $this->getContext()->getPath(),
            'issue',
            'view',
            $this->issue->getBestIssueId()
        );
    }

    protected function getIssueToc(): string
    {
        $request = Application::get()->getRequest();
        $templateMgr = TemplateManager::getManager($request);

        $templateMgr->assign([
            'issueIdentification' => $this->issue->getIssueIdentification(),
            'issueTitle' => $this->issue->getLocalizedTitle(),
            'issueSeries' => $this->issue->getIssueIdentification(['showTitle' => true]),
            'currentContext' => $this->getContext(),
            'currentJournal' => $this->getContext(),
        ]);

        $issueGalleyDao = DAORegistry::getDAO('IssueGalleyDAO'); /** @var IssueGalleyDAO $issueGalleyDao */

        $genreDao = DAORegistry::getDAO('GenreDAO'); /** @var GenreDAO $genreDao */
        $primaryGenres = $genreDao->getPrimaryByContextId($this->getContext()->getId())->toArray();
        $primaryGenreIds = array_map(function ($genre) {
            return $genre->getId();
        }, $primaryGenres);

        $issueSubmissions = Repo::submission()->getCollector()
            ->filterByContextIds([$this->issue->getJournalId()])
            ->filterByIssueIds([$this->issue->getId()])
            ->filterByStatus([Submission::STATUS_PUBLISHED])
            ->orderBy(\APP\submission\Collector::ORDERBY_SEQUENCE, \APP\submission\Collector::ORDER_DIR_ASC)
            ->getMany();

        $sections = Repo::section()->getByIssueId($this->issue->getId());
        $issueSubmissionsInSection = [];

        foreach ($sections as $section) {
            $issueSubmissionsInSection[$section->getId()] = [
                'title' => $section->getHideTitle() ? null : $section->getLocalizedTitle(),
                'hideAuthor' => $section->getHideAuthor(),
                'articles' => [],
            ];
        }

        foreach ($issueSubmissions as $submission) {
            if (!$sectionId = $submission->getCurrentPublication()->getData('sectionId')) {
                continue;
            }
            $issueSubmissionsInSection[$sectionId]['articles'][] = $submission;
        }

        $authorUserGroups = Repo::userGroup()->getCollector()
            ->filterByRoleIds([\PKP\security\Role::ROLE_ID_AUTHOR])
            ->filterByContextIds([$this->getContext()->getId()])
            ->getMany();

        $templateMgr->assign([
            'issueIdentification' => $this->issue->getIssueIdentification(),
            'issueTitle' => $this->issue->getLocalizedTitle(),
            'issueSeries' => $this->issue->getIssueIdentification(['showTitle' => true]),
            'currentContext' => $this->getContext(),
            'currentJournal' => $this->getContext(),
            'issue' => $this->issue,
            'issueGalleys' => $issueGalleyDao->getByIssueId($this->issue->getId()),
            'publishedSubmissions' => $issueSubmissionsInSection,
            'primaryGenreIds' => $primaryGenreIds,
            'authorUserGroups' => $authorUserGroups,
            'locale' => Locale::getLocale(),
        ]);

        return PKPString::stripUnsafeHtml($templateMgr->fetch('frontend/objects/issue_toc.tpl'));
    }
}
