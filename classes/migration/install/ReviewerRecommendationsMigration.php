<?php

/**
 * @file classes/migration/install/ReviewerRecommendationsMigration.php
 *
 * Copyright (c) 2025 Simon Fraser University
 * Copyright (c) 2025 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class ReviewerRecommendationsMigration
 *
 * @brief Describe database table structures .
 */

namespace APP\migration\install;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ReviewerRecommendationsMigration extends \PKP\migration\Migration
{
    /**
     * @copydoc \PKP\migration\install\ReviewerRecommendationsMigratio::contextTable()
     */
    public function contextTable(): string
    {
        return 'journals';
    }

    /**
     * @copydoc \PKP\migration\install\ReviewerRecommendationsMigratio::settingTable()
     */
    public function settingTable(): string
    {
        return 'journal_settings';
    }

    /**
     * @copydoc \PKP\migration\install\ReviewerRecommendationsMigratio::contextPrimaryKey()
     */
    public function contextPrimaryKey(): string
    {
        return 'journal_id';
    }

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('reviewer_recommendations', function (Blueprint $table) {
            $table->comment('Review recommendation selected by reviewer at the completion of review assignment');
            $table->bigInteger('recommendation_id')->autoIncrement();

            $table
                ->bigInteger('context_id')
                ->comment('Context for which the recommendation has been made');
            $table
                ->foreign('context_id')
                ->references($this->contextPrimaryKey())
                ->on($this->contextTable())
                ->onDelete('cascade');

            $table->index(['context_id'], 'reviewer_recommendations_context_id');

            $table->unsignedInteger('value');
            $table->unique(['context_id', 'value'], 'reviewer_recommendations_context_unique');

            $table
                ->boolean('status')
                ->default(true)
                ->comment('The status which determine if will be showen in recommendation list');

            $table->timestamps();

        });

        Schema::create('reviewer_recommendation_settings', function (Blueprint $table) {
            $table->comment('Reviewer recommendation settings table to contain multilingual or extra information');

            $table
                ->bigInteger('recommendation_id')
                ->comment('The foreign key mapping of this setting to reviewer_recommendation_id table');

            $table
                ->foreign('recommendation_id')
                ->references('recommendation_id')
                ->on('reviewer_recommendations')
                ->onDelete('cascade');

            $table->index(['recommendation_id'], 'reviewer_recommendation_settings_recommendation_id');
            $table->string('locale', 28)->default('');

            $table->string('setting_name', 255);
            $table->mediumText('setting_value')->nullable();

            $table->unique(['recommendation_id', 'locale', 'setting_name'], 'reviewer_recommendation_settings_unique');
            $table->index(['setting_name', 'locale'], 'reviewer_recommendation_settings_locale_setting_name_index');
        });
    }

    /**
     * Reverse the migration.
     */
    public function down(): void
    {
        Schema::disableForeignKeyConstraints();
        Schema::drop('reviewer_recommendations');
        Schema::drop('reviewer_recommendation_settings');
        Schema::enableForeignKeyConstraints();
    }
}
