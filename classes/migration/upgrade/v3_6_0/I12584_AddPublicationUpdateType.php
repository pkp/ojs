<?php

/**
 * @file classes/migration/upgrade/v3_6_0/I12584_AddPublicationUpdateType.php
 *
 * Copyright (c) 2026 Simon Fraser University
 * Copyright (c) 2026 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class I12584_AddPublicationUpdateType
 *
 * @brief Add the update_type column to the publications table.
 */

namespace APP\migration\upgrade\v3_6_0;

use APP\publication\enums\UpdateType;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use PKP\migration\Migration;

class I12584_AddPublicationUpdateType extends Migration
{
    public function up(): void
    {
        Schema::table('publications', function (Blueprint $table) {
            $table->enum('update_type', array_column(UpdateType::cases(), 'value'))
                ->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('publications', function (Blueprint $table) {
            $table->dropColumn('update_type');
        });
    }
}
