<?php

/**
 * @file plugins/oaiMetadataFormats/marc/OAIMetadataFormatPlugin_MARC.inc.php
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2003-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class OAIMetadataFormatPlugin_MARC
 * @ingroup oai_format
 *
 * @see OAI
 *
 * @brief marc metadata format plugin for OAI.
 */

use PKP\plugins\OAIMetadataFormatPlugin;

class OAIMetadataFormatPlugin_MARC extends OAIMetadataFormatPlugin
{
    /**
     * Get the name of this plugin. The name must be unique within
     * its category.
     *
     * @return string name of plugin
     */
    public function getName()
    {
        return 'OAIFormatPlugin_MARC';
    }

    public function getDisplayName()
    {
        return __('plugins.OAIMetadata.marc.displayName');
    }

    public function getDescription()
    {
        return __('plugins.OAIMetadata.marc.description');
    }

    public function getFormatClass()
    {
        return 'OAIMetadataFormat_MARC';
    }

    public static function getMetadataPrefix()
    {
        return 'oai_marc';
    }

    public static function getSchema()
    {
        return 'http://www.openarchives.org/OAI/1.1/oai_marc.xsd';
    }

    public static function getNamespace()
    {
        return 'http://www.openarchives.org/OAI/1.1/oai_marc';
    }
}
