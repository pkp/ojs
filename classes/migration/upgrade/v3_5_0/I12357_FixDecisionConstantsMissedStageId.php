<?php

/**
 * @file classes/migration/upgrade/v3_5_0/I12357_FixDecisionConstantsMissedStageId.php
 *
 * Copyright (c) 2025 Simon Fraser University
 * Copyright (c) 2025 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class I12357_FixDecisionConstantsMissedStageId
 *
 * @brief Fix ACCEPT decisions at PRODUCTION stage missed by previous migrations.
 *        OJS 3.3 allowed ACCEPT at any stage via the "change decision" UI,
 *        but I7725/I11241 only mapped ACCEPT at SUBMISSION, EXTERNAL_REVIEW, and EDITING.
 *
 * @see https://github.com/pkp/pkp-lib/issues/12357
 * @see https://github.com/pkp/pkp-lib/issues/11876
 */

namespace APP\migration\upgrade\v3_5_0;

use APP\core\Application;
use Illuminate\Support\Facades\DB;
use PKP\install\DowngradeNotSupportedException;

class I12357_FixDecisionConstantsMissedStageId extends \PKP\migration\upgrade\v3_4_0\I7725_DecisionConstantsUpdate
{
    /**
     * Get the decisions constants mappings
     */
    public function getDecisionMappings(): array
    {
        // Only ACCEPT (1→2) is targeted here. This is collision-safe because
        // no other OJS migration mapping produces decision=1 as output
        // (INTERNAL_REVIEW=1 is OMP-only and not registered in OJS).
        return [
            ['current_value' => 1, 'updated_value' => 2], // ACCEPT
        ];
    }

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // If the first installed version is 3.4.0+, no pre-3.4 legacy data exists
        $firstInstalledVersion = DB::table('versions')
            ->where('product', Application::get()->getName())
            ->where('product_type', 'core')
            ->orderBy('date_installed')
            ->first();

        if ($firstInstalledVersion->major > 3 || ($firstInstalledVersion->major == 3 && $firstInstalledVersion->minor >= 4)) {
            return;
        }

        // Run the parent's up() which uses configureUpdatedAtColumn(),
        // iterates mappings with whereNull('updated_at'), and removeUpdatedAtColumn().
        // Already-migrated ACCEPT rows have decision=2 (not 1), so
        // WHERE decision=1 only matches stranded rows.
        parent::up();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        throw new DowngradeNotSupportedException();
    }
}
