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

use APP\core\Application;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
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

            // \PKP\decision\Decision::EXTERNAL_REVIEW
            [
                'stage_id' => [WORKFLOW_STAGE_ID_SUBMISSION],
                'current_value' => 8,
                'updated_value' => 3,
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
