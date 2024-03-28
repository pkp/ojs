<?php

namespace APP\migration\upgrade\v3_5_0;

use APP\facades\Repo;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use PKP\migration\Migration;

class OrcidMigration extends Migration
{
    /**
     * @inheritDoc
     */
    public function up(): void
    {
        $q = DB::table('plugin_settings')
            ->where('plugin_name', '=', 'orcidprofileplugin')
            ->where('context_id', '<>', 0)
            ->select(['context_id', 'setting_name', 'setting_value']);

        $results = $q->get();
        $mappedResults = $results->map(function ($item) {
            if(!Str::startsWith($item->setting_name, 'orcid')) {
                $item->setting_name = 'orcid' . Str::ucfirst($item->setting_name);
            }
            $item->journal_id = $item->context_id;
            unset($item->context_id);

            return (array) $item;
        });

        DB::table('journal_settings')
            ->insert($mappedResults->toArray());

        // TODO: To be tested still. Keeping in plugin_settings during dev.
        //        DB::table('plugin_settings')
        //            ->where('plugin_name', '=', 'orcidprofileplugin')
        //            ->delete();

        Repo::emailTemplate()->dao->installEmailTemplates(
            Repo::emailTemplate()->dao->getMainEmailTemplatesFilename(),
            [],
            'ORCID_COLLECT_AUTHOR_ID',
            true,
        );
        Repo::emailTemplate()->dao->installEmailTemplates(
            Repo::emailTemplate()->dao->getMainEmailTemplatesFilename(),
            [],
            'ORCID_REQUEST_AUTHOR_AUTHORIZATION',
            true,
        );
    }

    /**
     * @inheritDoc
     */
    public function down(): void
    {
        // TODO: Implement down() method.
    }
}
