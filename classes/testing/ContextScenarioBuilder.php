<?php

/**
 * @file classes/testing/ContextScenarioBuilder.php
 *
 * Copyright (c) 2026 Simon Fraser University
 * Copyright (c) 2026 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class ContextScenarioBuilder
 *
 * @brief OJS scratch-journal scenario (a fresh journal gets its default
 * "Articles" section from the Context::add hook; user section assignments
 * resolve by abbrev).
 */

namespace APP\testing;

use PKP\context\Context;
use PKP\testing\PKPContextScenarioBuilder;

class ContextScenarioBuilder extends PKPContextScenarioBuilder
{
    protected function structureKey(): string
    {
        return 'sections';
    }

    protected function resolveStructureId(Context $context, string $identifier): ?int
    {
        return BootstrapSeeder::findSectionId($context, $identifier);
    }
}
