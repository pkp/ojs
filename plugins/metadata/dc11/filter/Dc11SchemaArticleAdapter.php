<?php

/**
 * @file plugins/metadata/dc11/filter/Dc11SchemaArticleAdapter.php
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2000-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class Dc11SchemaArticleAdapter
 *
 * @ingroup plugins_metadata_dc11_filter
 *
 * @see Article
 * @see PKPDc11Schema
 *
 * @brief Abstract base class for meta-data adapters that
 *  injects/extracts Dublin Core schema compliant meta-data into/from
 *  a Submission object.
 */

namespace APP\plugins\metadata\dc11\filter;

use APP\core\Application;
use APP\facades\Repo;
use APP\issue\IssueAction;
use APP\journal\Journal;
use APP\oai\ojs\OAIDAO;
use APP\plugins\PubIdPlugin;
use APP\submission\Submission;
use PKP\core\PKPApplication;
use PKP\db\DAORegistry;
use PKP\facades\Locale;
use PKP\metadata\MetadataDataObjectAdapter;
use PKP\metadata\MetadataDescription;
use PKP\plugins\Hook;
use PKP\plugins\PluginRegistry;

class Dc11SchemaArticleAdapter extends MetadataDataObjectAdapter
{
    //
    // Implement template methods from MetadataDataObjectAdapter
    //
    /**
     * @see MetadataDataObjectAdapter::injectMetadataIntoDataObject()
     *
     * @param MetadataDescription $metadataDescription
     * @param Submission $targetDataObject
     */
    public function &injectMetadataIntoDataObject(&$metadataDescription, &$targetDataObject)
    {
        // Not implemented
        assert(false);
    }

