<?php

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

require(dirname(__FILE__) . '/bootstrap.php');

class resolveAgencyDuplicates extends \PKP\cliTool\CommandLineTool
{
    private string|null $command = null;
    private string|null $agency_name = null;
    private bool $forceFlag = false;
    /**
     * List of potential agencies to choose from along with related fields for resolution.
     *
     * Array shape should look like:
     * `['agency_name' => [
     *      'status' => 'agency_name::status',
     *      'additionalFields': [...],
     *      ]
     * ]`
     */
    private array $agencies = [
        'crossref' => [
            'status' => 'crossref::status',
            'additionalFields' => [
                'crossref::registeredDoi',
                'crossref::batchId',
                'crossref::failedMsg',
            ],
        ],
        'datacite' => [
            'status' => 'datacite::status',
            'additionalFields' => [
                'datacite::registeredDoi',
            ],
        ],
        'medra' => [
            'status' => 'medra::status',
            'additionalFields' => [
                'medra::registeredDoi',
            ],
        ],
    ];

    public function __construct($argv = [])
    {
        parent::__construct($argv);

        $forceFlagIndex = array_search('--force', $this->argv);
        if ($forceFlagIndex) {
            $this->forceFlag = true;
            array_splice($this->argv, $forceFlagIndex, 1);
        }

        if (sizeof($this->argv) == 0) {
            $this->exitWithUsageMessage();
        }
        $this->command = array_shift($this->argv);
        $this->agency_name = array_shift($this->argv);

        if (
            $this->command === 'resolve' &&
            ($this->agency_name === null || !in_array($this->agency_name, array_keys($this->agencies)))
        ) {
            $this->exitWithUsageMessage();
        }
    }

    public function usage()
    {
        $agencies = implode(', ', array_keys($this->agencies));
        echo "Script to resolve DOI registration agency duplication pre-3.4.\n"
            . "NB: If a conflict exists for a submission, the corresponding publication objects (galleys, etc.) will also be cleaned up.\n\n"
            . "Usage:\n"
            . "{$this->scriptName} resolve [agency_name] --force : Remove conflicting DOI registration info, keeping agency_name.\n"
            . "{$this->scriptName} test : Returns list of conflicting items\n\n"
            . "Options:\n"
            . "agency_name      One of: {$agencies}.\n"
            . "--force          Force resolve operation. Will not delete data without it.\n";
    }

    public function execute(): void
    {
        switch ($this->command) {
            case 'resolve':
                $this->resolve();
                break;
            case 'test':
                $this->test();
                break;
            default:
                $this->exitWithUsageMessage();
                break;
        }
    }

    private function resolve(): void
    {
        if (!$this->forceFlag) {
            $this->print('Warning! This is a destructive operation. Ensure you have a database backup and re-run this command with the `--force` flag.');
            exit(0);
        }

        $agencies = array_filter($this->agencies, fn ($key) => $key !== $this->agency_name, ARRAY_FILTER_USE_KEY);
        $this->print('Removing duplicate registration info for ' . implode(', ', array_keys($agencies)) . '...');

        $agencyFields = array_reduce($agencies, fn ($carry, $item) => array_merge($carry, [$item['status']], $item['additionalFields']), []);
        $submissionIds = $this->getSubmissionIds();
        $galleyIds = $this->getGalleyIds();
        $issueIds = $this->getIssueIds();

        DB::table('submission_settings')
            ->whereIn('setting_name', $agencyFields)
            ->whereIn('submission_id', $submissionIds)
            ->delete();
        $this->print("Removed duplicates for {$submissionIds->count()} submission(s)");

        DB::table('publication_galley_settings')
            ->whereIn('setting_name', $agencyFields)
            ->whereIn('galley_id', $galleyIds)
            ->delete();
        $this->print("Removed duplicates for {$galleyIds->count()} galley(s)");

        DB::table('issue_settings')
            ->whereIn('setting_name', $agencyFields)
            ->whereIn('issue_id', $issueIds)
            ->delete();
        $this->print("Removed duplicates for {$issueIds->count()} issues(s)");
    }

    private function test(): void
    {
        $this->print('IDs with duplicate DOI registration metadata:');
        $this->print("Submissions: {$this->getSubmissionIds()}");
        $this->print("Galleys: {$this->getGalleyIds()}");
        $this->print("Issues: {$this->getIssueIds()}");
    }

    private function getSubmissionIds(): Collection
    {
        return DB::table('submission_settings')
            ->whereIn('setting_name', array_map(fn ($item) => $item['status'], $this->agencies))
            ->groupBy('submission_id')
            ->havingRaw('COUNT(submission_id) > 1')
            ->pluck('submission_id');
    }

    /**
     * Gets galley IDs that have duplicates OR are associated with submissions with conflicts
     */
    private function getGalleyIds(): Collection
    {
        return DB::table('publication_galley_settings')
            ->whereIn('setting_name', array_map(fn ($item) => $item['status'], $this->agencies))
            ->orWhereIn('galley_id', function (Builder $q) {
                $q->select('pg.galley_id')
                    ->from('publication_galleys', 'pg')
                    ->leftJoin('publications as p', 'pg.publication_id', '=', 'p.publication_id')
                    ->leftJoin('submissions as s', 'p.submission_id', '=', 's.submission_id')
                    ->whereIn('s.submission_id', $this->getSubmissionIds());
            })
            ->groupBy('galley_id')
            ->havingRaw('COUNT(galley_id) > 1')
            ->pluck('galley_id');
    }

    private function getIssueIds(): Collection
    {
        return DB::table('issue_settings')
            ->whereIn('setting_name', array_map(fn ($item) => $item['status'], $this->agencies))
            ->groupBy('issue_id')
            ->havingRaw('COUNT(issue_id) > 1')
            ->pluck('issue_id');
    }

    protected function print(string $message): void
    {
        echo $message . PHP_EOL;
    }
}

$tool = new resolveAgencyDuplicates($argv ?? []);
$tool->execute();
