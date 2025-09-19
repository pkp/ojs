<?php

/**
 * @file classes/migration/upgrade/v3_6_0/I11593_DOAJGenericPlugin.php
 *
 * Copyright (c) 2025 Simon Fraser University
 * Copyright (c) 2025 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class I11593_DOAJGenericPlugin
 *
 * @brief Migration for DOAJ generic plugin.
 */

namespace APP\migration\upgrade\v3_6_0;

use Illuminate\Support\Facades\DB;
use PKP\install\DowngradeNotSupportedException;

class I11593_DOAJGenericPlugin extends \PKP\migration\Migration
{
    public const FILTER_RENAME_MAP = [
        'APP\plugins\importexport\doaj\filter\DOAJXmlFilter' => 'APP\plugins\generic\doaj\filter\DOAJXmlFilter',
        'APP\plugins\importexport\doaj\filter\DOAJJsonFilter' => 'APP\plugins\generic\doaj\filter\DOAJJsonFilter',
    ];

    /**
     * Run the migration.
     */
    public function up(): void
    {
        // rename filters
        foreach (self::FILTER_RENAME_MAP as $oldName => $newName) {
            DB::statement('UPDATE filters SET class_name = ? WHERE class_name = ?', [$newName, $oldName]);
        }
        DB::statement('UPDATE filter_groups SET output_type=? WHERE output_type = ?', ['xml::schema(plugins/generic/doaj/doajArticles.xsd)', 'xml::schema(plugins/importexport/doaj/doajArticles.xsd)']);

        // remove DOAJ importexport plugin
        DB::table('versions')
            ->where('product_type', '=', 'plugins.importexport')
            ->where('product', '=', 'doaj')
            ->delete();

        // enable it for all existing journals
        $contextIds = DB::table('journals')->pluck('journal_id');
        $rows = [];
        foreach ($contextIds as $contextId) {
            $rows[] = [
                'plugin_name' => 'doajplugin',
                'context_id' => $contextId,
                'setting_name' => 'enabled',
                'setting_value' => true,
                'setting_type' => 'bool',
            ];
        }
        DB::table('plugin_settings')->insert($rows);
    }

    /**
     * Reverse the downgrades
     */
    public function down(): void
    {
        throw new DowngradeNotSupportedException();
    }
}
