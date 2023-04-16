<?php
/**
 * @defgroup oai_format OAI Formats
 */

/**
 * @file plugins/oaiMetadataFormats/dc/OAIMetadataFormat_DC.php
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2003-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class OAIMetadataFormat_DC
 *
 * @ingroup oai_format
 *
 * @see OAI
 *
 * @brief OAI metadata format class -- Dublin Core.
 */

namespace APP\plugins\oaiMetadataFormats\dc;

class OAIMetadataFormat_DC extends \PKP\plugins\oaiMetadataFormats\dc\PKPOAIMetadataFormat_DC
{
    /**
     * @see lib/pkp/plugins/oaiMetadataFormats/dc/PKPOAIMetadataFormat_DC::toXml()
     *
     * @param null|mixed $format
     */
    public function toXml($record, $format = null)
    {
        $article = & $record->getData('article');
        return parent::toXml($article, $format);
    }
}

if (!PKP_STRICT_MODE) {
    class_alias('\APP\plugins\oaiMetadataFormats\dc\OAIMetadataFormat_DC', '\OAIMetadataFormat_DC');
}