    /**
     * @see MetadataDataObjectAdapter::extractMetadataFromDataObject()
     *
     * @param Submission $article
     *
     * @return MetadataDescription
     *
     * @hook Dc11SchemaArticleAdapter::extractMetadataFromDataObject [[$this, $article, $journal, $issue, &$dc11Description]]
     */
    public function &extractMetadataFromDataObject(&$article)
    {
        // Retrieve data that belongs to the article.
        // FIXME: Retrieve this data from the respective entity DAOs rather than
        // from the OAIDAO once we've migrated all OAI providers to the
        // meta-data framework. We're using the OAIDAO here because it
        // contains cached entities and avoids extra database access if this
        // adapter is called from an OAI context.
        $oaiDao = DAORegistry::getDAO('OAIDAO'); /** @var OAIDAO $oaiDao */
        /** @var Journal */
        $journal = $oaiDao->getJournal($article->getData('contextId'));
        $section = $oaiDao->getSection($article->getSectionId());
        $publication = $article->getCurrentPublication();
        $issue = $oaiDao->getIssue($publication->getData('issueId'));

        $dc11Description = $this->instantiateMetadataDescription();

        // Title
        $this->_addLocalizedElements($dc11Description, 'dc:title', $publication->getFullTitles());

        // Creator
        foreach ($publication->getData('authors') as $author) {
            $this->_addLocalizedElements($dc11Description, 'dc:creator', $author->getFullNames(false, true));
        }

        // Subject
        $subjects = array_merge_recursive(
            collect($publication->getData('keywords'))
                ->map(
                    fn (array $items): array => collect($items)
                        ->pluck('name')
                        ->all()
                )
                ->all(),
            collect($publication->getData('subjects'))
                ->map(
                    fn (array $items): array => collect($items)
                        ->pluck('name')
                        ->all()
                )
                ->all()
        );

        $this->_addLocalizedElements($dc11Description, 'dc:subject', $subjects);

        // Description
        $this->_addLocalizedElements($dc11Description, 'dc:description', $publication->getData('abstract'));

        // Publisher
        $publisherInstitution = $journal->getData('publisherInstitution');
        if (!empty($publisherInstitution)) {
            $publishers = [$journal->getPrimaryLocale() => $publisherInstitution];
        } else {
            $publishers = $journal->getName(null); // Default
        }
        $this->_addLocalizedElements($dc11Description, 'dc:publisher', $publishers);

        // Contributor
        $contributors = (array) $publication->getData('sponsor');
        foreach ($contributors as $locale => $contributor) {
            $contributors[$locale] = array_map(trim(...), explode(';', $contributor));
        }
        $this->_addLocalizedElements($dc11Description, 'dc:contributor', $contributors);


        // Date
        if ($datePublished = $publication->getData('datePublished')) {
            $dc11Description->addStatement('dc:date', date('Y-m-d', strtotime($datePublished)));
        } elseif (isset($issue) && $issue->getDatePublished()) {
            $dc11Description->addStatement('dc:date', date('Y-m-d', strtotime($issue->getDatePublished())));
        }

        // Type
        $driverType = 'info:eu-repo/semantics/article';
        $dc11Description->addStatement('dc:type', $driverType, MetadataDescription::METADATA_DESCRIPTION_UNKNOWN_LOCALE);
        $types = $section->getIdentifyType(null);
        $types = array_merge_recursive(
            empty($types) ? [Locale::getLocale() => __('metadata.pkp.peerReviewed')] : $types,
            (array) $publication->getData('type')
        );
        $this->_addLocalizedElements($dc11Description, 'dc:type', $types);
        $driverVersion = 'info:eu-repo/semantics/publishedVersion';
        $dc11Description->addStatement('dc:type', $driverVersion, MetadataDescription::METADATA_DESCRIPTION_UNKNOWN_LOCALE);

        $galleys = Repo::galley()->getCollector()
            ->filterByPublicationIds([$publication->getId()])
            ->getMany();

        // Format
        foreach ($galleys as $galley) {
            $dc11Description->addStatement('dc:format', $galley->getFileType());
        }

        // Identifier: URL
        $issueAction = new IssueAction();
        $request = Application::get()->getRequest();
        $includeUrls = $journal->getSetting('publishingMode') != Journal::PUBLISHING_MODE_NONE || $issueAction->subscribedUser($request->getUser(), $journal, null, $article->getId());
        if ($includeUrls) {
            $dc11Description->addStatement('dc:identifier', $request->getDispatcher()->url($request, PKPApplication::ROUTE_PAGE, null, 'article', 'view', [$article->getBestId()], urlLocaleForPage: ''));
        }

        // Source (journal title, issue id and pages)
        $sources = $journal->getName(null);
        $pages = $publication->getData('pages');
        if (!empty($pages)) {
            $pages = '; ' . $pages;
        }
        foreach ($sources as $locale => $source) {
            $sources[$locale] .= '; ' . $issue->getIssueIdentification([], $locale);
            $sources[$locale] .= $pages;
        }
        $this->_addLocalizedElements($dc11Description, 'dc:source', $sources);
        if ($issn = $journal->getData('onlineIssn')) {
            $dc11Description->addStatement('dc:source', $issn, MetadataDescription::METADATA_DESCRIPTION_UNKNOWN_LOCALE);
        }
        if ($issn = $journal->getData('printIssn')) {
            $dc11Description->addStatement('dc:source', $issn, MetadataDescription::METADATA_DESCRIPTION_UNKNOWN_LOCALE);
        }

        // Language
        collect($galleys)
            ->map(fn ($g) => $g->getData('locale'))
            ->push($publication->getData('locale'))
            ->filter()
            ->unique()
            ->each(fn ($l) => $dc11Description->addStatement('dc:language', $l));

        // Relation
        // full text URLs
        if ($includeUrls) {
            foreach ($galleys as $galley) {
                $relation = $request->getDispatcher()->url($request, PKPApplication::ROUTE_PAGE, null, 'article', 'view', [$article->getBestId(), $galley->getBestGalleyId()], urlLocaleForPage: '');
                $dc11Description->addStatement('dc:relation', $relation);
            }
        }

        // Public identifiers
        $publicIdentifiers = array_map(fn (PubIdPlugin $plugin) => $plugin->getPubIdType(), (array) PluginRegistry::loadCategory('pubIds', true, $journal->getId()));
        if ($journal->areDoisEnabled()) {
            $publicIdentifiers[] = 'doi';
        }
        foreach ($publicIdentifiers as $publicIdentifier) {
            if ($issue && $pubIssueId = $issue->getStoredPubId($publicIdentifier)) {
                $dc11Description->addStatement('dc:source', $pubIssueId, MetadataDescription::METADATA_DESCRIPTION_UNKNOWN_LOCALE);
            }
            if ($pubArticleId = $publication->getStoredPubId($publicIdentifier)) {
                $dc11Description->addStatement('dc:identifier', $pubArticleId);
            }
            foreach ($galleys as $galley) {
                if ($pubGalleyId = $galley->getStoredPubId($publicIdentifier)) {
                    $dc11Description->addStatement('dc:relation', $pubGalleyId);
                }
            }
        }

        // Coverage
        $this->_addLocalizedElements($dc11Description, 'dc:coverage', (array) $publication->getData('coverage'));

        // Rights: Add both copyright statement and license
        $copyrightHolder = $publication->getLocalizedData('copyrightHolder');
        $copyrightYear = $publication->getData('copyrightYear');
        if (!empty($copyrightHolder) && !empty($copyrightYear)) {
            $dc11Description->addStatement('dc:rights', __('submission.copyrightStatement', ['copyrightHolder' => $copyrightHolder, 'copyrightYear' => $copyrightYear]));
        }
        if ($licenseUrl = $publication->getData('licenseUrl')) {
            $dc11Description->addStatement('dc:rights', $licenseUrl);
        }

        Hook::call('Dc11SchemaArticleAdapter::extractMetadataFromDataObject', [$this, $article, $journal, $issue, &$dc11Description]);

        return $dc11Description;
    }

    /**
     * @see MetadataDataObjectAdapter::getDataObjectMetadataFieldNames()
     *
     * @param bool $translated
     */
    public function getDataObjectMetadataFieldNames($translated = true)
    {
        // All DC fields are mapped.
        return [];
    }


    //
    // Private helper methods
    //
    /**
     * Add an array of localized values to the given description.
     *
     * @param MetadataDescription $description
     * @param string $propertyName
     * @param array $localizedValues
     */
    public function _addLocalizedElements(&$description, $propertyName, $localizedValues)
    {
        foreach (stripAssocArray((array) $localizedValues) as $locale => $values) {
            if (is_scalar($values)) {
                $values = [$values];
            }
            foreach ($values as $value) {
                if (!empty($value)) {
                    $description->addStatement($propertyName, $value, $locale);
                }
            }
        }
    }
}
