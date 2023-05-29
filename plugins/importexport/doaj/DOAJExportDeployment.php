<?php

/**
 * @file plugins/importexport/doaj/DOAJExportDeployment.php
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2000-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class DOAJExportDeployment
 *
 * @brief Base class configuring the DOAJ export process to an
 * application's specifics.
 */

namespace APP\plugins\importexport\doaj;

// XML attributes
define('DOAJ_XMLNS_XSI', 'http://www.w3.org/2001/XMLSchema-instance');
define('DOAJ_XSI_SCHEMALOCATION', 'http://doaj.org/static/doaj/doajArticles.xsd');

class DOAJExportDeployment
{
    /** @var \PKP\context\Context The current import/export context */
    public $_context;

    /** @var \PKP\plugins\Plugin The current import/export plugin */
    public $_plugin;

    /**
     * Get the plugin cache
     *
     * @return \APP\plugins\PubObjectCache
     */
    public function getCache()
    {
        return $this->_plugin->getCache();
    }

    /**
     * Constructor
     *
     * @param \PKP\context\Context $context
     * @param \APP\plugins\importexport\doaj\DOAJExportPlugin $plugin
     */
    public function __construct($context, $plugin)
    {
        $this->setContext($context);
        $this->setPlugin($plugin);
    }

    //
    // Deployment items for subclasses to override
    //
    /**
     * Get the root element name
     *
     * @return string
     */
    public function getRootElementName()
    {
        return 'records';
    }

    /**
     * Get the schema instance URN
     *
     * @return string
     */
    public function getXmlSchemaInstance()
    {
        return DOAJ_XMLNS_XSI;
    }

    /**
     * Get the schema location URL
     *
     * @return string
     */
    public function getXmlSchemaLocation()
    {
        return DOAJ_XSI_SCHEMALOCATION;
    }

    /**
     * Get the schema filename.
     *
     * @return string
     */
    public function getSchemaFilename()
    {
        return 'doajArticles.xsd';
    }

    //
    // Getter/setters
    //
    /**
     * Set the import/export context.
     *
     * @param \PKP\context\Context $context
     */
    public function setContext($context)
    {
        $this->_context = $context;
    }

    /**
     * Get the import/export context.
     *
     * @return \PKP\context\Context
     */
    public function getContext()
    {
        return $this->_context;
    }

    /**
     * Set the import/export plugin.
     *
     * @param \PKP\plugins\Plugin $plugin
     */
    public function setPlugin($plugin)
    {
        $this->_plugin = $plugin;
    }

    /**
     * Get the import/export plugin.
     *
     * @return \PKP\plugins\Plugin
     */
    public function getPlugin()
    {
        return $this->_plugin;
    }
}
