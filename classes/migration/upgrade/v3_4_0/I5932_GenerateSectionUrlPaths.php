<?php

/**
 * @file classes/migration/upgrade/v3_4_0/I5932_GenerateSectionUrlPaths.php
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2000-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class I5932_GenerateSectionUrlPaths
 *
 * @brief Create section urlPath column and generate URL paths for sections.
 */

namespace APP\migration\upgrade\v3_4_0;

use APP\core\Application;
use APP\facades\Repo;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use PKP\migration\Migration;
use Stringy\Stringy;

class I5932_GenerateSectionUrlPaths extends Migration
{
    /**
     * Run the migration.
     */
    public function up(): void
    {

        // pkp/pkp-lib#5932 Generate URL paths for sections
        Schema::table('sections', function (Blueprint $table) {
            $table->string('url_path', 255)->nullable();
            $table->smallInteger('not_browsable')->default(0);
        });

        $contextDao = Application::getContextDAO();
        $sections = Repo::section()->getCollector()->filterByContextIds(DB::table('journals')->pluck('journal_id')->toArray())->getMany();

        foreach ($sections as $section) {
            $context = $contextDao->getById($section->getContextId());
            $sectionTitle = $section->getLocalizedData('title', $context->getPrimaryLocale());
            $sectionUrlpath = (string) Stringy::create($sectionTitle)->toAscii()->toLowerCase()->dasherize()->regexReplace('[^a-z0-9\-\_.]', '');
            $section->setUrlPath($sectionUrlpath);
            Repo::section()->edit($section, []);
        }

        Schema::table('sections', function (Blueprint $table) {
            $table->string('url_path', 255)->nullable(false)->change();
        });
    }

    /**
     * Reverse the downgrades
     */
    public function down(): void
    {
        Schema::table('sections', function (Blueprint $table) {
            $table->dropColumn('url_path');
            $table->dropColumn('not_browsable');
        });
    }
}
