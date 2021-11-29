<?php

/**
 * @file classes/migration/upgrade/v3_4_0/I7014_DoiMigration.inc.php
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2000-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class I7014_DoiMigration
 * @brief Describe upgrade/downgrade operations for DB table dois.
 */

namespace APP\migration\upgrade\v3_4_0;

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Query\Builder;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use PKP\doi\Doi;
use PKP\install\DowngradeNotSupportedException;

class I7014_DoiMigration extends Migration
{
    // TODO: Add additions to OJSMigration

    /**
     * Run the migrations.
     */
    public function up()
    {
        // DOIs
        Schema::create('dois', function (Blueprint $table) {
            $table->bigInteger('doi_id')->autoIncrement();
            $table->bigInteger('context_id');
            $table->string('doi');
            $table->smallInteger('status')->default(1);

            $table->foreign('context_id')->references('journal_id')->on('journals');
        });

        // Settings
        Schema::create('doi_settings', function (Blueprint $table) {
            $table->bigInteger('doi_id');
            $table->string('locale', 14)->default('');
            $table->string('setting_name', 255);
            $table->text('setting_value')->nullable();

            // TODO: #doi Check on index/unique alongside foreign. Is it okay?
            $table->index(['doi_id'], 'doi_settings_doi_id');
            $table->unique(['doi_id', 'locale', 'setting_name'], 'doi_settings_pkey');
            $table->foreign('doi_id')->references('doi_id')->on('dois');
        });

        // Add doiId to issue
        Schema::table('issues', function (Blueprint $table) {
            $table->bigInteger('doi_id')->nullable();
            $table->foreign('doi_id')->references('doi_id')->on('dois')->nullOnDelete();
        });

        // Add doiId to publication
        Schema::table('publications', function (Blueprint $table) {
            $table->bigInteger('doi_id')->nullable();
            $table->foreign('doi_id')->references('doi_id')->on('dois')->nullOnDelete();
        });

        // Add doiId to galley
        Schema::table('publication_galleys', function (Blueprint $table) {
            $table->bigInteger('doi_id')->nullable();
            $table->foreign('doi_id')->references('doi_id')->on('dois')->nullOnDelete();
        });

        $this->migrateExistingDataUp();
    }

    /**
     * Reverse the downgrades
     *
     * @throws DowngradeNotSupportedException
     */
    public function down()
    {
        throw new DowngradeNotSupportedException();
    }

    private function migrateExistingDataUp()
    {
        // Find all existing DOIs, move to new DOI objects and add foreign key for pub object
        $this->_migrateDoiSettingsToContext();
        $this->_migratePublicationDoisUp();
        $this->_migrateGalleyDoisUp();
        $this->_migrateIssueDoisUp();
        $this->_migrateCrossrefSettingsToContext();
        $this->_migrateDataciteSettingsToContext();
        //  [ ] Medra ? Ask Bozana
    }

