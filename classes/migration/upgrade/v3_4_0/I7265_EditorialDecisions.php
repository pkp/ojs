<?php

/**
 * @file classes/migration/upgrade/v3_4_0/I7265_EditorialDecisions.php
 *
 * Copyright (c) 2014-2022 Simon Fraser University
 * Copyright (c) 2000-2022 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class I7265_EditorialDecisions
 *
 * @brief Database migrations for editorial decision refactor.
 */

namespace APP\migration\upgrade\v3_4_0;

use Illuminate\Support\Facades\DB;

class I7265_EditorialDecisions extends \PKP\migration\upgrade\v3_4_0\I7265_EditorialDecisions
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        parent::up();
        $this->upNewDecisions();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        parent::down();
        $this->downNewDecisions();
    }

    /**
     * Change decisions taken in submission stage
     *
     * APP\decision\Decision::ACCEPT = 1
     * APP\decision\Decision::REVERT_DECLINE = 17
     *
     * When these decisions have been recorded in
     * the submission stage they must become become:
     *
     * APP\decision\Decision::SKIP_EXTERNAL_REVIEW = 19
     * APP\decision\Decision::REVERT_INITIAL_DECLINE = 18
     *
     * In 3.3 and earlier, the decision constants were global
     * and named:
     *
     * Decision::ACCEPT
     * Decision::REVERT_DECLINE
     */
    public function upNewDecisions()
    {
        DB::table('edit_decisions')
            ->where('stage_id', '=', 1) // WORKFLOW_STAGE_ID_SUBMISSION
            ->where('decision', '=', 1) // APP\decision\Decision::ACCEPT
            ->update([
                'decision' => 19, // APP\decision\Decision::SKIP_EXTERNAL_REVIEW
            ]);
        DB::table('edit_decisions')
            ->where('stage_id', '=', 1) // WORKFLOW_STAGE_ID_SUBMISSION
            ->where('decision', '=', 17) // APP\decision\Decision::REVERT_DECLINE
            ->update([
                'decision' => 18, // APP\decision\Decision::REVERT_INITIAL_DECLINE
            ]);
    }

    /**
     * Reverse the decision type changes
     *
     * @see self::upNewSubmissionDecisions()
     */
    public function downNewDecisions()
    {
        DB::table('edit_decisions')
            ->where('stage_id', '=', 1) // WORKFLOW_STAGE_ID_SUBMISSION
            ->where('decision', '=', 19) // APP\decision\Decision::ACCEPT
            ->update([
                'decision' => 1, // APP\decision\Decision::ACCEPT
            ]);
        DB::table('edit_decisions')
            ->where('stage_id', '=', 1) // WORKFLOW_STAGE_ID_SUBMISSION
            ->where('decision', '=', 18) // APP\decision\Decision::REVERT_INITIAL_DECLINE
            ->update([
                'decision' => 17, // APP\decision\Decision::REVERT_DECLINE
            ]);
    }

    protected function getContextTable(): string
    {
        return 'journals';
    }

    protected function getContextSettingsTable(): string
    {
        return 'journal_settings';
    }

    protected function getContextIdColumn(): string
    {
        return 'journal_id';
    }
}
