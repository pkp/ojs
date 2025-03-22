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
 * @brief Upgrade migration add recommendations
 *
 */

namespace APP\migration\upgrade\v3_6_0;

use APP\facades\Repo;
use APP\migration\install\ReviewerRecommendationsMigration;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use PKP\facades\Locale;
use PKP\install\Installer;
use PKP\install\DowngradeNotSupportedException;
use PKP\submission\reviewer\recommendation\ReviewerRecommendation;

class I1660_ReviewerRecommendations extends \PKP\migration\Migration
{
    protected ReviewerRecommendationsMigration $recommendationInstallMigration;

    /**
     * Constructor
     */
    public function __construct(Installer $installer, array $attributes)
    {
        $this->recommendationInstallMigration = new ReviewerRecommendationsMigration(
            $installer,
            $attributes
        );

        parent::__construct($installer, $attributes);
    }

    /**
     * Run the migration.
     */
    public function up(): void
    {
        $this->recommendationInstallMigration->up();

        Schema::table('review_assignments', function (Blueprint $table) {
            $table->bigInteger('recommendation_id')->nullable()->after('reviewer_id');
            $table
                ->foreign('recommendation_id')
                ->references('recommendation_id')
                ->on('reviewer_recommendations')
                ->onDelete('set null');
            $table->index(['recommendation_id'], 'review_assignments_recommendation_id');
        });

        $this->seedDefaultRecommendations(Repo::reviewerRecommendation()->getDefaultRecommendations());

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
     * Seed the existing recommendations with context mapping on upgrade
     */
    protected function seedDefaultRecommendations(array $defaultRecommendations): void
    {
        if (empty($defaultRecommendations)) {
            return;
        }

        $contextSupportedLocales = DB::table($this->recommendationInstallMigration->contextTable())
            ->select($this->recommendationInstallMigration->contextPrimaryKey())
            ->addSelect([
                'supportedLocales' => DB::table($this->recommendationInstallMigration->settingTable())
                    ->select('setting_value')
                    ->whereColumn(
                        $this->recommendationInstallMigration->contextPrimaryKey(),
                        "{$this->recommendationInstallMigration->contextTable()}.{$this->recommendationInstallMigration->contextPrimaryKey()}"
                    )
                    ->where('setting_name', 'supportedLocales')
                    ->limit(1)
            ])
            ->orderBy($this->recommendationInstallMigration->contextPrimaryKey())
            ->get()
            ->pluck('supportedLocales', $this->recommendationInstallMigration->contextPrimaryKey())
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
            $recommendationIds = $this->createDefaultRecommendation(
                $contextId,
                $recommendations,
                $contextSupportedLocales
            );

            // If the context has no submission, then nothing to update and continue for next context
            if (empty($contextIdToSubmissionIdsMap[$contextId] ?? [])) {
                continue;
            }

            $caseQuery = $this->constructCaseQuery($defaultRecommendations, $recommendationIds);
            $submissionIds = implode(',', $contextIdToSubmissionIdsMap[$contextId]);

            DB::statement(
                "UPDATE `review_assignments` 
                SET `recommendation_id` = ({$caseQuery}) 
                WHERE `submission_id` IN ({$submissionIds})",
            );
        }
    }

    /**
     * Construct a query case string to update/set recommendation_id based on recommendation value
     */
    protected function constructCaseQuery(array $defaultRecommendations, array $recommendationIds): string
    {
        $caseQuery = 'CASE ';

        foreach ($defaultRecommendations as $value => $translatableKey) {
            $caseQuery = $caseQuery . "WHEN `recommendation` = {$value} THEN {$recommendationIds[$value]} ";
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
        $recommendationIds = [];

        foreach ($recommendations as $recommendationValue => $recommendation) {
            $recommendationIds[$recommendationValue] = ReviewerRecommendation::create(
                array_merge($recommendation, [
                    'contextId' => $contextId,
                    'title' => array_intersect_key(
                        $recommendation['title'],
                        array_flip($contextSupportedLocales)
                    ),
                ])
            )->id;
        }

        return $recommendationIds;
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
