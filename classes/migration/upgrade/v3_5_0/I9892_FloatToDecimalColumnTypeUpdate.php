<?php

/**
 * @file classes/migration/upgrade/v3_5_0/I9892_FloatToDecimalColumnTypeUpdate.php
 *
 * Copyright (c) 2024 Simon Fraser University
 * Copyright (c) 2024 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class I9892_FloatToDecimalColumnTypeUpdate
 *
 * @brief Changes columns types from to float to decimal
 *
 * @see https://laravel.com/docs/11.x/upgrade#floating-point-types
 */

namespace APP\migration\upgrade\v3_5_0;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class I9892_FloatToDecimalColumnTypeUpdate extends \PKP\migration\Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('subscription_types', function (Blueprint $table) {
            $table->decimal('cost', 8, 2)->unsigned()->change();
        });

        Schema::table('completed_payments', function (Blueprint $table) {
            $table->decimal('amount', 8, 2)->unsigned()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('subscription_types', function (Blueprint $table) {
            $table->double('cost')->change();
        });

        Schema::table('completed_payments', function (Blueprint $table) {
            $table->double('amount')->change();
        });
    }
}