    /**
     * Move DOI settings from plugin_settings to Context (Journal) settings
     */
    private function _migrateDoiSettingsToContext()
    {
        // Get plugin_based settings
        $q = DB::table('plugin_settings')
            ->where('plugin_name', '=', 'doipubidplugin')
            ->select(['context_id','setting_name', 'setting_value']);
        $results = $q->get();

        $data = new \stdClass();
        $data->enabledDois = [];
        $data->doiCreationTime = [];
        $data->enabledDoiTypes = [];
        $data->doiPrefix = [];
        $data->customDoiSuffixType = [];
        $data->doiIssueSuffixPattern = [];
        $data->doiPublicationSuffixPattern = [];
        $data->doiRepresentationSuffixPattern = [];

        // Map to context-based settings
        $results->reduce(function ($carry, $item) {
            switch ($item->setting_name) {
                case 'enabled':
                    $carry->enabledDois[] = [
                        'journal_id' => $item->context_id,
                        'setting_name' => 'enableDois',
                        'setting_value' => (int) $item->setting_value,
                    ];
                    $carry->doiCreationTime[] = [
                        'journal_id' => $item->context_id,
                        'setting_name' => 'doiCreationTime',
                        'setting_value' => 'copyEditCreationTime',
                    ];
                    return $carry;
                case 'enableIssueDoi':
                    if (!isset($carry->enabledDoiTypes[$item->context_id])) {
                        $carry->enabledDoiTypes[$item->context_id] = [
                            'journal_id' => $item->context_id,
                            'setting_name' => 'enabledDoiTypes',
                            'setting_value' => [],
                        ];
                    }

                    if ($item->setting_value === '1') {
                        array_push($carry->enabledDoiTypes[$item->context_id]['setting_value'], 'issue');
                    }
                    return $carry;
                case 'enablePublicationDoi':
                    if (!isset($carry->enabledDoiTypes[$item->context_id])) {
                        $carry->enabledDoiTypes[$item->context_id] = [
                            'journal_id' => $item->context_id,
                            'setting_name' => 'enabledDoiTypes',
                            'setting_value' => [],
                        ];
                    }

                    if ($item->setting_value === '1') {
                        array_push($carry->enabledDoiTypes[$item->context_id]['setting_value'], 'publication');
                    }
                    return $carry;
                case 'enableRepresentationDoi':
                    if (!isset($carry->enabledDoiTypes[$item->context_id])) {
                        $carry->enabledDoiTypes[$item->context_id] = [
                            'journal_id' => $item->context_id,
                            'setting_name' => 'enabledDoiTypes',
                            'setting_value' => [],
                        ];
                    }

                    if ($item->setting_value === '1') {
                        array_push($carry->enabledDoiTypes[$item->context_id]['setting_value'], 'representation');
                    }
                    return $carry;
                case 'doiSuffix':
                    $value = '';
                    switch ($item->setting_value) {
                        case 'default':
                            $value = 'issueBased';
                            break;
                        case 'pattern':
                            $value = 'customPattern';
                            break;
                        case 'customId':
                            $value = 'customId';
                            break;
                    }
                    $carry->customDoiSuffixType[] = [
                        'journal_id' => $item->context_id,
                        'setting_name' => 'customSuffixPattern',
                        'setting_value' => $value,
                    ];
                    return $carry;
                case 'doiPrefix':
                    $carry->doiPrefix[] = [
                        'journal_id' => $item->context_id,
                        'setting_name' => $item->setting_name,
                        'setting_value' => $item->setting_value,
                    ];
                    return $carry;
                case 'doiIssueSuffixPattern':
                    $carry->doiIssueSuffixPattern[] = [
                        'journal_id' => $item->context_id,
                        'setting_name' => $item->setting_name,
                        'setting_value' => $item->setting_value,
                    ];
                    return $carry;
                case 'doiPublicationSuffixPattern':
                    $carry->doiPublicationSuffixPattern[] = [
                        'journal_id' => $item->context_id,
                        'setting_name' => $item->setting_name,
                        'setting_value' => $item->setting_value,
                    ];
                    return $carry;
                case 'doiRepresentationSuffixPattern':
                    $carry->doiRepresentationSuffixPattern[] = [
                        'journal_id' => $item->context_id,
                        'setting_name' => $item->setting_name,
                        'setting_value' => $item->setting_value,
                    ];
                    return $carry;
            }
        }, $data);

        // Prepare insert statements
        $insertData = [];
        foreach ($data->enabledDois as $item) {
            array_push($insertData, $item);
        }
        foreach ($data->doiCreationTime as $item) {
            array_push($insertData, $item);
        }
        foreach ($data->enabledDoiTypes as $item) {
            $item['setting_value'] = json_encode($item['setting_value']);
            array_push($insertData, $item);
        }
        foreach ($data->doiPrefix as $item) {
            array_push($insertData, $item);
        }
        foreach ($data->customDoiSuffixType as $item) {
            array_push($insertData, $item);
        }
        foreach ($data->doiIssueSuffixPattern as $item) {
            array_push($insertData, $item);
        }
        foreach ($data->doiPublicationSuffixPattern as $item) {
            array_push($insertData, $item);
        }
        foreach ($data->doiRepresentationSuffixPattern as $item) {
            array_push($insertData, $item);
        }

        DB::table('journal_settings')->insert($insertData);

        // Add minimum required DOI settings to context if DOI plugin not previously enabled
        $missingDoiSettingsInsertStatement = DB::table('journals')
            ->select('journal_id')
            ->whereNotIn('journal_id', function (Builder $q) {
                $q->select('journal_id')
                    ->from('journal_settings')
                    ->where('setting_name', '=', 'enableDois');
            })
            ->get()
            ->reduce(function ($carry, $item) {
                $carry[] = [
                    'journal_id' => $item->journal_id,
                    'setting_name' => 'enableDois',
                    'setting_value' => 0,
                ];
                $carry[] = [
                    'journal_id' => $item->journal_id,
                    'setting_name' => 'doiCreationTime',
                    'setting_value' => 'copyEditCreationTime'
                ];
                return $carry;
            }, []);

        DB::table('journal_settings')->insert($missingDoiSettingsInsertStatement);
    }

