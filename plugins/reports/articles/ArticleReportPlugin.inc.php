<?php

/**
 * @file plugins/reports/articles/ArticleReportPlugin.inc.php
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2003-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class ArticleReportPlugin
 * @ingroup plugins_reports_article
 *
 * @brief Article report plugin
 */

use APP\decision\Decision;
use APP\facades\Repo;
use PKP\db\DAORegistry;
use PKP\plugins\ReportPlugin;
use PKP\security\Role;

use PKP\submission\PKPSubmission;

class ArticleReportPlugin extends ReportPlugin
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
        return 'ArticleReportPlugin';
    }

    /**
     * @copydoc Plugin::getDisplayName()
     */
    public function getDisplayName()
    {
        return __('plugins.reports.articles.displayName');
    }

    /**
     * @copydoc Plugin::getDescriptionName()
     */
    public function getDescription()
    {
        return __('plugins.reports.articles.description');
    }

    /**
     * @copydoc ReportPlugin::display()
     */
    public function display($args, $request)
    {
        $context = $request->getContext();
        $acronym = PKPString::regexp_replace('/[^A-Za-z0-9 ]/', '', $context->getLocalizedAcronym());

        // Prepare for UTF8-encoded CSV output.
        header('content-type: text/comma-separated-values');
        header('content-disposition: attachment; filename=articles-' . $acronym . '-' . date('Ymd') . '.csv');
        $fp = fopen('php://output', 'wt');
        // Add BOM (byte order mark) to fix UTF-8 in Excel
        fprintf($fp, chr(0xEF) . chr(0xBB) . chr(0xBF));

        $stageAssignmentDao = DAORegistry::getDAO('StageAssignmentDAO'); /** @var StageAssignmentDAO $stageAssignmentDao */
        $userGroupDao = DAORegistry::getDAO('UserGroupDAO'); /** @var UserGroupDAO $userGroupDao */
        $sectionDao = DAORegistry::getDAO('SectionDAO'); /** @var SectionDAO $sectionDao */
        $submissionKeywordDao = DAORegistry::getDAO('SubmissionKeywordDAO'); /** @var SubmissionKeywordDAO $submissionKeywordDao */
        $submissionSubjectDao = DAORegistry::getDAO('SubmissionSubjectDAO'); /** @var SubmissionSubjectDAO $submissionSubjectDao */
        $submissionDisciplineDao = DAORegistry::getDAO('SubmissionDisciplineDAO'); /** @var SubmissionDisciplineDAO $submissionDisciplineDao */
        $submissionAgencyDao = DAORegistry::getDAO('SubmissionAgencyDAO'); /** @var SubmissionAgencyDAO $submissionAgencyDao */

        $editorUserGroupIds = array_map(function ($userGroup) {
            return $userGroup->getId();
        }, array_filter($userGroupDao->getByContextId($context->getId())->toArray(), function ($userGroup) {
            return in_array($userGroup->getRoleId(), [Role::ROLE_ID_MANAGER, Role::ROLE_ID_SUB_EDITOR]);
        }));

        // Load the data from the database and store it in an array.
        // (This must be stored before display because we won't know the data
        // dimensions until it has all been loaded.)
        $results = $sectionTitles = [];
        $collector = Repo::submission()->getCollector()->filterByContextIds([$journal->getId()]);
        $submissions = Repo::submission()->getMany($collector);
        $maxAuthors = $maxEditors = $maxDecisions = 0;
        foreach ($submissions as $submission) {
            $publication = $submission->getCurrentPublication();
            $maxAuthors = max($maxAuthors, count($publication->getData('authors')));
            $editDecisions = Repo::decision()->getMany(
                Repo::decision()
                    ->getCollector()
                    ->filterBySubmissionIds([$submission->getId()])
            );
            $statusMap = $submission->getStatusMap();

            // Count the highest number of decisions per editor.
            $editDecisionsPerEditor = [];
            foreach ($editDecisions as $editDecision) {
                $editorId = $editDecision->getData('editorId');
                $editDecisionsPerEditor[$editorId] = ($editDecisionsPerEditor[$editorId] ?? 0) + 1;
                $maxDecisions = max($maxDecisions, $editDecisionsPerEditor[$editorId]);
            }

            // Load editor and decision information
            $stageAssignmentsFactory = $stageAssignmentDao->getBySubmissionAndStageId($submission->getId());
            $editors = $editorsById = [];
            while ($stageAssignment = $stageAssignmentsFactory->next()) {
                $userId = $stageAssignment->getUserId();
                if (!in_array($stageAssignment->getUserGroupId(), $editorUserGroupIds)) {
                    continue;
                }
                if (isset($editors[$userId])) {
                    continue;
                }
                if (!isset($editorsById[$userId])) {
                    $editor = Repo::user()->get($userId, true);
                    $editorsById[$userId] = [
                        $editor->getLocalizedGivenName(),
                        $editor->getLocalizedFamilyName(),
                        $editor->getData('orcid'),
                        $editor->getEmail(),
                    ];
                }
                $editors[$userId] = $editorsById[$userId];
                $maxEditors = max($maxEditors, count($editors));
            }

            // Load section title information
            $sectionId = $publication->getData('sectionId');
            if (!isset($sectionTitles[$sectionId])) {
                $section = $sectionDao->getById($sectionId);
                $sectionTitles[$sectionId] = $section->getLocalizedTitle();
            }

            // Store the submission results
            $results[] = [
                'submissionId' => $submission->getId(),
                'title' => $publication->getLocalizedFullTitle(),
                'abstract' => html_entity_decode(strip_tags($publication->getLocalizedData('abstract'))),
                'authors' => array_map(function ($author) {
                    return [
                        $author->getLocalizedGivenName(),
                        $author->getLocalizedFamilyName(),
                        $author->getData('orcid'),
                        $author->getData('country'),
                        $author->getLocalizedData('affiliation'),
                        $author->getData('email'),
                        $author->getData('url'),
                        html_entity_decode(strip_tags($author->getLocalizedData('biography'))),
                    ];
                }, $publication->getData('authors')),
                'sectionTitle' => $sectionTitles[$sectionId],
                'language' => $publication->getData('locale'),
                'coverage' => $publication->getLocalizedData('coverage'),
                'rights' => $publication->getLocalizedData('rights'),
                'source' => $publication->getLocalizedData('source'),
                'subjects' => join(', ', $submissionSubjectDao->getSubjects($submission->getCurrentPublication()->getId(), [$submission->getLocale()])[$submission->getLocale()] ?? []),
                'type' => $publication->getLocalizedData('type'),
                'disciplines' => join(', ', $submissionDisciplineDao->getDisciplines($submission->getCurrentPublication()->getId(), [$submission->getLocale()])[$submission->getLocale()] ?? []),
                'keywords' => join(', ', $submissionKeywordDao->getKeywords($submission->getCurrentPublication()->getId(), [$submission->getLocale()])[$submission->getLocale()] ?? []),
                'agencies' => join(', ', $submissionAgencyDao->getAgencies($submission->getCurrentPublication()->getId(), [$submission->getLocale()])[$submission->getLocale()] ?? []),
                'status' => $submission->getStatus() == PKPSubmission::STATUS_QUEUED ? $this->getStageLabel($submission->getStageId()) : __($statusMap[$submission->getStatus()]),
                'url' => $request->url(null, 'workflow', 'access', $submission->getId()),
                'doi' => $submission->getStoredPubId('doi'),
                'dateSubmitted' => $submission->getDateSubmitted(),
                'lastModified' => $submission->getLastModified(),
                'editors' => $editors,
                'decisions' => $editDecisions->toArray(),
            ];
        }

        // Build and display the column headers.
        $columns = [
            __('article.submissionId'),
            __('article.title'),
            __('article.abstract')
        ];

        $authorColumnCount = $editorColumnCount = $decisionColumnCount = 0;
        for ($a = 1; $a <= $maxAuthors; $a++) {
            $columns = array_merge($columns, $authorColumns = [
                __('user.givenName') . ' (' . __('user.role.author') . " ${a})",
                __('user.familyName') . ' (' . __('user.role.author') . " ${a})",
                __('user.orcid') . ' (' . __('user.role.author') . " ${a})",
                __('common.country') . ' (' . __('user.role.author') . " ${a})",
                __('user.affiliation') . ' (' . __('user.role.author') . " ${a})",
                __('user.email') . ' (' . __('user.role.author') . " ${a})",
                __('user.url') . ' (' . __('user.role.author') . " ${a})",
                __('user.biography') . ' (' . __('user.role.author') . " ${a})"
            ]);
            $authorColumnCount = count($authorColumns);
        }

        $columns = array_merge($columns, [
            __('section.title'),
            __('common.language'),
            __('article.coverage'),
            __('submission.rights'),
            __('submission.source'),
            __('common.subjects'),
            __('common.type'),
            __('search.discipline'),
            __('common.keywords'),
            __('submission.supportingAgencies'),
            __('common.status'),
            __('common.url'),
            __('metadata.property.displayName.doi'),
            __('common.dateSubmitted'),
            __('submission.lastModified'),
        ]);

        for ($e = 1; $e <= $maxEditors; $e++) {
            $columns = array_merge($columns, $editorColumns = [
                __('user.givenName') . ' (' . __('user.role.editor') . " ${e})",
                __('user.familyName') . ' (' . __('user.role.editor') . " ${e})",
                __('user.orcid') . ' (' . __('user.role.editor') . " ${e})",
                __('user.email') . ' (' . __('user.role.editor') . " ${e})",
            ]);
            $editorColumnCount = count($editorColumns);
            for ($d = 1; $d <= $maxDecisions; $d++) {
                $columns = array_merge($columns, $decisionColumns = [
                    __('submission.editorDecision') . " ${d} " . ' (' . __('user.role.editor') . " ${e})",
                    __('common.dateDecided') . " ${d} " . ' (' . __('user.role.editor') . " ${e})"
                ]);
                $decisionColumnCount = count($decisionColumns);
            }
        }
        fputcsv($fp, array_values($columns));

        // Display the data rows.
        foreach ($results as $result) {
            $row = [];
            foreach ($result as $column => $value) {
                switch ($column) {
                case 'authors':
                    for ($i = 0; $i < $maxAuthors; $i++) {
                        $row = array_merge($row, $value[$i] ?? array_fill(0, $authorColumnCount, ''));
                    }
                    break;
                case 'editors':
                    $editorIds = array_keys($value);
                    $editorEntries = array_values($value);
                    for ($i = 0; $i < $maxEditors; $i++) {
                        $submissionHasThisEditor = isset($editorEntries[$i]);
                        $row = array_merge($row, $submissionHasThisEditor ? $editorEntries[$i] : array_fill(0, $editorColumnCount, ''));
                        for ($j = 0; $j < $maxDecisions; $j++) {
                            if (!$submissionHasThisEditor) {
                                $row = array_merge($row, array_fill(0, $decisionColumnCount, ''));
                                continue;
                            }

                            $editorId = $editorIds[$i];
                            $latestDecision = $latestDecisionDate = '';
                            $decisionCounter = 0;
                            foreach ($result['decisions'] as $decision) {
                                if ($decision->getData('editorId') != $editorId) {
                                    continue;
                                }
                                if ($j != $decisionCounter++) {
                                    continue;
                                }
                                $latestDecision = $this->getDecisionMessage($decision->getData('decision'));
                                $latestDecisionDate = $decision->getData('dateDecided');
                            }
                            $row = array_merge($row, [$latestDecision, $latestDecisionDate]);
                        }
                    }
                    break;
                case 'decisions':
                    break; // Handled in the 'editors' case
                default: $row[] = $value; // Other columns can be sent as they are.
            }
            }
            fputcsv($fp, $row);
        }

        fclose($fp);
    }

    /**
     * Get stage label
     *
     * @param int $stageId WORKFLOW_STAGE_ID_...
     *
     * @return string
     */
    public function getStageLabel($stageId)
    {
        switch ($stageId) {
            case WORKFLOW_STAGE_ID_SUBMISSION:
                return __('submission.submission');
            case WORKFLOW_STAGE_ID_EXTERNAL_REVIEW:
                return __('submission.review');
            case WORKFLOW_STAGE_ID_EDITING:
                return __('submission.copyediting');
            case WORKFLOW_STAGE_ID_PRODUCTION:
                return __('submission.production');
        }
        return '';
    }

    /**
     * Get decision message
     *
     * @param int $decision SUBMISSION_EDITOR_DECISION_... or SUBMISSION_EDITOR_RECOMMEND_...
     *
     * @return string
     */
    public function getDecisionMessage($decision)
    {
        switch ($decision) {
            case Decision::ACCEPT:
                return __('editor.submission.decision.accept');
            case Decision::PENDING_REVISIONS:
                return __('editor.submission.decision.requestRevisions');
            case Decision::RESUBMIT:
                return __('editor.submission.decision.resubmit');
            case Decision::DECLINE:
                return __('editor.submission.decision.decline');
            case Decision::SEND_TO_PRODUCTION:
                return __('editor.submission.decision.sendToProduction');
            case Decision::EXTERNAL_REVIEW:
                return __('editor.submission.decision.sendExternalReview');
            case Decision::INITIAL_DECLINE:
                return __('editor.submission.decision.decline');
            case Decision::RECOMMEND_ACCEPT:
                return __('editor.submission.recommendation.display', ['recommendation' => __('editor.submission.decision.accept')]);
            case Decision::RECOMMEND_DECLINE:
                return __('editor.submission.recommendation.display', ['recommendation' => __('editor.submission.decision.decline')]);
            case Decision::RECOMMEND_PENDING_REVISIONS:
                return __('editor.submission.recommendation.display', ['recommendation' => __('editor.submission.decision.requestRevisions')]);
            case Decision::RECOMMEND_RESUBMIT:
                return __('editor.submission.recommendation.display', ['recommendation' => __('editor.submission.decision.resubmit')]);
            default:
                return '';
        }
    }
}
