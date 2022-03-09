<?php

/**
 * @file classes/migration/upgrade/v3_4_0/I6782_Metrics.inc.php
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2000-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class I6782_Metrics
 * @brief Migrate usage stats settings, and data from the old DB table metrics into the new DB tables.
 */

namespace APP\migration\upgrade\v3_4_0;

use APP\core\Application;
use APP\core\Services;
use APP\migration\install\MetricsMigration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use PKP\config\Config;
use PKP\core\Core;
use PKP\install\DowngradeNotSupportedException;
use PKP\migration\Migration;
use PKP\plugins\PluginRegistry;

class I6782_Metrics extends Migration
{
    /**
     * Run the migration.
     */
    public function up(): void
    {
        $metricsMigrations = new MetricsMigration($this->_installer, $this->_attributes);
        $metricsMigrations->up();

        // Read old usage stats settings
        // Geo data stats settings
        $optionalColumns = DB::table('plugin_settings')
            ->where('plugin_name', '=', 'usagestatsplugin')
            ->where('setting_name', '=', 'optionalColumns')
            ->value('setting_value');

        $enableGeoUsageStats = 'disabled';
        $keepDailyUsageStats = false;
        if (!is_null($optionalColumns)) {
            $keepDailyUsageStats = true;
            if (str_contains($optionalColumns, 'city')) {
                $enableGeoUsageStats = 'country+region+city';
            } elseif (str_contains($optionalColumns, 'region')) {
                $enableGeoUsageStats = 'country+region';
            } else {
                $enableGeoUsageStats = 'country';
            }
        }
        // Compress archives settings
        $compressArchives = DB::table('plugin_settings')
            ->where('plugin_name', '=', 'usagestatsplugin')
            ->where('setting_name', '=', 'compressArchives')
            ->value('setting_value');
        // Migrate site settings
        DB::table('site_settings')->insertOrIgnore([
            ['setting_name' => 'compressStatsLogs', 'setting_value' => $compressArchives],
            ['setting_name' => 'enableGeoUsageStats', 'setting_value' => $enableGeoUsageStats],
            ['setting_name' => 'keepDailyUsageStats', 'setting_value' => $keepDailyUsageStats]
        ]);

        // Display site settings
        $displayStatistics = DB::table('plugin_settings')
            ->where('plugin_name', '=', 'usagestatsplugin')
            ->where('setting_name', '=', 'displayStatistics')
            ->where('context_id', '=', 0)
            ->value('setting_value');
        $chartType = DB::table('plugin_settings')
            ->where('plugin_name', '=', 'usagestatsplugin')
            ->where('setting_name', '=', 'chartType')
            ->where('context_id', '=', 0)
            ->value('setting_value');
        // Migrate usage stats site display settings to the active site theme
        $siteThemePlugins = PluginRegistry::getPlugins('themes');
        $activeSiteTheme = null;
        foreach ($siteThemePlugins as $siteThemePlugin) {
            if ($siteThemePlugin->isActive()) {
                $activeSiteTheme = $siteThemePlugin;
                break;
            }
        }
        if (isset($activeSiteTheme)) {
            $siteUsageStatsDisplay = !$displayStatistics ? 'none' : $chartType;
            DB::table('plugin_settings')->insertOrIgnore([
                ['plugin_name' => $activeSiteTheme->getName(), 'context_id' => 0, 'setting_name' => 'displayStats', 'setting_value' => $siteUsageStatsDisplay, 'setting_type' => 'string'],
            ]);
        }

        // Migrate context settings
        // Get all, also disabled, contexts
        $contextIds = Services::get('context')->getIds();
        foreach ($contextIds as $contextId) {
            $contextDisplayStatistics = $contextChartType = null;
            $contextDisplayStatistics = DB::table('plugin_settings')
                ->where('plugin_name', '=', 'usagestatsplugin')
                ->where('setting_name', '=', 'displayStatistics')
                ->where('context_id', '=', $contextId)
                ->value('setting_value');
            $contextChartType = DB::table('plugin_settings')
                ->where('plugin_name', '=', 'usagestatsplugin')
                ->where('setting_name', '=', 'chartType')
                ->where('context_id', '=', $contextId)
                ->value('setting_value');
            // Migrate usage stats display settings to the active context theme
            $contextThemePlugins = PluginRegistry::loadCategory('themes', true, $contextId);
            $activeContextTheme = null;
            foreach ($contextThemePlugins as $contextThemePlugin) {
                if ($contextThemePlugin->isActive()) {
                    $activeContextTheme = $contextThemePlugin;
                    break;
                }
            }
            if (isset($activeContextTheme)) {
                $contextUsageStatsDisplay = !$contextDisplayStatistics ? 'none' : $contextChartType;
                DB::table('plugin_settings')->insertOrIgnore([
                    ['plugin_name' => $activeContextTheme->getName(), 'context_id' => $contextId, 'setting_name' => 'displayStats', 'setting_value' => $contextUsageStatsDisplay, 'setting_type' => 'string'],
                ]);
            }
        }

        $dayFormatSql = "DATE_FORMAT(STR_TO_DATE(m.day, '%Y%m%d'), '%Y-%m-%d')";
        if (substr(Config::getVar('database', 'driver'), 0, strlen('postgres')) === 'postgres') {
            $dayFormatSql = "to_date(m.day, 'YYYYMMDD')";
        }

        // The not existing foreign keys should already be moved to the metrics_tmp in I6782_OrphanedMetrics
        // Migrate context metrics
        $selectContextMetrics = DB::table('metrics as m')
            ->select(DB::raw("m.load_id, m.assoc_id, {$dayFormatSql}, m.metric"))
            ->where('m.assoc_type', '=', Application::ASSOC_TYPE_JOURNAL)
            ->where('m.metric_type', '=', 'ojs::counter');
        DB::table('metrics_context')->insertUsing(['load_id', 'context_id', 'date', 'metric'], $selectContextMetrics);

        // Migrate issue metrics; consider issue TOCs and galley files
        $selectIssueMetrics = DB::table('metrics as m')
            ->select(DB::raw("m.load_id, m.context_id, m.assoc_id, null, {$dayFormatSql}, m.metric"))
            ->where('m.assoc_type', '=', Application::ASSOC_TYPE_ISSUE)
            ->where('m.metric_type', '=', 'ojs::counter');
        DB::table('metrics_issue')->insertUsing(['load_id', 'context_id', 'issue_id', 'issue_galley_id', 'date', 'metric'], $selectIssueMetrics);

        $selectIssueGalleyMetrics = DB::table('metrics as m')
            ->join('issue_galleys as ig', 'ig.galley_id', '=', 'm.assoc_id')
            ->select(DB::raw("m.load_id, m.context_id, ig.issue_id, m.assoc_id, {$dayFormatSql}, m.metric"))
            ->where('m.assoc_type', '=', Application::ASSOC_TYPE_ISSUE_GALLEY)
            ->where('m.metric_type', '=', 'ojs::counter');
        DB::table('metrics_issue')->insertUsing(['load_id', 'context_id', 'issue_id', 'issue_galley_id', 'date', 'metric'], $selectIssueGalleyMetrics);

        // Migrate submission metrics; consider abstracts, galley and supp files
        $selectSubmissionMetrics = DB::table('metrics as m')
            ->select(DB::raw("m.load_id, m.context_id, m.assoc_id, null, null, null, m.assoc_type, {$dayFormatSql}, m.metric"))
            ->where('m.assoc_type', '=', Application::ASSOC_TYPE_SUBMISSION)
            ->where('m.metric_type', '=', 'ojs::counter');
        DB::table('metrics_submission')->insertUsing(['load_id', 'context_id', 'submission_id', 'representation_id', 'submission_file_id', 'file_type', 'assoc_type', 'date', 'metric'], $selectSubmissionMetrics);

        $selectSubmissionFileMetrics = DB::table('metrics as m')
            ->select(DB::raw("m.load_id, m.context_id, m.submission_id, m.representation_id, m.assoc_id, m.file_type, m.assoc_type, {$dayFormatSql}, m.metric"))
            ->where('m.assoc_type', '=', Application::ASSOC_TYPE_SUBMISSION_FILE)
            ->where('m.metric_type', '=', 'ojs::counter');
        DB::table('metrics_submission')->insertUsing(['load_id', 'context_id', 'submission_id', 'representation_id', 'submission_file_id', 'file_type', 'assoc_type', 'date', 'metric'], $selectSubmissionFileMetrics);

        $selectSubmissionSuppFileMetrics = DB::table('metrics as m')
            ->select(DB::raw("m.load_id, m.context_id, m.submission_id, m.representation_id, m.assoc_id, m.file_type, m.assoc_type, {$dayFormatSql}, m.metric"))
            ->where('m.assoc_type', '=', Application::ASSOC_TYPE_SUBMISSION_FILE_COUNTER_OTHER)
            ->where('m.metric_type', '=', 'ojs::counter');
        DB::table('metrics_submission')->insertUsing(['load_id', 'context_id', 'submission_id', 'representation_id', 'submission_file_id', 'file_type', 'assoc_type', 'date', 'metric'], $selectSubmissionSuppFileMetrics);

        // Migrate Geo metrics -- no matter if the Geo usage stats are currently enabled
        // fix wrong entries in the DB table metrics
        // do all this first in order for groupBy to function properly
        DB::table('metrics')->where('city', '')->update(['city' => null]);
        DB::table('metrics')->where('region', '')->orWhere('region', '0')->update(['region' => null]);
        DB::table('metrics')->where('country_id', '')->update(['country_id' => null]);
        // in the GeoIP Legacy databases, several country codes were included that don't represent countries
        DB::table('metrics')->whereIn('country_id', ['AP', 'EU', 'A1', 'A2'])->update(['country_id' => null, 'region' => null, 'city' => null]);
        // some regions are missing the leading '0'
        DB::table('metrics')->update(['region' => DB::raw("LPAD(region, 2, '0')")]);

        // insert into daily table
        $selectGeoMetrics = DB::table('metrics as m')
            ->select(DB::raw("m.load_id, m.context_id, m.submission_id, COALESCE(m.country_id, ''), COALESCE(m.region, ''), COALESCE(m.city, ''), {$dayFormatSql} as mday, SUM(m.metric), 0"))
            ->whereIn('m.assoc_type', [Application::ASSOC_TYPE_SUBMISSION, Application::ASSOC_TYPE_SUBMISSION_FILE, Application::ASSOC_TYPE_SUBMISSION_FILE_COUNTER_OTHER])
            ->where('m.metric_type', '=', 'ojs::counter')
            ->where(function ($q) {
                $q->whereNotNull('m.country_id')
                    ->orWhereNotNull('m.region')
                    ->orWhereNotNull('m.city');
            })
            ->groupBy(DB::raw('m.load_id, m.context_id, m.submission_id, m.country_id, m.region, m.city, mday'));
        DB::table('metrics_submission_geo_daily')->insertUsing(['load_id', 'context_id', 'submission_id', 'country', 'region', 'city', 'date', 'metric', 'metric_unique'], $selectGeoMetrics);

        // migrate region FIPS to ISO, s. https://dev.maxmind.com/geoip/whats-new-in-geoip2?lang=en
        // create a temporary table for the FIPS-ISO mapping
        if (!Schema::hasTable('region_mapping_tmp')) {
            Schema::create('region_mapping_tmp', function (Blueprint $table) {
                $table->string('country', 2);
                $table->string('fips', 3);
                $table->string('iso', 3)->nullable();
            });
            // read the FIPS to ISO mappings and isert them into the temporary table
            $mappings = include Core::getBaseDir() . '/' . PKP_LIB_PATH . '/lib/regionMapping.php';
            foreach ($mappings as $country => $regionMapping) {
                foreach ($regionMapping as $fips => $iso) {
                    DB::table('region_mapping_tmp')->insert([
                        'country' => $country,
                        'fips' => $fips,
                        'iso' => $iso
                    ]);
                }
            }
        }
        // temporary create index on the column country and region, in order to be able to update the region codes in a reasonable time
        Schema::table('metrics_submission_geo_daily', function (Blueprint $table) {
            $sm = Schema::getConnection()->getDoctrineSchemaManager();
            $indexesFound = $sm->listTableIndexes('metrics_submission_geo_daily');
            if (!array_key_exists('metrics_submission_geo_daily_tmp_index', $indexesFound)) {
                $table->index(['country', 'region'], 'metrics_submission_geo_daily_tmp_index');
            }
        });
        // update region code from FIPS to ISP
        // Laravel join+update does not work well with PostgreSQL, so use the direct SQLs
        if (substr(Config::getVar('database', 'driver'), 0, strlen('postgres')) === 'postgres') {
            DB::statement('
                UPDATE metrics_submission_geo_daily AS gd
                SET region = rm.iso
                FROM region_mapping_tmp AS rm
                WHERE gd.country = rm.country AND gd.region = rm.fips
            ');
        } else {
            DB::statement('
                UPDATE metrics_submission_geo_daily gd
                INNER JOIN region_mapping_tmp rm ON (rm.country = gd.country AND rm.fips = gd.region)
                SET gd.region = rm.iso
            ');
        }
        // drop the temporary index
        Schema::table('metrics_submission_geo_daily', function (Blueprint $table) {
            $sm = Schema::getConnection()->getDoctrineSchemaManager();
            $indexesFound = $sm->listTableIndexes('metrics_submission_geo_daily');
            if (array_key_exists('metrics_submission_geo_daily_tmp_index', $indexesFound)) {
                $table->dropIndex(['tmp']);
            }
        });
        // drop the temporary table
        if (Schema::hasTable('region_mapping_tmp')) {
            Schema::drop('region_mapping_tmp');
        }

        // Migrate to monthly table
        $monthFormatSql = "CAST(DATE_FORMAT(gd.date, '%Y%m') AS UNSIGNED)";
        if (substr(Config::getVar('database', 'driver'), 0, strlen('postgres')) === 'postgres') {
            $monthFormatSql = "to_char(gd.date, 'YYYYMM')::integer";
        }
        // use the table metrics_submission_geo_daily instead of table metrics to calculate the monthly numbers
        $selectSubmissionGeoDaily = DB::table('metrics_submission_geo_daily as gd')
            ->select(DB::raw("gd.context_id, gd.submission_id, COALESCE(gd.country, ''), COALESCE(gd.region, ''), COALESCE(gd.city, ''), {$monthFormatSql} as gdmonth, SUM(gd.metric), SUM(gd.metric_unique)"))
            ->groupBy(DB::raw('gd.context_id, gd.submission_id, gd.country, gd.region, gd.city, gdmonth'));
        DB::table('metrics_submission_geo_monthly')->insertUsing(['context_id', 'submission_id', 'country', 'region', 'city', 'month', 'metric', 'metric_unique'], $selectSubmissionGeoDaily);

        // Delete the entries with the metric type ojs::counter from the DB table metrics -> they were migrated above
        if (Schema::hasTable('metrics')) {
            DB::table('metrics')->where('metric_type', '=', 'ojs::counter')->delete();

            // Move back the orphaned metrics form the temporary metrics_tmp
            $metricsColumns = Schema::getColumnListing('metrics_tmp');
            $metricsTmp = DB::table('metrics_tmp')->select('*');
            DB::table('metrics')->insertUsing($metricsColumns, $metricsTmp);
            Schema::drop('metrics_tmp');

            $metricsExist = DB::table('metrics')->count();
            // if table metrics is now not empty rename it, else delete it
            if ($metricsExist > 0) {
                Schema::rename('metrics', 'metrics_old');
            } else {
                Schema::drop('metrics');
            }
        }
        // Delete the old usage_stats_temporary_records table
        if (Schema::hasTable('usage_stats_temporary_records')) {
            Schema::drop('usage_stats_temporary_records');
        }
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
