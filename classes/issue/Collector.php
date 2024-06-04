<?php
/**
 * @file classes/issue/Collector.php
 *
 * Copyright (c) 2014-2023 Simon Fraser University
 * Copyright (c) 2000-2023 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class Collector
 *
 * @brief A helper class to configure a query Builder to get a collection of issues
 */

namespace APP\issue;

use APP\facades\Repo;
use Exception;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\LazyCollection;
use InvalidArgumentException;
use PKP\core\interfaces\CollectorInterface;
use PKP\core\PKPApplication;
use PKP\plugins\Hook;

class Collector implements CollectorInterface
{
    public const ORDERBY_DATE_PUBLISHED = 'datePublished';
    public const ORDERBY_LAST_MODIFIED = 'lastModified';
    public const ORDERBY_SEQUENCE = 'seq';
    public const ORDERBY_PUBLISHED_ISSUES = 'publishedIssues';
    public const ORDERBY_UNPUBLISHED_ISSUES = 'unpublishedIssues';
    public const ORDERBY_SHELF = 'shelf';
    public const ORDER_DIR_ASC = 'ASC';
    public const ORDER_DIR_DESC = 'DESC';
    private const ORDER_CURRENT_ISSUE = 'currentIssue';

    public DAO $dao;

    public ?int $count = null;

    public ?int $offset = null;

    /** @var array|null Context ID or PKPApplication::CONTEXT_ID_ALL to get from all contexts */
    public ?array $contextIds = null;

    /** @var array|null List of issue IDs to include */
    public ?array $issueIds = null;

    /** @var array|null order and direction pairing for queries */
    public ?array $resultOrderings = null;

    /** @var bool|null return published issues */
    public ?bool $isPublished = null;

    /** @var array|null return issues in volume(s) */
    public ?array $volumes = null;

    /** @var array|null return issues with number(s) */
    public ?array $numbers = null;

    /** @var array|null return issues with year(s) */
    public ?array $years = null;

    /** @var array|null return issues that match a title */
    public ?array $titles = null;

    public ?array $doiStatuses = null;

    public ?bool $hasDois = null;

    /** @var array Which DOI types should be considered when checking if a submission has DOIs set */
    public array $enabledDoiTypes = [];

    /** @var string|null Returns Issue by URL path  */
    public ?string $urlPath = null;

    /** @var string|null return issues which match words from this search phrase */
    public ?string $searchPhrase = null;

    public function __construct(DAO $dao)
    {
        $this->dao = $dao;
    }

    /** @copydoc DAO::getCount() */
    public function getCount(): int
    {
        return $this->dao->getCount($this);
    }

    /**
     * @copydoc DAO::getIds()
     *
     * @return Collection<int,int>
     */
    public function getIds(): Collection
    {
        return $this->dao->getIds($this);
    }

    /**
     * @copydoc DAO::getMany()
     *
     * @return LazyCollection<int,Issue>
     */
    public function getMany(): LazyCollection
    {
        return $this->dao->getMany($this);
    }

    /**
     * Set context issues filter
     *
     * @return $this
     */
    public function filterByContextIds(?array $contextIds): static
    {
        $this->contextIds = $contextIds;
        return $this;
    }

    /**
     * Set issue ID filter
     *
     * @return $this
     */
    public function filterByIssueIds(?array $issueIds): static
    {
        $this->issueIds = $issueIds;
        return $this;
    }

    /**
     * Set result order and direction based on an ORDERBY_* constant
     *
     * @return $this
     */
    public function orderBy(string $orderByConstant): static
    {
        $this->resultOrderings = match ($orderByConstant) {
            static::ORDERBY_DATE_PUBLISHED => [
                ['orderBy' => 'i.date_published', 'direction' => static::ORDER_DIR_DESC]
            ],
            static::ORDERBY_LAST_MODIFIED => [
                ['orderBy' => 'i.last_modified', 'direction' => static::ORDER_DIR_DESC]
            ],
            static::ORDERBY_SEQUENCE => [
                ['orderBy' => 'o.seq', 'direction' => static::ORDER_DIR_ASC]
            ],
            static::ORDERBY_PUBLISHED_ISSUES => [
                ['orderBy' => 'o.seq', 'direction' => static::ORDER_DIR_ASC],
                ['orderBy' => 'currentIssue', 'direction' => static::ORDER_DIR_DESC],
                ['orderBy' => 'i.date_published', 'direction' => static::ORDER_DIR_DESC]
            ],
            static::ORDERBY_UNPUBLISHED_ISSUES => [
                ['orderBy' => 'i.year', 'direction' => static::ORDER_DIR_ASC],
                ['orderBy' => 'i.volume', 'direction' => static::ORDER_DIR_ASC],
                ['orderBy' => 'i.number', 'direction' => static::ORDER_DIR_ASC]
            ],
            static::ORDERBY_SHELF => [
                ['orderBy' => static::ORDER_CURRENT_ISSUE, 'direction' => static::ORDER_DIR_DESC],
                ['orderBy' => 'i.year', 'direction' => static::ORDER_DIR_ASC],
                ['orderBy' => 'i.volume', 'direction' => static::ORDER_DIR_ASC],
                ['orderBy' => 'i.number', 'direction' => static::ORDER_DIR_ASC]
            ],
            default => throw new InvalidArgumentException('One of ORDERBY_* constants must be provided')
        };
        return $this;
    }

