<?php

/**
 * @file plugins/importexport/native/NativeImportExportDeployment.php
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2000-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class NativeImportExportDeployment
 *
 * @ingroup plugins_importexport_native
 *
 * @brief Class configuring the native import/export process to this
 * application's specifics.
 */

namespace APP\plugins\importexport\native;

use APP\core\Application;
use APP\issue\Issue;

class NativeImportExportDeployment extends \PKP\plugins\importexport\native\PKPNativeImportExportDeployment
{
    public $_issue;


    //
    // Deployment items for subclasses to override
    //
    /**
     * Get the submission node name
     *
     * @return string
     */
    public function getSubmissionNodeName()
    {
        return 'article';
    }

    /**
     * Get the submissions node name
     *
     * @return string
     */
    public function getSubmissionsNodeName()
    {
        return 'articles';
    }

    /**
     * Get the representation node name
     */
    public function getRepresentationNodeName()
    {
        return 'article_galley';
    }

    /**
     * Get the schema filename.
     *
     * @return string
     */
    public function getSchemaFilename()
    {
        return 'native.xsd';
    }

    /**
     * Set the import/export issue.
     *
     * @param Issue $issue
     */
    public function setIssue($issue)
    {
        $this->_issue = $issue;
    }

    /**
     * Get the import/export issue.
     *
     * @return Issue
     */
    public function getIssue()
    {
        return $this->_issue;
    }

    /**
     * @see PKPNativeImportExportDeployment::getObjectTypes()
     */
    protected function getObjectTypes()
    {
        return parent::getObjectTypes() + [
            Application::ASSOC_TYPE_JOURNAL => __('context.context'),
            Application::ASSOC_TYPE_ISSUE => __('issue.issue'),
            Application::ASSOC_TYPE_ISSUE_GALLEY => __('editor.issues.galley'),
            Application::ASSOC_TYPE_PUBLICATION => __('common.publication'),
            Application::ASSOC_TYPE_GALLEY => __('submission.galley'),
        ];
    }
}
