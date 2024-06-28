<?php

/**
 * @file plugins/generic/datacite/filter/DataciteXmlFilter.php
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2000-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class DataciteXmlFilter
 *
 * @brief Class that converts an Issue to a DataCite XML document.
 */

namespace APP\plugins\generic\datacite\filter;

use APP\author\Author;
use APP\core\Application;
use APP\core\Services;
use APP\decision\Decision;
use APP\facades\Repo;
use APP\issue\Issue;
use APP\issue\IssueGalleyDAO;
use APP\plugins\generic\datacite\DataciteExportDeployment;
use APP\plugins\generic\datacite\DataciteExportPlugin;
use APP\publication\Publication;
use APP\submission\Submission;
use PKP\core\PKPString;
use PKP\db\DAORegistry;
use PKP\facades\Locale;
use PKP\galley\Galley;
use PKP\i18n\LocaleConversion;
use PKP\submission\Genre;
use PKP\submission\GenreDAO;
use PKP\submissionFile\SubmissionFile;

// Title types
define('DATACITE_TITLETYPE_TRANSLATED', 'TranslatedTitle');
define('DATACITE_TITLETYPE_ALTERNATIVE', 'AlternativeTitle');

// Date types
define('DATACITE_DATE_AVAILABLE', 'Available');
define('DATACITE_DATE_ISSUED', 'Issued');
define('DATACITE_DATE_SUBMITTED', 'Submitted');
define('DATACITE_DATE_ACCEPTED', 'Accepted');
define('DATACITE_DATE_CREATED', 'Created');
define('DATACITE_DATE_UPDATED', 'Updated');

// Identifier types
define('DATACITE_IDTYPE_PROPRIETARY', 'publisherId');
define('DATACITE_IDTYPE_EISSN', 'EISSN');
define('DATACITE_IDTYPE_ISSN', 'ISSN');
define('DATACITE_IDTYPE_DOI', 'DOI');
define('DATACITE_IDTYPE_URL', 'URL');

// Relation types
define('DATACITE_RELTYPE_ISVARIANTFORMOF', 'IsVariantFormOf');
define('DATACITE_RELTYPE_HASPART', 'HasPart');
define('DATACITE_RELTYPE_ISPARTOF', 'IsPartOf');
define('DATACITE_RELTYPE_ISPREVIOUSVERSIONOF', 'IsPreviousVersionOf');
define('DATACITE_RELTYPE_ISNEWVERSIONOF', 'IsNewVersionOf');
define('DATACITE_RELTYPE_ISPUBLISHEDIN', 'IsPublishedIn');

// Description types
define('DATACITE_DESCTYPE_ABSTRACT', 'Abstract');
define('DATACITE_DESCTYPE_SERIESINFO', 'SeriesInformation');
define('DATACITE_DESCTYPE_TOC', 'TableOfContents');
define('DATACITE_DESCTYPE_OTHER', 'Other');

class DataciteXmlFilter extends \PKP\plugins\importexport\native\filter\NativeExportFilter
{
    /**
     * Constructor
     *
     * @param \PKP\filter\FilterGroup $filterGroup
     */
    public function __construct($filterGroup)
    {
        $this->setDisplayName('DataCite XML export');
        parent::__construct($filterGroup);
    }

