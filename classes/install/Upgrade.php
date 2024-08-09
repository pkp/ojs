<?php

/**
 * @file classes/install/Upgrade.php
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2003-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class Upgrade
 *
 * @ingroup install
 *
 * @brief Perform system upgrade.
 */

namespace APP\install;

use APP\core\Application;
use APP\template\TemplateManager;
use Illuminate\Support\Facades\Schema;
use PKP\db\DAORegistry;
use PKP\install\Installer;
use PKP\navigationMenu\NavigationMenuItemDAO;
use PKP\submissionFile\SubmissionFile;

class Upgrade extends Installer
{
    protected $appEmailTemplateVariableNames = [
        'contextName' => 'journalName',
        'contextUrl' => 'journalUrl',
        'contextSignature' => 'journalSignature',
    ];

    /**
     * Constructor.
     *
     * @param array $params upgrade parameters
     * @param string $installFile Name of XML descriptor to install
     * @param bool $isPlugin True iff the installer is for a plugin.
     */
    public function __construct($params, $installFile = 'upgrade.xml', $isPlugin = false)
    {
        parent::__construct($installFile, $params, $isPlugin);
    }


    /**
     * Returns true iff this is an upgrade process.
     *
     * @return bool
     */
    public function isUpgrade()
    {
        return true;
    }

    //
    // Upgrade actions
    //

    /**
     * Rebuild the search index.
     *
     * @return bool
     */
    public function rebuildSearchIndex()
    {
        $submissionSearchIndex = Application::getSubmissionSearchIndex();
        $submissionSearchIndex->rebuildIndex();
        return true;
    }

    /**
     * Clear the CSS cache files (needed when changing LESS files)
     *
     * @return bool
     */
    public function clearCssCache()
    {
        $request = Application::get()->getRequest();
        $templateMgr = TemplateManager::getManager($request);
        $templateMgr->clearCssCache();
        return true;
    }

    /**
     * If StaticPages table exists we should port the data as NMIs
     *
     * @return bool
     */
    public function migrateStaticPagesToNavigationMenuItems()
    {
        if (Schema::hasTable('static_pages')) {
            $contextDao = Application::getContextDAO();
            $navigationMenuItemDao = DAORegistry::getDAO('NavigationMenuItemDAO'); /** @var NavigationMenuItemDAO $navigationMenuItemDao */

            $staticPagesDao = new \APP\plugins\generic\staticPages\classes\StaticPagesDAO();

            $contexts = $contextDao->getAll();
            while ($context = $contexts->next()) {
                $contextStaticPages = $staticPagesDao->getByContextId($context->getId())->toAssociativeArray();
                foreach ($contextStaticPages as $staticPage) {
                    $retNMIId = $navigationMenuItemDao->portStaticPage($staticPage);
                    if ($retNMIId) {
                        $staticPagesDao->deleteById($staticPage->getId());
                    } else {
                        error_log('WARNING: The StaticPage "' . $staticPage->getLocalizedTitle() . '" uses a path (' . $staticPage->getPath() . ') that conflicts with an existing Custom Navigation Menu Item path. Skipping this StaticPage.');
                    }
                }
            }
        }

        return true;
    }

    /**
     * Get the directory of a file based on its file stage
     *
     * @param int $fileStage One of SubmissionFile::SUBMISSION_FILE_ constants
     *
     * @return string
     */
    public function _fileStageToPath($fileStage)
    {
        static $fileStagePathMap = [
            SubmissionFile::SUBMISSION_FILE_SUBMISSION => 'submission',
            SubmissionFile::SUBMISSION_FILE_NOTE => 'note',
            SubmissionFile::SUBMISSION_FILE_REVIEW_FILE => 'submission/review',
            SubmissionFile::SUBMISSION_FILE_REVIEW_ATTACHMENT => 'submission/review/attachment',
            SubmissionFile::SUBMISSION_FILE_REVIEW_REVISION => 'submission/review/revision',
            SubmissionFile::SUBMISSION_FILE_FINAL => 'submission/final',
            SubmissionFile::SUBMISSION_FILE_COPYEDIT => 'submission/copyedit',
            SubmissionFile::SUBMISSION_FILE_DEPENDENT => 'submission/proof',
            SubmissionFile::SUBMISSION_FILE_PROOF => 'submission/proof',
            SubmissionFile::SUBMISSION_FILE_PRODUCTION_READY => 'submission/productionReady',
            SubmissionFile::SUBMISSION_FILE_ATTACHMENT => 'attachment',
            SubmissionFile::SUBMISSION_FILE_QUERY => 'submission/query',
        ];

        if (!isset($fileStagePathMap[$fileStage])) {
            throw new \Exception('A file assigned to the file stage ' . $fileStage . ' could not be migrated.');
        }

        return $fileStagePathMap[$fileStage];
    }
}

if (!PKP_STRICT_MODE) {
    class_alias('\APP\install\Upgrade', '\Upgrade');
}
