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
 *        context-build pipeline.
 */

namespace APP\API\v1\_test;

use Illuminate\Support\Facades\Route;
use PKP\API\v1\_test\PKPContextScenarioController;

class JournalScenarioController extends PKPContextScenarioController
{
    public function getGroupRoutes(): void
    {
        Route::post('journal', $this->context(...))
            ->name('test.scenarios.journal');
    }
}
