<?php

/**
 * @file classes/migration/upgrade/v3_4_0/I7186_OpenAccessNotification.php
 *
 * Copyright (c) 2014-2023 Simon Fraser University
 * Copyright (c) 2000-2023 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class I7186_OpenAccessNotification
 *
 * @brief Migrate the user's open access subscription setting from OJS 2.4.8 to
 *   the notification subscriptions table.
 */

namespace APP\migration\upgrade\v3_4_0;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use PKP\install\DowngradeNotSupportedException;
use PKP\migration\Migration;

class I7186_OpenAccessNotification extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $userIds = DB::table('users')
            ->whereNotIn('user_id', function (Builder $query) {
                $query->select('user_id')
                    ->from('user_settings')
                    ->where('setting_name', 'openAccessNotification')
                    ->where('setting_value', '1');
            })
            ->pluck('user_id');

        $contextIds = DB::table('journals')->pluck('journal_id');

        $rows = [];
        foreach ($userIds as $userId) {
            foreach ($contextIds as $contextId) {
                $rows[] = [
                    'setting_name' => 'blocked_emailed_notification',
                    'setting_value' => 50331659, // Notification::NOTIFICATION_TYPE_OPEN_ACCESS
                    'user_id' => $userId,
                    'context' => $contextId,
                    'setting_type' => 'int',
                ];
            }
        }

        DB::table('notification_subscription_settings')->insert($rows);

        DB::table('user_settings')
            ->where('setting_name', 'openAccessNotification')
            ->where('setting_value', '1')
            ->delete();
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
