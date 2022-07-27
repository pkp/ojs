<?php

namespace APP\issue;

use APP\facades\Repo;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use PKP\core\interfaces\CollectorInterface;
use PKP\core\PKPApplication;
use PKP\doi\Doi;
use PKP\plugins\HookRegistry;

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

    /**
     * Set context issues filter
     *
     * @return $this
     */
    public function filterByContextIds(?array $contextIds): self
    {
        $this->contextIds = $contextIds;
        return $this;
    }

    /**
     * Set result order and direction based on an ORDERBY_* constant
     *
     * @return $this
     */
    public function orderBy(string $orderByConstant): self
    {
        switch ($orderByConstant) {
            case self::ORDERBY_LAST_MODIFIED:
                $this->resultOrderings = [
                    [
                        'orderBy' => 'i.last_modified',
                        'direction' => self::ORDER_DIR_DESC
                    ]
                ];
                break;
            case self::ORDERBY_SEQUENCE:
                $this->resultOrderings = [
                    [
                        'orderBy' => 'o.seq',
                        'direction' => self::ORDER_DIR_ASC
                    ]
                ];
                break;
            case self::ORDERBY_PUBLISHED_ISSUES:
                $this->resultOrderings = [
                    [
                        'orderBy' => 'o.seq',
                        'direction' => self::ORDER_DIR_ASC
                    ],
                    [
                        'orderBy' => 'currentIssue',
                        'direction' => self::ORDER_DIR_DESC
                    ],
                    [
                        'orderBy' => 'i.date_published',
                        'direction' => self::ORDER_DIR_DESC
                    ]
                ];
                break;
            case self::ORDERBY_UNPUBLISHED_ISSUES:
                $this->resultOrderings = [
                    [
                        'orderBy' => 'i.year',
                        'direction' => self::ORDER_DIR_ASC
                    ],
                    [
                        'orderBy' => 'i.volume',
                        'direction' => self::ORDER_DIR_ASC
                    ],
                    [
                        'orderBy' => 'i.number',
                        'direction' => self::ORDER_DIR_ASC
                    ]
                ];
                break;
            case self::ORDERBY_SHELF:
                $this->resultOrderings = [
                    [
                        'orderBy' => self::ORDER_CURRENT_ISSUE,
                        'direction' => self::ORDER_DIR_DESC
                    ],
                    [
                        'orderBy' => 'i.year',
                        'direction' => self::ORDER_DIR_ASC
                    ],
                    [
                        'orderBy' => 'i.volume',
                        'direction' => self::ORDER_DIR_ASC
                    ],
                    [
                        'orderBy' => 'i.number',
                        'direction' => self::ORDER_DIR_ASC
                    ]
                ];
                break;
            default:
                throw new \InvalidArgumentException('One of ORDERBY_* constants must be provided');
        }
        return $this;
    }

    /**
     * Set published filter
     *
     * @return $this
     */
    public function filterByPublished(bool $isPublished): self
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
    public function filterByVolumes(?array $volumes): self
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
    public function filterByNumbers(?array $numbers): self
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
    public function filterByYears(?array $years): self
    {
        $this->years = $years;
        return $this;
    }

    /**
     * set urlPath filter
     *
     * @return $this
     */
    public function filterByUrlPath(string $urlPath): self
    {
        $this->urlPath = $urlPath;
        return $this;
    }

    /**
     * Set titles filter
     *
     * @return $this
     */
    public function filterByTitles(array $titles): self
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
    public function filterByDoiStatuses(?array $statuses): self
    {
        $this->doiStatuses = $statuses;
        return $this;
    }

    /**
     * Limit results to submissions that do/don't have any DOIs assign to their sub objects
     *
     * @param array|null $enabledDoiTypes TYPE_* constants to consider when checking submission has DOIs
     *
     * @return $this
     */
    public function filterByHasDois(?bool $hasDois, ?array $enabledDoiTypes = null): self
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
    public function searchPhrase(?string $phrase): self
    {
        $this->searchPhrase = $phrase;
        return $this;
    }

    /**
     * Limit the number of objects retrieved
     *
     * @return $this
     */
    public function limit(?int $count): self
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
    public function offset(?int $offset): self
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
        $q->when($this->titles !== null, function (Builder $q) {
            $q->whereIn('i.issue_id', function (Builder $q) {
                $q->select('issue_id')
                    ->from($this->dao->settingsTable)
                    ->where('setting_name', '=', 'title')
                    ->whereIn('setting_value', $this->titles);
            });
        });

        // Context
        // Never permit a query without a context_id unless the PKPApplication::CONTEXT_ID_ALL wildcard
        // has been explicitly set
        if (!in_array(PKPApplication::CONTEXT_ID_ALL, $this->contextIds)) {
            $q->whereIn('i.journal_id', $this->contextIds);
        }

        // Published
        $q->when($this->isPublished !== null, function (Builder $q) {
            $q->where('i.published', '=', $this->isPublished ? 1 : 0);
        });

        // Volumes
        $q->when($this->volumes !== null, function (Builder $q) {
            $q->whereIn('i.volume', $this->volumes);
        });

        // Numbers
        $q->when($this->numbers !== null, function (Builder $q) {
            $q->whereIn('i.number', $this->numbers);
        });

        // Years
        $q->when($this->years !== null, function (Builder $q) {
            $q->whereIn('i.year', $this->years);
        });

        // URL path
        $q->when($this->urlPath !== null, function (Builder $q) {
            $q->where('i.url_path', '=', $this->urlPath);
        });

        // DOI statuses
        $q->when($this->doiStatuses !== null, function (Builder $q) {
            $q->whereIn('i.issue_id', function (Builder $q) {
                $q->select('i.issue_id')
                    ->from('issues as i')
                    ->leftJoin('dois as d', 'd.doi_id', '=', 'i.doi_id')
                    ->whereIn('d.status', $this->doiStatuses);
            });
        });

        // By whether issue has DOI assigned
        $q->when($this->hasDois !== null, function (Builder $q) {
            $q->whereIn('i.issue_id', function (Builder $q) {
                $q->select('current_i.issue_id')
                    ->from('issues', 'current_i')
                    ->where(function (Builder $q) {
                        $q->when(in_array(Repo::doi()::TYPE_ISSUE, $this->enabledDoiTypes), function (Builder $q) {
                            $q->when($this->hasDois === true, function (Builder $q) {
                                $q->whereNotNull('current_i.doi_id');
                            });
                            $q->when($this->hasDois === false, function (Builder $q) {
                                $q->whereNull('current_i.doi_id');
                            });
                        });
                    });
            });
        });

        // Search phrase
        if ($this->searchPhrase !== null) {
            $searchPhrase = $this->searchPhrase;

            // Add support for searching for the volume, number and year
            // using the localized issue identification formats. In
            // en_US this will match Vol. 1. No. 1 (2018) against:
            // i.volume = 1 AND i.number = 1 AND i.year = 2018
            $volume = '';
            $number = '';
            $year = '';
            $volumeRegex = '/' . preg_quote(__('issue.vol')) . '\s\S/';
            if (preg_match($volumeRegex, $searchPhrase, $matches)) {
                $volume = trim(str_replace(__('issue.vol'), '', $matches[0]));
                $searchPhrase = str_replace($matches[0], '', $searchPhrase);
            }
            $numberRegex = '/' . preg_quote(__('issue.no')) . '\s\S/';
            if (preg_match($numberRegex, $searchPhrase, $matches)) {
                $number = trim(str_replace(__('issue.no'), '', $matches[0]));
                $searchPhrase = str_replace($matches[0], '', $searchPhrase);
            }
            if (preg_match('/\(\d{4}\)\:?/', $searchPhrase, $matches)) {
                $year = substr($matches[0], 1, 4);
                $searchPhrase = str_replace($matches[0], '', $searchPhrase);
            }
            if ($volume !== '' || $number !== '' || $year !== '') {
                $q->where(function ($q) use ($volume, $number, $year) {
                    if ($volume) {
                        $q->where('i.volume', '=', $volume);
                    }
                    if ($number) {
                        $q->where('i.number', '=', $number);
                    }
                    if ($year) {
                        $q->where('i.year', '=', $year);
                    }
                });
            }

            $words = array_unique(explode(' ', $searchPhrase));
            if (count($words)) {
                foreach ($words as $word) {
                    $likePattern = DB::raw("CONCAT('%', LOWER(?), '%')");
                    $q->whereIn('i.issue_id', function (Builder $q) use ($likePattern, $word) {
                        $q->select('iss_t.issue_id')
                            ->from($this->dao->settingsTable, 'iss_t')
                            ->where('iss_t.setting_name', '=', 'title')
                            ->where(DB::raw('lower(iss_t.setting_value)'), 'LIKE', $likePattern)->addBinding($word);
                    })
                        ->orWhereIn('i.issue_id', function (Builder $q) use ($likePattern, $word) {
                            $q->select('iss_d.issue_id')
                                ->from($this->dao->settingsTable, 'iss_d')
                                ->where('iss_d.setting_name', '=', 'name')
                                ->where(DB::raw('lower(iss_d.setting_value)'), 'LIKE', $likePattern)->addBinding($word);
                        });

                    // Match any four-digit number to the year
                    if (ctype_digit($word) && strlen($word) === 4) {
                        $q->orWhere('i.year', '=', $word);
                    }
                }
            }
        }

        // Ordering for query-builder-based and legacy-based orderings
        $q->when($this->resultOrderings !== null, function (Builder $q) {
            foreach ($this->resultOrderings as $resultOrdering) {
                if ($resultOrdering['orderBy'] == self::ORDER_CURRENT_ISSUE) {
                    // Custom query to order by current issue status from the journals table
                    $q->leftJoin('journals as j', 'j.current_issue_id', '=', 'i.issue_id')
                        ->orderByRaw('CASE WHEN j.current_issue_id IS NOT NULL then 1 else 0 END ' . $resultOrdering['direction']);
                } else {
                    $q->orderBy($resultOrdering['orderBy'], $resultOrdering['direction']);
                }
            }
        });

        // Limit and offset results for pagination
        $q->when($this->count !== null, function (Builder $q) {
            $q->limit($this->count);
        });

        $q->when($this->offset !== null, function (Builder $q) {
            $q->offset($this->offset);
        });

        // Add app-specific query statements
        HookRegistry::call('Issue::getMany::queryObject', [&$q, $this]);

        return $q;
    }
}