    //
    // Implement template methods from Filter
    //
    /**
     * @see Filter::process()
     *
     * @param Issue|Submission|Galley $pubObject
     *
     * @return \DOMDocument
     */
    public function &process(&$pubObject)
    {
        // Create the XML document
        $doc = new \DOMDocument('1.0', 'utf-8');
        $doc->preserveWhiteSpace = false;
        $doc->formatOutput = true;
        /** @var DataciteExportDeployment */
        $deployment = $this->getDeployment();
        $context = $deployment->getContext();
        /** @var DataciteExportPlugin */
        $plugin = $deployment->getPlugin();
        $cache = $plugin->getCache();

        // Get all objects
        $issue = $article = $galley = $galleyFile = null;
        if ($pubObject instanceof Issue) {
            $issue = $pubObject;
            if (!$cache->isCached('issues', $issue->getId())) {
                $cache->add($issue, null);
            }
        } elseif ($pubObject instanceof Submission) {
            $article = $pubObject;
            if (!$cache->isCached('articles', $article->getId())) {
                $cache->add($article, null);
            }
        } elseif ($pubObject instanceof Galley) {
            $galley = $pubObject;
            $galleyFile = $galley->getFile();
            $publication = Repo::publication()->get($galley->getData('publicationId'));
            if ($cache->isCached('articles', $publication->getData('submissionId'))) {
                $article = $cache->get('articles', $publication->getData('submissionId'));
            } else {
                $article = Repo::submission()->get($publication->getData('submissionId'));
                if ($article) {
                    $cache->add($article, null);
                }
            }
            if (isset($galleyFile)) {
                if ($cache->isCached('genres', $galleyFile->getData('genreId'))) {
                    $genre = $cache->get('genres', $galleyFile->getData('genreId'));
                } else {
                    /** @var GenreDAO */
                    $genreDao = DAORegistry::getDAO('GenreDAO');
                    $genre = $genreDao->getById($galleyFile->getData('genreId'));
                    if ($genre) {
                        $cache->add($genre, null);
                    }
                }
            }
        }
        if (!$issue) {
            $issueId = $article->getCurrentPublication()->getData('issueId');
            if ($cache->isCached('issues', $issueId)) {
                $issue = $cache->get('issues', $issueId);
            } else {
                $issue = Repo::issue()->get($issueId);
                $issue = $issue->getJournalId() == $context->getId() ? $issue : null;
                if ($issue) {
                    $cache->add($issue, null);
                }
            }
        }

        // Get the most recently published version
        $publication = $article ? $article->getCurrentPublication() : null;

        // Identify the object locale.
        $objectLocalePrecedence = $this->getObjectLocalePrecedence($context, $article, $publication, $galley);
        // The publisher is required.
        // Use the journal title as DataCite recommends for now.
        $publisher = $this->getPrimaryTranslation($context->getData('name'), $objectLocalePrecedence);
        assert(!empty($publisher));
        // The publication date is required.
        if ($publication) {
            $publicationDate = $publication->getData('datePublished');
        }
        if (empty($publicationDate)) {
            $publicationDate = $issue->getDatePublished();
        }
        assert(!empty($publicationDate));

        // Create the root node
        $rootNode = $this->createRootNode($doc);
        $doc->appendChild($rootNode);
        // DOI (mandatory)
        $doi = $pubObject->getStoredPubId('doi');
        if ($plugin->isTestMode($context)) {
            $testDOIPrefix = $plugin->getSetting($context->getId(), 'testDOIPrefix');
            assert(!empty($testDOIPrefix));
            $doi = PKPString::regexp_replace('#^[^/]+/#', $testDOIPrefix . '/', $doi);
        }
        $rootNode->appendChild($node = $doc->createElementNS($deployment->getNamespace(), 'identifier', htmlspecialchars($doi, ENT_COMPAT, 'UTF-8')));
        $node->setAttribute('identifierType', DATACITE_IDTYPE_DOI);
        // Creators (mandatory)
        $rootNode->appendChild($this->createCreatorsNode($doc, $issue, $publication, $galley, $galleyFile, $publisher, $objectLocalePrecedence));
        // Title (mandatory)
        $rootNode->appendChild($this->createTitlesNode($doc, $issue, $publication, $galley, $galleyFile, $objectLocalePrecedence));
        // Publisher (mandatory)
        $rootNode->appendChild($node = $doc->createElementNS($deployment->getNamespace(), 'publisher', htmlspecialchars($publisher, ENT_COMPAT, 'UTF-8')));
        // Publication Year (mandatory)
        $rootNode->appendChild($node = $doc->createElementNS($deployment->getNamespace(), 'publicationYear', date('Y', strtotime($publicationDate))));
        // Subjects
        $subjects = [];
        if (!empty($galleyFile) && !empty($genre) && $genre->getSupplementary()) {
            $subjects = (array) $this->getPrimaryTranslation($galleyFile->getData('subject'), $objectLocalePrecedence);
        } elseif (!empty($article) && !empty($publication)) {
            $subjects = array_merge(
                (array) $this->getPrimaryTranslation($publication->getData('keywords'), $objectLocalePrecedence),
                (array) $this->getPrimaryTranslation($publication->getData('subjects'), $objectLocalePrecedence)
            );
        }
        if (!empty($subjects)) {
            $subjectsNode = $doc->createElementNS($deployment->getNamespace(), 'subjects');
            foreach ($subjects as $subject) {
                $subjectsNode->appendChild($node = $doc->createElementNS($deployment->getNamespace(), 'subject', htmlspecialchars($subject, ENT_COMPAT, 'UTF-8')));
            }
            $rootNode->appendChild($subjectsNode);
        }
        // Dates
        $rootNode->appendChild($this->createDatesNode($doc, $issue, $article, $publication, $galley, $galleyFile, $publicationDate));
        // Language
        $rootNode->appendChild($node = $doc->createElementNS($deployment->getNamespace(), 'language', LocaleConversion::getIso1FromLocale($objectLocalePrecedence[0])));
        // Resource Type
        $resourceTypeNode = $this->createResourceTypeNode($doc, $issue, $article, $galley, $galleyFile);
        if ($resourceTypeNode) {
            $rootNode->appendChild($resourceTypeNode);
        }
        // Alternate Identifiers
        $rootNode->appendChild($this->createAlternateIdentifiersNode($doc, $issue, $article, $galley));
        // Related Identifiers
        $relatedIdentifiersNode = $this->createRelatedIdentifiersNode($doc, $issue, $article, $publication, $galley);
        if ($relatedIdentifiersNode) {
            $rootNode->appendChild($relatedIdentifiersNode);
        }
        // Sizes
        $sizesNode = $this->createSizesNode($doc, $issue, $article, $publication, $galley, $galleyFile);
        if ($sizesNode) {
            $rootNode->appendChild($sizesNode);
        }
        // Formats
        if (!empty($galleyFile)) {
            $format = $galleyFile->getData('mimetype');
            if (!empty($format)) {
                $formatsNode = $doc->createElementNS($deployment->getNamespace(), 'formats');
                $formatsNode->appendChild($node = $doc->createElementNS($deployment->getNamespace(), 'format', htmlspecialchars($format, ENT_COMPAT, 'UTF-8')));
                $rootNode->appendChild($formatsNode);
            }
        }
        // Rights
        $rightsURL = $publication ? $publication->getData('licenseUrl') : $context->getData('licenseUrl');
        if (!empty($rightsURL)) {
            $rightsNode = $doc->createElementNS($deployment->getNamespace(), 'rightsList');
            $rightsNode->appendChild($node = $doc->createElementNS($deployment->getNamespace(), 'rights', htmlspecialchars(strip_tags(Application::get()->getCCLicenseBadge($rightsURL)), ENT_COMPAT, 'UTF-8')));
            $node->setAttribute('rightsURI', $rightsURL);
            $rootNode->appendChild($rightsNode);
        }
        // Descriptions
        $descriptionsNode = $this->createDescriptionsNode($doc, $issue, $article, $publication, $galley, $galleyFile, $objectLocalePrecedence);
        if ($descriptionsNode) {
            $rootNode->appendChild($descriptionsNode);
        }
        // relatedItems
        $relatedItemsNode = $this->createRelatedItemsNode($doc, $issue, $article, $publication, $publisher, $objectLocalePrecedence);
        if ($relatedItemsNode) {
            $rootNode->appendChild($relatedItemsNode);
        }

        return $doc;
    }

