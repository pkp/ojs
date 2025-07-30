<?php

/**
 * @file classes/migration/upgrade/v3_5_0/I10045_AddReviewRemindDefaultData.php
 *
 * Copyright (c) 2025 Simon Fraser University
 * Copyright (c) 2025 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class I10045_AddReviewRemindDefaultData
 *
 * @brief Seed the REVIEW_REMIND email template into email_templates_default_data if it is missing.
 */

namespace APP\migration\upgrade\v3_5_0;

use Exception;
use Illuminate\Support\Facades\DB;
use PKP\db\XMLDAO;
use PKP\facades\Locale;
use PKP\migration\Migration;

class I10045_AddReviewRemindDefaultData extends Migration
{
    public function up(): void
    {
        $templateKey = 'REVIEW_REMIND';

        if (DB::table('email_templates_default_data')->where('email_key', $templateKey)->exists()) {
            return;
        }

        $xmlDao = new XMLDAO();
        $data = $xmlDao->parseStruct('registry/emailTemplates.xml', ['email']);
        if (empty($data['email'])) {
            throw new Exception('No <email> entries found in registry/emailTemplates.xml');
        }

        $locales = json_decode(DB::table('site')->pluck('installed_locales')->first());
        $found = false;

        foreach ($data['email'] as $entry) {
            $attrs = $entry['attributes'];
            if ($attrs['key'] !== $templateKey) {
                continue;
            }
            $found = true;

            // to temporarily suppress missing‑key warnings so __() fall back cleanly
            $prevHandler = Locale::getMissingKeyHandler();
            Locale::setMissingKeyHandler(fn (string $key) => '');

            foreach ($locales as $locale) {
                DB::table('email_templates_default_data')->insert([
                    'email_key' => $templateKey,
                    'locale' => $locale,
                    'name' => __($attrs['name'], [], $locale),
                    'subject' => __($attrs['subject'], [], $locale),
                    'body' => __($attrs['body'], [], $locale),
                ]);
            }

            Locale::setMissingKeyHandler($prevHandler);
            break;
        }

        if (!$found) {
            throw new Exception("Email template {$templateKey} not defined in registry/emailTemplates.xml");
        }
    }

    public function down(): void
    {
        DB::table('email_templates_default_data')
            ->where('email_key', 'REVIEW_REMIND')
            ->delete();
    }
}
