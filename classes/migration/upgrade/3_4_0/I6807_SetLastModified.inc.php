<?php

/**
 * @file classes/migration/upgrade/3_4_0/I6807_SetLastModified.inc.php
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2000-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class I6807_SetLastModified
 * @brief Update last modification dates where they are not yet set.
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Builder;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Capsule\Manager as Capsule;

class I6807_SetLastModified extends Migration {
	/**
	 * Run the migration.
	 * @return void
	 */
	public function up() {
		// pkp/pkp-lib#6807 Make sure all submission/issue last modification dates are set
		Capsule::statement('UPDATE issues SET last_modified = date_published WHERE last_modified IS NULL');
		Capsule::statement('UPDATE submissions SET last_modified = NOW() WHERE last_modified IS NULL');
	}

	/**
	 * Reverse the downgrades
	 * @return void
	 */
	public function down() {
		// We don't have the data to downgrade and downgrades are unwanted here anyway.
	}
}