    //
    // Conversion functions
    //
    /**
     * Create and return the root node.
     *
     * @param \DOMDocument $doc
     *
     * @return \DOMElement
     */
    public function createRootNode($doc)
    {
        /** @var DataciteExportDeployment */
        $deployment = $this->getDeployment();
        $rootNode = $doc->createElementNS($deployment->getNamespace(), $deployment->getRootElementName());
        $rootNode->setAttributeNS('http://www.w3.org/2000/xmlns/', 'xmlns:xsi', $deployment->getXmlSchemaInstance());
        $rootNode->setAttribute('xsi:schemaLocation', $deployment->getNamespace() . ' ' . $deployment->getSchemaFilename());
        return $rootNode;
    }

    /**
     * Create creators node.
     *
     * @param \DOMDocument $doc
     * @param \APP\issue\Issue $issue
     * @param \APP\publication\Publication $publication
     * @param \PKP\galley\Galley $galley
     * @param \PKP\submissionFile\SubmissionFile $galleyFile
     * @param string $publisher
     * @param array $objectLocalePrecedence
     *
     * @return \DOMElement
     */
    public function createCreatorsNode($doc, $issue, $publication, $galley, $galleyFile, $publisher, $objectLocalePrecedence)
    {
        /** @var DataciteExportDeployment */
        $deployment = $this->getDeployment();
        /** @var DataciteExportPlugin */
        $plugin = $deployment->getPlugin();
        $creators = [];
        switch (true) {
            case (isset($galleyFile) && ($genre = $plugin->getCache()->get('genres', $galleyFile->getData('genreId'))) && $genre->getSupplementary()):
                // Check whether we have a supp file creator set...
                $creator = $this->getPrimaryTranslation($galleyFile->getData('creator'), $objectLocalePrecedence);
                if (!empty($creator)) {
                    $creators[] = [
                        'name' => $creator,
                        'orcid' => null,
                        'affiliation' => null,
                        'ror' => null
                    ];
                    break;
                }
                // ...if not then go on by retrieving the publication
                // authors.
                // no break
            case isset($publication):
                // Retrieve the publication authors.
                $authors = $publication->getData('authors');
                assert(!empty($authors));
                foreach ($authors as $author) { /** @var Author $author */
                    $creators[] = [
                        'name' => $author->getFullName(false, true, $publication->getData('locale')),
                        'orcid' => $author->getOrcid(),
                        'affiliation' => $author->getLocalizedData('affiliation', $publication->getData('locale')),
                        'ror' => $author->getData('rorId') ?? null
                    ];
                }
                break;
            case isset($issue):
                $creators[] = [
                    'name' => $publisher,
                    'orcid' => null,
                    'affiliation' => null,
                    'ror' => null
                ];
                break;
        }
        assert(count($creators) >= 1);
        $creatorsNode = $doc->createElementNS($deployment->getNamespace(), 'creators');
        foreach ($creators as $creator) {
            $creatorNode = $doc->createElementNS($deployment->getNamespace(), 'creator');
            $creatorNode->appendChild($node = $doc->createElementNS($deployment->getNamespace(), 'creatorName', htmlspecialchars($creator['name'], ENT_COMPAT, 'UTF-8')));
            if ($creator['orcid']) {
                $node = $doc->createElementNS($deployment->getNamespace(), 'nameIdentifier');
                $node->appendChild($doc->createTextNode($creator['orcid']));
                $node->setAttribute('schemeURI', 'http://orcid.org/');
                $node->setAttribute('nameIdentifierScheme', 'ORCID');
                $creatorNode->appendChild($node);
            }
            if ($creator['affiliation']) {
                $node = $doc->createElementNS($deployment->getNamespace(), 'affiliation');
                if ($creator['ror']) {
                    $node->setAttribute('affiliationIdentifier', $creator['ror']);
                    $node->setAttribute('affiliationIdentifierScheme', 'ROR');
                    $node->setAttribute('schemeURI', 'https://ror.org');
                }
                $node->appendChild($doc->createTextNode($creator['affiliation']));
                $creatorNode->appendChild($node);
            }
            $creatorsNode->appendChild($creatorNode);
        }
        return $creatorsNode;
    }

