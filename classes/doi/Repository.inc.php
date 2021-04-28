<?php
/**
 * @file classes/doi/Repository.inc.php
 *
 * Copyright (c) 2014-2020 Simon Fraser University
 * Copyright (c) 2000-2020 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class doi
 *
 * @brief A repository to find and manage DOIs.
 */

namespace APP\doi;

use APP\article\ArticleGalley;
use APP\core\Application;
use APP\core\Request;
use APP\core\Services;
use APP\facades\Repo;
use APP\issue\Issue;
use APP\journal\JournalDAO;
use APP\plugins\PubIdPlugin;
use APP\publication\Publication;
use APP\submission\Submission;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\LazyCollection;
use PKP\context\Context;
use PKP\context\ContextDAO;
use PKP\core\DataObject;
use PKP\doi\Doi;
use PKP\services\PKPSchemaService;
use PKP\submission\Representation;

class Repository extends \PKP\doi\Repository
{
    public const TYPE_PUBLICATION = 'publication';
    public const TYPE_ISSUE = 'issue';
    public const TYPE_REPRESENTATION = 'representation';

    public const LEGACY_CUSTOM_ISSUE_PATTERN = 'doiIssueSuffixPattern';

    public function __construct(DAO $dao, Request $request, PKPSchemaService $schemaService)
    {
        parent::__construct($dao, $request, $schemaService);
    }


    /**
     * Create a DOI for the given publication.
     *
     *
     */
    public function mintPublicationDoi(Publication $publication, Submission $submission, Context $context): ?int
    {
        if ($context->getData(Context::SETTING_USE_DEFAULT_DOI_SUFFIX)) {
            $doiSuffix = $this->generateDefaultSuffix($context->getId());
        } else {
            assert(!is_null($submission));

            $issue = Repo::issue()->get($publication->getData('issueId'));

            if ($issue === null) {
                return null;
            } elseif ($issue && $context->getId() != $issue->getJournalId()) {
                return null;
            }

            $doiSuffix = $this->generateSuffixPattern($publication, $context, $context->getData(Context::SETTING_CUSTOM_DOI_SUFFIX_TYPE), $issue, $submission);
        }

        return $this->mintAndStoreDoi($context, $doiSuffix);
    }

    /**
     * Create a DOI for the given galley
     *
     *
     */
    public function mintGalleyDoi(ArticleGalley $galley, Publication $publication, Submission $submission, Context $context): ?int
    {
        if ($context->getData(Context::SETTING_USE_DEFAULT_DOI_SUFFIX)) {
            $doiSuffix = $this->generateDefaultSuffix($context->getId());
        } else {
            assert(!is_null($submission));
            $issue = Repo::issue()->getBySubmissionId($submission->getId());

            if ($issue === null) {
                return null;
            } elseif ($issue && $context->getId() != $issue->getJournalId()) {
                return null;
            }

            $doiSuffix = $this->generateSuffixPattern($publication, $context, $context->getData(Context::SETTING_CUSTOM_DOI_SUFFIX_TYPE), $issue, $submission, $galley);
        }

        return $this->mintAndStoreDoi($context, $doiSuffix);
    }

    /**
     * Create a DOI for the given Issue
     *
     *
     */
    public function mintIssueDoi(Issue $issue): ?int
    {
        /** @var JournalDAO $contextDao */
        $contextDao = \DAORegistry::getDAO('JournalDAO');
        $context = $contextDao->getById($issue->getData('journalId'));

        if ($context->getData(Context::SETTING_USE_DEFAULT_DOI_SUFFIX)) {
            $doiSuffix = $this->generateDefaultSuffix($context->getId());
        } else {
            if ($context->getId() != $issue->getJournalId()) {
                return null;
            }

            $doiSuffix = $this->generateSuffixPattern($issue, $context, $context->getData(Context::SETTING_CUSTOM_DOI_SUFFIX_TYPE), $issue);
        }

        return $this->mintAndStoreDoi($context, $doiSuffix);
    }

    /**
     * Handles updating issue DOI status when metadata changes
     *
     */
    public function issueUpdated(Issue $issue)
    {
        $doiIds = Repo::doi()->getDoisForIssue($issue->getId());
        $this->dao->setDoisToStale($doiIds);
    }

    /**
     * Generate a suffix using a provided pattern type
     *
     * @param string $patternType Repo::doi()::CUSTOM_SUFFIX_* constants
     *
     */
    protected function generateSuffixPattern(
        DataObject $object,
        Context $context,
        string $patternType,
        ?Issue $issue = null,
        ?Submission $submission = null,
        ?Representation $representation = null
    ): string {
        $doiSuffix = '';
        switch ($patternType) {
            case self::SUFFIX_ISSUE:
                $doiSuffix = PubIdPlugin::generateDefaultPattern($context, $issue, $submission, $representation);
                break;
            case self::SUFFIX_CUSTOM_PATTERN:
                $pubIdSuffixPattern = $this->getPubIdSuffixPattern($object, $context);
                $doiSuffix = PubIdPlugin::generateCustomPattern($context, $pubIdSuffixPattern, $object, $issue, $submission, $representation);
                break;
            case self::CUSTOM_SUFFIX_MANUAL:
                break;
        }

        return $doiSuffix;
    }

