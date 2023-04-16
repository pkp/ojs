<?php

/**
 * @file classes/migration/upgrade/v3_4_0/I8151_ExtendSettingValues.php
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2000-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class I8151_ExtendSettingValues
 *
 * @brief Describe upgrade/downgrade operations for extending TEXT columns to MEDIUMTEXT
 */

namespace APP\migration\upgrade\v3_4_0;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class I8151_ExtendSettingValues extends \PKP\migration\Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('section_settings', function (Blueprint $table) {
            $table->mediumText('setting_value')->nullable()->change();
        });

        Schema::table('issue_settings', function (Blueprint $table) {
            $table->mediumText('setting_value')->nullable()->change();
        });

        Schema::table('issue_galley_settings', function (Blueprint $table) {
            $table->mediumText('setting_value')->nullable()->change();
        });

        Schema::table('publication_galley_settings', function (Blueprint $table) {
            $table->mediumText('setting_value')->nullable()->change();
        });

        Schema::table('subscription_type_settings', function (Blueprint $table) {
            $table->mediumText('setting_value')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // This downgrade is intentionally not implemented. Changing MEDIUMTEXT back to TEXT
        // may result in data truncation. Having MEDIUMTEXT in place of TEXT in an otherwise
        // downgraded database will not have side-effects.
    }
}
