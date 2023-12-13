<?php

/**
 * @file plugins/importexport/native/filter/NativeXmlIssueFilter.php
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2000-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class NativeXmlIssueFilter
 *
 * @ingroup plugins_importexport_native
 *
 * @brief Base class that converts a Native XML document to a set of issues
 */

namespace APP\plugins\importexport\native\filter;

use APP\core\Application;
use APP\facades\Repo;
use APP\issue\Issue;
use APP\plugins\importexport\native\NativeImportExportDeployment;
use APP\section\Section;
use DOMElement;
use DOMNode;
use PKP\filter\FilterGroup;
use PKP\plugins\importexport\PKPImportExportFilter;
use PKP\plugins\PluginRegistry;

class NativeXmlIssueFilter extends \PKP\plugins\importexport\native\filter\NativeImportFilter
{
    /**
     * Constructor
     *
     * @param FilterGroup $filterGroup
     */
    public function __construct($filterGroup)
    {
        $this->setDisplayName('Native XML issue import');
        parent::__construct($filterGroup);
    }

    //
    // Implement template methods from NativeImportFilter
    //
    /**
     * Return the plural element name
     *
     * @return string
     */
    public function getPluralElementName()
    {
        return 'issues';
    }

    /**
     * Get the singular element name
     *
     * @return string
     */
    public function getSingularElementName()
    {
        return 'issue';
    }

    /**
     * Handle a singular element import.
     *
     * @param DOMElement $node
     *
     * @return Issue
     */
    public function handleElement($node)
    {
        /** @var NativeImportExportDeployment */
        $deployment = $this->getDeployment();
        $context = $deployment->getContext();

        // if the issue identification matches an existing issue, flag to process only child objects
        $issueExists = false;
        $issue = $this->_issueExists($node);
        if ($issue) {
            $issueExists = true;
        } else {
            // Create and insert the issue (ID needed for other entities)
            $issue = Repo::issue()->newDataObject();
            $issue->setJournalId($context->getId());
            $issue->setPublished($node->getAttribute('published'));
            $issue->setAccessStatus($node->getAttribute('access_status'));
            $issue->setData('urlPath', strlen($urlPath = (string) $node->getAttribute('url_path')) ? $urlPath : null);

            $issueId = Repo::issue()->add($issue);

            // Should update current status on journal once issue has been created
            if ($node->getAttribute('current')) {
                Repo::issue()->updateCurrent($context->getId(), Repo::issue()->get($issueId));
            }

            $deployment->addProcessedObjectId(Application::ASSOC_TYPE_ISSUE, $issue->getId());
            $deployment->addImportedRootEntity(Application::ASSOC_TYPE_ISSUE, $issue);
        }
        $deployment->setIssue($issue);

        for ($n = $node->firstChild; $n !== null; $n = $n->nextSibling) {
            if ($n instanceof DOMElement) {
                $this->handleChildElement($n, $issue, $issueExists);
            }
        }
        if (!$issueExists) {
            Repo::issue()->edit($issue, []); // Persist setters
        }
        return $issue;
    }

