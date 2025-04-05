<?php

/**
 * @file classes/migration/upgrade/v3_6_0/I1660_ReviewerRecommendations.php
 *
 * Copyright (c) 2025 Simon Fraser University
 * Copyright (c) 2025 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class I1660_ReviewerRecommendations.php
 *
 * @brief Upgrade migration to add recommendations
 *
 */

namespace APP\migration\upgrade\v3_6_0;

use Illuminate\Support\Facades\DB;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use PKP\facades\Locale;
use PKP\install\DowngradeNotSupportedException;
use PKP\submission\reviewer\recommendation\ReviewerRecommendation;

class I1660_ReviewerRecommendations extends \APP\migration\install\ReviewerRecommendationsMigration
{
    /**
     * Run the migration.
     */
    public function up(): void
    {
        Schema::table('review_assignments', function (Blueprint $table) {
            $table->bigInteger('reviewer_recommendation_id')->nullable()->after('reviewer_id');
            $table
                ->foreign('reviewer_recommendation_id')
                ->references('reviewer_recommendation_id')
                ->on('reviewer_recommendations')
                ->onDelete('set null');
            $table->index(['reviewer_recommendation_id'], 'review_assignments_recommendation_id');
        });

        $this->seedDefaultRecommendations();

        Schema::table('review_assignments', function (Blueprint $table) {
            $table->dropColumn('recommendation');
        });
    }

    /**
     * Reverse the migration
     */
    public function down(): void
    {
        throw new DowngradeNotSupportedException();
    }

    /**
     * Get default recommendation seed data mapped as value => localeKey
     */
    protected function getDefaultRecommendationsToMappedValue(): array
    {
        return [
            1 => 'reviewer.article.decision.accept', // SUBMISSION_REVIEWER_RECOMMENDATION_ACCEPT
            2 => 'reviewer.article.decision.pendingRevisions', // SUBMISSION_REVIEWER_RECOMMENDATION_PENDING_REVISIONS
            3 => 'reviewer.article.decision.resubmitHere', // SUBMISSION_REVIEWER_RECOMMENDATION_RESUBMIT_HERE
            4 => 'reviewer.article.decision.resubmitElsewhere', // SUBMISSION_REVIEWER_RECOMMENDATION_RESUBMIT_ELSEWHERE
            5 => 'reviewer.article.decision.decline', // SUBMISSION_REVIEWER_RECOMMENDATION_DECLINE
            6 => 'reviewer.article.decision.seeComments', // SUBMISSION_REVIEWER_RECOMMENDATION_SEE_COMMENTS
        ];
    }

    /**
     * Seed the existing recommendations with context mapping on upgrade
     */
    protected function seedDefaultRecommendations(): void
    {
        $defaultRecommendations = $this->getDefaultRecommendationsToMappedValue();

        $contextSupportedLocales = DB::table($this->contextTable())
            ->select($this->contextPrimaryKey())
            ->addSelect([
                'supportedLocales' => DB::table($this->settingTable())
                    ->select('setting_value')
                    ->whereColumn(
                        $this->contextPrimaryKey(),
                        "{$this->contextTable()}.{$this->contextPrimaryKey()}"
                    )
                    ->where('setting_name', 'supportedLocales')
                    ->limit(1)
            ])
            ->orderBy($this->contextPrimaryKey())
            ->get()
            ->pluck('supportedLocales', $this->contextPrimaryKey())
            ->filter()
            ->map(fn ($locales) => json_decode($locales));


        $recommendations = [];

        foreach ($defaultRecommendations as $recommendationValue => $translatableKey) {
            $recommendations[$recommendationValue] = [
                'contextId' => null,
                'status' => 1,
                'title' => [],
                'defaultTranslationKey' => $translatableKey,
            ];
        }

        $allContextSupportLocales = $contextSupportedLocales
            ->values()
            ->flatten()
            ->unique()
            ->toArray();

        foreach ($allContextSupportLocales as $locale) {
            foreach ($defaultRecommendations as $recommendationValue => $translatableKey) {
                $recommendations[$recommendationValue]['title'][$locale] = Locale::get(
                    $translatableKey,
                    [],
                    $locale
                );
            }
        }

        $contextIdToSubmissionIdsMap = $this->getContextIdToSubmissionsMap();

        foreach ($contextSupportedLocales->toArray() as $contextId => $contextSupportedLocales) {

            // first create the default recommendations no matter if the context has submissions or not
            $reviewerRecommendationIds = $this->createDefaultRecommendation(
                $contextId,
                $recommendations,
                $contextSupportedLocales
            );

            // If the context has no submission, then nothing to update and continue for next context
            if (empty($contextIdToSubmissionIdsMap[$contextId] ?? [])) {
                continue;
            }

            $caseQuery = $this->constructCaseQuery($defaultRecommendations, $reviewerRecommendationIds);
            $submissionIds = implode(',', $contextIdToSubmissionIdsMap[$contextId]);

            DB::statement(
                "UPDATE review_assignments 
                SET reviewer_recommendation_id = ({$caseQuery}) 
                WHERE submission_id IN ({$submissionIds})",
            );
        }
    }

    /**
     * Construct a query case string to update/set `reviewer_recommendation_id` based on recommendation value
     */
    protected function constructCaseQuery(array $defaultRecommendations, array $reviewerRecommendationIds): string
    {
        $caseQuery = 'CASE ';

        foreach ($defaultRecommendations as $value => $translatableKey) {
            $caseQuery = $caseQuery . "WHEN recommendation = {$value} THEN {$reviewerRecommendationIds[$value]} ";
        }

        return $caseQuery . 'END';
    }

    /**
     * Store/Create the default pre existing recommendations for the given context
     */
    protected function createDefaultRecommendation(
        int $contextId,
        array $recommendations,
        array $contextSupportedLocales
    ): array
    {
        $reviewerRecommendationIds = [];

        foreach ($recommendations as $recommendationValue => $recommendation) {
            $reviewerRecommendationIds[$recommendationValue] = ReviewerRecommendation::create(
                array_merge($recommendation, [
                    'contextId' => $contextId,
                    'title' => array_intersect_key(
                        $recommendation['title'],
                        array_flip($contextSupportedLocales)
                    ),
                ])
            )->id;
        }

        return $reviewerRecommendationIds;
    }

    /**
     * Get a map to context_id to submission_id as
     * [
     *     context_id_1 => [submission_id, submission_id, ...],
     *     context_id_2 => [submission_id, submission_id, ...],
     * ]
     */
    protected function getContextIdToSubmissionsMap(): array
    {
        $submissions = DB::table('submissions')
            ->select(['context_id', 'submission_id'])
            ->orderBy('context_id')
            ->get();

        return $submissions
            ->groupBy('context_id')
            ->mapWithKeys(function ($group, $contextId) {
                return [
                    $contextId => $group->pluck('submission_id')
                ];
            })
            ->toArray();
    }
}
