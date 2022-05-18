<?php
/**
 * @file classes/doi/Repository.inc.php
 *
 * Copyright (c) 2014-2022 Simon Fraser University
 * Copyright (c) 2000-2022 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class doi
 *
 * @brief A repository to find and manage DOIs.
 */

namespace APP\doi;

use APP\core\Request;
use APP\facades\Repo;
use APP\issue\Issue;
use APP\Jobs\Doi\DepositIssue;
use APP\journal\Journal;
use APP\journal\JournalDAO;
use APP\plugins\PubIdPlugin;
use APP\publication\Publication;
use APP\submission\Submission;
use PKP\context\Context;
use PKP\core\DataObject;
use PKP\db\DAORegistry;
use PKP\galley\Galley;
use PKP\services\PKPSchemaService;
use PKP\submission\Representation;

class Repository extends \PKP\doi\Repository
{
    public const TYPE_ISSUE = 'issue';

    public const CUSTOM_ISSUE_PATTERN = 'doiIssueSuffixPattern';

    public function __construct(DAO $dao, Request $request, PKPSchemaService $schemaService)
    {
        parent::__construct($dao, $request, $schemaService);
    }

    /**
     * Create a DOI for the given publication.
     */
    public function mintPublicationDoi(Publication $publication, Submission $submission, Context $context): ?int
    {
        assert(!is_null($submission));

        // Default suffix does not rely on any other metadata
        if ($context->getData(Context::SETTING_DOI_SUFFIX_TYPE) === Repo::doi()::SUFFIX_DEFAULT) {
            return $this->mintAndStoreDoi($context, $this->generateDefaultSuffix());
        }

        // If not using default suffix, additional checks are required
        $issueId = $publication->getData('issueId');
        if ($issueId === null) {
            return null;
        }

        $issue = Repo::issue()->get($publication->getData('issueId'));
        if ($issue === null) {
            return null;
        } elseif ($issue && $context->getId() != $issue->getJournalId()) {
            return null;
        }

        $doiSuffix = $this->generateSuffixPattern($publication, $context, $context->getData(Context::SETTING_DOI_SUFFIX_TYPE), $issue, $submission);

        return $this->mintAndStoreDoi($context, $doiSuffix);
    }

    /**
     * Create a DOI for the given galley
     */
    public function mintGalleyDoi(Galley $galley, Publication $publication, Submission $submission, Context $context): ?int
    {
        assert(!is_null($submission));

        // Default suffix does not rely on any other metadata
        if ($context->getData(Context::SETTING_DOI_SUFFIX_TYPE) === Repo::doi()::SUFFIX_DEFAULT) {
            return $this->mintAndStoreDoi($context, $this->generateDefaultSuffix());
        }

        // If not using default suffix, additional checks are required
        $issue = Repo::issue()->getBySubmissionId($submission->getId());

        if ($issue === null) {
            return null;
        } elseif ($issue && $context->getId() != $issue->getJournalId()) {
            return null;
        }

        $doiSuffix = $this->generateSuffixPattern($galley, $context, $context->getData(Context::SETTING_DOI_SUFFIX_TYPE), $issue, $submission, $galley);

        return $this->mintAndStoreDoi($context, $doiSuffix);
    }

    /**
     * Create a DOI for the given Issue
     */
    public function mintIssueDoi(Issue $issue, Context $context): ?int
    {
        if ($context->getId() != $issue->getJournalId()) {
            return null;
        }

        // Default suffix does not rely on any other metadata
        if ($context->getData(Context::SETTING_DOI_SUFFIX_TYPE) === Repo::doi()::SUFFIX_DEFAULT) {
            return $this->mintAndStoreDoi($context, $this->generateDefaultSuffix());
        }

        // If not using default suffix, use pattern generator
        $doiSuffix = $this->generateSuffixPattern($issue, $context, $context->getData(Context::SETTING_DOI_SUFFIX_TYPE), $issue);

        return $this->mintAndStoreDoi($context, $doiSuffix);
    }

