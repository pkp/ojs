<?php

namespace APP\migration\upgrade\v3_5_0;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use PKP\migration\Migration;

class I10157_CreateAndMigrateIssueIdField extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // adding new column to the publications table
        Schema::table('publications', function (Blueprint $table) {
            $table->bigInteger('issue_id')->nullable()->after('submission_id');
        });

        // migrating data from publication_settings to the new issue_id column
        DB::statement('UPDATE publications p JOIN publication_settings ps ON p.publication_id = ps.publication_id AND ps.setting_name = "issueId" SET p.issue_id = ps.setting_value');

        // clear the old data of issueId in publication_settings
        DB::table('publication_settings')->where('setting_name', 'issueId')->delete();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // remove the column in case rolling back
        Schema::table('publications', function (Blueprint $table) {
            $table->dropColumn('issue_id');
        });
    }
}
