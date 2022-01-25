<?php

namespace APP\issue\maps;

use APP\core\Application;
use APP\core\Services;
use APP\facades\Repo;
use APP\issue\Issue;
use APP\issue\IssueGalleyDAO;
use APP\journal\SectionDAO;
use Illuminate\Support\Enumerable;
use PKP\db\DAORegistry;
use PKP\security\UserGroup;
use PKP\services\PKPSchemaService;
use PKP\submission\Genre;

class Schema extends \PKP\core\maps\Schema
{
    /** @copydoc \PKP\core\maps\Schema::$collection */
    public Enumerable $collection;

    /** @copydoc \PKP\core\maps\Schema::$schema */
    public string $schema = PKPSchemaService::SCHEMA_ISSUE;

    /** @var UserGroup[] The user groups for this context. */
    public array $userGroups;

    /** @var Genre[] The genres for this context. */
    public array $genres;

    /**
     * Map an Issue
     *
     * Includes all properties in the Issue schema
     *
     * @param UserGroup[] $userGroups The user groups of this content
     * @param Genre[] $genres The genres of this context
     */
    public function map(Issue $item, array $userGroups, array $genres): array
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
    public function summarize(Issue $item): array
    {
        return $this->mapByProperties($this->getSummaryProps(), $item);
    }

    /**
     * Map a collection of Issues
     *
     * @see self::map
     *
     * @param UserGroup[] $userGroups The user groups of this content
     * @param Genre[] $genres The genres of this context
     */
    public function mapMany(Enumerable $collection, array $userGroups, array $genres): Enumerable
    {
        $this->collection = $collection;
        return $collection->map(function ($item, $userGroups, $genres) {
            return $this->map($item, $userGroups, $genres);
        });
    }

    /**
     * Summarize a collection of Issues
     *
     * @see self::summarize
     */
    public function summarizeMany(Enumerable $collection): Enumerable
    {
        $this->collection = $collection;
        return $collection->map(function ($item) {
            return $this->summarize($item);
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

                    $submissions = Repo::submission()->getMany(
                        Repo::submission()
                            ->getCollector()
                            ->filterByContextIds([$issue->getJournalId()])
                            ->filterByIssueIds([$issue->getId()])
                    );

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
                        $galleyArgs = ['issue' => $issue, 'request' => $request];
                        foreach ($galleys as $galley) {
                            $data[] = Services::get('galley')->getSummaryProperties($galley, $galleyArgs);
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
                    /** @var SectionDAO $sectionDao */
                    $sectionDao = DAORegistry::getDAO('SectionDAO');
                    $sections = $sectionDao->getByIssueId($issue->getId());
                    $request = Application::get()->getRequest();
                    if (!empty($sections)) {
                        foreach ($sections as $section) {
                            $sectionProperties = Services::get('section')->getSummaryProperties($section, [
                                'request' => $request
                            ]);
                            $customSequence = $sectionDao->getCustomSectionOrder($issue->getId(), $section->getId());
                            if ($customSequence) {
                                $sectionProperties['seq'] = $customSequence;
                            }
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
}
