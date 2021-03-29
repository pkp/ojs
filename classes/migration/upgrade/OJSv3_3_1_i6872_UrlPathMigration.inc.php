<?php

/**
 * @file classes/migration/upgrade/OJSv3_3_1_i6872_UrlPathMigration.inc.php
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2000-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class OJSv3_3_1_i6872_UrlPathMigration
 * @brief A database migration that converts empty string url_paths to null.
 * @see https://github.com/pkp/pkp-lib/issues/6872
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Capsule\Manager as Capsule;

class OJSv3_3_1_i6872_UrlPathMigration extends Migration {
	/**
	 * Run the migrations.
	 * @return void
	 */
	public function up() {
		Capsule::table('publications')->whereNull('url_path')->update(['url_path' => null]);
	}

	/**
	 * Reverse the downgrades
	 * @return void
	 */
	public function down() {
		// This migration is not destructive. A downgrade should leave these url_paths as null.
	}
}