    /**
     * Handles updating issue DOI status when metadata changes
     *
     */
    public function issueUpdated(Issue $issue)
    {
        $doiIds = Repo::doi()->getDoisForIssue($issue->getId());
        $this->dao->markStale($doiIds);
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
            case self::SUFFIX_CUSTOM_PATTERN:
                $pubIdSuffixPattern = $this->getPubIdSuffixPattern($object, $context);
                $doiSuffix = PubIdPlugin::generateCustomPattern($context, $pubIdSuffixPattern, $object, $issue, $submission, $representation);
                break;
            case self::SUFFIX_MANUAL:
                break;
        }

        return $doiSuffix;
    }

    /**
     * Gets all relevant DOI IDs related to a submission (article, galley)
     * NB: Assumes current publication only and only enabled DOI types
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
        $contextDao = DAORegistry::getDAO('JournalDAO');
        /** @var Journal $context */
        $context = $contextDao->getById($submission->getData('contextId'));

        foreach ($publications as $publication) {
            $publicationDoiId = $publication->getData('doiId');
            if (!empty($publicationDoiId) && $context->isDoiTypeEnabled(self::TYPE_PUBLICATION)) {
                $doiIds[] = $publicationDoiId;
            }

            // Galleys
            $galleys = Repo::galley()->getMany(
                Repo::galley()
                    ->getCollector()
                    ->filterByPublicationIds(['publicationIds' => $publication->getId()])
            );

            foreach ($galleys as $galley) {
                $galleyDoiId = $galley->getData('doiId');
                if (!empty($galleyDoiId) && $context->isDoiTypeEnabled(self::TYPE_REPRESENTATION)) {
                    $doiIds[] = $galleyDoiId;
                }
            }
        }

        return $doiIds;
    }

    /**
     * Gets all DOIs associated with an issue
     * NB: Assumes only enabled DOI types are allowed
     *
     * @param $enabledDoiTypesOnly
     *
     * @throws \Exception
     */
    public function getDoisForIssue(int $issueId, $enabledDoiTypesOnly = false): array
    {
        $doiIds = [];

        $issue = Repo::issue()->get($issueId);
        $issueDoiId = $issue->getData('doiId');

        /** @var JournalDAO $contextDao */
        $contextDao = DAORegistry::getDAO('JournalDAO');
        /** @var Journal $context */
        $context = $contextDao->getById($issue->getData('journalId'));

        if (!empty($issueDoiId)) {
            if ($enabledDoiTypesOnly == false || ($enabledDoiTypesOnly && $context->isDoiTypeEnabled(self::TYPE_ISSUE))) {
                $doiIds[] = $issueDoiId;
            }
        }
        return $doiIds;
    }

    /**
     * Schedules DOI deposits with the active registration agency for all valid and
     * unregistered/stale publication items. Items are added as a queued job to be
     * completed asynchronously.
     *
     *
     */
    public function depositAll(Context $context)
    {
        parent::depositAll($context);
        if (in_array(Repo::doi()::TYPE_ISSUE, $context->getData(Context::SETTING_ENABLED_DOI_TYPES) ?? [])) {
            // If there is no configured registration agency, nothing can be deposited.
            $agency = $context->getConfiguredDoiAgency();
            if (!$agency) {
                return;
            }

            $issuesCollection = $this->dao->getAllDepositableIssueIds($context);
            $issueData = $issuesCollection->reduce(function ($carry, $item) {
                $carry['issueIds'][] = $item->issue_id;
                $carry['doiIds'][] = $item->doi_id;
                return $carry;
            }, ['issueIds' => [], 'doiIds' => []]);

            // Schedule/queue jobs for issues
            foreach ($issueData['issueIds'] as $issueId) {
                dispatch(new DepositIssue($issueId, $context, $agency));
            }

            // Mark issue DOIs as submitted
            Repo::doi()->markSubmitted($issueData['doiIds']);
        }
    }

    /**
     * Get app-specific DOI type constants to check when scheduling deposit for submissions
     */
    protected function getValidSubmissionDoiTypes(): array
    {
        return [
            self::TYPE_PUBLICATION,
            self::TYPE_REPRESENTATION,
        ];
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
            return $context->getData(Repo::doi()::CUSTOM_ISSUE_PATTERN);
        } elseif ($object instanceof Representation) {
            return $context->getData(Repo::doi()::CUSTOM_REPRESENTATION_PATTERN);
        } else {
            return $context->getData(Repo::doi()::CUSTOM_PUBLICATION_PATTERN);
        }
    }
}