    /**
     * Set published filter
     *
     * @return $this
     */
    public function filterByPublished(bool $isPublished): static
    {
        $this->isPublished = $isPublished;
        return $this;
    }

    /**
     * Set volumes filter
     *
     * @param int[]|null $volumes
     *
     * @return $this
     */
    public function filterByVolumes(?array $volumes): static
    {
        $this->volumes = $volumes;
        return $this;
    }

    /**
     * Set volumes filter
     *
     * @param int[]|null $numbers
     *
     * @return $this
     */
    public function filterByNumbers(?array $numbers): static
    {
        $this->numbers = $numbers;
        return $this;
    }

    /**
     * Set volumes filter
     *
     * @param int[]|null $years
     *
     * @return $this
     */
    public function filterByYears(?array $years): static
    {
        $this->years = $years;
        return $this;
    }

    /**
     * set urlPath filter
     *
     * @return $this
     */
    public function filterByUrlPath(string $urlPath): static
    {
        $this->urlPath = $urlPath;
        return $this;
    }

    /**
     * Set titles filter
     *
     * @return $this
     */
    public function filterByTitles(array $titles): static
    {
        $this->titles = $titles;
        return $this;
    }

    /**
     * Limit results to issues with these statuses
     *
     * @param array|null $statuses One or more of DOI::STATUS_* constants
     *
     */
    public function filterByDoiStatuses(?array $statuses): static
    {
        $this->doiStatuses = $statuses;
        return $this;
    }

    /**
     * Limit results to issues that do/don't have any DOIs assign to their sub objects
     *
     * @param array|null $enabledDoiTypes TYPE_* constants to consider when checking issue has DOIs
     *
     * @return $this
     */
    public function filterByHasDois(?bool $hasDois, ?array $enabledDoiTypes = null): static
    {
        $this->hasDois = $hasDois;
        $this->enabledDoiTypes = $enabledDoiTypes === null ? [Repo::doi()::TYPE_ISSUE] : $enabledDoiTypes;
        return $this;
    }

    /**
     * Set query search phrase
     *
     * @return $this
     */
    public function searchPhrase(?string $phrase): static
    {
        $this->searchPhrase = $phrase;
        return $this;
    }

    /**
     * Limit the number of objects retrieved
     *
     * @return $this
     */
    public function limit(?int $count): static
    {
        $this->count = $count;
        return $this;
    }

