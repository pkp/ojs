<?php

namespace APP\migration\upgrade\v3_5_0;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class I10157_CreateAndMigrateIssueIdField extends \PKP\migration\Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // add the new column to publications table
        Schema::table('publications', function (Blueprint $table) {
            $table->bigInteger('issue_id')->nullable()->after('submission_id');
        });

        // add the foreign key constraint and index,
        Schema::table('publications', function (Blueprint $table) {
            $table->foreign('issue_id')
                ->references('issue_id')
                ->on('issues')
                ->nullOnDelete();
            $table->index(['issue_id'], 'publications_issue_id_index');
        });

        // migrate data from publication_settings to the new issue_id column.
        DB::table('publications')
            ->join('publication_settings', 'publication_settings.publication_id', '=', 'publications.publication_id')
            ->where('publication_settings.setting_name', 'issueId')
            ->update([
                'publications.issue_id' => DB::raw('publication_settings.setting_value'),
            ]);

        // clear the old data of issueId in publication_settings
        DB::table('publication_settings')
            ->where('setting_name', 'issueId')
            ->delete();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('publications', function (Blueprint $table) {
            $table->dropForeign(['issue_id']);
            $table->dropIndex('publications_issue_id_index');
            $table->dropColumn('issue_id');
        });
    }
}
