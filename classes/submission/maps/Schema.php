<?php
/**
 * @file classes/submission/maps/Schema.inc.php
 *
 * Copyright (c) 2014-2020 Simon Fraser University
 * Copyright (c) 2000-2020 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class submission
 *
 * @brief Map submissions to the properties defined in the submission schema
 */

namespace APP\submission\maps;

use APP\core\Application;
use APP\submission\Submission;

class Schema extends \PKP\submission\maps\Schema
{
    /**
     * @copydoc \PKP\submission\maps\Schema::mapByProperties()
     */
    protected function mapByProperties(array $props, Submission $submission): array
    {
        $output = parent::mapByProperties($props, $submission);

        if (in_array('urlPublished', $props)) {
            $output['urlPublished'] = $this->request->getDispatcher()->url(
                $this->request,
                Application::ROUTE_PAGE,
                $this->context->getPath(),
                'article',
                'view',
                $submission->getBestId()
            );
        }

        $output = $this->schemaService->addMissingMultilingualValues($this->schemaService::SCHEMA_SUBMISSION, $output, $this->context->getSupportedSubmissionLocales());

        ksort($output);

        return $this->withExtensions($output, $submission);
    }
}
