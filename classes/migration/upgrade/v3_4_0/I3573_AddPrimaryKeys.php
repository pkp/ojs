<?php

/**
 * @file classes/migration/upgrade/v3_4_0/I3573_AddPrimaryKeys.php
 *
 * Copyright (c) 2014-2023 Simon Fraser University
 * Copyright (c) 2000-2023 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class I3573_AddPrimaryKeys.php
 *
 * @brief Add primary keys to tables that do not have them, to better support database replication.
 *
 */

namespace APP\migration\upgrade\v3_4_0;

class I3573_AddPrimaryKeys extends \PKP\migration\upgrade\v3_4_0\I3573_AddPrimaryKeys
{
    public static function getKeyNames(): array
    {
        return array_merge(parent::getKeyNames(), [
            'issue_galley_settings' => 'issue_galley_setting_id',
            'issue_settings' => 'issue_setting_id',
            'journal_settings' => 'journal_setting_id',
            'publication_galley_settings' => 'publication_galley_setting_id',
            'section_settings' => 'section_setting_id',
            'subscription_type_settings' => 'subscription_type_setting_id',
            'usage_stats_unique_item_requests_temporary_records' => 'usage_stats_temp_item_id',
            'metrics_context' => 'metrics_context_id',
            'metrics_counter_submission_institution_daily' => 'metrics_counter_submission_institution_daily_id',
            'metrics_counter_submission_daily' => 'metrics_counter_submission_daily_id',
            'metrics_submission' => 'metrics_submission_id',
            'usage_stats_unique_item_investigations_temporary_records' => 'usage_stats_temp_unique_item_id',
            'metrics_counter_submission_monthly' => 'metrics_counter_submission_monthly_id',
            'usage_stats_total_temporary_records' => 'usage_stats_temp_total_id',
            'usage_stats_institution_temporary_records' => 'usage_stats_temp_institution_id',
            'metrics_submission_geo_daily' => 'metrics_submission_geo_daily_id',
            'metrics_counter_submission_institution_monthly' => 'metrics_counter_submission_institution_monthly_id',
            'metrics_issue' => 'metrics_issue_id',
            'metrics_submission_geo_monthly' => 'metrics_submission_geo_monthly_id',
            'custom_section_orders' => 'custom_section_order_id',
            'custom_issue_orders' => 'custom_issue_order_id',
            'funder_settings' => 'funder_setting_id', // PLUGIN
            'funder_award_settings' => 'funder_award_setting_id', // PLUGIN
        ]);
    }

    public static function getIndexData(): array
    {
        return array_merge(parent::getIndexData(), [
            'journal_settings' => ['journal_settings_pkey', ['journal_id', 'locale', 'setting_name'], 'journal_settings_unique', true],
            'section_settings' => ['section_settings_pkey', ['section_id', 'locale', 'setting_name'], 'section_settings_unique', true],
            'issue_settings' => ['issue_settings_pkey', ['issue_id', 'locale', 'setting_name'], 'issue_settings_unique', true],
            'issue_galley_settings' => ['issue_galley_settings_pkey', ['galley_id', 'locale', 'setting_name'], 'issue_galley_settings_unique', true],
            'custom_issue_orders' => ['custom_issue_orders_pkey', ['issue_id'], 'custom_issue_orders_unique', true],
            'custom_section_orders' => ['custom_section_orders_pkey', ['issue_id', 'section_id'], 'custom_section_orders_unique', true],
            'publication_galley_settings' => ['publication_galley_settings_pkey', ['galley_id', 'locale', 'setting_name'], 'publication_galley_settings_unique', true],
            'subscription_type_settings' => ['subscription_type_settings_pkey', ['type_id', 'locale', 'setting_name'], 'subscription_type_settings_unique', true],
        ]);
    }
}