    /**
     * Create titles node.
     *
     * @param \DOMDocument $doc
     * @param \APP\issue\Issue $issue
     * @param \APP\publication\Publication $publication
     * @param \PKP\galley\Galley $galley
     * @param \PKP\submissionFile\SubmissionFile $galleyFile
     * @param array $objectLocalePrecedence
     *
     * @return \DOMElement
     */
    public function createTitlesNode($doc, $issue, $publication, $galley, $galleyFile, $objectLocalePrecedence)
    {
        /** @var DataciteExportDeployment */
        $deployment = $this->getDeployment();
        /** @var DataciteExportPlugin */
        $plugin = $deployment->getPlugin();
        // Get an array of localized titles.
        $alternativeTitle = null;
        switch (true) {
            case (isset($galleyFile) && ($genre = $plugin->getCache()->get('genres', $galleyFile->getData('genreId'))) && $genre->getSupplementary()):
                $titles = $galleyFile->getData('name');
                break;
            case isset($publication):
                $titles = $publication->getTitles();
                break;
            case isset($issue):
                $titles = $this->getIssueInformation($issue);
                $alternativeTitle = $this->getPrimaryTranslation($issue->getTitle(null), $objectLocalePrecedence);
                break;
        }
        // Order titles by locale precedence.
        $titles = $this->getTranslationsByPrecedence($titles, $objectLocalePrecedence);
        // We expect at least one title.
        assert(count($titles) >= 1);
        $titlesNode = $doc->createElementNS($deployment->getNamespace(), 'titles');
        // Start with the primary object locale.
        $primaryTitle = array_shift($titles);
        $titlesNode->appendChild($node = $doc->createElementNS($deployment->getNamespace(), 'title', htmlspecialchars($primaryTitle, ENT_COMPAT, 'UTF-8')));
        // Then let the translated titles follow.
        foreach ($titles as $locale => $title) {
            $titlesNode->appendChild($node = $doc->createElementNS($deployment->getNamespace(), 'title', htmlspecialchars($title, ENT_COMPAT, 'UTF-8')));
            $node->setAttribute('titleType', DATACITE_TITLETYPE_TRANSLATED);
        }
        // And finally the alternative title.
        if (!empty($alternativeTitle)) {
            $titlesNode->appendChild($node = $doc->createElementNS($deployment->getNamespace(), 'title', htmlspecialchars($alternativeTitle, ENT_COMPAT, 'UTF-8')));
            $node->setAttribute('titleType', DATACITE_TITLETYPE_ALTERNATIVE);
        }
        return $titlesNode;
    }

    /**
     * Create a date node list.
     *
     * @param \DOMDocument $doc
     * @param Issue $issue
     * @param Submission $article
     * @param Publication $publication
     * @param Galley $galley
     * @param SubmissionFile $galleyFile
     * @param string $publicationDate
     *
     * @return \DOMElement
     */
    public function createDatesNode($doc, $issue, $article, $publication, $galley, $galleyFile, $publicationDate)
    {
        /** @var DataciteExportDeployment */
        $deployment = $this->getDeployment();
        /** @var DataciteExportPlugin */
        $plugin = $deployment->getPlugin();
        $dates = [];
        switch (true) {
            case isset($galleyFile):
                $genre = $plugin->getCache()->get('genres', $galleyFile->getData('genreId'));
                if ($genre->getSupplementary()) {
                    // Created date (for supp files only): supp file date created.
                    $createdDate = $galleyFile->getData('dateCreated');
                    if (!empty($createdDate)) {
                        $dates[DATACITE_DATE_CREATED] = $createdDate;
                    }
                }
                // Accepted date (for galleys files): file uploaded.
                $acceptedDate = $galleyFile->getData('createdAt');
                if (!empty($acceptedDate)) {
                    $dates[DATACITE_DATE_ACCEPTED] = $acceptedDate;
                }
                // Last modified date (for galley files): file modified date.
                $lastModified = $galleyFile->getData('updatedAt');
                if (!empty($lastModified)) {
                    $dates[DATACITE_DATE_UPDATED] = $lastModified;
                }
                break;
            case isset($article):
                // Submitted date (for articles): article date submitted.
                $submittedDate = $article->getData('dateSubmitted');
                if (!empty($submittedDate)) {
                    $dates[DATACITE_DATE_SUBMITTED] = $submittedDate;
                }
                // Accepted date: the last editor accept decision date
                $editDecisions = Repo::decision()->getCollector()
                    ->filterBySubmissionIds([$article->getId()])
                    ->getMany();

                foreach ($editDecisions->reverse() as $editDecision) {
                    if ($editDecision->getData('decision') == Decision::ACCEPT) {
                        $dates[DATACITE_DATE_ACCEPTED] = $editDecision->getData('dateDecided');
                    }
                }
                // Last modified date (for articles): last modified date.
                $lastModified = $publication->getData('lastModified');
                if (!empty($lastModified)) {
                    $dates[DATACITE_DATE_UPDATED] = $lastModified;
                }
                break;
            case isset($issue):
                // Last modified date (for issues): last modified date.
                $lastModified = $issue->getLastModified();
                if (!empty($lastModified)) {
                    $dates[DATACITE_DATE_UPDATED] = $issue->getLastModified();
                }
                break;
        }
        $datesNode = $doc->createElementNS($deployment->getNamespace(), 'dates');
        // Issued date: publication date.
        $dates[DATACITE_DATE_ISSUED] = $publicationDate;
        // Available date: issue open access date.
        $availableDate = $issue->getOpenAccessDate();
        if (!empty($availableDate)) {
            $dates[DATACITE_DATE_AVAILABLE] = $availableDate;
        }
        // Create the date elements for all dates.
        foreach ($dates as $dateType => $date) {
            // Format the date.
            $date = date('Y-m-d', strtotime($date));
            $datesNode->appendChild($node = $doc->createElementNS($deployment->getNamespace(), 'date', $date));
            $node->setAttribute('dateType', $dateType);
        }
        return $datesNode;
    }

