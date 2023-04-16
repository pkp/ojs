<?php

/**
 * @file classes/migration/upgrade/v3_4_0/I6782_MetricsContext.php
 *
 * Copyright (c) 2022 Simon Fraser University
 * Copyright (c) 2022 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class I6782_MetricsContext
 *
 * @brief Migrate context stats data from the old DB table metrics into the new DB table metrics_context.
 */

namespace APP\migration\upgrade\v3_4_0;

class I6782_MetricsContext extends \PKP\migration\upgrade\v3_4_0\I6782_MetricsContext
{
    private const ASSOC_TYPE_CONTEXT = 0x0000100;

    protected function getMetricType(): string
    {
        return 'ojs::counter';
    }

    protected function getContextAssocType(): int
    {
        return self::ASSOC_TYPE_CONTEXT;
    }
}
