<?php

/**
 * @file classes/migration/upgrade/v3_4_0/I6782_RemovePlugins.inc.php
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2000-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class I6782_RemovePlugins
 * @brief Remove the usageStats and views report plugin.
 *
 * This script has to be called after I6782_Metrics, i.e. after usageStats plugin settings were successfully migrated.
 */

namespace APP\migration\upgrade\v3_4_0;

use Illuminate\Support\Facades\DB;
use PKP\core\Core;
use PKP\file\FileManager;
use PKP\install\DowngradeNotSupportedException;
use PKP\migration\Migration;

class I6782_RemovePlugins extends Migration
{
    /**
     * Run the migration.
     */
    public function up(): void
    {
        $fileManager = new FileManager();

        // Remove usageStats plugin
        $category = 'generic';
        $productName = 'usageStats';
        $pluginVersionsEntryExists = DB::table('versions')
            ->where('current', 1)
            ->where('product_type', '=', 'plugins.' . $category)
            ->where('product', '=', $productName)
            ->exists();
        if ($pluginVersionsEntryExists) {
            $pluginDest = Core::getBaseDir() . DIRECTORY_SEPARATOR . 'plugins' . DIRECTORY_SEPARATOR . $category . DIRECTORY_SEPARATOR . $productName;
            $pluginLibDest = Core::getBaseDir() . DIRECTORY_SEPARATOR . PKP_LIB_PATH . DIRECTORY_SEPARATOR . 'plugins' . DIRECTORY_SEPARATOR . $category . DIRECTORY_SEPARATOR . $productName;
            // Delete files
            // Can we check permissions in the perflight spript?
            $fileManager->rmtree($pluginDest);
            $fileManager->rmtree($pluginLibDest);
            if (is_dir($pluginDest) || is_dir($pluginLibDest)) {
                $this->_installer->log("Plugin \"plugins.{$category}.{$productName}\" could not be deleted from the file system. This may be a permissions problem. Please make sure that the web server is able to write to the plugins directory (including subdirectories) but don't forget to secure it again later.");
            } else {
                // Differently to versionDao->disableVersion, we will remove the entry from the table 'versions' and plugin_settings
                // becase the plugin cannot be used any more
                DB::table('versions')
                    ->where('product_type', '=', 'plugins.' . $category)
                    ->where('product', '=', $productName)
                    ->delete();
                DB::table('plugin_settings')
                    ->where('plugin_name', '=', 'usagestatsplugin')
                    ->delete();
                // Do we need to do anything with PluginRegistry?
            }
        }

        // Remove views report plugin
        $category = 'reports';
        $productName = 'views';
        $pluginVersionsEntryExists = DB::table('versions')
            ->where('product_type', '=', 'plugins.' . $category)
            ->where('product', '=', $productName)
            ->exists();
        if ($pluginVersionsEntryExists) {
            $pluginDest = Core::getBaseDir() . DIRECTORY_SEPARATOR . 'plugins' . DIRECTORY_SEPARATOR . $category . DIRECTORY_SEPARATOR . $productName;
            $pluginLibDest = Core::getBaseDir() . DIRECTORY_SEPARATOR . PKP_LIB_PATH . DIRECTORY_SEPARATOR . 'plugins' . DIRECTORY_SEPARATOR . $category . DIRECTORY_SEPARATOR . $productName;
            // Delete files
            // Can we check permissions in the perflight spript?
            $fileManager->rmtree($pluginDest);
            $fileManager->rmtree($pluginLibDest);
            if (is_dir($pluginDest) || is_dir($pluginLibDest)) {
                $this->_installer->log("Plugin \"plugins.{$category}.{$productName}\" could not be deleted from the file system. This may be a permissions problem. Please make sure that the web server is able to write to the plugins directory (including subdirectories) but don't forget to secure it again later.");
            } else {
                // Differently to versionDao->disableVersion, we will remove the entry from the table 'versions'
                // becase the plugin cannot be used any more.
                // There were no entries in plugin_settings for the views report plugin.
                DB::table('versions')
                    ->where('product_type', '=', 'plugins.' . $category)
                    ->where('product', '=', $productName)
                    ->delete();
                // Do we need to do anything with PluginRegistry?
            }
        }

        // It is not needed to remove usageStats plugin scheduled task from the Acron plugin, because
        // PKPAcronPlugin function _parseCrontab() will be called at the end of update, that
        // will overwrite the old crontab setting.

        // Remove the old scheduled task from the table scheduled_tasks???
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

if (!PKP_STRICT_MODE) {
    class_alias('\APP\migration\upgrade\v3_4_0\I6782_RemovePlugins', '\I6782_RemovePlugins');
}