    /**
     * Offset the number of objects retrieved, for example to
     * retrieve the second page of contents
     *
     * @return $this
     */
    public function offset(?int $offset): static
    {
        $this->offset = $offset;
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function getQueryBuilder(): Builder
    {
        $q = DB::table($this->dao->table, 'i')
            ->select('i.*')
            ->leftJoin('custom_issue_orders as o', 'o.issue_id', '=', 'i.issue_id');

        // Issue titles (exact matches)
        $q->when(
            $this->titles !== null,
            fn (Builder $q) =>
            $q->whereIn(
                'i.issue_id',
                fn (Builder $q) =>
                $q->select('issue_id')
                    ->from($this->dao->settingsTable)
                    ->where('setting_name', '=', 'title')
                    ->whereIn('setting_value', $this->titles)
            )
        );

        // Context
        // Never permit a query without a context_id unless the PKPApplication::CONTEXT_ID_ALL wildcard
        // has been set explicitly.
        if (!isset($this->contextIds)) {
            throw new Exception('Submissions can not be retrieved without a context id. Pass the Application::CONTEXT_ID_ALL wildcard to get submissions from any context.');
        } elseif (!in_array(PKPApplication::CONTEXT_ID_ALL, $this->contextIds)) {
            $q->whereIn('i.journal_id', $this->contextIds);
        }

        // Issue IDs
        $q->when($this->issueIds !== null, fn (Builder $q) => $q->whereIn('i.issue_id', $this->issueIds));
        // Published
        $q->when($this->isPublished !== null, fn (Builder $q) => $q->where('i.published', '=', $this->isPublished ? 1 : 0));
        // Volumes
        $q->when($this->volumes !== null, fn (Builder $q) => $q->whereIn('i.volume', $this->volumes));
        // Numbers
        $q->when($this->numbers !== null, fn (Builder $q) => $q->whereIn('i.number', $this->numbers));
        // Years
        $q->when($this->years !== null, fn (Builder $q) => $q->whereIn('i.year', $this->years));
        // URL path
        $q->when($this->urlPath !== null, fn (Builder $q) => $q->where('i.url_path', '=', $this->urlPath));

        // DOI statuses
        $q->when(
            $this->doiStatuses !== null,
            fn (Builder $q) =>
            $q->whereIn(
                'i.issue_id',
                fn (Builder $q) =>
                $q->select('i.issue_id')
                    ->from('issues as i')
                    ->leftJoin('dois as d', 'd.doi_id', '=', 'i.doi_id')
                    ->whereIn('d.status', $this->doiStatuses)
            )
        );

        // By whether issue has DOI assigned
        $q->when(
            $this->hasDois !== null,
            fn (Builder $q) =>
            $q->whereIn(
                'i.issue_id',
                fn (Builder $q) =>
                $q->select('current_i.issue_id')
                    ->from('issues', 'current_i')
                    ->when(
                        in_array(Repo::doi()::TYPE_ISSUE, $this->enabledDoiTypes),
                        fn (Builder $q) =>
                        $this->hasDois ? $q->whereNotNull('current_i.doi_id') : $q->whereNull('current_i.doi_id')
                    )
            )
        );

        // Search phrase
        if ($this->searchPhrase !== null) {
            $searchPhrase = $this->searchPhrase;

            // Add support for searching for the volume, number and year
            // using the localized issue identification formats. In
            // en this will match Vol. 1. No. 1 (2018) against:
            // i.volume = 1 AND i.number = 1 AND i.year = 2018
            $volume = '';
            $volumeRegex = '/\b' . preg_quote(__('issue.vol'), '/') . '\s+(\d+)/';
            if (preg_match($volumeRegex, $searchPhrase, $matches)) {
                [$found, $volume] = $matches;
                $searchPhrase = str_replace($found, '', $searchPhrase);
            }
            $number = '';
            $numberRegex = '/\b' . preg_quote(__('issue.no'), '/') . '\s+(\S+)\b/';
            if (preg_match($numberRegex, $searchPhrase, $matches)) {
                [$found, $number] = $matches;
                $searchPhrase = str_replace($found, '', $searchPhrase);
            }
            $year = '';
            if (preg_match('/\((\d{4})\)/', $searchPhrase, $matches)) {
                [$found, $year] = $matches;
                $searchPhrase = str_replace($found, '', $searchPhrase);
            }
            $q->when(
                strlen($volume) || $number !== '' || $year !== '',
                fn (Builder $q) => $q->where(
                    fn (Builder $q) => $q
                        ->when($volume !== '', fn (Builder $q) => $q->where('i.volume', '=', $volume))
                        ->when($number !== '', fn (Builder $q) => $q->where('i.number', '=', $number))
                        ->when($year !== '', fn (Builder $q) => $q->where('i.year', '=', $year))
                )
            );

            $words = array_filter(array_unique(explode(' ', $searchPhrase)), 'strlen');
            if (count($words)) {
                $likePattern = DB::raw("CONCAT('%', LOWER(?), '%')");
                foreach ($words as $word) {
                    $q->where(
                        fn (Builder $q) => $q
                            ->whereIn(
                                'i.issue_id',
                                fn (Builder $q) =>
                                $q->select('iss_t.issue_id')
                                    ->from($this->dao->settingsTable, 'iss_t')
                                    ->where('iss_t.setting_name', '=', 'title')
                                    ->where(DB::raw('LOWER(iss_t.setting_value)'), 'LIKE', $likePattern)->addBinding($word)
                            )
                            ->orWhereIn(
                                'i.issue_id',
                                fn (Builder $q) =>
                                $q->select('iss_d.issue_id')
                                    ->from($this->dao->settingsTable, 'iss_d')
                                    ->where('iss_d.setting_name', '=', 'name')
                                    ->where(DB::raw('LOWER(iss_d.setting_value)'), 'LIKE', $likePattern)->addBinding($word)
                            )
                        // Match any four-digit number to the year
                            ->when(ctype_digit($word) && strlen($word) === 4, fn (Builder $q) => $q->orWhere('i.year', '=', $word))
                    );
                }
            }
        }

        // Ordering for query-builder-based and legacy-based orderings
        $q->when($this->resultOrderings !== null, function (Builder $q) {
            foreach ($this->resultOrderings as $resultOrdering) {
                if ($resultOrdering['orderBy'] === static::ORDER_CURRENT_ISSUE) {
                    // Custom query to order by current issue status from the journals table
                    $q->leftJoin('journals as j', 'j.current_issue_id', '=', 'i.issue_id')
                        ->orderByRaw('CASE WHEN j.current_issue_id IS NOT NULL then 1 else 0 END ' . $resultOrdering['direction']);
                } else {
                    $q->orderBy($resultOrdering['orderBy'], $resultOrdering['direction']);
                }
            }
        });

        // Limit and offset results for pagination
        $q->when($this->count !== null, fn (Builder $q) => $q->limit($this->count));
        $q->when($this->offset !== null, fn (Builder $q) => $q->offset($this->offset));

        // Add app-specific query statements
        Hook::call('Issue::getMany::queryObject', [&$q, $this]);

        return $q;
    }
}
