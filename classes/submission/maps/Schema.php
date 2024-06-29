<?php
/**
 * @file classes/submission/maps/Schema.php
 *
 * Copyright (c) 2014-2020 Simon Fraser University
 * Copyright (c) 2000-2020 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class Schema
 *
 * @brief Map submissions to the properties defined in the submission schema
 */

namespace APP\submission\maps;

use APP\core\Application;
use APP\submission\Submission;
use Illuminate\Support\Collection;
use PKP\submission\PKPSubmission;

class Schema extends \PKP\submission\maps\Schema
{
    /**
     * @copydoc \PKP\submission\maps\Schema::mapByProperties()
     */
    protected function mapByProperties(array $props, Submission $submission, bool|Collection $anonymizeReviews = false): array
    {
        $output = parent::mapByProperties($props, $submission, $anonymizeReviews);

        if (in_array('urlPublished', $props)) {
            $output['urlPublished'] = $this->request->getDispatcher()->url(
                $this->request,
                Application::ROUTE_PAGE,
                $this->context->getPath(),
                'article',
                'view',
                [$submission->getBestId()]
            );
        }

        if (in_array('scheduledIn', $props)) {
            $output['scheduledIn'] = $submission->getData('status') == PKPSubmission::STATUS_SCHEDULED ?
                $submission->getCurrentPublication()->getData('issueId') : null;
        }

        $locales = $this->context->getSupportedSubmissionMetadataLocales();

        if (!in_array($primaryLocale = $submission->getData('locale'), $locales)) {
            $locales[] = $primaryLocale;
        }

        $output = $this->schemaService->addMissingMultilingualValues($this->schemaService::SCHEMA_SUBMISSION, $output, $locales);

        ksort($output);

        return $this->withExtensions($output, $submission);
    }

    protected function appSpecificProps(): array
    {
        return [
            'scheduledIn',
        ];
    }
}
