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
use PKP\db\DAORegistry;
use PKP\facades\Locale;
use PKP\i18n\LocaleConversion;
use PKP\metadata\MetadataDataObjectAdapter;
use PKP\metadata\MetadataDescription;
use PKP\plugins\Hook;
use PKP\plugins\PluginRegistry;
use PKP\submission\SubmissionKeywordDAO;
use PKP\submission\SubmissionSubjectDAO;

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
     */
    public function &extractMetadataFromDataObject(&$article)
    {
        assert($article instanceof Submission);

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
        if ($article instanceof Submission) { /** @var Submission $article */
            $issue = $oaiDao->getIssue($publication->getData('issueId'));
        } else {
            $issue = null;
        }

        $dc11Description = $this->instantiateMetadataDescription();

        // Title
        $this->_addLocalizedElements($dc11Description, 'dc:title', $article->getFullTitle(null));

        // Creator
        foreach ($publication->getData('authors') as $author) {
            $this->_addLocalizedElements($dc11Description, 'dc:creator', $author->getFullNames(false, true));
        }

        // Subject
        $submissionKeywordDao = DAORegistry::getDAO('SubmissionKeywordDAO'); /** @var SubmissionKeywordDAO $submissionKeywordDao */
        $submissionSubjectDao = DAORegistry::getDAO('SubmissionSubjectDAO'); /** @var SubmissionSubjectDAO $submissionSubjectDao */
        $supportedLocales = $journal->getSupportedFormLocales();
        $subjects = array_merge_recursive(
            (array) $submissionKeywordDao->getKeywords($publication->getId(), $supportedLocales),
            (array) $submissionSubjectDao->getSubjects($publication->getId(), $supportedLocales)
        );
        $this->_addLocalizedElements($dc11Description, 'dc:subject', $subjects);

        // Description
        $this->_addLocalizedElements($dc11Description, 'dc:description', $article->getAbstract(null));

        // Publisher
        $publisherInstitution = $journal->getData('publisherInstitution');
        if (!empty($publisherInstitution)) {
            $publishers = [$journal->getPrimaryLocale() => $publisherInstitution];
        } else {
            $publishers = $journal->getName(null); // Default
        }
        $this->_addLocalizedElements($dc11Description, 'dc:publisher', $publishers);

        // Contributor
        $contributors = (array) $article->getSponsor(null);
        foreach ($contributors as $locale => $contributor) {
            $contributors[$locale] = array_map('trim', explode(';', $contributor));
        }
        $this->_addLocalizedElements($dc11Description, 'dc:contributor', $contributors);


        // Date
        if ($article instanceof Submission) {
            if ($article->getDatePublished()) {
                $dc11Description->addStatement('dc:date', date('Y-m-d', strtotime($article->getDatePublished())));
            } elseif (isset($issue) && $issue->getDatePublished()) {
                $dc11Description->addStatement('dc:date', date('Y-m-d', strtotime($issue->getDatePublished())));
            }
        }

        // Type
        $driverType = 'info:eu-repo/semantics/article';
        $dc11Description->addStatement('dc:type', $driverType, MetadataDescription::METADATA_DESCRIPTION_UNKNOWN_LOCALE);
        $types = $section->getIdentifyType(null);
        $types = array_merge_recursive(
            empty($types) ? [Locale::getLocale() => __('metadata.pkp.peerReviewed')] : $types,
            (array) $article->getType(null)
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
        if ($article instanceof Submission && $includeUrls) {
            $dc11Description->addStatement('dc:identifier', $request->url($journal->getPath(), 'article', 'view', [$article->getBestId()]));
        }

        // Source (journal title, issue id and pages)
        $sources = $journal->getName(null);
        $pages = $article->getPages();
        if (!empty($pages)) {
            $pages = '; ' . $pages;
        }
        foreach ($sources as $locale => $source) {
            if ($article instanceof Submission) {
                $sources[$locale] .= '; ' . $issue->getIssueIdentification([], $locale);
            }
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
        $locales = [];
        if ($article instanceof Submission) {
            foreach ($galleys as $galley) {
                $locale = $galley->getLocale();
                if (!is_null($locale) && !in_array($locale, $locales)) {
                    $locales[] = $locale;
                    $dc11Description->addStatement('dc:language', LocaleConversion::getIso3FromLocale($locale));
                }
            }
        }
        $articleLanguages = $article->getLanguage();
        if (empty($locales) && isset($articleLanguages[$article->getLocale()])) {
            foreach ($articleLanguages[$article->getLocale()] as $language) {
                $dc11Description->addStatement('dc:language', $language);
            }
        }

        // Relation
        // full text URLs
        if ($includeUrls) {
            foreach ($galleys as $galley) {
                $relation = $request->url($journal->getPath(), 'article', 'view', [$article->getBestId(), $galley->getBestGalleyId()]);
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
            if ($pubArticleId = $article->getStoredPubId($publicIdentifier)) {
                $dc11Description->addStatement('dc:identifier', $pubArticleId);
            }
            foreach ($galleys as $galley) {
                if ($pubGalleyId = $galley->getStoredPubId($publicIdentifier)) {
                    $dc11Description->addStatement('dc:relation', $pubGalleyId);
                }
            }
        }

        // Coverage
        $this->_addLocalizedElements($dc11Description, 'dc:coverage', (array) $article->getCoverage(null));

        // Rights: Add both copyright statement and license
        $copyrightHolder = $article->getLocalizedCopyrightHolder();
        $copyrightYear = $article->getCopyrightYear();
        if (!empty($copyrightHolder) && !empty($copyrightYear)) {
            $dc11Description->addStatement('dc:rights', __('submission.copyrightStatement', ['copyrightHolder' => $copyrightHolder, 'copyrightYear' => $copyrightYear]));
        }
        if ($licenseUrl = $article->getLicenseURL()) {
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
