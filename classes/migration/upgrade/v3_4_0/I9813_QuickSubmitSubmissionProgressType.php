<?php

/**
 * @file classes/migration/upgrade/v3_4_0/I9813_QuickSubmitSubmissionProgressType.php
 *
 * Copyright (c) 2024 Simon Fraser University
 * Copyright (c) 2024 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class I9813_QuickSubmitSubmissionProgressType
 *
 * @brief Fix the old submission_progress values inserted by QuickSubmit plugin,
 *   from an int to a string to match the new step ids
 */

namespace APP\migration\upgrade\v3_4_0;

use Illuminate\Support\Facades\DB;
use PKP\db\DAORegistry;
use PKP\install\DowngradeNotSupportedException;

class I9813_QuickSubmitSubmissionProgressType extends \PKP\migration\Migration
{
    public function up(): void
    {
        $thresholdVersion = '1.0.7.1';
        /** @var VersionDAO $versionDao */
        $versionDao = DAORegistry::getDAO('VersionDAO');
        $installedQuickSubmitPluginVersion = $versionDao->getCurrentVersion('plugins.importexport', 'quickSubmit');
        if (!$installedQuickSubmitPluginVersion) {
            return;
        }
        if ($installedQuickSubmitPluginVersion->compare($thresholdVersion) === 0) {
            $this->_installer->log('WARNING: You are using an old version of the QuickSubmit plugin, that is not supported anymore. Please upgrade the QuickSubmit plugin via plugin gallery immediately after this upgrade.');
        }

        foreach ($this->getStepMap() as $oldValue => $newValue) {
            DB::table('submissions')
                ->where('submission_progress', (string) $oldValue)
                ->update([
                    'submission_progress' => $newValue,
                ]);
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

    /**
     * Mapping of inserted QuickSubmit plugin's values to the new step ids
     *
     * @return array [oldValue => newValue]
     */
    protected function getStepMap(): array
    {
        return [
            0 => '',
            1 => 'start',
        ];
    }
}
