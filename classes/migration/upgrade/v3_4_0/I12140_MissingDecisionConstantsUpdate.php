<?php

/**
 * @file classes/migration/upgrade/v3_4_0/I12140_MissingDecisionConstantsUpdate.php
 *
 * Copyright (c) 2025 Simon Fraser University
 * Copyright (c) 2025 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class I12140_MissingDecisionConstantsUpdate
 *
 * @brief Fixed the missing decisions data in stages
 *
 * @see https://github.com/pkp/pkp-lib/issues/12140
 */

namespace APP\migration\upgrade\v3_4_0;

use APP\core\Application;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use PKP\install\DowngradeNotSupportedException;

class I12140_MissingDecisionConstantsUpdate extends \PKP\migration\upgrade\v3_4_0\I7725_DecisionConstantsUpdate
{
    /**
     * Get the decisions constants mappings
     */
    public function getDecisionMappings(): array
    {
        return [
            // \PKP\decision\Decision::ACCEPT
            // PRODUCTION added: OJS 3.3 allowed ACCEPT at any stage via "change decision" UI
            // See https://github.com/pkp/pkp-lib/issues/12357
            [
                'stage_id' => [WORKFLOW_STAGE_ID_SUBMISSION, WORKFLOW_STAGE_ID_EXTERNAL_REVIEW, WORKFLOW_STAGE_ID_PRODUCTION],
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
        // If the first installed version is 3.4.0-*
        // nothing to do and return
        $firstInstalledVersion = DB::table('versions')
            ->where('product', Application::get()->getName())
            ->where('product_type', 'core')
            ->orderBy('date_installed')
            ->first();

        if ($firstInstalledVersion->major == 3 && $firstInstalledVersion->minor == 4) {
            return;
        }

        // Get the current version from which is the upgrading is happening
        $currentVersion = DB::table('versions')
            ->where('product', Application::get()->getName())
            ->where('product_type', 'core')
            ->where('current', 1)
            ->first();

        // If NOT upgrading from 3.4.0-*, then fixed migration \APP\migration\upgrade\v3_4_0\I7725_DecisionConstantsUpdate
        // have the new fix in place and rest of the code does not need to execute
        if ($currentVersion->major == 3 && $currentVersion->minor != 4) {
            return;
        }

        // Upgrading from a 3.4.0-*
        // Need to figure out the first installed date of 3.4.0-*
        // Then need to update the decisions made before the first version of 3.4.0-* installed
        $firstVersionOf34 = DB::table('versions')
            ->where('product', Application::get()->getName())
            ->where('product_type', 'core')
            ->where('major', 3)
            ->where('minor', 4)
            ->orderBy('date_installed')
            ->first();

        $this->configureUpdatedAtColumn();

        collect($this->getDecisionMappings())
            ->each(
                fn ($decisionMapping) => DB::table('edit_decisions')
                    ->when(
                        isset($decisionMapping['stage_id']) && !empty($decisionMapping['stage_id']),
                        fn ($query) => $query->whereIn('stage_id', $decisionMapping['stage_id'])
                    )
                    ->where('decision', $decisionMapping['current_value'])
                    ->whereNull('updated_at')
                    ->where('date_decided', '<', $firstVersionOf34->date_installed)
                    ->update([
                        'decision' => $decisionMapping['updated_value'],
                        'updated_at' => Carbon::now(),
                    ])
            );

        // \PKP\decision\Decision::EXTERNAL_REVIEW (8→3) — special handling
        //
        // After the buggy I7725 ran, both stranded EXTERNAL_REVIEW rows and
        // correctly-migrated INITIAL_DECLINE rows share decision=8, stage_id=1.
        // They are indistinguishable from edit_decisions alone.
        //
        // Two-layer disambiguation:
        //   1. whereExists(review_rounds at EXTERNAL_REVIEW stage) — the submission
        //      must have actually reached external review
        //   2. whereNotExists(later decision=8 at same submission/stage) — only
        //      the MOST RECENT decision=8 per submission is the EXTERNAL_REVIEW;
        //      all earlier ones were INITIAL_DECLINE
        //
        // Why "most recent" instead of checking for REVERT_INITIAL_DECLINE:
        // OJS 3.3 had a loose workflow that allowed editors to decline a submission
        // and then send it to review WITHOUT recording a REVERT_INITIAL_DECLINE.
        // The "most recent" check handles ALL cases: with revert, without revert,
        // and multiple decline cycles.

        // Collect the IDs of the MOST RECENT decision=8 per submission
        // (the EXTERNAL_REVIEW row). Done as a separate SELECT because MySQL
        // does not allow referencing the target table in an UPDATE subquery.
        $externalReviewIds = DB::table('edit_decisions')
            ->where('edit_decisions.stage_id', WORKFLOW_STAGE_ID_SUBMISSION)
            ->where('edit_decisions.decision', 8)
            ->whereNull('edit_decisions.updated_at')
            ->where('edit_decisions.date_decided', '<', $firstVersionOf34->date_installed)
            ->whereExists(function ($query) {
                $query->select(DB::raw(1))
                    ->from('review_rounds')
                    ->whereColumn('review_rounds.submission_id', 'edit_decisions.submission_id')
                    ->where('review_rounds.stage_id', WORKFLOW_STAGE_ID_EXTERNAL_REVIEW);
            })
            ->whereNotExists(function ($query) use ($firstVersionOf34) {
                // If a LATER decision=8 at stage_id=1 exists for the same submission,
                // then this row was an earlier INITIAL_DECLINE — not the final
                // EXTERNAL_REVIEW that triggered the review
                $query->select(DB::raw(1))
                    ->from('edit_decisions as later_ed')
                    ->whereColumn('later_ed.submission_id', 'edit_decisions.submission_id')
                    ->where('later_ed.decision', 8)
                    ->where('later_ed.stage_id', WORKFLOW_STAGE_ID_SUBMISSION)
                    ->whereColumn('later_ed.date_decided', '>', 'edit_decisions.date_decided')
                    ->where('later_ed.date_decided', '<', $firstVersionOf34->date_installed);
            })
            // Layer 3 (idempotency): if this submission ALREADY has a pre-3.4
            // decision=3 at SUBMISSION, its EXTERNAL_REVIEW was migrated by a
            // prior application of this logic. Skip it so a genuine INITIAL_DECLINE
            // is not relabeled. The pre-3.4 date filter keeps a legitimate post-3.4
            // EXTERNAL_REVIEW (decision=3) from tripping this.
            ->whereNotExists(function ($query) use ($firstVersionOf34) {
                $query->select(DB::raw(1))
                    ->from('edit_decisions as existing_er')
                    ->whereColumn('existing_er.submission_id', 'edit_decisions.submission_id')
                    ->where('existing_er.decision', 3)
                    ->where('existing_er.stage_id', WORKFLOW_STAGE_ID_SUBMISSION)
                    ->where('existing_er.date_decided', '<', $firstVersionOf34->date_installed);
            })
            ->pluck('edit_decision_id');

        $externalReviewIds
            ->chunk(1000)
            ->each(function ($chunk) {
                DB::table('edit_decisions')
                    ->whereIn('edit_decision_id', $chunk->all())
                    ->update([
                        'decision' => 3,
                        'updated_at' => Carbon::now(),
                    ]);
            });

        $this->removeUpdatedAtColumn();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        throw new DowngradeNotSupportedException();
    }
}