    /**
     * Handle an element whose parent is the issue element.
     *
     * @param DOMElement $n
     * @param Issue $issue
     * @param bool $processOnlyChildren Do not modify the issue itself, only generate child objects
     */
    public function handleChildElement($n, $issue, $processOnlyChildren)
    {
        $deployment = $this->getDeployment();
        $context = $deployment->getContext();

        $localizedSetterMappings = $this->_getLocalizedIssueSetterMappings();
        $dateSetterMappings = $this->_getDateIssueSetterMappings();

        if (isset($localizedSetterMappings[$n->tagName])) {
            if (!$processOnlyChildren) {
                // If applicable, call a setter for localized content.
                $setterFunction = $localizedSetterMappings[$n->tagName];
                [$locale, $value] = $this->parseLocalizedContent($n);
                if (empty($locale)) {
                    $locale = $context->getPrimaryLocale();
                }
                $issue->$setterFunction($value, $locale);
            }
        } elseif (isset($dateSetterMappings[$n->tagName])) {
            if (!$processOnlyChildren) {
                // Not a localized element?  Check for a date.
                $setterFunction = $dateSetterMappings[$n->tagName];
                $issue->$setterFunction($n->textContent);
            }
        } else {
            switch ($n->tagName) {
                // Otherwise, delegate to specific parsing code
                case 'id':
                    if (!$processOnlyChildren) {
                        $this->parseIdentifier($n, $issue);
                    }
                    break;
                case 'articles':
                    $this->parseArticles($n, $issue);
                    break;
                case 'issue_galleys':
                    if (!$processOnlyChildren) {
                        $this->parseIssueGalleys($n, $issue);
                    }
                    break;
                case 'sections':
                    $this->parseSections($n, $issue);
                    break;
                case 'covers':
                    if (!$processOnlyChildren) {
                        $nativeFilterHelper = new NativeFilterHelper();
                        $nativeFilterHelper->parseIssueCovers($this, $n, $issue);
                    }
                    break;
                case 'issue_identification':
                    if (!$processOnlyChildren) {
                        $this->parseIssueIdentification($n, $issue);
                    }
                    break;
                default:
                    $deployment->addWarning(Application::ASSOC_TYPE_ISSUE, $issue->getId(), __('plugins.importexport.common.error.unknownElement', ['param' => $n->tagName]));
            }
        }
    }

    //
    // Element parsing
    //
    /**
     * Parse an identifier node and set up the issue object accordingly
     *
     * @param DOMElement $element
     * @param Issue $issue
     */
    public function parseIdentifier($element, $issue)
    {
        $deployment = $this->getDeployment();
        $context = $deployment->getContext();
        $advice = $element->getAttribute('advice');
        switch ($element->getAttribute('type')) {
            case 'internal':
                // "update" advice not supported yet.
                assert(!$advice || $advice == 'ignore');
                break;
            case 'public':
                if ($advice == 'update') {
                    $issue->setStoredPubId('publisher-id', $element->textContent);
                }
                break;
            default:
                if ($advice == 'update') {
                    if ($element->getAttribute('type') == 'doi') {
                        $doiFound = Repo::doi()->getCollector()->filterByIdentifier($element->textContent)->getMany()->first();
                        if ($doiFound) {
                            $issue->setData('doiId', $doiFound->getId());
                        } else {
                            $newDoiObject = Repo::doi()->newDataObject(
                                [
                                    'doi' => $element->textContent,
                                    'contextId' => $context->getId()
                                ]
                            );
                            $doiId = Repo::doi()->add($newDoiObject);
                            $issue->setData('doiId', $doiId);
                        }
                    } else {
                        // Load pub id plugins
                        $pubIdPlugins = PluginRegistry::loadCategory('pubIds', true, $context->getId());
                        $issue->setStoredPubId($element->getAttribute('type'), $element->textContent);
                    }
                }
        }
    }

    /**
     * Parse an articles element
     *
     * @param DOMElement $node
     * @param Issue $issue
     */
    public function parseIssueGalleys($node, $issue)
    {
        $deployment = $this->getDeployment();
        for ($n = $node->firstChild; $n !== null; $n = $n->nextSibling) {
            if ($n instanceof DOMElement) {
                switch ($n->tagName) {
                    case 'issue_galley':
                        $this->parseIssueGalley($n, $issue);
                        break;
                    default:
                        $deployment->addWarning(Application::ASSOC_TYPE_ISSUE, $issue->getId(), __('plugins.importexport.common.error.unknownElement', ['param' => $n->tagName]));
                }
            }
        }
    }

    /**
     * Parse an issue galley and add it to the issue.
     *
     * @param DOMElement $n
     * @param Issue $issue
     */
    public function parseIssueGalley($n, $issue)
    {
        $currentFilter = PKPImportExportFilter::getFilter('native-xml=>IssueGalley', $this->getDeployment());
        $issueGalleyDoc = new \DOMDocument('1.0', 'utf-8');
        $issueGalleyDoc->appendChild($issueGalleyDoc->importNode($n, true));
        return $currentFilter->execute($issueGalleyDoc);
    }

