<?php

/**
 * @file plugins/importexport/native/filter/NativeXmlIssueGalleyFilter.inc.php
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2000-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class NativeXmlIssueGalleyFilter
 * @ingroup plugins_importexport_native
 *
 * @brief Base class that converts a Native XML document to a set of issue galleys
 */

import('lib.pkp.plugins.importexport.native.filter.NativeImportFilter');

use APP\file\IssueFileManager;
use APP\issue\Issue;

class NativeXmlIssueGalleyFilter extends NativeImportFilter
{
    /**
     * Constructor
     *
     * @param FilterGroup $filterGroup
     */
    public function __construct($filterGroup)
    {
        $this->setDisplayName('Native XML issue galley import');
        parent::__construct($filterGroup);
    }

    //
    // Implement template methods from PersistableFilter
    //
    /**
     * @copydoc PersistableFilter::getClassName()
     */
    public function getClassName()
    {
        return 'plugins.importexport.native.filter.NativeXmlIssueGalleyFilter';
    }

    //
    // Override methods in NativeImportFilter
    //
    /**
     * Return the plural element name
     *
     * @return string
     */
    public function getPluralElementName()
    {
        return 'issue_galleys';
    }

    /**
     * Get the singular element name
     *
     * @return string
     */
    public function getSingularElementName()
    {
        return 'issue_galley';
    }

    //
    // Extend functions in the parent class
    //
    /**
     * Handle a submission element
     *
     * @param DOMElement $node
     *
     * @return IssueGalley
     */
    public function handleElement($node)
    {
        $deployment = $this->getDeployment();
        $context = $deployment->getContext();
        $issue = $deployment->getIssue();
        assert($issue instanceof Issue);

        // Create the data object
        $issueGalleyDao = DAORegistry::getDAO('IssueGalleyDAO'); /** @var IssueGalleyDAO $issueGalleyDao */
        $issueGalley = $issueGalleyDao->newDataObject();
        $issueGalley->setIssueId($issue->getId());
        $locale = $node->getAttribute('locale');
        if (empty($locale)) {
            $locale = $context->getPrimaryLocale();
        }
        $issueGalley->setLocale($locale);
        $issueGalley->setSequence($issueGalleyDao->getNextGalleySequence($issue->getId()));

        // Handle metadata in subelements.
        for ($n = $node->firstChild; $n !== null; $n = $n->nextSibling) {
            if (is_a($n, 'DOMElement')) {
                switch ($n->tagName) {
            case 'id':
                $this->parseIdentifier($n, $issueGalley);
                break;
            case 'label': $issueGalley->setLabel($n->textContent); break;
            case 'issue_file':
                $issueFileDao = DAORegistry::getDAO('IssueFileDAO'); /** @var IssueFileDAO $issueFileDao */
                $issueFile = $issueFileDao->newDataObject();
                $issueFile->setIssueId($issue->getId());

                for ($o = $n->firstChild; $o !== null; $o = $o->nextSibling) {
                    if (is_a($o, 'DOMElement')) {
                        switch ($o->tagName) {
                    case 'file_name': $issueFile->setServerFileName($o->textContent); break;
                    case 'file_type': $issueFile->setFileType($o->textContent); break;
                    case 'file_size': $issueFile->setFileSize($o->textContent); break;
                    case 'content_type': $issueFile->setContentType((int)$o->textContent); break;
                    case 'original_file_name': $issueFile->setOriginalFileName($o->textContent); break;
                    case 'date_uploaded': $issueFile->setDateUploaded($o->textContent); break;
                    case 'date_modified': $issueFile->setDateModified($o->textContent); break;
                    case 'embed':
                        $issueFileManager = new IssueFileManager($issue->getId());
                        $filePath = $issueFileManager->getFilesDir() . $issueFileManager->contentTypeToPath($issueFile->getContentType()) . '/' . $issueFile->getServerFileName();
                        $issueFileManager->writeFile($filePath, base64_decode($o->textContent));
                        break;
                }
                    }
                }
                $issueFileId = $issueFileDao->insertObject($issueFile);
                $issueGalley->setFileId($issueFileId);
                break;
        }
            }
        }

        $issueGalleyDao->insertObject($issueGalley);
        return $issueGalley;
    }

    /**
     * Parse an identifier node and set up the galley object accordingly
     *
     * @param DOMElement $element
     * @param Issue $issue
     */
    public function parseIdentifier($element, $issue)
    {
        $deployment = $this->getDeployment();
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
        }
    }
}
