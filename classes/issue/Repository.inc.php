<?php

namespace APP\issue;

use APP\core\Request;
use APP\facades\Repo;
use APP\file\IssueFileManager;
use APP\file\PublicFileManager;
use APP\journal\JournalDAO;
use APP\journal\SectionDAO;
use APP\publication\Publication;
use Illuminate\Support\Collection;
use Illuminate\Support\LazyCollection;
use PKP\db\DAORegistry;
use PKP\plugins\HookRegistry;
use PKP\services\PKPSchemaService;
use PKP\validation\ValidatorFactory;

class Repository
{
    /** @var DAO $dao */
    public $dao;

    /** @var string $schemaMap The name of the class to map this entity to its schema */
    public $schemaMap = maps\Schema::class;

    /** @var Request $request */
    protected $request;

    /** @var PKPSchemaService $schemaService */
    protected $schemaService;

    // TODO: Explicitly excluding caching for now as it wasn't actually setting data to cache

    public function __construct(DAO $dao, Request $request, PKPSchemaService $schemaService)
    {
        $this->dao = $dao;
        $this->request = $request;
        $this->schemaService = $schemaService;
    }

    /** @copydoc  DAO::newDataObject() */
    public function newDataObject(array $params = []): Issue
    {
        $object = $this->dao->newDataObject();
        if (!empty($params)) {
            $object->setAllData($params);
        }
        return $object;
    }

    /** @copydoc DAO::exists() */
    public function exists(int $id): bool
    {
        return $this->dao->exists($id);
    }

    /** @copydoc DAO::get()
     * TODO: Function signature should stick with ID, but previous DAO expected $useCache = false as default
     */
    public function get(int $id): ?Issue
    {
        // TODO: Caching as currently setup never properly caches objects and always fires a _cacheMiss()
//        if ($useCache) {
//            $cache = $this->dao->_getCache('issues');
//            $returner = $cache->get($id);
//            if ($returner && $contextId != null && $contextId != $returner->getJournalId()) {
//                $returner = null;
//            }
//            return $returner;
//        }

        return $this->dao->get($id);
    }

    /** @copydoc DAO::getCount() */
    public function getCount(Collector $query): int
    {
        return $this->dao->getCount($query);
    }

    /** @copydoc DAO::getIds() */
    public function getIds(Collector $query): Collection
    {
        return $this->dao->getIds($query);
    }

    /** @copydoc DAO::getMany() */
    public function getMany(Collector $query): LazyCollection
    {
        return $this->dao->getMany($query);
    }

    /** @copydoc DAO::getCollector() */
    public function getCollector(): Collector
    {
        return app(Collector::class);
    }

    /**
     * Get an instance of the map class for mapping
     * announcements to their schema
     */
    public function getSchemaMap(): maps\Schema
    {
        return app('maps')->withExtensions($this->schemaMap);
    }

    /**
     * Validate properties for an issue
     *
     * Perform validation checks on data used to add or edit an issue.
     *
     * @param array $props A key/value array with the new data to validate
     * @param array $allowedLocales The context's supported locales
     * @param string $primaryLocale The context's primary locale
     *
     * @throws \Exception
     *
     * @return array A key/value array with validation errors. Empty if no errors
     */
    public function validate(?Issue $object, array $props, array $allowedLocales, string $primaryLocale): array
    {
        $errors = [];

        $validator = ValidatorFactory::make(
            $props,
            $this->schemaService->getValidationRules($this->dao->schema, $allowedLocales),
        );

        // Check required fields
        ValidatorFactory::required(
            $validator,
            $object,
            $this->schemaService->getRequiredProps($this->dao->schema),
            $this->schemaService->getMultilingualProps($this->dao->schema),
            $allowedLocales,
            $primaryLocale
        );

        // Check for input from disallowed locales
        ValidatorFactory::allowedLocales($validator, $this->schemaService->getMultilingualProps($this->dao->schema), $allowedLocales);

        if ($validator->fails()) {
            $errors = $this->schemaService->formatValidationErrors($validator->errors(), $this->schemaService->get($this->dao->schema), $allowedLocales);
        }

        HookRegistry::call('Issue::validate', [&$errors, $object, $props, $allowedLocales, $primaryLocale]);

        return $errors;
    }

    /** @copydoc DAO::insert() */
    public function add(Issue $issue): int
    {
        return $this->dao->insert($issue);
    }

    /** @copydoc DAO::update() */
    public function edit(Issue $issue, array $params)
    {
        $newIssue = $this->newDataObject(array_merge($issue->_data, $params));

        HookRegistry::call('Issue::edit', [&$newIssue, $issue, $params]);

        $this->dao->update($newIssue);
    }

