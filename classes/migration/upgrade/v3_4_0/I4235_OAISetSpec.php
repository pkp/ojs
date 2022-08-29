<?php

/**
 * @file classes/migration/upgrade/v3_4_0/I4235_OAISetSpec.php
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2000-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class I4235_OAISetSpec
 */

namespace APP\migration\upgrade\v3_4_0;

use Illuminate\Support\Facades\DB;
use PKP\oai\OAIUtils;

class I4235_OAISetSpec extends \PKP\migration\Migration
{
    /**
     * Run the migration.
     */
    public function up(): void
    {
        // pkp/pkp-lib/issues/4235 Improve OAI-PMH set spec compliance
        // Convert stored setSpec strings to valid format
        $setSpecs = DB::table('data_object_tombstones')->select('set_spec')->distinct()->get()->toArray();
        foreach ($setSpecs as $row) {
            $a = preg_split('/:/', $row->set_spec);
            if (count($a) == 2) {
                [$journalSpec, $sectionSpec] = $a;
                $new = OAIUtils::toValidSetSpec(urldecode($journalSpec)) . ':' . OAIUtils::toValidSetSpec(urldecode($sectionSpec));
                DB::table('data_object_tombstones')->where('set_spec', $row->set_spec)->update(['set_spec' => $new]);
            }
        }
    }

    /**
     * Reverse the downgrades
     */
    public function down(): void
    {
        // The old format is not recoverable since some characters might have been stripped
    }
}