    /**
     * Create a resource type node.
     *
     * @param \DOMDocument $doc
     * @param Issue $issue
     * @param Submission $article
     * @param Galley $galley
     * @param SubmissionFile $galleyFile
     *
     * @return \DOMElement
     */
    public function createResourceTypeNode($doc, $issue, $article, $galley, $galleyFile)
    {
        /** @var DataciteExportDeployment */
        $deployment = $this->getDeployment();
        /** @var DataciteExportPlugin */
        $plugin = $deployment->getPlugin();
        $resourceTypeNode = null;
        switch (true) {
            case isset($galley):
                if (!$galley->getRemoteURL()) {
                    $genre = $plugin->getCache()->get('genres', $galleyFile->getData('genreId'));
                    if ($genre->getCategory() == Genre::GENRE_CATEGORY_DOCUMENT && !$genre->getSupplementary() && !$genre->getDependent()) {
                        $resourceType = 'Article';
                    }
                } else {
                    $resourceType = 'Article';
                }
                break;
            case isset($article):
                $resourceType = 'Article';
                break;
            case isset($issue):
                $resourceType = 'Journal Issue';
                break;
            default:
                assert(false);
        }
        if ($resourceType == 'Article') {
            // Create the resourceType element for Article and Galley.
            $resourceTypeNode = $doc->createElementNS($deployment->getNamespace(), 'resourceType');
            $resourceTypeNode->setAttribute('resourceTypeGeneral', 'JournalArticle');
        } elseif ($resourceType == 'Journal Issue') {
            $resourceTypeNode = $doc->createElementNS($deployment->getNamespace(), 'resourceType', $resourceType);
            $resourceTypeNode->setAttribute('resourceTypeGeneral', 'Text');
        } else {
            // It is a supplementary file
            $resourceTypeNode = $doc->createElementNS($deployment->getNamespace(), 'resourceType');
            $resourceTypeNode->setAttribute('resourceTypeGeneral', 'Dataset');
        }
        return $resourceTypeNode;
    }

    /**
     * Generate alternate identifiers node list.
     *
     * @param \DOMDocument $doc
     * @param Issue $issue
     * @param Submission $article
     * @param Galley $galley
     *
     * @return \DOMElement
     */
    public function createAlternateIdentifiersNode($doc, $issue, $article, $galley)
    {
        $deployment = $this->getDeployment();
        $context = $deployment->getContext();
        $alternateIdentifiersNode = $doc->createElementNS($deployment->getNamespace(), 'alternateIdentifiers');
        // Proprietary ID
        $proprietaryId = $context->getId();
        if ($issue) {
            $proprietaryId .= '-' . $issue->getId();
        }
        if ($article) {
            $proprietaryId .= '-' . $article->getId();
        }
        if ($galley) {
            $proprietaryId .= '-g' . $galley->getId();
        }
        $alternateIdentifiersNode->appendChild($node = $doc->createElementNS($deployment->getNamespace(), 'alternateIdentifier', $proprietaryId));
        $node->setAttribute('alternateIdentifierType', DATACITE_IDTYPE_PROPRIETARY);
        // ISSN - for issues only.
        if (!isset($article) && !isset($galley)) {
            $onlineIssn = $context->getData('onlineIssn');
            if (!empty($onlineIssn)) {
                $alternateIdentifiersNode->appendChild($node = $doc->createElementNS($deployment->getNamespace(), 'alternateIdentifier', $onlineIssn));
                $node->setAttribute('alternateIdentifierType', DATACITE_IDTYPE_EISSN);
            }
            $printIssn = $context->getData('printIssn');
            if (!empty($printIssn)) {
                $alternateIdentifiersNode->appendChild($node = $doc->createElementNS($deployment->getNamespace(), 'alternateIdentifier', $printIssn));
                $node->setAttribute('alternateIdentifierType', DATACITE_IDTYPE_ISSN);
            }
        }
        return $alternateIdentifiersNode;
    }

