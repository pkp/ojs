<?php

/**
 * @file classes/migration/upgrade/v3_4_0/I10962_UpdateEmailTemplateVariables.php
 *
 * Copyright (c) 2014-2025 Simon Fraser University
 * Copyright (c) 2000-2025 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class I10962_UpdateEmailTemplateVariables
 *
 * @brief Remap {$journalAcronym} to {$contextAcronym} in the COPYEDIT_REQUEST email template.
 */

namespace APP\migration\upgrade\v3_4_0;

use Illuminate\Support\Facades\DB;
use PKP\install\DowngradeNotSupportedException;
use PKP\migration\Migration;

class I10962_UpdateEmailTemplateVariables extends Migration
{
    public function up(): void
    {
        DB::transaction(function () {
            $map = [
                'journalAcronym' => 'contextAcronym',
                'journalName' => 'contextName',
                'journalUrl' => 'contextUrl',
                'journalSignature' => 'contextSignature',
            ];

            foreach ($map as $old => $new) {
                $oldToken = '{$' . $old . '}';
                $newToken = '{$' . $new . '}';
                $like = '%' . $oldToken . '%';

                DB::update(
                    'UPDATE email_templates_default_data
                       SET subject = replace(subject, ?, ?),
                           body    = replace(body,    ?, ?)
                     WHERE subject LIKE ? OR body LIKE ?',
                    [$oldToken, $newToken, $oldToken, $newToken, $like, $like]
                );

                DB::update(
                    'UPDATE email_templates_settings
                        SET setting_value = replace(setting_value, ?, ?)
                      WHERE setting_name IN (?, ?)
                        AND setting_value LIKE ?',
                    [$oldToken, $newToken, 'subject', 'body', $like]
                );
            }
        });
    }

    public function down(): void
    {
        throw new DowngradeNotSupportedException(__CLASS__);
    }
}
