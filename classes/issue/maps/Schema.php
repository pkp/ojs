<?php
/**
 * @file classes/issue/maps/Schema.php
 *
 * Copyright (c) 2014-2023 Simon Fraser University
 * Copyright (c) 2003-2023 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class Schema
 *
 * @brief Map sections to the properties defined in the issue schema
 */

namespace APP\issue\maps;

use APP\core\Application;
use APP\facades\Repo;
use APP\issue\Issue;
use APP\issue\IssueGalleyDAO;
use APP\journal\Journal;
use Illuminate\Support\Enumerable;
use Illuminate\Support\LazyCollection;
use PKP\db\DAORegistry;
use PKP\services\PKPSchemaService;
use PKP\submission\Genre;
use PKP\userGroup\UserGroup;

class Schema extends \PKP\core\maps\Schema
{
    /** @copydoc \PKP\core\maps\Schema::$collection */
    public Enumerable $collection;

    /** @copydoc \PKP\core\maps\Schema::$schema */
    public string $schema = PKPSchemaService::SCHEMA_ISSUE;

    /** @var LazyCollection<int,UserGroup> The user groups for this context. */
    public LazyCollection $userGroups;

    /** @var Genre[] The genres for this context. */
    public array $genres;

    /**
     * Map an Issue
     *
     * Includes all properties in the Issue schema
     *
     * @param LazyCollection<int,UserGroup> $userGroups The user groups of this content
     * @param Genre[] $genres The genres of this context
     */
    public function map(Issue $item, Journal $context, LazyCollection $userGroups, array $genres): array
    {
        $this->userGroups = $userGroups;
        $this->genres = $genres;
        return $this->mapByProperties($this->getProps(), $item);
    }

    /**
     * Summarize an Issue
     *
     * Includes properties with the apiSummary flag in the Issue schema.
     *
     */
    public function summarize(Issue $item, Journal $context): array
    {
        $this->context = $context;
        return $this->mapByProperties($this->getSummaryProps(), $item);
    }

    /**
     * Map a collection of Issues
     *
     * @see self::map
     *
     * @param LazyCollection<int,UserGroup> $userGroups The user groups of this content
     * @param Genre[] $genres The genres of this context
     */
    public function mapMany(Enumerable $collection, Journal $context, LazyCollection $userGroups, array $genres): Enumerable
    {
        $this->collection = $collection;
        return $collection->map(function ($item) use ($context, $userGroups, $genres) {
            return $this->map($item, $context, $userGroups, $genres);
        });
    }

    /**
     * Summarize a collection of Issues
     *
     * @see self::summarize
     */
    public function summarizeMany(Enumerable $collection, Journal $context): Enumerable
    {
        $this->collection = $collection;
        return $collection->map(function ($item) use ($context) {
            return $this->summarize($item, $context);
        });
    }

    /**
     * Map schema properties of an Issue to an assoc array
     *
     */
    private function mapByProperties(array $props, Issue $issue): array
    {
        $output = [];

        foreach ($props as $prop) {
            switch ($prop) {
                case '_href':
                    $output[$prop] = $this->getApiUrl('issues/' . $issue->getId(), $this->context->getData('urlPath'));
                    break;
                case 'articles':
                    $data = [];

                    $submissions = Repo::submission()
                        ->getCollector()
                        ->filterByContextIds([$issue->getJournalId()])
                        ->filterByIssueIds([$issue->getId()])
                        ->getMany();

                    foreach ($submissions as $submission) {
                        $data[] = Repo::submission()->getSchemaMap()->summarize($submission, $this->userGroups, $this->genres);
                    }

                    $output[$prop] = $data;
                    break;
                case 'coverImageUrl':
                    $output[$prop] = $issue->getCoverImageUrls();
                    break;
                case 'doiObject':
                    if ($issue->getData('doiObject')) {
                        $retVal = Repo::doi()->getSchemaMap()->summarize($issue->getData('doiObject'));
                    } else {
                        $retVal = null;
                    }

                    $output[$prop] = $retVal;
                    break;
                case 'galleys':
                    $data = [];
                    /** @var IssueGalleyDAO $issueGalleyDao */
                    $issueGalleyDao = DAORegistry::getDAO('IssueGalleyDAO');
                    $galleys = $issueGalleyDao->getByIssueId($issue->getId());
                    if (!empty($galleys)) {
                        $request = Application::get()->getRequest();
                        foreach ($galleys as $galley) {
                            $data[] = [
                                'fileId' => $galley->getData('fileId'),
                                'label' => $galley->getData('label'),
                                'locale' => $galley->getData('locale'),
                                'pub-id::publisher-id' => $galley->getData('pub-id::publisher-id'),
                                'sequence' => $galley->getData('sequence'),
                                'urlPublished' => $request->getDispatcher()->url(
                                    $request,
                                    Application::ROUTE_PAGE,
                                    $this->context->getPath(),
                                    'issue',
                                    'view',
                                    [
                                        $galley->getIssueId(),
                                        $galley->getId()
                                    ]
                                ),
                                'urlRemote' => $galley->getData('urlRemote'),
                            ];
                        }
                    }
                    $output[$prop] = $data;
                    break;
                case 'publishedUrl':
                    $output['publishedUrl'] = $this->request->getDispatcher()->url(
                        $this->request,
                        Application::ROUTE_PAGE,
                        $this->context->getPath(),
                        'issue',
                        'view',
                        $issue->getId()
                    );
                    break;
                case 'sections':
                    $data = [];
                    $sections = Repo::section()->getByIssueId($issue->getId());
                    if (!empty($sections)) {
                        $seq = 1;
                        foreach ($sections as $section) {
                            $sectionProperties = Repo::section()->getSchemaMap()->summarize($section);
                            $sectionProperties['seq'] = $seq;
                            $seq++;
                            $data[] = $sectionProperties;
                        }
                    }
                    $output[$prop] = $data;
                    break;
                case 'identification':
                    $output[$prop] = $issue->getIssueIdentification();
                    break;

                default:
                    $output[$prop] = $issue->getData($prop);
            }
        }

        return $output;
    }

    /**
     * Map an issue with only the issue identification for the stats list
     */
    public function mapToStats(Issue $issue): array
    {
        $props = $this->mapByProperties([
            '_href',
            'id',
            'identification',
            'publishedUrl'
        ], $issue);
        return $props;
    }
}