    /**
     * Transfer over batch_id, failedMsg and settings, filter info for importExport to generic plugin transfer
     */
    private function _migrateCrossrefSettingsToContext()
    {
        // ===== Filters ===== //
        DB::table('filters')
            ->where('class_name', '=', 'plugins.importexport.crossref.filter.IssueCrossrefXmlFilter')
            ->update(['class_name' => 'plugins.generic.crossref.filter.IssueCrossrefXmlFilter']);
        DB::table('filters')
            ->where('class_name', '=', 'plugins.importexport.crossref.filter.ArticleCrossrefXmlFilter')
            ->update(['class_name' => 'plugins.generic.crossref.filter.ArticleCrossrefXmlFilter']);

        // ===== Submissions Statuses & Settings ===== //
        // 1. Get submissions with Crossref info
        $publicationDois = DB::table('publications', 'p')
            ->select(['p.submission_id', 'p.doi_id'])
            ->whereNotNull('p.doi_id')
            ->get()
            ->map(function ($item) {
                return [
                    'submission_id' => $item->submission_id,
                    'doi_id' => $item->doi_id
                ];
            });
        $galleyDois = DB::table('publication_galleys', 'pg')
            ->leftJoin('publications as p', 'pg.publication_id', '=', 'p.publication_id')
            ->select(['p.submission_id', 'pg.doi_id'])
            ->whereNotNull('pg.doi_id')
            ->get()
            ->map(function ($item) {
                return [
                    'submission_id' => $item->submission_id,
                    'doi_id' => $item->doi_id
                ];
            });

        // 2. Get all DOIs possibly associated with submission (publications and galleys)
        $doisBySubmission = $publicationDois->concat($galleyDois)
            ->mapToGroups(function ($item) {
                return [$item['submission_id'] => $item['doi_id']];
            })
            ->map(function ($item) {
                return ['doiIds' => $item->all()];
            })->toArray();
        // 3. Apply batchId and failedMsg to all DOIs
        DB::table('submissions', 's')
            ->leftJoin('submission_settings as ss', 's.submission_id', '=', 'ss.submission_id')
            ->whereIn('ss.setting_name', ['crossref::registeredDoi', 'crossref::status', 'crossref::batchId', 'crossref::failedMsg'])
            ->select(['ss.submission_id', 'ss.setting_name', 'ss.setting_value'])
            ->get()
            ->each(function ($item) use (&$doisBySubmission) {
                if (!isset($doisBySubmission[$item->submission_id])) {
                    return;
                }
                switch ($item->setting_name) {
                    case 'crossref::registeredDoi':
                        $doisBySubmission[$item->submission_id]['crossref::registeredDoi'] = $item->setting_value;
                        return;
                    case 'crossref::status':
                        $status = Doi::STATUS_ERROR;
                        if (in_array($item->setting_value, ['found', 'registered', 'markedRegistered'])) {
                            $status = Doi::STATUS_REGISTERED;
                        }
                        $doisBySubmission[$item->submission_id]['status'] = $status;
                        return;
                    case 'crossref::batchId':
                        $doisBySubmission[$item->submission_id]['crossrefplugin_batchId'] = $item->setting_value;
                        return;
                    case 'crossref::failedMsg':
                        $doisBySubmission[$item->submission_id]['crossrefplugin_failedMsg'] = $item->setting_value;
                        return;
                }
            });
        // 4. Apply status to all DOIs
        $doiSettingInserts = [];
        $doiStatusUpdates = [];
        foreach ($doisBySubmission as $item) {
            $doiIds = $item['doiIds'];
            foreach ($doiIds as $doiId) {
                // Settings
                if (isset($item['crossrefplugin_batchId'])) {
                    $doiSettingInserts[] = [
                        'doi_id' => $doiId,
                        'setting_name' => 'crossrefplugin_batchId',
                        'setting_value' => $item['crossrefplugin_batchId'],
                    ];
                }
                if (isset($item['crossrefplugin_failedMsg'])) {
                    $doiSettingInserts[] = [
                        'doi_id' => $doiId,
                        'setting_name' => 'crossrefplugin_failedMsg',
                        'setting_value' => $item['crossrefplugin_failedMsg'],
                    ];
                }

                // Status
                if (isset($item['status'])) {
                    $doiStatusUpdates[$doiId] = ['status' => $item['status']];
                } elseif (isset($item['crossref::registeredDoi'])) {
                    $doiStatusUpdates[$doiId] = ['status' => 3];
                }
            }
        }

        foreach (array_chunk($doiSettingInserts, 100) as $chunkedInserts) {
            DB::table('doi_settings')
                ->insert($chunkedInserts);
        }
        foreach ($doiStatusUpdates as $doiId => $doiStatusUpdate) {
            DB::table('dois')
                ->where('doi_id', '=', $doiId)
                ->update($doiStatusUpdate);
        }

        // 5. Clean up old settings
        DB::table('submission_settings')
            ->whereIn('setting_name', ['crossref::registeredDoi', 'crossref::status', 'crossref::batchId'])
            ->delete();

        // ===== Issue Statuses & Settings ===== //
        // 1. Get issues with Crossref-related info
        $issueData = DB::table('issues', 'i')
            ->leftJoin('issue_settings as iss', 'i.issue_id', '=', 'iss.issue_id')
            ->whereIn('iss.setting_name', ['crossref::registeredDoi', 'crossref::status', 'crossref::batchId', 'crossref::failedMsg'])
            ->select(['i.issue_id', 'i.doi_id', 'iss.setting_name', 'iss.setting_value'])
            ->get()
            ->reduce(function ($carry, $item) {
                if (!isset($carry[$item->issue_id])) {
                    $carry[$item->issue_id] = [
                        'doi_id' => $item->doi_id
                    ];
                }

                $carry[$item->issue_id][$item->setting_name] = $item->setting_value;
                return $carry;
            }, []);

        // 2. Map settings/status insert statements
        $inserts = [];
        $statuses = [];
        foreach ($issueData as $item) {
            // Settings
            if (isset($item['crossref::batchId'])) {
                $inserts[] = [
                    'doi_id' => $item['doi_id'],
                    'setting_name' => 'crossrefplugin_batchId',
                    'setting_value' => $item['crossref::batchId'],
                ];
            }

            if (isset($item['crossref::failedMsg'])) {
                $inserts[] = [
                    'doi_id' => $item['doi_id'],
                    'setting_name' => 'crossrefplugin_failedMsg',
                    'setting_value' => $item['crossref::failedMsg'],
                ];
            }

            // Status
            if (isset($item['crossref::status'])) {
                $status = Doi::STATUS_ERROR;
                if (in_array($item['crossref::status'], ['found', 'registered', 'markedRegistered'])) {
                    $status = Doi::STATUS_REGISTERED;
                } elseif (isset($item['crossref::registeredDoi'])) {
                    $status = Doi::STATUS_REGISTERED;
                }
                $statuses[$item['doi_id']] = ['status' => $status];
            }
        }

        // 3. Insert updated settings/statuses
        foreach (array_chunk($inserts, 100) as $chunkedInserts) {
            DB::table('doi_settings')
                ->insert($chunkedInserts);
        }
        foreach ($statuses as $doiId => $insert) {
            DB::table('dois')
                ->where('doi_id', '=', $doiId)
                ->update($insert);
        }

        // 4. Clean up old settings
        DB::table('issue_settings')
            ->whereIn('setting_name', ['crossref::registeredDoi', 'crossref::status', 'crossref::batchId', 'crossref::failedMsg'])
            ->delete();
    }