    /**
     * Generate related identifiers node list.
     *
     * @param \DOMDocument $doc
     * @param Issue $issue
     * @param Submission $article
     * @param Publication $publication
     * @param Galley $galley
     *
     * @return ?\DOMElement
     */
    public function createRelatedIdentifiersNode($doc, $issue, $article, $publication, $galley)
    {
        $deployment = $this->getDeployment();
        $relatedIdentifiersNode = $doc->createElementNS($deployment->getNamespace(), 'relatedIdentifiers');
        switch (true) {
            case isset($galley):
                // Part of: article.
                assert(isset($article));
                $doi = $article->getStoredPubId('doi');
                if (!empty($doi)) {
                    $relatedIdentifiersNode->appendChild($node = $doc->createElementNS($deployment->getNamespace(), 'relatedIdentifier', htmlspecialchars($doi, ENT_COMPAT, 'UTF-8')));
                    $node->setAttribute('relatedIdentifierType', DATACITE_IDTYPE_DOI);
                    $node->setAttribute('relationType', DATACITE_RELTYPE_ISPARTOF);
                }
                break;
            case isset($article):
                // Part of: issue.
                assert(isset($issue));
                $doi = $issue->getDoi();
                if (!empty($doi)) {
                    $relatedIdentifiersNode->appendChild($node = $doc->createElementNS($deployment->getNamespace(), 'relatedIdentifier', htmlspecialchars($doi, ENT_COMPAT, 'UTF-8')));
                    $node->setAttribute('relatedIdentifierType', DATACITE_IDTYPE_DOI);
                    $node->setAttribute('relationType', DATACITE_RELTYPE_ISPARTOF);
                }
                unset($doi);
                // Parts: galleys.
                $galleysByArticle = $publication->getData('galleys');
                foreach ($galleysByArticle as $relatedGalley) {
                    $doi = $relatedGalley->getDoi();
                    if (!empty($doi)) {
                        $relatedIdentifiersNode->appendChild($node = $doc->createElementNS($deployment->getNamespace(), 'relatedIdentifier', htmlspecialchars($doi, ENT_COMPAT, 'UTF-8')));
                        $node->setAttribute('relatedIdentifierType', DATACITE_IDTYPE_DOI);
                        $node->setAttribute('relationType', DATACITE_RELTYPE_HASPART);
                    }
                    unset($relatedGalley, $doi);
                }
                break;
            case isset($issue):
                // Parts: articles in this issue.
                $submissions = Repo::submission()
                    ->getCollector()
                    ->filterByContextIds([$issue->getJournalId()])
                    ->filterByIssueIds([$issue->getId()])
                    ->getMany();

                foreach ($submissions as $relatedArticle) {
                    $doi = $relatedArticle->getCurrentPublication()?->getDoi();
                    if (!empty($doi)) {
                        $relatedIdentifiersNode->appendChild($node = $doc->createElementNS($deployment->getNamespace(), 'relatedIdentifier', htmlspecialchars($doi, ENT_COMPAT, 'UTF-8')));
                        $node->setAttribute('relatedIdentifierType', DATACITE_IDTYPE_DOI);
                        $node->setAttribute('relationType', DATACITE_RELTYPE_HASPART);
                    }
                    unset($relatedArticle, $doi);
                }
                break;
        }
        if ($relatedIdentifiersNode->hasChildNodes()) {
            return $relatedIdentifiersNode;
        } else {
            return null;
        }
    }

    /**
     * Create a sizes node list.
     *
     * @param \DOMDocument $doc
     * @param Issue $issue
     * @param Submission $article
     * @param Publication $publication
     * @param Galley $galley
     * @param SubmissionFile $galleyFile
     *
     * @return ?\DOMElement Can be null if a size cannot be identified for the given object.
     */
    public function createSizesNode($doc, $issue, $article, $publication, $galley, $galleyFile)
    {
        $deployment = $this->getDeployment();
        $sizes = [];
        switch (true) {
            case isset($galley):
                // The galley represents the article.
                if (isset($galleyFile)) {
                    $path = $galleyFile->getData('path');
                    $size = Services::get('file')->fs->fileSize($path);
                    $sizes[] = Services::get('file')->getNiceFileSize($size);
                }
                break;
            case isset($issue):
                $issueGalleyDao = DAORegistry::getDAO('IssueGalleyDAO'); /** @var IssueGalleyDAO $issueGalleyDao */
                $issueGalleyFiles = $issueGalleyDao->getByIssueId($issue->getId());
                foreach ($issueGalleyFiles as $issueGalleyFile) {
                    if ($issueGalleyFile) {
                        $sizes[] = $issueGalleyFile->getNiceFileSize();
                    }
                }
                break;
            default:
                assert(false);
        }
        $sizesNode = null;
        if (!empty($sizes)) {
            $sizesNode = $doc->createElementNS($deployment->getNamespace(), 'sizes');
            foreach ($sizes as $size) {
                $sizesNode->appendChild($node = $doc->createElementNS($deployment->getNamespace(), 'size', htmlspecialchars($size, ENT_COMPAT, 'UTF-8')));
            }
        }
        return $sizesNode;
    }

    /**
     * Create descriptions node list.
     *
     * @param \DOMDocument $doc
     * @param Issue $issue
     * @param Submission $article
     * @param Publication $publication
     * @param Galley $galley
     * @param SubmissionFile $galleyFile
     * @param array $objectLocalePrecedence
     *
     * @return ?\DOMElement Can be null if a size cannot be identified for the given object.
     */
    public function createDescriptionsNode($doc, $issue, $article, $publication, $galley, $galleyFile, $objectLocalePrecedence)
    {
        /** @var DataciteExportDeployment */
        $deployment = $this->getDeployment();
        /** @var DataciteExportPlugin */
        $plugin = $deployment->getPlugin();
        $descriptions = [];
        switch (true) {
            case isset($galley):
                if (!$galley->getRemoteURL()) {
                    $genre = $plugin->getCache()->get('genres', $galleyFile->getData('genreId'));
                    if ($genre->getSupplementary()) {
                        $suppFileDesc = $this->getPrimaryTranslation($galleyFile->getData('description'), $objectLocalePrecedence);
                        if (!empty($suppFileDesc)) {
                            $descriptions[DATACITE_DESCTYPE_OTHER] = $suppFileDesc;
                        }
                    }
                }
                break;
            case isset($article):
                $articleAbstract = $this->getPrimaryTranslation($publication->getData('abstract'), $objectLocalePrecedence);
                if (!empty($articleAbstract)) {
                    $descriptions[DATACITE_DESCTYPE_ABSTRACT] = $articleAbstract;
                }
                break;
            case isset($issue):
                $issueDesc = $this->getPrimaryTranslation($issue->getDescription(null), $objectLocalePrecedence);
                if (!empty($issueDesc)) {
                    $descriptions[DATACITE_DESCTYPE_OTHER] = $issueDesc;
                }
                $descriptions[DATACITE_DESCTYPE_TOC] = $this->getIssueToc($issue, $objectLocalePrecedence);
                break;
            default:
                assert(false);
        }
        $descriptionsNode = null;
        if (!empty($descriptions)) {
            $descriptionsNode = $doc->createElementNS($deployment->getNamespace(), 'descriptions');
            foreach ($descriptions as $descType => $description) {
                $descriptionsNode->appendChild($node = $doc->createElementNS($deployment->getNamespace(), 'description', htmlspecialchars(PKPString::html2text($description), ENT_COMPAT, 'UTF-8')));
                $node->setAttribute('descriptionType', $descType);
            }
        }
        return $descriptionsNode;
    }

