<?php

/**
 * @file api/v1/_test/JournalScenarioController.php
 *
 * Copyright (c) 2026 Simon Fraser University
 * Copyright (c) 2026 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class JournalScenarioController
 *
 * @ingroup api_v1_test
 *
 * @brief OJS subclass of PKPContextScenarioController. Registers
 *        POST /api/v1/_test/scenarios/journal which invokes the shared
 *        context-build pipeline plus OJS-specific extensions.
 *
 * Overrides `context()` to pull OJS-only concepts (issues) into the
 * journal scratch build. Issues are OJS-specific and never reach OMP/OPS,
 * so keeping them off the shared controller avoids polluting the
 * cross-app schema.
 */

namespace APP\API\v1\_test;

use Illuminate\Support\Facades\Route;
use PKP\API\v1\_test\PKPContextScenarioController;
use PKP\testing\bootstrap\Processor\IssueProcessor;

class JournalScenarioController extends PKPContextScenarioController
{
    public function getGroupRoutes(): void
    {
        Route::post('journal', $this->context(...))
            ->name('test.scenarios.journal');
    }

    /**
     * OJS-specific post-processing hook fired from the shared
     * PKPContextScenarioController::context() flow. Keeps the shared
     * controller's context() intact while adding OJS-only steps (issues
     * seeding) inside the same transaction.
     *
     * @see PKPContextScenarioController::afterContextCreated()
     */
    protected function afterContextCreated(array $spec, int $contextId): void
    {
        if (!empty($spec['issues']) && is_array($spec['issues'])) {
            (new IssueProcessor())->run($contextId, $spec['issues']);
        }
    }
}