    /**
     * Transfer over settings, filter info for importExport to generic plugin transfer
     */
    private function _migrateDataciteSettingsToContext()
    {
        // ===== Filters ===== //
        DB::table('filters')
            ->where('class_name', '=', 'plugins.importexport.datacite.filter.DataciteXmlFilter')
            ->update(['class_name' => 'plugins.generic.datacite.filter.DataciteXmlFilter']);

        // ===== Issues Statuses & Settings ===== //
        // 1. Get issues with Datacite-related info
        $issueData = DB::table('issues', 'i')
            ->leftJoin('issue_settings as iss', 'i.issue_id', '=', 'iss.issue_id')
            ->whereIn('iss.setting_name', ['datacite::registeredDoi', 'datacite::status'])
            ->select(['i.issue_id', 'i.doi_id', 'iss.setting_name', 'iss.setting_value'])
            ->get()
            ->reduce(function ($carry, $item) {
                if (!isset($carry[$item->issue_id])) {
                    $carry[$item->issue_id] = [
                        'doi_id' => $item->doi_id
                    ];
                }

                $carry[$item->issue_id][$item->setting_name] = $item->setting_value;
                return $carry;
            }, []);

        // 2. Map statuses insert statements
        $statuses = [];
        foreach ($issueData as $item) {
            // Status
            if (isset($item['datacite::status'])) {
                $status = Doi::STATUS_ERROR;
                if (in_array($item['datacite::status'], ['found', 'registered', 'markedRegistered'])) {
                    $status = Doi::STATUS_REGISTERED;
                } elseif (isset($item['datacite::registeredDoi'])) {
                    $status = Doi::STATUS_REGISTERED;
                }
                $statuses[$item['doi_id']] = ['status' => $status];
            }
        }

        // 3. Insert updated statuses
        foreach ($statuses as $doiId => $insert) {
            DB::table('dois')
                ->where('doi_id', '=', $doiId)
                ->update($insert);
        }

        // 4. Clean up old settings
        DB::table('issue_settings')
            ->whereIn('setting_name', ['datacite::registeredDoi', 'datacite::status'])
            ->delete();

        // ===== Publications Statuses & Settings ===== //
        // 1. Get publications with Datacite-related info
        $publicationData = DB::table('publications', 'p')
            ->leftJoin('publication_settings as ps', 'p.publication_id', '=', 'ps.publication_id')
            ->whereIn('ps.setting_name', ['datacite::registeredDoi', 'datacite::status'])
            ->select(['p.publication_id', 'p.doi_id', 'ps.setting_name', 'ps.setting_value'])
            ->get()
            ->reduce(function ($carry, $item) {
                if (!isset($carry[$item->publication_id])) {
                    $carry[$item->publication_id] = [
                        'doi_id' => $item->doi_id
                    ];
                }

                $carry[$item->publication_id][$item->setting_name] = $item->setting_value;
                return $carry;
            }, []);

        // 2. Map statuses insert statements
        $statuses = [];
        foreach ($publicationData as $item) {
            // Status
            if (isset($item['datacite::status'])) {
                $status = Doi::STATUS_ERROR;
                if (in_array($item['datacite::status'], ['found', 'registered', 'markedRegistered'])) {
                    $status = Doi::STATUS_REGISTERED;
                } elseif (isset($item['datacite::registeredDoi'])) {
                    $status = Doi::STATUS_REGISTERED;
                }
                $statuses[$item['doi_id']] = ['status' => $status];
            }
        }

        // 3. Insert updated statuses
        foreach ($statuses as $doiId => $insert) {
            DB::table('dois')
                ->where('doi_id', '=', $doiId)
                ->update($insert);
        }

        // 4. Clean up old settings
        DB::table('publication_settings')
            ->whereIn('setting_name', ['datacite::registeredDoi', 'datacite::status'])
            ->delete();

        // ===== Galleys Statuses & Settings ===== //
        // 1. Get galleys with Datacite-related info
        $galleyData = DB::table('publication_galleys', 'pg')
            ->leftJoin('publication_galley_settings as pgs', 'pg.galley_id', '=', 'pgs.galley_id')
            ->whereIn('pgs.setting_name', ['datacite::registeredDoi', 'datacite::status'])
            ->select(['pg.galley_id', 'pg.doi_id', 'pgs.setting_name', 'pgs.setting_value'])
            ->get()
            ->reduce(function ($carry, $item) {
                if (!isset($carry[$item->galley_id])) {
                    $carry[$item->galley_id] = [
                        'doi_id' => $item->doi_id
                    ];
                }

                $carry[$item->galley_id][$item->setting_name] = $item->setting_value;
                return $carry;
            }, []);

        // 2. Map statuses insert statements
        $statuses = [];
        foreach ($issueData as $item) {
            // Status
            if (isset($item['datacite::status'])) {
                $status = Doi::STATUS_ERROR;
                if (in_array($item['datacite::status'], ['found', 'registered', 'markedRegistered'])) {
                    $status = Doi::STATUS_REGISTERED;
                } elseif (isset($item['datacite::registeredDoi'])) {
                    $status = Doi::STATUS_REGISTERED;
                }
                $statuses[$item['doi_id']] = ['status' => $status];
            }
        }

        // 3. Insert updated statuses
        foreach ($statuses as $doiId => $insert) {
            DB::table('dois')
                ->where('doi_id', '=', $doiId)
                ->update($insert);
        }

        // 4. Clean up old settings
        DB::table('publication_galley_settings')
            ->whereIn('setting_name', ['datacite::registeredDoi', 'datacite::status'])
            ->delete();
    }