    /**
     * Parse an articles element
     *
     * @param DOMElement $node
     * @param Issue $issue
     */
    public function parseArticles($node, $issue)
    {
        $deployment = $this->getDeployment();
        for ($n = $node->firstChild; $n !== null; $n = $n->nextSibling) {
            if ($n instanceof DOMElement) {
                switch ($n->tagName) {
                    case 'article':
                        $this->parseArticle($n, $issue);
                        break;
                    default:
                        $deployment->addWarning(Application::ASSOC_TYPE_ISSUE, $issue->getId(), __('plugins.importexport.common.error.unknownElement', ['param' => $n->tagName]));
                }
            }
        }
    }

    /**
     * Parse an article and add it to the issue.
     *
     * @param DOMElement $n
     * @param Issue $issue
     */
    public function parseArticle($n, $issue)
    {
        $currentFilter = PKPImportExportFilter::getFilter('native-xml=>article', $this->getDeployment());
        $articleDoc = new \DOMDocument('1.0', 'utf-8');
        $articleDoc->appendChild($articleDoc->importNode($n, true));
        return $currentFilter->execute($articleDoc);
    }

    /**
     * Parse a submission file and add it to the submission.
     *
     * @param DOMElement $node
     * @param Issue $issue
     */
    public function parseSections($node, $issue)
    {
        $deployment = $this->getDeployment();
        for ($n = $node->firstChild; $n !== null; $n = $n->nextSibling) {
            if ($n instanceof DOMElement) {
                switch ($n->tagName) {
                    case 'section':
                        $this->parseSection($n);
                        break;
                    default:
                        $deployment->addWarning(Application::ASSOC_TYPE_ISSUE, $issue->getId(), __('plugins.importexport.common.error.unknownElement', ['param' => $n->tagName]));
                }
            }
        }
    }

    /**
     * Parse a section stored in an issue.
     *
     * @param DOMElement $node
     */
    public function parseSection($node)
    {
        $deployment = $this->getDeployment();
        $context = $deployment->getContext();

        // Create the data object
        $section = Repo::section()->newDataObject();
        $section->setContextId($context->getId());
        $reviewFormId = $node->getAttribute('review_form_id');
        $section->setReviewFormId($reviewFormId ? (int) $reviewFormId : null);
        $section->setSequence($node->getAttribute('seq'));
        $section->setEditorRestricted((bool) $node->getAttribute('editor_restricted'));
        $section->setMetaIndexed((bool) $node->getAttribute('meta_indexed'));
        $section->setMetaReviewed((bool) $node->getAttribute('meta_reviewed'));
        $section->setAbstractsNotRequired((bool) $node->getAttribute('abstracts_not_required'));
        $section->setHideAuthor((bool) $node->getAttribute('hide_author'));
        $section->setHideTitle((bool) $node->getAttribute('hide_title'));
        $section->setAbstractWordCount($node->getAttribute('abstract_word_count'));

        $unknownNodes = [];
        for ($n = $node->firstChild; $n !== null; $n = $n->nextSibling) {
            if ($n instanceof DOMElement) {
                switch ($n->tagName) {
                    case 'id':
                        // Only support "ignore" advice for now
                        $advice = $n->getAttribute('advice');
                        assert(!$advice || $advice == 'ignore');
                        break;
                    case 'abbrev':
                        [$locale, $value] = $this->parseLocalizedContent($n);
                        if (empty($locale)) {
                            $locale = $context->getPrimaryLocale();
                        }
                        $section->setAbbrev($value, $locale);
                        break;
                    case 'policy':
                        [$locale, $value] = $this->parseLocalizedContent($n);
                        if (empty($locale)) {
                            $locale = $context->getPrimaryLocale();
                        }
                        $section->setPolicy($value, $locale);
                        break;
                    case 'title':
                        [$locale, $value] = $this->parseLocalizedContent($n);
                        if (empty($locale)) {
                            $locale = $context->getPrimaryLocale();
                        }
                        $section->setTitle($value, $locale);
                        break;
                    default:
                        $unknownNodes[] = $n->tagName;
                }
            }
        }

        if (!$this->_sectionExist($section)) {
            $sectionId = Repo::section()->add($section);
            if (count($unknownNodes)) {
                foreach ($unknownNodes as $tagName) {
                    $deployment->addWarning(Application::ASSOC_TYPE_SECTION, $sectionId, __('plugins.importexport.common.error.unknownElement', ['param' => $tagName]));
                }
            }
            $deployment->addProcessedObjectId(Application::ASSOC_TYPE_SECTION, $sectionId);
        }
    }

