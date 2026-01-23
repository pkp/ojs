<?php

/**
 * @file classes/migration/upgrade/v3_6_0/classes/migration/upgrade/v3_6_0/I12046_AssignMachineReadableRecommendationType.php
 * Copyright (c) 2025 Simon Fraser University
 * Copyright (c) 2025 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class I11917_TaskTemplateDueDate.php
 *
 * @brief Migrate task template to include due date functionality.
 */

namespace APP\migration\upgrade\v3_6_0;

use Illuminate\Support\Facades\DB;
use PKP\migration\upgrade\v3_6_0\I12046_AssignMachineReadableRecommendationType as BaseI12046_AssignMachineReadableRecommendationType;

class I12046_AssignMachineReadableRecommendationType extends BaseI12046_AssignMachineReadableRecommendationType
{
    /**
     * @inheritDoc
     */
    public function up(): void
    {
        parent::up();
        $this->addTypeToDefaultRecommendations();
    }

    /**
     * Add machine-readable `type` to default recommendations where the title was never customized.
     *  Go through each context's default recommendations and assign the appropriate `type` if the default recommendation's title was never changed.
     *  Recommendations with customized titles are skipped because there's no guarantee that the customized title conveys the same general meaning as the default one.
     */
    private function addTypeToDefaultRecommendations(): void
    {
        /**
         * Mapping of machine-readable recommendation types to the locale keys of the default recommendations.
         * Each key is a type(see ReviewerRecommendationType.php), and its value is an array of locale keys for associated default recommendations.
         */
        $recommendationTypes = [
            // Accept
            1 => [
                'reviewer.article.decision.accept', // SUBMISSION_REVIEWER_RECOMMENDATION_ACCEPT,
            ],
            // Decline
            2 => [
                'reviewer.article.decision.decline', // SUBMISSION_REVIEWER_RECOMMENDATION_DECLINE
            ],
            // Revisions Requested
            3 => [
                'reviewer.article.decision.pendingRevisions', // SUBMISSION_REVIEWER_RECOMMENDATION_PENDING_REVISIONS,
                'reviewer.article.decision.resubmitHere', // SUBMISSION_REVIEWER_RECOMMENDATION_RESUBMIT_HERE
                'reviewer.article.decision.resubmitElsewhere', // SUBMISSION_REVIEWER_RECOMMENDATION_RESUBMIT_ELSEWHERE
            ],
            // With Comments
            4 => [
                'reviewer.article.decision.seeComments', // SUBMISSION_REVIEWER_RECOMMENDATION_SEE_COMMENTS
            ],
        ];

        DB::table('journals')
            ->orderBy('journal_id')
            ->chunk(100, function ($contexts) use ($recommendationTypes) {
                foreach ($contexts as $context) {
                    $contextId = $context->journal_id;

                    foreach ($recommendationTypes as $type => $localeKeys) {
                        $defaultRecommendationIdsToUpdate = DB::table('reviewer_recommendations as rr')
                            ->join('reviewer_recommendation_settings as rrs_default', 'rr.reviewer_recommendation_id', '=', 'rrs_default.reviewer_recommendation_id')
                            ->where('rrs_default.setting_name', 'defaultTranslationKey')
                            ->whereIn('rrs_default.setting_value', $localeKeys)
                            ->where('rr.context_id', $contextId)
                            ->select('rr.reviewer_recommendation_id as id')
                            ->get()
                            ->pluck('id');

                        if (!empty($defaultRecommendationIdsToUpdate)) {
                            DB::table('reviewer_recommendations')
                                ->whereIn('reviewer_recommendation_id', $defaultRecommendationIdsToUpdate)
                                ->update(['type' => $type]);
                        }
                    }
                }
            });
    }
}
