<?php

/**
 * @file plugins/generic/doaj/jobs/DOAJDelete.php
 *
 * Copyright (c) 2025 Simon Fraser University
 * Copyright (c) 2025 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class DOAJDelete
 *
 * @ingroup jobs
 *
 * @brief Delete publication with DOAJ.
 */

namespace APP\plugins\generic\doaj\jobs;

use APP\facades\Repo;
use APP\plugins\generic\doaj\DOAJExportPlugin;
use APP\publication\Publication;
use APP\submission\Submission;
use PKP\context\Context;
use PKP\job\exceptions\JobException;
use PKP\jobs\BaseJob;
use PKP\plugins\PluginRegistry;

class DOAJDelete extends BaseJob implements \PKP\queue\ContextAwareJob
{
    public function __construct(
        protected string $doajId,
        protected int $objectId,
        protected Context $context,
        protected string $jsonString
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

    public function handle()
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
        $result = $doajExportPlugin->deleteObject($this->doajId, $object, $this->context);
        if (is_array($result) && count($result[0]) >= 1) {
            $msg = __($result[0][0], ['param' => $result[0][1]]);
            throw new JobException($msg);
        }
        dispatch(new DOAJRegister($this->jsonString, $this->objectId, $this->context))->delay(now()->addMinutes(10));
    }
}
