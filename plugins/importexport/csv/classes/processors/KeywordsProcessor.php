<?php

/**
 * @file plugins/importexport/csv/classes/processors/KeywordsProcessor.php
 *
 * Copyright (c) 2014-2025 Simon Fraser University
 * Copyright (c) 2003-2025 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class KeywordsProcessor
 *
 * @ingroup plugins_importexport_csv
 *
 * @brief Processes the keywords data into the database.
 */

namespace APP\plugins\importexport\csv\classes\processors;

use APP\core\Application;
use APP\facades\Repo;
use PKP\controlledVocab\ControlledVocab;

class KeywordsProcessor
{
    /**
     * Processes data for Keywords
     */
    public static function process(object $data, int $publicationId): void
    {
        $keywordsList = array_map('trim', explode(';', $data->keywords));

        if (count($keywordsList) > 0) {
            Repo::controlledVocab()->insertBySymbolic(
                ControlledVocab::CONTROLLED_VOCAB_SUBMISSION_KEYWORD,
                [$data->locale => $keywordsList],
                Application::ASSOC_TYPE_PUBLICATION,
                $publicationId
            );
        }
    }
}
