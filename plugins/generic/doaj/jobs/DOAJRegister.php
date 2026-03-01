<?php

/**
 * @file plugins/generic/doaj/jobs/DOAJRegister.php
 *
 * Copyright (c) 2025 Simon Fraser University
 * Copyright (c) 2025 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class DOAJRegister
 *
 * @ingroup jobs
 *
 * @brief Register article or publication with DOAJ.
 */

namespace APP\plugins\generic\doaj\jobs;

use APP\facades\Repo;
use APP\plugins\generic\doaj\DOAJExportPlugin;
use APP\plugins\PubObjectsExportPlugin;
use APP\publication\Publication;
use APP\submission\Submission;
use PKP\context\Context;
use PKP\job\exceptions\JobException;
use PKP\jobs\BaseJob;
use PKP\plugins\PluginRegistry;

class DOAJRegister extends BaseJob implements \PKP\queue\ContextAwareJob
{
    public function __construct(
        protected string $jsonString,
        protected int $objectId,
        protected Context $context
    ) {
        parent::__construct();
    }

    /**
     * Get the context ID for this job.
     */
    public function getContextId(): int
    {
        return $this->context->getId();
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        /** @var Submission|Publication $object */
        $object = $this->context->getData(Context::SETTING_DOI_VERSIONING) ?
            Repo::publication()->get($this->objectId) :
            Repo::submission()->get($this->objectId);

        if (!$object) {
            throw new JobException(JobException::INVALID_PAYLOAD);
        }

        PluginRegistry::register('importexport', new DOAJExportPlugin(), 'plugins/generic/doaj/DOAJExportPlugin', $this->context->getId());
        $doajExportPlugin = PluginRegistry::getPlugin('importexport', 'DOAJExportPlugin'); /** @var DOAJExportPlugin $doajExportPlugin */

        $status = $object->getData($doajExportPlugin->getDepositStatusSettingName());
        if ($status == PubObjectsExportPlugin::EXPORT_STATUS_REGISTERED) {
            return;
        }

        $result = $doajExportPlugin->registerObject($this->jsonString, $object, $this->context);
        if (is_array($result) && count($result[0]) >= 1) {
            $msg = __($result[0][0], ['param' => $result[0][1]]);
            throw new JobException($msg);
        }
    }
}
