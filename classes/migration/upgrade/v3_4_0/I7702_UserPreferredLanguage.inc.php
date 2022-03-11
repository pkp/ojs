<?php

/**
 * @file classes/migration/upgrade/v3_4_0/I7702_UserPreferredLanguage.inc.php
 *
 * Copyright (c) 2014-2022 Simon Fraser University
 * Copyright (c) 2000-2022 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class I7702_UserPreferredLanguage
 * @brief Describe add column preferred_language for DB table user.
 */

namespace APP\migration\upgrade\v3_4_0;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use PKP\migration\Migration;

class I7702_UserPreferredLanguage extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (! Schema::hasColumn('users', 'preferred_language')) {
            Schema::table('users', function (Blueprint $table) {
                $table->addColumn('string', 'preferred_language', ['length' => 10])->nullable();
            });
        }
    }

    /**
     * Reverse the downgrades
     *
     */
    public function down(): void
    {
    }
}

if (!PKP_STRICT_MODE) {
    class_alias('\APP\migration\upgrade\v3_4_0\I7702_UserPreferredLanguage', '\I7702_UserPreferredLanguage');
}
