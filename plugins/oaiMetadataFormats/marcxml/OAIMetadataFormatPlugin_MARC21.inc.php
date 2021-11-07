<?php

/**
 * @file plugins/oaiMetadataFormats/marcxml/OAIMetadataFormatPlugin_MARC21.inc.php
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2003-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class OAIMetadataFormatPlugin_MARC21
 * @ingroup oai_format
 *
 * @see OAI
 *
 * @brief marc21 metadata format plugin for OAI.
 */

use PKP\plugins\OAIMetadataFormatPlugin;

class OAIMetadataFormatPlugin_MARC21 extends OAIMetadataFormatPlugin
{
    /**
     * Get the name of this plugin. The name must be unique within
     * its category.
     *
     * @return string name of plugin
     */
    public function getName()
    {
        return 'OAIFormatPlugin_MARC21';
    }

    public function getDisplayName()
    {
        return __('plugins.OAIMetadata.marcxml.displayName');
    }

    public function getDescription()
    {
        return __('plugins.OAIMetadata.marcxml.description');
    }

    public function getFormatClass()
    {
        return 'OAIMetadataFormat_MARC21';
    }

    public static function getMetadataPrefix()
    {
        return 'marcxml';
    }

    public static function getSchema()
    {
        return 'http://www.loc.gov/standards/marcxml/schema/MARC21slim.xsd';
    }

    public static function getNamespace()
    {
        return 'http://www.loc.gov/MARC21/slim';
    }
}
