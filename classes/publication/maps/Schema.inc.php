<?php
/**
 * @file classes/publication/maps/Schema.inc.php
 *
 * Copyright (c) 2014-2020 Simon Fraser University
 * Copyright (c) 2000-2020 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class publication
 *
 * @brief Map publications to the properties defined in the publication schema
 */

namespace APP\publication\maps;

use APP\core\Services;
use APP\publication\Publication;

use PKP\core\PKPApplication;
use PKP\services\PKPSchemaService;

class Schema extends \PKP\publication\maps\Schema
{
    /** @copydoc \PKP\publication\maps\Schema::mapByProperties() */
    protected function mapByProperties(array $props, Publication $publication, bool $anonymize): array
    {
        $output = parent::mapByProperties($props, $publication, $anonymize);

        if (in_array('galleys', $props)) {
            if ($anonymize) {
                $output['galleys'] = [];
            } else {
                $galleyArgs = [
                    'publication' => $publication,
                    'request' => $this->request,
                    'submission' => $this->submission,
                ];
                $output['galleys'] = array_map(
                    function ($galley) use ($galleyArgs) {
                        return Services::get('galley')->getSummaryProperties($galley, $galleyArgs);
                    },
                    $publication->getData('galleys')
                );
            }
        }

        if (in_array('urlPublished', $props)) {
            $output['urlPublished'] = $this->request->getDispatcher()->url(
                $this->request,
                PKPApplication::ROUTE_PAGE,
                $this->context->getData('urlPath'),
                'article',
                'view',
                [
                    $this->submission->getBestId(),
                    'version',
                    $publication->getId(),
                ]
            );
        }

        $output = $this->schemaService->addMissingMultilingualValues(PKPSchemaService::SCHEMA_PUBLICATION, $output, $this->context->getSupportedSubmissionLocales());

        ksort($output);

        return $this->withExtensions($output, $publication);
    }
}
