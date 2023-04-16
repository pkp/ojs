<?php

/**
 * @file classes/migration/upgrade/v3_4_0/I7513_DoiSettings.php
 *
 * Copyright (c) 2014-2023 Simon Fraser University
 * Copyright (c) 2000-2023 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class I7513_DoiSettings
 *
 * @brief Database migrations for DOI settings refactor.
 */

namespace APP\migration\upgrade\v3_4_0;

use Illuminate\Support\Facades\DB;

class I7513_DoiSettings extends \PKP\migration\Migration
{
    /**
     * @inheritDoc
     */
    public function up(): void
    {
        DB::table('plugin_settings')
            ->where('plugin_name', '=', 'crossrefexportplugin')
            ->update(['plugin_name' => 'crossrefplugin']);

        DB::table('plugin_settings')
            ->where('plugin_name', '=', 'dataciteexportplugin')
            ->update(['plugin_name' => 'dataciteplugin']);
    }

    /**
     * @inheritDoc
     */
    public function down(): void
    {
        DB::table('plugin_settings')
            ->where('plugin_name', '=', 'crossrefplugin')
            ->whereNot('setting_name', '=', 'enabled')
            ->update(['plugin_name' => 'crossrefexportplugin']);

        DB::table('plugin_settings')
            ->where('plugin_name', '=', 'dataciteplugin')
            ->whereNot('setting_name', '=', 'enabled')
            ->update(['plugin_name' => 'dataciteexportplugin']);
    }
}
