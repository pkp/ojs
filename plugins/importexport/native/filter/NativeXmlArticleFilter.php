<?php

/**
 * @file plugins/importexport/native/filter/NativeXmlArticleFilter.php
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2000-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class NativeXmlArticleFilter
 *
 * @ingroup plugins_importexport_native
 *
 * @brief Class that converts a Native XML document to a set of articles.
 */

namespace APP\plugins\importexport\native\filter;

use APP\core\Application;
use PKP\filter\Filter;
use PKP\plugins\importexport\PKPImportExportFilter;

class NativeXmlArticleFilter extends \PKP\plugins\importexport\native\filter\NativeXmlSubmissionFilter
{
    /**
     * Get the import filter for a given element.
     *
     * @param string $elementName Name of XML element
     *
     * @return Filter
     */
    public function getImportFilter($elementName)
    {
        $deployment = $this->getDeployment();
        $submission = $deployment->getSubmission();
        switch ($elementName) {
            case 'submission_file':
                $importClass = 'SubmissionFile';
                break;
            case 'publication':
                $importClass = 'Publication';
                break;
            default:
                $importClass = null; // Suppress scrutinizer warn
                $deployment->addWarning(Application::ASSOC_TYPE_SUBMISSION, $submission->getId(), __('plugins.importexport.common.error.unknownElement', ['param' => $elementName]));
        }
        // Caps on class name for consistency with imports, whose filter
        // group names are generated implicitly.
        $currentFilter = PKPImportExportFilter::getFilter('native-xml=>' . $importClass, $deployment);
        return $currentFilter;
    }
}