    /**
     * Move publication DOIs from publication_settings table to DOI objects
     */
    private function _migratePublicationDoisUp(): void
    {
        $q = DB::table('submissions', 's')
            ->select(['s.context_id', 'p.publication_id', 'p.doi_id', 'pss.setting_name', 'pss.setting_value'])
            ->leftJoin('publications as p', 'p.submission_id', '=', 's.submission_id')
            ->leftJoin('publication_settings as pss', 'pss.publication_id', '=', 'p.publication_id')
            ->where('pss.setting_name', '=', 'pub-id::doi');

        $q->chunkById(1000, function ($items) {
            foreach ($items as $item) {
                // Double-check to ensure a DOI object does not already exist for publication
                // TODO: #doi This check would be for pre-flight check
                if ($item->doi_id === null) {
                    $doiId = $this->_addDoi($item->context_id, $item->setting_value);

                    // Add association to newly created DOI to publication
                    DB::table('publications')
                        ->where('publication_id', '=', $item->publication_id)
                        ->update(['doi_id' => $doiId]);
                } else {
                    // Otherwise, update existing DOI object -- TODO: #doi See if this reasonable or problematic approach
                    $this->_updateDoi($item->doi_id, $item->context_id, $item->setting_value);
                }
            }
        }, 'publication_id');

        // Remove pub-id::doi settings entry
        DB::table('publication_settings')
            ->where('setting_name', '=', 'pub-id::doi')
            ->delete();
    }