    /**
     * Compose final DOI and save to database
     *
     *
     */
    protected function mintAndStoreDoi(Context $context, string $doiSuffix): ?int
    {
        $doiPrefix = $context->getData(Context::SETTING_DOI_PREFIX);
        if (empty($doiPrefix)) {
            return null;
        }

        $completedDoi = $doiPrefix . '/' . $doiSuffix;

        $doiDataParams = [
            'doi' => $completedDoi,
            'contextId' => $context->getId()
        ];

        $doi = $this->newDataObject($doiDataParams);
        return $this->add($doi);
    }

    /**
     * Gets all relevant DOI IDs related to a submission (article, galley)
     * NB: Assumes current publication only and only enabled DOI types
     *
     *
     * @throws \Exception
     *
     * @return array DOI IDs
     */
    public function getDoisForSubmission(int $submissionId): array
    {
        $doiIds = [];

        $submission = Repo::submission()->get($submissionId);
        /** @var Publication[] $publications */
        $publications = [$submission->getCurrentPublication()];


        /** @var JournalDAO $contextDao */
        $contextDao = \DAORegistry::getDAO('JournalDAO');
        if (!$submission) {
            $submission = Repo::submission()->get($submissionId);
        }
        $context = $contextDao->getById($submission->getData('contextId'));

        foreach ($publications as $publication) {
            $publicationDoiId = $publication->getData('doiId');
            if (!empty($publicationDoiId)) {
                if ($context->isDoiTypeEnabled(self::TYPE_PUBLICATION)) {
                    $doiIds[] = $publicationDoiId;
                }
            }

            // Galleys
            /** @var ArticleGalley[] $galleys */
            $galleys = Services::get('galley')->getMany(['publicationIds' => $publication->getId()]);
            foreach ($galleys as $galley) {
                $galleyDoiId = $galley->getData('doiId');
                if (!empty($galleyDoiId)) {
                    if ($context->isDoiTypeEnabled(self::TYPE_REPRESENTATION)) {
                        $doiIds[] = $galleyDoiId;
                    }
                }
            }
        }

        return $doiIds;
    }

    public function getDoisForIssue(int $issueId, $enabledDoiTypesOnly = false): array
    {
        $doiIds = [];

        $issue = Repo::issue()->get($issueId);
        $issueDoiId = $issue->getData('doiId');

        /** @var JournalDAO $contextDao */
        $contextDao = \DAORegistry::getDAO('JournalDAO');
        $context = $contextDao->getById($issue->getData('journalId'));

        if (!empty($issueDoiId)) {
            if ($enabledDoiTypesOnly == false || ($enabledDoiTypesOnly && $context->isDoiTypeEnabled(self::TYPE_ISSUE))) {
                $doiIds[] = $issueDoiId;
            }
        }
        return $doiIds;
    }

    /**
     * Gets all published issues with DOIs that need to be registered
     *
     *
     */
    public function getUnregisteredIssues(Context $context): LazyCollection
    {
        // Get unregistered articles
        $collector = Repo::issue()->getCollector();
        $collector
            ->filterByContextIds([$context->getId()])
            ->filterByPublished(true)
            ->filterByDoiStatus(
                [
                    Doi::STATUS_UNREGISTERED,
                    Doi::STATUS_ERROR,
                    Doi::STATUS_STALE
                ],
                true
            );

        return Repo::issue()->getMany($collector);
    }

    /**
     * Get all published galley DOIs that need to be registered;
     * TODO: #doi revisit with Galley EntityDAO
     */
    public function getUnregisteredGalleyIds(int $contextId): Collection
    {
        return $this->dao->getUnregisteredGalleyIds($contextId);
    }


    public function scheduleDepositAll(Context $context)
    {
        parent::scheduleDepositAll($context);
        if (in_array(Repo::doi()::TYPE_ISSUE, $context->getData(Context::SETTING_ENABLED_DOI_TYPES))) {
            $issuesCollection = $this->dao->getAllDepositableIssueIds($context);
            $issueData = $issuesCollection->reduce(function ($carry, $item) {
                $carry['issueIds'][] = $item->issue_id;
                $carry['doiIds'][] = $item->doi_id;
                return $carry;
            }, ['issueIds' => [], 'doiIds' => []]);

            // Schedule/queue jobs for issues
            $contextId = $context->getId();
            $agency = $this->_getAgencyFromContext($context);

            foreach ($issueData['issueIds'] as $issueId) {
                Queue::push(function () use ($issueId, $contextId, $agency) {
                    $issue = Repo::issue()->get($issueId);

                    /** @var ContextDAO $contextDao */
                    $contextDao = Application::getContextDAO();
                    $context = $contextDao->getById($contextId);

                    if (!$issue || !$agency) {
                        // TODO: #doi Something went wrong if there's no issue or agency. Bail out or mark failed?
                    }
                    $retResults = $agency->depositIssues([$issue], $context);
                });
            }

            // Mark issue DOIs as submitted
            Repo::doi()->setDoisToSubmitted($issueData['doiIds']);
        }
    }

    /**
     *  Gets legacy, user-generated suffix pattern associated with object type and context
     *
     *
     * @return mixed|null
     */
    private function getPubIdSuffixPattern(DataObject $object, Context $context)
    {
        if ($object instanceof Issue) {
            return $context->getData(Repo::doi()::LEGACY_CUSTOM_ISSUE_PATTERN);
        } elseif ($object instanceof Representation) {
            return $context->getData(Repo::doi()::LEGACY_CUSTOM_REPRESENTATION_PATTERN);
        } else {
            return $context->getData(Repo::doi()::LEGACY_CUSTOM_PUBLICATION_PATTERN);
        }
    }
}
