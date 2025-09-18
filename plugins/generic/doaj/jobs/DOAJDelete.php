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
use PKP\context\Context;
use PKP\job\exceptions\JobException;
use PKP\jobs\BaseJob;
use PKP\plugins\PluginRegistry;

class DOAJDelete extends BaseJob
{
    public function __construct(
        protected string $doajId,
        protected int $publicationId,
        protected Context $context
    ) {
        parent::__construct();
    }

    public function handle()
    {
        $publication = Repo::publication()->get($this->publicationId);

        if (!$publication) {
            throw new JobException(JobException::INVALID_PAYLOAD);
        }

        // if publication not in context

        // check publication statuses

        PluginRegistry::register('importexport', new DOAJExportPlugin(), 'plugins/generic/doaj/DOAJExportPlugin', $this->context->getId());
        $doajExportPlugin = PluginRegistry::getPlugin('importexport', 'DOAJExportPlugin'); /** @var DOAJExportPlugin $doajExportPlugin */
        $result = $doajExportPlugin->deleteObject($this->doajId, $publication, $this->context);
        if (is_array($result) && count($result[0]) >= 1) {
            $msg = __($result[0][0], ['param' => $result[0][1]]);
            throw new JobException($msg);
        }
        dispatch(new DOAJRegister($doajId, $objects->getId(), $context));
    }
}