    /**
     * Move galley DOIs from publication_galley_settings table to DOI objects
     */
    private function _migrateGalleyDoisUp(): void
    {
        $q = DB::table('submissions', 's')
            ->select(['s.context_id', 'pg.galley_id', 'pg.doi_id', 'pgss.setting_name', 'pgss.setting_value'])
            ->leftJoin('publications as p', 'p.submission_id', '=', 's.submission_id')
            ->leftJoin('publication_galleys as pg', 'pg.publication_id', '=', 'p.publication_id')
            ->leftJoin('publication_galley_settings as pgss', 'pgss.galley_id', '=', 'pg.galley_id')
            ->where('pgss.setting_name', '=', 'pub-id::doi');

        $q->chunkById(1000, function ($items) {
            foreach ($items as $item) {
                // Double-check to ensure a DOI object does not already exist for galley
                if ($item->doi_id === null) {
                    $doiId = $this->_addDoi($item->context_id, $item->setting_value);

                    // Add association to newly created DOI to galley
                    DB::table('publication_galleys')
                        ->where('galley_id', '=', $item->galley_id)
                        ->update(['doi_id' => $doiId]);
                } else {
                    // Otherwise update existing DOI object -- TODO: #doi see if this reasonable or problematic approach
                    $this->_updateDoi($item->doi_id, $item->context_id, $item->setting_value);
                }
            }
        }, 'galley_id');
    }