    /**
     * Parse out the issue identification and store it in an issue.
     *
     * @param DOMElement $node
     * @param Issue $issue
     * @param bool $allowWarnings Warnings should be suppressed if this function is not being used to populate a new issue
     */
    public function parseIssueIdentification($node, $issue, $allowWarnings = true)
    {
        $deployment = $this->getDeployment();
        $context = $deployment->getContext();
        for ($n = $node->firstChild; $n !== null; $n = $n->nextSibling) {
            if ($n instanceof DOMElement) {
                switch ($n->tagName) {
                    case 'volume':
                        $issue->setVolume($n->textContent);
                        $issue->setShowVolume(1);
                        break;
                    case 'number':
                        $issue->setNumber($n->textContent);
                        $issue->setShowNumber(1);
                        break;
                    case 'year':
                        $issue->setYear($n->textContent);
                        $issue->setShowYear(1);
                        break;
                    case 'title':
                        [$locale, $value] = $this->parseLocalizedContent($n);
                        if (empty($locale)) {
                            $locale = $context->getPrimaryLocale();
                        }
                        $issue->setTitle($value, $locale);
                        $issue->setShowTitle(1);
                        break;
                    default:
                        if ($allowWarnings) {
                            $deployment->addWarning(Application::ASSOC_TYPE_ISSUE, $issue->getId(), __('plugins.importexport.common.error.unknownElement', ['param' => $n->tagName]));
                        }
                }
            }
        }
    }

    //
    // Helper functions
    //
    /**
     * Get node name to setter function mapping for localized data.
     *
     * @return array
     */
    public function _getLocalizedIssueSetterMappings()
    {
        return [
            'description' => 'setDescription',
        ];
    }

    /**
     * Get node name to setter function mapping for issue date fields.
     *
     * @return array
     */
    public function _getDateIssueSetterMappings()
    {
        return [
            'date_published' => 'setDatePublished',
            'date_notified' => 'setDateNotified',
            'last_modified' => 'setLastModified',
            'open_access_date' => 'setOpenAccessDate',
        ];
    }

    /**
     * Check if the issue already exists.
     *
     * @param DOMNode $node issue node
     * return Issue|null matching issue, or null if no match
     */
    public function _issueExists($node)
    {
        $deployment = $this->getDeployment();
        $context = $deployment->getContext();
        $issue = null;
        foreach ($node->getElementsByTagName('issue_identification') as $n) {
            $searchIssue = Repo::issue()->newDataObject();
            $this->parseIssueIdentification($n, $searchIssue, false);

            $collector = Repo::issue()->getCollector()
                ->filterByContextIds([$context->getId()]);
            if ($searchIssue->getVolume() !== null) {
                $collector->filterByVolumes([$searchIssue->getVolume()]);
            }
            if ($searchIssue->getNumber() !== null) {
                $collector->filterByNumbers([$searchIssue->getNumber()]);
            }
            if ($searchIssue->getYear() !== null) {
                $collector->filterByYears([$searchIssue->getYear()]);
            }
            if (!empty((array) $searchIssue->getTitle(null))) {
                $collector->filterByTitles((array) $searchIssue->getTitle(null));
            }

            $foundIssues = $collector->getMany();
            foreach ($foundIssues as $issue) {
                $deployment->addWarning(Application::ASSOC_TYPE_ISSUE, $issue->getId(), __('plugins.importexport.native.import.error.issueIdentificationDuplicate', ['issueId' => $issue->getId(), 'issueIdentification' => $n->ownerDocument->saveXML($n)]));
            }
        }
        return $issue;
    }