    /** @copydoc DAO::delete() */
    public function delete(Issue $issue)
    {
        $publicFileManager = new PublicFileManager();

        if (is_array($issue->getCoverImage(null))) {
            foreach ($issue->getCoverImage(null) as $coverImage) {
                if ($coverImage != '') {
                    $publicFileManager->removeContextFile($issue->getJournalId(), $coverImage);
                }
            }
        }

        $issueId = $issue->getId();

        // Delete issue-specific ordering if it exists.
        $sectionDao = DAORegistry::getDAO('SectionDAO'); /** @var SectionDAO $sectionDao */
        $sectionDao->deleteCustomSectionOrdering($issueId);

        // Delete published issue galleys and issue files
        $issueGalleyDao = DAORegistry::getDAO('IssueGalleyDAO'); /** @var IssueGalleyDAO $issueGalleyDao */
        $issueGalleyDao->deleteByIssueId($issueId);

        $issueFileDao = DAORegistry::getDAO('IssueFileDAO'); /** @var IssueFileDAO $issueFileDao */
        $issueFileDao->deleteByIssueId($issueId);

        $issueFileManager = new IssueFileManager($issueId);
        $issueFileManager->deleteIssueTree();

        $this->dao->deleteCustomIssueOrdering($issueId);

        $this->dao->delete($issue);
    }

    /**
     * Delete a collection of issues
     */
    public function deleteMany(Collector $collector)
    {
        $issueIds = $this->getIds($collector);
        foreach ($issueIds as $issueId) {
            $this->dao->deleteById($issueId);
        }
    }

    /**
     * Retrieve current issue
     *
     * @param bool $useCache TODO: Not currently implemented. Adding to preserved desired cache usage in future
     */
    public function getCurrent(int $contextId, bool $useCache = false): ?Issue
    {
        // TODO: Caching as currently setup never properly caches objects and always fires a _cacheMiss()
        //	    if ($useCache) {
        //	        $cache = $this->dao->_getCache('current');
        //	        return $cache->get($contextId);
//        }

        /** @var JournalDAO $journalDao */
        $journalDao = DAORegistry::getDAO('JournalDAO');

        $journal = $journalDao->getById($contextId);
        $issueId = $journal->getData('currentIssueId');

        return $issueId != null ? $this->get($issueId) : null;
    }

    /**
     * Update the current issue for the journal.
     *
     */
    public function updateCurrent(int $contextId, ?Issue $newCurrentIssue = null)
    {
        /** @var JournalDAO $journalDao */
        $journalDao = DAORegistry::getDAO('JournalDAO');
        $journal = $journalDao->getById($contextId);

        if ($newCurrentIssue) {
            $journal->setData('currentIssueId', $newCurrentIssue->getId());
            $this->edit($newCurrentIssue, []);
            $journalDao->updateObject($journal);
        } else {
            $journalDao->removeCurrentIssue($journal->getId());
        }
    }

    /**
     * Get issue by a submission id
     *
     */
    public function getBySubmissionId(int $submissionId): ?Issue
    {
        $submission = Repo::submission()->get($submissionId);

        $publication = $submission->getCurrentPublication(); /** @var Publication $publication */
        if (is_null($publication)) {
            return null;
        }

        $issueId = $publication->getData('issueId');

        return $issueId != null ? $this->get($issueId) : null;
    }

    /**
     * Retrieve Issue by "best" issue id -- url path if it exists,
     * falling back on the internal issue ID otherwise.
     *
     * @param bool $useCache TODO: Carryover from IssueDAO—was not in use
     */
    public function getByBestId(string $idOrUrlPath, int $contextId = null, bool $useCache = false): ?Issue
    {
        // Get the issue that matches the requested urlPath (if $idOrUrlPath is one)
        $issue = $this->getByUrlPath($idOrUrlPath, $contextId);

        if (!$issue && (is_int($idOrUrlPath) || ctype_digit($idOrUrlPath))) {
            $issue = $this->get((int) $idOrUrlPath);
            $issue = $issue->getJournalId() == $contextId ? $issue : null;
        }

        return $issue ?? null;
    }

    /**
     * Get an issue by its urlPath
     *
     */
    public function getByUrlPath(string $urlPath, int $contextId): ?Issue
    {
        $issueId = $this->dao->getIdByUrlPath($urlPath, $contextId);

        return $issueId
            ? $this->get($issueId)
            : null;
    }

    /**
     * Gets a list of all the years in which issues have been published
     *
     */
    public function getYearsIssuesPublished(int $contextId): Collection
    {
        return $this->dao->getYearsIssuesPublished($contextId);
    }

    /**
     * Deletes all Issues for a given context
     *
     */
    public function deleteByContextId(int $contextId)
    {
        $collector = $this->getCollector()->filterByContextIds([$contextId]);
        Repo::issue()->deleteMany($collector);
    }

    /**
     * Creates a DOI for the given issue
     */
    public function createDoi(Issue $issue)
    {
        /** @var JournalDAO $contextDao */
        $contextDao = \DAORegistry::getDAO('JournalDAO');
        $context = $contextDao->getById($issue->getData('journalId'));

        if ($context->isDoiTypeEnabled(Repo::doi()::TYPE_ISSUE) && empty($issue->getData('doiId'))) {
            $doiId = Repo::doi()->mintIssueDoi($issue, $context);
            if ($doiId !== null) {
                $issue->setData('doiId', $doiId);
                $this->dao->update($issue);
            }
        }
    }
}