    /**
     * Create related items node.
     *
     * @param \DOMDocument $doc
     * @param Issue $issue
     * @param Submission $article
     * @param Publication $publication
     * @param string $publisher
     * @param array $objectLocalePrecedence
     *
     * @return ?\DOMElement Can be null if a size cannot be identified for the given object.
     */
    public function createRelatedItemsNode($doc, $issue, $article, $publication, $publisher, $objectLocalePrecedence)
    {
        /** @var DataciteExportDeployment */
        $deployment = $this->getDeployment();
        $context = $deployment->getContext();
        $request = Application::get()->getRequest();

        $relatedItemsNode = null;
        if (isset($article)) {
            $relatedItemsNode = $doc->createElementNS($deployment->getNamespace(), 'relatedItems');

            $relatedItemNode = $doc->createElementNS($deployment->getNamespace(), 'relatedItem');
            $relatedItemNode->setAttribute('relationType', DATACITE_RELTYPE_ISPUBLISHEDIN);
            $relatedItemNode->setAttribute('relatedItemType', 'Journal');

            if (null !== $context->getData('onlineIssn')) {
                $relatedItemIdentifierNode = $doc->createElementNS($deployment->getNamespace(), 'relatedItemIdentifier', $context->getData('onlineIssn'));
                $relatedItemIdentifierNode->setAttribute('relatedItemIdentifierType', DATACITE_IDTYPE_EISSN);
            } elseif (null !== $context->getData('printIssn')) {
                $relatedItemIdentifierNode = $doc->createElementNS($deployment->getNamespace(), 'relatedItemIdentifier', $context->getData('printIssn'));
                $relatedItemIdentifierNode->setAttribute('relatedItemIdentifierType', DATACITE_IDTYPE_ISSN);
            } else {
                $contextUrl = $request->getDispatcher()->url(
                    $request,
                    Application::ROUTE_PAGE,
                    $context->getPath()
                );
                $relatedItemIdentifierNode = $doc->createElementNS($deployment->getNamespace(), 'relatedItemIdentifier', $contextUrl);
                $relatedItemIdentifierNode->setAttribute('relatedItemIdentifierType', DATACITE_IDTYPE_URL);
            }
            $relatedItemNode->appendChild($relatedItemIdentifierNode);

            $titlesNode = $doc->createElementNS($deployment->getNamespace(), 'titles');
            $titleNode = $doc->createElementNS($deployment->getNamespace(), 'title');
            $titleNode->appendChild($doc->createTextNode($publisher));
            $titlesNode->appendChild($titleNode);
            $relatedItemNode->appendChild($titlesNode);

            if ($issue->getVolume()) {
                $relatedItemNode->appendChild($node = $doc->createElementNS($deployment->getNamespace(), 'volume', $issue->getVolume()));
            }

            $issueNode = $doc->createElementNS($deployment->getNamespace(), 'issue');
            if ($issue->getNumber()) {
                $issueNode->appendChild($doc->createTextNode($issue->getNumber()));
            } else {
                $issueNode->appendChild($doc->createTextNode($this->getIssueInformation($issue, $objectLocalePrecedence)));
            }
            $relatedItemNode->appendChild($issueNode);

            $pages = $publication->getPageArray();
            if (!empty($pages)) {
                $firstRange = array_shift($pages);
                $firstPage = array_shift($firstRange);
                if (count($firstRange)) {
                    // There is a first page and last page for the first range
                    $lastPage = array_shift($firstRange);
                } else {
                    // There is not a range in the first segment
                    $lastPage = '';
                }
                // No punctuation in first_page or last_page
                if ((!empty($firstPage) || $firstPage === '0') && !preg_match('/[^[:alnum:]]/', $firstPage) && !preg_match('/[^[:alnum:]]/', $lastPage)) {
                    $relatedItemNode->appendChild($node = $doc->createElementNS($deployment->getNamespace(), 'firstPage', $firstPage));
                    if ($lastPage != '') {
                        $relatedItemNode->appendChild($node = $doc->createElementNS($deployment->getNamespace(), 'lastPage', $lastPage));
                    }
                }
            }

            $relatedItemsNode->appendChild($relatedItemNode);
        }
        return $relatedItemsNode;
    }


    //
    // Helper functions
    //
    /**
     * Identify the locale precedence for this export.
     *
     * @param \PKP\context\Context $context
     * @param \APP\submission\Submission $article
     * @param \APP\publication\Publication $publication
     * @param \PKP\galley\Galley $galley
     *
     * @return array A list of valid PKP locales in descending
     *  order of priority.
     */
    public function getObjectLocalePrecedence($context, $article, $publication, $galley)
    {
        $locales = [];
        if ($galley instanceof Galley && Locale::isLocaleValid($galley->getLocale())) {
            $locales[] = $galley->getLocale();
        }
        if ($article instanceof Submission) {
            if (!is_null($publication->getData('locale'))) {
                $locales[] = $publication->getData('locale');
            }
        }
        // Use the journal locale as fallback.
        $locales[] = $context->getPrimaryLocale();
        // Use form locales as fallback.
        $formLocales = $context->getSupportedFormLocales();
        // Sort form locales alphabetically so that
        // we get a well-defined order.
        sort($formLocales);
        foreach ($formLocales as $formLocale) {
            if (!in_array($formLocale, $locales)) {
                $locales[] = $formLocale;
            }
        }
        assert(!empty($locales));
        return $locales;
    }

