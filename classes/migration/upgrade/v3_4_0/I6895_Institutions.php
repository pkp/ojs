<?php

/**
 * @file classes/migration/upgrade/v3_4_0/I6895_Institutions.php
 *
 * Copyright (c) 2022 Simon Fraser University
 * Copyright (c) 2022 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class I6895_Institutions
 *
 * @brief Migrate institution data from subscriptions into the new institution data model.
 */

namespace APP\migration\upgrade\v3_4_0;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use PKP\install\DowngradeNotSupportedException;
use PKP\migration\Migration;

class I6895_Institutions extends Migration
{
    /**
     * Run the migration.
     */
    public function up(): void
    {
        // Requires that institution tables are already there
        // Add the new column institution_id to the table institutional_subscriptions
        Schema::table('institutional_subscriptions', function (Blueprint $table) {
            $table->bigInteger('institution_id')->default(0);
        });

        // pkp/pkp-lib#6895 Migrate all institutions from institutional subscriptions into new databases
        $institutionalSubscriptions = DB::table('institutional_subscriptions AS i')
            ->select('i.institutional_subscription_id', 'i.subscription_id', 'i.institution_name', 's.journal_id', 'j.primary_locale')
            ->join('subscriptions AS s', 's.subscription_id', '=', 'i.subscription_id')
            ->join('journals AS j', 'j.journal_id', '=', 's.journal_id')
            ->get();

        foreach ($institutionalSubscriptions as $institutionalSubscription) {
            $institutionId = DB::table('institutions')->insertGetId(['context_id' => $institutionalSubscription->journal_id], 'institution_id');
            if ($institutionId) {
                DB::table('institution_settings')->insert(['institution_id' => $institutionId, 'setting_name' => 'name', 'setting_value' => $institutionalSubscription->institution_name, 'locale' => $institutionalSubscription->primary_locale]);

                $affected = DB::table('institutional_subscriptions')
                    ->where('institutional_subscription_id', $institutionalSubscription->institutional_subscription_id)
                    ->update(['institution_id' => $institutionId]);

                // Get IP ranges
                $ipRanges = DB::table('institutional_subscription_ip')
                    ->select('ip_string', 'ip_start', 'ip_end')
                    ->where('subscription_id', '=', $institutionalSubscription->subscription_id)
                    ->get();
                foreach ($ipRanges as $ipRange) {
                    DB::table('institution_ip')->insert(['institution_id' => $institutionId, 'ip_string' => $ipRange->ip_string, 'ip_start' => $ipRange->ip_start, 'ip_end' => $ipRange->ip_end]);
                }
            }
        }

        // Drop the table institutional_subscription_ip
        Schema::drop('institutional_subscription_ip');

        // Drop column institution_name form institutional_subscriptions
        Schema::table('institutional_subscriptions', function (Blueprint $table) {
            $table->dropColumn('institution_name');
        });

        // Create the foreign key constraint (now that the values are correct and match the IDs in the parent table)
        Schema::table('institutional_subscriptions', function (Blueprint $table) {
            $table->foreign('institution_id')->references('institution_id')->on('institutions')->onDelete('cascade');
            $table->index(['institution_id'], 'institutional_subscriptions_institution_id');
        });

        DB::statement('ALTER TABLE institutional_subscriptions ALTER COLUMN institution_id DROP DEFAULT');
    }

    /**
     * Reverse the downgrades
     *
     * @throws DowngradeNotSupportedException
     */
    public function down(): void
    {
        throw new DowngradeNotSupportedException();
    }
}
