<?php

/**
 * @file classes/migration/upgrade/v3_4_0/I6241_RequiredGenres.php
 *
 * Copyright (c) 2014-2023 Simon Fraser University
 * Copyright (c) 2000-2023 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class I6241_RequiredGenres
 *
 * @brief Set a required file genre for this app.
 */

namespace APP\migration\upgrade\v3_4_0;

use Illuminate\Support\Facades\DB;

class I6241_RequiredGenres extends \PKP\migration\upgrade\v3_4_0\I6241_RequiredGenres
{
    public function up(): void
    {
        parent::up();

        DB::table('genres')
            ->where('entry_key', 'SUBMISSION') // "Article Text" from genres.xml
            ->update(['required' => 1]);
    }
}
