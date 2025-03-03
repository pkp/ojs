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
use PKP\facades\Locale;
use PKP\install\Installer;
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
     * @copydoc \PKP\migration\upgrade\v3_6_0\I1660_ReviewerRecommendations::systemDefineNonRemovableRecommendations()
     */
    protected function systemDefineNonRemovableRecommendations(): array
    {
        return Repo::reviewerRecommendation()->getDefaultRecommendations();
    }

    /**
     * Run the migration.
     */
    public function up(): void
    {
        $this->recommendationInstallMigration->up();

        $this->seedNonRemovableRecommendations($this->systemDefineNonRemovableRecommendations());
    }

    /**
     * Reverse the migration
     */
    public function down(): void
    {
        $this->recommendationInstallMigration->down();
    }

    /**
     * Seed the existing recommendations with context mapping on upgrade
     */
    protected function seedNonRemovableRecommendations(array $nonRemovablerecommendations): void
    {
        if (empty($nonRemovablerecommendations)) {
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
            ->get()
            ->pluck('supportedLocales', $this->recommendationInstallMigration->contextPrimaryKey())
            ->filter()
            ->map(fn ($locales) => json_decode($locales));


        $recommendations = [];

        foreach ($nonRemovablerecommendations as $recommendationValue => $translatableKey) {
            $recommendations[$recommendationValue] = [
                'contextId' => null,
                'value' => $recommendationValue,
                'status' => 1,
                'title' => [],
            ];
        }

        $allContextSupportLocales = $contextSupportedLocales
            ->values()
            ->flatten()
            ->unique()
            ->toArray();

        ReviewerRecommendation::unguard();

        foreach ($allContextSupportLocales as $locale) {
            foreach ($nonRemovablerecommendations as $recommendationValue => $translatableKey) {
                $recommendations[$recommendationValue]['title'][$locale] = Locale::get(
                    $translatableKey,
                    [],
                    $locale
                );
            }
        }

        $contextSupportedLocales->each(
            fn (array $supportedLocales, int $contextId) => collect($recommendations)->each(
                fn (array $recommendation) =>
                    ReviewerRecommendation::create(
                        array_merge($recommendation, [
                            'contextId' => $contextId,
                            'title' => array_intersect_key(
                                $recommendation['title'],
                                array_flip($supportedLocales)
                            )
                        ])
                    )
            )
        );

        ReviewerRecommendation::reguard();
    }
}
