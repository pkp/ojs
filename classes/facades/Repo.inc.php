<?php

/**
 * @file classes/facade/Repo.inc.php
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2000-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class Repo
 *
 * @brief Extends the base Repo facade with any overrides for OJS
 */

namespace APP\facades;

use Illuminate\Support\Facades\App;

class Repo extends \PKP\facades\Repo
{
    public static function publication(): \APP\publication\Repository
    {
        return App::make(\APP\publication\Repository::class);
    }

    public static function submission(): \APP\submission\Repository
    {
        return App::make(\APP\submission\Repository::class);
    }
}