    /**
     * Move issue DOIs from issue_settings table to DOI objects
     */
    private function _migrateIssueDoisUp(): void
    {
        $q = DB::table('issues', 'i')
            ->select(['i.issue_id', 'i.journal_id', 'i.doi_id', 'iss.setting_name', 'iss.setting_value'])
            ->leftJoin('issue_settings as iss', 'iss.issue_id', '=', 'i.issue_id')
            ->where('iss.setting_name', '=', 'pub-id::doi');

        $q->chunkById(1000, function ($items) {
            foreach ($items as $item) {
                // Double-check to ensure a DOI object does not already exist for issue
                if ($item->doi_id === null) {
                    $doiId = $this->_addDoi($item->journal_id, $item->setting_value);

                    // Add association to newly created DOI to issue
                    DB::table('issues')
                        ->where('issue_id', '=', $item->issue_id)
                        ->update(['doi_id' => $doiId]);
                } else {
                    // Otherwise update existing DOI object -- TODO: #doi see if this reasonable or problematic approach
                    $this->_updateDoi($item->doi_id, $item->journal_id, $item->setting_value);
                }
            }
        }, 'issue_id');
    }

    /**
     * Creates a new DOI object for a given context ID and DOI
     *
     */
    private function _addDoi(string $contextId, string $doi): int
    {
        return DB::table('dois')
            ->insertGetId(
                [
                    'context_id' => $contextId,
                    'doi' => $doi,
                ]
            );
    }

    /**
     * Update the context ID and doi for a given DOI object
     *
     */
    private function _updateDoi(int $doiId, string $contextId, string $doi): int
    {
        // TODO: #doi should this be the case
        //     Assume old data to be more correct and update
        return DB::table('dois')
            ->where('doi_id', '=', $doiId)
            ->update(
                [
                    'context_id' => $contextId,
                    'doi' => $doi
                ]
            );
    }
}

if (!PKP_STRICT_MODE) {
    class_alias('\APP\migration\upgrade\v3_4_0\I7014_DoiMigration', '\I7014_DoiMigration');
}
