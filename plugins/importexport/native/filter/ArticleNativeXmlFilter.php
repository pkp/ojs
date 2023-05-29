<?php

/**
 * @file plugins/importexport/native/filter/ArticleNativeXmlFilter.php
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2000-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class ArticleNativeXmlFilter
 *
 * @ingroup plugins_importexport_native
 *
 * @brief Class that converts a Article to a Native XML document.
 */

namespace APP\plugins\importexport\native\filter;

use APP\submission\Submission;
use DOMElement;

class ArticleNativeXmlFilter extends \PKP\plugins\importexport\native\filter\SubmissionNativeXmlFilter
{
    //
    // Submission conversion functions
    //
    /**
     * Create and return a submission node.
     *
     * @param \DOMDocument $doc
     * @param Submission $submission
     *
     * @return DOMElement
     */
    public function createSubmissionNode($doc, $submission)
    {
        $deployment = $this->getDeployment();
        $submissionNode = parent::createSubmissionNode($doc, $submission);

        return $submissionNode;
    }
}
