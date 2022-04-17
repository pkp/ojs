<?php

/**
 * @file plugins/importexport/native/filter/NativeXmlArticleGalleyFilter.inc.php
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2000-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class NativeXmlArticleGalleyFilter
 * @ingroup plugins_importexport_native
 *
 * @brief Class that converts a Native XML document to a set of publication formats.
 */

import('lib.pkp.plugins.importexport.native.filter.NativeXmlRepresentationFilter');

use APP\facades\Repo;
use APP\submission\Submission;
use PKP\galley\Galley;

// FIXME: Add namespacing
// use DOMElement;

class NativeXmlArticleGalleyFilter extends NativeXmlRepresentationFilter
{
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
        return 'article_galleys'; // defined if needed in the future.
    }

    /**
     * Get the singular element name
     *
     * @return string
     */
    public function getSingularElementName()
    {
        return 'article_galley';
    }

    //
    // Implement template methods from PersistableFilter
    //
    /**
     * @copydoc PersistableFilter::getClassName()
     */
    public function getClassName()
    {
        return 'plugins.importexport.native.filter.NativeXmlArticleGalleyFilter';
    }


    /**
     * Handle a submission element
     *
     * @param DOMElement $node
     *
     * @return array Array of Galley objects
     */
    public function handleElement($node)
    {
        $deployment = $this->getDeployment();
        $context = $deployment->getContext();
        $submission = $deployment->getSubmission();
        assert($submission instanceof Submission);

        $submissionFileRefNodes = $node->getElementsByTagName('submission_file_ref');
        assert($submissionFileRefNodes->length <= 1);
        $addSubmissionFile = false;
        if ($submissionFileRefNodes->length == 1) {
            $fileNode = $submissionFileRefNodes->item(0);
            $newSubmissionFileId = $deployment->getSubmissionFileDBId($fileNode->getAttribute('id'));
            if ($newSubmissionFileId) {
                $addSubmissionFile = true;
            }
        }
        /** @var Galley $representation */
        $representation = parent::handleElement($node);

        for ($n = $node->firstChild; $n !== null; $n = $n->nextSibling) {
            if ($n instanceof DOMElement) {
                switch ($n->tagName) {
            case 'name':
                // Labels are not localized in OJS Galleys, but we use the <name locale="....">...</name> structure.
                $locale = $n->getAttribute('locale');
                if (empty($locale)) {
                    $locale = $submission->getLocale();
                }
                $representation->setLabel($n->textContent);
                $representation->setLocale($locale);
                break;
        }
            }
        }

        if ($addSubmissionFile) {
            $representation->setData('submissionFileId', $newSubmissionFileId);
        }
        Repo::galley()->dao->insert($representation);

        if ($addSubmissionFile) {
            // Update the submission file.
            $submissionFile = Repo::submissionFile()->get($newSubmissionFileId);
            Repo::submissionFile()->edit(
                $submissionFile,
                [
                    'assocType' => ASSOC_TYPE_REPRESENTATION,
                    'assocId' => $representation->getId(),
                ]
            );
        }

        // representation proof files
        return $representation;
    }
}