    /**
     * Check if the section already exists.
     *
     * @param Section $importSection New created section
     *
     * @return bool
     */
    public function _sectionExist($importSection)
    {
        /** @var NativeImportExportDeployment */
        $deployment = $this->getDeployment();
        $issue = $deployment->getIssue();
        // title and, optionally, abbrev contain information that can
        // be used to locate an existing section. If title and abbrev each match an
        // existing section, but not the same section, throw an error.
        $contextId = $importSection->getContextId();
        $section = null;
        $foundSectionId = $foundSectionTitle = null;
        $index = 0;
        $titles = $importSection->getTitle(null);
        foreach ($titles as $locale => $title) {
            $section = Repo::section()->getCollector()->filterByContextIds([$contextId])->filterByTitles([$title])->getMany()->first();
            if ($section) {
                $sectionId = $section->getId();
                if ($foundSectionId) {
                    if ($foundSectionId != $sectionId) {
                        // Mismatching sections found.
                        $deployment->addWarning(Application::ASSOC_TYPE_ISSUE, $issue->getId(), __('plugins.importexport.native.import.error.sectionTitleMismatch', ['section1Title' => $title, 'section2Title' => $foundSectionTitle, 'issueTitle' => $issue->getIssueIdentification()]));
                    }
                } elseif ($index > 0) {
                    // the current title matches, but the prev titles didn't
                    $deployment->addWarning(Application::ASSOC_TYPE_ISSUE, $issue->getId(), __('plugins.importexport.native.import.error.sectionTitleMatch', ['sectionTitle' => $title, 'issueTitle' => $issue->getIssueIdentification()]));
                }
                $foundSectionId = $sectionId;
                $foundSectionTitle = $title;
            } else {
                if ($foundSectionId) {
                    // a prev title matched, but the current doesn't
                    $deployment->addWarning(Application::ASSOC_TYPE_ISSUE, $issue->getId(), __('plugins.importexport.native.import.error.sectionTitleMatch', ['sectionTitle' => $foundSectionTitle, 'issueTitle' => $issue->getIssueIdentification()]));
                }
            }
            $index++;
        }
        // check abbrevs:
        $abbrevSection = null;
        $foundSectionId = $foundSectionAbbrev = null;
        $index = 0;
        $abbrevs = $importSection->getAbbrev(null);
        foreach ($abbrevs as $locale => $abbrev) {
            $section = Repo::section()->getCollector()->filterByContextIds([$contextId])->filterByAbbrevs([$abbrev])->getMany()->first();
            if ($abbrevSection) {
                $sectionId = $abbrevSection->getId();
                if ($foundSectionId) {
                    if ($foundSectionId != $sectionId) {
                        // Mismatching sections found.
                        $deployment->addWarning(Application::ASSOC_TYPE_ISSUE, $issue->getId(), __('plugins.importexport.native.import.error.sectionAbbrevMismatch', ['section1Abbrev' => $abbrev, 'section2Abbrev' => $foundSectionAbbrev, 'issueTitle' => $issue->getIssueIdentification()]));
                    }
                } elseif ($index > 0) {
                    // the current abbrev matches, but the prev abbrevs didn't
                    $deployment->addWarning(Application::ASSOC_TYPE_ISSUE, $issue->getId(), __('plugins.importexport.native.import.error.sectionAbbrevMatch', ['sectionAbbrev' => $abbrev, 'issueTitle' => $issue->getIssueIdentification()]));
                }
                $foundSectionId = $sectionId;
                $foundSectionAbbrev = $abbrev;
            } else {
                if ($foundSectionId) {
                    // a prev abbrev matched, but the current doesn't
                    $deployment->addWarning(Application::ASSOC_TYPE_ISSUE, $issue->getId(), __('plugins.importexport.native.import.error.sectionAbbrevMatch', ['sectionAbbrev' => $foundSectionAbbrev, 'issueTitle' => $issue->getIssueIdentification()]));
                }
            }
            $index++;
        }
        if (isset($section) && isset($abbrevSection)) {
            return $section->getId() == $abbrevSection->getId();
        } else {
            return isset($section) || isset($abbrevSection);
        }
    }
}
