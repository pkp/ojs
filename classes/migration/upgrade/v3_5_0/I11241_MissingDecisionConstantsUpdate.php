<?php

/**
 * @file classes/migration/upgrade/v3_5_0/I11241_MissingDecisionConstantsUpdate.php
 *
 * Copyright (c) 2025 Simon Fraser University
 * Copyright (c) 2025 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class I11241_MissingDecisionConstantsUpdate
 *
 * @brief Fixed the missing decisions data in stages
 *
 * @see https://github.com/pkp/pkp-lib/issues/11241
 */

namespace APP\migration\upgrade\v3_5_0;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Query\JoinClause;
use PKP\decision\Decision;
use PKP\install\DowngradeNotSupportedException;

class I11241_MissingDecisionConstantsUpdate extends \PKP\migration\upgrade\v3_4_0\I7725_DecisionConstantsUpdate
{
    /**
     * Get the decisions constants mappings
     */
    public function getDecisionMappings(): array
    {
        return [
            // \PKP\decision\Decision::ACCEPT
            /**
             * NOTE : Accept of submission can happen at the
             * 1. submission stage without going through external review phase
             * 2. external review stage after going through external review phase
             */
            [
                'stage_id' => [WORKFLOW_STAGE_ID_SUBMISSION, WORKFLOW_STAGE_ID_EXTERNAL_REVIEW],
                'current_value' => 1,
                'updated_value' => 2,
            ],

            // \PKP\decision\Decision::SKIP_EXTERNAL_REVIEW
            [
                'stage_id' => [WORKFLOW_STAGE_ID_SUBMISSION],
                'current_value' => 19,
                'updated_value' => 17,
            ],

            // \PKP\decision\Decision::BACK_FROM_PRODUCTION
            [
                'stage_id' => [WORKFLOW_STAGE_ID_PRODUCTION],
                'current_value' => 31,
                'updated_value' => 29,
            ],

            // \PKP\decision\Decision::BACK_FROM_COPYEDITING
            [
                'stage_id' => [WORKFLOW_STAGE_ID_EDITING],
                'current_value' => 32,
                'updated_value' => 30,
            ],
        ];
    }

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        parent::up();

        /*
         * Following query aims to fix the issue where in the initial migration
         * forgot the update the value of `EXTERNAL_REVIEW` value from `8` to `3` for stage `WORKFLOW_STAGE_ID_SUBMISSION`
         * 
         * This create a decision inconsistency where the `decision` value remain as `8` which point to `INITIAL_DECLINE`
         * in the `WORKFLOW_STAGE_ID_SUBMISSION` stage instead of `3` which will point to `EXTERNAL_REVIEW`
         * 
         * We also can not update all the decisions in the `WORKFLOW_STAGE_ID_SUBMISSION` stage from `8` to `3`
         * because there can be valid `INITIAL_DECLINE` decision also . Instead we need to update only those
         * decisions for the stage `WORKFLOW_STAGE_ID_SUBMISSION` where the decision is `INITIAL_DECLINE` but 
         * then there is a following decision in the `WORKFLOW_STAGE_ID_EXTERNAL_REVIEW` stage and between these
         * two, there is not such row/s where the initial decline has been reverted or directly sent to external
         * review stage with also explicitly revert the initial decline.
         * 
         * So the following query will run in following manner considering the above conditions
         *    - For each `submission_id`, retrieve the last row (highest edit_decision_id) where `stage_id` = WORKFLOW_STAGE_ID_SUBMISSION 
         *      and decision = Decision::INITIAL_DECLINE, but only if:
         *        - There is at least one subsequent row (higher edit_decision_id) with 
         *          `stage_id` = WORKFLOW_STAGE_ID_EXTERNAL_REVIEW and a non-NULL review_round_id.
         *        - Between the targeted row (`stage_id` = WORKFLOW_STAGE_ID_SUBMISSION, `decision` = Decision::INITIAL_DECLINE) 
         *          and the subsequent `stage_id` = WORKFLOW_STAGE_ID_EXTERNAL_REVIEW row, there must be no row with
         *          `stage_id` = WORKFLOW_STAGE_ID_SUBMISSION and decision in 
         *          (Decision::EXTERNAL_REVIEW, Decision::REVERT_INITIAL_DECLINE)
         * 
         */
        DB::table("edit_decisions as ed1")
            ->select("ed1.*")
            ->joinSub(
                function (Builder $sub) {
                    $sub->from("edit_decisions as ed2")
                        ->select("ed2.*")
                        ->where("ed2.stage_id", WORKFLOW_STAGE_ID_SUBMISSION)
                        ->where("ed2.decision", Decision::INITIAL_DECLINE)
                        ->join("edit_decisions as ed3", function (JoinClause $join) {
                            $join
                                ->on("ed3.submission_id", "=", "ed2.submission_id")
                                ->where("ed3.stage_id", WORKFLOW_STAGE_ID_EXTERNAL_REVIEW)
                                ->whereNotNull("ed3.review_round_id")
                                ->whereColumn(
                                    "ed3.edit_decision_id",
                                    ">",
                                    "ed2.edit_decision_id"
                                );
                        })
                        ->leftJoin("edit_decisions as ed4", function (JoinClause $join) {
                            $join
                                ->on("ed4.submission_id", "=", "ed2.submission_id")
                                ->where("ed4.stage_id", WORKFLOW_STAGE_ID_SUBMISSION)
                                ->whereIn(
                                    "ed4.decision",
                                    [Decision::EXTERNAL_REVIEW, Decision::REVERT_INITIAL_DECLINE]
                                )
                                ->whereColumn(
                                    "ed4.edit_decision_id",
                                    ">",
                                    "ed2.edit_decision_id"
                                )
                                ->whereColumn(
                                    "ed4.edit_decision_id",
                                    "<",
                                    "ed3.edit_decision_id"
                                );
                        })
                        ->whereNull("ed4.edit_decision_id")
                        ->groupBy("ed2.submission_id")
                        ->select(
                            DB::raw(
                                "MAX(ed2.edit_decision_id) as max_edit_decision_id"
                            )
                        );
                },
                "sub",
                "ed1.edit_decision_id",
                "=",
                "sub.max_edit_decision_id"
            )
            ->where("ed1.stage_id", WORKFLOW_STAGE_ID_SUBMISSION)
            ->where("ed1.decision", Decision::INITIAL_DECLINE)
            ->update([
                'decision' => Decision::EXTERNAL_REVIEW,
            ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        throw new DowngradeNotSupportedException();
    }
}