    /**
     * Try to translate an ISO language code to an OJS locale.
     *
     * @param string $language 2- or 3-letter ISO language code
     *
     * @return string|null An OJS locale or null if no matching
     *  locale could be found.
     */
    public function translateLanguageToLocale($language)
    {
        $locale = null;
        if (strlen($language) == 2) {
            $language = LocaleConversion::get3LetterFrom2LetterIsoLanguage($language);
        }
        if (strlen($language) == 3) {
            $language = LocaleConversion::getLocaleFrom3LetterIso($language);
        }
        if (Locale::isLocaleValid($language)) {
            $locale = $language;
        }
        return $locale;
    }

    /**
     * Identify the primary translation from an array of
     * localized data.
     *
     * @param array $localizedData An array of localized
     *  data (key: locale, value: localized data).
     * @param array $localePrecedence An array of locales
     *  by descending priority.
     *
     * @return mixed|null The value of the primary locale
     *  or null if no primary translation could be found.
     */
    public function getPrimaryTranslation($localizedData, $localePrecedence)
    {
        // Check whether we have localized data at all.
        if (!is_array($localizedData) || empty($localizedData)) {
            return null;
        }
        // Try all locales from the precedence list first.
        foreach ($localePrecedence as $locale) {
            if (isset($localizedData[$locale]) && !empty($localizedData[$locale])) {
                return $localizedData[$locale];
            }
        }
        // As a fallback: use any translation by alphabetical
        // order of locales.
        ksort($localizedData);
        foreach ($localizedData as $locale => $value) {
            if (!empty($value)) {
                return $value;
            }
        }
        // If we found nothing (how that?) return null.
        return null;
    }

    /**
     * Re-order localized data by locale precedence.
     *
     * @param array $localizedData An array of localized
     *  data (key: locale, value: localized data).
     * @param array $localePrecedence An array of locales
     *  by descending priority.
     *
     * @return array Re-ordered localized data.
     */
    public function getTranslationsByPrecedence($localizedData, $localePrecedence)
    {
        $reorderedLocalizedData = [];

        // Check whether we have localized data at all.
        if (!is_array($localizedData) || empty($localizedData)) {
            return $reorderedLocalizedData;
        }

        // Order by explicit locale precedence first.
        foreach ($localePrecedence as $locale) {
            if (isset($localizedData[$locale]) && !empty($localizedData[$locale])) {
                $reorderedLocalizedData[$locale] = $localizedData[$locale];
            }
            unset($localizedData[$locale]);
        }

        // Order any remaining values alphabetically by locale
        // and amend the re-ordered array.
        ksort($localizedData);
        $reorderedLocalizedData = array_merge($reorderedLocalizedData, $localizedData);

        return $reorderedLocalizedData;
    }

    /**
     * Construct an issue title from the journal title
     * and the issue identification.
     *
     * @param \APP\issue\Issue $issue
     * @param array $objectLocalePrecedence
     *
     * @return array|string An array of localized issue titles
     *  or a string if a locale has been given.
     */
    public function getIssueInformation($issue, $objectLocalePrecedence = null)
    {
        $deployment = $this->getDeployment();
        $context = $deployment->getContext();
        $issueIdentification = $issue->getIssueIdentification();
        assert(!empty($issueIdentification));
        if (is_null($objectLocalePrecedence)) {
            $issueInfo = [];
            foreach ($context->getName(null) as $locale => $contextName) {
                $issueInfo[$locale] = "{$contextName}, {$issueIdentification}";
            }
        } else {
            $issueInfo = $this->getPrimaryTranslation($context->getName(null), $objectLocalePrecedence);
            if (!empty($issueInfo)) {
                $issueInfo .= ', ';
            }
            $issueInfo .= $issueIdentification;
        }
        return $issueInfo;
    }

    /**
     * Construct a table of content for an issue.
     *
     * @param \APP\issue\Issue $issue
     * @param array $objectLocalePrecedence
     *
     * @return string
     */
    public function getIssueToc($issue, $objectLocalePrecedence)
    {
        $submissions = Repo::submission()
            ->getCollector()
            ->filterByContextIds([$issue->getJournalId()])
            ->filterByIssueIds([$issue->getId()])
            ->getMany();

        $toc = '';
        foreach ($submissions as $submissionInIssue) { /** @var Submission $submissionInIssue */
            $currentEntry = $this->getPrimaryTranslation(
                // get html format because later PKPString::html2text will be applied
                $submissionInIssue->getCurrentPublication()?->getTitles('html') ?? [],
                $objectLocalePrecedence
            );
            assert(!empty($currentEntry));
            $pages = $submissionInIssue?->getCurrentPublication()?->getData('pages') ?? '';
            if (!empty($pages)) {
                $currentEntry .= '...' . $pages;
            }
            $toc .= $currentEntry . '<br />';
            unset($submissionInIssue);
        }
        return $toc;
    }
}
